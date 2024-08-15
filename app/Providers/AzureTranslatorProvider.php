<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AzureTranslatorService;

class AzureTranslatorProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AzureTranslatorService::class, function ($app) {
            return new AzureTranslatorService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
