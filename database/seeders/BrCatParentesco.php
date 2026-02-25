<?php

namespace Database\Seeders;

use App\Models\CatParentesco;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BrCatParentesco extends Seeder
{
    // Seeder para poblar la tabla de parentesco
    public function run(): void
    {
        $parentescos = [
            [ 'parentesco' => 'Ninguno'      ],
            [ 'parentesco' => 'Padre/Madre'  ],
            [ 'parentesco' => 'Tutor(a)'     ],
            [ 'parentesco' => 'Hermano(a)'   ],
            [ 'parentesco' => 'Abuelo(a)'    ],
            [ 'parentesco' => 'Tio(a)'       ],
            [ 'parentesco' => 'Bisabuelo(a)' ],
            [ 'parentesco' => 'Esposo(a)'    ],
            [ 'parentesco' => 'Concubino(a)' ],
            [ 'parentesco' => 'Hijo(a)'      ],
            [ 'parentesco' => 'Sobrino(a)'   ],
            [ 'parentesco' => 'Nieto(a)'     ],
            [ 'parentesco' => 'Bisnieto(a)'  ],
            [ 'parentesco' => 'Tutoreado(a)' ],
            [ 'parentesco' => 'Primo(a)'     ],
            [ 'parentesco' => 'Otro'         ],
        ];

        foreach ($parentescos as $parentesco) {
            CatParentesco::create($parentesco);
        }
    }
}
