<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Migracion para crear la tabla de parentesco
    public function up(): void
    {
        Schema::create('br_cat_parentesco', function (Blueprint $table) {
            $table->id('ct_parentesco');
            $table->string('parentesco', 50);
                                
        });
    }

    // Revertir la migracion de la tabla de parentesco
    public function down(): void
    {
        Schema::dropIfExists('br_cat_parentesco');
    }
};
