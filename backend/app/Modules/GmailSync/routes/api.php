<?php

use Illuminate\Support\Facades\Route;
use Modules\GmailSync\Http\Controllers\Api\V1\GmailSyncController;

/*
|--------------------------------------------------------------------------
| Gmail Sync API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('gmail/accounts')->middleware(['auth:api'])->group(function () {
    // Trigger sync
    Route::post('/{accountId}/sync', [GmailSyncController::class, 'syncAccount'])
        ->name('gmail.sync.sync')
        ->middleware('throttle:5,1'); // 5 requests per minute

    // Get sync status
    Route::get('/{accountId}/sync-status', [GmailSyncController::class, 'getSyncStatus'])
        ->name('gmail.sync.status')
        ->middleware('throttle:60,1'); // 60 requests per minute (for polling)
});
