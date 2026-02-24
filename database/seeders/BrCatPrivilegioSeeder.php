<?php

namespace Database\Seeders;

use App\Models\BrCatPrivilegio;
use Illuminate\Database\Seeder;

class BrCatPrivilegioSeeder extends Seeder
{

    // Datos para la tabla br_cat_privilegios
    public function run(): void
    {
        $privilegios = [
            'CAN_READ',
            'CAN_INSERT',
            'CAN_UPDATE',
            'CAN_DELETE',
        ];

        foreach ($privilegios as $privilegio) {
            BrCatPrivilegio::create([
                'descripcion' => $privilegio,
            ]);
        }
    }
}
