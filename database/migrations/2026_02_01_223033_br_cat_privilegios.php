<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    // Migracion para crear la tabla de cat_privilegios
    public function up(): void
    {
        Schema::create('br_cat_privilegios', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion')->comment('CAN_READ, CAN_INSERT, CAN_UPDATE, CAN_DELETE');
        });
    }

    // Eliminar la tabla de cat_privilegios
    public function down(): void
    {
        Schema::dropIfExists('br_cat_privilegios');
    }
};
