<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatParentesco extends Model
{
    use HasFactory;

    protected $table = 'br_cat_parentesco';
    protected $primaryKey = 'ct_parentesco';
    public $timestamps = false;
    protected $fillable = [
        'parentesco',
    ];
}
