<?php

namespace Modules\HealthCheck\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * HealthCheck Logging Middleware
 *
 * Logs health check requests and responses for monitoring and debugging.
 * Tracks request metadata, response status, and execution time.
 *
 * @package HealthCheck
 */
class HealthCheckLoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Logs health check requests and responses with error handling.
     * If logging fails, the middleware continues to process the request
     * to ensure health checks remain functional even if logging is broken.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $startTime = microtime(true);

            // Log incoming health check request
            $this->logRequest($request);

            // Process the request
            $response = $next($request);

            // Calculate execution time
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            // Log health check response
            $this->logResponse($request, $response, $executionTime);

            return $response;

        } catch (\Throwable $e) {
            // Log the exception but don't prevent health checks from working
            try {
                Log::error('HealthCheck Middleware Exception', [
                    'endpoint' => $request->path(),
                    'exception' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            } catch (\Throwable $logException) {
                // If logging fails, silently continue
            }

            // Re-throw the exception to let Laravel handle it
            throw $e;
        }
    }

    /**
     * Log the incoming health check request
     */
    private function logRequest(Request $request): void
    {
        Log::info('HealthCheck Request', [
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log the health check response
     */
    private function logResponse(Request $request, Response $response, float $executionTime): void
    {
        $statusCode = $response->getStatusCode();
        $logLevel = $this->getLogLevel($statusCode);

        // Parse response content
        $content = $this->parseResponseContent($response);

        Log::log($logLevel, 'HealthCheck Response', [
            'endpoint' => $request->path(),
            'status_code' => $statusCode,
            'status_text' => Response::$statusTexts[$statusCode] ?? 'Unknown',
            'execution_time_ms' => $executionTime,
            'health_status' => $content['status'] ?? $content['alive'] ?? $content['ready'] ?? 'unknown',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Parse response content to extract health status
     */
    private function parseResponseContent(Response $response): array
    {
        try {
            $content = $response->getContent();
            if ($content && is_string($content)) {
                return json_decode($content, true) ?? [];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to parse health check response content', [
                'error' => $e->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * Determine log level based on HTTP status code
     */
    private function getLogLevel(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 500 => 'error',
            $statusCode >= 400 => 'warning',
            $statusCode >= 300 => 'info',
            default => 'info',
        };
    }
}

