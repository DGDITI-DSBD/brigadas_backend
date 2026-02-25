<?php

namespace Database\Seeders;

use App\Models\CatEstadosCiviles;
use Illuminate\Database\Seeder;

class BrCatEstadosCiviles extends Seeder
{

    // Seeder para poblar la tabla de estados civiles
    public function run(): void
    {
        $estadosCiviles = [
            ['estado_civil' => 'Ninguno'       ],
            ['estado_civil' => 'Soltero(a)'    ],
            ['estado_civil' => 'Casado(a)'     ],
            ['estado_civil' => 'Viudo(a)'      ],
            ['estado_civil' => 'Divorciado(a)' ],
            ['estado_civil' => 'Unión Libre'   ],
            ['estado_civil' => 'Separado(a)'   ],
            ['estado_civil' => 'Concubinato'   ],
            ['estado_civil' => 'Otro'          ],
        ];

        foreach ($estadosCiviles as $estadoCivil) {
            CatEstadosCiviles::create($estadoCivil);
        }


    }
}
