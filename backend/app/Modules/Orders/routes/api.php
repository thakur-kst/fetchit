<?php

use Illuminate\Support\Facades\Route;
use Modules\Orders\Http\Controllers\Api\V1\OrderController;

/*
|--------------------------------------------------------------------------
| Orders API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('orders')->middleware(['auth:api'])->group(function () {
    Route::get('/', [OrderController::class, 'index'])
        ->name('orders.index')
        ->middleware('throttle:60,1'); // 60 requests per minute

    Route::post('/', [OrderController::class, 'store'])
        ->name('orders.store');

    Route::get('/{orderId}', [OrderController::class, 'show'])
        ->name('orders.show')
        ->middleware('throttle:60,1');

    Route::put('/{orderId}', [OrderController::class, 'update'])
        ->name('orders.update');

    Route::delete('/{orderId}', [OrderController::class, 'destroy'])
        ->name('orders.destroy');
});
