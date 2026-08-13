<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewReportePAsesorias extends Model
{
    use HasFactory;


    protected $table = 'reporte_asesorias_respuestas';

    public $incrementing = false;
    public $timestamps = false;



}
