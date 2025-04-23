<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CartService
{
    /**
     * Get the current cart for the user or session
     *
     * @return Cart
     */
    public function getCurrentCart()
    {
        if (Auth::check()) {
            // User is logged in, get or create their cart
            $cart = Cart::firstOrCreate(
                ['user_id' => Auth::id()],
                ['session_id' => null]
            );
        } else {
            // User is a guest, get or create a session-based cart
            $sessionId = Session::getId();
            
            // If no session ID, generate one
            if (!$sessionId) {
                $sessionId = Str::uuid()->toString();
                Session::setId($sessionId);
                Session::start();
            }
            
            $cart = Cart::firstOrCreate(
                ['session_id' => $sessionId],
                ['user_id' => null]
            );
        }
        
        return $cart;
    }
    
    /**
     * Add a product to the cart
     *
     * @param int $productId
     * @param int $quantity
     * @return CartItem
     */
    public function addProductToCart($productId, $quantity = 1)
    {
        $cart = $this->getCurrentCart();
        $product = Product::findOrFail($productId);
        
        // Check if the product is already in the cart
        $cartItem = $cart->cartItems()
            ->where('product_id', $productId)
            ->where('variant_id', null)
            ->first();
        
        if ($cartItem) {
            // Update quantity if the product is already in the cart
            $cartItem->update([
                'quantity' => $cartItem->quantity + $quantity
            ]);
        } else {
            // Add new cart item
            $cartItem = $cart->cartItems()->create([
                'product_id' => $productId,
                'variant_id' => null,
                'quantity' => $quantity
            ]);
        }
        
        return $cartItem;
    }
    
    /**
     * Add a variant to the cart
     *
     * @param int $variantId
     * @param int $quantity
     * @return CartItem
     */
    public function addVariantToCart($variantId, $quantity = 1)
    {
        $cart = $this->getCurrentCart();
        $variant = Variant::with('product')->findOrFail($variantId);
        
        // Check if the variant is already in the cart
        $cartItem = $cart->cartItems()
            ->where('variant_id', $variantId)
            ->first();
        
        if ($cartItem) {
            // Update quantity if the variant is already in the cart
            $cartItem->update([
                'quantity' => $cartItem->quantity + $quantity
            ]);
        } else {
            // Add new cart item
            $cartItem = $cart->cartItems()->create([
                'product_id' => $variant->product->id,
                'variant_id' => $variantId,
                'quantity' => $quantity
            ]);
        }
        
        return $cartItem;
    }
    
    /**
     * Update cart item quantity
     *
     * @param int $cartItemId
     * @param int $quantity
     * @return CartItem
     */
    public function updateCartItemQuantity($cartItemId, $quantity)
    {
        $cart = $this->getCurrentCart();
        $cartItem = $cart->cartItems()->findOrFail($cartItemId);
        
        $cartItem->update([
            'quantity' => $quantity
        ]);
        
        return $cartItem;
    }
    
    /**
     * Remove an item from the cart
     *
     * @param int $cartItemId
     * @return bool
     */
    public function removeCartItem($cartItemId)
    {
        $cart = $this->getCurrentCart();
        $cartItem = $cart->cartItems()->findOrFail($cartItemId);
        
        return $cartItem->delete();
    }
    
    /**
     * Clear all items from the cart
     *
     * @return bool
     */
    public function clearCart()
    {
        $cart = $this->getCurrentCart();
        
        return $cart->cartItems()->delete();
    }
    
    /**
     * Get cart contents with product/variant details
     *
     * @return array
     */
    public function getCartContents()
    {
        $cart = $this->getCurrentCart();
        
        // Load cart items with their related products and variants
        $cartItems = $cart->cartItems()
            ->with(['product', 'variant', 'variant.attributeValues.attribute'])
            ->get();
        
        $items = [];
        $totalPrice = 0;
        
        foreach ($cartItems as $cartItem) {
            // Determine the price (use variant price if available, otherwise product price)
            $price = $cartItem->variant ? $cartItem->variant->price : $cartItem->product->price;
            $subtotal = $price * $cartItem->quantity;
            $totalPrice += $subtotal;
            
            // Format the item data
            $item = [
                'id' => $cartItem->id,
                'quantity' => $cartItem->quantity,
                'price' => $price,
                'subtotal' => $subtotal,
                'product' => [
                    'id' => $cartItem->product->id,
                    'name' => $cartItem->product->name,
                    'image' => $cartItem->product->images->first() ? $cartItem->product->images->first()->path : null,
                ],
                'variant' => null
            ];
            
            // Add variant details if this is a variant
            if ($cartItem->variant) {
                $item['variant'] = [
                    'id' => $cartItem->variant->id,
                    'name' => $cartItem->variant->name,
                    'image' => $cartItem->variant->images->first() ? $cartItem->variant->images->first()->path : null,
                    'attributes' => $cartItem->variant->attributeValues->map(function ($value) {
                        return [
                            'name' => $value->attribute->name,
                            'value' => $value->name
                        ];
                    })
                ];
            }
            
            $items[] = $item;
        }
        
        return [
            'items' => $items,
            'total_price' => $totalPrice,
            'item_count' => $cartItems->sum('quantity')
        ];
    }
    
    /**
     * Transfer a guest cart to a user cart after login
     *
     * @param string $sessionId
     * @param int $userId
     * @return Cart|null
     */
    public function transferGuestCart($sessionId, $userId)
    {
        // Find the guest cart
        $guestCart = Cart::where('session_id', $sessionId)->first();
        
        if (!$guestCart || $guestCart->cartItems->isEmpty()) {
            return null;
        }
        
        // Find or create the user cart
        $userCart = Cart::firstOrCreate(
            ['user_id' => $userId],
            ['session_id' => null]
        );
        
        // Transfer items from guest cart to user cart
        foreach ($guestCart->cartItems as $guestItem) {
            // Check if the same product/variant is already in the user's cart
            $existingItem = $userCart->cartItems()
                ->where('product_id', $guestItem->product_id)
                ->where('variant_id', $guestItem->variant_id)
                ->first();
            
            if ($existingItem) {
                // Update quantity if the item already exists
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $guestItem->quantity
                ]);
            } else {
                // Create a new item in the user's cart
                $userCart->cartItems()->create([
                    'product_id' => $guestItem->product_id,
                    'variant_id' => $guestItem->variant_id,
                    'quantity' => $guestItem->quantity
                ]);
            }
        }
        
        // Delete the guest cart
        $guestCart->delete();
        
        return $userCart;
    }
}
