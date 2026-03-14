<?php

namespace App\Http\Controllers;

use App\Models\BrCatPregunta;
use App\Models\BrCatOpcion;
use Illuminate\Http\Request;

use App\Models\BrPaciente;
use App\Models\BrAsesoriasPreguntasRespuestas;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\traits\carpeta_respuestas_t;

use Log;

class catalogosController extends Controller
{

    use carpeta_respuestas_t;


    public function __construct()
    {
        $this->Auth = Auth::guard('api');
        $this->respuesta_padre = 'catalogos';
        $this->respuesta_hijo = null;
        $this->construir_respuesta = [];
    }

    public function getCuestionarioAsesoria(Request $request){
        try{

            $beneficiarias_asesorias = null;
            Log::info("Obteniendo cuestionario para asesoría tipo ID: " . $request -> asesoria_tipo_id);

            $getPreguntasRespuestaPerAsesoria = BrCatPregunta::with('opciones')
            ->where('asesoria_tipo_id', $request -> asesoria_tipo_id)
            ->get();

                $this -> respuesta_hijo = 'consulta_success';
                $this -> construir_respuesta = $getPreguntasRespuestaPerAsesoria;
            
            if(count($getPreguntasRespuestaPerAsesoria) == 0){
                $this -> respuesta_hijo = 'consulta_sin_datos';
                $this -> construir_respuesta = null;
            }

            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
                

        }catch(\Exception $e){

            $this -> respuesta_hijo = 'consulta_fail';
            $this -> construir_respuesta = ['error' => $e -> getMessage()];
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
            
        }

    }

    
}
