<?php

namespace Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Shared\Services\IPWhitelistService;
use Symfony\Component\HttpFoundation\Response;

/**
 * IP Whitelist Middleware
 *
 * Centralized IP whitelist middleware.
 * Restricts access to whitelisted IPs only.
 *
 * @package Modules\Shared\Http\Middleware
 * @version 1.0.0
 */
class IPWhitelistMiddleware
{
    public function __construct(
        private IPWhitelistService $whitelistService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string|null $module Module name
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?string $module = null): Response
    {
        $ip = $request->ip();

        // If module not provided, try to infer from route prefix
        if (!$module && $request->route()) {
            $uri = $request->route()->uri();
            if (preg_match('/api\/v\d+\/(\w+)/', $uri, $matches)) {
                $module = $matches[1];
            }
        }

        // Check if route requires whitelist
        $routeName = $request->route()?->getName();
        if ($routeName && $module && $this->whitelistService->routeRequiresWhitelist($routeName, $module)) {
            if (!$this->whitelistService->isWhitelisted($ip, $module)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. IP address not whitelisted.',
                ], 403);
            }
        } elseif (!$this->whitelistService->isWhitelisted($ip, $module)) {
            // Global whitelist check
            return response()->json([
                'success' => false,
                'message' => 'Access denied. IP address not whitelisted.',
            ], 403);
        }

        return $next($request);
    }
}

