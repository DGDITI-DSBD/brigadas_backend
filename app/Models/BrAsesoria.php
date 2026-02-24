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

    public function asesor()
    {
        return $this->belongsTo(BrDirectorio::class, 'asesor_atendio_id');
    }

    public function paciente()
    {
        return $this->belongsTo(BrPaciente::class, 'paciente_id');
    }

    public function tipoAsesoriaCanaliza()
    {
        return $this->belongsTo(BrCatTipoAsesoria::class, 'tipo_asesoria_canaliza_id');
    }
}
