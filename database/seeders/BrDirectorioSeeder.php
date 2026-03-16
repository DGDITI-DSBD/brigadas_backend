<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BrAcceso;
use App\Models\BrDirectorio;

class BrDirectorioSeeder extends Seeder
{
    /**
     * Crea las entradas del directorio para los 7 especialistas de la brigada.
     *
     * NOTA: `nombre_usuario` en $data se usa SÓLO para encontrar al usuario en br_accesos.
     *       `primer_ap_dir`, `segundo_ap_dir` y `nombres` son el nombre profesional real que
     *       verá el dashboard y los reportes exportados.
     */
    public function run(): void
    {
        $data = [
            [
                'nombre_usuario' => 'medicina general',
                'tipo_asesoria'  => 1,
                'primer_ap_dir'  => 'TORRES',
                'segundo_ap_dir' => 'HERNÁNDEZ',
                'nombres'        => 'Patricia',
                'telefono'       => '7224110001',
                'email'          => 'patricia.torres@brigadas.edomex.gob.mx',
            ],
            [
                'nombre_usuario' => 'fisioterapia',
                'tipo_asesoria'  => 2,
                'primer_ap_dir'  => 'CASTRO',
                'segundo_ap_dir' => 'MENDOZA',
                'nombres'        => 'Roberto',
                'telefono'       => '7224110002',
                'email'          => 'roberto.castro@brigadas.edomex.gob.mx',
            ],
            [
                'nombre_usuario' => 'trabajo social',
                'tipo_asesoria'  => 3,
                'primer_ap_dir'  => 'ÁVILA',
                'segundo_ap_dir' => 'GUZMÁN',
                'nombres'        => 'Laura',
                'telefono'       => '7224110003',
                'email'          => 'laura.avila@brigadas.edomex.gob.mx',
            ],
            [
                'nombre_usuario' => 'gerontologia',
                'tipo_asesoria'  => 4,
                'primer_ap_dir'  => 'VEGA',
                'segundo_ap_dir' => 'SALINAS',
                'nombres'        => 'Marta',
                'telefono'       => '7224110004',
                'email'          => 'marta.vega@brigadas.edomex.gob.mx',
            ],
            [
                'nombre_usuario' => 'psicologica',
                'tipo_asesoria'  => 5,
                'primer_ap_dir'  => 'RAMÍREZ',
                'segundo_ap_dir' => 'FLORES',
                'nombres'        => 'Claudia',
                'telefono'       => '7224110005',
                'email'          => 'claudia.ramirez@brigadas.edomex.gob.mx',
            ],
            [
                'nombre_usuario' => 'juridica',
                'tipo_asesoria'  => 6,
                'primer_ap_dir'  => 'HERRERA',
                'segundo_ap_dir' => 'MORALES',
                'nombres'        => 'Jorge',
                'telefono'       => '7224110006',
                'email'          => 'jorge.herrera@brigadas.edomex.gob.mx',
            ],
            [
                'nombre_usuario' => 'nutricional',
                'tipo_asesoria'  => 7,
                'primer_ap_dir'  => 'MORALES',
                'segundo_ap_dir' => 'ORTIZ',
                'nombres'        => 'Sandra',
                'telefono'       => '7224110007',
                'email'          => 'sandra.morales@brigadas.edomex.gob.mx',
            ],
        ];

        $usuarios = BrAcceso::select('id', 'nombre_usuario')
            ->whereIn('nombre_usuario', array_column($data, 'nombre_usuario'))
            ->get()
            ->keyBy('nombre_usuario');

        foreach ($data as $entry) {
            $usuario = $usuarios->get($entry['nombre_usuario']);

            if (!$usuario) {
                $this->command->warn("  ⚠ Usuario [{$entry['nombre_usuario']}] no encontrado — omitiendo.");
                continue;
            }

            BrDirectorio::firstOrCreate(
                ['usuario_id' => $usuario->id],
                [
                    'tipo_asesoria'  => $entry['tipo_asesoria'],
                    'primer_ap_dir'  => $entry['primer_ap_dir'],
                    'segundo_ap_dir' => $entry['segundo_ap_dir'],
                    'nombres'        => $entry['nombres'],
                    'telefono'       => $entry['telefono'],
                    'email'          => $entry['email'],
                    'estado'         => true,
                ]
            );
        }
    }
}
