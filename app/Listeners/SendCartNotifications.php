<?php

namespace App\Listeners;

use App\Events\CartAbandoned;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendCartNotifications implements ShouldQueue
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
     * @param  CartAbandoned  $event
     * @return void
     */
    public function handle(CartAbandoned $event)
    {
        try {
            $cart = $event->cart;

            // Only send notification if cart has items and belongs to a user
            if ($cart->user_id && $cart->cartItems->count() > 0) {
                $this->notificationService->notifyAbandonedCart($cart);

                Log::info('Abandoned cart notification sent', [
                    'cart_id' => $cart->id,
                    'user_id' => $cart->user_id,
                    'item_count' => $cart->cartItems->count()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send cart notification', [
                'cart_id' => $event->cart->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
