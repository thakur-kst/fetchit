<?php

namespace Modules\Shared\Contracts;

/**
 * Cacheable Interface
 *
 * Interface for entities that can be cached.
 * Defines methods for cache key generation.
 *
 * @package Modules\Shared\Contracts
 * @version 1.0.0
 */
interface CacheableInterface
{
    /**
     * Get the cache key for this entity
     *
     * @return string
     */
    public function getCacheKey(): string;

    /**
     * Get the cache TTL in seconds
     *
     * @return int
     */
    public function getCacheTtl(): int;

    /**
     * Get cache tags for this entity (for tag-based invalidation)
     *
     * @return array
     */
    public function getCacheTags(): array;
}

