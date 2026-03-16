<?php

use App\Http\Controllers\accesoController;
use App\Http\Controllers\authController;
use App\Http\Controllers\catalogosController;
use App\Http\Controllers\asesoriaController;

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

    // --------- Rutas protegidas -----------
    Route::middleware(['auth:api'])->group(function () {

        // Auth protegidas
        Route::get('checking-user', [authController::class, 'checkingUser']);
        Route::post('update-first-login', [authController::class, 'updateFirstLogin']);


        // Controller - Usuarios
        Route::get('usuarios', [accesoController::class, 'getListUsuario']);
        Route::post('usuarios/filters', [accesoController::class, 'getFiltersListUsuario']);
        Route::post('store-usuarios', [accesoController::class, 'StoreUsuario']);
        Route::get('get-usuarios/{id}', [accesoController::class, 'getFieldsEditUsuario']);
        Route::put('update-usuarios/{id}', [accesoController::class, 'UpdateUsuario']);
        Route::delete('delete-usuarios/{id}', [accesoController::class, 'DeleteUsuario']);

    });

    Route::group(['middleware' => 'auth:api', 'prefix' => 'catalogos'], function () {
        Route::post('/preguntas_opciones', [catalogosController::class, 'getCuestionarioAsesoria']);
        Route::get('/beneficiarias-historico', [asesoriaController::class, 'get_beneficiarias_historico']);
        Route::post('/importar-paciente', [asesoriaController::class, 'import_padron_historico']);
    });
    
    //Route::post('/preguntas_opciones', [catalogosController::class, 'getCuestionarioAsesoria']);
    Route::group(['middleware' => 'auth:api', 'prefix' => 'asesorias'], function () {
        // Listar beneficiarias con asesorías según usuario logueado
        Route::get('/beneficiarias', [asesoriaController::class, 'beneficiaria_asesoria']);
        
        // CRUD de Asesorías
        Route::post('/', [asesoriaController::class, 'create_asesoria']);
        Route::post('/crear-completa', [asesoriaController::class, 'crear_asesoria_completa']); // Endpoint transaccional
        Route::put('/{id}', [asesoriaController::class, 'update_asesoria']);
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

});
