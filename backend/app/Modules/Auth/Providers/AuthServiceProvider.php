<?php

namespace Modules\Auth\Providers;

use Modules\Auth\Http\Middleware\AuthMiddleware;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/auth.php', 'auth');
        $this->mergeConfigFrom(__DIR__ . '/../config/response_wrapper.php', 'auth.response_wrapper');

        // Register repositories
        $this->registerRepositories();
    }

    /**
     * Register repositories
     *
     * @return void
     */
    private function registerRepositories(): void
    {
        // Lazy binding - repositories created only when first needed
        $this->app->singleton(
            \Modules\Auth\Contracts\UserRepositoryInterface::class,
            \Modules\Auth\Repositories\UserRepository::class
        );
    }

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerMiddleware();
        $this->registerRoutes();
        $this->registerMigrations();
        $this->registerCommands();
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../config/auth.php' => config_path('auth.php'),
        ], 'auth-config');
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('auth.middleware', AuthMiddleware::class);
        $router->aliasMiddleware('shared.response_wrapper', \Modules\Shared\Http\Middleware\ResponseWrapperMiddleware::class);
    }

    protected function registerRoutes(): void
    {
        Route::prefix('api/v1')
            ->name('v1.')
            ->middleware(['api', 'shared.rate_limit'])
            ->group(function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            });

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }

    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/Migrations');
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Commands can be registered here
            ]);
        }
    }
}
