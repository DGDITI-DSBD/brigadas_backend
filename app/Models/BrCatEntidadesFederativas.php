<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrCatEntidadesFederativas extends Model

{
    use HasFactory;

    protected $table = 'br_cat_entidades_federativas';
    protected $primaryKey = 'ct_entidad_federativa';
    public $timestamps = false;

    protected $fillable = [
        'entidad_federativa',
        'abre_entidad_federativa',
    ];

    public function municipios()
    {
        return $this->hasMany(CatMunicipio::class, 'ct_entidad_federativa', 'ct_entidad_federativa');
    }

    public function localidades()
    {
        return $this->hasMany(CatLocalidad::class, 'ct_entidad_federativa', 'ct_entidad_federativa');
    }
}
