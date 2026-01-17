<?php

namespace Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Shared\Services\AuditService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Audit Log Middleware
 *
 * Automatic audit logging for routes (opt-in).
 * Can be applied per route or route group.
 *
 * @package Modules\Shared\Http\Middleware
 * @version 1.0.0
 */
class AuditLogMiddleware
{
    public function __construct(
        private AuditService $auditService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string|null $module Module name
     * @param string|null $entityType Entity type
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?string $module = null, ?string $entityType = null): Response
    {
        $response = $next($request);

        // Only log if audit is enabled
        if (!config('shared.audit.enabled', false)) {
            return $response;
        }

        // If module not provided, try to infer from route prefix
        if (!$module && $request->route()) {
            $uri = $request->route()->uri();
            if (preg_match('/api\/v\d+\/(\w+)/', $uri, $matches)) {
                $module = $matches[1];
            }
        }

        // Determine action from HTTP method
        $action = $this->getActionFromMethod($request->method());

        // Extract entity ID from route parameters
        $entityId = $this->extractEntityId($request);

        // Log the request
        if ($module && $entityType && $entityId) {
            $this->auditService->log(
                $module,
                $entityType,
                $entityId,
                $action,
                [],
                $this->extractNewValues($request, $response),
                $request
            );
        }

        return $response;
    }

    /**
     * Get action from HTTP method
     *
     * @param string $method
     * @return string
     */
    private function getActionFromMethod(string $method): string
    {
        return match ($method) {
            'GET' => 'viewed',
            'POST' => 'created',
            'PUT', 'PATCH' => 'updated',
            'DELETE' => 'deleted',
            default => 'accessed',
        };
    }

    /**
     * Extract entity ID from route parameters
     *
     * @param Request $request
     * @return string|int|null
     */
    private function extractEntityId(Request $request): string|int|null
    {
        $route = $request->route();
        if (!$route) {
            return null;
        }

        // Try common parameter names
        $params = $route->parameters();
        foreach (['id', 'uuid', 'entityId', 'entityUuid'] as $key) {
            if (isset($params[$key])) {
                return $params[$key];
            }
        }

        // Return first parameter if exists
        return !empty($params) ? reset($params) : null;
    }

    /**
     * Extract new values from request/response
     *
     * @param Request $request
     * @param Response $response
     * @return array
     */
    private function extractNewValues(Request $request, Response $response): array
    {
        // For POST/PUT/PATCH, get request data
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            return $request->except(['password', 'password_confirmation', '_token']);
        }

        // For GET, return empty (or response data if needed)
        return [];
    }
}

