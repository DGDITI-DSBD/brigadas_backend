<?php

namespace App\Http\Controllers;

use App\Http\Requests\Store_Usuario_Request;
use App\Models\BrAcceso;
use App\Models\BrDirectorio;
use App\Models\UsuarioPrivilegio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class accesoController extends Controller
{
    public function getListUsuario()
    {
        try{
            $usuarios = BrAcceso::paginate(10);

            return response()->json(['data' => $usuarios], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener la lista de usuarios', 'message' => $e->getMessage()], 500);
        }

    }

    public function getFiltersListUsuario(Request $request)
    {
        try{
            Log::info('Filtros recibidos', $request->all());
            $tipo_usuario   = $request['tipo_usuario'];
            $estado         = $request['estatus'];
            $search         = $request['search'];

            $query = BrAcceso::query();
            if ($tipo_usuario) {
                $query->where('tipo_usuario', $tipo_usuario);
            }
            if ($estado) {
                $query->where('estado', $estado);
            }
            if ($search) {
                $query->where('nombre_completo',$search)
                    //   ->orWhere('nombre_usuario',$search)
                    //   ->orWhere('id', $search);
                ;
            }
            $usuarios = $query->get();

            return response()->json(['data' => $usuarios], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener la lista de usuarios con filtros', 'message' => $e->getMessage()], 500);
        }

    }

    public function StoreUsuario(Store_Usuario_Request $request)
    {
        try {
            DB::beginTransaction();
            log::info('Datos recibidos para crear usuario', $request->all());


            switch ($request['tipo_usuario']) {
                case 'ADMINISTRADOR':
                    $usuario = BrAcceso::create([
                        'nombre_usuario' => $request['nombre_usuario'],
                        'nombre_completo' => $request['nombres'] . ' ' . $request['primer_ap_dir'] . ' ' . $request['segundo_ap_dir'],
                        'password' => bcrypt($request['password']),
                        'password_plain' => $request['password'],
                        'tipo_usuario' => $request['tipo_usuario'],
                        'estado' => $request['estado'],
                    ]);

                    $permisos = [1, 2, 3, 4];
                    $privilegios = UsuarioPrivilegio::insert(
                        array_map(function ($permiso_id) use ($usuario) {
                            return [
                                'usuario_id' => $usuario->id,
                                'privilegio_id' => $permiso_id,
                            ];
                        }, $permisos)
                    );
                    break;
                case 'COORDINADOR':
                    $usuario = BrAcceso::create([
                        'nombre_usuario' => $request['nombre_usuario'],
                        'nombre_completo' => $request['nombres'] . ' ' . $request['primer_ap_dir'] . ' ' . $request['segundo_ap_dir'],
                        'password' => bcrypt($request['password']),
                        'password_plain' => $request['password'],
                        'tipo_usuario' => $request['tipo_usuario'],
                        'estado' => $request['estado'],
                    ]);

                    $permisos = [1, 2];
                    $privilegios = UsuarioPrivilegio::insert(
                        array_map(function ($permiso_id) use ($usuario) {
                            return [
                                'usuario_id' => $usuario->id,
                                'privilegio_id' => $permiso_id,
                            ];
                        }, $permisos)
                    );
                    break;
                case 'ESPECIALISTA':
                    $usuario = BrAcceso::create([
                        'nombre_usuario' => $request['nombre_usuario'],
                        'nombre_completo' => $request['nombres'] . ' ' . $request['primer_ap_dir'] . ' ' . $request['segundo_ap_dir'],
                        'password' => bcrypt($request['password']),
                        'password_plain' => $request['password'],
                        'tipo_usuario' => $request['tipo_usuario'],
                        'estado' => $request['estado'],
                    ]);

                    $directorio = BrDirectorio::create([
                        'usuario_id' => $usuario->id,
                        'primer_ap_dir' => $request['primer_ap_dir'],
                        'segundo_ap_dir' => $request['segundo_ap_dir'],
                        'nombres' => $request['nombres'],
                        'telefono' => $request['telefono'],
                        'email' => $request['email'],
                        'tipo_asesoria' => $request['tipo_asesoria'],
                        'estado' => $request['estado'],
                    ]);

                    $permisos = [1, 2];
                    $privilegios = UsuarioPrivilegio::insert(
                        array_map(function ($permiso_id) use ($usuario) {
                            return [
                                'usuario_id' => $usuario->id,
                                'privilegio_id' => $permiso_id,
                            ];
                        }, $permisos)
                    );
                    break;
            }

            DB::commit();
            return response()->json([
                'message' => 'Usuario creado exitosamente',
                'data' => [
                    'usuario' => isset($usuario) ? $usuario : null,
                    'directorio' => isset($directorio) ? $directorio : null,
                    'privilegios' => isset($privilegios) ? $privilegios : null
                ]
            ], 201);


        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al crear el usuario', 'message' => $e->getMessage()], 500);
        }

    }

    public function getFieldsEditUsuario($id)
    {
        try {
            $usuario = BrAcceso::findOrFail($id);
            $directorio = BrDirectorio::where('usuario_id', $id)->first();

            return response()->json([
                'data' => [
                    'usuario' => $usuario,
                    'directorio' => $directorio
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener los datos del usuario', 'message' => $e->getMessage()], 500);
        }

    }

    public function UpdateUsuario(Request $request, $id)
    {

    }

    public function DeleteUsuario($id)
    {

    }

}
