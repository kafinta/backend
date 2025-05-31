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

            // Get the current user
            $user = Auth::user();
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

            // Calculate order totals
            $cartContents = $this->cartService->getCartContents($sessionId);
            $subtotal = $cartContents['total_price'];

            // Create the order
            $order = new Order([
                'user_id' => Auth::id(),
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax' => 0, // We'll implement tax calculation later
                'shipping_cost' => 0, // We'll implement shipping cost calculation later
                'total' => $subtotal, // For now, total = subtotal
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
            foreach ($cart->cartItems as $cartItem) {
                $product = $cartItem->product;
                $variant = $cartItem->variant;

                // Determine price (use variant price if available, otherwise product price)
                $price = $variant ? $variant->price : $product->price;

                $orderItem = new OrderItem([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant ? $variant->id : null,
                    'quantity' => $cartItem->quantity,
                    'price' => $price,
                    'subtotal' => $price * $cartItem->quantity,
                    'product_name' => $product->name,
                    'variant_name' => $variant ? $variant->name : null,
                ]);

                $orderItem->save();
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
        $prefix = 'ORD-';
        $timestamp = now()->format('Ymd');
        $random = strtoupper(Str::random(4));

        return $prefix . $timestamp . '-' . $random;
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
        $order = $this->getOrder($orderId);

        if ($order->status !== 'pending') {
            throw new \Exception('Only pending orders can be cancelled');
        }

        $oldStatus = $order->status;
        $order->status = 'cancelled';
        $order->save();

        // Fire order status changed event
        event(new OrderStatusChanged($order, $oldStatus, 'cancelled'));

        return $order;
    }
}
