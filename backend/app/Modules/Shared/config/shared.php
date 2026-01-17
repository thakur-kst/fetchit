<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Shared Module Configuration
    |--------------------------------------------------------------------------
    |
    | Centralized configuration for cross-cutting concerns:
    | - Rate limiting
    | - IP whitelisting
    | - Caching
    | - Audit logging
    |
    */

    /**
     * Rate Limiting Configuration
     */
    'rate_limits' => [
        /**
         * Default rate limit (applies if no specific limit is found)
         */
        'default' => [
            'max_attempts' => env('SHARED_RATE_LIMIT_DEFAULT_MAX', 120),
            'decay_minutes' => env('SHARED_RATE_LIMIT_DEFAULT_DECAY', 1),
        ],

        /**
         * Module-specific rate limits
         * Format: 'module_name' => ['max_attempts' => X, 'decay_minutes' => Y]
         */
        'modules' => [
            'shared' => [
                'max_attempts' => env('SHARED_RATE_LIMIT_WALLET_MAX', 50),
                'decay_minutes' => env('SHARED_RATE_LIMIT_WALLET_DECAY', 1),
            ],
            'payment' => [
                'max_attempts' => env('SHARED_RATE_LIMIT_PAYMENT_MAX', 100),
                'decay_minutes' => env('SHARED_RATE_LIMIT_PAYMENT_DECAY', 1),
            ],
        ],

        /**
         * Endpoint-specific rate limits
         * Format: 'route_name' => ['max_attempts' => X, 'decay_minutes' => Y]
         */
        'endpoints' => [
            'payment.initiate' => [
                'max_attempts' => env('SHARED_RATE_LIMIT_WALLET_TRANSFER_MAX', 20),
                'decay_minutes' => env('SHARED_RATE_LIMIT_WALLET_TRANSFER_DECAY', 1),
            ],
            // HealthCheck endpoints (route names include v1. prefix)
            'v1.health.basic' => [
                'max_attempts' => env('SHARED_RATE_LIMIT_HEALTH_BASIC_MAX', 10),
                'decay_minutes' => env('SHARED_RATE_LIMIT_HEALTH_BASIC_DECAY', 1),
            ],
            'v1.health.detailed' => [
                'max_attempts' => env('SHARED_RATE_LIMIT_HEALTH_DETAILED_MAX', 10),
                'decay_minutes' => env('SHARED_RATE_LIMIT_HEALTH_DETAILED_DECAY', 1),
            ],
            'v1.health.readiness' => [
                'max_attempts' => env('SHARED_RATE_LIMIT_HEALTH_READINESS_MAX', 30),
                'decay_minutes' => env('SHARED_RATE_LIMIT_HEALTH_READINESS_DECAY', 1),
            ],
            'v1.health.liveness' => [
                'max_attempts' => env('SHARED_RATE_LIMIT_HEALTH_LIVENESS_MAX', 30),
                'decay_minutes' => env('SHARED_RATE_LIMIT_HEALTH_LIVENESS_DECAY', 1),
            ],
        ],

        /**
         * Role-based rate limits
         * Format: 'role_slug' => ['max_attempts' => X, 'decay_minutes' => Y]
         */
        'roles' => [
            'admin' => [
                'max_attempts' => env('SHARED_RATE_LIMIT_ADMIN_MAX', 1000),
                'decay_minutes' => env('SHARED_RATE_LIMIT_ADMIN_DECAY', 1),
            ],
            'customer' => [
                'max_attempts' => env('SHARED_RATE_LIMIT_CUSTOMER_MAX', 100),
                'decay_minutes' => env('SHARED_RATE_LIMIT_CUSTOMER_DECAY', 1),
            ],
        ],
    ],

    /**
     * IP Whitelist Configuration
     */
    'ip_whitelist' => [
        /**
         * Enable IP whitelisting globally
         */
        'enabled' => env('SHARED_IP_WHITELIST_ENABLED', false),

        /**
         * Global whitelisted IPs
         */
        'ips' => env('SHARED_IP_WHITELIST')
            ? explode(',', env('SHARED_IP_WHITELIST'))
            : [],

        /**
         * Module-specific IP whitelisting
         * Format: 'module_name' => ['ips' => [...], 'routes' => [...]]
         */
        'by_module' => [
            'wallet' => [
                'ips' => env('SHARED_IP_WHITELIST_WALLET')
                    ? explode(',', env('SHARED_IP_WHITELIST_WALLET'))
                    : [],
                'routes' => ['wallet.credit', 'wallet.debit', 'wallet.transfer'],
            ],
        ],
    ],

    /**
     * Caching Configuration
     */
    'caching' => [
        /**
         * Enable caching globally
         */
        'enabled' => env('SHARED_CACHE_ENABLED', true),

        /**
         * Default cache TTL in seconds
         */
        'default_ttl' => env('SHARED_CACHE_TTL', 300), // 5 minutes

        /**
         * Cache prefix
         */
        'prefix' => env('SHARED_CACHE_PREFIX', 'shared'),
    ],

    /**
     * Audit Logging Configuration
     */
    'audit' => [
        /**
         * Enable audit logging globally
         */
        'enabled' => env('SHARED_AUDIT_ENABLED', false),

        /**
         * Log read operations (GET requests)
         */
        'log_reads' => env('SHARED_AUDIT_LOG_READS', false),

        /**
         * Modules that should be audited (empty = all modules)
         */
        'modules' => env('SHARED_AUDIT_MODULES')
            ? explode(',', env('SHARED_AUDIT_MODULES'))
            : [],
    ],

    /**
     * Queue Priority Configuration
     */
    'queues' => [
        /**
         * Priority queue names
         * These can be customized via environment variables if needed
         */
        'priorities' => [
            'critical' => env('QUEUE_PRIORITY_CRITICAL', 'critical'),
            'high' => env('QUEUE_PRIORITY_HIGH', 'high'),
            'medium' => env('QUEUE_PRIORITY_MEDIUM', 'medium'),
            'default' => env('QUEUE_PRIORITY_DEFAULT', 'default'),
            'low' => env('QUEUE_PRIORITY_LOW', 'low'),
        ],

        /**
         * Default priority for jobs that don't specify one
         */
        'default_priority' => env('QUEUE_DEFAULT_PRIORITY', 'default'),
    ],

    /**
     * ETag Configuration
     */
    'etags' => [
        /**
         * Enable ETags globally
         */
        'enabled' => env('SHARED_ETAG_ENABLED', true),

        /**
         * Use weak ETags (W/"...") instead of strong ETags
         * Weak ETags are useful for collections and aggregated data
         */
        'weak_etags' => env('SHARED_ETAG_WEAK', false),

        /**
         * ETag generation strategy
         * - 'model': Generate from model timestamps (default)
         * - 'content': Generate from response content hash
         * - 'hybrid': Use model for resources, content for collections
         */
        'strategy' => env('SHARED_ETAG_STRATEGY', 'model'),

        /**
         * Routes that should be excluded from ETag processing
         * Can be route names or URI patterns
         */
        'excluded_routes' => env('SHARED_ETAG_EXCLUDED_ROUTES')
            ? explode(',', env('SHARED_ETAG_EXCLUDED_ROUTES'))
            : [],
    ],

    /**
     * Response Wrapper Configuration
     */
    'response_wrapper' => [
        /**
         * Enable or disable response wrapping globally
         */
        'enabled' => env('RESPONSE_WRAPPER_ENABLED', true),

        /**
         * The field name to use for the status in the response
         */
        'status_field' => env('RESPONSE_WRAPPER_STATUS_FIELD', 'status'),

        /**
         * Default source for status value
         * Options: 'http_status' (use HTTP status code) or 'custom'
         */
        'default_source' => env('RESPONSE_WRAPPER_DEFAULT_SOURCE', 'http_status'),

        /**
         * Allow overriding existing status field in response
         * If false, existing status field will be preserved
         */
        'allow_override' => env('RESPONSE_WRAPPER_ALLOW_OVERRIDE', false),
    ],
];
