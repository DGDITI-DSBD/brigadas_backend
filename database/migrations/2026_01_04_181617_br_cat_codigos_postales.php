<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Migracion para codigos postales, con relación a localidades, municipios y entidades federativas
    public function up(): void
    {
        Schema::create('br_cat_codigos_postales', function (Blueprint $table) {
            $table->id('cp_id'); // id autoincremental único
            $table->string('codigo_postal');
            $table->string('colonia'); // Nombre del asentamiento (colonia, barrio, etc.)
            $table->unsignedBigInteger('ct_cp'); 
            $table->unsignedBigInteger('ct_municipio');
            $table->unsignedBigInteger('ct_entidad_federativa');
            $table->integer('periodo');
            $table->boolean('estatus')->default(true); // true=activo, false=inactivo


            // Índice único compuesto para la relación con br_pacientes
            $table->unique(['ct_entidad_federativa', 'ct_municipio', 'codigo_postal', 'colonia']);

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

    // Revertir la migracion de codigos postales
    public function down(): void
    {
        Schema::dropIfExists('br_cat_codigos_postales');
    }
};
