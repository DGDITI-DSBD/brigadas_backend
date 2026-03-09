<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('br_asesorias', function (Blueprint $table) {
            // Fecha explícita de la asesoría (para estadísticas diarias limpias)
            $table->date('fecha_asesoria')->nullable()->after('tipo_asesoria_canaliza_id');
        });

        // Poblar fecha_asesoria con la fecha de created_at para registros existentes
        DB::statement('UPDATE br_asesorias SET fecha_asesoria = DATE(created_at) WHERE fecha_asesoria IS NULL');
    }

    public function down(): void
    {
        Schema::table('br_asesorias', function (Blueprint $table) {
            $table->dropColumn('fecha_asesoria');
        });
    }
};
