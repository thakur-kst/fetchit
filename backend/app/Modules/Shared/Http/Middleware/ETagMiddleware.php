<?php

namespace Modules\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Modules\Shared\Services\ETagService;

/**
 * ETag Middleware
 *
 * Handles ETag validation for conditional requests:
 * - If-None-Match: Returns 304 Not Modified if ETag matches (GET requests)
 * - If-Match: Returns 412 Precondition Failed if ETag doesn't match (PUT/PATCH/DELETE)
 *
 * @package Modules\Shared\Http\Middleware
 * @version 1.0.0
 */
class ETagMiddleware
{
    /**
     * HTTP Methods
     */
    private const METHOD_GET = 'GET';
    private const METHOD_HEAD = 'HEAD';
    private const METHOD_PUT = 'PUT';
    private const METHOD_PATCH = 'PATCH';
    private const METHOD_DELETE = 'DELETE';

    /**
     * HTTP Status Codes
     */
    private const STATUS_NOT_MODIFIED = 304;
    private const STATUS_PRECONDITION_FAILED = 412;

    /**
     * HTTP Headers
     */
    private const HEADER_IF_NONE_MATCH = 'If-None-Match';
    private const HEADER_IF_MATCH = 'If-Match';
    private const HEADER_ETAG = 'ETag';
    private const HEADER_CONTENT_TYPE = 'Content-Type';
    private const HEADER_CONTENT_LENGTH = 'Content-Length';

    /**
     * Methods that use If-Match header (optimistic locking)
     */
    private const METHODS_WITH_IF_MATCH = [
        self::METHOD_PUT,
        self::METHOD_PATCH,
        self::METHOD_DELETE,
    ];

    /**
     * Methods that use If-None-Match header (cache validation)
     */
    private const METHODS_WITH_IF_NONE_MATCH = [
        self::METHOD_GET,
        self::METHOD_HEAD,
    ];

    /**
     * Create a new middleware instance
     *
     * @param ETagService $etagService
     */
    public function __construct(
        private readonly ETagService $etagService
    ) {}

    /**
     * Handle an incoming request
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Check if ETags are enabled
        if (!config('shared.etags.enabled', true)) {
            return $next($request);
        }

        // Check if route is excluded
        if ($this->isExcludedRoute($request)) {
            return $next($request);
        }

        // Handle request
        $response = $next($request);

        // Only process HTTP responses
        if (!$response instanceof SymfonyResponse) {
            return $response;
        }

        // Handle If-Match header (for PUT, PATCH, DELETE)
        if (in_array($request->method(), self::METHODS_WITH_IF_MATCH)) {
            return $this->handleIfMatch($request, $response);
        }

        // Handle If-None-Match header (for GET, HEAD)
        if (in_array($request->method(), self::METHODS_WITH_IF_NONE_MATCH)) {
            return $this->handleIfNoneMatch($request, $response);
        }

        // Add ETag header to response if not already present
        $this->addETagToResponse($response);

        return $response;
    }

    /**
     * Handle If-Match header (optimistic locking)
     *
     * Validates that the resource hasn't been modified since the client's last request.
     * Returns 412 Precondition Failed if ETag doesn't match.
     *
     * @param Request $request
     * @param SymfonyResponse $response
     * @return SymfonyResponse
     */
    protected function handleIfMatch(Request $request, SymfonyResponse $response): SymfonyResponse
    {
        $ifMatch = $request->header(self::HEADER_IF_MATCH);
        
        if (!$ifMatch) {
            // No If-Match header, proceed normally
            $this->addETagToResponse($response);
            return $response;
        }

        // Get or generate current ETag from response
        $currentEtag = $this->getOrGenerateETag($response);
        
        if (!$currentEtag) {
            // No ETag available, can't validate - proceed normally
            $this->addETagToResponse($response);
            return $response;
        }

        // Validate if request ETag matches current ETag
        if (!$this->validateETagMatch($ifMatch, $currentEtag)) {
            // ETag doesn't match - resource was modified
            return $this->createPreconditionFailedResponse();
        }

        // ETag matches, proceed with update
        $this->addETagToResponse($response);
        return $response;
    }

