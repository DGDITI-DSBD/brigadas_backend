<?php

namespace App\Http\Controllers;

use App\Models\BrCatPregunta;
use App\Models\BrCatOpcion;
use Illuminate\Http\Request;

use App\Models\BrPaciente;
use App\Models\BrAsesoriasPreguntasRespuestas;
use App\Models\BrDirectorio;

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

    public function getDirectorioEspecialistas(Request $request){

    try{
        $this->tipo_respuesta_padre = 'directorios';
        $id = $request->especialidad_id;

        $getDirectorios = BrDirectorio::select('id', 'primer_ap_dir', 'segundo_ap_dir', 'nombres')
        ->where('tipo_asesoria', $id)
        ->get();

        if(count($getDirectorios) <= 0){
            $this->respuesta_hijo='consulta_sin_datos';
            $this->construir_respuesta = null;
            return response()->json($this->typeResponse($this->tipo_respuesta_padre, $this->respuesta_hijo, $this->construir_respuesta));
        }
        $this->respuesta_hijo='consulta_success';
        $this->construir_respuesta = $getDirectorios;
        return response()->json($this->typeResponse($this->tipo_respuesta_padre, $this->respuesta_hijo, $this->construir_respuesta));



    }catch(Exception $e){
        $this->respuesta_hijo='consulta_fail';
        $this->construir_respuesta = ['error' => $e->getMessage()];
        return response()->json($this->typeResponse($this->tipo_respuesta_padre, $this->respuesta_hijo, $this->construir_respuesta));
    }
    }

    public function get_catalogos_generales() {
        try{

        $this -> consulta_padre = 'catalogos';

            $get_estados_civiles = DB::table('br_cat_estado_civil')->get();
            $get_escolaridad     = DB::table('br_cat_grado_estudios')->get();
            $get_nacionalidad    = DB::table('br_cat_nacionalidad')->get();
            $get_documentos_oficiales   = DB::table('br_cat_documentos_oficiales')->get();
            $get_entidades              = DB::table('br_cat_entidades_federativas')->get();
            $get_municipios             = DB::table('br_cat_municipios')->get();
            $get_localidades            = DB::table('br_cat_localidades')->get();
            $get_codigos_postales       = DB::table('br_cat_codigos_postales')->get();

            $this -> respuesta_hijo = 'consulta_success';
            $this -> construir_respuesta = [
                'estados_civiles' => $get_estados_civiles,
                'escolaridad' => $get_escolaridad,
                'nacionalidad' => $get_nacionalidad,
                'documentos_oficiales' => $get_documentos_oficiales,
                'entidades' => $get_entidades,
                'municipios' => $get_municipios,
                'localidades' => $get_localidades,
                'codigos_postales' => $get_codigos_postales
            ];

            return response() -> json($this -> typeResponse($this -> consulta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));



        }catch(Exception $e){
            $this -> consulta_hijo = 'consulta_fail';
            $this -> construir_respuesta = ['error' => $e -> getMessage()];
            return response() -> json($this -> typeResponse($this -> consulta_padre, $this -> consulta_hijo, $this -> construir_respuesta));

        }
    }

    
}
