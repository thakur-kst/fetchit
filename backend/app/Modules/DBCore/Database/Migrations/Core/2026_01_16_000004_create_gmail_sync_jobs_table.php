<?php

use Modules\SchemaMgr\Support\Schema as SchemaHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

/**
 * Gmail Sync Jobs Table Migration
 * 
 * Tracks Gmail sync job progress for polling.
 * Created when sync is triggered, updated by queue workers.
 * 
 * Schema: fetchit
 */
return new class extends Migration {
    public function up(): void
    {
        $fetchitSchema = Config::get('dbcore.fetchit_schema', 'fetchit');

        SchemaHelper::createInSchema($fetchitSchema, 'gmail_sync_jobs', function (Blueprint $table) use ($fetchitSchema) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('gmail_account_id')->notNull();
            $table->integer('total_emails')->default(0);
            $table->integer('processed_emails')->default(0);
            $table->integer('new_orders')->default(0);
            $table->string('status', 20)->default('processing')->comment('processing, completed, failed');
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('gmail_account_id')
                ->references('id')
                ->on("{$fetchitSchema}.gmail_accounts")
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Check constraint for status
            DB::statement("ALTER TABLE {$fetchitSchema}.gmail_sync_jobs ADD CONSTRAINT chk_gmail_sync_jobs_status CHECK (status IN ('processing', 'completed', 'failed'))");

            // Indexes
            $table->index('gmail_account_id', 'idx_gmail_sync_jobs_account');
            $table->index(['gmail_account_id', 'status'], 'idx_gmail_sync_jobs_status');
            $table->index('created_at', 'idx_gmail_sync_jobs_created');
        });
    }

    public function down(): void
    {
        $fetchitSchema = Config::get('dbcore.fetchit_schema', 'fetchit');
        SchemaHelper::dropFromSchema($fetchitSchema, 'gmail_sync_jobs');
    }
};
