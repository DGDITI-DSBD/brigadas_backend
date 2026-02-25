<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatEstadosCiviles extends Model
{
    use HasFactory;

    protected $table = 'br_cat_estado_civil';
    protected $primaryKey = 'ct_edo_civil';
    public $timestamps = false;
    protected $fillable = [
        'estado_civil',
    ];

}
