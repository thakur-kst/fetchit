<?php

namespace Modules\HealthCheck\Services\Checkers;

use Modules\HealthCheck\ValueObjects\CheckName;
use Modules\HealthCheck\ValueObjects\CheckResult;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Cache Health Checker
 *
 * Tests cache read/write operations with performance monitoring
 *
 * @package HealthCheck
 * @version 1.0.0
 */
class CacheChecker implements HealthCheckerInterface
{
    /**
     * Warning threshold for slow cache operations in milliseconds
     */
    private const WARNING_THRESHOLD_MS = 500;

    /**
     * Perform cache health check
     *
     * Tests cache write, read, and delete operations to ensure
     * the cache system is functioning correctly.
     *
     * @return CheckResult Health check result with cache driver info
     */
    public function check(): CheckResult
    {
        try {
            $startTime = microtime(true);
            $key = 'health_check_' . time();
            $value = 'test';

            // Test write, read, and delete operations
            Cache::put($key, $value, 10);
            $retrieved = Cache::get($key);
            Cache::forget($key);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($retrieved !== $value) {
                throw new Exception('Cache read/write test failed');
            }

            // Warn if cache operation is slow
            if ($responseTime > self::WARNING_THRESHOLD_MS) {
                return CheckResult::healthy(
                    CheckName::cache(),
                    'Cache is operational but slow',
                    [
                        'driver' => config('cache.default'),
                        'response_time_ms' => $responseTime,
                        'warning' => 'Response time exceeded ' . self::WARNING_THRESHOLD_MS . 'ms',
                    ],
                    $responseTime
                );
            }

            return CheckResult::healthy(
                CheckName::cache(),
                'Cache is operational',
                ['driver' => config('cache.default')],
                $responseTime
            );
        } catch (Exception $e) {
            return CheckResult::unhealthy(
                CheckName::cache(),
                'Cache check failed',
                $e->getMessage()
            );
        }
    }
}

