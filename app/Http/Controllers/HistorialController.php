<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\carpeta_respuestas_t;
use App\Services\HistorialService;

class HistorialController extends Controller
{
    use carpeta_respuestas_t;

    protected HistorialService $service;
    protected string $respuesta_padre = 'historial';

    public function __construct(HistorialService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/v1/historial/ruta
     * Parámetros: desde, hasta, municipio, especialidad (todos opcionales)
     */
    public function ruta(Request $request)
    {
        try {
            $desde = $request->get('desde');
            $hasta = $request->get('hasta');
            $municipio = $request->get('municipio');
            $especialidad = $request->get('especialidad');

            $resultado = $this->service->obtenerHistorialRuta($desde, $hasta, $municipio, $especialidad);

            $key = empty($resultado['route_data']) ? 'historial_sin_datos' : 'historial_success';
            return response()->json($this->typeResponse($this->respuesta_padre, $key, $resultado));
        } catch (\Exception $e) {
            return response()->json(
                $this->typeResponse($this->respuesta_padre, 'historial_fail', ['error' => $e->getMessage()])
            );
        }
    }
}
