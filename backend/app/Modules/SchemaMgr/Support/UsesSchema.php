<?php

namespace Modules\SchemaMgr\Support;

use Illuminate\Support\Facades\Config;

/**
 * Uses Schema Trait
 *
 * Automatically sets the correct PostgreSQL schema for Eloquent models
 * based on the table configuration in config/schemas.php
 *
 * This trait should be added to any Eloquent model that needs to
 * operate within a specific schema (core, customer_portal, etc.)
 *
 * Usage:
 * ```php
 * use Modules\SchemaMgr\Support\UsesSchema;
 *
 * class Organization extends Model
 * {
 *     use UsesSchema;
 *
 *     protected $schema = 'core'; // Optional: explicit schema
 *     protected $table = 'organizations';
 * }
 * ```
 *
 * @package SchemaMgr
 * @version 1.0.0
 */
trait UsesSchema
{
    /**
     * Get the table name with schema prefix
     *
     * Automatically prepends the appropriate schema name to the table.
     * The schema is determined either from:
     * 1. Explicit $schema property on the model
     * 2. Auto-detection from config/schemas.php
     * 3. Default schema from configuration
     *
     * @return string Fully qualified table name (schema.table)
     */
    public function getTable(): string
    {
        $table = parent::getTable();

        // Check if table already has schema prefix (format: schema.table)
        if (strpos($table, '.') !== false) {
            return $table;
        }

        // If schema is explicitly set on the model
        if (isset($this->schema)) {
            return $this->schema . '.' . $table;
        }

        // Auto-detect schema from configuration
        $schema = $this->detectSchema($table);

        return $schema ? $schema . '.' . $table : $table;
    }

    /**
     * Detect the schema for a given table name
     *
     * Searches through the schema configuration to find which
     * schema the table belongs to.
     *
     * @param string $table Table name
     * @return string|null Schema name or null if not found
     */
    protected function detectSchema(string $table): ?string
    {
        // Check if schema configuration is disabled
        if (!Config::get('schemas.enabled', true)) {
            return null;
        }

        // Check if using PostgreSQL
        if (Config::get('database.default') !== 'pgsql') {
            return null;
        }

        // Get the schema from table mapping
        $schemas = Config::get('schemas.schemas', []);

        foreach ($schemas as $schemaName => $schemaConfig) {
            if (in_array($table, $schemaConfig['tables'] ?? [])) {
                return $schemaName;
            }
        }

        // Default to public schema if not found
        return Config::get('schemas.default', 'public');
    }

    /**
     * Get the raw table name without schema prefix
     *
     * Returns just the table name, excluding the schema prefix.
     * Useful for cases where you need the bare table name.
     *
     * @return string Table name without schema
     */
    public function getRawTableName(): string
    {
        return parent::getTable();
    }

    /**
     * Get the schema name for this model
     *
     * @return string Schema name (e.g., 'core', 'customer_portal', 'public')
     */
    public function getSchemaName(): string
    {
        if (isset($this->schema)) {
            return $this->schema;
        }

        return $this->detectSchema(parent::getTable()) ?? 'public';
    }

    /**
     * Get the fully qualified table name
     *
     * Alias for getTable() for clarity in code
     *
     * @return string Fully qualified table name (schema.table)
     */
    public function getQualifiedTableName(): string
    {
        return $this->getTable();
    }
}
