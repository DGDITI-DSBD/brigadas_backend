<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Migracion para crear la tabla de directorio
    public function up(): void
    {
        Schema::create('br_directorio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('tipo_asesoria');
            $table->string('primer_ap_dir');
            $table->string('segundo_ap_dir');
            $table->string('nombres');
            $table->string('telefono');
            $table->string('email');
            $table->boolean('estado');
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('br_accesos')->onDelete('cascade');
            $table->foreign('tipo_asesoria')->references('id')->on('br_cat_tipo_asesoria')->onDelete('cascade');
        });
    }

    // Eliminar la tabla de directorio
    public function down(): void
    {
        Schema::dropIfExists('br_directorio');
    }
};
