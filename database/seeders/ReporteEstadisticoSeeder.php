<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\BrAcceso;
use App\Models\BrDirectorio;
use Carbon\Carbon;

/**
 * ReporteEstadisticoSeeder
 * ────────────────────────────────────────────────────────────────────────────
 * Genera datos estadísticos realistas para el dashboard "Reportes Avanzados":
 *
 *   • Usa los 7 especialistas creados por BrDirectorioSeeder.
 *     Si no existen (ejecución en solitario) los crea desde cero.
 *   • Municipios: prioriza los 10 municipios principales del Estado de México
 *     (ct_entidad_federativa = 15). Si el catálogo está vacío los inserta.
 *   • ~1 000 asesorías distribuidas en 90 días con variación realista por
 *     especialista: distintos pesos de volumen y cobertura municipal.
 *   • Distribución de estados: 55% CERRADA, 20% ABIERTA, 15% CANALIZADO, 10% PENDIENTE.
 *
 * Uso:
 *   php artisan db:seed --class=ReporteEstadisticoSeeder
 * ────────────────────────────────────────────────────────────────────────────
 */
class ReporteEstadisticoSeeder extends Seeder
{
    // ── Municipios objetivo (EDOMEX, entidad 15, códigos INEGI) ──────────────
    private const MUNICIPIOS_EDOMEX = [
        ['ct_municipio' =>  33, 'municipio' => 'Ecatepec de Morelos'],
        ['ct_municipio' =>  57, 'municipio' => 'Naucalpan de Juárez'],
        ['ct_municipio' => 106, 'municipio' => 'Toluca'],
        ['ct_municipio' =>  99, 'municipio' => 'Texcoco'],
        ['ct_municipio' =>  25, 'municipio' => 'Chalco'],
        ['ct_municipio' =>  29, 'municipio' => 'Chimalhuacán'],
        ['ct_municipio' =>  58, 'municipio' => 'Nezahualcóyotl'],
        ['ct_municipio' => 104, 'municipio' => 'Tlalnepantla de Baz'],
        ['ct_municipio' =>  11, 'municipio' => 'Atizapán de Zaragoza'],
        ['ct_municipio' =>  39, 'municipio' => 'Ixtapaluca'],
    ];

    // ── Configuración de volumen y cobertura por especialidad ────────────────
    // municipios_idx: índices sobre el array de municipios disponibles
    // peso:           asesorías máximas por día activo
    // dias_activos:   probabilidad (%) de que haya actividad en un día dado
    private const CONFIG_ESPECIALIDADES = [
        'medicina general' => [
            'municipios_idx' => [0, 1, 2, 3, 4, 5, 6, 7],  // 8 municipios
            'peso'           => 9,
            'dias_activos'   => 80,
        ],
        'fisioterapia' => [
            'municipios_idx' => [0, 1, 2, 3],               // 4 municipios
            'peso'           => 5,
            'dias_activos'   => 70,
        ],
        'trabajo social' => [
            'municipios_idx' => [0, 1, 3, 5, 6, 7],         // 6 municipios
            'peso'           => 6,
            'dias_activos'   => 75,
        ],
        'gerontologia' => [
            'municipios_idx' => [0, 2, 4],                  // 3 municipios
            'peso'           => 3,
            'dias_activos'   => 60,
        ],
        'psicologica' => [
            'municipios_idx' => [0, 1, 2, 3, 4, 5, 6, 7],  // 8 municipios
            'peso'           => 8,
            'dias_activos'   => 78,
        ],
        'juridica' => [
            'municipios_idx' => [0, 1],                     // 2 municipios
            'peso'           => 3,
            'dias_activos'   => 55,
        ],
        'nutricional' => [
            'municipios_idx' => [0, 1, 2, 4, 8],            // 5 municipios
            'peso'           => 5,
            'dias_activos'   => 72,
        ],
    ];

    private const ESTADOS     = ['ABIERTA', 'CERRADA', 'CANALIZADO', 'PENDIENTE'];
    private const PESOS_ESTADO = [20, 55, 15, 10];

    private array $municipiosDisponibles = [];

    // ─────────────────────────────────────────────────────────────────────────

