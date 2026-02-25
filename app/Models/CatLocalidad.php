<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatLocalidad extends Model
{
    use HasFactory;

    protected $table = 'br_cat_localidades';
    protected $primaryKey = 'localidad_id';
    public $timestamps = false;

    protected $fillable = [
        'ct_localidad',
        'localidad',
        'ct_municipio',
        'ct_entidad_federativa',
        'periodo',
        'estatus',
    ];

    public function municipio()
    {
        return $this->belongsTo(CatMunicipio::class, 'ct_municipio', 'ct_municipio');
    }

    public function entidadFederativa()
    {
        return $this->belongsTo(BrCatEntidadesFederativas::class, 'ct_entidad_federativa', 'ct_entidad_federativa');
    }   
}
