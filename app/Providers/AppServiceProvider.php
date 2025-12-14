<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureStrictMode();
        $this->configureDatabaseLogging();
    }

    /**
     * Configure strict mode for Eloquent models.
     */
    private function configureStrictMode(): void
    {
        // Enable strict mode in non-production environments
        Model::shouldBeStrict(! $this->app->isProduction());

        // Always prevent lazy loading (N+1 protection)
        Model::preventLazyLoading(! $this->app->isProduction());

        // Prevent silently discarding attributes
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Prevent accessing missing attributes
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());
    }

    /**
     * Configure database query logging for development.
     */
    private function configureDatabaseLogging(): void
    {
        if ($this->app->isProduction()) {
            return;
        }

        DB::listen(static function ($query): void {
            if ($query->time > 100) {
                logger()->warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                ]);
            }
        });
    }
}
