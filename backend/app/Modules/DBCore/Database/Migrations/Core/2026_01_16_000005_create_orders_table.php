<?php

use Modules\SchemaMgr\Support\Schema as SchemaHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

/**
 * Orders Table Migration
 * 
 * Stores parsed orders from Gmail emails.
 * Uses JSONB for flexible metadata and items storage.
 * 
 * Schema: fetchit
 */
return new class extends Migration {
    public function up(): void
    {
        $fetchitSchema = Config::get('dbcore.fetchit_schema', 'fetchit');

        SchemaHelper::createInSchema($fetchitSchema, 'orders', function (Blueprint $table) use ($fetchitSchema) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('user_id')->notNull();
            $table->uuid('gmail_account_id')->nullable();
            $table->string('email_id', 255)->nullable()->comment('Gmail message ID');
            $table->string('order_id', 255)->nullable()->comment('Vendor order ID (e.g., Amazon order #)');
            $table->string('vendor', 100)->notNull();
            $table->string('status', 50)->notNull();
            $table->text('subject')->notNull();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->timestamp('order_date')->nullable();
            $table->timestamp('delivery_date')->nullable();
            $table->jsonb('items')->nullable()->comment('Array of product items');
            $table->jsonb('metadata')->nullable()->comment('replyTo, category, deeplink, otp, etc.');
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')
                ->references('uuid')
                ->on("{$fetchitSchema}.users")
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('gmail_account_id')
                ->references('id')
                ->on("{$fetchitSchema}.gmail_accounts")
                ->onDelete('set null')
                ->onUpdate('cascade');

            // Unique constraint to prevent duplicate email syncing
            $table->unique(['email_id', 'user_id'], 'idx_orders_email_user_unique');

            // Standard indexes
            $table->index('user_id', 'idx_orders_user_id');
            $table->index('gmail_account_id', 'idx_orders_gmail_account_id');
            $table->index('email_id', 'idx_orders_email_id');
            $table->index('vendor', 'idx_orders_vendor');
            $table->index('status', 'idx_orders_status');
            $table->index('order_date', 'idx_orders_order_date');
            $table->index(['user_id', 'order_date'], 'idx_orders_user_date');
            $table->index(['user_id', 'vendor', 'order_date'], 'idx_orders_user_vendor');
            $table->index(['user_id', 'status', 'order_date'], 'idx_orders_user_status');
        });

        // JSONB indexes for filtering (using raw SQL as Laravel doesn't support GIN indexes directly)
        DB::statement("CREATE INDEX idx_orders_metadata_replyto ON {$fetchitSchema}.orders USING gin ((metadata->'replyTo'))");
        DB::statement("CREATE INDEX idx_orders_metadata_category ON {$fetchitSchema}.orders USING gin ((metadata->'category'))");
        DB::statement("CREATE INDEX idx_orders_items ON {$fetchitSchema}.orders USING gin (items)");
    }

    public function down(): void
    {
        $fetchitSchema = Config::get('dbcore.fetchit_schema', 'fetchit');
        
        // Drop JSONB indexes first
        DB::statement("DROP INDEX IF EXISTS {$fetchitSchema}.idx_orders_metadata_replyto");
        DB::statement("DROP INDEX IF EXISTS {$fetchitSchema}.idx_orders_metadata_category");
        DB::statement("DROP INDEX IF EXISTS {$fetchitSchema}.idx_orders_items");
        
        SchemaHelper::dropFromSchema($fetchitSchema, 'orders');
    }
};
