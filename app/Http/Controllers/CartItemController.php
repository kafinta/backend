<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ImprovedController;
use App\Http\Resources\CartItemResource;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartItemController extends ImprovedController
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Display all cart items for the current user/session
     */
    public function index(Request $request)
    {
        try {
            $sessionId = $this->extractSessionId($request);
            $cart = $this->cartService->getCurrentCart($sessionId);

            $cartItems = $cart->cartItems()->with(['product', 'variant'])->get();

            return $this->respondWithSuccess('Cart items retrieved successfully', 200, CartItemResource::collection($cartItems));
        } catch (\Exception $e) {
            return $this->respondWithError('Error retrieving cart items: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display a specific cart item
     */
    public function show(Request $request, $id)
    {
        try {
            $sessionId = $this->extractSessionId($request);
            $cart = $this->cartService->getCurrentCart($sessionId);

            $cartItem = $cart->cartItems()->with(['product', 'variant'])->findOrFail($id);

            return $this->respondWithSuccess('Cart item retrieved successfully', 200, new CartItemResource($cartItem));
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'No query results for model')) {
                return $this->respondWithError('Cart item not found', 404);
            }
            return $this->respondWithError('Error retrieving cart item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Add a new cart item (alias for CartController::addToCart)
     */
    public function store(Request $request)
    {
        // Redirect to CartController::addToCart for consistency
        $cartController = new \App\Http\Controllers\CartController($this->cartService);
        return $cartController->addToCart($request);
    }

    /**
     * Update a cart item (alias for CartController::updateCartItem)
     */
    public function update(Request $request, $id)
    {
        // Redirect to CartController::updateCartItem for consistency
        $cartController = new \App\Http\Controllers\CartController($this->cartService);
        return $cartController->updateCartItem($request, $id);
    }

    /**
     * Delete a cart item (alias for CartController::deleteCartItem)
     */
    public function destroy(Request $request, $id)
    {
        // Redirect to CartController::deleteCartItem for consistency
        $cartController = new \App\Http\Controllers\CartController($this->cartService);
        return $cartController->deleteCartItem($request, $id);
    }

    /**
     * Extract session ID from request (same logic as CartController)
     */
    private function extractSessionId(Request $request)
    {
        if (Auth::check()) {
            return null; // User is authenticated, no session ID needed
        }

        // Try to get session ID from cookie first, then from request
        return $request->cookie('cart_session_id') ?? $request->input('session_id');
    }
}
