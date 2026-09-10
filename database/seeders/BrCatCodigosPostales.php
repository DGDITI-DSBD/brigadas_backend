<?php

namespace Database\Seeders;

use App\Models\CatCodigosPostales;
use Illuminate\Database\Seeder;
use App\Traits\clean_data;
use DB;


class BrCatCodigosPostales extends Seeder
{
    use clean_data;
    // Datos para la tabla br_cat_codigos_postales  
    protected $table = 'br_cat_codigos_postales';
    protected $route_file = 'app/cat_codigos_postales_act_2026.csv';

    public function run(): void
    {
        $file_csv = storage_path($this->route_file);

        if(!file_exists($file_csv)){
            $this->command->error("Archivo no encontrado...");
            return;
        }

       

        $max_chunk = $this->recalculate_rows_per_params($this->table);

        $this->command->info("Ejecutando inserción de filas...");

        $data = [];
        $_ex_open_file = fopen($file_csv, 'r');

        $lote = 0;
        $num_lotes = 0;

        $cabecera = array_map([$this, 'cleanBOM'], fgetcsv($_ex_open_file));
       
   
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
