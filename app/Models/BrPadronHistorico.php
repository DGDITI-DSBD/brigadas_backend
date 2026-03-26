<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrPadronHistorico extends Model
{
    use HasFactory;
    protected $table = 'padron_historico';
    public $timestamps = false;
    protected $fillable = [
        'folio_relacionado',
        'primer_ap',
        'segundo_ap',
        'nombres',
        'fecha_nacimiento',
        'edad',
        'genero',
        'ct_edo_civil',
        'ct_escolaridad',
        'tp_id_oficial',
        'id_oficial',
        'ct_ent_nac',
        'curp',
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
        //'grupo_vulnerable'
    ];

    public function scopeSearching($query, $search)
    {
        if($search) {
        return $query->where('folio_relacionado', 'like', '%'.$search.'%')
            ->orWhere('curp', 'like', '%'.$search.'%')
            ->orWhereRaw("CONCAT(primer_ap, ' ', segundo_ap, ' ', nombres) LIKE ?", ["%$search%"])
            ->orWhere('primer_ap', 'like', '%'.$search.'%')
            ->orWhere('segundo_ap', 'like', '%'.$search.'%')
            ->orWhere('nombres', 'like', '%'.$search.'%');
        }
        return $query;
    }
    public function scopeFolioRelacionado($query, $folio_relacionado)
    {
        if($folio_relacionado) {
        return $query->where('folio_relacionado', 'like', '%'.$folio_relacionado.'%');
        }
        return $query;
    }
    public function scopeCurp($query, $curp)
    {
        if($curp) {
        return $query->orWhere('curp', 'like', '%'.$curp.'%');
        }
        return $query;
    }
    public function scopeNombreCompleto($query, $nombre_completo)
    {
        if($nombre_completo) {
                   
            return $query->orWhereRaw("CONCAT(primer_ap, ' ', segundo_ap, ' ', nombres) LIKE ?", ["%$nombre_completo%"])
                ->orWhere('primer_ap', 'like', '%'.$nombre_completo.'%')
                ->orWhere('segundo_ap', 'like', '%'.$nombre_completo.'%')
                ->orWhere('nombres', 'like', '%'.$nombre_completo.'%');
        }
        return $query;
    }
}
