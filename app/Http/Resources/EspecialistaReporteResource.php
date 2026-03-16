<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * EspecialistaReporteResource
 * Formatea la respuesta JSON para el dashboard de Reportes Avanzados del frontend React.
 */
class EspecialistaReporteResource extends JsonResource
{
    /**
     * $this->resource es el array retornado por ReporteAvanzadoService::generarReporteEspecialista()
     * Se envuelve en el sobre estándar ok/backlog/backdata del proyecto.
     */
    public function toArray(Request $request): array
    {
        $data = $this->resource;

        return [
            'ok' => true,
            'backlog' => 0,
            'message_description' => 'Reporte generado con éxito',
            'backdata' => [
                'filtros' => $data['filtros'],
                'especialista' => $data['especialista'],
                'resumen' => [
                    // Volúmenes
                    'total_asesorias' => $data['resumen']['total_asesorias'],
                    'total_pacientes' => $data['resumen']['total_pacientes'],
                    'municipios_atendidos' => $data['resumen']['municipios_atendidos'],
                    // Estados
                    'asesorias_abiertas' => $data['resumen']['asesorias_abiertas'],
                    'asesorias_cerradas' => $data['resumen']['asesorias_cerradas'],
                    'asesorias_canalizadas' => $data['resumen']['asesorias_canalizadas'],
                    'asesorias_pendientes' => $data['resumen']['asesorias_pendientes'],
                    // KPIs avanzados
                    'participacion_porcentaje' => $data['resumen']['participacion_porcentaje'],
                    'promedio_diario' => $data['resumen']['promedio_diario'],
                    'cobertura_municipal' => $data['resumen']['cobertura_municipal'],
                    'cobertura_porcentaje' => $data['resumen']['cobertura_porcentaje'],
                    'max_sesion_diaria' => $data['resumen']['max_sesion_diaria'],
                    'min_sesion_diaria' => $data['resumen']['min_sesion_diaria'],
                ],
                'por_municipio' => $data['por_municipio'],
                'por_mes' => $data['por_mes'],
                'por_estado' => $data['por_estado'],
                'lista_asesorias' => $data['lista_asesorias'],
            ],
        ];
    }
}
