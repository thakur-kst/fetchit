<?php

use Illuminate\Support\Facades\Route;
use Modules\GmailAccounts\Http\Controllers\Api\V1\GmailAccountController;

/*
|--------------------------------------------------------------------------
| Gmail Accounts API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('gmail')->middleware(['auth:api'])->group(function () {
    // Get authorization URL
    Route::get('/auth/authorize', [GmailAccountController::class, 'getAuthorizationUrl'])
        ->name('gmail.auth.authorize');

    // Gmail accounts
    Route::get('/accounts', [GmailAccountController::class, 'index'])
        ->name('gmail.accounts.index')
        ->middleware('throttle:60,1'); // 60 requests per minute

    Route::post('/accounts', [GmailAccountController::class, 'store'])
        ->name('gmail.accounts.store')
        ->middleware('throttle:10,1'); // 10 requests per minute

    Route::delete('/accounts/{accountId}', [GmailAccountController::class, 'destroy'])
        ->name('gmail.accounts.destroy');

    Route::post('/accounts/{accountId}/refresh', [GmailAccountController::class, 'refreshToken'])
        ->name('gmail.accounts.refresh')
        ->middleware('throttle:20,1'); // 20 requests per minute
});
