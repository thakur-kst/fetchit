<?php

use Modules\SchemaMgr\Support\Schema as SchemaHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Create Password Reset Tokens Table Migration
 *
 * Creates the password_reset_tokens table in the public schema.
 * This is Laravel's standard table for password reset functionality.
 *
 * Schema: public
 * Purpose: Store password reset tokens for users
 *
 * @package DBCore
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        SchemaHelper::createInSchema('public', 'password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        SchemaHelper::dropFromSchema('public', 'password_reset_tokens');
    }
};
