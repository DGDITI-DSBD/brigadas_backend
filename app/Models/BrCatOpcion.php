<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrCatOpcion extends Model
{
    use HasFactory;

    protected $table = 'br_cat_opciones';

    public $timestamps = false;

    protected $fillable = [
        'pregunta_id',
        'texto_descripcion',
    ];

    public function pregunta()
    {
        return $this->belongsTo(BrCatPregunta::class, 'pregunta_id');
    }
}
