<?php

namespace App\Services;

use App\Models\BrAsesoria;
use App\Models\BrDirectorio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ReporteService
{
    /**
     * Genera el reporte detallado para un especialista.
     */
    public function generarReporteEspecialista(BrDirectorio $directorio, $desde = null, $hasta = null): array
    {
        $directorio_id = $directorio->id;

        // 1. Query base para asesorías del especialista
        $queryEspecialista = BrAsesoria::where('asesor_atendio_id', $directorio_id)
            ->with(['paciente:id,primer_ap,segundo_ap,nombres,municipio', 'asesoria_canalizacion:id,descripcion']);

        if ($desde)
            $queryEspecialista->whereDate('created_at', '>=', $desde);
        if ($hasta)
            $queryEspecialista->whereDate('created_at', '<=', $hasta);

        $asesorias = $queryEspecialista->orderBy('created_at', 'desc')->get();

        // 2. Lógica de Cobertura Dinámica
        $cobertura = $this->calcularCobertura($directorio_id, $desde, $hasta);

        // 3. Estadísticas Diarias (Max/Min)
        $statsDiarias = $this->getStatsDiarias($directorio_id, $desde, $hasta);

        // 4. Transformación de datos (Métricas existentes)
        $metricas = $this->procesarMetricas($asesorias);

        return array_merge([
            'filtros' => ['desde' => $desde, 'hasta' => $hasta],
            'especialista' => [
                'id' => $directorio->id,
                'nombre' => trim($directorio->primer_ap_dir . ' ' . $directorio->segundo_ap_dir . ' ' . $directorio->nombres),
                'especialidad' => $directorio->tipoAsesoria ? $directorio->tipoAsesoria->descripcion : null,
                'email' => $directorio->email,
                'telefono' => $directorio->telefono,
            ],
            'resumen' => array_merge($metricas['resumen'], [
                'cobertura_texto' => $cobertura['texto'],
                'cobertura_porcentaje' => $cobertura['porcentaje'],
                'max_sesion_diaria' => $statsDiarias['max'],
                'min_sesion_diaria' => $statsDiarias['min'],
            ]),
            'por_municipio' => $metricas['por_municipio'],
            'por_mes' => $metricas['por_mes'],
            'por_estado' => $metricas['por_estado'],
            'lista_asesorias' => $metricas['lista_asesorias']
        ]);
    }

    /**
     * Calcula la cobertura dinámica: (municipios especialista) / (municipios totales registrados)
     */
    private function calcularCobertura($directorio_id, $desde, $hasta): array
    {
        // Denominador: Municipios únicos totales en br_asesorias (vía relación paciente)
        $queryGlobal = DB::table('br_asesorias')
            ->join('br_pacientes', 'br_asesorias.paciente_id', '=', 'br_pacientes.id')
            ->whereNotNull('br_pacientes.municipio');

        if ($desde)
            $queryGlobal->whereDate('br_asesorias.created_at', '>=', $desde);
        if ($hasta)
            $queryGlobal->whereDate('br_asesorias.created_at', '<=', $hasta);

        $totalMunicipios = $queryGlobal->distinct('br_pacientes.municipio')->count('br_pacientes.municipio');

        // Numerador: Municipios únicos atendidos por el especialista
        $queryEspecialista = DB::table('br_asesorias')
            ->join('br_pacientes', 'br_asesorias.paciente_id', '=', 'br_pacientes.id')
            ->where('br_asesorias.asesor_atendio_id', $directorio_id)
            ->whereNotNull('br_pacientes.municipio');

        if ($desde)
            $queryEspecialista->whereDate('br_asesorias.created_at', '>=', $desde);
        if ($hasta)
            $queryEspecialista->whereDate('br_asesorias.created_at', '<=', $hasta);

        $municipiosEspecialista = $queryEspecialista->distinct('br_pacientes.municipio')->count('br_pacientes.municipio');

        return [
            'texto' => "{$municipiosEspecialista}/{$totalMunicipios}",
            'porcentaje' => $totalMunicipios > 0 ? round(($municipiosEspecialista / $totalMunicipios) * 100, 2) : 0
        ];
    }

    /**
     * Obtiene el máximo y mínimo de atenciones diarias.
     */
    private function getStatsDiarias($directorio_id, $desde, $hasta): array
    {
        $subquery = DB::table('br_asesorias')
            ->select(DB::raw('DATE(created_at) as fecha'), DB::raw('COUNT(*) as total'))
            ->where('asesor_atendio_id', $directorio_id);

        if ($desde)
            $subquery->whereDate('created_at', '>=', $desde);
        if ($hasta)
            $subquery->whereDate('created_at', '<=', $hasta);

        $results = $subquery->groupBy(DB::raw('DATE(created_at)'))->get();

        if ($results->isEmpty()) {
            return ['max' => 0, 'min' => 0];
        }

        return [
            'max' => $results->max('total'),
            'min' => $results->min('total')
        ];
    }

    /**
     * Procesa las métricas básicas de una colección de asesorías.
     */
    private function procesarMetricas(Collection $asesorias): array
    {
        return [
            'resumen' => [
                'total_asesorias' => $asesorias->count(),
                'total_pacientes' => $asesorias->pluck('paciente_id')->unique()->count(),
                'asesorias_abiertas' => $asesorias->where('estado_asesoria', 'ABIERTA')->count(),
                'asesorias_cerradas' => $asesorias->where('estado_asesoria', 'CERRADA')->count(),
                'asesorias_canalizadas' => $asesorias->where('estado_asesoria', 'CANALIZADO')->count(),
                'asesorias_pendientes' => $asesorias->where('estado_asesoria', 'PENDIENTE')->count(),
                'municipios_atendidos' => $asesorias->filter(fn($a) => $a->paciente)->pluck('paciente.municipio')->filter()->unique()->count(),
            ],
            'por_municipio' => $asesorias->filter(fn($a) => $a->paciente && $a->paciente->municipio)->groupBy('paciente.municipio')->map(fn($g, $m) => ['municipio' => $m, 'cantidad' => $g->count()])->values(),
            'por_mes' => $asesorias->groupBy(fn($a) => substr($a->created_at, 0, 7))->map(fn($g, $m) => ['mes' => $m, 'cantidad' => $g->count()])->sortKeys()->values(),
            'por_estado' => $asesorias->groupBy('estado_asesoria')->map(fn($g, $e) => ['estado' => $e, 'cantidad' => $g->count()])->values(),
            'lista_asesorias' => $asesorias->map(fn($a) => [
                'id' => $a->id,
                'paciente' => $a->paciente ? trim($a->paciente->primer_ap . ' ' . $a->paciente->segundo_ap . ' ' . $a->paciente->nombres) : null,
                'municipio' => $a->paciente ? $a->paciente->municipio : null,
                'estado_asesoria' => $a->estado_asesoria,
                'canalizacion' => $a->asesoria_canalizacion ? $a->asesoria_canalizacion->descripcion : null,
                'created_at' => substr($a->created_at, 0, 10),
            ])
        ];
    }
}
