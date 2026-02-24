<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrCatPrivilegio extends Model
   
{
    use HasFactory;

    protected $table = 'br_cat_privilegios';

    public $timestamps = false;

    protected $fillable = [
        'descripcion',
    ];

    public function usuarioPrivilegios()
    {
        return $this->hasMany(UsuarioPrivilegio::class, 'privilegio_id');
    }
}
