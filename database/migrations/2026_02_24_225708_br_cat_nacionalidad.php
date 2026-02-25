<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    // Migracion de la tabla br_cat_nacionalidad
    public function up(): void
    {
        Schema::create('br_cat_nacionalidad', function (Blueprint $table) {
            $table->id();
            $table->string('nacionalidad', 100);
        });
    }

    // Revertir la migracion de la tabla br_cat_nacionalidad
    public function down(): void
    {
        Schema::dropIfExists('br_cat_nacionalidad');
    }
};
