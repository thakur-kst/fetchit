<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database Schemas Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration defines the schema architecture for the FetchIt application.
    | Single schema architecture for simplicity and better performance.
    |
    */

    'enabled' => env('DB_SCHEMAS_ENABLED', true),

    'default' => env('DB_DEFAULT_SCHEMA', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Schema Definitions
    |--------------------------------------------------------------------------
    |
    | All tables use the public schema only.
    |
    */

    'schemas' => [
        'public' => [
            'description' => 'All application and Laravel framework tables',
            'tables' => [
                // Laravel framework
                'migrations',
                'cache',
                'cache_locks',
                'sessions',
                'password_reset_tokens',
                'personal_access_tokens',
                'failed_jobs',
                'jobs',
                'job_batches',
                // FetchIt application
                'users',
                'refresh_tokens',
                'gmail_accounts',
                'gmail_sync_jobs',
                'orders',
                // Telescope (if used)
                'telescope_entries',
                'telescope_entries_tags',
                'telescope_monitoring',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema Table Mapping
    |--------------------------------------------------------------------------
    */

    'table_schema_map' => collect(config('schemas.schemas.public.tables', []))->mapWithKeys(fn ($table) => [$table => 'public'])->toArray(),

];
