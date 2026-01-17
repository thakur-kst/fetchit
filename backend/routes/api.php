<?php

use Illuminate\Support\Facades\Route;

/*
||--------------------------------------------------------------------------
|| API Routes
||--------------------------------------------------------------------------
||
|| Main API routes file for the FetchIt API.
||
|| Architecture:
|| - This file serves as the entry point for API versioning
|| - Individual modules register their routes through ServiceProviders
|| - Each module has its own routes file in: app/Modules/{Module}/routes/api.php
|| - Modules are self-contained and register routes with 'api/v1' prefix
||
|| Module Registration:
|| - Modules register routes in their ServiceProvider::registerRoutes() method
|| - All module routes are automatically prefixed with 'api/v1'
|| - See bootstrap/providers.php for registered modules
||
|| Available Modules:
|| - Auth: Authentication and authorization endpoints
|| - Customer: Customer management and profile endpoints
|| - Payment: Payment processing and wallet management
|| - RBAC: Role-based access control
|| - HealthCheck: Health monitoring endpoints
|| - Tools: Utility and tool endpoints
|| - And more...
||
*/

/*
||--------------------------------------------------------------------------
|| API Version 1 (v1)
||--------------------------------------------------------------------------
|| Current stable API version. All new features should be added here.
|| Breaking changes require a new version (v2).
||
|| Note: Module routes are registered separately through ServiceProviders.
|| This file contains only core API-level routes.
||
*/

Route::prefix('v1')->name('v1.')->group(function () {
    /**
     * API Root Endpoint
     * 
     * Provides API version information and available endpoints.
     * Useful for API discovery and version verification.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    Route::get('/', function () {
        return response()->json([
            'name' => config('app.name', 'FetchIt'),
            'version' => config('scramble.info.version', '1.0.0'),
            'status' => 'operational',
            'environment' => config('app.env'),
            'timestamp' => now()->toIso8601String(),
            'documentation' =>config('app.env') === 'local' ? [
                'api_docs' => url('/docs/api'),
            ] : [],
        ], 200, [
            'Content-Type' => 'application/json',
            'X-API-Version' => config('scramble.info.version', '1.0.0'),
        ]);
    })->name('api.root');

});
