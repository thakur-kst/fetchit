<?php

namespace Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Shared\Services\RateLimitService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate Limit Middleware
 *
 * Centralized rate limiting middleware supporting:
 * - Per endpoint: wallet.credit: 10/min
 * - Per module: wallet: 100/min
 * - Per user role: admin: 1000/min, customer: 100/min
 *
 * @package Modules\Shared\Http\Middleware
 * @version 1.0.0
 */
class RateLimitMiddleware
{
    public function __construct(
        private RateLimitService $rateLimitService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string|null $endpoint Route name or endpoint identifier
     * @param string|null $module Module name
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?string $endpoint = null, ?string $module = null): Response
    {
        // If endpoint not provided, try to get from route name
        if (!$endpoint && $request->route()) {
            $endpoint = $request->route()->getName();
        }

        // If module not provided, try to infer from route prefix or controller namespace
        if (!$module && $request->route()) {
            // First, try to detect from controller namespace (more reliable)
            $action = $request->route()->getAction();
            if (isset($action['controller'])) {
                $controller = $action['controller'];
                // Extract module name from namespace pattern: Modules\{ModuleName}\...
                if (preg_match('/Modules\\\\([^\\\\]+)\\\\/', $controller, $matches)) {
                    $module = strtolower($matches[1]);
                }
            }
            
            // Fallback to URI pattern detection
            if (!$module) {
                $uri = $request->route()->uri();
                if (preg_match('/api\/v\d+\/(\w+)/', $uri, $matches)) {
                    $module = $matches[1];
                }
            }
        }

        if ($this->rateLimitService->tooManyAttempts($request, $endpoint, $module)) {
            $availableAt = $this->rateLimitService->availableAt($request, $endpoint, $module);

            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $availableAt,
            ], 429)->withHeaders([
                'Retry-After' => $availableAt,
                'X-RateLimit-Limit' => $this->getLimit($request, $endpoint, $module),
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        $response = $next($request);

        // Hit rate limit after successful request
        $this->rateLimitService->hit($request, $endpoint, $module);

        // Add rate limit headers
        $remaining = $this->rateLimitService->remaining($request, $endpoint, $module);
        $limit = $this->getLimit($request, $endpoint, $module);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => $remaining,
        ]);
    }

    /**
     * Get rate limit for request
     *
     * @param Request $request
     * @param string|null $endpoint
     * @param string|null $module
     * @return int
     */
    private function getLimit(Request $request, ?string $endpoint = null, ?string $module = null): int
    {
        $config = \Modules\Shared\Support\RateLimitConfig::resolve($request, $endpoint, $module);
        return $config['max_attempts'];
    }
}

