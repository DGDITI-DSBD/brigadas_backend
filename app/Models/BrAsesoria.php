<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrAsesoria extends Model
{
    use HasFactory;

    protected $table = 'br_asesorias';

    protected $fillable = [
        'asesor_atendio_id',
        'paciente_id',
        'estado_asesoria',
        'estado_registro',
        'tipo_asesoria_canaliza_id',
    ];

    public function directorio()
    {
        return $this->belongsTo(BrDirectorio::class, 'asesor_atendio_id');
    }

    public function paciente()
    {
        return $this->belongsTo(BrPaciente::class, 'paciente_id');
    }

    public function asesoria_canalizacion()
    {
        return $this->belongsTo(BrCatTipoAsesoria::class, 'tipo_asesoria_canaliza_id');
    }

    public function scope_searchByParamsPacientes($query, $params){
        if(isset($params['nombre_completo']) && !empty($params['nombre_completo'])){
            $query->whereHas('paciente', function($q) use ($params){
                $q->whereRaw("CONCAT(primer_ap, ' ', segundo_ap, ' ', nombres) LIKE ?", ['%'.$params['nombre_completo'].'%']);
            });
        }
        if(!is_null($params['folio_relacionado']) && !empty($params['folio_relacionado'])){
            $query->whereHas('paciente', function($q) use ($params){
                $q->orWhere('folio_relacionado', 'LIKE', '%'.$params['folio_relacionado'].'%');
            });
        }

        if(!is_null($params['curp']) && !empty($params['curp'])){
            $query->whereHas('paciente', function($q) use ($params){
                $q->orWhere('curp', 'LIKE', '%'.$params['curp'].'%');
            });
        }

        return $query;
    }
    // Scope para administradores y coordinadores
    public function scope_searchByProfesionista($query, $directorio_id){
        return $query->where('asesor_atendio_id', $directorio_id);
    }
    public function scope_searchByEspecialisacion($query, $especializacion_id){
        return $query->whereHas('directorio', function($q) use ($especializacion_id){
            $q->where('tipo_asesoria', $especializacion_id);
        });
    }
}
