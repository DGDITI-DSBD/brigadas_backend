<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatMunicipio extends Model
{
    use HasFactory;

    protected $table = 'br_cat_municipios';
    protected $primaryKey = 'municipio_id';
    public $timestamps = false;

    protected $fillable = [
        'ct_municipio',
        'municipio',
        'ct_entidad_federativa',
    ];

    public function entidadFederativa()
    {
        return $this->belongsTo(BrCatEntidadesFederativas::class, 'ct_entidad_federativa', 'ct_entidad_federativa');
    }

        public function localidades()
        {
            return $this->hasMany(CatLocalidad::class, 'ct_municipio', 'ct_municipio')
                ->where('ct_entidad_federativa', $this->ct_entidad_federativa);
        }
}
