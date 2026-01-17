<?php

namespace Modules\HealthCheck\Services\Checkers;

use Modules\HealthCheck\ValueObjects\CheckName;
use Modules\HealthCheck\ValueObjects\CheckResult;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Database Health Checker
 *
 * Checks database connectivity and performance with timeout protection
 *
 * @package HealthCheck
 * @version 1.0.0
 */
class DatabaseChecker implements HealthCheckerInterface
{
    /**
     * Timeout for database health check in seconds
     */
    private const TIMEOUT_SECONDS = 5;

    /**
     * Warning threshold for slow queries in milliseconds
     */
    private const WARNING_THRESHOLD_MS = 1000;

    /**
     * Perform database health check
     *
     * Tests database connectivity and query performance with timeout protection.
     * Returns a warning status if the check takes longer than the warning threshold.
     *
     * @return CheckResult Health check result with timing information
     */
    public function check(): CheckResult
    {
        try {
            $startTime = microtime(true);

            // Set statement timeout (PostgreSQL)
            DB::statement("SET statement_timeout = " . (self::TIMEOUT_SECONDS * 1000));

            // Test database connection
            DB::connection()->getPdo();

            // Run a simple query
            DB::select('SELECT 1 as health_check');

            // Reset timeout to default
            DB::statement("SET statement_timeout = 0");

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            // Warn if check is slow
            if ($responseTime > self::WARNING_THRESHOLD_MS) {
                return CheckResult::healthy(
                    CheckName::database(),
                    'Database connection successful but slow',
                    [
                        'connection' => config('database.default'),
                        'driver' => config('database.connections.' . config('database.default') . '.driver'),
                        'response_time_ms' => $responseTime,
                        'warning' => 'Response time exceeded ' . self::WARNING_THRESHOLD_MS . 'ms',
                    ],
                    $responseTime
                );
            }

            return CheckResult::healthy(
                CheckName::database(),
                'Database connection successful',
                [
                    'connection' => config('database.default'),
                    'driver' => config('database.connections.' . config('database.default') . '.driver'),
                ],
                $responseTime
            );
        } catch (Exception $e) {
            // Reset timeout in case of error
            try {
                DB::statement("SET statement_timeout = 0");
            } catch (Exception $resetException) {
                // Ignore reset errors
            }

            return CheckResult::unhealthy(
                CheckName::database(),
                'Database connection failed',
                $e->getMessage()
            );
        }
    }
}

