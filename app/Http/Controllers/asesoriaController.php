<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\traits\asesorias_t;

use App\Models\BrPaciente;
use App\Models\BrAsesoriasPreguntasRespuestas;

use Illuminate\Support\Facades\Auth;
use DB;

class asesoriaController extends Controller
{


    public function __construct()
    {
        $this->Auth = Auth::guard('api');
        $this->respuesta_padre = null;
        $this->respuesta_hijo = null;
        $this->construir_respuesta = [];
    }

    public function beneficiaria_asesoria(Request $request){
        try{

            $_type_user = $this->Auth->user()->tipo_usuario;
            
            $this -> respuesta_padre = 'asesorias';
            $beneficiarias_asesorias = null;
            switch($_type_user){

                // Usuario administrador
                case 'ADMINISTRADOR':
                    $beneficiaria_asesorias = BrPaciente::with(['asesorias', 
                    'asesoria_canalizacion:id,descripcion',
                    'asesorias.directorio:id,primer_ap_dir,segundo_ap_dir,nombres,telefono,email'])
                    ->searchByParamsPacientes($request->all())
                    ->searchByProfesionista($request->get('directorio_id')) // Filtrar directorios asociados a cada profesión.
                    ->searchByMunicipio($request->get('municipio_id')) // Filtrar por municipio.
                    ->searchByLocalidad($request->get('localidad_id')) // Filtrar por localidad.
                    ->orderBy('created_at', 'desc')
                    ->paginate(10)
                    ->appends(request()->query());
                    if($beneficiaria_asesorias->isEmpty()){
                        $this -> respuesta_hijo = 'consulta_sin_datos';
                        $this -> construir_respuesta = [];
                    }
                        $this -> respuesta_hijo = 'consulta_exitosa';
                        $this -> construir_respuesta = $beneficiaria_asesorias;
                break;

                case 'COORDINADOR':

                    $beneficiaria_asesorias = BrPaciente::with(['asesorias', 
                    'asesoria_canalizacion:id,descripcion',
                    'asesorias.directorio:id,primer_ap_dir,segundo_ap_dir,nombres,telefono,email'])
                    ->searchByParamsPacientes($request->all())
                    ->searchByProfesionista($request->get('directorio_id')) // Filtrar directorios asociados a cada profesión.
                    ->searchByMunicipio($request->get('municipio_id')) // Filtrar por municipio.
                    ->searchByLocalidad($request->get('localidad_id')) // Filtrar por localidad.
                    ->orderBy('created_at', 'desc')
                    ->paginate(10)
                    ->appends(request()->query());

                break;

                case 'ESPECIALISTA':

                    $_has_directorio = $this->Auth->user()->directorios;
                    
                    if(!$_has_directorio || $_has_directorio->isEmpty()){
                        $this -> respuesta_padre = 'directorios';
                        $this -> respuesta_hijo = 'usuario_not_directorio';
                        $this -> construir_respuesta = [];
                    }

                    $beneficiaria_asesorias = BrPaciente::whereHas('asesorias', function($query) use ($_has_directorio) {
                        $query->where('asesor_atendio_id', $_has_directorio->first()->id);
                    })
                    ->with(['asesorias' => function($query) use ($_has_directorio) {
                        $query->where('asesor_atendio_id', $_has_directorio->first()->id)
                        ->with('directorio:id,primer_ap_dir,segundo_ap_dir,nombres,telefono,email');
                    }, 
                    'asesoria_canalizacion:id,descripcion'])
                    ->searchByMunicipio($request->get('municipio_id')) // Filtrar por municipio.
                    ->searchByLocalidad($request->get('localidad_id')) // Filtrar por localidad.
                    ->orderBy('created_at', 'desc')
                    ->paginate(10)
                    ->appends(request()->query());

                    if($beneficiaria_asesorias->isEmpty()){
                        $this -> respuesta_hijo = 'consulta_sin_datos';
                        $this -> construir_respuesta = [];
                    }
                        $this -> respuesta_hijo = 'consulta_exitosa';
                        $this -> construir_respuesta = $beneficiaria_asesorias;
                break;


            }

                return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, []));
                

        }catch(\Exception $e){

            $this -> respuesta_hijo = 'busqueda_fail';
            $this -> construir_respuesta = ['error' => $e -> getMessage()];
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
            
        }

    }

    public function get_asesorias($id_beneficiaria){

        try{
                $_type_user = $this->Auth->user()->tipo_usuario;
                $this -> respuesta_padre = 'asesorias';

                switch ($_type_user) {
                    case 'ADMINISTRADOR':
                        $get_asesoria_beneficiaria = BrPaciente::with([
                            'asesorias' => function($query) use ($id_beneficiaria){
                                $query->where('paciente_id', $id_beneficiaria)
                                ->orderBy('created_at', 'desc')
                                ->with('directorio:id,primer_ap_dir,segundo_ap_dir,nombres,telefono,email')
                                ->with('asesoria_canalizacion:id,descripcion');
                            }
                        ])->where('id', $id_beneficiaria)
                        ->first();
                        break;
                    case 'ESPECIALISTA':
                        $_directorio = $this->Auth->user()->directorios->first();
                        $get_asesoria_beneficiaria = BrPaciente::with([
                            'asesorias' => function($query) use ($id_beneficiaria, $_directorio){
                                $query->where('paciente_id', $id_beneficiaria)
                                ->where('asesor_atendio_id', $_directorio->id)
                                ->orderBy('created_at', 'desc')
                                ->with('directorio:id,primer_ap_dir,segundo_ap_dir,nombres,telefono,email')
                                ->with('asesoria_canalizacion:id,descripcion');
                            }
                        ])->where('id', $id_beneficiaria)
                        ->first();
                    break;
                    default:

                        $get_asesoria_beneficiaria = BrPaciente::with([
                            'asesorias' => function($query) use ($id_beneficiaria){
                                $query->where('paciente_id', $id_beneficiaria)
                                ->orderBy('created_at', 'desc')
                                ->with('directorio:id,primer_ap_dir,segundo_ap_dir,nombres,telefono,email')
                                ->with('asesoria_canalizacion:id,descripcion');
                            }
                        ])->where('id', $id_beneficiaria)
                        ->first();


                    break;
                }
                

                if(!$get_asesoria_beneficiaria || $get_asesoria_beneficiaria->asesorias->isEmpty()){
                    $this -> respuesta_hijo = 'consulta_sin_datos';
                    $this -> construir_respuesta = [];
                }else{
                    $this -> respuesta_hijo = 'consulta_asesoria_success';
                    $this -> construir_respuesta = $get_asesoria_beneficiaria->asesorias;
                } 
                return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
        }catch(\Exception $e){

                $this -> respuesta_hijo = 'consulta_asesoria_fail';
                $this -> construir_respuesta = ['error' => $e -> getMessage()];
                return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));

        }
    }

    public function get_asesoria_preguntas_respuestas($id_asesoria){

        try{
            $this -> respuesta_padre = 'asesorias';

            $getPreguntasRespuestas = BrAsesoriasPreguntasRespuestas::with([
                'pregunta:id,descripcion',
                'opcion:id,descripcion'
            ])
            ->where('asesoria_id', $id_asesoria)->first();

            if(!$getPreguntasRespuestas){
                $this -> respuesta_hijo = 'consulta_preguntas_respuestas_sin_datos';
                $this -> construir_respuesta = [];
            }else{
                $this -> respuesta_hijo = 'consulta_preguntas_respuestas_success';
                $this -> construir_respuesta = $getPreguntasRespuestas;
            }

            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));

        }catch(\Exception $e){

                $this -> respuesta_hijo = 'consulta_preguntas_respuestas_fail';
                $this -> construir_respuesta = ['error' => $e -> getMessage()];
                return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));

        }


    }

    public function create_asesoria_preguntas_respuestas(Request $request){

            try{

                // Validación de datos de preguntas y opciones 
                $validatedData = $request->validate([
                    'asesoria_id'    => 'required|exists:br_asesorias,id',
                    'pregunta_id'    => 'required|exists:br_cat_preguntas,id',
                    'opcion_id'      => 'nullable|exists:br_cat_opciones,id',
                    'valor_contexto' => 'nullable|string|max:1000',
                    'pregunta_id.*' => 'required|exists:br_cat_preguntas,id',
                    'opcion_id.*'   => 'nullable|exists:br_cat_opciones,id',
                    'valor_contexto.*' => 'nullable|string|max:1000',
                ]);

                if(!is_array($validatedData['pregunta_id']) || empty($validatedData['pregunta_id'])){
                        $this -> respuesta_hijo = 'excepcion_validar_preguntas_respuestas';
                        $this -> construir_respuesta = ['error' => 'La pregunta_id debe ser un array no vacío.'];
                        return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
                }

                    $preguntas_respuestas_data = [];
                    foreach ($validatedData['pregunta_id'] as $index => $pregunta_id) {
                        $preguntas_respuestas_data[] = [
                            'asesoria_id'    => $validatedData['asesoria_id'],
                            'pregunta_id'    => $pregunta_id,
                            'opcion_id'      => $validatedData['opcion_id'][$index] ?? null,
                            'valor_contexto' => $validatedData['valor_contexto'][$index] ?? null,
                            'created_at'     => now(),
                            'updated_at'     => now(),
                        ];
                    }

                
                try {
                    DB::beginTransaction();
                    BrAsesoriasPreguntasRespuestas::insert($preguntas_respuestas_data);
                    DB::commit();
                    $this -> respuesta_hijo = 'insercion_preguntas_respuestas_success';
                    $this -> construir_respuesta = ['message' => 'Preguntas y respuestas insertadas exitosamente.'];
                    return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
                } catch (\Throwable $th) {
                        $this -> respuesta_hijo = 'excepcion_transaccion_preguntas_respuestas';
                        $this -> construir_respuesta = ['error' => 'Error al iniciar la transacción: ' . $th->getMessage()];
                        return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
                }
                    
                    

            }catch(\Exception $e){
                    $this -> respuesta_hijo = 'insercion_preguntas_respuestas_fail';
                    $this -> construir_respuesta = ['error' => 'Error al insertar preguntas y respuestas: ' . $e->getMessage()];
                    return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
            }

    }

    public function update_asesoria_preguntas_respuestas(Request $request, $id){

    }

    public function delete_asesoria_preguntas_respuestas($id){

    }

    public function canalizar_asesoria(Request $request, $id){

    }

    public function estado_update_asesoria(Request $request, $id){
    }
}
