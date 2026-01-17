<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration is deprecated. The fetchit schema is now created
     * by the 2026_01_17_000000_create_fetchit_schema.php migration.
     * This file is kept for backward compatibility but does nothing.
     */
    public function up(): void
    {
        // Schema creation moved to 2026_01_17_000000_create_fetchit_schema.php
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema dropping handled by 2026_01_17_000000_create_fetchit_schema.php
    }
};
