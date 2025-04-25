<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
    protected $cartService;
    protected $orderService;

    /**
     * Create a new controller instance.
     *
     * @param CartService $cartService
     * @param OrderService $orderService
     */
    public function __construct(CartService $cartService, OrderService $orderService)
    {
        $this->cartService = $cartService;
        $this->orderService = $orderService;
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
            $sessionId = $this->extractSessionId($request);
            $cartContents = $this->cartService->getCartContents($sessionId);

            if (empty($cartContents['items'])) {
                return $this->respondWithError('Your cart is empty', 400);
            }

            // For now, we're just returning the cart contents
            // In the future, we can calculate shipping and tax here
            return $this->respondWithSuccess('Order totals calculated', 200, [
                'subtotal' => $cartContents['total_price'],
                'tax' => 0,
                'shipping' => 0,
                'total' => $cartContents['total_price'],
                'items' => $cartContents['items'],
                'item_count' => $cartContents['item_count']
            ]);
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
        try {
            $validator = Validator::make($request->all(), [
                'shipping_name' => 'required|string|max:255',
                'shipping_address' => 'required|string|max:255',
                'shipping_city' => 'required|string|max:255',
                'shipping_state' => 'required|string|max:255',
                'shipping_postal_code' => 'required|string|max:20',
                'shipping_country' => 'required|string|max:255',
                'shipping_phone' => 'required|string|max:20',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors(), 422);
            }

            $sessionId = $this->extractSessionId($request);
            $cartContents = $this->cartService->getCartContents($sessionId);

            if (empty($cartContents['items'])) {
                return $this->respondWithError('Your cart is empty', 400);
            }

            $order = $this->orderService->createOrder($request->all(), $sessionId);

            return $this->respondWithSuccess('Order placed successfully', 201, [
                'order' => $order,
                'order_number' => $order->order_number,
                'total' => $order->total,
                'status' => $order->status
            ]);
        } catch (\Exception $e) {
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
        // For now, we'll just return some dummy shipping methods
        // In the future, this could be fetched from a database or shipping API
        $shippingMethods = [
            [
                'id' => 'standard',
                'name' => 'Standard Shipping',
                'description' => '3-5 business days',
                'price' => 5.99
            ],
            [
                'id' => 'express',
                'name' => 'Express Shipping',
                'description' => '1-2 business days',
                'price' => 12.99
            ],
            [
                'id' => 'free',
                'name' => 'Free Shipping',
                'description' => '5-7 business days',
                'price' => 0.00
            ]
        ];

        return $this->respondWithSuccess('Shipping methods retrieved', 200, [
            'shipping_methods' => $shippingMethods
        ]);
    }

    /**
     * Get available payment methods
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentMethods()
    {
        // For now, we'll just return some dummy payment methods
        // In the future, this could be fetched from a database or payment gateway
        $paymentMethods = [
            [
                'id' => 'cash',
                'name' => 'Cash on Delivery',
                'description' => 'Pay when you receive your order'
            ],
            [
                'id' => 'bank_transfer',
                'name' => 'Bank Transfer',
                'description' => 'Pay via bank transfer'
            ]
        ];

        return $this->respondWithSuccess('Payment methods retrieved', 200, [
            'payment_methods' => $paymentMethods
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

    /**
     * Return a success response
     *
     * @param string $message
     * @param int $statusCode
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithSuccess($message, $statusCode = 200, $data = [])
    {
        return response()->json([
            'status' => 'success',
            'status_code' => $statusCode,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Return an error response
     *
     * @param string|array $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithError($message, $statusCode = 400)
    {
        return response()->json([
            'status' => 'fail',
            'status_code' => $statusCode,
            'message' => $message
        ], $statusCode);
    }
}
