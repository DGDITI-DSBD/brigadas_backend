<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Migracion para crear la tabla de cat_preguntas
    public function up(): void
    {
        Schema::create('br_cat_preguntas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asesoria_tipo_id')->nullable();
            $table->integer('orden_pregunta');
            $table->string('tipo_respuesta')->comment('opcion_multiple, texto_libre, opcion_unica, si_no');
            $table->string('descripcion');
            $table->boolean('es_requerido');
            $table->boolean('estado');
            $table->unsignedBigInteger('depende_de')->nullable();
            $table->string('depende_respuesta')->nullable();
            $table->timestamps();

            $table->foreign('asesoria_tipo_id')->references('id')->on('br_cat_tipo_asesoria')->onDelete('set null');
            $table->foreign('depende_de')->references('id')->on('br_cat_preguntas')->onDelete('set null');
        });
    }

    // Eliminar la tabla de cat_preguntas
    public function down(): void
    {
        Schema::dropIfExists('br_cat_preguntas');
    }
};
