<?php

namespace Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Shared\Services\ResponseWrapperService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Response Wrapper Middleware
 *
 * Intercepts JSON responses and wraps them with a status field.
 * The status defaults to the HTTP status code but can be overridden.
 *
 * @package Modules\Shared\Http\Middleware
 * @version 1.0.0
 */
class ResponseWrapperMiddleware
{
    /**
     * Create a new middleware instance
     *
     * @param ResponseWrapperService $wrapperService
     */
    public function __construct(
        private readonly ResponseWrapperService $wrapperService
    ) {}

    /**
     * Handle an incoming request
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only wrap JSON responses
        if ($response instanceof JsonResponse) {
            return $this->wrapperService->wrap($response, $request);
        }

        return $response;
    }
}

