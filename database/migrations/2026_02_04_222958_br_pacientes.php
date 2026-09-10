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
            $table->integer('edad')->nullable();
            $table->date('fecha_nacimiento')->comment('DD/MM/AAAA');
            $table->char('genero', 1)->comment('M/H VALIDACIÓN LOGICA (PADRON DE ALIMENTACIÓN PARA EL BIENESTAR');
            $table->unsignedBigInteger('ct_edo_civil');
            $table->unsignedBigInteger('tp_id_oficial');
            $table->string('id_oficial', 20);
            $table->integer('ct_ent_nac');
            $table->string('curp', 18);
            $table->string('calle', 150);
            $table->string('num_ext', 40);
            $table->string('num_int', 40);
            $table->string('entre_calle', 80);
            $table->string('y_calle', 80);
            $table->string('otra_referencia', 100);
            $table->string('colonia', 80);
            $table->unsignedBigInteger('ct_localidad');
            $table->string('localidad', 200);
            $table->integer('ct_municipio');
            $table->string('municipio', 50);
            $table->integer('ct_entidad_federativa');
            $table->string('entidad_federativa', 50);
            $table->unsignedBigInteger('codigo_postal_id')->default(1);
            $table->string('codigo_postal', 5);
            $table->string('telefono', 10);
            $table->string('celular', 10);
            $table->string('email', 80);
            $table->unsignedBigInteger('ct_gdo_estudios');
            $table->unsignedBigInteger('ct_nacionalidad');
            $table->string('estado_padron')->default('BENEFICIARIO')->comment('BENEFICIARIO, CAPTACION');
           // $table->boolean('requiere_seguimiento')->comment('true=si, false=no');
            $table->timestamps();



            // Foreign key - Entidades federativas
            $table->foreign('ct_entidad_federativa')
                ->references('ct_entidad_federativa')
                ->on('br_cat_entidades_federativas')
                ->onDelete('restrict');

            // Foreign key compuesta - Municipios
            $table->foreign(['ct_entidad_federativa', 'ct_municipio'])
                ->references(['ct_entidad_federativa', 'ct_municipio'])
                ->on('br_cat_municipios')
                ->onDelete('restrict');

            // Foreign key compuesta - Localidades
            $table->foreign(['ct_entidad_federativa', 'ct_municipio', 'ct_localidad'])
                ->references(['ct_entidad_federativa', 'ct_municipio', 'ct_localidad'])
                ->on('br_cat_localidades')
                ->onDelete('restrict');

            // Foreign key compuesta - Codigos postales
            $table->foreign('codigo_postal_id')
                ->references('cp_id')
                ->on('br_cat_codigos_postales')
                ->onDelete('set null');

            // Foreign key - Nacionalidades
            $table->foreign('ct_nacionalidad')
                ->references('ct_nacionalidad')
                ->on('br_cat_nacionalidad')
                ->onDelete('restrict');


            // Foreign key - Estados civiles
            $table->foreign('ct_edo_civil')
                ->references('ct_edo_civil')
                ->on('br_cat_estado_civil')
                ->onDelete('restrict');
            
            // Foreign key - Grados de estudios
            $table->foreign('ct_gdo_estudios')
                ->references('ct_gdo_estudios')
                ->on('br_cat_grado_estudios')
                ->onDelete('restrict');

            // Foreign key - Documentos oficiales
            $table->foreign('tp_id_oficial')
                ->references('tp_id_oficial')
                ->on('br_cat_documentos_oficiales')
                ->onDelete('restrict');
            
        });
    }

    // Eliminar la tabla de pacientes
    public function down(): void
    {
        Schema::dropIfExists('br_pacientes');
    }
};
