<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Migracion para crear la tabla de pacientes
    public function up(): void
    {
        Schema::create('br_pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('folio_relacionado', 18)->nullable();
            $table->string('primer_ap', 80);
            $table->string('segundo_ap', 80);
            $table->string('nombres', 80);
            $table->date('fecha_nacimiento')->comment('DD/MM/AAAA');
            $table->char('genero', 1)->comment('M/H VALIDACIÓN LOGICA (PADRON DE ALIMENTACIÓN PARA EL BIENESTAR');
            $table->integer('ct_edo_civil');
            $table->integer('tp_id_oficial');
            $table->integer('id_oficial');
            $table->integer('ct_ent_nac');
            $table->string('curp', 18);
            $table->string('calle', 150);
            $table->string('num_ext', 40);
            $table->string('num_int', 40);
            $table->string('entre_calle', 80);
            $table->string('y_calle', 80);
            $table->string('otra_referencia', 100);
            $table->string('colonia', 80);
            $table->integer('ct_localidad');
            $table->string('localidad', 200);
            $table->integer('ct_municipio');
            $table->string('municipio', 50);
            $table->unsignedBigInteger('ct_entidad_federativa');
            $table->string('entidad_federativa', 50);
            $table->string('codigo_postal', 5);
            $table->string('telefono', 10);
            $table->string('celular', 10);
            $table->string('email', 80);
            $table->integer('ct_gdo_estudios');
            $table->integer('ct_nacionalidad');
            $table->string('estado_padron')->comment('BENEFICIARIO, CAPTACION');
            $table->boolean('requiere_seguimiento')->comment('true=si, false=no');



            // Foreign keys - Entidades federativas
            $table->foreign('ct_entidad_federativa')
                ->references('ct_entidad_federativa')
                ->on('br_cat_entidades_federativas')
                ->onDelete('restrict');
        });
    }

    // Eliminar la tabla de pacientes
    public function down(): void
    {
        Schema::dropIfExists('br_pacientes');
    }
};
