<?php

namespace Database\Seeders;

use App\Models\CatDocumentosOficiales;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BrCatDocumentosOficiales extends Seeder
{
    // Seeder para poblar la tabla de documentos oficiales
    public function run(): void
    {
        $documentos = [
            [ 'documento_oficial' => 'RFC'                  ],
            [ 'documento_oficial' => 'CREDENCIAL INE'       ],
            [ 'documento_oficial' => 'ACTA DE NACIMIENTO'   ],
            [ 'documento_oficial' => 'LICENCIA DE MANEJO'   ],
            [ 'documento_oficial' => 'CARTILLA MILITAR'     ],
            [ 'documento_oficial' => 'CREDENCIAL INAPAM'    ],
            [ 'documento_oficial' => 'CREDENCIAL IMSS'      ],
            [ 'documento_oficial' => 'CREDENCIAL ISSSTE'    ],
            [ 'documento_oficial' => 'CREDENCIAL INSEN'     ],
            [ 'documento_oficial' => 'PASAPORTE'            ],
            [ 'documento_oficial' => 'CURP'                 ],
        ];

        foreach ($documentos as $documento) {
            CatDocumentosOficiales::create($documento);
        }
    }
}
