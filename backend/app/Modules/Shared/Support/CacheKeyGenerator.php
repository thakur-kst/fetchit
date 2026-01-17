<?php

namespace Modules\Shared\Support;

/**
 * Cache Key Generator
 *
 * Standardized cache key generation utilities.
 * Ensures consistent cache key format across all modules.
 *
 * @package Modules\Shared\Support
 * @version 1.0.0
 */
class CacheKeyGenerator
{
    /**
     * Generate cache key for entity
     *
     * Format: {module}:{entity}:{identifier}
     *
     * @param string $module Module name
     * @param string $entity Entity name
     * @param string|int $identifier Entity identifier (ID or UUID)
     * @return string
     */
    public static function entity(string $module, string $entity, string|int $identifier): string
    {
        return sprintf('%s:%s:%s', strtolower($module), strtolower($entity), $identifier);
    }

    /**
     * Generate cache key for entity by ID
     *
     * @param string $module
     * @param string $entity
     * @param int $id
     * @return string
     */
    public static function entityById(string $module, string $entity, int $id): string
    {
        return self::entity($module, $entity, $id);
    }

    /**
     * Generate cache key for entity by UUID
     *
     * @param string $module
     * @param string $entity
     * @param string $uuid
     * @return string
     */
    public static function entityByUuid(string $module, string $entity, string $uuid): string
    {
        return self::entity($module, $entity, $uuid);
    }

    /**
     * Generate cache key for list/collection
     *
     * Format: {module}:{entity}:list:{filters}
     *
     * @param string $module
     * @param string $entity
     * @param array $filters
     * @return string
     */
    public static function list(string $module, string $entity, array $filters = []): string
    {
        $filterKey = empty($filters) ? 'all' : md5(serialize($filters));
        return sprintf('%s:%s:list:%s', strtolower($module), strtolower($entity), $filterKey);
    }

    /**
     * Generate cache key for user-specific data
     *
     * Format: {module}:{entity}:user:{user_id}
     *
     * @param string $module
     * @param string $entity
     * @param int|string $userId
     * @return string
     */
    public static function user(string $module, string $entity, int|string $userId): string
    {
        return sprintf('%s:%s:user:%s', strtolower($module), strtolower($entity), $userId);
    }

    /**
     * Generate cache tag
     *
     * @param string $module
     * @param string $entity
     * @return string
     */
    public static function tag(string $module, string $entity): string
    {
        return sprintf('%s:%s', strtolower($module), strtolower($entity));
    }
}

