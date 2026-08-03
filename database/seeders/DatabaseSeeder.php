<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    // Seeder principal que llama a todos los demás seeders para poblar la base de datos
    public function run(): void
    {
        $this->call([
            BrCatPrivilegioSeeder::class,
            BrCatTipoAsesoriaSeeder::class,
            BrCatEntidadesFederativasSeeder::class,
            BrCatMunicipios::class,
            BrCatLocalidades::class,
            BrCatCodigosPostales::class,
            BrCatNacionalidades::class,
            BrCatEstadosCiviles::class,
            BrCatGradoEstudios::class,
            BrCatParentesco::class,
            BrCatDocumentosOficiales::class,
            BrAccesoSeeder::class,
            BrDirectorioSeeder::class,
            BrPreguntasSeeder::class,
            BrOpcionesSeeder::class,
            ReporteEstadisticoSeeder::class,
        ]);
    }
}
