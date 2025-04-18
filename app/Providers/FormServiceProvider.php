<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\MultistepFormService;
use App\Services\MultistepFormServiceV2;

class FormServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(MultistepFormService::class, function ($app) {
            return new MultistepFormService();
        });

        $this->app->singleton(MultistepFormServiceV2::class, function ($app) {
            return new MultistepFormServiceV2();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
