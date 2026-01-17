<?php

namespace Modules\Shared\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Shared\Support\CacheKeyGenerator;

/**
 * Cache Service
 *
 * Centralized cache management service.
 * Provides utilities for cache operations with standardized key generation.
 *
 * @package Modules\Shared\Services
 * @version 1.0.0
 */
class CacheService
{
    /**
     * Default cache TTL in seconds
     *
     * @var int
     */
    private int $defaultTtl;

    public function __construct()
    {
        $this->defaultTtl = config('shared.caching.default_ttl', 300);
    }

    /**
     * Get cached value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    /**
     * Put value in cache
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        return Cache::put($key, $value, $ttl ?? $this->defaultTtl);
    }

    /**
     * Remember value (get or compute and cache)
     *
     * @param string $key
     * @param callable $callback
     * @param int|null $ttl
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        return Cache::remember($key, $ttl ?? $this->defaultTtl, $callback);
    }

    /**
     * Forget cached value
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Forget multiple keys by pattern
     *
     * @param string $pattern
     * @return void
     */
    public function forgetByPattern(string $pattern): void
    {
        // This requires Redis or similar cache driver that supports pattern matching
        if (config('cache.default') === 'redis') {
            $keys = Cache::getRedis()->keys($pattern);
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        }
    }

    /**
     * Forget cache by tags
     *
     * @param array $tags
     * @return void
     */
    public function forgetByTags(array $tags): void
    {
        Cache::tags($tags)->flush();
    }

    /**
     * Clear all cache
     *
     * @return bool
     */
    public function flush(): bool
    {
        return Cache::flush();
    }

    /**
     * Get cache key generator
     *
     * @return CacheKeyGenerator
     */
    public function key(): CacheKeyGenerator
    {
        return new CacheKeyGenerator();
    }
}

