<?php

namespace Modules\SchemaMgr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Check Schema Command
 *
 * Validates schema configuration and checks for issues
 *
 * Usage: php artisan schema:check
 */
class CheckSchemaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:check
                            {--fix : Attempt to fix issues automatically}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check schema configuration for issues';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (Config::get('database.default') !== 'pgsql') {
            $this->error('This command only works with PostgreSQL database');
            return self::FAILURE;
        }

        $this->info('Checking schema configuration...');
        $this->line('');

        $issues = 0;

        // Check 1: Schema existence
        $issues += $this->checkSchemaExistence();

        // Check 2: Search path configuration
        $issues += $this->checkSearchPath();

        // Check 3: Table placement
        $issues += $this->checkTablePlacement();

        // Check 4: Duplicate table definitions
        $issues += $this->checkDuplicateTables();

        // Check 5: Foreign key references across schemas
        $issues += $this->checkCrossSchemaReferences();

        $this->line('');

        if ($issues === 0) {
            $this->info('✓ No issues found');
            return self::SUCCESS;
        } else {
            $this->error("✗ Found {$issues} issue(s)");
            return self::FAILURE;
        }
    }

    /**
     * Check if configured schemas exist in database
     *
     * @return int Number of issues
     */
    protected function checkSchemaExistence(): int
    {
        $this->line('Checking schema existence...');

        $schemas = array_keys(Config::get('schemas.schemas', []));
        $dbSchemas = DB::select("SELECT schema_name FROM information_schema.schemata");
        $dbSchemaNames = collect($dbSchemas)->pluck('schema_name')->toArray();

        $issues = 0;

        foreach ($schemas as $schema) {
            if (!in_array($schema, $dbSchemaNames)) {
                $this->warn("  ✗ Schema '{$schema}' does not exist in database");

                if ($this->option('fix')) {
                    try {
                        DB::statement("CREATE SCHEMA {$schema}");
                        $this->info("    → Created schema '{$schema}'");
                    } catch (\Exception $e) {
                        $this->error("    → Failed to create: {$e->getMessage()}");
                        $issues++;
                    }
                } else {
                    $issues++;
                }
            } else {
                $this->line("  ✓ Schema '{$schema}' exists");
            }
        }

        return $issues;
    }

    /**
     * Check search path configuration
     *
     * @return int Number of issues
     */
    protected function checkSearchPath(): int
    {
        $this->line('');
        $this->line('Checking search path...');

        $searchPath = Config::get('database.connections.pgsql.search_path', 'public');
        $schemas = array_keys(Config::get('schemas.schemas', []));

        $searchPathSchemas = array_map('trim', explode(',', $searchPath));

        $issues = 0;

        foreach ($schemas as $schema) {
            if (!in_array($schema, $searchPathSchemas)) {
                $this->warn("  ✗ Schema '{$schema}' not in search_path");
                $issues++;
            }
        }

        if ($issues === 0) {
            $this->line("  ✓ Search path: {$searchPath}");
        } else {
            $this->line("  Current: {$searchPath}");
            $this->line("  Recommended: " . implode(',', $schemas));
        }

        return $issues;
    }

    /**
     * Check if tables are in their configured schemas
     *
     * @return int Number of issues
     */
    protected function checkTablePlacement(): int
    {
        $this->line('');
        $this->line('Checking table placement...');

        $schemas = Config::get('schemas.schemas', []);
        $issues = 0;

        foreach ($schemas as $schema => $config) {
            $configuredTables = $config['tables'] ?? [];

            // Check if schema exists
            $schemaExists = DB::selectOne(
                "SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?",
                [$schema]
            );

            if (!$schemaExists) {
                continue;
            }

            foreach ($configuredTables as $table) {
                // Check if table exists in configured schema
                $exists = DB::selectOne(
                    "SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_name = ?",
                    [$schema, $table]
                );

                if (!$exists) {
                    // Check if it exists in wrong schema
                    $wrongSchema = DB::selectOne(
                        "SELECT table_schema FROM information_schema.tables WHERE table_name = ?",
                        [$table]
                    );

                    if ($wrongSchema) {
                        $this->warn("  ✗ Table '{$table}' is in '{$wrongSchema->table_schema}' schema, should be in '{$schema}'");
                        $issues++;
                    }
                }
            }
        }

        if ($issues === 0) {
            $this->line('  ✓ All configured tables are in correct schemas');
        }

        return $issues;
    }

    /**
     * Check for duplicate table definitions
     *
     * @return int Number of issues
     */
    protected function checkDuplicateTables(): int
    {
        $this->line('');
        $this->line('Checking for duplicate table definitions...');

        $schemas = Config::get('schemas.schemas', []);
        $allTables = [];
        $duplicates = [];

        foreach ($schemas as $schema => $config) {
            foreach ($config['tables'] ?? [] as $table) {
                if (isset($allTables[$table])) {
                    $duplicates[$table] = [$allTables[$table], $schema];
                } else {
                    $allTables[$table] = $schema;
                }
            }
        }

        if (empty($duplicates)) {
            $this->line('  ✓ No duplicate table definitions');
            return 0;
        }

        foreach ($duplicates as $table => $schemas) {
            $this->warn("  ✗ Table '{$table}' defined in multiple schemas: " . implode(', ', $schemas));
        }

        return count($duplicates);
    }

    /**
     * Check cross-schema foreign key references
     *
     * @return int Number of issues
     */
    protected function checkCrossSchemaReferences(): int
    {
        $this->line('');
        $this->line('Checking cross-schema references...');

        // This is informational - cross-schema references are valid
        $crossSchemaFKs = DB::select("
            SELECT
                n1.nspname AS from_schema,
                t1.relname AS from_table,
                n2.nspname AS to_schema,
                t2.relname AS to_table,
                c.conname AS constraint_name
            FROM pg_constraint c
            JOIN pg_class t1 ON c.conrelid = t1.oid
            JOIN pg_namespace n1 ON t1.relnamespace = n1.oid
            JOIN pg_class t2 ON c.confrelid = t2.oid
            JOIN pg_namespace n2 ON t2.relnamespace = n2.oid
            WHERE c.contype = 'f'
              AND n1.nspname != n2.nspname
              AND n1.nspname IN ('core', 'customer_portal')
            ORDER BY n1.nspname, t1.relname
        ");

        if (empty($crossSchemaFKs)) {
            $this->line('  ℹ No cross-schema foreign keys found');
        } else {
            $this->line('  ℹ Found ' . count($crossSchemaFKs) . ' cross-schema foreign key(s):');
            foreach ($crossSchemaFKs as $fk) {
                $this->line("    {$fk->from_schema}.{$fk->from_table} → {$fk->to_schema}.{$fk->to_table}");
            }
        }

        return 0; // Not an issue, just informational
    }
}
