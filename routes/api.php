<?php

use App\Http\Controllers\authController;
use App\Http\Controllers\ReporteAvanzadoController;
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

    });

});
