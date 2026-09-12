<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DriverApprovalController;
use App\Http\Controllers\Api\DriverLocationController;
use App\Http\Controllers\Api\GeoController;
use App\Http\Controllers\Api\ServiceRequestController;
use App\Http\Controllers\Api\TripAssignmentController;
use Illuminate\Support\Facades\Route;

// ---------- Público (sin token) ----------
Route::post('/auth/register/citizen', [AuthController::class, 'registerCitizen']);
Route::post('/auth/register/driver', [AuthController::class, 'registerDriver']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    // ---------- Cualquier usuario autenticado ----------
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/geo/zone', [GeoController::class, 'zone']);
    Route::get('/drivers/nearby', [DriverLocationController::class, 'nearby']);

    // ---------- Conductor ----------
    // IMPORTANTE: /service-requests/open debe declararse ANTES que
    // /service-requests/{serviceRequest} (más abajo, grupo citizen),
    // porque Laravel resuelve las rutas en el orden en que se registran:
    // si la ruta con {parámetro} quedara primero, "open" se interpretaría
    // como un ID y nunca llegaría a esta ruta.
    Route::middleware('role:driver')->group(function () {
        Route::get('/service-requests/open', [ServiceRequestController::class, 'openRequests']);
        Route::post('/service-requests/{serviceRequest}/accept', [TripAssignmentController::class, 'accept']);
        Route::patch('/trip-assignments/{tripAssignment}/status', [TripAssignmentController::class, 'updateStatus']);
        Route::post('/driver/location', [DriverLocationController::class, 'update']);
        Route::post('/driver/availability', [DriverLocationController::class, 'updateAvailability']);
    });

    // ---------- Ciudadano ----------
    Route::middleware('role:citizen')->group(function () {
        Route::post('/service-requests', [ServiceRequestController::class, 'store']);
        Route::get('/service-requests/{serviceRequest}', [ServiceRequestController::class, 'show']);
    });

    // ---------- Admin ----------
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/stats', [AdminController::class, 'stats']);
        Route::get('/admin/drivers', [DriverApprovalController::class, 'all']);
        Route::get('/admin/drivers/pending', [DriverApprovalController::class, 'pending']);
        Route::post('/admin/drivers/{driverProfile}/decision', [DriverApprovalController::class, 'decide']);
    });
});
