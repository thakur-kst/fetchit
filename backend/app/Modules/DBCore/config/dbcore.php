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
    | FetchIt Schema Name
    | 
    | The PostgreSQL schema name for all application tables.
    | Single schema architecture for simplicity and better performance.
    */
    'fetchit_schema' => env('DB_SCHEMA_FETCHIT', 'fetchit'),

    /*
    | Module metadata
    */
    'version' => '1.0.0',
    'author' => 'FetchIt Team',
    'description' => 'Database foundation module for FetchIt single-schema architecture',
];

