<?php

use Modules\SchemaMgr\Support\Schema as SchemaHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

/**
 * Gmail Accounts Table Migration
 * 
 * Stores linked Gmail accounts with OAuth tokens.
 * Tokens are encrypted using Laravel's encryption.
 * 
 * Schema: fetchit
 */
return new class extends Migration {
    public function up(): void
    {
        $fetchitSchema = Config::get('dbcore.fetchit_schema', 'fetchit');

        SchemaHelper::createInSchema($fetchitSchema, 'gmail_accounts', function (Blueprint $table) use ($fetchitSchema) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('user_id')->notNull();
            $table->string('email', 255)->notNull();
            $table->string('display_name', 255)->nullable();
            $table->text('picture_url')->nullable();
            $table->text('access_token')->notNull()->comment('Encrypted access token');
            $table->text('refresh_token')->nullable()->comment('Encrypted refresh token');
            $table->string('token_type', 20)->default('Bearer');
            $table->text('scope')->nullable()->comment('OAuth scopes granted');
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('last_synced_at')->nullable()->comment('Timestamp of last successful sync');
            $table->boolean('is_active')->default(true);
            $table->string('locale', 10)->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('user_id')
                ->references('uuid')
                ->on("{$fetchitSchema}.users")
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Unique constraint: one Gmail account per user
            $table->unique(['user_id', 'email'], 'idx_gmail_accounts_user_email_unique');

            // Indexes
            $table->index('user_id', 'idx_gmail_accounts_user_id');
            $table->index('email', 'idx_gmail_accounts_email');
            $table->index(['user_id', 'last_synced_at'], 'idx_gmail_accounts_last_synced');
        });
    }

    public function down(): void
    {
        $fetchitSchema = Config::get('dbcore.fetchit_schema', 'fetchit');
        SchemaHelper::dropFromSchema($fetchitSchema, 'gmail_accounts');
    }
};
