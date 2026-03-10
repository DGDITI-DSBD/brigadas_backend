<?php

namespace App\Http\Controllers;

use App\Models\BrAcceso;
use Illuminate\Http\Request;
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

    public function StoreUsuario(Request $request)
    {

    }

    public function getFieldsEditUsuario($id)
    {

    }

    public function UpdateUsuario(Request $request, $id)
    {

    }

    public function DeleteUsuario($id)
    {

    }

}
