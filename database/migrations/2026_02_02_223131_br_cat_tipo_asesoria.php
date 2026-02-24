<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Migración para la tabla br_cat_tipo_asesoria
    public function up(): void
    {
        Schema::create('br_cat_tipo_asesoria', function (Blueprint $table) {
            $table->id();
            $table->enum('descripcion', ['MEDICINA GENERAL', 'FISIOTERAPIA', 'TRABAJO SOCIAL', 'GERONTOLOGÍA', 'PSICOLÓGICA', 'JURÍDICA', 'NUTRICIONAL'])->comment('MEDICINA GENERAL, FISIOTERAPIA, TRABAJO SOCIAL, GERONTOLOGÍA, PSICOLÓGICA, JURÍDICA, NUTRICIONAL');
        });
    }

    // Eliminar la tabla br_cat_tipo_asesoria
    public function down(): void
    {
        Schema::dropIfExists('br_cat_tipo_asesoria');
    }
};
