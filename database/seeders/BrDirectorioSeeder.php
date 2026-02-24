<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BrAcceso;
use App\Models\BrDirectorio;

class BrDirectorioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $data = [
            [
                'tipo_asesoria' => 1,
                'primer_ap_dir' => 'MEDICINA GENERAL',
                'segundo_ap_dir' => 'MEDICINA GENERAL',
                'nombres' => 'medicina general',
                'telefono' => '5551234567',
                'email' => 'medicina.general@example.com'
            ],

            [
                'tipo_asesoria' => 2,
                'primer_ap_dir' => 'FISIOTERAPIA',
                'segundo_ap_dir' => 'FISIOTERAPIA',
                'nombres' => 'fisioterapia',
                'telefono' => '5559876543',
                'email' => 'fisioterapia@example.com'
            ],

            [
                'tipo_asesoria' => 3,
                'primer_ap_dir' => 'TRABAJO SOCIAL',
                'segundo_ap_dir' => 'TRABAJO SOCIAL',
                'nombres' => 'trabajo social',
                'telefono' => '5555555555',
                'email' => 'trabajo.social@example.com'
            ],
            
            [
                'tipo_asesoria' => 4,
                'primer_ap_dir' => 'GERONTOLOGÍA',
                'segundo_ap_dir' => 'GERONTOLOGÍA',
                'nombres' => 'gerontologia',
                'telefono' => '5551112222',
                'email' => 'gerontologia@example.com'
            ],

            [
                'tipo_asesoria' => 5,
                'primer_ap_dir' => 'PSICOLÓGICA',
                'segundo_ap_dir' => 'PSICOLÓGICA',
                'nombres' => 'psicologica',
                'telefono' => '5553334444',
                'email' => 'psicologia@example.com'
            ],
            
            [
                'tipo_asesoria' => 6,
                'primer_ap_dir' => 'JURÍDICA',
                'segundo_ap_dir' => 'JURÍDICA',
                'nombres' => 'juridica',
                'telefono' => '5556667777',
                'email' => 'juridico@example.com'
            ],
            [
                'tipo_asesoria' => 7,
                'primer_ap_dir' => 'NUTRICIONAL',
                'segundo_ap_dir' => 'NUTRICIONAL',
                'nombres' => 'nutricional',
                'telefono' => '5558889999',
                'email' => 'nutricion@example.com'
            ]
        ];


        $fetchingUsers = BrAcceso::select('id', 'nombre_usuario')
        ->whereIn('nombre_usuario', ['medicina general', 'fisioterapia', 'trabajo social', 'gerontologia', 'psicologica', 'juridica', 'nutricional'])
        ->get();

        foreach ($data as $index => $entry) {
            $usuario = $fetchingUsers->firstWhere('nombre_usuario', $entry['nombres']);
            if ($usuario) {
                BrDirectorio::create([
                    'usuario_id' => $usuario->id,
                    'tipo_asesoria' => $entry['tipo_asesoria'],
                    'primer_ap_dir' => $entry['primer_ap_dir'],
                    'segundo_ap_dir' => $entry['segundo_ap_dir'],
                    'nombres' => $entry['nombres'],
                    'telefono' => $entry['telefono'],
                    'email' => $entry['email'],
                    'estado' => true,
                ]);
            }
        }

        
    }
}
