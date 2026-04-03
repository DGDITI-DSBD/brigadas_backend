<?php

use App\Http\Controllers\accesoController;
use App\Http\Controllers\authController;
use App\Http\Controllers\PacientesController;
use App\Http\Controllers\ReporteAvanzadoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\catalogosController;
use App\Http\Controllers\asesoriaController;
use App\Http\Controllers\HistorialController;
/************************************************
 * Rutas de autenticación
 ************************************************/

// =============================
// API v1
// =============================
Route::prefix('v1')->group(function () {

    // --------- Rutas públicas ----------
    Route::prefix('auth')->group(function () {
        Route::post('login', [authController::class, 'login']);
    });

    // --------- Rutas protegidas por JWT -----------
    Route::middleware(['auth:api'])->group(function () {

        // Auth protegidas
        Route::get('checking-user', [authController::class, 'checkingUser']);
        Route::post('update-first-login', [authController::class, 'updateFirstLogin']);

        // ─── Reportes Avanzados ─────────────────────────────────────────
        // v1/reportes/asesorias      — listado plano de asesorías (Tablero, PorDía, PorMunicipio, General, Adultos)
        // v1/reportes/especialista   — reporte individual (filtros: directorio_id, desde, hasta, municipio_id)
        // v1/reportes/especialistas  — listado de todos los especialistas activos (Admin/Coord)
        // v1/reportes/exportar       — exportar CSV (solo ADMINISTRADOR y COORDINADOR)
        Route::prefix('reportes')->group(function () {
            Route::get('asesorias', [ReporteAvanzadoController::class, 'listar_asesorias']);
            Route::get('especialista', [ReporteAvanzadoController::class, 'reporte_individual_especialista']);
            Route::get('especialistas', [ReporteAvanzadoController::class, 'listar_especialistas']);
            Route::get('exportar', [ReporteAvanzadoController::class, 'exportar_csv']);
            Route::get('tablero/pdf', [ReporteAvanzadoController::class, 'tablero_pdf']);
            Route::get('chart/pdf', [ReporteAvanzadoController::class, 'chart_pdf']);
        });

        // ─── Historial de Brigadas ─────────────────────────────────────────
        Route::prefix('historial')->group(function () {
            Route::get('ruta', [HistorialController::class, 'ruta']);
        });


        // Controller - Usuarios
        Route::get('usuarios', [accesoController::class, 'getListUsuario']);
        Route::post('usuarios/filters', [accesoController::class, 'getFiltersListUsuario']);
        Route::post('store-usuarios', [accesoController::class, 'StoreUsuario']);
        Route::get('get-usuarios/{id}', [accesoController::class, 'getFieldsEditUsuario']);
        Route::put('update-usuarios/{id}', [accesoController::class, 'UpdateUsuario']);
        Route::delete('delete-usuarios/{id}', [accesoController::class, 'DeleteUsuario']);


        Route::get('directorio-beneficiarias'   , [PacientesController::class, 'getDirectorioBeneficiarias']);
        Route::post('store-beneficiarias'       , [PacientesController::class, 'storeDirectorioBeneficiarias']);
        Route::get('beneficiaria/{id}',         [PacientesController::class, 'getDirectorioBeneficiariasById']);
        Route::put('update-beneficiaria/{id}'   , [PacientesController::class, 'updateDirectorioBeneficiarias']);
        Route::delete('delete-beneficiaria/{id}'   , [PacientesController::class, 'deleteDirectorioBeneficiarias']);


    });

    Route::group(['middleware' => 'auth:api', 'prefix' => 'catalogos'], function () {
        Route::post('/preguntas_opciones', [catalogosController::class, 'getCuestionarioAsesoria']);
        Route::get('/beneficiarias-historico', [asesoriaController::class, 'get_beneficiarias_historico']);
        Route::post('/importar-paciente', [asesoriaController::class, 'import_padron_historico']);

        Route::get('/entidades-federativas' , [catalogosController::class, 'getEntidadesFederativas']);
        Route::get('/municipios/{entidad}'            , [catalogosController::class, 'getMunicipios']);
        Route::get('/localidades/{municipio}'           , [catalogosController::class, 'getLocalidades']);
        Route::get('/codigos-postales/{municipio}'      , [catalogosController::class, 'getCodigosPostales']);
        Route::get('/nacionalidades'        , [catalogosController::class, 'getNacionalidades']);
        Route::get('/estado-civil'          , [catalogosController::class, 'getEstadoCivil']);
        Route::get('/grado-estudios'        , [catalogosController::class, 'getGradoDeEstudios']);
        Route::get('/parentesco'            , [catalogosController::class, 'getParentesco']);
        Route::get('/documentos-oficiales'  , [catalogosController::class, 'getDocumentosOficiales']);


        Route::get('/directorio-especialistas', [catalogosController::class, 'getDirectorioEspecialistas']);
        Route::get('/catalogos-generales', [catalogosController::class, 'get_catalogos_generales']);
    });
    
    //Route::post('/preguntas_opciones', [catalogosController::class, 'getCuestionarioAsesoria']);
    Route::get('/asesorias-beneficiarias', [asesoriaController::class, 'asesoria_beneficiaria']);
    Route::group(['middleware' => 'auth:api', 'prefix' => 'asesorias'], function () {
        // Listar beneficiarias con asesorías según usuario logueado
        Route::get('/beneficiarias', [asesoriaController::class, 'beneficiaria_asesoria']);
        Route::get('/relacion-beneficiarias', [asesoriaController::class, 'asesoria_beneficiaria']);
        
        // CRUD de Asesorías
        Route::post('/', [asesoriaController::class, 'create_asesoria']);
        Route::post('/crear-completa', [asesoriaController::class, 'crear_asesoria_completa']); // Endpoint transaccional
        Route::put('/{id}', [asesoriaController::class, 'update_asesoria']);
        Route::put('/{id}/actualizar-completa', [asesoriaController::class, 'actualizar_asesoria_completa']); // Actualizar asesoría completa
        Route::get('/beneficiaria/{id}', [asesoriaController::class, 'get_asesorias']);
        
        // Preguntas y Respuestas
        Route::get('/{id}/preguntas-respuestas', [asesoriaController::class, 'get_asesoria_preguntas_respuestas']);
        Route::post('/preguntas-respuestas', [asesoriaController::class, 'create_asesoria_preguntas_respuestas']);
        Route::put('/preguntas-respuestas/{id}', [asesoriaController::class, 'update_asesoria_preguntas_respuestas']);
        Route::delete('/preguntas-respuestas/{id}', [asesoriaController::class, 'delete_asesoria_preguntas_respuestas']);
        
        // Acciones específicas
        Route::put('/{id}/canalizar', [asesoriaController::class, 'canalizar_asesoria']);
        Route::put('/{id}/estado', [asesoriaController::class, 'estado_update_asesoria']);
    });

    // CRUD de Pacientes/Beneficiarias
    Route::group(['middleware' => 'auth:api', 'prefix' => 'pacientes'], function () {
        Route::put('/{id}', [asesoriaController::class, 'update_paciente']); // Actualizar datos del paciente
    });

});
