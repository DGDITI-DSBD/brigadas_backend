<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;

class BrAcceso extends Model implements JWTSubject, Authenticatable
    // Métodos requeridos por JWTSubject

{
    use HasFactory, AuthenticatableTrait;

    protected $table = 'br_accesos';

    protected $fillable = [
        'nombre_usuario',
        'nombre_completo',
        'password',
        'password_plain',
        'estado',
        'tipo_usuario',
    ];

    protected $hidden = [
        'password',
        'password_plain',
    ];

    public function usuarioPrivilegios()
    {
        return $this->hasMany(UsuarioPrivilegio::class, 'usuario_id');
    }

    public function directorio()
    {
        return $this->hasOne(BrDirectorio::class, 'usuario_id');
    }

    public function directorioActivo()
    {
        return $this->hasOne(BrDirectorio::class, 'usuario_id')->where('estado', true);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
