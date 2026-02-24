<?php

namespace Database\Seeders;

use App\Models\BrCatTipoAsesoria;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BrCatTipoAsesoriaSeeder extends Seeder
{

    // Datos para la tabla br_cat_tipo_asesoria
    public function run(): void
    {
        $tipos = [
            'MEDICINA GENERAL',
            'FISIOTERAPIA',
            'TRABAJO SOCIAL',
            'GERONTOLOGÍA',
            'PSICOLÓGICA',
            'JURÍDICA',
            'NUTRICIONAL',
        ];

        foreach ($tipos as $tipo) {
            BrCatTipoAsesoria::create([
                'descripcion' => $tipo,
            ]);
        }
    }
}
