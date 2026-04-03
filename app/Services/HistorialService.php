<?php

namespace App\Services;

use App\Models\BrAsesoria;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Servicio para el Historial de Brigadas.
 * Agrega asesorías por (fecha, municipio, especialidad) y genera route_data y tabla_detalle.
 */
class HistorialService
{
    /** Mapeo municipio BD → svgKey (nombre exacto en MapEdomex) */
    private const MUNICIPIO_TO_SVG = [
        'Ecatepec' => 'Ecatepec de Morelos',
        'Naucalpan' => 'Naucalpan de Juárez',
        'Atizapán' => 'Atizapán de Zaragoza',
        'Coacalco' => 'Coacalco de Berriozábal',
        'San Martín' => 'San Martín de las Pirámides',
        'San Martín de las Pirámides' => 'San Martín de las Pirámides',
        'San Martín de las Piramides' => 'San Martín de las Pirámides',
        'Toluca de Lerdo' => 'Toluca',
        'Villa Nicolás Romero' => 'Nicolás Romero',
        'Santiago Tianguistenco' => 'Tianguistenco',
        'Santiago Tiangustenco' => 'Tianguistenco',
        'Amecameca de Juárez' => 'Amecameca',
        'Amecameca' => 'Amecameca',
        'Chalco de Díaz Covarrubias' => 'Chalco',
        'Texcoco de Mora' => 'Texcoco',
        'Cuautitlán de Romero Rubio' => 'Cuautitlán',
        'Zumpango de Ocampo' => 'Zumpango',
        'Metepec' => 'Metepec',
        'Valle de Bravo' => 'Valle de Bravo',
        'Tenango del Valle' => 'Tenango del Valle',
        'Malinalco' => 'Malinalco',
        'Atlacomulco de Fabela' => 'Atlacomulco',
        'Villa del Carbón' => 'Villa del Carbón',
        'Lerma de Villada' => 'Lerma',
    ];

    /** @var array<string, array{0: float, 1: float}>|null */
    private static ?array $svgCoords = null;

    private static function getSvgCoords(): array
    {
        if (self::$svgCoords !== null) {
            return self::$svgCoords;
        }
        $path = __DIR__ . '/edomex_coords.json';
        if (File::exists($path)) {
            $json = File::get($path);
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                self::$svgCoords = $decoded;
                return self::$svgCoords;
            }
        }
        self::$svgCoords = [];
        return self::$svgCoords;
    }

    public function __construct(
        private ReporteAvanzadoService $reporteService
    ) {}

    /**
     * Obtiene route_data (nodos para el mapa) y tabla_detalle (filas para la tabla).
     *
     * @param string|null $desde       Fecha inicio (Y-m-d)
     * @param string|null $hasta       Fecha fin (Y-m-d)
     * @param string|null $municipio   Filtro por municipio (nombre)
     * @param string|null $especialidad Filtro por especialidad
     */
    public function obtenerHistorialRuta(
        ?string $desde = null,
        ?string $hasta = null,
        ?string $municipio = null,
        ?string $especialidad = null
    ): array {
        $asesorias = $this->reporteService->listarAsesorias($desde, $hasta, $municipio, $especialidad);

        if ($asesorias->isEmpty()) {
            return [
                'route_data' => [],
                'tabla_detalle' => [],
            ];
        }

        // Agrupar por (fecha, municipio) — un nodo por visita a municipio (realista para brigadas)
        $agrupadoRuta = $asesorias->groupBy(fn($a) => ($a['fecha'] ?? '') . '|' . ($a['municipio'] ?? ''));
        // tabla_detalle: desglose por (fecha, municipio, especialidad)
        $agrupadoDetalle = $asesorias->groupBy(fn($a) => ($a['fecha'] ?? '') . '|' . ($a['municipio'] ?? '') . '|' . ($a['especialidad'] ?? ''));

        $routeNodes = [];
        $tablaDetalle = [];
        $coords = self::getSvgCoords();

        $id = 1;
        foreach ($agrupadoRuta as $key => $grupo) {
            $first = $grupo->first();
            $fecha = $first['fecha'] ?? '';
            $mun = $first['municipio'] ?? 'Sin municipio';
            $svgKey = $this->toSvgKey($mun);

            // Solo incluir municipios que existen en el mapa
            if (!isset($coords[$svgKey]) || $svgKey === 'Sin municipio') {
                continue;
            }

            $totalAtendidos = $grupo->sum('cantidad');
            $especialidades = $grupo->groupBy('especialidad')->map(fn($g) => $g->sum('cantidad'));
            $espPrincipal = $especialidades->sortDesc()->keys()->first() ?? 'General';
            $descEsp = $especialidades->map(fn($v, $k) => "{$k}: {$v}")->implode(', ');

            [$x, $y] = $this->getCoords($svgKey);

            $routeNodes[] = [
                'id' => $id++,
                'date' => $fecha,
                'municipio' => $mun,
                'svgKey' => $svgKey,
                'esp' => $espPrincipal,
                'stats' => [
                    'atendidos' => $totalAtendidos,
                    'meta' => null,
                    'sats' => null,
                ],
                'x' => $x,
                'y' => $y,
                'desc' => "{$totalAtendidos} asesorías en {$mun} ({$descEsp}).",
            ];
        }

        foreach ($agrupadoDetalle as $key => $grupo) {
            $first = $grupo->first();
            $fecha = $first['fecha'] ?? '';
            $mun = $first['municipio'] ?? 'Sin municipio';
            $esp = $first['especialidad'] ?? 'Sin especialidad';
            $cantidad = $grupo->sum('cantidad');
            $tablaDetalle[] = [
                'date' => $fecha,
                'municipio' => $mun,
                'esp' => $esp,
                'stats' => ['atendidos' => $cantidad],
            ];
        }

        // Ordenar por fecha ascendente (más antigua primero) para la línea de tiempo
        usort($routeNodes, fn($a, $b) => strcmp($a['date'] ?? '', $b['date'] ?? ''));
        usort($tablaDetalle, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));

        return [
            'route_data' => array_values($routeNodes),
            'tabla_detalle' => array_values($tablaDetalle),
        ];
    }

    private function toSvgKey(string $municipio): string
    {
        $mun = trim($municipio);
        if ($mun === '') {
            return 'Sin municipio';
        }
        if (isset(self::MUNICIPIO_TO_SVG[$mun])) {
            return self::MUNICIPIO_TO_SVG[$mun];
        }
        $coords = self::getSvgCoords();
        // Coincidencia exacta
        if (isset($coords[$mun])) {
            return $mun;
        }
        // Coincidencia parcial (ej. "Ecatepec" en "Ecatepec de Morelos")
        foreach (array_keys($coords) as $svg) {
            if (stripos($svg, $mun) !== false || stripos($mun, $svg) !== false) {
                return $svg;
            }
        }
        return $mun;
    }

    private function getCoords(string $svgKey): array
    {
        $coords = self::getSvgCoords();
        if (isset($coords[$svgKey])) {
            return $coords[$svgKey];
        }
        return [50, 50];
    }
}
