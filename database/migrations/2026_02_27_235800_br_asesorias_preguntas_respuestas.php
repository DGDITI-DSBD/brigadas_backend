<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('br_asesorias_preguntas_respuestas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asesoria_id');
            $table->unsignedBigInteger('pregunta_id');
            $table->integer('opcion_id')->nullable();
            $table->text('valor_contexto')->nullable();
            $table->timestamps();

            $table->foreign('asesoria_id')->references('id')->on('br_asesorias')->onDelete('set null');
            $table->foreign('pregunta_id')->references('id')->on('br_cat_preguntas');
            $table->foreign('opcion_id')->references('id')->on('br_cat_opciones');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('br_asesorias_preguntas_respuestas');
    }
};
