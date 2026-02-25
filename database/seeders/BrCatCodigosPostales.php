<?php

namespace Database\Seeders;

use App\Models\CatCodigosPostales;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
class BrCatCodigosPostales extends Seeder
{
    // Datos para la tabla br_cat_codigos_postales  
    public function run(): void
    {
        $now = Carbon::now();

        // Ruta del archivo CSV
        $csvPath = storage_path('app/br_codigos_postales_una_hoja_csv_region_15.csv');

        if (!file_exists($csvPath)) {
            $this->command->error("Archivo CSV no encontrado: {$csvPath}");
            return;
        }

        $this->command->info("Iniciando importación de códigos postales...");
        $this->command->info("Archivo: {$csvPath}");

        // Abrir archivo CSV
        $file = fopen($csvPath, 'r');

        // Leer header (primera línea)
        $header = fgetcsv($file);
        $this->command->info("Columnas encontradas: " . implode(', ', $header));    

        $batchSize = 1000;
        $batch = [];
        $count = 0;
        $totalLines = 157196;
        $this->command->info("Procesando ~{$totalLines} registros en lotes de {$batchSize}...");

        $uniqueKeys = [];

        while (($data = fgetcsv($file)) !== false) {
            // Validar que la fila tenga suficientes columnas
            if (count($data) < 7) continue;

            // Generar clave única
            $key = $data[5] . '-' . $data[4] . '-' . $data[1] . '-' . $data[2];
            if (isset($uniqueKeys[$key])) continue; // Saltar duplicados
            $uniqueKeys[$key] = true;

            $codigoPostal = [
                'codigo_postal'             => (int) $data[1],
                'colonia'                   => $data[2],
                'ct_cp'                     => (int) $data[3],
                'ct_municipio'              => (int) $data[4],
                'ct_entidad_federativa'     => (int) $data[5],
                'periodo'                   => (int) $data[6],
                'estatus'                   => true,
            ];
            $batch[] = $codigoPostal;
            $count++;
            if (count($batch) >= $batchSize) {
                CatCodigosPostales::insert($batch);
                $progress = round(($count / $totalLines) * 100, 1);
                $this->command->info("Insertados {$count} códigos postales... ({$progress}%)");
                $batch = [];

                // Liberar memoria cada cierto número de lotes
                if ($count % 10000 === 0) {
                    gc_collect_cycles();
                }
            }
        }

        // Insertar último lote si queda algo
        if (!empty($batch)) {
            CatCodigosPostales::insert($batch);
        }

        fclose($file);

        $this->command->info("✅ Total de códigos postales insertados: {$count}");
        $this->command->info("Importación completada exitosamente.");
    }
}
