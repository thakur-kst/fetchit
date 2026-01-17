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

    'default' => env('DB_DEFAULT_SCHEMA', 'fetchit'),

    /*
    |--------------------------------------------------------------------------
    | Schema Definitions
    |--------------------------------------------------------------------------
    |
    | Define all available schemas and their purposes:
    | - fetchit: All application tables (users, orders, gmail_accounts, etc.)
    | - public: Laravel framework tables (migrations, cache, sessions, etc.)
    |
    */

    'schemas' => [
        'fetchit' => [
            'description' => 'FetchIt application tables',
            'tables' => [
                // User Management
                'users',
                'refresh_tokens',

                // Gmail Integration
                'gmail_accounts',
                'gmail_sync_jobs',

                // Orders
                'orders',

                // Telescope Tables (if used)
                'telescope_entries',
                'telescope_entries_tags',
                'telescope_monitoring',
            ],
        ],

        'public' => [
            'description' => 'Laravel framework tables',
            'tables' => [
                // Laravel Framework Tables
                'migrations',
                'cache',
                'cache_locks',
                'sessions',
                'password_reset_tokens',
                'personal_access_tokens',
                'failed_jobs',
                'jobs',
                'job_batches',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema Table Mapping
    |--------------------------------------------------------------------------
    |
    | Reverse mapping: table name => schema name
    | Automatically generated from the schemas array above
    |
    */

    'table_schema_map' => collect([
        'fetchit' => collect(config('schemas.schemas.fetchit.tables', [])),
        'public' => collect(config('schemas.schemas.public.tables', [])),
    ])->flatMap(function ($tables, $schema) {
        return $tables->mapWithKeys(function ($table) use ($schema) {
            return [$table => $schema];
        });
    })->toArray(),

];
