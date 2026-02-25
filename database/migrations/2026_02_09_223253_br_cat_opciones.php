<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Migración para la tabla br_cat_opciones
    public function up(): void
    {
        Schema::create('br_cat_opciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pregunta_id');
            $table->string('texto_descripcion');
            $table->timestamps();

            $table->foreign('pregunta_id')->references('id')->on('br_cat_preguntas')->onDelete('set null');
        });
    }

    // Eliminar la tabla br_cat_opciones
    public function down(): void
    {
        Schema::dropIfExists('br_cat_opciones');
    }
};
