<?php

namespace Database\Seeders;

use Modules\DBCore\Database\Seeders\DatabaseSeeder as DBCoreDatabaseSeeder;
use Illuminate\Database\Seeder;

/**
 * Root Database Seeder
 *
 * This is the main entry point for Laravel's seeder system.
 * It delegates to the DBCore module's DatabaseSeeder.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(DBCoreDatabaseSeeder::class);
    }
}
