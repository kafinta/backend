<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

// Product Events
use App\Events\Product\ProductCreated;
use App\Events\Product\ProductUpdated;
use App\Events\Product\AttributeAdded;
use App\Events\Product\AttributeUpdated;
use App\Events\Product\AttributeRemoved;
use App\Events\Product\SubcategoryChanged;
use App\Events\Product\ValidationFailed;

// Product Listeners
use App\Listeners\Product\UpdateProductCache;
use App\Listeners\Product\HandleAttributesForSubcategoryChange;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Product Events
        ProductCreated::class => [
            UpdateProductCache::class,
        ],

        ProductUpdated::class => [
            UpdateProductCache::class,
        ],

        AttributeAdded::class => [
            UpdateProductCache::class,
        ],

        AttributeUpdated::class => [
            UpdateProductCache::class,
        ],

        AttributeRemoved::class => [
            UpdateProductCache::class,
        ],

        SubcategoryChanged::class => [
            HandleAttributesForSubcategoryChange::class,
            UpdateProductCache::class,
        ],

        ValidationFailed::class => [],

        // Order Events
        \App\Events\OrderPlaced::class => [
            \App\Listeners\SendOrderNotifications::class,
        ],

        \App\Events\OrderStatusChanged::class => [
            \App\Listeners\SendOrderNotifications::class,
        ],

        // Cart Events
        \App\Events\CartAbandoned::class => [
            \App\Listeners\SendCartNotifications::class,
        ],

        // Product Events
        \App\Events\ProductCreated::class => [
            \App\Listeners\SendProductNotifications::class,
        ],

        \App\Events\ProductStatusChanged::class => [
            \App\Listeners\SendProductNotifications::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
