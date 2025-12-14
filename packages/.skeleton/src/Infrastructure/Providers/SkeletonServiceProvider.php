<?php

declare(strict_types=1);

namespace Larasuite\Skeleton\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class SkeletonServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../../config/skeleton.php',
            'skeleton'
        );

        $this->registerRepositories();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../../config/skeleton.php' => config_path('skeleton.php'),
            ], 'skeleton-config');

            $this->publishes([
                __DIR__.'/../../../database/migrations' => database_path('migrations'),
            ], 'skeleton-migrations');
        }
    }

    /**
     * Register repository bindings.
     */
    private function registerRepositories(): void
    {
        // Example:
        // $this->app->bind(
        //     \Larasuite\Skeleton\Domain\Repositories\ExampleRepositoryInterface::class,
        //     \Larasuite\Skeleton\Infrastructure\Persistence\EloquentExampleRepository::class
        // );
    }
}
