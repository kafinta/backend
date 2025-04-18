<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Services\FileService;
use App\Services\MultistepFormService;
use App\Services\MultistepFormServiceV2;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register services as singletons

        // File handling service
        $this->app->singleton(FileService::class, function ($app) {
            return new FileService();
        });

        // Form services
        $this->app->singleton(MultistepFormService::class, function ($app) {
            return new MultistepFormService();
        });

        $this->app->singleton(MultistepFormServiceV2::class, function ($app) {
            return new MultistepFormServiceV2();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(UrlGenerator $url)
    {
        if (env('APP_ENV') == 'production') {
            $url->forceScheme('https');
        }
    }
}
