<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrCatTipoAsesoria extends Model
    
{
    use HasFactory;

    protected $table = 'br_cat_tipo_asesoria';

    public $timestamps = false;
    protected $fillable = [
        'descripcion',
    ];

    public function directorios()
    {
        return $this->hasMany(BrDirectorio::class, 'tipo_asesoria');
    }

    public function asesoriasCanalizadas()
    {
        return $this->hasMany(BrAsesoria::class, 'tipo_asesoria_canaliza_id');
    }

    public function preguntas()
    {
        return $this->hasMany(BrCatPregunta::class, 'asesoria_tipo_id');
    }
}
