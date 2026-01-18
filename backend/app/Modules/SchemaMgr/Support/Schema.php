<?php

namespace Modules\SchemaMgr\Support;

use Closure;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema as LaravelSchema;

/**
 * Schema Helper for Multi-Schema Database Architecture
 *
 * Provides convenient methods for working with PostgreSQL schemas
 * in Laravel migrations and application code.
 *
 * @package SchemaMgr
 * @version 1.0.0
 */
class Schema
{
    /**
     * Create a table in a specific schema
     *
     * @param string $schema Schema name (e.g., 'core', 'customer_portal')
     * @param string $table Table name without schema prefix
     * @param Closure $callback Blueprint callback for table definition
     * @return void
     */
    public static function createInSchema(string $schema, string $table, Closure $callback): void
    {
        // For 'public', use table name only so Laravel creates in search_path (public)
        $fullTableName = ($schema === 'public') ? $table : $schema . '.' . $table;
        LaravelSchema::create($fullTableName, $callback);
    }

    /**
     * Modify a table in a specific schema
     *
     * @param string $schema Schema name
     * @param string $table Table name
     * @param Closure $callback Blueprint callback for modifications
     * @return void
     */
    public static function tableInSchema(string $schema, string $table, Closure $callback): void
    {
        $fullTableName = ($schema === 'public') ? $table : $schema . '.' . $table;
        LaravelSchema::table($fullTableName, $callback);
    }

    /**
     * Drop a table from a specific schema
     *
     * @param string $schema Schema name
     * @param string $table Table name
     * @return void
     */
    public static function dropFromSchema(string $schema, string $table): void
    {
        $fullTableName = ($schema === 'public') ? $table : $schema . '.' . $table;
        LaravelSchema::dropIfExists($fullTableName);
    }

    /**
     * Check if a table exists in a specific schema
     *
     * @param string $schema Schema name
     * @param string $table Table name
     * @return bool
     */
    public static function hasTableInSchema(string $schema, string $table): bool
    {
        $fullTableName = ($schema === 'public') ? $table : $schema . '.' . $table;
        return LaravelSchema::hasTable($fullTableName);
    }

    /**
     * Get the schema name for a table from configuration
     *
     * Looks up the table in config/schemas.php and returns
     * the schema it should belong to.
     *
     * @param string $table Table name
     * @return string Schema name (defaults to 'public')
     */
    public static function getSchemaForTable(string $table): string
    {
        $schemas = Config::get('schemas.schemas', []);

        foreach ($schemas as $schemaName => $schemaConfig) {
            if (in_array($table, $schemaConfig['tables'] ?? [])) {
                return $schemaName;
            }
        }

        return Config::get('schemas.default', 'public');
    }

    /**
     * Create a table in the schema defined in configuration
     *
     * Automatically determines the correct schema from config/schemas.php
     * based on the table name.
     *
     * @param string $table Table name
     * @param Closure $callback Blueprint callback for table definition
     * @return void
     */
    public static function createConfigured(string $table, Closure $callback): void
    {
        $schema = self::getSchemaForTable($table);
        self::createInSchema($schema, $table, $callback);
    }

    /**
     * Modify a table in the schema defined in configuration
     *
     * @param string $table Table name
     * @param Closure $callback Blueprint callback for modifications
     * @return void
     */
    public static function tableConfigured(string $table, Closure $callback): void
    {
        $schema = self::getSchemaForTable($table);
        self::tableInSchema($schema, $table, $callback);
    }

    /**
     * Drop a table from the schema defined in configuration
     *
     * @param string $table Table name
     * @return void
     */
    public static function dropConfigured(string $table): void
    {
        $schema = self::getSchemaForTable($table);
        self::dropFromSchema($schema, $table);
    }

    /**
     * Create a foreign key reference to a table in its configured schema
     *
     * Automatically resolves the schema for the referenced table
     * and creates a properly qualified foreign key constraint.
     *
     * @param Blueprint $table Blueprint instance
     * @param string $column Foreign key column name
     * @param string $referencedTable Referenced table name
     * @param string $referencedColumn Referenced column name (default: 'id')
     * @return void
     */
    public static function addForeignKey(
        Blueprint $table,
        string $column,
        string $referencedTable,
        string $referencedColumn = 'id'
    ): void {
        $schema = self::getSchemaForTable($referencedTable);
        $fullTableName = $schema . '.' . $referencedTable;

        $table->foreign($column)
            ->references($referencedColumn)
            ->on($fullTableName)
            ->onDelete('cascade');
    }

    /**
     * Get all configured schemas
     *
     * @return array Array of schema names
     */
    public static function getAllSchemas(): array
    {
        return array_keys(Config::get('schemas.schemas', []));
    }

    /**
     * Get all tables for a specific schema
     *
     * @param string $schema Schema name
     * @return array Array of table names
     */
    public static function getTablesForSchema(string $schema): array
    {
        return Config::get("schemas.schemas.{$schema}.tables", []);
    }

    /**
     * Check if multi-schema support is enabled
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return Config::get('schemas.enabled', true)
            && Config::get('database.default') === 'pgsql';
    }
}
