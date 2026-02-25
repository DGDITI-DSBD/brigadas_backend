<?php

namespace Database\Seeders;

use App\Models\CatLocalidad;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class BrCatLocalidades extends Seeder
{
    // Datos para la tabla br_cat_localidades
    public function run(): void
    {
        $now = Carbon::now();

        // Ruta del archivo CSV
        $csvPath = storage_path('app/br_cat_localidades_csv_region_15.csv');

        if (!file_exists($csvPath)) {
            $this->command->error("Archivo CSV no encontrado: {$csvPath}");
            return;
        }

        $this->command->info("Iniciando importación de localidades...");
        $this->command->info("Archivo: {$csvPath}");

        // Abrir archivo CSV
        $file = fopen($csvPath, 'r');

        // Leer header (primera línea)
        $header = fgetcsv($file);
        $this->command->info("Header CSV: " . implode(', ', $header));

        $batchSize = 500; // Lotes más grandes para mejor rendimiento
        $batch = [];
        $count = 0;
        $errorCount = 0;

        // Leer datos línea por línea
        while (($data = fgetcsv($file)) !== false) {
            // Validar que la línea tenga los campos necesarios
            if (count($data) < 5) {
                $errorCount++;
                continue;
            }

            $localidad = [
                'localidad_id'              => (int) $data[0], // id_municipio en CSV se mapea a id_localidad
                'ct_localidad'              => (int) $data[1],
                'localidad'                 => trim($data[2]), // LOCALIDAD en CSV
                'ct_municipio'              => (int) $data[3],
                'ct_entidad_federativa'     => (int) $data[4],
                'periodo'                   => (int) $data[5],
                'estatus'                   => $data[6],            // Activo por defecto
            ];

            $batch[] = $localidad;
            $count++;

            // Insertar en lotes para mejor rendimiento
            if (count($batch) >= $batchSize) {
                try {
                    CatLocalidad::insert($batch);
                    $this->command->info("Insertadas {$count} localidades...");
                } catch (\Exception $e) {
                    $this->command->error("Error al insertar lote: " . $e->getMessage());
                    $errorCount += count($batch);
                }
                $batch = [];
            }
        }

        // Insertar último lote si queda algo
        if (!empty($batch)) {
            try {
                CatLocalidad::insert($batch);
                $this->command->info("Insertando último lote...");
            } catch (\Exception $e) {
                $this->command->error("Error al insertar último lote: " . $e->getMessage());
                $errorCount += count($batch);
            }
        }

        fclose($file);

        $this->command->info("✅ Total de localidades insertadas: {$count}");
        if ($errorCount > 0) {
            $this->command->warn("⚠️  Registros con errores: {$errorCount}");
        }
    }
}
