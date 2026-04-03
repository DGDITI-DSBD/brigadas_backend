<?php

namespace App\Services;

use App\Models\BrAsesoria;
use App\Models\BrDirectorio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ReporteAvanzadoService
{
    // ═══════════════════════════════════════════════════════════════
    //  PUNTO DE ENTRADA PRINCIPAL
    // ═══════════════════════════════════════════════════════════════

    /**
     * Genera el reporte avanzado para un especialista.
     *
     * @param BrDirectorio $directorio  Perfil del especialista
     * @param string|null  $desde       Fecha inicio (Y-m-d)
     * @param string|null  $hasta       Fecha fin    (Y-m-d)
     * @param int|null     $municipio_id Filtro opcional por ct_municipio
     */
    public function generarReporteEspecialista(
        BrDirectorio $directorio,
        ?string $desde = null,
        ?string $hasta = null,
        ?int $municipio_id = null
    ): array {
        $directorio_id = $directorio->id;

        // 1 ─ Asesorías del especialista (con relaciones)
        $query = BrAsesoria::where('asesor_atendio_id', $directorio_id)
            ->with(['paciente:id,primer_ap,segundo_ap,nombres,municipio,ct_municipio', 'asesoria_canalizacion:id,descripcion']);

        $this->applyDateFilters($query, $desde, $hasta);
        $this->applyMunicipioFilter($query, $municipio_id);

        $asesorias = $query->orderBy('fecha_asesoria', 'desc')->get();

        // 2 ─ Métricas de negocio (DB-level para eficiencia)
        $participacion = $this->calcularParticipacion($directorio_id, $desde, $hasta, $municipio_id);
        $promedioDiario = $this->calcularPromedioDiario($directorio_id, $desde, $hasta, $municipio_id);
        $cobertura = $this->calcularCoberturaDinamica($directorio_id, $desde, $hasta, $municipio_id);
        $statsDiarias = $this->calcularStatsDiarias($directorio_id, $desde, $hasta, $municipio_id);
        $metricas = $this->procesarMetricas($asesorias);

        return [
            'filtros' => [
                'desde' => $desde,
                'hasta' => $hasta,
                'municipio_id' => $municipio_id,
            ],
            'especialista' => [
                'id' => $directorio->id,
                'nombre' => trim($directorio->primer_ap_dir . ' ' . $directorio->segundo_ap_dir . ' ' . $directorio->nombres),
                'especialidad' => $directorio->tipoAsesoria ? $directorio->tipoAsesoria->descripcion : null,
                'email' => $directorio->email,
                'telefono' => $directorio->telefono,
            ],
            'resumen' => array_merge($metricas['resumen'], [
                'participacion_porcentaje' => $participacion,
                'promedio_diario' => $promedioDiario,
                'cobertura_municipal' => $cobertura['texto'],
                'cobertura_porcentaje' => $cobertura['porcentaje'],
                'max_sesion_diaria' => $statsDiarias['max'],
                'min_sesion_diaria' => $statsDiarias['min'],
            ]),
            'por_municipio' => $metricas['por_municipio'],
            'por_mes' => $metricas['por_mes'],
            'por_estado' => $metricas['por_estado'],
            'lista_asesorias' => $metricas['lista_asesorias'],
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    //  MÉTRICAS DE NEGOCIO (cálculos a nivel DB)
    // ═══════════════════════════════════════════════════════════════

    /**
     * PARTICIPACIÓN %
     * P = (asesorías del especialista / asesorías globales en el periodo) × 100
     */
    private function calcularParticipacion($directorio_id, $desde, $hasta, $municipio_id): float
    {
        $globalQuery = DB::table('br_asesorias')
            ->join('br_pacientes', 'br_asesorias.paciente_id', '=', 'br_pacientes.id');

        $this->applyDateFiltersDB($globalQuery, $desde, $hasta);
        if ($municipio_id) {
            $globalQuery->where('br_pacientes.ct_municipio', $municipio_id);
        }

        $totalGlobal = $globalQuery->count();
        if ($totalGlobal === 0)
            return 0.0;

        $especialistaQuery = DB::table('br_asesorias')
            ->join('br_pacientes', 'br_asesorias.paciente_id', '=', 'br_pacientes.id')
            ->where('br_asesorias.asesor_atendio_id', $directorio_id);

        $this->applyDateFiltersDB($especialistaQuery, $desde, $hasta);
        if ($municipio_id) {
            $especialistaQuery->where('br_pacientes.ct_municipio', $municipio_id);
        }

        $totalEspecialista = $especialistaQuery->count();

        return round(($totalEspecialista / $totalGlobal) * 100, 2);
    }

    /**
     * PROMEDIO DIARIO
     * = total_asesorias_especialista / COUNT(DISTINCT fecha_asesoria)
     * Solo cuenta días donde el especialista tuvo actividad.
     */
    private function calcularPromedioDiario($directorio_id, $desde, $hasta, $municipio_id): float
    {
        $query = DB::table('br_asesorias')
            ->join('br_pacientes', 'br_asesorias.paciente_id', '=', 'br_pacientes.id')
            ->where('br_asesorias.asesor_atendio_id', $directorio_id)
            ->whereNotNull('br_asesorias.fecha_asesoria');

        $this->applyDateFiltersDB($query, $desde, $hasta);
        if ($municipio_id) {
            $query->where('br_pacientes.ct_municipio', $municipio_id);
        }

        // Clonar para contar días distintos
        $diasDistintos = (clone $query)->distinct('br_asesorias.fecha_asesoria')->count('br_asesorias.fecha_asesoria');
        $totalAsesorias = (clone $query)->count();

        if ($diasDistintos === 0)
            return 0.0;

        return round($totalAsesorias / $diasDistintos, 2);
    }

    /**
     * COBERTURA MUNICIPAL DINÁMICA
     * Denominador: municipios únicos en br_asesorias para el periodo (global)
     * Numerador  : municipios únicos atendidos por el especialista
     * Formato    : "X/Y"
     */
    private function calcularCoberturaDinamica($directorio_id, $desde, $hasta, $municipio_id): array
    {
        // Denominador global
        $globalQuery = DB::table('br_asesorias')
            ->join('br_pacientes', 'br_asesorias.paciente_id', '=', 'br_pacientes.id')
            ->whereNotNull('br_pacientes.ct_municipio');

        $this->applyDateFiltersDB($globalQuery, $desde, $hasta);
        if ($municipio_id) {
            $globalQuery->where('br_pacientes.ct_municipio', $municipio_id);
        }

        $totalMunicipios = $globalQuery->distinct('br_pacientes.ct_municipio')->count('br_pacientes.ct_municipio');

        // Numerador del especialista
        $especialistaQuery = DB::table('br_asesorias')
            ->join('br_pacientes', 'br_asesorias.paciente_id', '=', 'br_pacientes.id')
            ->where('br_asesorias.asesor_atendio_id', $directorio_id)
            ->whereNotNull('br_pacientes.ct_municipio');

        $this->applyDateFiltersDB($especialistaQuery, $desde, $hasta);
        if ($municipio_id) {
            $especialistaQuery->where('br_pacientes.ct_municipio', $municipio_id);
        }

        $municipiosEspecialista = $especialistaQuery->distinct('br_pacientes.ct_municipio')->count('br_pacientes.ct_municipio');

        $porcentaje = $totalMunicipios > 0
            ? round(($municipiosEspecialista / $totalMunicipios) * 100, 2)
            : 0.0;

        return [
            'texto' => "{$municipiosEspecialista}/{$totalMunicipios}",
            'porcentaje' => $porcentaje,
        ];
    }

    /**
     * SESIONES MÁX / MÍN DIARIAS
     * Agrupa asesorías del especialista por fecha_asesoria → max y min del conteo.
     */
    private function calcularStatsDiarias($directorio_id, $desde, $hasta, $municipio_id): array
    {
        $query = DB::table('br_asesorias')
            ->select(DB::raw('fecha_asesoria'), DB::raw('COUNT(*) as total'))
            ->where('asesor_atendio_id', $directorio_id)
            ->whereNotNull('fecha_asesoria');

        $this->applyDateFiltersDB($query, $desde, $hasta, false);

        if ($municipio_id) {
            $query->join('br_pacientes', 'br_asesorias.paciente_id', '=', 'br_pacientes.id')
                ->where('br_pacientes.ct_municipio', $municipio_id);
        }

        $results = $query->groupBy('fecha_asesoria')->get();

        if ($results->isEmpty()) {
            return ['max' => 0, 'min' => 0];
        }

        return [
            'max' => (int) $results->max('total'),
            'min' => (int) $results->min('total'),
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    //  MÉTRICAS DERIVADAS (Collection-level)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Procesa la colección de asesorías y retorna las métricas agrupadas.
     */
    private function procesarMetricas(Collection $asesorias): array
    {
        return [
            'resumen' => [
                'total_asesorias' => $asesorias->count(),
                'total_pacientes' => $asesorias->pluck('paciente_id')->unique()->count(),
                'municipios_atendidos' => $asesorias
                    ->filter(fn($a) => $a->paciente)
                    ->pluck('paciente.ct_municipio')
                    ->filter()
                    ->unique()
                    ->count(),
                'asesorias_abiertas' => $asesorias->where('estado_asesoria', 'ABIERTA')->count(),
                'asesorias_cerradas' => $asesorias->where('estado_asesoria', 'CERRADA')->count(),
                'asesorias_canalizadas' => $asesorias->where('estado_asesoria', 'CANALIZADO')->count(),
                'asesorias_pendientes' => $asesorias->where('estado_asesoria', 'PENDIENTE')->count(),
            ],
            'por_municipio' => $asesorias
                ->filter(fn($a) => $a->paciente && $a->paciente->municipio)
                ->groupBy('paciente.municipio')
                ->map(fn($g, $m) => ['municipio' => $m, 'cantidad' => $g->count()])
                ->values(),
            'por_mes' => $asesorias
                ->groupBy(fn($a) => substr((string) $a->fecha_asesoria, 0, 7))
                ->map(fn($g, $m) => ['mes' => $m, 'cantidad' => $g->count()])
                ->sortKeys()
                ->values(),
            'por_estado' => $asesorias
                ->groupBy('estado_asesoria')
                ->map(fn($g, $e) => ['estado' => $e, 'cantidad' => $g->count()])
                ->values(),
            'lista_asesorias' => $asesorias->map(fn($a) => [
                'id' => $a->id,
                'paciente' => $a->paciente
                    ? trim($a->paciente->primer_ap . ' ' . $a->paciente->segundo_ap . ' ' . $a->paciente->nombres)
                    : null,
                'municipio' => $a->paciente ? $a->paciente->municipio : null,
                'ct_municipio' => $a->paciente ? $a->paciente->ct_municipio : null,
                'estado_asesoria' => $a->estado_asesoria,
                'canalizacion' => $a->asesoria_canalizacion ? $a->asesoria_canalizacion->descripcion : null,
                'fecha' => $a->fecha_asesoria,
            ])->values(),
        ];
    }

    // ═══════════════════════════════════════════════════════════════
    //  HELPERS PARA FILTROS
    // ═══════════════════════════════════════════════════════════════

    /** Aplica filtros de fecha a un Eloquent Builder (usa campo fecha_asesoria) */
    private function applyDateFilters($query, $desde, $hasta): void
    {
        if ($desde)
            $query->whereDate('fecha_asesoria', '>=', $desde);
        if ($hasta)
            $query->whereDate('fecha_asesoria', '<=', $hasta);
    }

    /** Aplica filtros de municipio a un Eloquent Builder via relación paciente */
    private function applyMunicipioFilter($query, $municipio_id): void
    {
        if ($municipio_id) {
            $query->whereHas('paciente', fn($q) => $q->where('ct_municipio', $municipio_id));
        }
    }

    /**
     * Aplica filtros de fecha a un Query Builder (DB::table).
     * @param bool $useJoinedTable Si hay join con br_pacientes se usa el prefijo de tabla
     */
    private function applyDateFiltersDB($query, $desde, $hasta, bool $prefixTable = true): void
    {
        $field = $prefixTable ? 'br_asesorias.fecha_asesoria' : 'fecha_asesoria';
        if ($desde)
            $query->whereDate($field, '>=', $desde);
        if ($hasta)
            $query->whereDate($field, '<=', $hasta);
    }

    // ═══════════════════════════════════════════════════════════════
    //  LISTADO DE ASESORÍAS — datos planos para todas las pestañas
    // ═══════════════════════════════════════════════════════════════

    /**
     * Prepara datos agregados para el PDF del Tablero General.
     * Usado por el endpoint de exportación PDF.
     */
    public function prepararDatosTablero(?string $desde = null, ?string $hasta = null): array
    {
        $data = $this->listarAsesorias($desde, $hasta)->toArray();

        $municipios = array_values(array_unique(array_column($data, 'municipio')));
        $especialidades = array_values(array_unique(array_column($data, 'especialidad')));
        $especialistas = array_values(array_unique(array_column($data, 'especialista')));
        $brigadistas = array_values(array_unique(array_filter(array_column($data, 'brigadista'))));

        $total = array_sum(array_column($data, 'cantidad'));

        $byEsp = [];
        foreach ($especialidades as $e) {
            $t = array_sum(array_map(fn($d) => $d['especialidad'] === $e ? $d['cantidad'] : 0, $data));
            $byEsp[] = ['especialidad' => $e, 'total' => $t];
        }
        usort($byEsp, fn($a, $b) => $b['total'] <=> $a['total']);

        $byMun = [];
        foreach ($municipios as $m) {
            $t = array_sum(array_map(fn($d) => $d['municipio'] === $m ? $d['cantidad'] : 0, $data));
            $byMun[] = ['municipio' => $m, 'total' => $t];
        }
        usort($byMun, fn($a, $b) => $b['total'] <=> $a['total']);

        $mesesSet = array_values(array_unique(array_filter(array_map(fn($d) => isset($d['fecha']) ? substr($d['fecha'], 0, 7) : null, $data))));
        sort($mesesSet);

        $mesLabels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $monthData = [];
        foreach ($mesesSet as $full) {
            [$y, $mo] = explode('-', $full);
            $label = $mesLabels[(int) $mo - 1] ?? $mo;
            $recs = array_filter($data, fn($d) => isset($d['fecha']) && str_starts_with($d['fecha'], $full));
            $byEspM = [];
            foreach ($especialidades as $e) {
                $byEspM[$e] = array_sum(array_map(fn($r) => $r['especialidad'] === $e ? $r['cantidad'] : 0, $recs));
            }
            $monthData[] = [
                'mes' => $label,
                'full' => $full,
                'byEsp' => $byEspM,
                'total' => array_sum(array_column($recs, 'cantidad')),
            ];
        }

        $grandTotal = array_sum(array_column($monthData, 'total'));
        $dateRange = count($monthData) > 0
            ? ($monthData[0]['mes'] . ' – ' . $monthData[count($monthData) - 1]['mes'])
            : 'Sin datos';

        return [
            'total' => $total,
            'municipios' => $municipios,
            'especialidades' => $especialidades,
            'especialistas' => $especialistas,
            'brigadistas' => $brigadistas,
            'byEsp' => $byEsp,
            'byMun' => $byMun,
            'monthData' => $monthData,
            'grandTotal' => $grandTotal,
            'dateRange' => $dateRange,
            'generado' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Retorna todas las asesorías con datos enriquecidos (especialista, municipio, edad, etc.)
     * en formato plano listo para el frontend (Tablero, Por Día, Por Municipio, General, Adultos).
     */
    public function listarAsesorias(
        ?string $desde = null,
        ?string $hasta = null,
        ?string $municipio = null,
        ?string $especialidad = null
    ): Collection {
        $query = BrAsesoria::with([
                'directorio.tipoAsesoria:id,descripcion',
                'paciente:id,primer_ap,segundo_ap,nombres,municipio,ct_municipio,fecha_nacimiento',
            ])
            ->whereNotNull('fecha_asesoria');

        $this->applyDateFilters($query, $desde, $hasta);

        if ($municipio) {
            $query->whereHas('paciente', fn($q) => $q->where('municipio', 'ILIKE', "%{$municipio}%"));
        }

        if ($especialidad) {
            $query->whereHas('directorio.tipoAsesoria', fn($q) => $q->where('descripcion', 'ILIKE', "%{$especialidad}%"));
        }

        $asesorias = $query->orderBy('fecha_asesoria', 'desc')->get();

        return $asesorias->map(function ($a) {
            $directorio = $a->directorio;
            $paciente = $a->paciente;

            $edad = 0;
            if ($paciente && $paciente->fecha_nacimiento) {
                try {
                    $edad = (int) now()->diffInYears(\Carbon\Carbon::parse($paciente->fecha_nacimiento));
                } catch (\Exception $e) {
                    $edad = 0;
                }
            }

            return [
                'id'            => $a->id,
                'fecha'         => $a->fecha_asesoria ? $a->fecha_asesoria->format('Y-m-d') : null,
                'municipio'     => $paciente?->municipio ?? 'Sin municipio',
                'especialidad'  => $directorio?->tipoAsesoria?->descripcion ?? 'Sin especialidad',
                'especialista'  => $directorio
                    ? trim($directorio->primer_ap_dir . ' ' . $directorio->segundo_ap_dir . ' ' . $directorio->nombres)
                    : 'Sin especialista',
                'brigadista'    => '',
                'cantidad'      => 1,
                'edad'          => $edad,
            ];
        });
    }

    // ═══════════════════════════════════════════════════════════════
    //  LISTADO DE ESPECIALISTAS — todos los KPIs en una sola llamada
    // ═══════════════════════════════════════════════════════════════

    /**
     * Retorna todos los especialistas activos con KPIs estadísticos completos.
     *
     * Ejecuta 5 consultas SQL eficientes (sin N+1) para calcular:
     *   - Total asesorías, primera/última fecha, días laborados
     *   - Sesiones máximas y mínimas por día
     *   - Municipio principal (mayor volumen)
     *   - Lista de municipios atendidos
     *   - % Participación y % Cobertura municipal
     */
    public function listarEspecialistas(?string $desde = null, ?string $hasta = null): Collection
    {
        // ── Construir cláusulas de fecha con alias (a.fecha_asesoria) ──
        $dateAlias   = '';  // con alias "a." — para queries con JOIN
        $dateInner   = '';  // sin alias    — para subquery directa sobre br_asesorias
        $bindings    = [];
        $bindInner   = [];

        if ($desde) {
            $dateAlias  .= ' AND a.fecha_asesoria >= ?';
            $dateInner  .= ' AND fecha_asesoria >= ?';
            $bindings[]  = $desde;
            $bindInner[] = $desde;
        }
        if ($hasta) {
            $dateAlias  .= ' AND a.fecha_asesoria <= ?';
            $dateInner  .= ' AND fecha_asesoria <= ?';
            $bindings[]  = $hasta;
            $bindInner[] = $hasta;
        }

        // ── 1. Totales globales del período (participación y cobertura) ──
        $globalRow = DB::selectOne("
            SELECT
                COUNT(*)                                 AS total_global,
                COUNT(DISTINCT p.ct_municipio)           AS municipios_global
            FROM br_asesorias  a
            JOIN br_pacientes  p ON a.paciente_id = p.id
            WHERE a.fecha_asesoria IS NOT NULL
            {$dateAlias}
        ", $bindings);

        $totalGlobal      = $globalRow ? (int) $globalRow->total_global      : 0;
        $municipiosGlobal = $globalRow ? (int) $globalRow->municipios_global  : 0;

        // ── 2. Stats básicas por especialista ──
        $statsRows = DB::select("
            SELECT
                a.asesor_atendio_id,
                COUNT(*)                               AS total_asesorias,
                MIN(a.fecha_asesoria)                  AS primera_fecha,
                MAX(a.fecha_asesoria)                  AS ultima_fecha,
                COUNT(DISTINCT a.fecha_asesoria)       AS dias_laborados,
                COUNT(DISTINCT p.ct_municipio)         AS municipios_cubiertos
            FROM br_asesorias  a
            JOIN br_pacientes  p ON a.paciente_id = p.id
            WHERE a.fecha_asesoria IS NOT NULL
            {$dateAlias}
            GROUP BY a.asesor_atendio_id
        ", $bindings);

        $statsMap = collect($statsRows)->keyBy('asesor_atendio_id');

        // ── 3. Sesiones máximas y mínimas diarias ──
        $dailyRows = DB::select("
            SELECT
                asesor_atendio_id,
                MAX(cnt) AS max_sesion_diaria,
                MIN(cnt) AS min_sesion_diaria
            FROM (
                SELECT asesor_atendio_id,
                       fecha_asesoria,
                       COUNT(*) AS cnt
                FROM   br_asesorias
                WHERE  fecha_asesoria IS NOT NULL
                {$dateInner}
                GROUP  BY asesor_atendio_id, fecha_asesoria
            ) daily
            GROUP BY asesor_atendio_id
        ", $bindInner);

        $dailyMap = collect($dailyRows)->keyBy('asesor_atendio_id');

        // ── 4. Municipio principal por especialista (mayor volumen) ──
        $munPrincipalRows = DB::select("
            SELECT DISTINCT ON (sub.asesor_atendio_id)
                sub.asesor_atendio_id,
                sub.municipio
            FROM (
                SELECT a2.asesor_atendio_id,
                       p2.municipio,
                       COUNT(*) AS cnt
                FROM   br_asesorias  a2
                JOIN   br_pacientes  p2 ON a2.paciente_id = p2.id
                WHERE  a2.fecha_asesoria IS NOT NULL
                  AND  p2.municipio     IS NOT NULL
                {$dateAlias}
                GROUP  BY a2.asesor_atendio_id, p2.municipio
            ) sub
            ORDER  BY sub.asesor_atendio_id, sub.cnt DESC
        ", $bindings);

        $munPrincipalMap = collect($munPrincipalRows)->keyBy('asesor_atendio_id');

        // ── 5. Lista de municipios únicos por especialista ──
        $munListQuery = DB::table('br_asesorias as a')
            ->join('br_pacientes as p', 'a.paciente_id', '=', 'p.id')
            ->selectRaw('a.asesor_atendio_id, p.municipio')
            ->whereNotNull('a.fecha_asesoria')
            ->whereNotNull('p.municipio');

        if ($desde) $munListQuery->whereDate('a.fecha_asesoria', '>=', $desde);
        if ($hasta) $munListQuery->whereDate('a.fecha_asesoria', '<=', $hasta);

        $munListMap = $munListQuery->distinct()
            ->get()
            ->groupBy('asesor_atendio_id')
            ->map(fn($g) => $g->pluck('municipio')->sort()->values()->toArray());

        // ── 6. Componer resultado final ──
        return BrDirectorio::with('tipoAsesoria:id,descripcion')
            ->where('estado', true)
            ->get()
            ->map(function ($d) use (
                $statsMap, $dailyMap, $munPrincipalMap, $munListMap,
                $totalGlobal, $municipiosGlobal
            ) {
                $stats   = $statsMap->get($d->id);
                $daily   = $dailyMap->get($d->id);
                $munP    = $munPrincipalMap->get($d->id);
                $munList = $munListMap->get($d->id) ?? [];

                $total   = $stats ? (int) $stats->total_asesorias    : 0;
                $diasLab = $stats ? (int) $stats->dias_laborados      : 0;
                $munCub  = $stats ? (int) $stats->municipios_cubiertos : 0;

                return [
                    'directorio_id'            => $d->id,
                    'nombre'                   => trim($d->primer_ap_dir . ' ' . $d->segundo_ap_dir . ' ' . $d->nombres),
                    'especialidad'             => $d->tipoAsesoria?->descripcion,
                    // Volumen
                    'total_asesorias'          => $total,
                    'primera_fecha'            => $stats ? (string) $stats->primera_fecha : null,
                    'ultima_fecha'             => $stats ? (string) $stats->ultima_fecha  : null,
                    'dias_laborados'           => $diasLab,
                    'promedio_diario'          => $diasLab > 0 ? round($total / $diasLab, 2) : 0.0,
                    // Sesiones
                    'max_sesion_diaria'        => $daily ? (int) $daily->max_sesion_diaria : 0,
                    'min_sesion_diaria'        => $daily ? (int) $daily->min_sesion_diaria : 0,
                    // Cobertura
                    'municipio_principal'      => $munP ? $munP->municipio : null,
                    'municipios'               => $munList,
                    'municipios_cubiertos'     => $munCub,
                    'cobertura_porcentaje'     => $municipiosGlobal > 0
                        ? round(($munCub / $municipiosGlobal) * 100, 2)
                        : 0.0,
                    // Participación
                    'participacion_porcentaje' => $totalGlobal > 0
                        ? round(($total / $totalGlobal) * 100, 2)
                        : 0.0,
                ];
            });
    }
}
