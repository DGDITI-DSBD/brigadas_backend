<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Migracion para crear la tabla de usuario_privilegio
    public function up(): void
    {
        Schema::create('usuario_privilegio', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('privilegio_id');

            $table->foreign('usuario_id')->references('id')->on('br_accesos')->onDelete('cascade');
            $table->foreign('privilegio_id')->references('id')->on('br_cat_privilegios')->onDelete('cascade');
        });
    }

    // Eliminar la tabla de usuario_privilegio
    public function down(): void
    {
        Schema::dropIfExists('usuario_privilegio');
    }
};
