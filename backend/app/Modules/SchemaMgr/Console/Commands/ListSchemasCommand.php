<?php

namespace Modules\SchemaMgr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * List Schemas Command
 *
 * Lists all PostgreSQL schemas and their tables with
 * configuration status and health information.
 *
 * Usage:
 *   php artisan schema:list
 *   php artisan schema:list --schema=core
 *   php artisan schema:list --tables
 *
 * @package SchemaMgr
 * @version 1.0.0
 */
class ListSchemasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:list
                            {--schema= : Show details for a specific schema}
                            {--tables : Show tables in each schema}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all PostgreSQL schemas and their configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (Config::get('database.default') !== 'pgsql') {
            $this->error('❌ This command only works with PostgreSQL database');
            return self::FAILURE;
        }

        $specificSchema = $this->option('schema');
        $showTables = $this->option('tables');

        if ($specificSchema) {
            $this->showSchemaDetails($specificSchema, $showTables);
        } else {
            $this->listAllSchemas($showTables);
        }

        return self::SUCCESS;
    }

    /**
     * List all schemas
     *
     * @param bool $showTables
     * @return void
     */
    protected function listAllSchemas(bool $showTables): void
    {
        $this->info('📊 PostgreSQL Schemas Configuration');
        $this->newLine();

        // Get configured schemas
        $schemas = Config::get('schemas.schemas', []);

        // Get actual schemas from database
        $dbSchemas = DB::select("SELECT schema_name FROM information_schema.schemata ORDER BY schema_name");
        $dbSchemaNames = collect($dbSchemas)->pluck('schema_name')->toArray();

        $tableData = [];

        foreach ($schemas as $schema => $config) {
            $exists = in_array($schema, $dbSchemaNames);
            $tableCount = $exists ? $this->getTableCount($schema) : 0;
            $configuredTables = count($config['tables'] ?? []);

            $tableData[] = [
                $schema,
                $exists ? '✅' : '❌',
                $config['description'] ?? '',
                $tableCount,
                $configuredTables,
            ];
        }

        $this->table(
            ['Schema', 'Exists', 'Description', 'Tables', 'Configured'],
            $tableData
        );

        if ($showTables) {
            $this->newLine();
            foreach (array_keys($schemas) as $schema) {
                if (in_array($schema, $dbSchemaNames)) {
                    $this->showSchemaDetails($schema, true);
                    $this->newLine();
                }
            }
        }
    }

    /**
     * Show details for a specific schema
     *
     * @param string $schema
     * @param bool $showTables
     * @return void
     */
    protected function showSchemaDetails(string $schema, bool $showTables): void
    {
        $config = Config::get("schemas.schemas.{$schema}");

        if (!$config) {
            $this->error("❌ Schema '{$schema}' not found in configuration");
            return;
        }

        $this->info("📦 Schema: {$schema}");
        $this->line("   Description: " . ($config['description'] ?? 'N/A'));

        // Check if exists in database
        $exists = DB::selectOne(
            "SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?",
            [$schema]
        );

        $status = $exists ? '✅ Exists' : '❌ Does not exist';
        $this->line("   Status: {$status}");

        if ($showTables) {
            $configuredTables = $config['tables'] ?? [];
            $actualTables = $exists ? $this->getTables($schema) : [];

            $this->newLine();
            $this->line("   Configured Tables: " . count($configuredTables));

            if ($exists) {
                $this->line("   Actual Tables: " . count($actualTables));

                // Show missing tables
                $missingTables = array_diff($configuredTables, $actualTables);
                if (!empty($missingTables)) {
                    $this->warn("   ⚠️  Missing tables: " . implode(', ', $missingTables));
                }

                // Show extra tables
                $extraTables = array_diff($actualTables, $configuredTables);
                if (!empty($extraTables)) {
                    $this->warn("   ⚠️  Extra tables: " . implode(', ', $extraTables));
                }

                // Show table list
                if (!empty($actualTables)) {
                    $this->newLine();
                    $this->line('   Tables in schema:');
                    foreach ($actualTables as $table) {
                        $configured = in_array($table, $configuredTables) ? '✅' : '❓';
                        $this->line("      {$configured} {$table}");
                    }
                }
            }
        }
    }

    /**
     * Get table count for a schema
     *
     * @param string $schema
     * @return int
     */
    protected function getTableCount(string $schema): int
    {
        $result = DB::selectOne(
            "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?",
            [$schema]
        );

        return (int) $result->count;
    }

    /**
     * Get tables in a schema
     *
     * @param string $schema
     * @return array
     */
    protected function getTables(string $schema): array
    {
        $tables = DB::select(
            "SELECT table_name FROM information_schema.tables WHERE table_schema = ? ORDER BY table_name",
            [$schema]
        );

        return collect($tables)->pluck('table_name')->toArray();
    }
}
