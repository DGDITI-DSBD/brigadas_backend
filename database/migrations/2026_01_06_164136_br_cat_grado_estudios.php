<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Migracion para crear la tabla de grados de estudios
    public function up(): void
    {
        Schema::create('br_cat_grado_estudios', function (Blueprint $table) {
            $table->id('ct_gdo_estudios');
            $table->string('grado_estudios', 100);
        });
    }

    // Revertir la migracion de la tabla de grados de estudios
    public function down(): void
    {
        Schema::dropIfExists('br_cat_grado_estudios');
    }
};
