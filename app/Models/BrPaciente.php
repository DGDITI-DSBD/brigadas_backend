<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrPaciente extends Model
{
    use HasFactory;

    protected $table = 'br_pacientes';

    protected $fillable = [
        'folio_relacionado',
        'primer_ap',
        'segundo_ap',
        'nombres',
        'fecha_nacimiento',
        'genero',
        'ct_edo_civil',
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
        'codigo_postal',
        'telefono',
        'celular',
        'email',
        'ct_gdo_estudios',
        'ct_nacionalidad',
        'estado_padron',
        'requiere_seguimiento',
    ];

    public function asesorias()
    {
        return $this->hasMany(BrAsesoria::class, 'paciente_id');
    }


    public function entidadFederativa()
    {
        return $this->belongsTo(BrCatEntidadesFederativas::class, 'ct_entidad_federativa', 'ct_entidad_federativa');
    }

}