    public function run(): void
    {
        $this->command->info('📦 ReporteEstadisticoSeeder — iniciando...');

        // ── PASO 0: Preparar municipios ──────────────────────────────────────
        $this->municipiosDisponibles = $this->prepararMunicipios();
        $total = count($this->municipiosDisponibles);
        $this->command->info("  ✔ {$total} municipios disponibles");

        // ── PASO 1: Obtener (o crear) los directorios de especialistas ────────
        $directorios = $this->obtenerDirectorios();
        if (empty($directorios)) {
            $this->command->error('  ✘ No se encontraron directorios de especialistas. Abortando.');
            return;
        }
        $this->command->info('  ✔ ' . count($directorios) . ' especialistas cargados');

        // ── PASO 2: Generar asesorías ─────────────────────────────────────────
        $fechaBase    = Carbon::now()->subDays(90)->startOfDay();
        $totalCreadas = 0;

        foreach ($directorios as $nombreUsuario => $directorio) {
            $config = self::CONFIG_ESPECIALIDADES[$nombreUsuario]
                ?? ['municipios_idx' => [0, 1], 'peso' => 4, 'dias_activos' => 65];

            $cant = $this->crearAsesorias($directorio, $config, $fechaBase);
            $totalCreadas += $cant;
            $this->command->info("  ✔ [{$nombreUsuario}]: {$cant} asesorías");
        }

        $this->command->info("✅ Seeder completado — Total asesorías insertadas: {$totalCreadas}");
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  MUNICIPIOS
    // ─────────────────────────────────────────────────────────────────────────

    private function prepararMunicipios(): array
    {
        $entidad = 15; // Estado de México

        // Intentar cargar municipios de EDOMEX desde el catálogo
        $rows = DB::table('br_cat_municipios')
            ->where('ct_entidad_federativa', $entidad)
            ->orderByRaw(
                "CASE ct_municipio
                    WHEN 33  THEN 1  WHEN 57  THEN 2  WHEN 106 THEN 3
                    WHEN 99  THEN 4  WHEN 25  THEN 5  WHEN 29  THEN 6
                    WHEN 58  THEN 7  WHEN 104 THEN 8  WHEN 11  THEN 9
                    WHEN 39  THEN 10 ELSE 99
                END"
            )
            ->limit(10)
            ->get(['ct_municipio', 'municipio', 'ct_entidad_federativa']);

        if ($rows->isEmpty()) {
            // Fallback: buscar cualquier municipio en el catálogo
            $rows = DB::table('br_cat_municipios')
                ->limit(10)
                ->get(['ct_municipio', 'municipio', 'ct_entidad_federativa']);
        }

        if ($rows->isEmpty()) {
            // Bootstrap de emergencia: insertar los 10 municipios EDOMEX
            $this->command->warn('  ⚠ br_cat_municipios vacío — insertando municipios del Estado de México...');
            foreach (self::MUNICIPIOS_EDOMEX as $m) {
                DB::table('br_cat_municipios')->insertOrIgnore([
                    'ct_municipio'         => $m['ct_municipio'],
                    'municipio'            => $m['municipio'],
                    'ct_entidad_federativa' => $entidad,
                ]);
            }
            $rows = DB::table('br_cat_municipios')
                ->where('ct_entidad_federativa', $entidad)
                ->limit(10)
                ->get(['ct_municipio', 'municipio', 'ct_entidad_federativa']);
        }

        $municipios = $rows->map(fn($r) => [
            'ct_municipio'          => $r->ct_municipio,
            'ct_entidad_federativa' => $r->ct_entidad_federativa,
            'nombre'                => $r->municipio,
        ])->toArray();

        // Asegurar localidad (ct_localidad=1) y código postal base para cada municipio
        foreach ($municipios as $m) {
            DB::table('br_cat_localidades')->insertOrIgnore([
                'ct_localidad'          => 1,
                'localidad'             => 'Cabecera Municipal',
                'ct_municipio'          => $m['ct_municipio'],
                'ct_entidad_federativa' => $m['ct_entidad_federativa'],
                'periodo'               => 2026,
                'estatus'               => true,
            ]);
            DB::table('br_cat_codigos_postales')->insertOrIgnore([
                'codigo_postal'         => '50000',
                'colonia'               => 'Col. Centro',
                'ct_cp'                 => 1,
                'ct_municipio'          => $m['ct_municipio'],
                'ct_entidad_federativa' => $m['ct_entidad_federativa'],
                'periodo'               => 2026,
                'estatus'               => true,
            ]);
        }

        return $municipios;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  DIRECTORIOS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retorna un array [nombre_usuario => BrDirectorio] de los especialistas activos.
     * Si no existen los crea con datos profesionales realistas.
     */
    private function obtenerDirectorios(): array
    {
        $nombresEsperados = array_keys(self::CONFIG_ESPECIALIDADES);

        $directorios = BrDirectorio::with(['usuario:id,nombre_usuario', 'tipoAsesoria:id,descripcion'])
            ->where('estado', true)
            ->get()
            ->filter(fn($d) => $d->usuario && in_array($d->usuario->nombre_usuario, $nombresEsperados))
            ->keyBy(fn($d) => $d->usuario->nombre_usuario)
            ->all();

        // Si no hay ninguno (ejecución en solitario), crear desde cero
        if (empty($directorios)) {
            $this->command->warn('  ⚠ No se encontraron directorios en br_directorio — creando especialistas de prueba...');
            $directorios = $this->crearEspecialistasEmergencia();
        }

        return $directorios;
    }

    private function crearEspecialistasEmergencia(): array
    {
        $specs = [
            ['usuario' => 'medicina general', 'tipo' => 1, 'primer_ap' => 'TORRES',   'segundo_ap' => 'HERNÁNDEZ', 'nombres' => 'Patricia', 'tel' => '7224110001'],
            ['usuario' => 'fisioterapia',     'tipo' => 2, 'primer_ap' => 'CASTRO',   'segundo_ap' => 'MENDOZA',   'nombres' => 'Roberto',  'tel' => '7224110002'],
            ['usuario' => 'trabajo social',   'tipo' => 3, 'primer_ap' => 'ÁVILA',    'segundo_ap' => 'GUZMÁN',    'nombres' => 'Laura',    'tel' => '7224110003'],
            ['usuario' => 'gerontologia',     'tipo' => 4, 'primer_ap' => 'VEGA',     'segundo_ap' => 'SALINAS',   'nombres' => 'Marta',    'tel' => '7224110004'],
            ['usuario' => 'psicologica',      'tipo' => 5, 'primer_ap' => 'RAMÍREZ',  'segundo_ap' => 'FLORES',    'nombres' => 'Claudia',  'tel' => '7224110005'],
            ['usuario' => 'juridica',         'tipo' => 6, 'primer_ap' => 'HERRERA',  'segundo_ap' => 'MORALES',   'nombres' => 'Jorge',    'tel' => '7224110006'],
            ['usuario' => 'nutricional',      'tipo' => 7, 'primer_ap' => 'MORALES',  'segundo_ap' => 'ORTIZ',     'nombres' => 'Sandra',   'tel' => '7224110007'],
        ];

        $result = [];
        foreach ($specs as $s) {
            $acceso = BrAcceso::firstOrCreate(
                ['nombre_usuario' => $s['usuario']],
                [
                    'nombre_completo' => "{$s['primer_ap']} {$s['segundo_ap']}, {$s['nombres']}",
                    'password'        => bcrypt($s['usuario'] . '2026'),
                    'password_plain'  => $s['usuario'] . '2026',
                    'estado'          => true,
                    'tipo_usuario'    => 'ESPECIALISTA',
                ]
            );

            $directorio = BrDirectorio::firstOrCreate(
                ['usuario_id' => $acceso->id],
                [
                    'tipo_asesoria'  => $s['tipo'],
                    'primer_ap_dir'  => $s['primer_ap'],
                    'segundo_ap_dir' => $s['segundo_ap'],
                    'nombres'        => $s['nombres'],
                    'telefono'       => $s['tel'],
                    'email'          => strtolower("{$s['nombres']}.{$s['primer_ap']}@brigadas.edomex.gob.mx"),
                    'estado'         => true,
                ]
            );

            $result[$s['usuario']] = $directorio;
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  ASESORÍAS
    // ─────────────────────────────────────────────────────────────────────────

    private function crearAsesorias(BrDirectorio $directorio, array $config, Carbon $fechaBase): int
    {
        $totalDisp   = count($this->municipiosDisponibles);
        $munIndices  = array_filter(
            $config['municipios_idx'],
            fn($i) => $i < $totalDisp
        );

        // Si los índices superan los municipios disponibles, usar los que haya
        if (empty($munIndices)) {
            $munIndices = range(0, min(2, $totalDisp - 1));
        }

        $municipios    = array_map(fn($i) => $this->municipiosDisponibles[$i], $munIndices);
        $peso          = $config['peso'];
        $diasActivos   = $config['dias_activos'];
        $totalCreadas  = 0;

        for ($dia = 0; $dia < 90; $dia++) {
            $fecha = $fechaBase->copy()->addDays($dia);

            // Actividad probabilística según el peso del especialista
            if (rand(1, 100) > $diasActivos) {
                continue;
            }

            // Número de asesorías ese día (mínimo 1, máximo = peso)
            $cantidadDia = rand(1, $peso);

            $pacientesHoy = [];
            for ($i = 0; $i < $cantidadDia; $i++) {
                $municipio = $municipios[array_rand($municipios)];
                $estado    = $this->elegirEstado();

                $pacienteId = DB::table('br_pacientes')->insertGetId([
                    'primer_ap'             => $this->apellido(),
                    'segundo_ap'            => $this->apellido(),
                    'nombres'               => $this->nombre(),
                    'fecha_nacimiento'      => $this->fechaNacimientoAdultoMayor(),
                    'genero'                => rand(0, 1) ? 'F' : 'M',
                    'ct_edo_civil'          => rand(1, 5),
                    'tp_id_oficial'         => 1,
                    'id_oficial'            => (string) rand(1000000, 9999999),
                    'ct_ent_nac'            => rand(1, 32),
                    'curp'                  => $this->generarCurp(),
                    'calle'                 => 'Calle ' . rand(1, 200),
                    'num_ext'               => (string) rand(1, 500),
                    'num_int'               => '',
                    'entre_calle'           => 'Av. Principal',
                    'y_calle'               => 'Calle Norte',
                    'otra_referencia'       => 'Frente al parque municipal',
                    'colonia'               => 'Col. Centro',
                    'ct_localidad'          => 1,
                    'localidad'             => 'Área Urbana',
                    'ct_municipio'          => $municipio['ct_municipio'],
                    'municipio'             => $municipio['nombre'],
                    'ct_entidad_federativa' => $municipio['ct_entidad_federativa'],
                    'entidad_federativa'    => 'Estado de México',
                    'codigo_postal'         => '50000',
                    'telefono'              => '72' . rand(10000000, 99999999),
                    'celular'               => '55' . rand(10000000, 99999999),
                    'email'                 => strtolower(substr(str_shuffle('abcdefghijklmnop'), 0, 7)) . '@test.com',
                    'ct_gdo_estudios'       => rand(1, 6),
                    'ct_nacionalidad'       => 1,
                    'estado_padron'         => 'ACTIVO',
                    'requiere_seguimiento'  => false,
                ]);

                DB::table('br_asesorias')->insert([
                    'asesor_atendio_id'        => $directorio->id,
                    'paciente_id'              => $pacienteId,
                    'estado_asesoria'          => $estado,
                    'estado_registro'          => true,
                    'tipo_asesoria_canaliza_id' => $estado === 'CANALIZADO' ? rand(1, 7) : null,
                    'fecha_asesoria'           => $fecha->toDateString(),
                    'created_at'               => $fecha->copy()->addHours(rand(8, 16)),
                    'updated_at'               => $fecha->copy()->addHours(rand(8, 16)),
                ]);

                $totalCreadas++;
                unset($pacientesHoy);
            }
        }

        return $totalCreadas;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  HELPERS DE DATOS ALEATORIOS
    // ─────────────────────────────────────────────────────────────────────────

    private function apellido(): string
    {
        static $pool = [
            'García','Hernández','Martínez','López','González','Pérez','Sánchez',
            'Ramírez','Torres','Flores','Rivera','Gómez','Díaz','Reyes','Cruz',
            'Morales','Ortiz','Gutiérrez','Chávez','Ramos','Medina','Aguilar',
            'Vela','Castillo','Jiménez','Moreno','Núñez','Mendoza','Rojas','Ruiz',
        ];
        return $pool[array_rand($pool)];
    }

    private function nombre(): string
    {
        static $pool = [
            'María','José','Juan','Ana','Luis','Carmen','Carlos','Laura','Pedro',
            'Rosa','Miguel','Elena','Jorge','Patricia','Antonio','Sofía','Ricardo',
            'Isabel','Fernando','Alejandra','Francisco','Gabriela','Roberto',
            'Verónica','Sergio','Claudia','Eduardo','Natalia','Héctor','Diana',
        ];
        return $pool[array_rand($pool)];
    }

    /**
     * Genera una fecha de nacimiento para adultos mayores (60-90 años).
     */
    private function fechaNacimientoAdultoMayor(): string
    {
        $anio = rand(date('Y') - 90, date('Y') - 60);
        $mes  = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
        $dia  = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        return "{$anio}-{$mes}-{$dia}";
    }

    /**
     * Genera un CURP con formato válido de 18 caracteres.
     * Formato: AAAA YYMMDD X EE CCC NN
     */
    private function generarCurp(): string
    {
        $letras    = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4));
        $anio      = rand(34, 64); // nacidos entre 1934 y 1964 (60-90 años)
        $mes       = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
        $dia       = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        $sexo      = rand(0, 1) ? 'H' : 'M';
        $estado    = 'MC'; // Estado de México
        $consonantes = strtoupper(substr(str_shuffle('BCDFGHJKLMNPQRSTVWXYZ'), 0, 3));
        $digitos   = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
        return "{$letras}{$anio}{$mes}{$dia}{$sexo}{$estado}{$consonantes}{$digitos}";
    }

    private function elegirEstado(): string
    {
        $rand = rand(1, 100);
        $acum = 0;
        foreach (self::ESTADOS as $i => $estado) {
            $acum += self::PESOS_ESTADO[$i];
            if ($rand <= $acum) return $estado;
        }
        return 'CERRADA';
    }
}
