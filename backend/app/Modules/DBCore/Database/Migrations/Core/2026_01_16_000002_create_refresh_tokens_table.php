<?php

use Modules\SchemaMgr\Support\Schema as SchemaHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

/**
 * Refresh Tokens Table Migration
 * 
 * Stores JWT refresh tokens for user authentication.
 * Tokens are hashed with SHA-256 for security.
 * 
 * Schema: public
 */
return new class extends Migration {
    public function up(): void
    {
        $fetchitSchema = Config::get('dbcore.fetchit_schema', 'public');

        SchemaHelper::createInSchema($fetchitSchema, 'refresh_tokens', function (Blueprint $table) use ($fetchitSchema) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('user_id')->notNull();
            $table->string('token_hash', 64)->unique()->notNull()->comment('SHA-256 hash of refresh token');
            $table->string('device_name', 255)->nullable()->comment('e.g., iPhone 15 Pro, Samsung Galaxy S23');
            $table->string('device_id', 255)->nullable();
            $table->timestamp('expires_at')->notNull();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('user_id')
                ->references('uuid')
                ->on("{$fetchitSchema}.users")
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // Indexes
            $table->index('user_id', 'idx_refresh_tokens_user_id');
            $table->index('token_hash', 'idx_refresh_tokens_token_hash');
            $table->index('expires_at', 'idx_refresh_tokens_expires_at');
        });
    }

    public function down(): void
    {
        $fetchitSchema = Config::get('dbcore.fetchit_schema', 'public');
        SchemaHelper::dropFromSchema($fetchitSchema, 'refresh_tokens');
    }
};
