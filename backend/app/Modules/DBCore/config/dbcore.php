<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DBCore Module Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains schema settings for the DBCore
    | module. These settings define which PostgreSQL schemas to use for core
    | and customer portal data.
    |
    */

    /*
    | Application Schema Name
    |
    | The PostgreSQL schema for all application tables. Uses 'public' only.
    */
    'fetchit_schema' => env('DB_SCHEMA_FETCHIT', 'public'),

    /*
    | Module metadata
    */
    'version' => '1.0.0',
    'author' => 'FetchIt Team',
    'description' => 'Database foundation module for FetchIt single-schema architecture',
];

