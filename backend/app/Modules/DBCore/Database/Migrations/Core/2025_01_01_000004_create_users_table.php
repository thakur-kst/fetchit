<?php

use Modules\SchemaMgr\Support\Schema as SchemaHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

/**
 * Users Table Migration
 *
 * Creates the users table in the public schema for FetchIt application:
 * - Incremental ID as primary key (BIGINT)
 * - UUID as unique identifier
 * - Google OAuth fields
 * - Password nullable (since we use Google OAuth)
 * - Soft deletes
 * - Global email uniqueness constraint
 *
 * Schema: public
 * Purpose: User accounts for FetchIt application
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $fetchitSchema = Config::get('dbcore.fetchit_schema', 'public');

        // Create users table in public schema
        SchemaHelper::createInSchema($fetchitSchema, 'users', function (Blueprint $table) use ($fetchitSchema) {
            $table->id()->comment('Incremental primary key');

            // UUID as unique identifier
            $table->uuid('uuid')
                ->unique()
                ->default(DB::raw('gen_random_uuid()'))
                ->comment('UUID generated at database level');

            // Basic Information
            $table->string('name');
            $table->string('first_name', 255)->nullable()->after('name');
            $table->string('last_name', 255)->nullable()->after('first_name');

            // Email
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();

            // Google OAuth fields
            $table->string('google_id', 255)->nullable()->unique()->after('email')->comment('Google OAuth user ID');
            $table->text('picture')->nullable()->after('google_id')->comment('Profile picture URL from Google');
            $table->string('locale', 10)->nullable()->after('picture')->comment('User locale preference');

            // Password (nullable since we use Google OAuth)
            $table->string('password')->nullable();
            $table->rememberToken();

            // Status
            $table->string('status', 20)->default('active')->after('last_name')->comment('active, inactive, suspended, etc.');

            // Last login
            $table->timestamp('last_login')->nullable()->after('status')->comment('Last login timestamp');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();
        });

        // Add indexes for performance
        SchemaHelper::tableInSchema($fetchitSchema, 'users', function (Blueprint $table) {
            $table->index('status', 'idx_users_status');
            $table->index('google_id', 'idx_users_google_id');
            $table->index('uuid', 'idx_users_uuid');
            $table->index('deleted_at', 'idx_users_deleted_at');

            // Global email uniqueness
            $table->unique('email', 'idx_users_email_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $fetchitSchema = Config::get('dbcore.fetchit_schema', 'public');
        SchemaHelper::dropFromSchema($fetchitSchema, 'users');
    }
};
