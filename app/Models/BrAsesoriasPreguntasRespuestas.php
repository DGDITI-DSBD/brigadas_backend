<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrAsesoriasPreguntasRespuestas extends Model
{
    use HasFactory;

    protected $table = 'br_asesorias_preguntas_respuestas';

    protected $fillable = [
        'asesoria_id',
        'pregunta_id',
        'opcion_id',
        'valor_contexto',
    ];

    public $incrementing = true;
    public $timestamps = true;

    public function asesoria()
    {
        return $this->belongsTo(BrAsesoria::class, 'asesoria_id');
    }
    
    public function pregunta()
    {
        return $this->belongsTo(BrCatPregunta::class, 'pregunta_id');
    }

    public function opcion()
    {
        return $this->belongsTo(BrCatOpcion::class, 'opcion_id');
    }
}
