<?php

namespace App\Http\Controllers;

use App\Models\BrPaciente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PacientesController extends Controller
{
    // Función - Listar pacientes
    public function getDirectorioBeneficiarias(Request $request)
    {

        try {

            $municipioId = $request->query('municipio_id');
            $search = trim((string) $request->query('search', ''));
            $perPage = 10;

            $directorio = BrPaciente::query()
                ->when($municipioId, function ($query) use ($municipioId) {
                    $query->where('ct_municipio', $municipioId);
                })
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery->where('id', 'LIKE', '%' . $search . '%')
                            ->orWhere('folio_relacionado', 'LIKE', '%' . $search . '%')
                            ->orWhere('curp', 'LIKE', '%' . $search . '%')
                            ->orWhere('id_oficial', 'LIKE', '%' . $search . '%')
                            ->orWhereRaw("CONCAT_WS(' ', primer_ap, segundo_ap, nombres) LIKE ?", ['%' . $search . '%']);
                    });
                })
                ->orderBy('id', 'desc')
                ->paginate($perPage)
                ->appends($request->query());

            $municipios = BrPaciente::query()
                ->select('ct_municipio', 'municipio')
                ->whereNotNull('ct_municipio')
                ->whereNotNull('municipio')
                ->distinct()
                ->orderBy('municipio')
                ->get()
                ->map(function ($municipio) {
                    return [
                        'id' => $municipio->ct_municipio,
                        'nombre' => $municipio->municipio,
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Lista de pacientes',
                'data' => $directorio->items(),
                'meta' => [
                    'filters' => [
                        'municipio_id' => $municipioId,
                        'search' => $search,
                    ],
                    'municipios' => $municipios,
                    'pagination' => [
                        'current_page' => $directorio->currentPage(),
                        'last_page' => $directorio->lastPage(),
                        'per_page' => $directorio->perPage(),
                        'total' => $directorio->total(),
                        'from' => $directorio->firstItem(),
                        'to' => $directorio->lastItem(),
                        'has_more_pages' => $directorio->hasMorePages(),
                    ],
                    'total' => $directorio->total(),
                ],
            ]);


        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de pacientes: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }

    }

    // Función - Obtener detalles de un paciente
    public function getDirectorioBeneficiariasById($id)
    {
        try {
            $paciente = BrPaciente::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Detalles del paciente',
                'data' => $paciente
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los detalles del paciente: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    // Función - Actualizar beneficiaria por ID
    public function updateDirectorioBeneficiarias(Request $request, $id)
    {
        try {
       

            $UpData = $request->only([
                'ct_edo_civil',
                'tp_id_oficial',
                'id_oficial',
                'ct_ent_nac',
                'calle',
                'num_ext',
                'num_int',
                'entre_calle',
                'y_calle',
                'otra_referencia',
                'colonia',
                'ct_localidad',
                'localidad',
                'ct_municipio',
                'municipio',
                'ct_entidad_federativa',
                'entidad_federativa',
                'codigo_postal_id',
                'codigo_postal',
                'telefono',
                'celular',
                'email',
                'ct_gdo_estudios',
                'ct_nacionalidad',
            ]);
            $tipoUsuario = strtoupper((string) $request->input('tipo_usuario', ''));
            $actualizar = 0;

            $paciente = BrPaciente::findOrFail($id);

        

            switch ($tipoUsuario) {
                case 'ADMINISTRADOR':
                        $actualizar = BrPaciente::where('id', $id)->update([
                            'ct_edo_civil'          => $UpData ['ct_edo_civil'],
                            'tp_id_oficial'         => $UpData ['tp_id_oficial'],
                            'id_oficial'            => $UpData ['id_oficial'],
                            'ct_ent_nac'            => $UpData ['ct_ent_nac'],
                            'calle'                 => $UpData ['calle'],
                            'num_ext'               => $UpData ['num_ext'],
                            'num_int'               => $UpData ['num_int'],
                            'entre_calle'           => $UpData ['entre_calle'],
                            'y_calle'               => $UpData ['y_calle'],
                            'otra_referencia'       => $UpData ['otra_referencia'],
                            'ct_entidad_federativa' => $UpData ['ct_entidad_federativa'],
                            'entidad_federativa'    => $UpData ['entidad_federativa'],
                            'ct_municipio'          => $UpData ['ct_municipio'],
                            'municipio'             => $UpData ['municipio'],
                            'ct_localidad'          => $UpData ['ct_localidad'],
                            'localidad'             => $UpData ['localidad'],
                            'codigo_postal_id'      => $UpData ['codigo_postal_id'],
                            'codigo_postal'         => $UpData ['codigo_postal'],
                            'colonia'               => $UpData ['colonia'],
                            'telefono'              => $UpData ['telefono'],
                            'celular'               => $UpData ['celular'],
                            'email'                 => $UpData ['email'],
                            'ct_gdo_estudios'       => $UpData ['ct_gdo_estudios'],
                            'ct_nacionalidad'       => $UpData ['ct_nacionalidad']
                        ]);
                    break;

                case 'COORDINADOR':
                    $actualizar = BrPaciente::where('id', $id)->update([
                            'ct_edo_civil'          => $UpData ['ct_edo_civil'],
                            'telefono'              => $UpData ['telefono'],
                            'celular'               => $UpData ['celular'],
                            'email'                 => $UpData ['email'],
                            'ct_gdo_estudios'       => $UpData ['ct_gdo_estudios'],
                        ]);
                    break;
                
                case 'ESPECIALISTA':
                    $actualizar = BrPaciente::where('id', $id)->update([
                            'ct_edo_civil'          => $UpData ['ct_edo_civil'],
                            'telefono'              => $UpData ['telefono'],
                            'celular'               => $UpData ['celular'],
                            'email'                 => $UpData ['email'],
                            'ct_gdo_estudios'       => $UpData ['ct_gdo_estudios'],
                        ]);
                    break;
                
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Tipo de usuario no válido para actualizar la beneficiaria',
                        'data' => null
                    ], 400);
            }

            $paciente->refresh();

     

            return response()->json([
                'success' => true,
                'message' => 'Beneficiaria actualizada exitosamente',
                'data' => [
                    'updated_rows' => $actualizar,
                    'beneficiaria' => $paciente,
                ],
            ]);
        } catch (\Exception $e) {
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la beneficiaria: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

}
