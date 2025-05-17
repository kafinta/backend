<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ImprovedController;
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
            $cartContents = $this->cartService->getCartContents($sessionId);

            // Set a cookie with the session ID for future requests
            if (!Auth::check() && isset($cartContents['session_id'])) {
                $cookie = cookie('cart_session_id', $cartContents['session_id'], 60 * 24 * 30); // 30 days
                return $this->respondWithSuccess('Cart retrieved successfully', 200, $cartContents)->withCookie($cookie);
            }

            return $this->respondWithSuccess('Cart retrieved successfully', 200, $cartContents);
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

            // Get updated cart contents
            $cartContents = $this->cartService->getCartContents($sessionId);

            // Set a cookie with the session ID for future requests
            $response = $this->respondWithSuccess($message, 200, [
                'cart_item' => $cartItem,
                'cart' => $cartContents
            ]);

            // Add session ID cookie for guest users
            if (!Auth::check() && isset($cartContents['session_id'])) {
                $cookie = cookie('cart_session_id', $cartContents['session_id'], 60 * 24 * 30); // 30 days
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

            // Get updated cart contents
            $cartContents = $this->cartService->getCartContents($sessionId);

            // Set a cookie with the session ID for future requests
            $response = $this->respondWithSuccess('Cart item updated successfully', 200, [
                'cart_item' => $cartItem,
                'cart' => $cartContents
            ]);

            // Add session ID cookie for guest users
            if (!Auth::check() && isset($cartContents['session_id'])) {
                $cookie = cookie('cart_session_id', $cartContents['session_id'], 60 * 24 * 30); // 30 days
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

            // Get updated cart contents
            $cartContents = $this->cartService->getCartContents($sessionId);

            // Set a cookie with the session ID for future requests
            $response = $this->respondWithSuccess('Cart item removed successfully', 200, [
                'cart' => $cartContents
            ]);

            // Add session ID cookie for guest users
            if (!Auth::check() && isset($cartContents['session_id'])) {
                $cookie = cookie('cart_session_id', $cartContents['session_id'], 60 * 24 * 30); // 30 days
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

            // Get updated cart contents (empty but with session ID)
            $cartContents = $this->cartService->getCartContents($sessionId);

            // Set a cookie with the session ID for future requests
            $response = $this->respondWithSuccess('Cart cleared successfully', 200, [
                'cart' => $cartContents
            ]);

            // Add session ID cookie for guest users
            if (!Auth::check() && isset($cartContents['session_id'])) {
                $cookie = cookie('cart_session_id', $cartContents['session_id'], 60 * 24 * 30); // 30 days
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

            // Get updated cart contents
            $cartContents = $this->cartService->getCartContents();

            // Create response
            $response = $this->respondWithSuccess('Guest cart transferred successfully', 200, [
                'cart' => $cartContents
            ]);

            // Clear the cart_session_id cookie
            $response->cookie('cart_session_id', '', -1);

            return $response;
        } catch (\Exception $e) {
            return $this->respondWithError('Error transferring guest cart: ' . $e->getMessage(), 500);
        }
    }

    public function checkout(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return $this->respondWithError('User not authenticated', 401);
        }

        // Validate the cart or perform any additional checks as needed

        // Begin a database transaction
        DB::beginTransaction();

        try {
            // Create an order
            $order = Order::create([
                'user_id' => $user->id,
                // Add other necessary fields like total amount, status, etc.
            ]);

            // Move cart items to order items
            $cartItems = $user->cartItems;

            foreach ($cartItems as $cartItem) {
                $order->orderItems()->create([
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    // Add other necessary fields like price, subtotal, etc.
                ]);
            }

            // Clear the cart
            $user->cartItems()->delete();

            // Commit the transaction
            DB::commit();

            return $this->respondWithSuccess('Checkout successful', 200, [
                'order_id' => $order->id
            ]);
        } catch (\Exception $e) {
            // An error occurred, rollback the transaction
            DB::rollBack();

            return $this->respondWithError('Checkout failed: ' . $e->getMessage(), 500);
        }
    }

    public function viewOrderSummary(Request $request)
    {
        try {
            // Retrieve and display the order summary
            // This is a placeholder for future implementation
            return $this->respondWithSuccess('Order summary retrieved successfully', 200, [
                'summary' => 'Order summary will be implemented here'
            ]);
        } catch (\Exception $e) {
            return $this->respondWithError('Error retrieving order summary: ' . $e->getMessage(), 500);
        }
    }
}
