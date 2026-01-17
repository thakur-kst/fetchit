<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Modules\SchemaMgr\Support\Schema as SchemaHelper;

return new class extends Migration {

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        SchemaHelper::createInSchema('public', 'telescope_entries', function (Blueprint $table) {
            $table->bigIncrements('sequence');
            $table->uuid('uuid');
            $table->uuid('batch_id');
            $table->string('family_hash')->nullable();
            $table->boolean('should_display_on_index')->default(true);
            $table->string('type', 20);
            $table->longText('content');
            $table->dateTime('created_at')->nullable();
            $table->unique('uuid');
            $table->index('batch_id');
            $table->index('family_hash');
            $table->index('created_at');
            $table->index(['type', 'should_display_on_index']);
        });

        SchemaHelper::createInSchema('public', 'telescope_entries_tags', function (Blueprint $table) {
            $table->uuid('entry_uuid')->references('uuid')->on('public.telescope_entries')->onDelete('cascade');
            $table->string('tag');
            $table->primary(['entry_uuid', 'tag']);
            $table->index('tag');
        });

        SchemaHelper::createInSchema('public', 'telescope_monitoring', function (Blueprint $table) {
            $table->string('tag')->primary();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        SchemaHelper::dropFromSchema('public', 'telescope_entries_tags');
        SchemaHelper::dropFromSchema('public', 'telescope_entries');
        SchemaHelper::dropFromSchema('public', 'telescope_monitoring');
    }
};