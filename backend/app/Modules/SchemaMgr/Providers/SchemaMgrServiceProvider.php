<?php

namespace Modules\SchemaMgr\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Modules\SchemaMgr\Console\Commands\CreateSchemasCommand;
use Modules\SchemaMgr\Console\Commands\ListSchemasCommand;
use Modules\SchemaMgr\Console\Commands\CheckSchemaCommand;

/**
 * Schema Manager Service Provider
 *
 * Manages PostgreSQL multi-schema architecture for the application.
 * Provides tools and utilities for working with core, customer_portal,
 * and other configured database schemas.
 *
 * Features:
 * - Automatic schema detection for models
 * - Schema-aware migration helpers
 * - Artisan commands for schema management
 * - Search path configuration
 *
 * @package SchemaMgr
 * @version 1.0.0
 */
class SchemaMgrServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register schema configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/schemas.php',
            'schemas'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only proceed if schemas are enabled and using PostgreSQL
        if (!Config::get('schemas.enabled', true)) {
            return;
        }

        if (Config::get('database.default') !== 'pgsql') {
            return;
        }

        // Set search path after database connection
        $this->configureSearchPath();

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateSchemasCommand::class,
                ListSchemasCommand::class,
                CheckSchemaCommand::class,
            ]);

            // Publish configuration
            $this->publishes([
                __DIR__ . '/../config/schemas.php' => config_path('schemas.php'),
            ], 'schema-management-config');
        }
    }

    /**
     * Configure PostgreSQL search path
     *
     * Sets the search_path for the current database connection
     * to include all configured schemas.
     *
     * @return void
     */
    protected function configureSearchPath(): void
    {
        try {
            // Use DB::listen to set search path after connection is established
            DB::listen(function ($query) {
                static $searchPathSet = false;
                static $isSettingPath = false;

                // Prevent recursion - don't set path if we're already setting it
                if ($isSettingPath) {
                    return;
                }

                // Skip if query is setting search_path itself or checking schema existence
                $sql = $query->sql ?? '';
                if (stripos($sql, 'SET search_path') !== false ||
                    stripos($sql, 'information_schema.schemata') !== false ||
                    stripos($sql, 'CREATE SCHEMA') !== false ||
                    stripos($sql, 'DROP SCHEMA') !== false ||
                    stripos($sql, 'GRANT') !== false ||
                    stripos($sql, 'ALTER DEFAULT PRIVILEGES') !== false) {
                    return;
                }

                if (!$searchPathSet) {
                    $isSettingPath = true;
                    try {
                        $this->setSearchPath();
                        $searchPathSet = true;
                    } finally {
                        $isSettingPath = false;
                    }
                }
            });
        } catch (\Exception $e) {
            Log::warning("Failed to configure PostgreSQL search_path: {$e->getMessage()}");
        }
    }

    /**
     * Set PostgreSQL search path
     *
     * @return void
     */
    protected function setSearchPath(): void
    {
        try {
            $searchPath = Config::get('database.connections.pgsql.search_path', 'public');

            // Verify that all schemas in the search path exist before setting it
            // This prevents errors when schemas haven't been created yet
            $searchPathSchemas = array_map('trim', explode(',', $searchPath));
            $placeholders = implode(',', array_fill(0, count($searchPathSchemas), '?'));
            $existingSchemas = DB::select("SELECT schema_name FROM information_schema.schemata WHERE schema_name IN ({$placeholders})", $searchPathSchemas);
            $existingSchemaNames = collect($existingSchemas)->pluck('schema_name')->toArray();

            // Only include schemas that exist in the search path
            $validSearchPath = array_filter($searchPathSchemas, function ($schema) use ($existingSchemaNames) {
                return in_array($schema, $existingSchemaNames);
            });

            // If some schemas don't exist, use only public (or existing ones)
            if (count($validSearchPath) < count($searchPathSchemas)) {
                // If we're missing schemas, just use public for now
                // This happens during schema creation
                $validSearchPath = ['public'];
            }

            $finalSearchPath = implode(',', $validSearchPath);
            DB::statement("SET search_path TO {$finalSearchPath}");

           
        } catch (\Exception $e) {
            // Silently fail - this is expected during schema creation
            // Log only if it's not a schema-related error
            if (!str_contains($e->getMessage(), 'schema') && !str_contains($e->getMessage(), 'does not exist')) {
                Log::warning("Failed to set PostgreSQL search_path: {$e->getMessage()}");
            }
        }
    }

    /**
     * Get all configured schemas
     *
     * @return array
     */
    protected function getConfiguredSchemas(): array
    {
        return array_keys(Config::get('schemas.schemas', []));
    }

    /**
     * Get tables for a specific schema
     *
     * @param string $schema
     * @return array
     */
    public function getTablesForSchema(string $schema): array
    {
        return Config::get("schemas.schemas.{$schema}.tables", []);
    }

    /**
     * Check if a table exists in configured schemas
     *
     * @param string $table
     * @return bool
     */
    public function isTableConfigured(string $table): bool
    {
        $schemas = Config::get('schemas.schemas', []);

        foreach ($schemas as $schemaConfig) {
            if (in_array($table, $schemaConfig['tables'] ?? [])) {
                return true;
            }
        }

        return false;
    }
}
