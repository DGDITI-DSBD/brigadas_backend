<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Migracion para crear la tabla de asesorias
    public function up(): void
    {
        Schema::create('br_asesorias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asesor_atendio_id');
            $table->unsignedBigInteger('paciente_id');
            $table->string('estado_asesoria')->comment('PENDIENTE, ABIERTA, CANALIZADO, CERRADA');
            $table->boolean('estado_registro')->comment('ALTA, BAJA');
            $table->unsignedBigInteger('tipo_asesoria_canaliza_id')->nullable();
            $table->timestamps();

            $table->foreign('asesor_atendio_id')->references('id')->on('br_directorio')->onDelete('cascade');
            $table->foreign('paciente_id')->references('id')->on('br_pacientes')->onDelete('cascade');
            $table->foreign('tipo_asesoria_canaliza_id')->references('id')->on('br_cat_tipo_asesoria')->onDelete('set null');
        });
    }

    // Eliminar la tabla de asesorias
    public function down(): void
    {
        Schema::dropIfExists('br_asesorias');
    }
};
