<?php

namespace Modules\Shared\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\Shared\Services\RateLimitService;
use Modules\Shared\Services\IPWhitelistService;
use Modules\Shared\Services\AuditService;
use Modules\Shared\Services\CacheService;
use Modules\Shared\Services\QueueService;
use Modules\Shared\Services\ETagService;
use Modules\Shared\Services\S3Service;
use Modules\Shared\Services\ResponseWrapperService;

/**
 * Shared Service Provider
 *
 * Registers all shared services, publishes configuration,
 * and registers middleware aliases.
 *
 * @package Modules\Shared\Providers
 * @version 1.0.0
 */
class SharedServiceProvider extends ServiceProvider
{
    /**
     * Register services
     *
     * @return void
     */
    public function register(): void
    {
        // Merge module configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/shared.php',
            'shared'
        );

        // Register services as singletons
        $this->app->singleton(RateLimitService::class);
        $this->app->singleton(IPWhitelistService::class);
        $this->app->singleton(AuditService::class);
        $this->app->singleton(CacheService::class);
        $this->app->singleton(QueueService::class);
        $this->app->singleton(ETagService::class);
        $this->app->singleton(S3Service::class);
        $this->app->singleton(ResponseWrapperService::class);
    }

    /**
     * Bootstrap services
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerMiddlewareAliases();

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/shared.php' => config_path('shared.php'),
        ], 'shared-config');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/Migrations');
    }

    /**
     * Register routes
     *
     * @return void
     */
    protected function registerRoutes(): void
    {
        Route::prefix('api/v1')
            ->name('v1.')
            ->middleware(['api', 'shared.rate_limit'])
            ->group(function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            });

        $this->loadRoutesFrom(__DIR__ . '/../routes/console.php');
    }

    /**
     * Register middleware aliases
     *
     * @return void
     */
    protected function registerMiddlewareAliases(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware(
            'shared.rate_limit',
            \Modules\Shared\Http\Middleware\RateLimitMiddleware::class
        );

        $router->aliasMiddleware(
            'shared.ip_whitelist',
            \Modules\Shared\Http\Middleware\IPWhitelistMiddleware::class
        );

        $router->aliasMiddleware(
            'shared.audit',
            \Modules\Shared\Http\Middleware\AuditLogMiddleware::class
        );

        $router->aliasMiddleware(
            'shared.etag',
            \Modules\Shared\Http\Middleware\ETagMiddleware::class
        );
    }
}

