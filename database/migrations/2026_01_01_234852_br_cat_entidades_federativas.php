<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    // Migración para la tabla br_cat_entidades_federativas
    public function up(): void
    {
        Schema::create('br_cat_entidades_federativas', function (Blueprint $table) {
            $table->id('ct_entidad_federativa');
            $table->string('entidad_federativa')->comment('NOMBRE DE LA ENTIDAD FEDERATIVA');
            $table->string('abre_entidad_federativa')->comment('ABREVIATURA DE LA ENTIDAD FEDERATIVA');

        });
    }

    // Eliminar la tabla br_cat_entidades_federativas
    public function down(): void
    {
        Schema::dropIfExists('br_cat_entidades_federativas');
    }
};
