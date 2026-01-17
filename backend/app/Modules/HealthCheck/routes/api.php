<?php

use Illuminate\Support\Facades\Route;
use Modules\HealthCheck\Http\Controllers\Api\V1\HealthCheckController;

/*
|--------------------------------------------------------------------------
| HealthCheck Module Routes - Version 1
|--------------------------------------------------------------------------
|
| Health check endpoints for monitoring application status.
| These endpoints are publicly accessible (no authentication required).
|
*/

Route::get('/health', [HealthCheckController::class, 'index'])->name('health.basic');
Route::get('/health/detailed', [HealthCheckController::class, 'detailed'])->name('health.detailed');
Route::get('/health/readiness', [HealthCheckController::class, 'readiness'])->name('health.readiness');
Route::get('/health/liveness', [HealthCheckController::class, 'liveness'])->name('health.liveness');
