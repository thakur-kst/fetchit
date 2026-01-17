<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HealthCheck Module Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains settings for the HealthCheck module.
    | These settings control various aspects of health checking behavior.
    |
    */

    /*
    | Enable or disable health check endpoints
    */
    'enabled' => env('HEALTHCHECK_ENABLED', true),

    /*
    | Health check endpoints configuration
    */
    'endpoints' => [
        'basic' => '/health',
        'detailed' => '/health/detailed',
        'readiness' => '/health/readiness',
        'liveness' => '/health/liveness',
    ],

    /*
    | Checkers configuration
    | Enable or disable specific health checkers
    */
    'checkers' => [
        'application' => env('HEALTHCHECK_APPLICATION', true),
        'database' => env('HEALTHCHECK_DATABASE', true),
        'cache' => env('HEALTHCHECK_CACHE', true),
        'redis' => env('HEALTHCHECK_REDIS', true),
    ],

    /*
    | Logging configuration
    */
    'logging' => [
        'enabled' => env('HEALTHCHECK_LOGGING_ENABLED', true),
        'channel' => env('HEALTHCHECK_LOGGING_CHANNEL', 'stack'),
        'log_requests' => env('HEALTHCHECK_LOG_REQUESTS', true),
        'log_responses' => env('HEALTHCHECK_LOG_RESPONSES', true),
    ],

    /*
    | Database logging configuration
    */
    'database_logging' => [
        'enabled' => env('HEALTHCHECK_DB_LOGGING_ENABLED', false),
        'table' => 'health_check_logs',
    ],

    /*
    | Performance thresholds (in milliseconds)
    */
    'thresholds' => [
        'slow_check' => env('HEALTHCHECK_SLOW_THRESHOLD', 1000),
        'warning_check' => env('HEALTHCHECK_WARNING_THRESHOLD', 500),
        'database' => [
            'timeout_ms' => env('HEALTHCHECK_DB_TIMEOUT', 5000),
            'warning_ms' => env('HEALTHCHECK_DB_WARNING', 1000),
        ],
        'cache' => [
            'timeout_ms' => env('HEALTHCHECK_CACHE_TIMEOUT', 3000),
            'warning_ms' => env('HEALTHCHECK_CACHE_WARNING', 500),
        ],
        'redis' => [
            'timeout_ms' => env('HEALTHCHECK_REDIS_TIMEOUT', 3000),
            'warning_ms' => env('HEALTHCHECK_REDIS_WARNING', 500),
        ],
    ],

    /*
    | Response caching configuration
    */
    'caching' => [
        'enabled' => env('HEALTHCHECK_CACHING_ENABLED', true),
        'basic_health_ttl' => env('HEALTHCHECK_BASIC_TTL', 30),  // seconds
    ],

    /*
    | Module metadata
    */
    'version' => '1.0.0',
    'author' => 'FetchIt Team',
    'description' => 'Health check monitoring and logging module',
];
