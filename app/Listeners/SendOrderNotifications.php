<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Models\Product;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOrderNotifications implements ShouldQueue
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
            if ($event instanceof OrderPlaced) {
                $this->handleOrderPlaced($event);
            } elseif ($event instanceof OrderStatusChanged) {
                $this->handleOrderStatusChanged($event);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send order notification', [
                'event' => get_class($event),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle order placed event
     *
     * @param OrderPlaced $event
     * @return void
     */
    protected function handleOrderPlaced(OrderPlaced $event)
    {
        $order = $event->order;

        // Notify customer
        $this->notificationService->notifyOrderPlaced($order);

        // Notify sellers
        $this->notifyRelevantSellers($order);

        Log::info('Order placed notifications sent', [
            'order_id' => $order->id,
            'order_number' => $order->order_number
        ]);
    }

    /**
     * Handle order status changed event
     *
     * @param OrderStatusChanged $event
     * @return void
     */
    protected function handleOrderStatusChanged(OrderStatusChanged $event)
    {
        $order = $event->order;
        $oldStatus = $event->oldStatus;
        $newStatus = $event->newStatus;

        // Notify customer about status change
        $this->notificationService->notifyOrderStatusChange($order, $oldStatus, $newStatus);

        Log::info('Order status change notification sent', [
            'order_id' => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ]);
    }

    /**
     * Notify sellers who have products in the order
     *
     * @param \App\Models\Order $order
     * @return void
     */
    protected function notifyRelevantSellers($order)
    {
        // Get unique seller IDs from order items
        $sellerIds = $order->orderItems()
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->pluck('products.user_id')
            ->unique();

        // Send notification to each seller
        foreach ($sellerIds as $sellerId) {
            $this->notificationService->notifySellerNewOrder($order, $sellerId);
        }
    }
}
