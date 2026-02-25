<?php

namespace Database\Seeders;

use App\Models\CatGradoEstudios;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BrCatGradoEstudios extends Seeder
{
    // Seeder para poblar la tabla de grados de estudios
    public function run(): void
    {
        $gradosEstudios = [
            ['grado_estudios' => 'Ninguno(a)'                                    ],
            ['grado_estudios' => 'Preescolar'                                    ],
            ['grado_estudios' => 'Primaria'                                      ],
            ['grado_estudios' => 'Secundaria'                                    ],
            ['grado_estudios' => 'Bachillerato, Preparatoria o Equivalente'      ],
            ['grado_estudios' => 'Licenciatura o Equivalente'                    ],
            ['grado_estudios' => 'Técnico'                                       ],
            ['grado_estudios' => 'Maestría'                                      ],
            ['grado_estudios' => 'Doctorado'                                     ],
            ['grado_estudios' => 'Educación Especial'                            ],
        ];

        foreach ($gradosEstudios as $gradoEstudio) {
            CatGradoEstudios::create($gradoEstudio);
        }
    }
}
