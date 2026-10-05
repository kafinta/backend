<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ImprovedController;
use App\Http\Resources\CheckoutResource;
use App\Http\Resources\OrderResource;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PaystackPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends ImprovedController
{
    protected $cartService;
    protected $orderService;
    protected $paymentService;

    /**
     * Create a new controller instance.
     *
     * @param CartService $cartService
     * @param OrderService $orderService
     */
    public function __construct(CartService $cartService, OrderService $orderService, PaystackPaymentService $paymentService)
    {
        $this->cartService = $cartService;
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
    }

    /**
     * Calculate order totals (shipping, tax, etc.)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculateTotals(Request $request)
    {
        try {
            $shippingMethod = $request->input('shipping_method', config('checkout.default_shipping_method'));
            $shipping = config("checkout.shipping_methods.{$shippingMethod}");

            if (!$shipping) {
                return $this->respondWithError('Invalid shipping method', 422);
            }

            $sessionId = $this->extractSessionId($request);
            $cartContents = $this->cartService->getCartContents($sessionId);

            if (empty($cartContents['items'])) {
                return $this->respondWithError('Your cart is empty', 400);
            }

            // Create checkout data object for resource
            $checkoutData = (object) [
                'subtotal' => $cartContents['total_price'],
                'tax' => 0,
                'shipping' => $shipping['price'],
                'total' => round($cartContents['total_price'] + $shipping['price'], 2),
                'items' => $cartContents['items'],
                'item_count' => $cartContents['item_count'],
                'total_quantity' => $cartContents['total_quantity'] ?? 0,
                'shipping_methods' => array_values(config('checkout.shipping_methods', [])),
                'payment_methods' => config('checkout.payment_methods', []),
                'can_checkout' => true,
                'checkout_errors' => []
            ];

            return $this->respondWithSuccess('Order totals calculated', 200, new CheckoutResource($checkoutData));
        } catch (\Exception $e) {
            return $this->respondWithError('Error calculating totals: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Place an order
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function placeOrder(Request $request)
    {
        $order = null;

        try {
            $validator = Validator::make($request->all(), [
                'shipping_name' => 'required|string|max:255',
                'shipping_address' => 'required|string|max:255',
                'shipping_city' => 'required|string|max:255',
                'shipping_state' => 'required|string|max:255',
                'shipping_postal_code' => 'required|string|max:20',
                'shipping_country' => 'required|string|max:255',
                'shipping_phone' => 'required|string|max:20',
                'shipping_method' => 'nullable|string',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors(), 422);
            }

            if ($request->filled('shipping_method') &&
                !array_key_exists($request->input('shipping_method'), config('checkout.shipping_methods', []))) {
                return $this->respondWithError('Invalid shipping method', 422);
            }

            $sessionId = $this->extractSessionId($request);
            $cart = $this->cartService->getCurrentCart($sessionId);

            if (!$cart || !$cart->cartItems()->exists()) {
                return $this->respondWithError('Your cart is empty', 400);
            }

            $order = $this->orderService->createOrder($request->all(), $sessionId);
            $payment = $this->paymentService->initialize($order);

            // Load relationships for resource
            $order->load(['orderItems.product', 'orderItems.variant', 'payment']);
            $order->setRelation('payment', $payment);

            // Add computed fields
            $order->item_count = $order->orderItems->count();
            $order->total_quantity = $order->orderItems->sum('quantity');
            $order->can_cancel = $order->status === 'pending';

            return $this->respondWithSuccess('Order placed successfully', 201, new OrderResource($order));
        } catch (\Exception $e) {
            if ($order && $order->exists && !$order->paid_at && $order->status === 'pending') {
                try {
                    $this->orderService->cancelOrder($order->id);
                } catch (\Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            if (str_contains($e->getMessage(), 'must be authenticated') ||
                str_contains($e->getMessage(), 'Cart is empty') ||
                str_contains($e->getMessage(), 'no longer available') ||
                str_contains($e->getMessage(), 'Insufficient stock')) {
                return $this->respondWithError($e->getMessage(), 409);
            }

            if (str_contains($e->getMessage(), 'Paystack')) {
                return $this->respondWithError($e->getMessage(), 502);
            }

            if (str_contains($e->getMessage(), 'Invalid shipping method')) {
                return $this->respondWithError($e->getMessage(), 422);
            }

            return $this->respondWithError('Error placing order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get available shipping methods
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getShippingMethods()
    {
        return $this->respondWithSuccess('Shipping methods retrieved', 200, [
            'shipping_methods' => array_values(config('checkout.shipping_methods', [])),
        ]);
    }

    /**
     * Get available payment methods
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentMethods()
    {
        return $this->respondWithSuccess('Payment methods retrieved', 200, [
            'payment_methods' => config('checkout.payment_methods', []),
        ]);
    }

    /**
     * Extract the cart session ID from the request
     *
     * @param Request $request
     * @return string|null
     */
    protected function extractSessionId(Request $request)
    {
        // Check for session_id in the request headers
        if ($request->hasHeader('X-Cart-Session')) {
            return $request->header('X-Cart-Session');
        }

        // Check for session_id in the request parameters
        if ($request->has('session_id')) {
            return $request->input('session_id');
        }

        // Check for session_id in cookies
        if ($request->hasCookie('cart_session_id')) {
            return $request->cookie('cart_session_id');
        }

        return null;
    }


}
