<?php

namespace Modules\HealthCheck\Services\Checkers;

use Modules\HealthCheck\ValueObjects\CheckName;
use Modules\HealthCheck\ValueObjects\CheckResult;
use Illuminate\Support\Facades\Redis;
use Exception;

/**
 * Redis Health Checker
 *
 * Tests Redis connectivity and performance with timeout monitoring
 *
 * @package HealthCheck
 * @version 1.0.0
 */
class RedisChecker implements HealthCheckerInterface
{
    /**
     * Warning threshold for slow Redis operations in milliseconds
     */
    private const WARNING_THRESHOLD_MS = 500;

    /**
     * Perform Redis health check
     *
     * Tests Redis connectivity using the PING command and monitors
     * response time for performance degradation.
     *
     * @return CheckResult Health check result with Redis connection info
     */
    public function check(): CheckResult
    {
        try {
            $startTime = microtime(true);
            $response = Redis::ping();
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Warn if Redis response is slow
            if ($responseTime > self::WARNING_THRESHOLD_MS) {
                return CheckResult::healthy(
                    CheckName::redis(),
                    'Redis connection successful but slow',
                    [
                        'host' => config('database.redis.default.host'),
                        'port' => config('database.redis.default.port'),
                        'response_time_ms' => $responseTime,
                        'warning' => 'Response time exceeded ' . self::WARNING_THRESHOLD_MS . 'ms',
                    ],
                    $responseTime
                );
            }

            return CheckResult::healthy(
                CheckName::redis(),
                'Redis connection successful',
                [
                    'host' => config('database.redis.default.host'),
                    'port' => config('database.redis.default.port'),
                ],
                $responseTime
            );
        } catch (Exception $e) {
            return CheckResult::unhealthy(
                CheckName::redis(),
                'Redis connection failed',
                $e->getMessage()
            );
        }
    }
}

