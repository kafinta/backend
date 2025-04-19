<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Services\FileService;
use App\Services\MultistepFormService;
use App\Services\MultistepFormServiceV2;
use App\Services\ProductImageService;
use App\Services\ProductService;
use App\Services\ProductAttributeService;

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

        // Product services
        $this->app->singleton(ProductImageService::class, function ($app) {
            return new ProductImageService($app->make(FileService::class));
        });

        $this->app->singleton(ProductAttributeService::class, function ($app) {
            return new ProductAttributeService($app->make(MultistepFormServiceV2::class));
        });

        $this->app->singleton(ProductService::class, function ($app) {
            return new ProductService(
                $app->make(ProductImageService::class),
                $app->make(ProductAttributeService::class),
                $app->make(FileService::class)
            );
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
