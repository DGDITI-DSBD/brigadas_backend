<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrDirectorio extends Model
{
    use HasFactory;

    protected $table = 'br_directorio';

    protected $fillable = [
        'usuario_id',
        'tipo_asesoria',
        'primer_ap_dir',
        'segundo_ap_dir',
        'nombres',
        'telefono',
        'email',
        'estado',
    ];

    public function usuario()
    {
        return $this->belongsTo(BrAcceso::class, 'usuario_id');
    }

    public function tipoAsesoria()
    {
        return $this->belongsTo(BrCatTipoAsesoria::class, 'tipo_asesoria');
    }

    public function asesorias()
    {
        return $this->hasMany(BrAsesoria::class, 'asesor_atendio_id');
    }
}
