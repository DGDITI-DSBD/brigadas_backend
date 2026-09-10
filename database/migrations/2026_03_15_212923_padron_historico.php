<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('padron_historico', function (Blueprint $table) {
            $table->id();
            $table->string('folio_relacionado')->nullable(); // No editable
            $table->string('primer_ap'); // No editable
            $table->string('segundo_ap')->nullable(); // No editable
            $table->string('nombres'); // No editable
            $table->date('fecha_nacimiento'); // No editable
            $table->integer('edad'); // No consultable
            $table->string('genero'); // No consultable
            $table->integer('ct_edo_civil');
            $table->integer('ct_gdo_estudios')->nullable();
            $table->integer('tp_id_oficial');
            $table->string('id_oficial');
            $table->integer('ct_ent_nac');
            $table->string('curp');
            $table->string('calle');
            $table->string('num_ext');
            $table->string('num_int')->nullable();
            $table->string('entre_calle')->nullable();
            $table->string('y_calle')->nullable();
            $table->string('otra_referencia')->nullable();
            $table->string('colonia');
            $table->integer('ct_localidad');
            $table->string('localidad');
            $table->integer('ct_municipio');
            $table->string('municipio');
            $table->integer('ct_entidad_federativa');
            $table->string('entidad_federativa');
            $table->integer('codigo_postal_id');
            $table->string('codigo_postal');
            $table->string('telefono')->nullable();
            $table->string('celular')->nullable();
            $table->string('email')->nullable();
            //$table->integer('grupo_vulnerable')->nullable();
            $table->integer('ct_nacionalidad')->nullable()->default(126) // Nacionalidad mexicana por default
                ->comment('Referencia a catálogo de nacionalidades, 126 = MEXICANA');
            //$table->integer('ct_ageb')->nullable();
            // $table->integer('ct_red_social')->nullable();
            // $table->string('red_social')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('padron_historico');

    }
};
