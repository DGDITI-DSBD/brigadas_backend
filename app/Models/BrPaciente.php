<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Log;

class BrPaciente extends Model
{
    use HasFactory;

    protected $table = 'br_pacientes';

    protected $primaryKey = 'id';

    protected $fillable = [
        'asesoria_id',
        'folio_relacionado',
        'primer_ap',
        'segundo_ap',
        'nombres',
        'fecha_nacimiento',
        'edad',
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
        'codigo_postal_id',
        'codigo_postal',
        'telefono',
        'celular',
        'email',
        'ct_gdo_estudios',
        'ct_nacionalidad',
        'estado_padron',
        'requiere_seguimiento',
    ];

    public $timestamps = true;
    public $incrementing = true;

    public function asesorias()
    {
        return $this->hasMany(BrAsesoria::class, 'paciente_id');
    }


    public function entidadFederativa()
    {
        return $this->belongsTo(BrCatEntidadesFederativas::class, 'ct_entidad_federativa', 'ct_entidad_federativa');
    }

    public function municipio()
    {
        return $this->belongsTo(CatMunicipio::class, 'ct_municipio', 'ct_municipio')
            ->where('ct_entidad_federativa', $this->ct_entidad_federativa);
    }

    public function localidad()
    {
        return $this->belongsTo(CatLocalidad::class, 'ct_localidad', 'ct_localidad')
            ->where('ct_entidad_federativa', $this->ct_entidad_federativa)
            ->where('ct_municipio', $this->ct_municipio);
    }

    public function codigoPostalColonia()
    {

        return CatCodigosPostales::where('ct_entidad_federativa', $this->ct_entidad_federativa)
            ->where('ct_municipio', $this->ct_municipio)
            ->where('ct_localidad', $this->ct_localidad)
            ->where('codigo_postal', $this->codigo_postal)
            ->where('colonia', $this->colonia)
            ->first();
    }

    public function scopeSearchByParamsPacientes($query, $params)
    {
        
        if (isset($params['nombre_completo']) && !empty($params['nombre_completo'])) {
            $query->whereRaw("CONCAT(primer_ap, ' ', segundo_ap, ' ', nombres) LIKE ?", ['%' . $params['nombre_completo'] . '%']);
        }
        if (isset($params['folio_relacionado']) && !is_null($params['folio_relacionado']) && !empty($params['folio_relacionado'])) {
            $query->orWhere('folio_relacionado', 'LIKE', '%' . $params['folio_relacionado'] . '%');
        }
        if (isset($params['curp']) && !is_null($params['curp']) && !empty($params['curp'])) {
            $query->orWhere('curp', 'LIKE', '%' . $params['curp'] . '%');
        }
        return $query;
    }

    public function scopeSearchByProfesionista($query, $directorio_id)
    {
        if(is_null($directorio_id)) {
            return $query;
        }
        return $query->whereHas('asesorias', function ($q) use ($directorio_id) {
            $q->where('asesor_atendio_id', $directorio_id);
        });

    }

    public function scopeSearchByEspecialisacion($query, $especializacion_id)
    {
        if(is_null($especializacion_id)) {
            return $query;
        }
        return $query->whereHas('asesorias', function ($q) use ($especializacion_id) {
            $q->whereHas('directorio', function ($q2) use ($especializacion_id) {
                $q2->where('tipo_asesoria', $especializacion_id);
            });
        });
    }

    public function scopeSearchByMunicipio($query, $municipio_id)
    {
        if(is_null($municipio_id)) {
            return $query;
        }
        return $query->where('ct_municipio', $municipio_id);
    }

    public function scopeSearchByLocalidad($query, $localidad_id)
    {
        if(is_null($localidad_id)) {
            return $query;
        }
        return $query->where('ct_localidad', $localidad_id);
    }


}
