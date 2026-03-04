<?php

use App\Http\Controllers\authController;
use App\Http\Controllers\reporteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

        // Reportes
        Route::prefix('reportes')->group(function () {
            Route::get('especialista', [reporteController::class, 'reporte_individual_especialista']);
        });

    });

});
