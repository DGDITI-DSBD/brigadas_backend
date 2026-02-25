<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatGradoEstudios extends Model
{
    use HasFactory;

    protected $table = 'br_cat_grado_estudios';
    protected $primaryKey = 'ct_gdo_estudios';
    public $timestamps = false;
    protected $fillable = [
        'grado_estudios',
    ];
}
