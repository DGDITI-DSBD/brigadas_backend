<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\carpeta_respuestas_t;

use App\Models\BrDirectorio;
use App\Models\BrAsesoria;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class reporteController extends Controller
{
    use carpeta_respuestas_t;

    protected $Auth;
    protected $respuesta_padre = 'reportes';
    protected $respuesta_hijo = null;
    protected $construir_respuesta = [];

    public function __construct()
    {
        $this->Auth = Auth::guard('api');
    }

    /**
     * GET /api/v1/reportes/especialista
     *
     * Query params opcionales:
     *   - desde        (YYYY-MM-DD)
     *   - hasta        (YYYY-MM-DD)
     *   - directorio_id (solo ADMINISTRADOR / COORDINADOR)
     *
     * Roles soportados:
     *   - ESPECIALISTA   → devuelve sus propias métricas
     *   - ADMINISTRADOR  → requiere directorio_id en query param
     *   - COORDINADOR    → requiere directorio_id en query param
     */
    public function reporte_individual_especialista(Request $request)
    {
        try {

            $tipo_usuario = $this->Auth->user()->tipo_usuario;
            $desde = $request->get('desde');
            $hasta = $request->get('hasta');

            // ── Resolver el directorio_id según el rol ──────────────────
            if ($tipo_usuario === 'ESPECIALISTA') {

                $directorio = $this->Auth->user()->directorios;

                if (!$directorio) {
                    $this->respuesta_hijo = 'especialista_no_directorio';
                    $this->construir_respuesta = [];
                    return response()->json(
                        $this->typeResponse($this->respuesta_padre, $this->respuesta_hijo, $this->construir_respuesta)
                    );
                }

                $directorio_id = $directorio->id;

            } else {
                // ADMINISTRADOR / COORDINADOR deben enviar directorio_id
                $directorio_id = $request->get('directorio_id');

                if (!$directorio_id) {
                    $this->respuesta_hijo = 'directorio_requerido';
                    $this->construir_respuesta = [];
                    return response()->json(
                        $this->typeResponse($this->respuesta_padre, $this->respuesta_hijo, $this->construir_respuesta)
                    );
                }

                $directorio = BrDirectorio::find($directorio_id);

                if (!$directorio) {
                    $this->respuesta_hijo = 'especialista_no_directorio';
                    $this->construir_respuesta = [];
                    return response()->json(
                        $this->typeResponse($this->respuesta_padre, $this->respuesta_hijo, $this->construir_respuesta)
                    );
                }
            }

            // ── Query base de asesorías del especialista ─────────────────
            $query = BrAsesoria::where('asesor_atendio_id', $directorio_id)
                ->with([
                    'paciente:id,primer_ap,segundo_ap,nombres,ct_municipio,municipio',
                    'asesoria_canalizacion:id,descripcion',
                ]);

            // Filtros opcionales de fecha
            if ($desde) {
                $query->whereDate('created_at', '>=', $desde);
            }
            if ($hasta) {
                $query->whereDate('created_at', '<=', $hasta);
            }

            $asesorias = $query->orderBy('created_at', 'desc')->get();

            if ($asesorias->isEmpty()) {
                $this->respuesta_hijo = 'reporte_individual_sin_datos';
                $this->construir_respuesta = $this->_buildPayload($directorio, collect(), $desde, $hasta);
                return response()->json(
                    $this->typeResponse($this->respuesta_padre, $this->respuesta_hijo, $this->construir_respuesta)
                );
            }

            // ── Construir payload con métricas ───────────────────────────
            $this->respuesta_hijo = 'reporte_individual_success';
            $this->construir_respuesta = $this->_buildPayload($directorio, $asesorias, $desde, $hasta);

            return response()->json(
                $this->typeResponse($this->respuesta_padre, $this->respuesta_hijo, $this->construir_respuesta)
            );

        } catch (\Exception $e) {

            $this->respuesta_hijo = 'reporte_individual_fail';
            $this->construir_respuesta = ['error' => $e->getMessage()];
            return response()->json(
                $this->typeResponse($this->respuesta_padre, $this->respuesta_hijo, $this->construir_respuesta)
            );
        }
    }

    // ── Helpers privados ──────────────────────────────────────────────────

    /**
     * Construye el payload de métricas dado el directorio y la colección de asesorías.
     */
    private function _buildPayload(BrDirectorio $directorio, $asesorias, $desde, $hasta): array
    {
        // Nombre completo del especialista
        $nombre_especialista = trim(
            $directorio->primer_ap_dir . ' ' .
            $directorio->segundo_ap_dir . ' ' .
            $directorio->nombres
        );

        // Tipo de especialidad (relación lazy)
        $especialidad = $directorio->tipoAsesoria
            ? $directorio->tipoAsesoria->descripcion
            : null;

        // Resumen general
        $total_asesorias = $asesorias->count();
        $pacientes_ids = $asesorias->pluck('paciente_id')->unique()->count();
        $asesorias_abiertas = $asesorias->where('estado_asesoria', 'ABIERTA')->count();
        $asesorias_cerradas = $asesorias->where('estado_asesoria', 'CERRADA')->count();
        $asesorias_canalizadas = $asesorias->where('estado_asesoria', 'CANALIZADO')->count();
        $asesorias_pendientes = $asesorias->where('estado_asesoria', 'PENDIENTE')->count();

        // Municipios únicos atendidos
        $municipios_unicos = $asesorias
            ->filter(fn($a) => $a->paciente)
            ->pluck('paciente.municipio')
            ->filter()
            ->unique()
            ->count();

        // Distribución por municipio
        $por_municipio = $asesorias
            ->filter(fn($a) => $a->paciente && $a->paciente->municipio)
            ->groupBy(fn($a) => $a->paciente->municipio)
            ->map(fn($grupo, $municipio) => [
                'municipio' => $municipio,
                'cantidad' => $grupo->count(),
            ])
            ->values();

        // Distribución por mes (YYYY-MM)
        $por_mes = $asesorias
            ->groupBy(fn($a) => substr($a->created_at, 0, 7))
            ->map(fn($grupo, $mes) => [
                'mes' => $mes,
                'cantidad' => $grupo->count(),
            ])
            ->sortKeys()
            ->values();

        // Distribución por estado de asesoría
        $por_estado = $asesorias
            ->groupBy('estado_asesoria')
            ->map(fn($grupo, $estado) => [
                'estado' => $estado,
                'cantidad' => $grupo->count(),
            ])
            ->values();

        // Lista simplificada de asesorías
        $lista_asesorias = $asesorias->map(fn($a) => [
            'id' => $a->id,
            'paciente' => $a->paciente
                ? trim($a->paciente->primer_ap . ' ' . $a->paciente->segundo_ap . ' ' . $a->paciente->nombres)
                : null,
            'municipio' => $a->paciente ? $a->paciente->municipio : null,
            'estado_asesoria' => $a->estado_asesoria,
            'canalizacion' => $a->asesoria_canalizacion ? $a->asesoria_canalizacion->descripcion : null,
            'created_at' => substr($a->created_at, 0, 10),
        ]);

        return [
            'filtros' => [
                'desde' => $desde,
                'hasta' => $hasta,
            ],
            'especialista' => [
                'id' => $directorio->id,
                'nombre' => $nombre_especialista,
                'especialidad' => $especialidad,
                'email' => $directorio->email,
                'telefono' => $directorio->telefono,
            ],
            'resumen' => [
                'total_asesorias' => $total_asesorias,
                'total_pacientes' => $pacientes_ids,
                'asesorias_abiertas' => $asesorias_abiertas,
                'asesorias_cerradas' => $asesorias_cerradas,
                'asesorias_canalizadas' => $asesorias_canalizadas,
                'asesorias_pendientes' => $asesorias_pendientes,
                'municipios_atendidos' => $municipios_unicos,
            ],
            'por_municipio' => $por_municipio,
            'por_mes' => $por_mes,
            'por_estado' => $por_estado,
            'lista_asesorias' => $lista_asesorias,
        ];
    }
}
