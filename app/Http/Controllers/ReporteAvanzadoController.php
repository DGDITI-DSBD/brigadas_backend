<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\carpeta_respuestas_t;
use App\Models\BrDirectorio;
use App\Services\ReporteAvanzadoService;
use App\Http\Resources\EspecialistaReporteResource;
use Illuminate\Support\Facades\Auth;

class ReporteAvanzadoController extends Controller
{
    use carpeta_respuestas_t;

    protected $Auth;
    protected $service;
    protected $respuesta_padre = 'reportes';

    // Roles con acceso completo (incluye exportación)
    private const ROLES_ADMIN = ['ADMINISTRADOR', 'COORDINADOR'];

    public function __construct(ReporteAvanzadoService $service)
    {
        $this->Auth = Auth::guard('api');
        $this->service = $service;
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /api/v1/reportes/asesorias
    // Devuelve todas las asesorías en formato plano para los reportes
    // ─────────────────────────────────────────────────────────────────
    public function listar_asesorias(Request $request)
    {
        try {
            $desde = $request->get('desde');
            $hasta = $request->get('hasta');

            $asesorias = $this->service->listarAsesorias($desde, $hasta);

            $key = $asesorias->isEmpty() ? 'asesorias_sin_datos' : 'asesorias_success';
            return response()->json($this->typeResponse($this->respuesta_padre, $key, $asesorias->values()));

        } catch (\Exception $e) {
            return response()->json(
                $this->typeResponse($this->respuesta_padre, 'reporte_individual_fail', ['error' => $e->getMessage()])
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /api/v1/reportes/especialista
    // Parámetros: directorio_id (para admin/coord), desde, hasta, municipio_id
    // ─────────────────────────────────────────────────────────────────
    public function reporte_individual_especialista(Request $request)
    {
        try {
            $user = $this->Auth->user();
            $tipo_usuario = $user->tipo_usuario;

            $desde = $request->get('desde');
            $hasta = $request->get('hasta');
            $municipio_id = $request->get('municipio_id') ? (int) $request->get('municipio_id') : null;

            // ── Resolver directorio según el rol ──────────────────────
            if ($tipo_usuario === 'ESPECIALISTA') {
                $directorio = $user->directorio;
                if (!$directorio) {
                    return response()->json(
                        $this->typeResponse($this->respuesta_padre, 'especialista_no_directorio', [])
                    );
                }
            } else {
                // ADMINISTRADOR / COORDINADOR / BRIGADISTA pueden pasar directorio_id
                $directorio_id = $request->get('directorio_id');
                if (!$directorio_id) {
                    return response()->json(
                        $this->typeResponse($this->respuesta_padre, 'directorio_requerido', [])
                    );
                }
                $directorio = BrDirectorio::find($directorio_id);
                if (!$directorio) {
                    return response()->json(
                        $this->typeResponse($this->respuesta_padre, 'especialista_no_directorio', [])
                    );
                }
            }

            // ── Delegar lógica al servicio ───────────────────────────
            $reporte = $this->service->generarReporteEspecialista($directorio, $desde, $hasta, $municipio_id);

            // ── Retornar usando el Resource ──────────────────────────
            if (empty($reporte['lista_asesorias']) || count($reporte['lista_asesorias']) === 0) {
                return response()->json(
                    $this->typeResponse($this->respuesta_padre, 'reporte_individual_sin_datos', $reporte)
                );
            }

            return (new EspecialistaReporteResource($reporte))->response();

        } catch (\Exception $e) {
            return response()->json(
                $this->typeResponse($this->respuesta_padre, 'reporte_individual_fail', ['error' => $e->getMessage()])
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /api/v1/reportes/especialistas  (listado para Admin/Coord)
    // ─────────────────────────────────────────────────────────────────
    public function listar_especialistas(Request $request)
    {
        try {
            $desde = $request->get('desde');
            $hasta = $request->get('hasta');

            $lista = $this->service->listarEspecialistas($desde, $hasta);

            $key = $lista->isEmpty() ? 'lista_especialistas_sin_datos' : 'lista_especialistas_success';
            return response()->json($this->typeResponse($this->respuesta_padre, $key, $lista->values()));

        } catch (\Exception $e) {
            return response()->json(
                $this->typeResponse($this->respuesta_padre, 'reporte_individual_fail', ['error' => $e->getMessage()])
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /api/v1/reportes/exportar
    // RBAC: Solo ADMINISTRADOR y COORDINADOR
    // ─────────────────────────────────────────────────────────────────
    public function exportar_csv(Request $request)
    {
        $tipo_usuario = $this->Auth->user()->tipo_usuario;

        // ── Verificación RBAC ────────────────────────────────────────
        if (!in_array($tipo_usuario, self::ROLES_ADMIN)) {
            return response()->json(
                $this->typeResponse($this->respuesta_padre, 'acceso_denegado', []),
                403
            );
        }

        $desde = $request->get('desde');
        $hasta = $request->get('hasta');
        $directorio_id = $request->get('directorio_id');

        // Construir el CSV en memoria
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="reporte_asesorias_' . now()->format('Ymd_His') . '.csv"',
        ];

        $directorio = $directorio_id ? BrDirectorio::find($directorio_id) : null;

        $callback = function () use ($directorio, $desde, $hasta) {
            $handle = fopen('php://output', 'w');
            // BOM para Excel
            fputs($handle, "\xEF\xBB\xBF");

            // ── Encabezados ──────────────────────────────────────────
            fputcsv($handle, ['ID', 'Especialista', 'Paciente', 'Municipio', 'Estado', 'Canalización', 'Fecha']);

            if ($directorio) {
                $reporte = app(ReporteAvanzadoService::class)
                    ->generarReporteEspecialista($directorio, $desde, $hasta);

                foreach ($reporte['lista_asesorias'] as $row) {
                    fputcsv($handle, [
                        $row['id'],
                        trim($directorio->primer_ap_dir . ' ' . $directorio->segundo_ap_dir . ' ' . $directorio->nombres),
                        $row['paciente'] ?? '',
                        $row['municipio'] ?? '',
                        $row['estado_asesoria'] ?? '',
                        $row['canalizacion'] ?? '',
                        $row['fecha'] ?? '',
                    ]);
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
