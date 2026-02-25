<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Migracion para crear la tabla de documentos oficiales
    public function up(): void
    {
        Schema::create('br_cat_documentos_oficiales', function (Blueprint $table) {
            $table->id('tp_id_oficial');
            $table->string('documento_oficial', 100);
        });
    }

    // Revertir la migracion de la tabla de documentos oficiales
    public function down(): void
    {
        Schema::dropIfExists('br_cat_documentos_oficiales');
    }
};
