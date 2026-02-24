<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Migracion para crear la tabla de accesos
    public function up(): void
    {
        Schema::create('br_accesos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_usuario');
            $table->string('nombre_completo');
            $table->boolean('estado')->comment('ACTIVO, INACTIVO');
            $table->string('tipo_usuario')->comment('ADMINISTRADOR, COORDINADOR, ESPECIALISTA');
            $table->timestamps();
        });
    }

    // Eliminar la tabla de accesos
    public function down(): void
    {
        Schema::dropIfExists('br_accesos');
    }
};
