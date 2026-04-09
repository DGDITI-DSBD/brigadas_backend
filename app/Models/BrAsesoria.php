<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Log;

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
        'fecha_asesoria',
    ];

    protected $casts = [
        'fecha_asesoria' => 'date:Y-m-d',
    ];

    public $timestamps = true;
    public $incrementing = true;



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

    public function preguntasRespuestas()
    {
        return $this->hasMany(BrAsesoriasPreguntasRespuestas::class, 'asesoria_id');
    }

    public function scopeSearchByParamsPacientes($query, $general)
    {
  
        if($general) {
            return $query->whereHas('paciente', function ($q) use ($general) {
                $q->where('nombres', 'like', "%$general%")
                  ->orWhere('segundo_ap', 'like', "%$general%")
                  ->orWhere('primer_ap', 'like', "%$general%")
                  ->orWhere('curp', 'like', "%$general%")
                  ->orWhere('folio_relacionado', 'like', "%$general%");
            });
        }

        return $query;
    }
    // Scope para administradores y coordinadores
    public function scopeSearchByProfesionista($query, $directorio_id)
    {
        if($directorio_id) {
            return $query->where('asesor_atendio_id', $directorio_id);
        }
        return $query;
    }
    public function scopeSearchByEspecializacion($query, $especializacion_id)
    {
        if($especializacion_id) {
            return $query->whereHas('directorio', function ($q) use ($especializacion_id) {
            $q->where('tipo_asesoria', $especializacion_id);
        });
        }
        return $query;
        
    }

    public function scopeSearchByMunicipio($query, $municipio_id)
    {
        if($municipio_id) {
            return $query->whereHas('paciente', function ($q) use ($municipio_id) {
                $q->where('ct_municipio', $municipio_id);
            });
        }
        return $query;
    }

    public function scopeSearchByFecha($query, $fecha)
    {
        if($fecha) {
            return $query->whereDate('fecha_asesoria', $fecha);
        }
        return $query;
    }
}
