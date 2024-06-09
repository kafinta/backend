<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use Illuminate\Http\Request;

class CartItemController extends ImprovedController
{
    public function showAllCartItems()
    {
        $cartItems = CartItem::all();
        return response()->json('cart items', compact('cartItems'));
    }

    public function showCartItem($id)
    {
        $cartItem = CartItem::find($id);
        return response()->json('cart item', compact('cartItem'));
    }

    public function addCartItem(Request $request)
    {
        // Validate and store the new cart item
    }

    public function updateCartItem(Request $request, $id)
    {
        // Validate and update the cart item
    }

    public function deleteCartItem($id)
    {
        $cartItem = CartItem::find($id);
        $cartItem->delete();
    }
}
