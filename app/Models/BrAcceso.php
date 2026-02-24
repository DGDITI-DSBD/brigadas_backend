<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrAcceso extends Model
{
    use HasFactory;

    protected $table = 'br_accesos';

    protected $fillable = [
        'nombre_usuario',
        'nombre_completo',
        'estado',
        'tipo_usuario',
    ];

    public function usuarioPrivilegios()
    {
        return $this->hasMany(UsuarioPrivilegio::class, 'usuario_id');
    }

    public function directorios()
    {
        return $this->hasMany(BrDirectorio::class, 'usuario_id');
    }
}
