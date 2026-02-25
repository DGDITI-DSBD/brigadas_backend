<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Migracion para crear la tabla de estados civiles
    public function up(): void
    {
        Schema::create('br_cat_estado_civil', function (Blueprint $table) {
            $table->id('ct_edo_civil');
            $table->string('estado_civil', 50);
        });
    }

    // Eliminar la tabla de estados civiles
    public function down(): void
    {
        Schema::dropIfExists('br_cat_estado_civil');
    }
};
