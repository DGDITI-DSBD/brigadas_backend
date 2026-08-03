<?php

namespace App\Http\Controllers;

use App\Models\BrAsesoria;
use Illuminate\Http\Request;
use App\Traits\carpeta_respuestas_t;
use App\Models\BrDirectorio;
use App\Services\ReporteAvanzadoService;
use App\Http\Resources\EspecialistaReporteResource;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

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
                $directorio = $user->directorios()->first();
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

    // ─────────────────────────────────────────────────────────────────
    // GET /api/v1/reportes/tablero/pdf
    // Genera PDF del Tablero General con diseño institucional
    // Parámetros: desde, hasta (opcionales)
    // ─────────────────────────────────────────────────────────────────
    public function tablero_pdf(Request $request)
    {
        try {
            $desde = $request->get('desde');
            $hasta = $request->get('hasta');

            $datos = $this->service->prepararDatosTablero($desde, $hasta);

            $pdf = Pdf::loadView('pdf.tablero-general', $datos)
                ->setPaper('a4', 'portrait');

            $filename = 'Tablero_General_' . now()->format('Y-m-d_His') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json(
                $this->typeResponse($this->respuesta_padre, 'reporte_individual_fail', ['error' => $e->getMessage()]),
                500
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // GET /api/v1/reportes/chart/pdf?tipo=especialidad|municipio|mensual
    // Genera PDF de una gráfica individual del Tablero General
    // Parámetros: tipo (requerido), desde, hasta (opcionales)
    // ─────────────────────────────────────────────────────────────────
    public function chart_pdf(Request $request)
    {
        $tipo = $request->get('tipo');
        $validos = ['especialidad', 'municipio', 'mensual'];
        if (!in_array($tipo, $validos)) {
            return response()->json(
                $this->typeResponse($this->respuesta_padre, 'reporte_individual_fail', ['error' => 'tipo inválido. Use: especialidad, municipio o mensual']),
                400
            );
        }
        try {
            $desde = $request->get('desde');
            $hasta = $request->get('hasta');
            $datos = $this->service->prepararDatosTablero($desde, $hasta);

            $vistas = [
                'especialidad' => 'pdf.chart-especialidad',
                'municipio' => 'pdf.chart-municipio',
                'mensual' => 'pdf.chart-mensual',
            ];
            $nombres = [
                'especialidad' => 'Asesorias_Por_Especialidad',
                'municipio' => 'Asesorias_Por_Municipio',
                'mensual' => 'Distribucion_Mensual_Especialidad',
            ];

            $pdf = Pdf::loadView($vistas[$tipo], $datos)
                ->setPaper('a4', 'portrait');

            $filename = $nombres[$tipo] . '_' . now()->format('Y-m-d_His') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json(
                $this->typeResponse($this->respuesta_padre, 'reporte_individual_fail', ['error' => $e->getMessage()]),
                500
            );
        }
    }


    public function reporte_por_mes_pdf(Request $request)
    {
        try {
            // Obtener el año (por defecto el año actual)
            $year = $request->get('year', date('Y'));

            // Obtener todas las asesorías del año con sus relaciones
            $asesorias = BrAsesoria::with([
                'asesoria_canalizacion:id,descripcion'
            ])
                ->whereYear('fecha_asesoria', $year)
                ->where('estado_registro', 1) // Ajusta según tu lógica de negocio
                ->get();

            // Nombres de meses en español
            $meses = [
                1 => 'Enero',
                2 => 'Febrero',
                3 => 'Marzo',
                4 => 'Abril',
                5 => 'Mayo',
                6 => 'Junio',
                7 => 'Julio',
                8 => 'Agosto',
                9 => 'Septiembre',
                10 => 'Octubre',
                11 => 'Noviembre',
                12 => 'Diciembre'
            ];

            // Inicializar estructura de datos
            $monthlyData = [];
            $annualSummary = [];

            // Inicializar todos los meses
            for ($month = 1; $month <= 12; $month++) {
                $monthlyData[$month] = [
                    'month' => $meses[$month],
                    'rows' => [],
                    'total' => 0
                ];
            }

            // Procesar asesorías
            foreach ($asesorias as $asesoria) {
                $month = (int) date('m', strtotime($asesoria->fecha_asesoria));
                $tipoAsesoria = $asesoria->asesoria_canalizacion->descripcion ?? 'Sin clasificar';

                // Inicializar el tipo de asesoría si no existe en el mes
                if (!isset($monthlyData[$month]['rows'][$tipoAsesoria])) {
                    $monthlyData[$month]['rows'][$tipoAsesoria] = [
                        'service' => $tipoAsesoria,
                        'count' => 0
                    ];
                }

                // Incrementar contador mensual
                $monthlyData[$month]['rows'][$tipoAsesoria]['count']++;
                $monthlyData[$month]['total']++;

                // Acumular para resumen anual
                if (!isset($annualSummary[$tipoAsesoria])) {
                    $annualSummary[$tipoAsesoria] = [
                        'service' => $tipoAsesoria,
                        'count' => 0
                    ];
                }
                $annualSummary[$tipoAsesoria]['count']++;
            }

            // Preparar datos para la respuesta
            $monthlyTables = [];

            // Convertir a array indexado y filtrar meses sin datos
            for ($month = 1; $month <= 12; $month++) {
                if ($monthlyData[$month]['total'] > 0) {
                    $monthlyData[$month]['rows'] = array_values($monthlyData[$month]['rows']);
                    $monthlyTables[] = $monthlyData[$month];
                }
            }

            // Ordenar resumen anual por cantidad (mayor a menor)
            $annualSummary = array_values($annualSummary);
            usort($annualSummary, function ($a, $b) {
                return $b['count'] - $a['count'];
            });

            // Calcular total anual
            $annualTotal = array_sum(array_column($annualSummary, 'count'));

            // Estructurar respuesta final
            $responseData = [
                'title' => "Reporte de Asesorías por Mes $year",
                'year' => (int) $year,
                'monthlyTables' => $monthlyTables,
                'annualSummary' => $annualSummary,
                'annualTotal' => $annualTotal
            ];

            // Respuesta exitosa
            return response()->json([
                'status' => 'success',
                'message' => 'Reporte por mes generado exitosamente',
                'data' => $responseData
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error en reporte_por_mes_pdf: ' . $e->getMessage());

            // Respuesta de error
            return response()->json([
                'status' => 'error',
                'message' => 'Error al generar el reporte por mes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
