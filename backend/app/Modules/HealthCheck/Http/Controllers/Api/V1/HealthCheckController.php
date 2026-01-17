<?php

namespace Modules\HealthCheck\Http\Controllers\Api\V1;

use Modules\HealthCheck\Services\HealthCheckApplicationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Health Check Controller - Version 1 (Presentation Layer)
 *
 * Handles HTTP requests for health checks
 * Delegates business logic to Application Service
 *
 * @version 1.0
 * @package HealthCheck
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
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $healthDTO = $this->healthCheckService->getBasicHealth();

        return response()->json($healthDTO->toArray());
    }

    /**
     * Comprehensive health check with service dependencies
     *
     * @return JsonResponse
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
     * @return JsonResponse
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
     * @return JsonResponse
     */
    public function liveness(): JsonResponse
    {
        $livenessDTO = $this->healthCheckService->getLiveness();

        return response()->json($livenessDTO->toArray());
    }
}

