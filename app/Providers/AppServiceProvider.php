<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Services\FileService;
use App\Services\MultistepFormService;
use App\Services\ProductImageService;
use App\Services\ProductService;
use App\Services\ProductAttributeService;
use App\Services\VariantService;
use App\Services\CartService;
use App\Services\OrderService;

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
            return new MultistepFormService($app->make(FileService::class));
        });

        // Product services
        $this->app->singleton(ProductImageService::class, function ($app) {
            return new ProductImageService($app->make(FileService::class));
        });

        $this->app->singleton(ProductAttributeService::class, function ($app) {
            return new ProductAttributeService($app->make(MultistepFormService::class));
        });

        $this->app->singleton(ProductService::class, function ($app) {
            return new ProductService(
                $app->make(ProductImageService::class),
                $app->make(ProductAttributeService::class),
                $app->make(FileService::class),
                $app->make(VariantService::class)
            );
        });

        // Variant service
        $this->app->singleton(VariantService::class, function ($app) {
            return new VariantService();
        });

        // Cart service
        $this->app->singleton(CartService::class, function ($app) {
            return new CartService();
        });

        // Order service
        $this->app->singleton(OrderService::class, function ($app) {
            return new OrderService($app->make(CartService::class));
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
