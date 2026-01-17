<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\Http\Controllers\Api\V1\KycDocumentTypeController;
use Modules\Shared\Http\Controllers\Api\V1\CountryController;
use Modules\Shared\Http\Controllers\Api\V1\StateController;
use Modules\Shared\Http\Controllers\Api\V1\CityController;
use Modules\Shared\Http\Controllers\Api\V1\PostcodeController;

/*
|--------------------------------------------------------------------------
| Shared Module API Routes
|--------------------------------------------------------------------------
|
| Routes for shared/master data endpoints.
|
*/

Route::prefix('master-data')->middleware('auth.middleware')->name('master-data.')->group(function () {
    // KYC Document Types
    Route::get('/kyc-document-types', [KycDocumentTypeController::class, 'index'])
        ->name('kyc-document-types.index');

    // Countries
    Route::get('/countries', [CountryController::class, 'index'])
        ->name('countries.index');

    // States
    Route::get('/states', [StateController::class, 'index'])
        ->name('states.index');

    // Cities
    Route::get('/cities', [CityController::class, 'index'])
        ->name('cities.index');

    // Postcodes
    Route::get('/postcodes', [PostcodeController::class, 'index'])
        ->name('postcodes.index');
});

