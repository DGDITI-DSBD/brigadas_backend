<?php

namespace Database\Seeders;

use App\Models\BrCatEntidadesFederativas;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Traits\clean_data;
use Log;

class BrCatEntidadesFederativasSeeder extends Seeder
{

    use clean_data;

   
    public function run(): void
    {
       

        $file_csv = storage_path('app/cat_entidades.csv');

        if(!file_exists($file_csv)){
            $this->command->error("Archivo no encontrado...");
            return;
        }

        $this->command->info("Ejecutando inserción de filas...");

        $data = [];
        $_ex_open_file = fopen($file_csv, 'r');

        $cabecera = array_map([$this, 'cleanBOM'], fgetcsv($_ex_open_file));
       
        
        while(($row = fgetcsv($_ex_open_file)) !== false){

            $row = array_map([$this, 'cleanBOM'], $row);

            $_mapping = [];
            // Log::info("row", [$row]);
            foreach ($cabecera as $index => $nombre_cabecera) {
                
                $_mapping[$nombre_cabecera] = $row[$index] ?? null;

            }
                $data[] = $_mapping; 

        }

        //Log::info("data", $data);
        if(count($data) === 0){
            $this->command->info("Deteniendo ejecución, sin datos a insertar");
            return;
        }else {
            BrCatEntidadesFederativas::insert($data);
        }

        
    }

    
}
