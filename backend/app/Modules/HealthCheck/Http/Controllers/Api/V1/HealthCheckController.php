<?php

namespace Modules\HealthCheck\Http\Controllers\Api\V1;

use Modules\HealthCheck\Services\HealthCheckApplicationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Health Check Controller - Version 1 (Presentation Layer)
 *
 * Handles HTTP requests for health checks. Delegates business logic to Application Service.
 *
 * @tags Health
 */
class HealthCheckController extends Controller
{
    private HealthCheckApplicationService $healthCheckService;

    public function __construct(HealthCheckApplicationService $healthCheckService)
    {
        $this->healthCheckService = $healthCheckService;
    }

    /**
     * Basic health check endpoint
     *
     * Returns status, timestamp, service name, and environment. No dependency checks.
     *
     * @operationId healthBasic
     * @tags Health
     * @response 200 {"status": "ok", "timestamp": "2024-01-15T12:00:00Z", "service": "FetchIt", "environment": "local"}
     */
    public function index(): JsonResponse
    {
        $healthDTO = $this->healthCheckService->getBasicHealth();

        return response()->json($healthDTO->toArray());
    }

    /**
     * Comprehensive health check with service dependencies
     *
     * Returns status, timestamp, service, environment, and checks (database, redis, etc.). HTTP status may be 503 if unhealthy.
     *
     * @operationId healthDetailed
     * @tags Health
     * @response 200 {"status": "healthy", "timestamp": "2024-01-15T12:00:00Z", "service": "FetchIt", "environment": "local", "checks": {"database": {"status": "ok", "message": "Connection successful"}, "redis": {"status": "ok", "message": "Connection successful"}}}
     * @response 503 {"status": "unhealthy", "timestamp": "2024-01-15T12:00:00Z", "service": "FetchIt", "environment": "local", "checks": {"database": {"status": "error", "error": "Connection timeout"}}}
     */
    public function detailed(): JsonResponse
    {
        $healthStatusDTO = $this->healthCheckService->getDetailedHealth();
        $statusCode = $this->healthCheckService->getStatusCode($healthStatusDTO);

        return response()->json($healthStatusDTO->toArray(), $statusCode);
    }

    /**
     * Readiness check - determine if app is ready to receive traffic
     *
     * Used by load balancers and orchestrators. Returns 200 if ready, 503 otherwise.
     *
     * @operationId healthReadiness
     * @tags Health
     * @response 200 {"ready": true, "timestamp": "2024-01-15T12:00:00Z", "checks": {"database": {"status": "ok"}, "redis": {"status": "ok"}}}
     * @response 503 {"ready": false, "timestamp": "2024-01-15T12:00:00Z", "checks": {"database": {"status": "error", "error": "Connection failed"}}}
     */
    public function readiness(): JsonResponse
    {
        $readinessDTO = $this->healthCheckService->getReadiness();
        $statusCode = $this->healthCheckService->getReadinessStatusCode($readinessDTO);

        return response()->json($readinessDTO->toArray(), $statusCode);
    }

    /**
     * Liveness check - determine if app process is alive
     *
     * Simple alive/dead check for Kubernetes and similar. Always 200 when the process responds.
     *
     * @operationId healthLiveness
     * @tags Health
     * @response 200 {"alive": true, "timestamp": "2024-01-15T12:00:00Z"}
     */
    public function liveness(): JsonResponse
    {
        $livenessDTO = $this->healthCheckService->getLiveness();

        return response()->json($livenessDTO->toArray());
    }
}

