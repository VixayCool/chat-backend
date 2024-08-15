<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AzureSummarizationService;
use App\Services\AzureTranslatorService;

class AzureSummarizationProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(AzureSummarizationService::class, function ($app){
            return new AzureSummarizationService($app->make(AzureTranslatorService::class));
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
