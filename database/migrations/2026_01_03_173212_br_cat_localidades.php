<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Migracion para crear la tabla `br_cat_localidades` con relación a `br_cat_municipios` y `br_cat_entidades_federativas`
    public function up(): void
    {
        Schema::create('br_cat_localidades', function (Blueprint $table) {
            $table->id('localidad_id'); // id autoincremental único
            $table->unsignedBigInteger('ct_localidad'); // código oficial de la localidad
            $table->string('localidad');
            $table->integer('ct_municipio');
            $table->integer('ct_entidad_federativa');
            $table->integer('periodo');
            $table->boolean('estatus')->default(true); // true=activo, false=inactivo

            // Índice único compuesto para evitar duplicados oficiales
            $table->unique(['ct_entidad_federativa', 'ct_municipio', 'ct_localidad']);

            // Foreign key compuesta - Municipios
            $table->foreign(['ct_entidad_federativa', 'ct_municipio'])
                ->references(['ct_entidad_federativa', 'ct_municipio'])
                ->on('br_cat_municipios')
                ->onDelete('restrict');

            // Foreign key - Entidades federativas
            $table->foreign('ct_entidad_federativa')
                ->references('ct_entidad_federativa')
                ->on('br_cat_entidades_federativas')
                ->onDelete('restrict');
        });
    }

    // Revertir la migracion de la tabla `br_cat_localidades`
    public function down(): void
    {
        Schema::dropIfExists('br_cat_localidades');
    }
};
