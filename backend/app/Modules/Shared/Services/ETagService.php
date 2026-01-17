<?php

namespace Modules\Shared\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Shared\Support\ETagGenerator;

/**
 * ETag Service
 *
 * Core service for generating and validating ETags.
 * Provides methods for ETag generation from various sources and validation.
 *
 * @package Modules\Shared\Services
 * @version 1.0.0
 */
class ETagService
{
    /**
     * Generate ETag from an Eloquent model
     *
     * @param Model $model
     * @param bool $weak Whether to generate weak ETag
     * @return string
     */
    public function generateFromModel(Model $model, bool $weak = false): string
    {
        if ($weak) {
            return ETagGenerator::weakFromContent($model->toArray());
        }
        
        return ETagGenerator::fromModel($model);
    }

    /**
     * Generate ETag from response content
     *
     * @param mixed $content
     * @param bool $weak Whether to generate weak ETag
     * @return string
     */
    public function generateFromContent($content, bool $weak = false): string
    {
        if ($weak) {
            return ETagGenerator::weakFromContent($content);
        }
        
        return ETagGenerator::fromContent($content);
    }

    /**
     * Generate ETag from a collection
     *
     * @param Collection|array $collection
     * @param bool $weak Whether to generate weak ETag (default true for collections)
     * @return string
     */
    public function generateFromCollection($collection, bool $weak = true): string
    {
        if (is_array($collection)) {
            $collection = collect($collection);
        }
        
        if ($weak) {
            return ETagGenerator::fromCollection($collection);
        }
        
        return ETagGenerator::fromModels($collection, false);
    }

    /**
     * Validate if two ETags match
     *
     * Weak ETags match if their values are equal (ignoring weak prefix).
     * Strong ETags must match exactly.
     *
     * @param string $etag1
     * @param string $etag2
     * @return bool
     */
    public function validate(string $etag1, string $etag2): bool
    {
        // Normalize ETags
        $normalized1 = ETagGenerator::removeWeakPrefix(trim($etag1));
        $normalized2 = ETagGenerator::removeWeakPrefix(trim($etag2));
        
        // Remove quotes for comparison
        $value1 = ETagGenerator::normalize($normalized1);
        $value2 = ETagGenerator::normalize($normalized2);
        
        return $value1 === $value2;
    }

    /**
     * Validate multiple ETags (for If-None-Match with multiple values)
     *
     * @param string|array $requestEtags ETags from request header
     * @param string $currentEtag Current resource ETag
     * @return bool True if any ETag matches
     */
    public function validateAny($requestEtags, string $currentEtag): bool
    {
        if (is_string($requestEtags)) {
            $requestEtags = $this->parseETagHeader($requestEtags);
        }
        
        foreach ($requestEtags as $etag) {
            if ($this->validate($etag, $currentEtag)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if ETag is weak
     *
     * @param string $etag
     * @return bool
     */
    public function isWeak(string $etag): bool
    {
        return ETagGenerator::isWeak($etag);
    }

    /**
     * Get ETag from request header
     *
     * @param Request $request
     * @param string $headerName Header name (If-None-Match, If-Match)
     * @return string|null
     */
    public function getETagFromRequest(Request $request, string $headerName): ?string
    {
        $header = $request->header($headerName);
        
        if (!$header) {
            return null;
        }
        
        // Parse header (can contain multiple ETags separated by commas)
        $etags = $this->parseETagHeader($header);
        
        return !empty($etags) ? $etags[0] : null;
    }

    /**
     * Parse ETag header value
     *
     * Handles multiple ETags separated by commas
     *
     * @param string $headerValue
     * @return array
     */
    public function parseETagHeader(string $headerValue): array
    {
        // Split by comma and trim each ETag
        $etags = array_map('trim', explode(',', $headerValue));
        
        // Filter out empty values
        return array_filter($etags, fn($etag) => !empty($etag));
    }

    /**
     * Compare ETags for conditional requests
     *
     * @param string|null $requestEtag ETag from request
     * @param string $currentEtag Current resource ETag
     * @return bool True if ETags match
     */
    public function compare(?string $requestEtag, string $currentEtag): bool
    {
        if (!$requestEtag) {
            return false;
        }
        
        return $this->validate($requestEtag, $currentEtag);
    }
}