    /**
     * Handle If-None-Match header (cache validation)
     *
     * Returns 304 Not Modified if the resource hasn't changed since the client's last request.
     * This allows clients to use cached responses without re-downloading unchanged content.
     *
     * @param Request $request
     * @param SymfonyResponse $response
     * @return SymfonyResponse
     */
    protected function handleIfNoneMatch(Request $request, SymfonyResponse $response): SymfonyResponse
    {
        $ifNoneMatch = $request->header(self::HEADER_IF_NONE_MATCH);
        
        if (!$ifNoneMatch) {
            // No If-None-Match header, add ETag and return response
            $this->addETagToResponse($response);
            return $response;
        }

        // Get or generate current ETag from response
        $currentEtag = $this->getOrGenerateETag($response);
        
        if (!$currentEtag) {
            // No ETag available, return full response
            $this->addETagToResponse($response);
            return $response;
        }

        // Validate if request ETag matches current ETag
        if ($this->validateETagMatch($ifNoneMatch, $currentEtag)) {
            // ETag matches - resource unchanged, return 304
            return $this->createNotModifiedResponse($response, $currentEtag);
        }

        // ETag doesn't match - resource changed, return full response
        $this->addETagToResponse($response);
        return $response;
    }

    /**
     * Add ETag header to response if not already present
     *
     * @param SymfonyResponse $response
     * @return void
     */
    protected function addETagToResponse(SymfonyResponse $response): void
    {
        if ($response->headers->has(self::HEADER_ETAG)) {
            return;
        }

        $etag = $this->generateETagFromResponse($response);
        
        if ($etag) {
            $response->headers->set(self::HEADER_ETAG, $etag);
        }
    }

    /**
     * Create a 304 Not Modified response
     *
     * @param SymfonyResponse $response The original response
     * @param string $etag The ETag value to include in the response
     * @return SymfonyResponse
     */
    protected function createNotModifiedResponse(SymfonyResponse $response, string $etag): SymfonyResponse
    {
        $response->setStatusCode(self::STATUS_NOT_MODIFIED);
        $response->setContent('');
        
        // Keep ETag header
        $response->headers->set(self::HEADER_ETAG, $etag);
        
        // Remove content-related headers for 304
        $response->headers->remove(self::HEADER_CONTENT_TYPE);
        $response->headers->remove(self::HEADER_CONTENT_LENGTH);
        
        return $response;
    }

    /**
     * Create a 412 Precondition Failed response
     *
     * @return SymfonyResponse
     */
    protected function createPreconditionFailedResponse(): SymfonyResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Resource has been modified. Please refresh and try again.',
            'error' => 'precondition_failed',
        ], self::STATUS_PRECONDITION_FAILED);
    }

    /**
     * Get ETag from response headers
     *
     * @param SymfonyResponse $response
     * @return string|null
     */
    protected function getETagFromResponse(SymfonyResponse $response): ?string
    {
        return $response->headers->get(self::HEADER_ETAG);
    }

    /**
     * Generate ETag from response content
     *
     * @param SymfonyResponse $response
     * @return string|null
     */
    protected function generateETagFromResponse(SymfonyResponse $response): ?string
    {
        $content = $response->getContent();
        
        if (empty($content)) {
            return null;
        }

        $useWeakETags = config('shared.etags.weak_etags', false);
        
        return $this->etagService->generateFromContent($content, $useWeakETags);
    }

    /**
     * Get or generate ETag for response
     * 
     * Retrieves existing ETag from response headers, or generates one if not present.
     * If generated, the ETag is added to the response headers.
     *
     * @param SymfonyResponse $response
     * @return string|null
     */
    protected function getOrGenerateETag(SymfonyResponse $response): ?string
    {
        $etag = $this->getETagFromResponse($response);
        
        if ($etag) {
            return $etag;
        }

        $etag = $this->generateETagFromResponse($response);
        
        if ($etag) {
            $response->headers->set(self::HEADER_ETAG, $etag);
        }

        return $etag;
    }

    /**
     * Validate if request ETags match the current ETag
     *
     * @param string $requestHeaderValue The If-Match or If-None-Match header value
     * @param string $currentEtag The current ETag from the response
     * @return bool True if any request ETag matches the current ETag
     */
    protected function validateETagMatch(string $requestHeaderValue, string $currentEtag): bool
    {
        $requestEtags = $this->etagService->parseETagHeader($requestHeaderValue);
        
        return $this->etagService->validateAny($requestEtags, $currentEtag);
    }

    /**
     * Check if current route is excluded from ETag processing
     *
     * Routes can be excluded by name or URI pattern as configured in
     * 'shared.etags.excluded_routes' config.
     *
     * @param Request $request
     * @return bool True if route should be excluded from ETag processing
     */
    protected function isExcludedRoute(Request $request): bool
    {
        $excludedRoutes = config('shared.etags.excluded_routes', []);
        
        if (empty($excludedRoutes)) {
            return false;
        }

        $routeName = $request->route()?->getName();
        $routeUri = $request->path();
        
        foreach ($excludedRoutes as $excluded) {
            // Check route name
            if ($routeName && $routeName === $excluded) {
                return true;
            }
            
            // Check URI pattern
            if (str_contains($routeUri, $excluded)) {
                return true;
            }
        }
        
        return false;
    }
}

