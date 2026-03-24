<?php

namespace App\Http\Controllers;

use App\Models\BrAsesoria;
use Illuminate\Http\Request;

use App\Models\BrPaciente;
use App\Models\BrAsesoriasPreguntasRespuestas;

use App\Models\BrPadronHistorico;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\traits\carpeta_respuestas_t;
use Log;


class asesoriaController extends Controller
{

    use carpeta_respuestas_t;

    public function __construct()
    {
        $this->Auth = Auth::guard('api');
        $this->respuesta_padre = null;
        $this->respuesta_hijo = null;
        $this->construir_respuesta = [];
    }

    public function asesoria_beneficiaria(Request $request){
         try{

             //$_type_user = $this->Auth->user()->tipo_usuario;
             $this -> respuesta_padre = 'asesorias';
             $_type_user = 'ADMINISTRADOR';
             
             $paramsSearch = $request->params;
             $asesorias = BrAsesoria::with(['paciente', 'preguntasRespuestas', 'preguntasRespuestas.pregunta', 'asesoria_canalizacion' ,'directorio', 'directorio.tipoAsesoria']);
             $deepQuery = null;
             if($_type_user === 'ADMINISTRADOR' || $_type_user === 'COORDINADOR'){

                
                $deepQuery = $asesorias->searchByParamsPacientes($paramsSearch ?? [])
                                       ->searchByProfesionista($request->get('directorio_id' ) ?? null);
                            
            } else {

                //$deepUser = $_type_user->directorio->id;
                $deepUser = 3;

                $deepQuery = $asesorias->searchByParamsPacientes($paramsSearch ?? [])
                                        ->searchByProfesionista($deepUser);

            }

            if($deepQuery->get()->isEmpty()){
                $this -> respuesta_hijo = 'consulta_sin_datos';
                $this -> construir_respuesta = [];
            }
                $this -> respuesta_hijo = 'consulta_exitosa';
                $this -> construir_respuesta = $deepQuery
                ->orderBy('fecha_asesoria', 'desc')
                ->get();
                return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));

         }catch(Exception $e){

            $this -> respuesta_hijo = 'consulta_fail';
            $this -> construir_respuesta = ['error' => $e -> getMessage()];
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));

         }
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
                    'asesorias.asesoria_canalizacion:id,descripcion',
                    'asesorias.directorio:id,primer_ap_dir,segundo_ap_dir,nombres,telefono,email'])
                    ->searchByParamsPacientes($request->all() ?? [])
                    ->searchByProfesionista($request->get('directorio_id' ) ?? null) // Filtrar directorios asociados a cada profesión.
                    ->searchByMunicipio($request->get('municipio_id') ?? null) // Filtrar por municipio.
                    ->searchByLocalidad($request->get('localidad_id') ?? null) // Filtrar por localidad.
                    ->orderBy('created_at', 'desc')
                    ->paginate(10)
                    ->appends(request()->query());
                    if($beneficiaria_asesorias->isEmpty()){
                        $this -> respuesta_hijo = 'consulta_sin_datos';
                        $this -> construir_respuesta = [];
                    }
                        $this -> respuesta_hijo = 'consulta_exitosa';
                        $this -> construir_respuesta = $beneficiaria_asesorias;
                    Log::info('Beneficiarias con asesorías para ADMINISTRADOR: ' . $beneficiaria_asesorias->count());
                break;

                case 'COORDINADOR':

                    $beneficiaria_asesorias = BrPaciente::with(['asesorias', 
                    'asesorias.asesoria_canalizacion:id,descripcion',
                    'asesorias.directorio:id,primer_ap_dir,segundo_ap_dir,nombres,telefono,email'])
                    ->searchByParamsPacientes($request->all() ?? [])
                    ->searchByProfesionista($request->get('directorio_id' ) ?? null) // Filtrar directorios asociados a cada profesión.
                    ->searchByMunicipio($request->get('municipio_id') ?? null) // Filtrar por municipio.
                    ->searchByLocalidad($request->get('localidad_id') ?? null) // Filtrar por localidad.
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

                case 'ESPECIALISTA':

                    $_has_directorio = $this->Auth->user()->directorio;
                    
                    if(!$_has_directorio){
                        $this -> respuesta_padre = 'directorios';
                        $this -> respuesta_hijo = 'usuario_not_directorio';
                        $this -> construir_respuesta = [];
                    }

                    $beneficiaria_asesorias = BrPaciente::whereHas('asesorias', function($query) use ($_has_directorio) {
                        $query->where('asesor_atendio_id', $_has_directorio->id);
                    })
                    ->with(['asesorias' => function($query) use ($_has_directorio) {
                        $query->where('asesor_atendio_id', $_has_directorio->first()->id)
                        ->with('directorio:id,primer_ap_dir,segundo_ap_dir,nombres,telefono,email');
                    }, 
                    'asesorias.asesoria_canalizacion:id,descripcion'])
                    ->searchByMunicipio($request->get('municipio_id') ?? null) // Filtrar por municipio.
                    ->searchByLocalidad($request->get('localidad_id') ?? null) // Filtrar por localidad.
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

                return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
                

        }catch(\Exception $e){
            Log::error('Error al consultar beneficiarias con asesorías: ' . $e->getMessage(), ['exception' => $e]);
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
                        $_directorio = $this->Auth->user()->directorio;
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

    public function create_asesoria(Request $request){
        try{
            $this -> respuesta_padre = 'asesorias';

            $validatedData = $request->validate([
                'asesor_atendio_id'         => 'required|exists:br_directorio,id',
                'paciente_id'               => 'required|exists:br_pacientes,id',
                'estado_asesoria'           => 'required|in:ABIERTA,CERRADA,CANALIZADA',
                'tipo_asesoria_canaliza_id' => 'nullable|exists:br_cat_tipo_asesorias,id',
            ]);

            // Si el estado es CANALIZADA, tipo_asesoria_canaliza_id es obligatorio
            if ($validatedData['estado_asesoria'] === 'CANALIZADA' && empty($validatedData['tipo_asesoria_canaliza_id'])) {
                $this -> respuesta_hijo = 'validacion_asesoria_fail';
                $this -> construir_respuesta = ['error' => 'El campo tipo_asesoria_canaliza_id es obligatorio cuando el estado es CANALIZADA.'];
                return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta), 422);
            }

            $asesoria = BrAsesoria::create([
                'asesor_atendio_id'         => $validatedData['asesor_atendio_id'],
                'paciente_id'               => $validatedData['paciente_id'],
                'estado_asesoria'           => $validatedData['estado_asesoria'],
                'estado_registro'           => true,
                'tipo_asesoria_canaliza_id' => $validatedData['tipo_asesoria_canaliza_id'] ?? null,
            ]);

            $this -> respuesta_hijo = 'creacion_asesoria_success';
            $this -> construir_respuesta = $asesoria;
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta), 201);

        }catch(\Illuminate\Validation\ValidationException $e){
            $this -> respuesta_hijo = 'validacion_asesoria_fail';
            $this -> construir_respuesta = ['errors' => $e->errors()];
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta), 422);
        }catch(\Exception $e){
            Log::error('Error al crear asesoría: ' . $e->getMessage() . ' | Request: ' . json_encode($request->all()));
            $this -> respuesta_hijo = 'creacion_asesoria_fail';
            $this -> construir_respuesta = ['error' => $e -> getMessage()];
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta), 500);
        }
    }

    public function update_asesoria(Request $request, $id){
        try{
            $this -> respuesta_padre = 'asesorias';

            $asesoria = BrAsesoria::find($id);
            if(!$asesoria){
                $this -> respuesta_hijo = 'consulta_asesoria_sin_datos';
                $this -> construir_respuesta = [];
                return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta), 404);
            }

            $validatedData = $request->validate([
                'asesor_atendio_id'         => 'sometimes|required|exists:br_directorio,id',
                'paciente_id'               => 'sometimes|required|exists:br_pacientes,id',
                'estado_asesoria'           => 'sometimes|required|in:ABIERTA,CERRADA,CANALIZADA',
                'estado_registro'           => 'sometimes|required|in:ACTIVO,INACTIVO',
                'tipo_asesoria_canaliza_id' => 'nullable|exists:br_cat_tipo_asesorias,id',
            ]);

            // Si el estado es CANALIZADA, tipo_asesoria_canaliza_id es obligatorio
            if (isset($validatedData['estado_asesoria']) && $validatedData['estado_asesoria'] === 'CANALIZADA' && empty($validatedData['tipo_asesoria_canaliza_id'])) {
                $this -> respuesta_hijo = 'validacion_asesoria_fail';
                $this -> construir_respuesta = ['error' => 'El campo tipo_asesoria_canaliza_id es obligatorio cuando el estado es CANALIZADA.'];
                return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta), 422);
            }

            $asesoria->update($validatedData);

            $this -> respuesta_hijo = 'actualizacion_asesoria_success';
            $this -> construir_respuesta = $asesoria->fresh();
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));

        }catch(\Illuminate\Validation\ValidationException $e){
            $this -> respuesta_hijo = 'validacion_asesoria_fail';
            $this -> construir_respuesta = ['errors' => $e->errors()];
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta), 422);
        }catch(\Exception $e){
            $this -> respuesta_hijo = 'actualizacion_asesoria_fail';
            $this -> construir_respuesta = ['error' => $e -> getMessage()];
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta), 500);
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

            $this -> respuesta_padre = 'asesorias';

                // Validación de datos de preguntas y opciones 
                Log::info('Validando datos de preguntas y respuestas para asesoría con datos: ' . json_encode($request->all()));
                $validatedData = $request->validate([
                    'asesoria_id' => 'required|exists:br_asesorias,id',
                    'pregunta_id' => 'required|array',
                    'pregunta_id.*' => 'required|exists:br_cat_preguntas,id',
                    'opcion_id' => 'required|array',
                    'opcion_id.*' => 'nullable|exists:br_cat_opciones,id',
                    'valor_contexto' => 'nullable|array',
                    'valor_contexto.*' => 'nullable|string',
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
                        Log::error('Error al iniciar la transacción para insertar preguntas y respuestas: ' . $th->getMessage() . ' | Data: ' . json_encode($preguntas_respuestas_data));
                        $this -> respuesta_hijo = 'excepcion_transaccion_preguntas_respuestas';
                        $this -> construir_respuesta = ['error' => 'Error al iniciar la transacción: ' . $th->getMessage()];
                        return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
                }
                    
                    

            }catch(\Exception $e){
                    $this -> respuesta_hijo = 'insercion_preguntas_respuestas_fail';
                    Log::error('Error al insertar preguntas y respuestas: ' . $e->getMessage() . ' | Request: ' . json_encode($request->all()));
                    $this -> construir_respuesta = ['error' => 'Error al insertar preguntas y respuestas: ' . $e->getMessage()];
                    return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
            }

    }

    public function update_asesoria_preguntas_respuestas(Request $request, $id){

        try{
            $validatedData = $request->validate([
                'id'                => 'required|array',
                'id.*'              => 'required|exists:br_asesorias_preguntas_respuestas,id',
                'pregunta_id'       => 'required|array',
                'pregunta_id.*'     => 'required|exists:br_cat_preguntas,id',
                'opcion_id'         => 'nullable|array',
                'opcion_id.*'       => 'nullable|exists:br_cat_opciones,id',
                'valor_contexto'    => 'nullable|array',
                'valor_contexto.*'  => 'nullable|string|max:1000',
            ]);

            // Validar que los arrays tengan el mismo tamaño y no estén vacíos
            $total = count($validatedData['id']);
            $error = null;
            if ($total === 0 || count($validatedData['pregunta_id']) !== $total) {
                $error = 'Los arrays id y pregunta_id deben ser no vacíos y del mismo tamaño.';
            }
            if (array_key_exists('opcion_id', $validatedData) && is_array($validatedData['opcion_id']) && count($validatedData['opcion_id']) !== $total) {
                $error = 'El array opcion_id debe tener el mismo tamaño que id.';
            }
            if (array_key_exists('valor_contexto', $validatedData) && is_array($validatedData['valor_contexto']) && count($validatedData['valor_contexto']) !== $total) {
                $error = 'El array valor_contexto debe tener el mismo tamaño que id.';
            }
            if ($error) {
                $this->respuesta_hijo = 'excepcion_validar_preguntas_respuestas';
                $this->construir_respuesta = ['error' => $error];
                return response()->json($this->typeResponse($this->respuesta_padre, $this->respuesta_hijo, $this->construir_respuesta));
            }

            // Procesar cada registro para actualización
            $actualizados = [];
            $sin_cambios = [];
            for ($i = 0; $i < $total; $i++) { 
                $registro = BrAsesoriasPreguntasRespuestas::find($validatedData['id'][$i]);
                if (!$registro) {
                    $sin_cambios[] = [
                        'id' => $validatedData['id'][$i],
                        'error' => 'Registro no encontrado.'
                    ];
                    continue;
                }
                $cambios = [];
                if ($registro->pregunta_id != $validatedData['pregunta_id'][$i]) {
                    $cambios['pregunta_id'] = $validatedData['pregunta_id'][$i];
                }
                if (
                    (array_key_exists('opcion_id', $validatedData) &&
                    array_key_exists($i, $validatedData['opcion_id']) &&
                    $registro->opcion_id != $validatedData['opcion_id'][$i])
                ) {
                    $cambios['opcion_id'] = $validatedData['opcion_id'][$i];
                }
                if (
                    (array_key_exists('valor_contexto', $validatedData) &&
                    array_key_exists($i, $validatedData['valor_contexto']) &&
                    $registro->valor_contexto != $validatedData['valor_contexto'][$i])
                ) {
                    $cambios['valor_contexto'] = $validatedData['valor_contexto'][$i];
                }
                if (!empty($cambios)) {
                    $registro->update($cambios);
                    $actualizados[] = [
                        'id' => $registro->id,
                        'cambios' => $cambios
                    ];
                } else {
                    $sin_cambios[] = [
                        'id' => $registro->id,
                        'message' => 'No se realizaron cambios porque los datos son iguales.'
                    ];
                }
            }
            $this->respuesta_hijo = 'actualizacion_pregunta_respuesta_success';
            $this->construir_respuesta = [
                'actualizados' => $actualizados,
                'sin_cambios' => $sin_cambios
            ];
            return response()->json($this->typeResponse($this->respuesta_padre, $this->respuesta_hijo, $this->construir_respuesta));
        }catch(\Exception $e){
            $this->respuesta_hijo = 'actualizacion_pregunta_respuesta_fail';
            $this->construir_respuesta = ['error' => 'Error al actualizar pregunta y respuesta: ' . $e->getMessage()];
            return response()->json($this->typeResponse($this->respuesta_padre, $this->respuesta_hijo, $this->construir_respuesta));
        }
    }

    public function delete_asesoria_preguntas_respuestas($id){
        try{
            $pregunta_respuesta = BrAsesoriasPreguntasRespuestas::find($id);
            if(!$pregunta_respuesta){
                $this -> respuesta_hijo = 'consulta_pregunta_respuesta_sin_datos';
                $this -> construir_respuesta = [];
                return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
            }

            $pregunta_respuesta->delete();
            $this -> respuesta_hijo = 'eliminacion_pregunta_respuesta_success';
            $this -> construir_respuesta = ['message' => 'Pregunta y respuesta eliminada exitosamente.'];
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
        }catch(\Exception $e){
            $this -> respuesta_hijo = 'eliminacion_pregunta_respuesta_fail';
            $this -> construir_respuesta = ['error' => 'Error al eliminar pregunta y respuesta: ' . $e->getMessage()];
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
        }



    }

    public function canalizar_asesoria(Request $request, $id){

        try{
            $asesoria = BrAsesoria::find($id);
            if(!$asesoria){
                $this -> respuesta_hijo = 'consulta_asesoria_sin_datos';
                $this -> construir_respuesta = [];
                return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
            }

            $validatedData = $request->validate([
                'canalizacion_id' => 'required|exists:br_cat_canalizaciones,id',
                'observaciones'   => 'nullable|string|max:1000',
            ]);

            $asesoria->update([
                'canalizacion_id' => $validatedData['canalizacion_id'],
                'observaciones'   => $validatedData['observaciones'] ?? null,
                'estado'         => 'CANALIZADA', // Cambiar el estado a canalizada
            ]);

            $this -> respuesta_hijo = 'canalizacion_asesoria_success';
            $this -> construir_respuesta = ['message' => 'Asesoría canalizada exitosamente.'];
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
        
        }catch(\Exception $e){
            $this -> respuesta_hijo = 'canalizacion_asesoria_fail';
            $this -> construir_respuesta = ['error' => 'Error al canalizar asesoría: ' . $e->getMessage()];
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
        }
}
                        
    public function estado_update_asesoria(Request $request, $id){
        try{
            $asesoria = BrAsesoria::find($id);
            if(!$asesoria){
                $this -> respuesta_hijo = 'consulta_asesoria_sin_datos';
                $this -> construir_respuesta = [];
                return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
            }

            $validatedData = $request->validate([
                'estado' => 'required|in:EN PROCESO,FINALIZADA,CANCELADA',
            ]);

            $asesoria->update([
                'estado' => $validatedData['estado'],
            ]);

            $this -> respuesta_hijo = 'actualizacion_estado_asesoria_success';
            $this -> construir_respuesta = ['message' => 'Estado de asesoría actualizado exitosamente.'];
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));

        }catch(\Exception $e){
            $this -> respuesta_hijo = 'actualizacion_estado_asesoria_fail';
            $this -> construir_respuesta = ['error' => 'Error al actualizar estado de asesoría: ' . $e->getMessage()];
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
        }
    }

    public function get_beneficiarias_historico(Request $request){
            try{
                $this -> respuesta_padre = 'catalogos';
                Log::info('Iniciando consulta de beneficiarias en histórico con parámetros: ' . json_encode($request->all()));

                $beneficiarias_historico = BrPadronHistorico::select('folio_relacionado', 
                'primer_ap', 'segundo_ap', 'nombres', 'fecha_nacimiento', 'curp', 'ct_municipio', 'municipio', 
                'genero', 'edad', 'ct_edo_civil', 'ct_escolaridad', 'tp_id_oficial', 'id_oficial',
                'ct_ent_nac', 'calle', 'num_ext', 'num_int', 'entre_calle', 'y_calle', 'otra_referencia', 
                'colonia', 'ct_localidad', 'localidad', 'ct_entidad_federativa', 'entidad_federativa', 'codigo_postal_id', 'codigo_postal', 
                'telefono', 'celular', 'email', 'ct_nacionalidad')
                ->searching($request->get('search') ?? null)
                ->take(5)
                ->get();

                if($beneficiarias_historico->isEmpty()){
                    $this -> respuesta_hijo = 'consulta_sin_datos';
                    $this -> construir_respuesta = [];
                }else{
                    $this -> respuesta_hijo = 'consulta_success';
                    $this -> construir_respuesta = $beneficiarias_historico;
                }   

                return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
            }catch(\Exception $e){
                $this -> respuesta_hijo = 'consulta_fail';
                $this -> construir_respuesta = ['error' => 'Error al consultar beneficiarias en histórico: ' . $e->getMessage()];
                return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
            }
    }

    public function import_padron_historico(Request $request){
        try{
            $this -> respuesta_padre = 'pacientes';

            Log::info('Iniciando importación de padrón histórico con datos: ' . json_encode($request->all()));

            $validatedData = $request->validate([
                'folio_relacionado' => 'required|string',
                'primer_ap'         => 'required|string',
                'segundo_ap'        => 'nullable|string',
                'nombres'           => 'required|string',
                'fecha_nacimiento'  => 'nullable|date',
                'edad'              => 'nullable|integer',
                'genero'            => 'nullable|string',
                'ct_edo_civil'      => 'nullable|integer',
                'ct_escolaridad'   => 'nullable|integer',
                'tp_id_oficial'     => 'nullable|integer',
                'id_oficial'       => 'nullable|string',
                'ct_ent_nac'       => 'nullable|integer',
                'curp'              => 'nullable|string',
                'calle'             => 'nullable|string',
                'num_ext'          => 'nullable|string',
                'num_int'          => 'nullable|string',
                'entre_calle'      => 'nullable|string',
                'y_calle'          => 'nullable|string',
                'otra_referencia'  => 'nullable|string',
                'colonia'          => 'nullable|string',
                'ct_municipio'      => 'nullable|integer',
                'municipio'         => 'nullable|string',
                'ct_localidad'      => 'nullable',
                'localidad'         => 'nullable|string',
                'ct_entidad_federativa' => 'nullable|integer',
                'entidad_federativa' => 'nullable|string',
                'codigo_postal_id' => 'required|integer|exists:br_cat_codigos_postales,ct_cp',
                'codigo_postal'    => 'nullable|string',
                'telefono'          => 'nullable|string',
                'celular'           => 'nullable|string',
                'email'             => 'nullable|string',
                'ct_nacionalidad'   => 'nullable|integer',
            ]);

           

            // Buscar si ya existe el paciente por folio_relacionado o CURP
            $paciente = BrPaciente::where('folio_relacionado', $validatedData['folio_relacionado'])
                ->orWhere(function($query) use ($validatedData) {
                    if (!empty($validatedData['curp'])) {
                        $query->where('curp', $validatedData['curp']);
                    }
                })
                ->first();

            if ($paciente) {
                // Si ya existe, devolver el paciente existente
                $this -> respuesta_hijo = 'paciente_existente';
                $this -> construir_respuesta = $paciente;
                return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta));
            }

            

            // Crear nuevo paciente
            $paciente = BrPaciente::create([
                'folio_relacionado'  => $validatedData['folio_relacionado'],
                'primer_ap'          => $validatedData['primer_ap'],
                'segundo_ap'         => $validatedData['segundo_ap'] ?? '',
                'nombres'            => $validatedData['nombres'],
                'fecha_nacimiento'   => $validatedData['fecha_nacimiento'] ?? null,
                'edad'              => $validatedData['edad'] ?? $request->edad ?? null,
                'genero'             => $validatedData['genero'] ?? null,
                'ct_edo_civil'       => $validatedData['ct_edo_civil'] ?? null,
                'ct_gdo_estudios'    => $validatedData['ct_escolaridad'] ?? null,
                'tp_id_oficial'      => $validatedData['tp_id_oficial'] ?? null,
                'id_oficial'        => $validatedData['id_oficial'] ?? null,
                'ct_ent_nac'        => $validatedData['ct_ent_nac'] ?? null,
                'curp'               => $validatedData['curp'] ?? null,
                'calle'              => $validatedData['calle'] ?? null,
                'num_ext'           => $validatedData['num_ext'] ?? null,
                'num_int'           => $validatedData['num_int'] ?? null,
                'entre_calle'       => $validatedData['entre_calle'] ?? null,
                'y_calle'           => $validatedData['y_calle'] ?? null,
                'otra_referencia'   => $validatedData['otra_referencia'] ?? null,
                'colonia'           => $validatedData['colonia'] ?? null,
                'ct_municipio'       => $validatedData['ct_municipio'] ?? null,
                'municipio'          => $validatedData['municipio'] ?? null,
                'ct_localidad'       => $validatedData['ct_localidad'] ?? null,
                'localidad'          => $validatedData['localidad'] ?? null,
                'ct_entidad_federativa' => $validatedData['ct_entidad_federativa'] ?? null,
                'entidad_federativa' => $validatedData['entidad_federativa'] ?? null,
                'codigo_postal_id'  => $validatedData['codigo_postal_id'],
                'codigo_postal'     => $validatedData['codigo_postal'] ?? null,
                'telefono'           => $validatedData['telefono'] ?? null,
                'celular'            => $validatedData['celular'] ?? null,
                'email'              => $validatedData['email'] ?? null,
                'ct_nacionalidad'    => $validatedData['ct_nacionalidad'] ?? null,
                'estado_padron'      => 'ACTIVO',
                'requiere_seguimiento'  => false
            ]);

            $this -> respuesta_hijo = 'paciente_creado_success';
            $this -> construir_respuesta = $paciente;
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta), 201);

        }catch(\Illuminate\Validation\ValidationException $e){
            $this -> respuesta_hijo = 'validacion_paciente_fail';
            Log::info('Error de validación al importar paciente desde padrón histórico: ' . json_encode($e->errors()) . ' | Request: ' . json_encode($request->all()));
            $this -> construir_respuesta = ['errors' => $e->errors()];
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta), 422);
        }catch(\Exception $e){
            Log::info('Datos validados para importar paciente desde padrón histórico: ' . json_encode($validatedData));
            Log::error('Error al importar paciente desde padrón histórico: ' . $e->getMessage() . ' | Request: ' . json_encode($request->all()));
            $this -> respuesta_hijo = 'creacion_paciente_fail';
            $this -> construir_respuesta = ['error' => 'Error al crear paciente: ' . $e->getMessage()];
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta), 500);
        }
    }

    /**
     * Crear asesoría completa con transacción atómica
     * Maneja: importación de paciente, creación de asesoría y registro de preguntas-respuestas
     * Todo en una transacción para evitar registros huérfanos
     */
    public function crear_asesoria_completa(Request $request){
        try{
            $this -> respuesta_padre = 'asesorias';

            Log::info('Iniciando creación de asesoría completa con datos: ' . json_encode($request->all()));

            // Validar todos los datos necesarios
            $validatedData = $request->validate([
                // Datos del paciente (para importar si no existe)
                'paciente.folio_relacionado' => 'required|string',
                'paciente.primer_ap'         => 'required|string',
                'paciente.segundo_ap'        => 'nullable|string',
                'paciente.nombres'           => 'required|string',
                'paciente.fecha_nacimiento'  => 'nullable|date',
                'paciente.edad'              => 'nullable|integer',
                'paciente.genero'            => 'nullable|string',
                'paciente.ct_edo_civil'      => 'nullable|integer',
                'paciente.ct_escolaridad'    => 'nullable|integer',
                'paciente.tp_id_oficial'     => 'nullable|integer',
                'paciente.id_oficial'        => 'nullable|string',
                'paciente.ct_ent_nac'        => 'nullable|integer',
                'paciente.curp'              => 'nullable|string',
                'paciente.calle'             => 'nullable|string',
                'paciente.num_ext'           => 'nullable|string',
                'paciente.num_int'           => 'nullable|string',
                'paciente.entre_calle'       => 'nullable|string',
                'paciente.y_calle'           => 'nullable|string',
                'paciente.otra_referencia'   => 'nullable|string',
                'paciente.colonia'           => 'nullable|string',
                'paciente.ct_municipio'      => 'nullable|integer',
                'paciente.municipio'         => 'nullable|string',
                'paciente.ct_localidad'      => 'nullable',
                'paciente.localidad'         => 'nullable|string',
                'paciente.ct_entidad_federativa' => 'nullable|integer',
                'paciente.entidad_federativa' => 'nullable|string',
                'paciente.codigo_postal_id'  => 'required|integer|exists:br_cat_codigos_postales,ct_cp',
                'paciente.codigo_postal'     => 'nullable|string',
                'paciente.telefono'          => 'nullable|string',
                'paciente.celular'           => 'nullable|string',
                'paciente.email'             => 'nullable|string',
                'paciente.ct_nacionalidad'   => 'nullable|integer',
                

                // Datos de la asesoría
                'asesoria.asesor_atendio_id'         => 'required|exists:br_directorio,id',
                'asesoria.estado_asesoria'           => 'required|in:ABIERTA,CERRADA,CANALIZADA',
                'asesoria.tipo_asesoria_canaliza_id' => 'nullable|exists:br_cat_tipo_asesorias,id',
                'asesoria.fecha_asesoria'            => 'nullable|date',

                // Datos de preguntas y respuestas
                'respuestas.pregunta_id'       => 'required|array',
                'respuestas.pregunta_id.*'     => 'required|exists:br_cat_preguntas,id',
                'respuestas.opcion_id'         => 'required|array',
                'respuestas.opcion_id.*'       => 'nullable|exists:br_cat_opciones,id',
                'respuestas.valor_contexto'    => 'nullable|array',
                'respuestas.valor_contexto.*'  => 'nullable|string',
            ]);

            // Validación adicional: si estado es CANALIZADA, tipo_asesoria_canaliza_id es obligatorio
            if ($validatedData['asesoria']['estado_asesoria'] === 'CANALIZADA' && empty($validatedData['asesoria']['tipo_asesoria_canaliza_id'])) {
                $this -> respuesta_hijo = 'validacion_asesoria_fail';
                $this -> construir_respuesta = ['error' => 'El campo tipo_asesoria_canaliza_id es obligatorio cuando el estado es CANALIZADA.'];
                return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta), 422);
            }

            // Validación: arrays de respuestas deben tener el mismo tamaño
            if(!is_array($validatedData['respuestas']['pregunta_id']) || empty($validatedData['respuestas']['pregunta_id'])){
                $this -> respuesta_hijo = 'excepcion_validar_preguntas_respuestas';
                $this -> construir_respuesta = ['error' => 'La pregunta_id debe ser un array no vacío.'];
                return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta), 422);
            }

            // Iniciar transacción para garantizar atomicidad
            DB::beginTransaction();

            try {
                // PASO 1: Crear o buscar paciente
                $pacienteData = $validatedData['paciente'];
                
                $paciente = BrPaciente::where('folio_relacionado', $pacienteData['folio_relacionado'])
                    ->orWhere(function($query) use ($pacienteData) {
                        if (!empty($pacienteData['curp'])) {
                            $query->where('curp', $pacienteData['curp']);
                        }
                    })
                    ->first();

                if (!$paciente) {
                    // Crear nuevo paciente
                    $paciente = BrPaciente::create([
                        'folio_relacionado'      => $pacienteData['folio_relacionado'],
                        'primer_ap'              => $pacienteData['primer_ap'],
                        'segundo_ap'             => $pacienteData['segundo_ap'] ?? '',
                        'nombres'                => $pacienteData['nombres'],
                        'fecha_nacimiento'       => $pacienteData['fecha_nacimiento'] ?? null,
                        'edad'                   => $pacienteData['edad'] ?? null,
                        'genero'                 => $pacienteData['genero'] ?? null,
                        'ct_edo_civil'           => $pacienteData['ct_edo_civil'] ?? null,
                        'ct_gdo_estudios'        => $pacienteData['ct_escolaridad'] ?? null,
                        'tp_id_oficial'          => $pacienteData['tp_id_oficial'] ?? null,
                        'id_oficial'             => $pacienteData['id_oficial'] ?? null,
                        'ct_ent_nac'             => $pacienteData['ct_ent_nac'] ?? null,
                        'curp'                   => $pacienteData['curp'] ?? null,
                        'calle'                  => $pacienteData['calle'] ?? null,
                        'num_ext'                => $pacienteData['num_ext'] ?? null,
                        'num_int'                => $pacienteData['num_int'] ?? null,
                        'entre_calle'            => $pacienteData['entre_calle'] ?? null,
                        'y_calle'                => $pacienteData['y_calle'] ?? null,
                        'otra_referencia'        => $pacienteData['otra_referencia'] ?? null,
                        'colonia'                => $pacienteData['colonia'] ?? null,
                        'ct_municipio'           => $pacienteData['ct_municipio'] ?? null,
                        'municipio'              => $pacienteData['municipio'] ?? null,
                        'ct_localidad'           => $pacienteData['ct_localidad'] ?? null,
                        'localidad'              => $pacienteData['localidad'] ?? null,
                        'ct_entidad_federativa'  => $pacienteData['ct_entidad_federativa'] ?? null,
                        'entidad_federativa'     => $pacienteData['entidad_federativa'] ?? null,
                        'codigo_postal_id'       => $pacienteData['codigo_postal_id'],
                        'codigo_postal'          => $pacienteData['codigo_postal'] ?? null,
                        'telefono'               => $pacienteData['telefono'] ?? null,
                        'celular'                => $pacienteData['celular'] ?? null,
                        'email'                  => $pacienteData['email'] ?? null,
                        'ct_nacionalidad'        => $pacienteData['ct_nacionalidad'] ?? null,
                        'requiere_seguimiento'    => false,
                        'estado_padron'          => 'ACTIVO',
                    ]);

                    Log::info('Paciente creado con ID: ' . $paciente->id);
                } else {
                    Log::info('Paciente existente encontrado con ID: ' . $paciente->id);
                }

                // PASO 2: Crear asesoría
                $asesoriaData = $validatedData['asesoria'];
                
                $asesoria = BrAsesoria::create([
                    'asesor_atendio_id'         => $asesoriaData['asesor_atendio_id'],
                    'paciente_id'               => $paciente->id,
                    'estado_asesoria'           => $asesoriaData['estado_asesoria'],
                    'estado_registro'           => true,
                    'tipo_asesoria_canaliza_id' => $asesoriaData['tipo_asesoria_canaliza_id'] ?? null,
                    'fecha_asesoria'            => $asesoriaData['fecha_asesoria'] ?? now(),
                ]);

                Log::info('Asesoría creada con ID: ' . $asesoria->id);

                // PASO 3: Crear preguntas y respuestas
                $respuestasData = $validatedData['respuestas'];
                $preguntas_respuestas_data = [];

                foreach ($respuestasData['pregunta_id'] as $index => $pregunta_id) {
                    $preguntas_respuestas_data[] = [
                        'asesoria_id'    => $asesoria->id,
                        'pregunta_id'    => $pregunta_id,
                        'opcion_id'      => $respuestasData['opcion_id'][$index] ?? null,
                        'valor_contexto' => $respuestasData['valor_contexto'][$index] ?? null,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ];
                }

                BrAsesoriasPreguntasRespuestas::insert($preguntas_respuestas_data);

                Log::info('Preguntas y respuestas insertadas: ' . count($preguntas_respuestas_data) . ' registros');

                // Commit de la transacción
                DB::commit();

                $this -> respuesta_hijo = 'creacion_asesoria_completa_success';
                $this -> construir_respuesta = [
                    'paciente' => $paciente,
                    'asesoria' => $asesoria,
                    'respuestas_insertadas' => count($preguntas_respuestas_data)
                ];

                return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta), 201);

            } catch (\Throwable $th) {
                // Rollback automático en caso de error
                DB::rollBack();
                
                Log::error('Error en transacción de asesoría completa: ' . $th->getMessage() . ' | Trace: ' . $th->getTraceAsString());
                
                $this -> respuesta_hijo = 'creacion_asesoria_completa_fail';
                $this -> construir_respuesta = ['error' => 'Error en la transacción: ' . $th->getMessage()];
                return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta), 500);
            }

        }catch(\Illuminate\Validation\ValidationException $e){
            $this -> respuesta_hijo = 'validacion_asesoria_fail';
            $this -> construir_respuesta = ['errors' => $e->errors()];
            Log::info('Error de validación al crear asesoría completa: ' . json_encode($e->errors()) . ' | Request: ' . json_encode($request->all()));
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta), 422);
        }catch(\Exception $e){
            Log::error('Error al crear asesoría completa: ' . $e->getMessage() . ' | Request: ' . json_encode($request->all()));
            $this -> respuesta_hijo = 'creacion_asesoria_completa_fail';
            $this -> construir_respuesta = ['error' => $e -> getMessage()];
            return response() -> json($this -> typeResponse($this -> respuesta_padre, $this -> respuesta_hijo, $this -> construir_respuesta), 500);
        }
    }
}
