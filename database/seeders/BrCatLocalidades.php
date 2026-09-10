<?php

namespace Database\Seeders;

use App\Models\CatLocalidad;
use Illuminate\Database\Seeder;
use App\Traits\clean_data;
use Carbon\Carbon;
use DB;

use Log;
class BrCatLocalidades extends Seeder
{

    use clean_data;
    // Datos para la tabla br_cat_localidades
    protected $table = 'br_cat_localidades';

    public function run(): void
    {

        $file_csv = storage_path('app/cat_localidades_2026.csv');

        if(!file_exists($file_csv)){
            $this->command->error("Archivo no encontrado...");
            return;
        }
        // $_ini_config_limit = ini_get('memory_limit');
        // if(is_null($_ini_config_limit) || empty($_ini_config_limit)){
        //     $this->command->info("La variable de configuración 'memory_limit' no te definida");
        //     return;
        // }

        // $_convert_int = $this->convertBytes($_ini_config_limit);
        // $_limit_thread = $_convert_int * 0.80; // Flush al 80% de capacidad

        $max_chunk = $this->recalculate_rows_per_params($this->table);

        $this->command->info("Ejecutando inserción de filas...");

        $data = [];
        $_ex_open_file = fopen($file_csv, 'r');
        //$batchSize = $this->maxBatchBytes();

        $lote = 0;
        $num_lotes = 0;

        $cabecera = array_map([$this, 'cleanBOM'], fgetcsv($_ex_open_file));
       
        //Log::info("cabecera", [$cabecera]);
        while(($row = fgetcsv($_ex_open_file)) !== false){

            $row = array_map([$this, 'cleanBOM'], $row);

            $_mapping = [];
            
            foreach ($cabecera as $index => $nombre_cabecera) {
                
                $_mapping[$nombre_cabecera] = $row[$index] ?? null;

            }
                $data[] = $_mapping; 
                $lote++;
                

            if($lote >= $max_chunk){
                DB::table($this->table)->insert($data);
                $num_lotes++;
                $this->command->info("Refrescando lote: ". $num_lotes);

                $data = [];
                $lote = 0;
                
                gc_collect_cycles(); // Liberamos memoria
            }

        }

           
                if(!empty($data)){
                    DB::table($this->table)->insert($data);
                }
            
        
        
    }
}
