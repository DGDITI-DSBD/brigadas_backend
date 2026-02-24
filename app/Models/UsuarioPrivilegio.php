<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsuarioPrivilegio extends Model
{
    use HasFactory;

    protected $table = 'usuario_privilegio';

    public $timestamps = false;


    protected $fillable = [
        'usuario_id',
        'privilegio_id',
    ];

    public function acceso()
    {
        return $this->belongsTo(BrAcceso::class, 'usuario_id');
    }

    public function privilegio()
    {
        return $this->belongsTo(BrCatPrivilegio::class, 'privilegio_id');
    }
}
