<?php

namespace Modules\SchemaMgr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Create Schemas Command
 *
 * Creates all configured PostgreSQL schemas with proper permissions
 * and grants. This command should be run before migrations.
 *
 * Usage:
 *   php artisan schema:create
 *   php artisan schema:create --schema=core
 *   php artisan schema:create --force
 *
 * @package SchemaMgr
 * @version 1.0.0
 */
class CreateSchemasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:create
                            {--schema= : Create a specific schema}
                            {--force : Force creation even if schema exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create PostgreSQL schemas defined in configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (Config::get('database.default') !== 'pgsql') {
            $this->error('❌ This command only works with PostgreSQL database');
            return self::FAILURE;
        }

        $schemas = $this->option('schema')
            ? [$this->option('schema')]
            : array_keys(Config::get('schemas.schemas', []));

        $this->info('🔧 Creating schemas...');
        $this->newLine();

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($schemas as $schema) {
            $result = $this->createSchema($schema);

            if ($result === 'created') {
                $created++;
            } elseif ($result === 'skipped') {
                $skipped++;
            } else {
                $errors++;
            }
        }

        $this->newLine();
        $this->info("✅ Schema creation completed");
        $this->line("   Created: {$created}");

        if ($skipped > 0) {
            $this->line("   Skipped: {$skipped}");
        }

        if ($errors > 0) {
            $this->line("   Errors: {$errors}");
        }

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Create a single schema
     *
     * @param string $schema Schema name
     * @return string Result status: 'created', 'skipped', or 'error'
     */
    protected function createSchema(string $schema): string
    {
        try {
            // Validate schema name to prevent SQL injection
            if (!preg_match('/^[a-z][a-z0-9_]*$/', $schema)) {
                $this->error("  ❌ Invalid schema name '{$schema}'. Schema names must start with a letter and contain only lowercase letters, numbers, and underscores.");
                return 'error';
            }

            // Use a connection without search path interference
            // Get a fresh connection to avoid search path issues
            $connection = DB::connection('pgsql');
            
            // Temporarily set search path to public only to avoid issues with non-existent schemas
            $connection->statement("SET search_path TO public");

            // Check if schema exists
            $exists = $connection->selectOne(
                "SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?",
                [$schema]
            );

            if ($exists && !$this->option('force')) {
                $this->warn("  ⚠️  Schema '{$schema}' already exists (use --force to recreate)");
                return 'skipped';
            }

            // Drop if force option is used
            if ($exists && $this->option('force')) {
                $connection->statement("DROP SCHEMA IF EXISTS \"{$schema}\" CASCADE");
                $this->line("  🗑️  Dropped existing schema '{$schema}'");
            }

            // Create schema (quote identifier to be safe)
            $connection->statement("CREATE SCHEMA IF NOT EXISTS \"{$schema}\"");

            // Grant permissions
            $user = Config::get('database.connections.pgsql.username');
            if ($user) {
                // Quote user identifier
                $quotedUser = "\"{$user}\"";
                $connection->statement("GRANT ALL PRIVILEGES ON SCHEMA \"{$schema}\" TO {$quotedUser}");
                $connection->statement("ALTER DEFAULT PRIVILEGES IN SCHEMA \"{$schema}\" GRANT ALL ON TABLES TO {$quotedUser}");
                $connection->statement("ALTER DEFAULT PRIVILEGES IN SCHEMA \"{$schema}\" GRANT ALL ON SEQUENCES TO {$quotedUser}");
            }

            $this->info("  ✅ Created schema '{$schema}'");

            // Show description
            $description = Config::get("schemas.schemas.{$schema}.description");
            if ($description) {
                $this->line("     {$description}");
            }

            // Show table count
            $tableCount = count(Config::get("schemas.schemas.{$schema}.tables", []));
            $this->line("     Configured tables: {$tableCount}");

            return 'created';
        } catch (\PDOException $e) {
            $this->error("  ❌ Database error creating schema '{$schema}': {$e->getMessage()}");
            return 'error';
        } catch (\Exception $e) {
            $this->error("  ❌ Failed to create schema '{$schema}': {$e->getMessage()}");
            return 'error';
        }
    }
}
