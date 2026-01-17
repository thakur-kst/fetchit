<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Enable PostGIS Extension Migration
 *
 * Enables the PostGIS extension for PostgreSQL to support geographic data types
 * and spatial queries in the branches table (location field).
 *
 * PostGIS provides:
 * - POINT type for storing geographic coordinates
 * - Spatial indexing (GIST) for efficient geographic queries
 * - Geographic distance calculations
 * - Spatial relationship queries
 *
 * @package Tenancy
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // Enable PostGIS extension if not already enabled
            // Using raw SQL as Laravel doesn't have built-in support for extensions
            DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        } catch (\Illuminate\Database\QueryException $e) {
            // PostGIS extension not available - skip if it's not installed
            // This allows migrations to continue in environments without PostGIS
            if (str_contains($e->getMessage(), 'extension "postgis" is not available')) {
                // Log warning but don't fail migration
                \Illuminate\Support\Facades\Log::warning(
                    'PostGIS extension is not available. Skipping PostGIS migration. ' .
                    'Install PostGIS if you need geographic data support.'
                );
                return;
            }
            // Re-throw if it's a different error
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     *
     * Note: Dropping PostGIS extension will fail if any tables use PostGIS types.
     * This migration should only be rolled back if all PostGIS-dependent tables are dropped first.
     */
    public function down(): void
    {
        // Check if any tables are using PostGIS types before dropping
        $tablesUsingPostGIS = DB::select("
            SELECT DISTINCT table_name
            FROM information_schema.columns
            WHERE data_type = 'USER-DEFINED'
            AND udt_name IN ('geometry', 'geography', 'point')
        ");

        if (count($tablesUsingPostGIS) > 0) {
            throw new \RuntimeException(
                'Cannot drop PostGIS extension. The following tables are using PostGIS types: ' .
                implode(', ', array_column($tablesUsingPostGIS, 'table_name'))
            );
        }

        DB::statement('DROP EXTENSION IF EXISTS postgis CASCADE');
    }
};

