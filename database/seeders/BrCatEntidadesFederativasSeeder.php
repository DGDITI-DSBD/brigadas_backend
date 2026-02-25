<?php

namespace Database\Seeders;

use App\Models\BrCatEntidadesFederativas;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrCatEntidadesFederativasSeeder extends Seeder
{
    // Seeder para poblar la tabla de entidades federativas
    public function run(): void
    {
        $entidades = [
            [ 'ct_entidad_federativa' => 1,   'entidad_federativa' => 'Aguascalientes',                   'abre_entidad_federativa' => 'Ags.'    ],
            [ 'ct_entidad_federativa' => 2,   'entidad_federativa' => 'Baja California',                  'abre_entidad_federativa' => 'BC'      ],
            [ 'ct_entidad_federativa' => 3,   'entidad_federativa' => 'Baja California Sur',              'abre_entidad_federativa' => 'BCS'     ],
            [ 'ct_entidad_federativa' => 4,   'entidad_federativa' => 'Campeche',                         'abre_entidad_federativa' => 'Camp.'   ],
            [ 'ct_entidad_federativa' => 5,   'entidad_federativa' => 'Coahuila de Zaragoza',             'abre_entidad_federativa' => 'Coah.'   ],
            [ 'ct_entidad_federativa' => 6,   'entidad_federativa' => 'Colima',                           'abre_entidad_federativa' => 'Col.'    ],
            [ 'ct_entidad_federativa' => 7,   'entidad_federativa' => 'Chiapas',                          'abre_entidad_federativa' => 'Chis.'   ],
            [ 'ct_entidad_federativa' => 8,   'entidad_federativa' => 'Chihuahua',                        'abre_entidad_federativa' => 'Chih.'   ],
            [ 'ct_entidad_federativa' => 9,   'entidad_federativa' => 'Ciudad de México',                 'abre_entidad_federativa' => 'CDMX'    ],
            [ 'ct_entidad_federativa' => 10,  'entidad_federativa' => 'Durango',                          'abre_entidad_federativa' => 'Dgo.'    ],
            [ 'ct_entidad_federativa' => 11,  'entidad_federativa' => 'Guanajuato',                       'abre_entidad_federativa' => 'Gto.'    ],
            [ 'ct_entidad_federativa' => 12,  'entidad_federativa' => 'Guerrero',                         'abre_entidad_federativa' => 'Gro.'    ],
            [ 'ct_entidad_federativa' => 13,  'entidad_federativa' => 'Hidalgo',                          'abre_entidad_federativa' => 'Hgo.'    ],
            [ 'ct_entidad_federativa' => 14,  'entidad_federativa' => 'Jalisco',                          'abre_entidad_federativa' => 'Jal.'    ],
            [ 'ct_entidad_federativa' => 15,  'entidad_federativa' => 'México',                           'abre_entidad_federativa' => 'Méx.'    ],
            [ 'ct_entidad_federativa' => 16,  'entidad_federativa' => 'Michoacán de Ocampo',              'abre_entidad_federativa' => 'Mich.'   ],
            [ 'ct_entidad_federativa' => 17,  'entidad_federativa' => 'Morelos',                          'abre_entidad_federativa' => 'Mor.'    ],
            [ 'ct_entidad_federativa' => 18,  'entidad_federativa' => 'Nayarit',                          'abre_entidad_federativa' => 'Nay.'    ],
            [ 'ct_entidad_federativa' => 19,  'entidad_federativa' => 'Nuevo León',                       'abre_entidad_federativa' => 'NL'      ],
            [ 'ct_entidad_federativa' => 20,  'entidad_federativa' => 'Oaxaca',                           'abre_entidad_federativa' => 'Oax.'    ],
            [ 'ct_entidad_federativa' => 21,  'entidad_federativa' => 'Puebla',                           'abre_entidad_federativa' => 'Pue.'    ],
            [ 'ct_entidad_federativa' => 22,  'entidad_federativa' => 'Querétaro',                        'abre_entidad_federativa' => 'Qro.'    ],
            [ 'ct_entidad_federativa' => 23,  'entidad_federativa' => 'Quintana Roo',                     'abre_entidad_federativa' => 'Q. Roo'  ],
            [ 'ct_entidad_federativa' => 24,  'entidad_federativa' => 'San Luis Potosí',                  'abre_entidad_federativa' => 'SLP'     ],
            [ 'ct_entidad_federativa' => 25,  'entidad_federativa' => 'Sinaloa',                          'abre_entidad_federativa' => 'Sin.'    ],
            [ 'ct_entidad_federativa' => 26,  'entidad_federativa' => 'Sonora',                           'abre_entidad_federativa' => 'Son.'    ],
            [ 'ct_entidad_federativa' => 27,  'entidad_federativa' => 'Tabasco',                          'abre_entidad_federativa' => 'Tab.'    ],
            [ 'ct_entidad_federativa' => 28,  'entidad_federativa' => 'Tamaulipas',                       'abre_entidad_federativa' => 'Tamps.'  ],
            [ 'ct_entidad_federativa' => 29,  'entidad_federativa' => 'Tlaxcala',                         'abre_entidad_federativa' => 'Tlax.'   ],
            [ 'ct_entidad_federativa' => 30,  'entidad_federativa' => 'Veracruz de Ignacio de la Llave',  'abre_entidad_federativa' => 'Ver.'    ],
            [ 'ct_entidad_federativa' => 31,  'entidad_federativa' => 'Yucatán',                          'abre_entidad_federativa' => 'Yuc.'    ],
            [ 'ct_entidad_federativa' => 32,  'entidad_federativa' => 'Zacatecas',                        'abre_entidad_federativa' => 'Zac.'    ],
        ];

        BrCatEntidadesFederativas::insert($entidades);
    }
}
