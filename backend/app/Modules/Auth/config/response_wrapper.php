<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auth Module Response Wrapper Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration allows you to override the default status field
    | for Auth module responses.
    |
    | Priority:
    | 1. Controller override (via setResponseStatus() method)
    | 2. Route-specific override (defined in 'routes' array)
    | 3. Module-level override (defined in 'status' key)
    | 4. HTTP status code (default)
    |
    */

    /**
     * Module-level status override
     * Set to null to use HTTP status code as default
     */
    'status' => null,

    /**
     * Route-specific status overrides
     * Key: route name (e.g., 'auth.login')
     * Value: array with 'status' key
     */
    'routes' => [
        // Example:
        // 'auth.login' => ['status' => 200],
        // 'auth.logout' => ['status' => 200],
    ],
];

