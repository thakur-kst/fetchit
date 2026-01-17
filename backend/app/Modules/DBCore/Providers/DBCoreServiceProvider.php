<?php

namespace Modules\DBCore\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * DBCore Service Provider
 *
 * Centralized database migrations and models module that organizes all
 * database structure definitions by schema and purpose:
 * - Core schema migrations & models (master data)
 * - Customer Portal schema migrations & models (tenant data)
 * - Framework migrations (Laravel system tables)
 *
 * This module serves as the single source of truth for all database
 * structure definitions and ORM models across the application.
 *
 * @package DBCore
 * @version 1.0.0
 */
class DBCoreServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register dbcore configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/dbcore.php',
            'dbcore'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register migrations from all subdirectories
        $this->loadMigrationsFrom([
            // Schema creation (must run first)
            __DIR__ . '/../Database/Migrations',

            // Core schema migrations (master data)
            __DIR__ . '/../Database/Migrations/Core',

            // Customer Portal schema migrations (tenant data)
            __DIR__ . '/../Database/Migrations/CustomerPortal',

            // Framework migrations (Laravel system tables)
            __DIR__ . '/../Database/Migrations/Framework',
        ]);

        // Publish migrations if needed
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../Database/Migrations' => database_path('migrations'),
            ], 'dbcore-migrations');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [];
    }
}
