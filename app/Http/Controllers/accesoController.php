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
            $usuarios = BrAcceso::orderBy('id', 'asc')
            ->paginate(perPage: 10);

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
            $usuarios = $query->orderBy('id', 'asc')
            ->paginate(perPage: 10);

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
        try {
            DB::beginTransaction();
            Log::info('Datos recibidos para actualizar usuario', $request->all());

            $usuario        = BrAcceso::findOrFail($id);
            $tipo_usuario   = $request['tipo_usuario'] ?? $usuario->tipo_usuario;

            // Actualizar datos comunes
            $usuario->nombre_usuario        = $request['nombre_usuario'] ?? $usuario->nombre_usuario;
            $usuario->nombre_completo       = ($request['nombres'] ?? '') . ' ' . ($request['primer_ap_dir'] ?? '') . ' ' . ($request['segundo_ap_dir'] ?? '');
            if ($request['password']) {
                $usuario->password          = bcrypt($request['password']);
                $usuario->password_plain    = $request['password'];
            }
            $usuario->tipo_usuario          = $tipo_usuario;
            $usuario->estado                = $request['estado'] ?? $usuario->estado;
            $usuario->save();

            // Actualizar privilegios
            UsuarioPrivilegio::where('usuario_id', $usuario->id)->delete();
            switch ($tipo_usuario) {
                case 'ADMINISTRADOR':
                    $permisos = [1, 2, 3, 4];
                    
                    break;
                case 'COORDINADOR':
                    $permisos = [1, 2];
                    break;
                case 'ESPECIALISTA':
                    $permisos = [1, 2];
                    break;
                default:
                    $permisos = [];
            }
            $privilegios = UsuarioPrivilegio::insert(
                array_map(function ($permiso_id) use ($usuario) {
                    return [
                        'usuario_id' => $usuario->id,
                        'privilegio_id' => $permiso_id,
                    ];
                }, $permisos)
            );

            // Lógica para directorio según tipo_usuario
            $directorio = BrDirectorio::where('usuario_id', $usuario->id)->first();

            // Si el usuario pasa a ESPECIALISTA
            if ($tipo_usuario === 'ESPECIALISTA') {
                if ($directorio) {
                    // Actualizar directorio existente
                    $directorio->primer_ap_dir      = $request['primer_ap_dir']     ?? $directorio->primer_ap_dir;
                    $directorio->segundo_ap_dir     = $request['segundo_ap_dir']    ?? $directorio->segundo_ap_dir;
                    $directorio->nombres            = $request['nombres']           ?? $directorio->nombres;
                    $directorio->telefono           = $request['telefono']          ?? $directorio->telefono;
                    $directorio->email              = $request['email']             ?? $directorio->email;
                    $directorio->tipo_asesoria      = $request['tipo_asesoria']     ?? $directorio->tipo_asesoria;
                    $directorio->estado             = $request['estado']            ?? $directorio->estado;
                    $directorio->save();
                } else {
                    // Crear directorio si no existe
                    $directorio = BrDirectorio::create([
                        'usuario_id'        => $usuario->id,
                        'primer_ap_dir'     => $request['primer_ap_dir'] ?? null,
                        'segundo_ap_dir'    => $request['segundo_ap_dir'] ?? null,
                        'nombres'           => $request['nombres'] ?? null,
                        'telefono'          => $request['telefono'] ?? null,
                        'email'             => $request['email'] ?? null,
                        'tipo_asesoria'     => $request['tipo_asesoria'] ?? null,
                        'estado'            => $request['estado'] ?? null,
                    ]);
                }
            } else {
                // Si el usuario deja de ser especialista, cambia datos del directorio a "CAMBIO DE ROL" y estado a false
                if ($directorio) {
                    $directorio->primer_ap_dir  = '';
                    $directorio->segundo_ap_dir = '';
                    $directorio->nombres        = '';
                    $directorio->telefono       = '';
                    $directorio->email          = '';
                    $directorio->tipo_asesoria  = 1;
                    $directorio->estado         = false;
                    $directorio->save();

                    // si no se desea conservar el historial del directorio, se comenta el bloque anterior y se activa el siguiente para eliminar el registro del directorio
                    // $directorio->delete();
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Usuario actualizado exitosamente',
                'data' => [
                    'usuario'       => $usuario,
                    'directorio'    => $directorio,
                    'privilegios'   => $privilegios
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al actualizar el usuario', 'message' => $e->getMessage()], 500);
        }
    }

    public function DeleteUsuario($id)
    {
        try {
            DB::beginTransaction();

            // Eliminación Directa [TABLA = DIRECTORIO - PRIVILEGIOS - ACCESO]
            BrDirectorio::where('usuario_id', $id)->delete();
            UsuarioPrivilegio::where('usuario_id', $id)->delete();
            BrAcceso::where('id', $id)->delete();

            // Eliminación Lógica [SOLO CAMBIA ESTATUS Y VALORES]
            // TABLAS [ACCESO - DIRECTORIO - PRIVILEGIOS]
            // DESCOMENTAR SI SOLO SE DESEA ELIMINACIÓN LÓGICA PARA MANTENER HISTÓRICO

            // $usuario = BrAcceso::findOrFail($id);
            // $usuario->estado = false;
            // $usuario->save();

            // $directorio = BrDirectorio::where('usuario_id', $id)->first();
            // if ($directorio) {
            //     $directorio->estado = false;
            //     $directorio->save();
            // }

            // Actualizar todos los privilegios del usuario
            // UsuarioPrivilegio::where('usuario_id', $id)->update(['privilegio_id' => 0]);

            DB::commit();
            return response()->json(['message' => 'Usuario eliminado exitosamente', 'id' => $id], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al eliminar el usuario', 'message' => $e->getMessage()], 500);
        }
    }

}
