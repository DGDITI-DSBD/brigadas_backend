<?php

use App\Http\Controllers\accesoController;
use App\Http\Controllers\authController;
use App\Http\Controllers\catalogosController;

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
    });
    //Route::post('/preguntas_opciones', [catalogosController::class, 'getCuestionarioAsesoria']);
    Route::group(['middleware' => 'auth:api', 'prefix' => 'asesorias'], function () {
        
    });

});
