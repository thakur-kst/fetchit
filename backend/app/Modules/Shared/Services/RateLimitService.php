<?php

namespace Modules\Shared\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Shared\Support\RateLimitConfig;

/**
 * Rate Limit Service
 *
 * Core rate limiting logic supporting multiple strategies.
 * Uses Laravel's RateLimiter facade.
 *
 * @package Modules\Shared\Services
 * @version 1.0.0
 */
class RateLimitService
{
    /**
     * Check if request exceeds rate limit
     *
     * @param Request $request
     * @param string|null $endpoint
     * @param string|null $module
     * @return bool
     */
    public function tooManyAttempts(Request $request, ?string $endpoint = null, ?string $module = null): bool
    {
        $config = RateLimitConfig::resolve($request, $endpoint, $module);
        $key = RateLimitConfig::getKey($request, $endpoint, $module);

        return RateLimiter::tooManyAttempts($key, $config['max_attempts']);
    }

    /**
     * Hit rate limit (increment attempt counter)
     *
     * @param Request $request
     * @param string|null $endpoint
     * @param string|null $module
     * @return int
     */
    public function hit(Request $request, ?string $endpoint = null, ?string $module = null): int
    {
        $config = RateLimitConfig::resolve($request, $endpoint, $module);
        $key = RateLimitConfig::getKey($request, $endpoint, $module);

        return RateLimiter::hit($key, $config['decay_minutes'] * 60);
    }

    /**
     * Get remaining attempts
     *
     * @param Request $request
     * @param string|null $endpoint
     * @param string|null $module
     * @return int
     */
    public function remaining(Request $request, ?string $endpoint = null, ?string $module = null): int
    {
        $config = RateLimitConfig::resolve($request, $endpoint, $module);
        $key = RateLimitConfig::getKey($request, $endpoint, $module);

        return RateLimiter::remaining($key, $config['max_attempts']);
    }

    /**
     * Get available at timestamp (when rate limit resets)
     *
     * @param Request $request
     * @param string|null $endpoint
     * @param string|null $module
     * @return int
     */
    public function availableAt(Request $request, ?string $endpoint = null, ?string $module = null): int
    {
        $key = RateLimitConfig::getKey($request, $endpoint, $module);

        return RateLimiter::availableIn($key);
    }

    /**
     * Clear rate limit for a request
     *
     * @param Request $request
     * @param string|null $endpoint
     * @param string|null $module
     * @return void
     */
    public function clear(Request $request, ?string $endpoint = null, ?string $module = null): void
    {
        $key = RateLimitConfig::getKey($request, $endpoint, $module);
        RateLimiter::clear($key);
    }
}

