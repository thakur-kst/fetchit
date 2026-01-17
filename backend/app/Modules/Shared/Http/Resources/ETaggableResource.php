<?php

namespace Modules\Shared\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Modules\Shared\Services\ETagService;

/**
 * ETaggable Resource
 *
 * Base resource class that extends JsonResource with automatic ETag support.
 * Resources extending this class will automatically include ETag headers.
 *
 * Note: We do NOT override JsonResource::toArray() here, so child resources
 * define their own serialization as usual.
 *
 * @package Modules\Shared\Http\Resources
 * @version 1.0.0
 */
class ETaggableResource extends JsonResource
{
    /**
     * Create a new resource instance
     *
     * @param mixed $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * Get the ETag for this resource
     *
     * Override this method to customize ETag generation.
     * By default, generates ETag from the model's updated_at timestamp.
     *
     * @param Request $request
     * @return string|null
     */
    public function getETag(Request $request): ?string
    {
        if (!$this->resource instanceof Model) {
            return null;
        }

        $etagService = app(ETagService::class);
        $useWeakETags = config('shared.etags.weak_etags', false);

        return $etagService->generateFromModel($this->resource, $useWeakETags);
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function toResponse($request)
    {
        $response = parent::toResponse($request);

        // Add ETag header if not already present
        if (!$response->headers->has('ETag')) {
            $etag = $this->getETag($request);
            
            if ($etag) {
                $response->headers->set('ETag', $etag);
            }
        }

        return $response;
    }

    /**
     * Add ETag to response
     *
     * @param Response $response
     * @param Request $request
     * @return Response
     */
    public function withETag(Response $response, Request $request): Response
    {
        $etag = $this->getETag($request);
        if ($etag) {
            $response->headers->set('ETag', $etag);
        }

        return $response;
    }
}

