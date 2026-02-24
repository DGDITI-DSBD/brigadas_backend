<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BrCatPrivilegioSeeder::class,
            BrCatTipoAsesoriaSeeder::class,
            BrCatEntidadesFederativasSeeder::class,
            BrAccesoSeeder::class,
            BrDirectorioSeeder::class,
            BrPreguntasSeeder::class,
            BrOpcionesSeeder::class,
            
        ]);
    }
}
