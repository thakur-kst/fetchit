<?php

use Modules\SchemaMgr\Support\Schema as SchemaHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Failed Jobs Table Migration
 *
 * Creates the failed_jobs table in the public schema.
 * This is Laravel's standard table for failed queue jobs.
 *
 * Schema: public
 * Purpose: Store failed queue jobs
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        SchemaHelper::createInSchema('public', 'failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        SchemaHelper::dropFromSchema('public', 'failed_jobs');
    }
};

