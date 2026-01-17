<?php

namespace Modules\Shared\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Response Wrapper Service
 *
 * Handles wrapping JSON responses with a status field.
 * Defaults to HTTP status code, but can be overridden per module or route.
 *
 * @package Modules\Shared\Services
 * @version 1.0.0
 */
class ResponseWrapperService
{
    /**
     * Wrap response with status field
     *
     * @param JsonResponse $response
     * @param Request $request
     * @return JsonResponse
     */
    public function wrap(JsonResponse $response, Request $request): JsonResponse
    {
        // Check if wrapping is enabled
        if (!config('shared.response_wrapper.enabled', true)) {
            return $response;
        }

        // Get the status field name
        $statusField = config('shared.response_wrapper.status_field', 'status');

        // Get current response data
        $data = $response->getData(true);

        // If status field already exists, don't override unless explicitly configured
        if (isset($data[$statusField]) && !config('shared.response_wrapper.allow_override', false)) {
            return $response;
        }

        // Resolve the status value
        $status = $this->resolveStatus($response, $request);

        // Add status field to response data
        $data[$statusField] = $status;

        // Create new response with wrapped data
        return response()->json($data, $response->getStatusCode(), $response->headers->all());
    }

    /**
     * Resolve the status value for the response
     *
     * Priority:
     * 1. Controller override (via trait)
     * 2. Route-specific config
     * 3. Module-level config
     * 4. HTTP status code (default)
     *
     * @param JsonResponse $response
     * @param Request $request
     * @return int
     */
    protected function resolveStatus(JsonResponse $response, Request $request): int
    {
        // Check for controller override (stored in request attributes)
        $controllerStatus = $request->attributes->get('response_status_override');
        if ($controllerStatus !== null) {
            return (int) $controllerStatus;
        }

        // Get current route
        $route = $request->route();
        if (!$route) {
            return $response->getStatusCode();
        }

        $routeName = $route->getName();
        $moduleName = $this->getModuleName($route);

        // Check route-specific override
        if ($routeName && $moduleName) {
            $routeConfig = config("{$moduleName}.response_wrapper.routes.{$routeName}");
            if (isset($routeConfig['status'])) {
                return (int) $routeConfig['status'];
            }
        }

        // Check module-level override
        if ($moduleName) {
            $moduleStatus = config("{$moduleName}.response_wrapper.status");
            if ($moduleStatus !== null) {
                return (int) $moduleStatus;
            }
        }

        // Default to HTTP status code
        return $response->getStatusCode();
    }

    /**
     * Get module name from route
     *
     * @param \Illuminate\Routing\Route|null $route
     * @return string|null
     */
    protected function getModuleName($route): ?string
    {
        if (!$route) {
            return null;
        }

        // Get controller class
        $action = $route->getAction();
        $controller = $action['controller'] ?? null;

        if (!$controller || !is_string($controller)) {
            return null;
        }

        // Extract module name from controller namespace
        // Format: Modules\{ModuleName}\Http\Controllers\...
        if (preg_match('/Modules\\\\([^\\\\]+)\\\\Http\\\\Controllers/', $controller, $matches)) {
            $moduleName = strtolower($matches[1]);
            return $moduleName;
        }

        return null;
    }
}

