<?php

namespace Modules\HealthCheck\Services;

use Modules\HealthCheck\DTOs\BasicHealthDTO;
use Modules\HealthCheck\DTOs\HealthStatusDTO;
use Modules\HealthCheck\DTOs\LivenessDTO;
use Modules\HealthCheck\DTOs\ReadinessDTO;
use Modules\HealthCheck\ValueObjects\Timestamp;
use Modules\HealthCheck\Services\Checkers\ApplicationChecker;
use Modules\HealthCheck\Services\Checkers\CacheChecker;
use Modules\HealthCheck\Services\Checkers\DatabaseChecker;
use Modules\HealthCheck\Services\Checkers\RedisChecker;
use Illuminate\Support\Facades\Cache;

/**
 * Health Check Application Service
 *
 * Orchestrates health checks across multiple system dependencies
 * and returns structured DTOs for API responses.
 *
 * This service coordinates health checkers for various system components
 * (database, cache, Redis, application) and provides different health check
 * endpoints for monitoring and orchestration purposes.
 *
 * @package HealthCheck
 * @version 1.0.0
 */
class HealthCheckApplicationService
{
    private ApplicationChecker $applicationChecker;
    private DatabaseChecker $databaseChecker;
    private CacheChecker $cacheChecker;
    private RedisChecker $redisChecker;

    public function __construct(
        ApplicationChecker $applicationChecker,
        DatabaseChecker $databaseChecker,
        CacheChecker $cacheChecker,
        RedisChecker $redisChecker
    ) {
        $this->applicationChecker = $applicationChecker;
        $this->databaseChecker = $databaseChecker;
        $this->cacheChecker = $cacheChecker;
        $this->redisChecker = $redisChecker;
    }

    /**
     * Get basic health status
     *
     * Returns a simple health status indicating the application is running.
     * This endpoint is cached for 30 seconds to reduce load from high-frequency
     * monitoring systems.
     *
     * @return BasicHealthDTO Simple health status with timestamp
     */
    public function getBasicHealth(): BasicHealthDTO
    {
        $cacheTTL = config('healthcheck.caching.basic_health_ttl', 30);

        return Cache::remember('healthcheck:basic', $cacheTTL, function () {
            $timestamp = Timestamp::now();

            return new BasicHealthDTO(
                'healthy',
                $timestamp->toIso8601String(),
                config('app.name'),
                config('app.env')
            );
        });
    }

    /**
     * Get detailed health status with all checker results
     *
     * Runs all configured health checkers (application, database, cache, Redis)
     * and aggregates their results into a comprehensive health status report.
     * Returns HTTP 503 if any checker reports an unhealthy status.
     *
     * @return HealthStatusDTO Complete health status with all check results
     */
    public function getDetailedHealth(): HealthStatusDTO
    {
        $timestamp = Timestamp::now();
        $service = config('app.name');
        $environment = config('app.env');

        // Run all checks
        $checks = [
            $this->applicationChecker->check(),
            $this->databaseChecker->check(),
            $this->cacheChecker->check(),
            $this->redisChecker->check(),
        ];

        // Build checks array
        $checksArray = [];
        $isHealthy = true;
        foreach ($checks as $check) {
            $checksArray[$check->getName()->value()] = $check->toArray();
            if (!$check->isHealthy()) {
                $isHealthy = false;
            }
        }

        return new HealthStatusDTO(
            $isHealthy ? 'healthy' : 'unhealthy',
            $timestamp->toIso8601String(),
            $service,
            $environment,
            $checksArray
        );
    }

    /**
     * Get readiness status (for Kubernetes readiness probes)
     *
     * Checks only critical dependencies (database and cache) to determine
     * if the application is ready to receive traffic. This is typically used
     * by orchestration systems to know when to route traffic to the application.
     *
     * @return ReadinessDTO Readiness status with critical dependency checks
     */
    public function getReadiness(): ReadinessDTO
    {
        $timestamp = Timestamp::now();

        // Check only critical components for readiness
        $databaseCheck = $this->databaseChecker->check();
        $cacheCheck = $this->cacheChecker->check();

        $isReady = $databaseCheck->isHealthy() && $cacheCheck->isHealthy();

        $checks = [
            $databaseCheck->getName()->value() => $databaseCheck->toArray(),
            $cacheCheck->getName()->value() => $cacheCheck->toArray(),
        ];

        return new ReadinessDTO(
            $isReady,
            $timestamp->toIso8601String(),
            $checks
        );
    }

    /**
     * Get liveness status (for Kubernetes liveness probes)
     *
     * Checks only the application itself to determine if the process is alive
     * and functioning. This is typically used by orchestration systems to know
     * when to restart the application container.
     *
     * @return LivenessDTO Liveness status with application check
     */
    public function getLiveness(): LivenessDTO
    {
        $timestamp = Timestamp::now();

        // Check only application for liveness
        $applicationCheck = $this->applicationChecker->check();
        $isAlive = $applicationCheck->isHealthy();

        return new LivenessDTO(
            $isAlive,
            $timestamp->toIso8601String()
        );
    }

    /**
     * Get HTTP status code for detailed health check
     *
     * Returns 200 if all checks are healthy, 503 if any check is unhealthy.
     *
     * @param HealthStatusDTO $healthStatus The health status DTO
     * @return int HTTP status code (200 or 503)
     */
    public function getStatusCode(HealthStatusDTO $healthStatus): int
    {
        return $healthStatus->status === 'healthy' ? 200 : 503;
    }

    /**
     * Get HTTP status code for readiness check
     *
     * Returns 200 if ready to receive traffic, 503 if not ready.
     *
     * @param ReadinessDTO $readiness The readiness DTO
     * @return int HTTP status code (200 or 503)
     */
    public function getReadinessStatusCode(ReadinessDTO $readiness): int
    {
        return $readiness->ready ? 200 : 503;
    }
}

