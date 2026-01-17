<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Api\V1\AuthController;

/*
|--------------------------------------------------------------------------
| Auth API Routes
|--------------------------------------------------------------------------
*/

// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    // Google OAuth verification
    Route::post('/google/verify', [AuthController::class, 'verifyGoogleToken'])
        ->name('auth.google.verify')
        ->middleware('throttle:10,1'); // 10 requests per minute

    // Refresh token
    Route::post('/refresh', [AuthController::class, 'refresh'])
        ->name('auth.refresh')
        ->middleware('throttle:20,1'); // 20 requests per minute
});

// Authenticated routes (require Bearer token)
Route::prefix('auth')->middleware(['auth:api'])->group(function () {
    // Get current user
    Route::get('/me', [AuthController::class, 'me'])
        ->name('auth.me');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('auth.logout');
});
