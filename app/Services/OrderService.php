<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Create a new order from the user's cart
     *
     * @param array $shippingDetails
     * @param string|null $sessionId
     * @return Order
     */
    public function createOrder(array $shippingDetails, $sessionId = null)
    {
        // Start a database transaction
        return DB::transaction(function () use ($shippingDetails, $sessionId) {
            // Get the current cart
            $cart = $this->cartService->getCurrentCart($sessionId);

            if (!$cart || $cart->cartItems->isEmpty()) {
                throw new \Exception('Cannot create order: Cart is empty');
            }

            // Orders are only available to authenticated customers.
            $user = Auth::user();
            if (!$user) {
                throw new \Exception('You must be authenticated to place an order');
            }
            $userId = $user->id;

            // Only check for seller's own products if the user is a seller
            if ($user && $user->isSeller()) {
                // Get all products in the cart
                $sellerProducts = [];
                foreach ($cart->cartItems as $cartItem) {
                    $product = $cartItem->product;

                    // Check if the user is the seller of this product
                    if ($product->user_id === $userId) {
                        $sellerProducts[] = $product->name;
                    }
                }

                // If there are any products sold by the user, throw an exception
                if (!empty($sellerProducts)) {
                    throw new \Exception('As a seller, you cannot order your own products: ' . implode(', ', $sellerProducts));
                }
            }

            // Re-read inventory and prices inside the transaction so checkout cannot
            // use stale cart data or oversell concurrent orders.
            $subtotal = 0;
            $orderItems = [];
            foreach ($cart->cartItems as $cartItem) {
                $product = Product::whereKey($cartItem->product_id)->lockForUpdate()->first();
                $variant = $cartItem->variant_id
                    ? Variant::whereKey($cartItem->variant_id)->lockForUpdate()->first()
                    : null;

                if (!$product || $product->status !== 'active' || ($cartItem->variant_id && (!$variant || $variant->product_id !== $product->id))) {
                    throw new \Exception('One or more items in your cart are no longer available');
                }

                $stockItem = $variant ?: $product;
                if (!$stockItem->isInStock($cartItem->quantity)) {
                    throw new \Exception("Insufficient stock for {$product->name}");
                }

                $price = $variant ? (float) $variant->price : $product->getDiscountedPrice();
                $lineSubtotal = round($price * $cartItem->quantity, 2);
                $subtotal += $lineSubtotal;
                $orderItems[] = compact('product', 'variant', 'cartItem', 'price', 'lineSubtotal');
            }
            $subtotal = round($subtotal, 2);

            $shippingMethod = $shippingDetails['shipping_method'] ?? config('checkout.default_shipping_method');
            $shippingOption = config("checkout.shipping_methods.{$shippingMethod}");
            if (!$shippingOption) {
                throw new \Exception('Invalid shipping method');
            }
            $shippingCost = (float) $shippingOption['price'];
            $total = round($subtotal + $shippingCost, 2);

            // Create the order
            $order = new Order([
                'user_id' => $userId,
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => 0, // We'll implement tax calculation later
                'shipping_cost' => $shippingCost,
                'shipping_method' => $shippingMethod,
                'total' => $total,
                'currency' => config('services.paystack.currency', 'NGN'),
                'shipping_name' => $shippingDetails['shipping_name'],
                'shipping_address' => $shippingDetails['shipping_address'],
                'shipping_city' => $shippingDetails['shipping_city'],
                'shipping_state' => $shippingDetails['shipping_state'],
                'shipping_postal_code' => $shippingDetails['shipping_postal_code'],
                'shipping_country' => $shippingDetails['shipping_country'],
                'shipping_phone' => $shippingDetails['shipping_phone'],
                'notes' => $shippingDetails['notes'] ?? null,
            ]);

            $order->save();

            // Create order items from cart items
            foreach ($orderItems as $item) {
                $product = $item['product'];
                $variant = $item['variant'];
                $cartItem = $item['cartItem'];
                $price = $item['price'];
                $lineSubtotal = $item['lineSubtotal'];
                $orderItem = new OrderItem([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant ? $variant->id : null,
                    'quantity' => $cartItem->quantity,
                    'price' => $price,
                    'subtotal' => $lineSubtotal,
                    'product_name' => $product->name,
                    'variant_name' => $variant ? $variant->name : null,
                ]);

                $orderItem->save();

                $stockItem = $variant ?: $product;
                if ($stockItem->manage_stock) {
                    $stockItem->decrement('stock_quantity', $cartItem->quantity);
                }
            }

            // Clear the cart
            $this->cartService->clearCart($sessionId);

            // Fire order placed event
            event(new OrderPlaced($order));

            return $order;
        });
    }

    /**
     * Generate a unique order number
     *
     * @return string
     */
    protected function generateOrderNumber()
    {
        do {
            $orderNumber = 'ORD' . Str::upper(Str::random(14));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Get an order by ID
     *
     * @param int $orderId
     * @return Order
     */
    public function getOrder($orderId)
    {
        return Order::with('orderItems')->findOrFail($orderId);
    }

    public function getOrderByNumber(string $orderNumber): Order
    {
        return Order::with('orderItems')
            ->where('order_number', strtoupper($orderNumber))
            ->firstOrFail();
    }

    public function getUserOrderByNumber(string $orderNumber, int $userId): Order
    {
        return Order::with('orderItems')
            ->where('user_id', $userId)
            ->where('order_number', strtoupper($orderNumber))
            ->firstOrFail();
    }

    /**
     * Get all orders for the current user
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserOrders()
    {
        return Order::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Cancel an order
     *
     * @param int $orderId
     * @return Order
     */
    public function cancelOrder($orderId)
    {
        return DB::transaction(function () use ($orderId) {
            $order = Order::with('orderItems')->lockForUpdate()->findOrFail($orderId);

            if ($order->status !== 'pending') {
                throw new \Exception('Only pending orders can be cancelled');
            }

            foreach ($order->orderItems as $orderItem) {
                $stockItem = $orderItem->variant_id
                    ? Variant::whereKey($orderItem->variant_id)->lockForUpdate()->first()
                    : Product::whereKey($orderItem->product_id)->lockForUpdate()->first();

                if ($stockItem && $stockItem->manage_stock) {
                    $stockItem->increment('stock_quantity', $orderItem->quantity);
                }

                $orderItem->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);
            }

            $oldStatus = $order->status;
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            event(new OrderStatusChanged($order, $oldStatus, 'cancelled'));

            return $order;
        });
    }

    public function cancelOrderByNumber(string $orderNumber): Order
    {
        $order = $this->getOrderByNumber($orderNumber);

        return $this->cancelOrder($order->id);
    }

    public function cancelUserOrderByNumber(string $orderNumber, int $userId): Order
    {
        $order = $this->getUserOrderByNumber($orderNumber, $userId);

        return $this->cancelOrder($order->id);
    }
}
