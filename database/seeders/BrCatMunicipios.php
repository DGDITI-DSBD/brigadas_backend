<?php

namespace Database\Seeders;

use App\Models\CatMunicipio;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class BrCatMunicipios extends Seeder
{
    // Seeder para la tabla cat_municipios
    public function run(): void
    {
        $now = Carbon::now();

        // Ruta del archivo CSV
        $csvPath = storage_path('app/br_cat_municipios_csv.csv');

        if (!file_exists($csvPath)) {
            $this->command->error("Archivo CSV no encontrado: {$csvPath}");
            return;
        }

        // Abrir archivo CSV
        $file = fopen($csvPath, 'r');

        // Leer header (primera línea)
        $header = fgetcsv($file);

        $batchSize = 100;
        $batch = [];
        $count = 0;

        // Leer datos línea por línea
        while (($data = fgetcsv($file)) !== false) {
            $municipio = [
                'ct_municipio'              => (int) $data[1],
                'municipio'                 => trim($data[2]),
                'ct_entidad_federativa'     => (int) $data[3],
            ];

            $batch[] = $municipio;
            $count++;

            // Insertar en lotes para mejor rendimiento
            if (count($batch) >= $batchSize) {
                CatMunicipio::insert($batch);
                $this->command->info("Insertados {$count} municipios...");
                $batch = [];
            }
        }

        // Insertar último lote si queda algo
        if (!empty($batch)) {
            CatMunicipio::insert($batch);
        }

        fclose($file);

        $this->command->info("Total de municipios insertados: {$count}");
    }
}
