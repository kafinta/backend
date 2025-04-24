<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cart;
use Carbon\Carbon;

class CleanExpiredCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cart:clean-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired guest carts from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning up expired carts...');

        // Find expired guest carts
        $expiredCarts = Cart::guest()->expired()->get();

        $count = $expiredCarts->count();
        $this->info("Found {$count} expired carts.");

        if ($count === 0) {
            $this->info('No expired carts to clean up.');
            return 0;
        }

        // Delete each cart and its items
        $deletedCount = 0;
        foreach ($expiredCarts as $cart) {
            $this->info("Deleting cart #{$cart->id} (expired at {$cart->expires_at})");

            // Delete cart items first
            $itemCount = $cart->cartItems()->count();
            $cart->cartItems()->delete();

            // Then delete the cart
            $cart->delete();

            $this->info("Deleted cart #{$cart->id} with {$itemCount} items.");
            $deletedCount++;
        }

        $this->info("Successfully cleaned up {$deletedCount} expired carts.");
        return 0;
    }
}
