<?php

use Modules\SchemaMgr\Support\Schema as SchemaHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Job Batches Table Migration
 *
 * Creates the job_batches table in the public schema.
 * This is Laravel's standard table for batch job tracking.
 *
 * Schema: public
 * Purpose: Store batch job information
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        SchemaHelper::createInSchema('public', 'job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        SchemaHelper::dropFromSchema('public', 'job_batches');
    }
};

