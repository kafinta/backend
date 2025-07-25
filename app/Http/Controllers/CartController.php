<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ImprovedController;
use App\Http\Resources\CartResource;
use App\Http\Resources\CartItemResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Variant;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

// NOTE: All checkout and order flows should use CheckoutController. CartController only handles cart CRUD and transfer logic.
class CartController extends ImprovedController
{
    protected $cartService;

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
     * Create a new controller instance.
     *
     * @param CartService $cartService
     */
    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }
    /**
     * Get the current cart contents
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewCart(Request $request)
    {
        try {
            $sessionId = $this->extractSessionId($request);
            $cart = $this->cartService->getCurrentCart($sessionId);

            // Load cart items with relationships for resource
            $cart->load(['cartItems.product', 'cartItems.variant']);

            // Get cart contents with totals
            $cartContents = $this->cartService->getCartContents($sessionId);

            // Add totals and counts to cart model for resource
            $cart->totals = $cartContents['totals'] ?? null;
            $cart->item_count = $cartContents['item_count'] ?? 0;
            $cart->total_quantity = $cartContents['total_quantity'] ?? 0;

            // Set a cookie with the session ID for future requests
            if (!Auth::check() && isset($cartContents['session_id'])) {
                $cookie = cookie('cart_session_id', $cartContents['session_id'], 60 * 24 * 30, null, null, null, true); // 30 days, HTTP-only
                return $this->respondWithSuccess('Cart retrieved successfully', 200, new CartResource($cart))->withCookie($cookie);
            }

            return $this->respondWithSuccess('Cart retrieved successfully', 200, new CartResource($cart));
        } catch (\Exception $e) {
            return $this->respondWithError('Error retrieving cart: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Add an item to the cart
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addToCart(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required_without:variant_id|exists:products,id',
                'variant_id' => 'required_without:product_id|exists:variants,id',
                'quantity' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors(), 422);
            }

            $quantity = $request->input('quantity', 1);
            $sessionId = $this->extractSessionId($request);

            if ($request->has('variant_id')) {
                // Add variant to cart
                $cartItem = $this->cartService->addVariantToCart(
                    $request->input('variant_id'),
                    $quantity,
                    $sessionId
                );
                $message = 'Variant added to cart';
            } else {
                // Add product to cart
                $cartItem = $this->cartService->addProductToCart(
                    $request->input('product_id'),
                    $quantity,
                    $sessionId
                );
                $message = 'Product added to cart';
            }

            // Load relationships for resource
            $cartItem->load(['product', 'variant']);

            // Get updated cart contents
            $cartContents = $this->cartService->getCartContents($sessionId);

            // Set a cookie with the session ID for future requests
            $response = $this->respondWithSuccess($message, 200, new CartItemResource($cartItem));

            // Add session ID cookie for guest users
            if (!Auth::check() && isset($cartContents['session_id'])) {
                $cookie = cookie('cart_session_id', $cartContents['session_id'], 60 * 24 * 30, null, null, null, true); // 30 days, HTTP-only
                $response->withCookie($cookie);
            }

            return $response;
        } catch (\Exception $e) {
            return $this->respondWithError('Error adding to cart: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update a cart item's quantity
     *
     * @param Request $request
     * @param int $cartItemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCartItem(Request $request, $cartItemId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors(), 422);
            }

            $sessionId = $this->extractSessionId($request);
            $cartItem = $this->cartService->updateCartItemQuantity(
                $cartItemId,
                $request->input('quantity'),
                $sessionId
            );

            // Load relationships for resource
            $cartItem->load(['product', 'variant']);

            // Get updated cart contents
            $cartContents = $this->cartService->getCartContents($sessionId);

            // Set a cookie with the session ID for future requests
            $response = $this->respondWithSuccess('Cart item updated successfully', 200, new CartItemResource($cartItem));

            // Add session ID cookie for guest users
            if (!Auth::check() && isset($cartContents['session_id'])) {
                $cookie = cookie('cart_session_id', $cartContents['session_id'], 60 * 24 * 30, null, null, null, true); // 30 days, HTTP-only
                $response->withCookie($cookie);
            }

            return $response;
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'No query results for model')) {
                return $this->respondWithError('Cart item not found', 404);
            }
            return $this->respondWithError('Error updating cart item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove an item from the cart
     *
     * @param int $cartItemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCartItem(Request $request, $cartItemId)
    {
        try {
            $sessionId = $this->extractSessionId($request);
            $this->cartService->removeCartItem($cartItemId, $sessionId);

            // Get updated cart with relationships
            $cart = $this->cartService->getCurrentCart($sessionId);
            $cart->load(['cartItems.product', 'cartItems.variant']);

            // Get cart contents with totals
            $cartContents = $this->cartService->getCartContents($sessionId);

            // Add totals to cart for resource
            $cart->totals = $cartContents['totals'] ?? null;
            $cart->item_count = $cartContents['item_count'] ?? 0;
            $cart->total_quantity = $cartContents['total_quantity'] ?? 0;

            // Set a cookie with the session ID for future requests
            $response = $this->respondWithSuccess('Cart item removed successfully', 200, new CartResource($cart));

            // Add session ID cookie for guest users
            if (!Auth::check() && isset($cartContents['session_id'])) {
                $cookie = cookie('cart_session_id', $cartContents['session_id'], 60 * 24 * 30, null, null, null, true); // 30 days, HTTP-only
                $response->withCookie($cookie);
            }

            return $response;
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'No query results for model')) {
                return $this->respondWithError('Cart item not found', 404);
            }
            return $this->respondWithError('Error removing cart item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Clear all items from the cart
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCart(Request $request)
    {
        try {
            $sessionId = $this->extractSessionId($request);
            $this->cartService->clearCart($sessionId);

            // Get updated cart (empty but with session ID)
            $cart = $this->cartService->getCurrentCart($sessionId);
            $cart->load(['cartItems.product', 'cartItems.variant']);

            // Get cart contents with totals
            $cartContents = $this->cartService->getCartContents($sessionId);

            // Add totals to cart for resource
            $cart->totals = $cartContents['totals'] ?? null;
            $cart->item_count = $cartContents['item_count'] ?? 0;
            $cart->total_quantity = $cartContents['total_quantity'] ?? 0;

            // Set a cookie with the session ID for future requests
            $response = $this->respondWithSuccess('Cart cleared successfully', 200, new CartResource($cart));

            // Add session ID cookie for guest users
            if (!Auth::check() && isset($cartContents['session_id'])) {
                $cookie = cookie('cart_session_id', $cartContents['session_id'], 60 * 24 * 30, null, null, null, true); // 30 days, HTTP-only
                $response->withCookie($cookie);
            }

            return $response;
        } catch (\Exception $e) {
            return $this->respondWithError('Error clearing cart: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Transfer a guest cart to a user cart after login
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function transferGuestCart(Request $request)
    {
        try {
            // Get session ID from cookie or request
            $sessionId = $this->extractSessionId($request);

            if (!$sessionId) {
                return $this->respondWithSuccess('No guest cart found', 200);
            }

            if (!Auth::check()) {
                return $this->respondWithError('User not authenticated', 401);
            }

            $userCart = $this->cartService->transferGuestCart(
                $sessionId,
                Auth::id()
            );

            if (!$userCart) {
                return $this->respondWithSuccess('No guest cart found or cart was empty', 200);
            }

            // Get updated cart with relationships
            $cart = $this->cartService->getCurrentCart();
            $cart->load(['cartItems.product', 'cartItems.variant']);

            // Get cart contents with totals
            $cartContents = $this->cartService->getCartContents();

            // Add totals to cart for resource
            $cart->totals = $cartContents['totals'] ?? null;
            $cart->item_count = $cartContents['item_count'] ?? 0;
            $cart->total_quantity = $cartContents['total_quantity'] ?? 0;

            // Create response
            $response = $this->respondWithSuccess('Guest cart transferred successfully', 200, new CartResource($cart));

            // Clear the cart_session_id cookie
            $response->cookie('cart_session_id', '', -1);

            return $response;
        } catch (\Exception $e) {
            return $this->respondWithError('Error transferring guest cart: ' . $e->getMessage(), 500);
        }
    }
}

