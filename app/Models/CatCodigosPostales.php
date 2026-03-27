<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatCodigosPostales extends Model
{
    use HasFactory;
    protected $table = 'br_cat_codigos_postales';

    public $timestamps = false;

    protected $primaryKey = 'cp_id';
    protected $fillable = [
        'cp_id',
        'codigo_postal',
        'colonia',
        'ct_cp',
        'ct_municipio',
        'ct_entidad_federativa',
        'periodo',
        'estatus',
    ];
    
    public function entidadFederativa()
    {
        return $this->belongsTo(BrCatEntidadesFederativas::class, 'ct_entidad_federativa', 'ct_entidad_federativa');
    }

    public function municipio()
    {
        return $this->belongsTo(CatMunicipio::class, 'ct_municipio', 'ct_municipio')
            ->where('ct_entidad_federativa', $this->ct_entidad_federativa);
    }

    public function localidad()
    {
        return $this->belongsTo(CatLocalidad::class, 'ct_cp', 'ct_cp')
            ->where('ct_entidad_federativa', $this->ct_entidad_federativa)
            ->where('ct_municipio', $this->ct_municipio);
    }


}
