<?php

namespace App\Http\Controllers;

use App\Models\BrPaciente;
use Illuminate\Http\Request;

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
            $paciente = BrPaciente::findOrFail($id);

            $data = $request->only($paciente->getFillable());

            $paciente->fill($data);
            $paciente->save();

            return response()->json([
                'success' => true,
                'message' => 'Beneficiaria actualizada exitosamente',
                'data' => $paciente->fresh(),
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
