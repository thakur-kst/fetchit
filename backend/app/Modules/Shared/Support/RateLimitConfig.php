<?php

namespace Modules\Shared\Support;

use Illuminate\Http\Request;

/**
 * Rate Limit Config
 *
 * Helper class to resolve rate limit configuration.
 * Priority: endpoint > module > role > default
 *
 * @package Modules\Shared\Support
 * @version 1.0.0
 */
class RateLimitConfig
{
    /**
     * Resolve rate limit configuration for a request
     *
     * @param Request $request
     * @param string|null $endpoint
     * @param string|null $module
     * @return array{max_attempts: int, decay_minutes: int}
     */
    public static function resolve(Request $request, ?string $endpoint = null, ?string $module = null): array
    {
        $config = config('shared.rate_limits', []);
        $default = $config['default'] ?? ['max_attempts' => 60, 'decay_minutes' => 1];

        // Priority 1: Endpoint-specific configuration
        if ($endpoint && isset($config['endpoints'][$endpoint])) {
            return $config['endpoints'][$endpoint];
        }

        // Priority 2: Module-specific configuration
        if ($module && isset($config['modules'][$module])) {
            return $config['modules'][$module];
        }

        // Priority 3: Role-based configuration
        // Try to get user, but handle authentication exceptions gracefully
        // (e.g., invalid Bearer tokens should not break rate limiting)
        try {
            $user = $request->user();
            if ($user && isset($user->roles)) {
                $userRoles = $user->roles->pluck('slug')->toArray();
                foreach ($userRoles as $role) {
                    if (isset($config['roles'][$role])) {
                        return $config['roles'][$role];
                    }
                }
            }
        } catch (\Throwable $e) {
            // If authentication fails (e.g., invalid token), treat as unauthenticated
            // Continue to default configuration
        }

        // Priority 4: Default configuration
        return $default;
    }

    /**
     * Get rate limit key for a request
     *
     * @param Request $request
     * @param string|null $endpoint
     * @param string|null $module
     * @return string
     */
    public static function getKey(Request $request, ?string $endpoint = null, ?string $module = null): string
    {
        // Try to get user, but handle authentication exceptions gracefully
        // (e.g., invalid Bearer tokens should not break rate limiting)
        $identifier = $request->ip();
        try {
            $user = $request->user();
            if ($user) {
                $identifier = $user->id;
            }
        } catch (\Throwable $e) {
            // If authentication fails (e.g., invalid token), use IP address
            // This is already set as default above
        }

        if ($endpoint) {
            return "rate_limit:endpoint:{$endpoint}:{$identifier}";
        }

        if ($module) {
            return "rate_limit:module:{$module}:{$identifier}";
        }

        return "rate_limit:default:{$identifier}";
    }
}

