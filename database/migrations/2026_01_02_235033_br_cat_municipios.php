<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Migracion de la tabla br_cat_municipios
    public function up(): void
    {
        Schema::create('br_cat_municipios', function (Blueprint $table) {
            $table->id('municipio_id'); // id autoincremental único
            $table->integer('ct_municipio'); // código oficial del municipio
            $table->string('municipio');
            $table->unsignedBigInteger('ct_entidad_federativa');

            // Índice único compuesto para evitar duplicados oficiales
            $table->unique(['ct_entidad_federativa', 'ct_municipio']);

            // Foreign key - Entidades federativas
            $table->foreign('ct_entidad_federativa')
                ->references('ct_entidad_federativa')
                ->on('br_cat_entidades_federativas')
                ->onDelete('restrict');

        });
    }

    // Revertir la migracion de la tabla br_cat_municipios
    public function down(): void
    {
        Schema::dropIfExists('br_cat_municipios');
    }
};
