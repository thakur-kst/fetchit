<?php

namespace Modules\HealthCheck\Providers;

use Modules\HealthCheck\Http\Middleware\HealthCheckLoggingMiddleware;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * HealthCheck Module Service Provider
 *
 * Registers routes, middleware, config, migrations, and services for the HealthCheck module.
 * This makes the module completely self-contained and package-ready.
 *
 * @package HealthCheck
 * @version 1.0.0
 */
class HealthCheckServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge module configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/healthcheck.php',
            'healthcheck'
        );

        // Register module-specific bindings here if needed
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->registerMiddleware();
        $this->registerRoutes();
        $this->registerMigrations();
        $this->registerFactories();
    }

    /**
     * Register the module's configuration.
     */
    protected function registerConfig(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/healthcheck.php' => config_path('healthcheck.php'),
        ], 'healthcheck-config');
    }

    /**
     * Register the module's middleware.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        // Register module-specific middleware with an alias
        $router->aliasMiddleware('healthcheck.logging', HealthCheckLoggingMiddleware::class);
    }

    /**
     * Register the module's routes.
     */
    protected function registerRoutes(): void
    {
        // Register API routes with versioning
        Route::prefix('api/v1')
            ->name('v1.')
            ->middleware(['api', 'healthcheck.logging', 'shared.rate_limit'])  // Apply logging and rate limiting middleware to all routes
            ->group(function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            });

        // Register web routes (if needed in the future)
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Register console routes (if needed in the future)
        $this->loadRoutesFrom(__DIR__ . '/../routes/console.php');

        // Register broadcast channels (if needed in the future)
        $this->loadRoutesFrom(__DIR__ . '/../routes/channels.php');
    }

    /**
     * Register the module's migrations.
     */
    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/Migrations');
    }

    /**
     * Register the module's factories.
     */
    protected function registerFactories(): void
    {
        // Factory registration is handled automatically by Laravel
        // if the model's newFactory() method returns the correct factory
    }
}
