<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatDocumentosOficiales extends Model
{
    use HasFactory;
    protected $table = 'br_cat_documentos_oficiales';
    protected $primaryKey = 'tp_id_oficial';
    public $timestamps = false;
    protected $fillable = [
        'documento_oficial',
    ];
}
