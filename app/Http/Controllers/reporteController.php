<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\carpeta_respuestas_t;
use App\Models\BrDirectorio;
use App\Services\ReporteService;
use Illuminate\Support\Facades\Auth;

class reporteController extends Controller
{
    use carpeta_respuestas_t;

    protected $Auth;
    protected $reporteService;
    protected $respuesta_padre = 'reportes';
    protected $respuesta_hijo = null;
    protected $construir_respuesta = [];

    public function __construct(ReporteService $reporteService)
    {
        $this->Auth = Auth::guard('api');
        $this->reporteService = $reporteService;
    }

    /**
     * GET /api/v1/reportes/especialista
     */
    public function reporte_individual_especialista(Request $request)
    {
        try {
            $user = $this->Auth->user();
            $tipo_usuario = $user->tipo_usuario;
            $desde = $request->get('desde');
            $hasta = $request->get('hasta');

            // ── Resolver el directorio_id según el rol ──────────────────
            if ($tipo_usuario === 'ESPECIALISTA') {
                $directorio = $user->directorios;
                if (!$directorio) {
                    return response()->json($this->typeResponse($this->respuesta_padre, 'especialista_no_directorio', []));
                }
                $directorio_id = $directorio->id;
            } else {
                $directorio_id = $request->get('directorio_id');
                if (!$directorio_id) {
                    return response()->json($this->typeResponse($this->respuesta_padre, 'directorio_requerido', []));
                }
                $directorio = BrDirectorio::find($directorio_id);
                if (!$directorio) {
                    return response()->json($this->typeResponse($this->respuesta_padre, 'especialista_no_directorio', []));
                }
            }

            // ── Delegar lógica al servicio ──────────────────────────────
            $reporte = $this->reporteService->generarReporteEspecialista($directorio, $desde, $hasta);

            $this->respuesta_hijo = empty($reporte['lista_asesorias']) ? 'reporte_individual_sin_datos' : 'reporte_individual_success';

            return response()->json($this->typeResponse($this->respuesta_padre, $this->respuesta_hijo, $reporte));

        } catch (\Exception $e) {
            return response()->json($this->typeResponse($this->respuesta_padre, 'reporte_individual_fail', ['error' => $e->getMessage()]));
        }
    }

    /**
     * GET /api/v1/reportes/exportar
     * Restricción: Solo ADMINISTRADOR o COORDINADOR
     */
    public function exportar_csv(Request $request)
    {
        $tipo_usuario = $this->Auth->user()->tipo_usuario;

        if (!in_array($tipo_usuario, ['ADMINISTRADOR', 'COORDINADOR'])) {
            return response()->json($this->typeResponse($this->respuesta_padre, 'acceso_denegado', []), 403);
        }

        // Simulación de exportación para cumplimiento de reglas de negocio
        return response()->json([
            'ok' => true,
            'message' => 'Exportación autorizada y en proceso para rol administrativo',
            'download_url' => '/api/v1/reportes/download-mock-sample'
        ]);
    }
}
