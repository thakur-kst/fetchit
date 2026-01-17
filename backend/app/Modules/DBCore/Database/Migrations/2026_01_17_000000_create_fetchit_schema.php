<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

/**
 * Create FetchIt Schema Migration
 * 
 * Creates the single 'fetchit' schema for all application tables.
 * This replaces the previous multi-schema architecture (core, customer_portal).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $fetchitSchema = Config::get('dbcore.fetchit_schema', 'fetchit');

        // Create fetchit schema
        DB::statement("CREATE SCHEMA IF NOT EXISTS {$fetchitSchema}");

        // Grant usage on schema
        DB::statement("GRANT USAGE ON SCHEMA {$fetchitSchema} TO CURRENT_USER");

        // Grant all privileges on schema
        DB::statement("GRANT ALL PRIVILEGES ON SCHEMA {$fetchitSchema} TO CURRENT_USER");

        // Grant all privileges on all tables in schema
        DB::statement("GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA {$fetchitSchema} TO CURRENT_USER");

        // Grant all privileges on all sequences in schema
        DB::statement("GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA {$fetchitSchema} TO CURRENT_USER");

        // Set default privileges for future tables
        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA {$fetchitSchema} GRANT ALL ON TABLES TO CURRENT_USER");

        // Set default privileges for future sequences
        DB::statement("ALTER DEFAULT PRIVILEGES IN SCHEMA {$fetchitSchema} GRANT ALL ON SEQUENCES TO CURRENT_USER");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $fetchitSchema = Config::get('dbcore.fetchit_schema', 'fetchit');

        // Drop schema (CASCADE will drop all objects in the schema)
        DB::statement("DROP SCHEMA IF EXISTS {$fetchitSchema} CASCADE");
    }
};
