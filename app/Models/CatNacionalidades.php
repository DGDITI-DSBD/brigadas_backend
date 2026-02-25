<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatNacionalidades extends Model
{
    use HasFactory;

    protected $table = 'br_cat_nacionalidad';
    public $timestamps = false;
    protected $fillable = [
        'nacionalidad',
    ];
    

}
