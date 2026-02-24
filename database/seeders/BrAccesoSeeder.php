<?php

namespace Database\Seeders;

use App\Models\BrAcceso;
use App\Models\BrCatPrivilegio;
use App\Models\UsuarioPrivilegio;
use Illuminate\Database\Seeder;

class BrAccesoSeeder extends Seeder
{

    // Datos para la tabla br_accesos
    public function run(): void
    {
        $usuarios = [
            [
                'nombre_usuario' => 'admin',
                'nombre_completo' => 'Administrador General',
                'estado' => true,
                'tipo_usuario' => 'ADMINISTRADOR',
            ],
            [
                'nombre_usuario' => 'coordinador',
                'nombre_completo' => 'Coordinador Principal',
                'estado' => true,
                'tipo_usuario' => 'COORDINADOR',
            ],
            [
                'nombre_usuario' => 'medicina general',
                'nombre_completo' => 'Especialista de Área',
                'estado' => true,
                'tipo_usuario' => 'ESPECIALISTA',
            ],

            [
                'nombre_usuario' => 'fisioterapia',
                'nombre_completo' => 'Especialista de Área',
                'estado' => true,
                'tipo_usuario' => 'ESPECIALISTA',
            ],

            [
                'nombre_usuario' => 'trabajo social',
                'nombre_completo' => 'Especialista de Área',
                'estado' => true,
                'tipo_usuario' => 'ESPECIALISTA',
            ],


             [
                'nombre_usuario' => 'gerontologia',
                'nombre_completo' => 'Especialista de Área',
                'estado' => true,
                'tipo_usuario' => 'ESPECIALISTA',
            ],

            [
                'nombre_usuario' => 'psicologica',
                'nombre_completo' => 'Especialista de Área',
                'estado' => true,
                'tipo_usuario' => 'ESPECIALISTA',
            ],

            [
                'nombre_usuario' => 'juridica',
                'nombre_completo' => 'Especialista de Área',
                'estado' => true,
                'tipo_usuario' => 'ESPECIALISTA',
            ],

            [
                'nombre_usuario' => 'nutricional',
                'nombre_completo' => 'Especialista de Área',
                'estado' => true,
                'tipo_usuario' => 'ESPECIALISTA',
            ]
        ];

        $usuarios_creados = [];
        foreach ($usuarios as $usuario) {
            $usuarios_creados[$usuario['tipo_usuario']] = BrAcceso::create($usuario);
        }


        $privilegios = BrCatPrivilegio::all()->pluck('id', 'descripcion');

        $asignaciones = [
            'ADMINISTRADOR' => ['CAN_READ', 'CAN_INSERT', 'CAN_UPDATE', 'CAN_DELETE'],
            'COORDINADOR'   => ['CAN_READ', 'CAN_UPDATE', 'CAN_DELETE'],
            'ESPECIALISTA'  => ['CAN_READ', 'CAN_INSERT', 'CAN_UPDATE'],
        ];

        foreach ($asignaciones as $tipo => $privs) {
            $usuario = $usuarios_creados[$tipo];
            foreach ($privs as $priv) {
                UsuarioPrivilegio::create([
                    'usuario_id' => $usuario->id,
                    'privilegio_id' => $privilegios[$priv],
                ]);
            }
        }
    }
}
