<?php

namespace App\Listeners;

use App\Events\ProductCreated;
use App\Events\ProductStatusChanged;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendProductNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    /**
     * Create the event listener.
     *
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        try {
            if ($event instanceof ProductCreated) {
                $this->handleProductCreated($event);
            } elseif ($event instanceof ProductStatusChanged) {
                $this->handleProductStatusChanged($event);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send product notification', [
                'event' => get_class($event),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle product created event
     *
     * @param ProductCreated $event
     * @return void
     */
    protected function handleProductCreated(ProductCreated $event)
    {
        $product = $event->product;
        $isFirstProduct = $event->isFirstProduct;

        // Send first product notification if this is the seller's first product
        if ($isFirstProduct) {
            $this->notificationService->notifyFirstProductSubmitted($product);

            Log::info('First product submission notification sent', [
                'product_id' => $product->id,
                'seller_id' => $product->user_id,
                'product_name' => $product->name
            ]);
        }

        // Note: We could also send admin notifications here for new products
        // $this->notificationService->notifyAdminNewProduct($product);
    }

    /**
     * Handle product status changed event
     *
     * @param ProductStatusChanged $event
     * @return void
     */
    protected function handleProductStatusChanged(ProductStatusChanged $event)
    {
        $product = $event->product;
        $oldStatus = $event->oldStatus;
        $newStatus = $event->newStatus;
        $denialReason = $event->denialReason;

        // Send appropriate notification based on status change
        if ($newStatus === 'active' && $oldStatus === 'draft') {
            // Product approved
            $this->notificationService->notifyProductApproval($product, true);
            
            Log::info('Product approval notification sent', [
                'product_id' => $product->id,
                'seller_id' => $product->user_id,
                'product_name' => $product->name
            ]);
        } elseif ($newStatus === 'denied') {
            // Product denied
            $this->notificationService->notifyProductDenied($product, $denialReason);
            
            Log::info('Product denial notification sent', [
                'product_id' => $product->id,
                'seller_id' => $product->user_id,
                'product_name' => $product->name,
                'reason' => $denialReason
            ]);
        }
    }
}
