<?php

namespace App\Http\Controllers;

use App\Models\BrAsesoria;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\carpeta_respuestas_t;
use App\Models\BrDirectorio;
use App\Services\ReporteAvanzadoService;
use App\Http\Resources\EspecialistaReporteResource;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
            // SOLO asesorías que TIENEN un tipo de asesoría asignado
            $asesorias = BrAsesoria::with([
                'asesoria_canalizacion:id,descripcion'
            ])
                ->whereYear('fecha_asesoria', $year)
                ->where('estado_registro', 1)
                ->whereNotNull('tipo_asesoria_canaliza_id')
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

            // ========== CATÁLOGO DE ESPECIALIDADES EN ORDEN ==========
            $catalogoEspecialidades = [
                'MEDICINA GENERAL',
                'FISIOTERAPIA',
                'TRABAJO SOCIAL',
                'GERONTOLOGÍA',
                'PSICOLÓGICA',
                'JURÍDICA',
                'NUTRICIONAL',
            ];

            // Inicializar estructura de datos
            $monthlyData = [];
            $annualSummary = [];

            // Inicializar TODAS las especialidades en 0 para el resumen anual
            foreach ($catalogoEspecialidades as $especialidad) {
                $annualSummary[$especialidad] = [
                    'service' => $especialidad,
                    'count' => 0
                ];
            }

            // Inicializar todos los meses con TODAS las especialidades en 0
            for ($month = 1; $month <= 12; $month++) {
                $monthlyData[$month] = [
                    'month' => $meses[$month],
                    'rows' => [],
                    'total' => 0
                ];

                // Inicializar cada especialidad en 0 para este mes
                foreach ($catalogoEspecialidades as $especialidad) {
                    $monthlyData[$month]['rows'][$especialidad] = [
                        'service' => $especialidad,
                        'count' => 0
                    ];
                }
            }

            // Procesar asesorías
            foreach ($asesorias as $asesoria) {
                $month = (int) date('m', strtotime($asesoria->fecha_asesoria));
                $tipoAsesoria = $asesoria->asesoria_canalizacion->descripcion;

                // Solo contar si está en el catálogo
                if (in_array($tipoAsesoria, $catalogoEspecialidades)) {
                    // Incrementar contador mensual
                    $monthlyData[$month]['rows'][$tipoAsesoria]['count']++;
                    $monthlyData[$month]['total']++;

                    // Acumular para resumen anual
                    $annualSummary[$tipoAsesoria]['count']++;
                }
            }

            // Preparar datos para la respuesta
            $monthlyTables = [];

            // Convertir a array indexado y filtrar meses sin datos
            for ($month = 1; $month <= 12; $month++) {
                if ($monthlyData[$month]['total'] > 0) {
                    // Convertir rows a array indexado manteniendo el orden del catálogo
                    $orderedRows = [];
                    foreach ($catalogoEspecialidades as $especialidad) {
                        $orderedRows[] = $monthlyData[$month]['rows'][$especialidad];
                    }
                    $monthlyData[$month]['rows'] = $orderedRows;
                    $monthlyTables[] = $monthlyData[$month];
                }
            }

            // Convertir annualSummary a array indexado manteniendo el orden del catálogo
            $orderedAnnualSummary = [];
            foreach ($catalogoEspecialidades as $especialidad) {
                $orderedAnnualSummary[] = $annualSummary[$especialidad];
            }
            $annualSummary = $orderedAnnualSummary;

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

    public function reporte_por_municipio_pdf(Request $request)
    {
        try {
            Log::info('Generando reporte por municipio con parámetros: ', ['Filtros' => $request->all()]);

            $municipio_id = $request->get('municipio');
            $desde = $request->get('desde');
            $hasta = $request->get('hasta');

            Log::info('Parámetros recibidos - municipio_id: ' . ($municipio_id ?? 'TODOS') . ', desde: ' . ($desde ?? 'NO') . ', hasta: ' . ($hasta ?? 'NO'));

            // ========== VALIDACIONES ==========

            // Caso 4: Sin municipio y sin fechas → Error
            if (!$municipio_id && !$desde && !$hasta) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Debe seleccionar al menos un municipio o un rango de fechas',
                    'data' => []
                ], 400);
            }

            // Si hay fechas, validar que desde <= hasta
            if ($desde && $hasta) {
                if ($desde > $hasta) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'La fecha "desde" no puede ser mayor que la fecha "hasta"',
                        'data' => []
                    ], 400);
                }
            }

            // ========== CONSTRUIR QUERY BASE ==========

            // Usamos la relación con directorio para obtener la especialidad del profesionista
            $query = BrAsesoria::with([
                'paciente:id,ct_municipio,municipio',
                'directorio.tipoAsesoria:id,descripcion'  
            ])
                ->where('estado_registro', true)                    // Solo asesorías activas
                // ->whereNotNull('tipo_asesoria_canaliza_id')      // Solo asesorías con tipo de asesoría asignado
                // ->whereHas('directorio')                         // Asegurar que existe el directorio
                // ->whereHas('directorio.tipoAsesoria')            // Asegurar que existe el tipo de asesoría
            ;
            // ========== APLICAR FILTROS ==========

            // Filtro por municipio
            if ($municipio_id) {
                $query->whereHas('paciente', function ($q) use ($municipio_id) {
                    $q->where('ct_municipio', $municipio_id);
                });
            }

            // Filtro por rango de fechas (solo lunes a viernes) - Se comento la restricción de días de la semana para incluir todos los días
            if ($desde && $hasta) {
                $query->whereBetween('fecha_asesoria', [$desde, $hasta])
                    // ->whereRaw("EXTRACT(DOW FROM fecha_asesoria) NOT IN (0, 6)")
                    ;
            } elseif ($desde && !$hasta) {
                $query->where('fecha_asesoria', '>=', $desde)
                    // ->whereRaw("EXTRACT(DOW FROM fecha_asesoria) NOT IN (0, 6)")
                    ;
            } elseif (!$desde && $hasta) {
                $query->where('fecha_asesoria', '<=', $hasta)
                    // ->whereRaw("EXTRACT(DOW FROM fecha_asesoria) NOT IN (0, 6)")
                    ;
            } elseif ($municipio_id && !$desde && !$hasta) {
                // Caso 3: Solo municipio → año actual
                $year = date('Y');
                $query->whereYear('fecha_asesoria', $year);
                Log::info('Caso 3: Solo municipio - filtrando por año actual: ' . $year);
            }

            // ========== OBTENER RESULTADOS ==========
            $asesorias = $query->orderBy('fecha_asesoria')->get();

            Log::info('Asesorías encontradas: ' . $asesorias->count());

            // ========== PROCESAR DATOS ==========
            $rows = [];
            $total = 0;

            foreach ($asesorias as $asesoria) {
                $municipioNombre = $asesoria->paciente->municipio ?? 'SIN MUNICIPIO';
                $fecha = $asesoria->fecha_asesoria;
                // Obtener especialidad del directorio (profesionista que atendió)
                $especialidad = $asesoria->directorio->tipoAsesoria->descripcion ?? 'SIN ESPECIALIDAD';

                $key = $municipioNombre . '||' . $fecha . '||' . $especialidad;

                if (!isset($rows[$key])) {
                    $rows[$key] = [
                        'municipio' => $municipioNombre,
                        'fecha' => $fecha,
                        'especialidad' => $especialidad,
                        'cantidad' => 0,
                    ];
                }

                $rows[$key]['cantidad']++;
                $total++;
            }

            $rows = array_values($rows);

            // ========== ESTRUCTURAR RESPUESTA ==========
            $municipioLabel = $municipio_id
                ? ($asesorias->first()?->paciente->municipio ?? 'Municipio ' . $municipio_id)
                : 'Todos';

            $responseData = [
                'title' => 'Reporte de Asesorías por Municipio',
                'filtros' => [
                    'municipio' => $municipioLabel,
                    'desde' => $desde,
                    'hasta' => $hasta,
                ],
                'rows' => $rows,
                'total' => $total,
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Reporte por municipio generado exitosamente',
                'data' => $responseData
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en reporte_por_municipio_pdf: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error al generar el reporte por municipio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ─── Lista fija de las 7 especialidades ───────────────────────────
    
    private $especialidadesTodas = [
        'MEDICINA GENERAL',
        'FISIOTERAPIA',
        'TRABAJO SOCIAL',
        'GERONTOLOGÍA',
        'PSICOLÓGICA',
        'JURÍDICA',
        'NUTRICIONAL'
    ];


    public function reporte_general_excel(Request $request)
    {
        try {
            // ─── Parámetros de filtro opcionales ───────────────────────
            $desde = $request->input('desde');
            $hasta = $request->input('hasta');

            // ─── Consulta base ─────────────────────────────────────────
            $query = BrAsesoria::query()
                ->join('br_pacientes', 'br_asesorias.paciente_id', '=', 'br_pacientes.id')
                ->join('br_cat_tipo_asesoria', 'br_asesorias.tipo_asesoria_canaliza_id', '=', 'br_cat_tipo_asesoria.id')
                ->where('br_asesorias.estado_registro', true)
                ->select(
                    'br_pacientes.municipio',
                    'br_cat_tipo_asesoria.descripcion as especialidad',
                    'br_asesorias.fecha_asesoria'
                );

            if ($desde) {
                $query->whereDate('br_asesorias.fecha_asesoria', '>=', $desde);
            }
            if ($hasta) {
                $query->whereDate('br_asesorias.fecha_asesoria', '<=', $hasta);
            }

            $asesorias = $query->orderBy('br_asesorias.fecha_asesoria')->get();

            // ─── Agrupar datos: [mesKey][municipio][especialidad] = ['dias' => [], 'cantidad' => int]
            $agrupado = [];
            $totalGeneral = 0;

            foreach ($asesorias as $asesoria) {
                $fecha = Carbon::parse($asesoria->fecha_asesoria);
                $mesKey = $fecha->format('Y-m');
                $dia = (int) $fecha->format('d');
                $municipio = $asesoria->municipio;
                $especialidad = $asesoria->especialidad;

                if (!isset($agrupado[$mesKey])) {
                    $agrupado[$mesKey] = [
                        'nombre_mes' => $this->getMesNombre($fecha->month),
                        'municipios' => []
                    ];
                }

                if (!isset($agrupado[$mesKey]['municipios'][$municipio])) {
                    $agrupado[$mesKey]['municipios'][$municipio] = [];
                }

                if (!isset($agrupado[$mesKey]['municipios'][$municipio][$especialidad])) {
                    $agrupado[$mesKey]['municipios'][$municipio][$especialidad] = [
                        'dias' => [],
                        'cantidad' => 0
                    ];
                }

                // Acumular días únicos POR ESPECIALIDAD
                if (!in_array($dia, $agrupado[$mesKey]['municipios'][$municipio][$especialidad]['dias'])) {
                    $agrupado[$mesKey]['municipios'][$municipio][$especialidad]['dias'][] = $dia;
                }

                $agrupado[$mesKey]['municipios'][$municipio][$especialidad]['cantidad']++;
                $totalGeneral++;
            }

            // ─── Ordenar días en cada especialidad ─────────────────────
            foreach ($agrupado as &$mesData) {
                foreach ($mesData['municipios'] as &$munData) {
                    foreach ($munData as &$espData) {
                        sort($espData['dias']);
                    }
                }
            }
            unset($mesData, $munData, $espData);

            // ─── Crear Spreadsheet ─────────────────────────────────────
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // ─── Estilos ───────────────────────────────────────────────
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6F1111']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ];

            $dataStyle = [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ];

            $zeroStyle = [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'font' => ['color' => ['rgb' => '999999']]
            ];

            $indicadorStyle = [
                'font' => ['bold' => true, 'size' => 12],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ];

            // ─── Escribir encabezados ──────────────────────────────────
            $row = 1;
            $headers = ['MUNICIPIO', 'ASESORÍA', 'DIAS', 'CANTIDAD', 'PORCENTAJE', 'TOTAL', 'MES'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $sheet->getStyle($col . $row)->applyFromArray($headerStyle);
                $col++;
            }
            $row++;

            // ─── Escribir datos ────────────────────────────────────────
            $META_FIJA = 50000;
            $avanceTotal = 0;

            ksort($agrupado);

            foreach ($agrupado as $mesKey => $mesData) {
                $municipios = $mesData['municipios'];
                ksort($municipios);

                // Calcular total del mes
                $totalMes = 0;
                foreach ($municipios as $munData) {
                    foreach ($munData as $espData) {
                        $totalMes += $espData['cantidad'];
                    }
                }

                foreach ($municipios as $nombreMunicipio => $munData) {
                    // Calcular total del municipio
                    $totalMunicipio = 0;
                    foreach ($munData as $espData) {
                        $totalMunicipio += $espData['cantidad'];
                    }

                    $primeraFilaMunicipio = true;

                    // ─── MOSTRAR SIEMPRE LAS 7 ESPECIALIDADES ─────────
                    foreach ($this->especialidadesTodas as $nombreEspecialidad) {
                        if (isset($munData[$nombreEspecialidad])) {
                            // Especialidad con datos
                            $espData = $munData[$nombreEspecialidad];
                            $diasStr = implode(',', $espData['dias']);
                            $cantidad = $espData['cantidad'];
                            $porcentaje = $totalMunicipio > 0 ? round(($cantidad / $totalMunicipio) * 100, 1) : 0;
                            $tieneDatos = true;
                        } else {
                            // Especialidad sin datos (días vacíos, 0, 0%)
                            $diasStr = '';
                            $cantidad = 0;
                            $porcentaje = 0;
                            $tieneDatos = false;
                        }

                        // MUNICIPIO (solo primera fila)
                        $sheet->setCellValue('A' . $row, $primeraFilaMunicipio ? $nombreMunicipio : '');

                        // ASESORÍA
                        $sheet->setCellValue('B' . $row, $nombreEspecialidad);

                        // DIAS
                        $sheet->setCellValue('C' . $row, $diasStr);

                        // CANTIDAD
                        $sheet->setCellValue('D' . $row, $cantidad);

                        // PORCENTAJE
                        $sheet->setCellValue('E' . $row, $porcentaje . '%');

                        // TOTAL (solo primera fila del municipio)
                        $sheet->setCellValue('F' . $row, $primeraFilaMunicipio ? $totalMes : '');

                        // MES (solo primera fila del municipio)
                        $sheet->setCellValue('G' . $row, $primeraFilaMunicipio ? $mesData['nombre_mes'] : '');

                        // Aplicar estilo según tenga datos o no
                        $estilo = $tieneDatos ? $dataStyle : $zeroStyle;
                        foreach (range('A', 'G') as $col) {
                            $sheet->getStyle($col . $row)->applyFromArray($estilo);
                            $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(
                                in_array($col, ['D', 'E', 'F']) ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT
                            );
                        }

                        $primeraFilaMunicipio = false;
                        $row++;
                    }
                }
            }

            // ─── Indicadores finales ───────────────────────────────────
            $avanceTotal = $totalGeneral;
            $faltante = $META_FIJA - $avanceTotal;
            $porcentajeAvance = $META_FIJA > 0 ? round(($avanceTotal / $META_FIJA) * 100, 1) : 0;
            $porcentajeFaltante = $META_FIJA > 0 ? round(($faltante / $META_FIJA) * 100, 1) : 0;

            // Fila vacía separadora
            $row++;
            $sheet->mergeCells("A{$row}:G{$row}");
            $row++;

            // Meta
            $sheet->setCellValue('A' . $row, 'Meta');
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->setCellValue('D' . $row, number_format($META_FIJA));
            $sheet->setCellValue('E' . $row, '100%');
            $sheet->mergeCells("F{$row}:G{$row}");
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($indicadorStyle);
            $sheet->getStyle("A{$row}:G{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                ->setStartColor(new Color('E8F5E9'));
            $row++;

            // Avance
            $sheet->setCellValue('A' . $row, 'Avance');
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->setCellValue('D' . $row, number_format($avanceTotal));
            $sheet->setCellValue('E' . $row, $porcentajeAvance . '%');
            $sheet->mergeCells("F{$row}:G{$row}");
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($indicadorStyle);
            $sheet->getStyle("A{$row}:G{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                ->setStartColor(new Color('FFF3E0'));
            $row++;

            // Falta
            $sheet->setCellValue('A' . $row, 'Falta');
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->setCellValue('D' . $row, number_format($faltante));
            $sheet->setCellValue('E' . $row, $porcentajeFaltante . '%');
            $sheet->mergeCells("F{$row}:G{$row}");
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($indicadorStyle);
            $sheet->getStyle("A{$row}:G{$row}")->getFill()->setFillType(Fill::FILL_SOLID)
                ->setStartColor(new Color('FFEBEE'));

            // ─── Ajustar ancho de columnas ─────────────────────────────
            $sheet->getColumnDimension('A')->setWidth(25);
            $sheet->getColumnDimension('B')->setWidth(22);
            $sheet->getColumnDimension('C')->setWidth(25);
            $sheet->getColumnDimension('D')->setWidth(12);
            $sheet->getColumnDimension('E')->setWidth(14);
            $sheet->getColumnDimension('F')->setWidth(12);
            $sheet->getColumnDimension('G')->setWidth(15);

            // ─── Generar archivo para descarga ─────────────────────────
            $writer = new Xlsx($spreadsheet);
            $filename = 'reporte_general_' . now()->format('Y-m-d_His') . '.xlsx';

            $tempPath = storage_path('app/temp/' . $filename);
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            $writer->save($tempPath);

            return response()->download($tempPath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error en reporte_general_excel: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Error al generar el reporte general',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ─── Helper para nombre de mes en español ─────────────────────────
    private function getMesNombre($mes)
    {
        $meses = [
            1 => 'ENERO',
            2 => 'FEBRERO',
            3 => 'MARZO',
            4 => 'ABRIL',
            5 => 'MAYO',
            6 => 'JUNIO',
            7 => 'JULIO',
            8 => 'AGOSTO',
            9 => 'SEPTIEMBRE',
            10 => 'OCTUBRE',
            11 => 'NOVIEMBRE',
            12 => 'DICIEMBRE',
        ];
        return $meses[$mes] ?? '';
    }
}
