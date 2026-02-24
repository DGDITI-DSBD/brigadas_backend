<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrCatPregunta extends Model
{
    use HasFactory;

    protected $table = 'br_cat_preguntas';
    public $timestamps = false;

    protected $fillable = [
        'asesoria_tipo_id',
        'orden_pregunta',
        'tipo_respuesta',
        'tipo_dato',
        'descripcion',
        'ayuda',
        'es_requerido',
        'estado',
        'depende_de',
        'depende_respuesta',
    ];

    public function tipoAsesoria()
    {
        return $this->belongsTo(BrCatTipoAsesoria::class, 'asesoria_tipo_id');
    }

    public function opciones()
    {
        return $this->hasMany(BrCatOpcion::class, 'pregunta_id');
    }

    public function preguntaPadre()
    {
        return $this->belongsTo(BrCatPregunta::class, 'depende_de');
    }

    public function preguntasHijas()
    {
        return $this->hasMany(BrCatPregunta::class, 'depende_de');
    }
}
