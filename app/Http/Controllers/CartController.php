<?php

namespace App\Http\Controllers;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function viewCart()
    {
        $user = auth()->user(); // Assuming you have authentication

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // Retrieve cart items associated with the user
        $cartItems = $user->cartItems;

        return response()->json(['cart_items' => $cartItems], 200);
    }

    public function addToCart(Request $request, $productId)
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $cartItem = CartItem::where('product_id', $productId)->first();

        if ($cartItem) {
            // If the product is already in the cart, update the quantity
            $cartItem->update([
                'quantity' => $cartItem->quantity + 1,
            ]);
        } else {
            // If the product is not in the cart, create a new cart item
            $cartItem = CartItem::create([
                'product_id' => $productId,
                'quantity' => 1,
            ]);
        }

        return response()->json(['message' => 'Product added to cart', 'cart_item' => $cartItem], 200);
    }

    public function updateCartItem(Request $request, $cartItemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1', // Validate that the quantity is an integer and at least 1
        ]);

        $cartItem = CartItem::find($cartItemId);

        if (!$cartItem) {
            return response()->json(['error' => 'Cart item not found'], 404);
        }

        $cartItem->update([
            'quantity' => $request->input('quantity'),
        ]);

        return response()->json(['message' => 'Cart item updated successfully', 'cart_item' => $cartItem], 200);
    }

    public function deleteCartItem($cartItemId)
    {
        $cartItem = CartItem::find($cartItemId);

        if (!$cartItem) {
            return response()->json(['error' => 'Cart item not found'], 404);
        }

        $cartItem->delete();

        return response()->json(['message' => 'Cart item deleted successfully']);
    }

    public function clearCart()
    {
        $user = auth()->user(); // Retrieve the authenticated user

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // Delete all cart items associated with the user
        $user->cartItems()->delete();

        return response()->json(['message' => 'Cart cleared successfully']);
    }

    public function checkout(Request $request)
    {
        $user = auth()->user(); // Assuming you have authentication

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
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

            return response()->json(['message' => 'Checkout successful', 'order_id' => $order->id]);
        } catch (\Exception $e) {
            // An error occurred, rollback the transaction
            DB::rollBack();

            return response()->json(['error' => 'Checkout failed', 'message' => $e->getMessage()], 500);
        }
    }

    public function viewOrderSummary()
    {
        // Retrieve and display the order summary
    }
}
