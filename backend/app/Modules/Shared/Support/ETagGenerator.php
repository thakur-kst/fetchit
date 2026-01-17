<?php

namespace Modules\Shared\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * ETag Generator
 *
 * Static utility class for generating ETags from various sources.
 * Provides helper methods for creating ETags based on models, content, and collections.
 *
 * @package Modules\Shared\Support
 * @version 1.0.0
 */
class ETagGenerator
{
    /**
     * Generate a strong ETag from an Eloquent model
     *
     * Format: "model-id-timestamp-hash"
     * Uses model ID and updated_at timestamp for uniqueness
     *
     * @param Model $model
     * @return string
     */
    public static function fromModel(Model $model): string
    {
        $id = $model->getKey();
        $updatedAt = $model->getUpdatedAtColumn();
        $timestamp = $model->$updatedAt ? $model->$updatedAt->timestamp : $model->created_at->timestamp;
        
        $data = sprintf('%s-%s-%d', get_class($model), $id, $timestamp);
        $hash = hash('sha256', $data);
        
        return sprintf('"%s"', substr($hash, 0, 32));
    }

    /**
     * Generate a strong ETag from model with custom fields
     *
     * @param Model $model
     * @param array $fields Additional fields to include in ETag
     * @return string
     */
    public static function fromModelWithFields(Model $model, array $fields = []): string
    {
        $id = $model->getKey();
        $updatedAt = $model->getUpdatedAtColumn();
        $timestamp = $model->$updatedAt ? $model->$updatedAt->timestamp : $model->created_at->timestamp;
        
        $data = sprintf('%s-%s-%d', get_class($model), $id, $timestamp);
        
        // Add custom fields
        foreach ($fields as $field) {
            if (isset($model->$field)) {
                $data .= '-' . $model->$field;
            }
        }
        
        $hash = hash('sha256', $data);
        
        return sprintf('"%s"', substr($hash, 0, 32));
    }

    /**
     * Generate a weak ETag from content
     *
     * Format: W/"content-hash"
     *
     * @param mixed $content
     * @return string
     */
    public static function weakFromContent($content): string
    {
        $serialized = is_string($content) ? $content : json_encode($content, JSON_UNESCAPED_SLASHES);
        $hash = hash('sha256', $serialized);
        
        return sprintf('W/"%s"', substr($hash, 0, 32));
    }

    /**
     * Generate a strong ETag from content
     *
     * Format: "content-hash"
     *
     * @param mixed $content
     * @return string
     */
    public static function fromContent($content): string
    {
        $serialized = is_string($content) ? $content : json_encode($content, JSON_UNESCAPED_SLASHES);
        $hash = hash('sha256', $serialized);
        
        return sprintf('"%s"', substr($hash, 0, 32));
    }

    /**
     * Generate a weak ETag from a collection
     *
     * Format: W/"collection-hash"
     *
     * @param Collection $collection
     * @return string
     */
    public static function fromCollection(Collection $collection): string
    {
        $data = $collection->map(function ($item) {
            if ($item instanceof Model) {
                return static::fromModel($item);
            }
            return is_array($item) ? json_encode($item) : (string) $item;
        })->implode('-');
        
        $hash = hash('sha256', $data);
        
        return sprintf('W/"%s"', substr($hash, 0, 32));
    }

    /**
     * Generate ETag from array of models
     *
     * @param array|Collection $models
     * @param bool $weak Whether to generate weak ETag
     * @return string
     */
    public static function fromModels($models, bool $weak = false): string
    {
        $collection = is_array($models) ? collect($models) : $models;
        
        if ($weak) {
            return static::fromCollection($collection);
        }
        
        // For strong ETags, use the most recent updated_at
        $latestTimestamp = $collection->map(function ($model) {
            if ($model instanceof Model) {
                $updatedAt = $model->getUpdatedAtColumn();
                return $model->$updatedAt ? $model->$updatedAt->timestamp : $model->created_at->timestamp;
            }
            return 0;
        })->max();
        
        $data = sprintf('collection-%d-%d', $collection->count(), $latestTimestamp);
        $hash = hash('sha256', $data);
        
        return sprintf('"%s"', substr($hash, 0, 32));
    }

    /**
     * Normalize ETag value (remove quotes if present)
     *
     * @param string $etag
     * @return string
     */
    public static function normalize(string $etag): string
    {
        return trim($etag, '"');
    }

    /**
     * Check if ETag is weak
     *
     * @param string $etag
     * @return bool
     */
    public static function isWeak(string $etag): bool
    {
        return str_starts_with($etag, 'W/');
    }

    /**
     * Remove weak prefix from ETag
     *
     * @param string $etag
     * @return string
     */
    public static function removeWeakPrefix(string $etag): string
    {
        if (static::isWeak($etag)) {
            return substr($etag, 2);
        }
        
        return $etag;
    }
}

