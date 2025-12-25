<?php

namespace Azeemade\BulkUpload;

use Illuminate\Support\ServiceProvider;

class BulkUploadServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/bulk-upload.php',
            'bulk-upload'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/bulk-upload.php' => config_path('bulk-upload.php'),
        ], 'bulk-upload-config');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Azeemade\BulkUpload\Console\Commands\PruneBulkUploads::class,
            ]);
        }
    }
}
