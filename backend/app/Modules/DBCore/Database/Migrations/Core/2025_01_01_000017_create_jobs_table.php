<?php

use Modules\SchemaMgr\Support\Schema as SchemaHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Jobs Table Migration
 *
 * Creates the jobs table in the public schema.
 * This is Laravel's standard table for queue jobs.
 *
 * Schema: public
 * Purpose: Store queued jobs
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        SchemaHelper::createInSchema('public', 'jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        SchemaHelper::dropFromSchema('public', 'jobs');
    }
};

