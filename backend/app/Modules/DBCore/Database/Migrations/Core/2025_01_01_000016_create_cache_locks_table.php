<?php

use Modules\SchemaMgr\Support\Schema as SchemaHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Cache Locks Table Migration
 *
 * Creates the cache_locks table in the public schema.
 * This is Laravel's standard table for cache locking.
 *
 * Schema: public
 * Purpose: Store cache lock data
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        SchemaHelper::createInSchema('public', 'cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        SchemaHelper::dropFromSchema('public', 'cache_locks');
    }
};

