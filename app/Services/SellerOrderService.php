<?php

namespace App\Services;

use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SellerOrderService
{
    /**
     * Get all orders containing products sold by the authenticated seller
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSellerOrders()
    {
        $sellerId = Auth::id();

        // Get all products belonging to the seller
        $sellerProductIds = Product::where('user_id', $sellerId)->pluck('id')->toArray();

        if (empty($sellerProductIds)) {
            return collect();
        }

        // Find all order items containing the seller's products
        $orderItems = OrderItem::whereIn('product_id', $sellerProductIds)
            ->with('order')
            ->get();

        // Extract unique order IDs
        $orderIds = $orderItems->pluck('order.id')->unique()->toArray();

        // Get the orders with only this seller's items.
        return Order::whereIn('id', $orderIds)
            ->with(['orderItems' => function($query) use ($sellerProductIds) {
                // Only include items for this seller's products
                $query->whereIn('product_id', $sellerProductIds);
            }])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get a specific order with items sold by the authenticated seller
     *
     * @param int $orderId
     * @return Order
     */
    public function getSellerOrder($orderId)
    {
        $sellerId = Auth::id();

        // Get all products belonging to the seller
        $sellerProductIds = Product::where('user_id', $sellerId)->pluck('id')->toArray();

        if (empty($sellerProductIds)) {
            throw new \Exception('No products found for this seller');
        }

        return Order::whereKey($orderId)
            ->whereHas('orderItems', function ($query) use ($sellerProductIds) {
                $query->whereIn('product_id', $sellerProductIds);
            })
            ->with(['orderItems' => function ($query) use ($sellerProductIds) {
                $query->whereIn('product_id', $sellerProductIds);
            }])
            ->firstOrFail();
    }

    /**
     * Update the status of a seller's items in an order
     *
     * @param int $orderId
     * @param string $status
     * @return Order
     */
    public function updateOrderStatus($orderId, $status)
    {
        $sellerId = Auth::id();

        // Validate status
        $validStatuses = ['processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Invalid status. Valid statuses are: ' . implode(', ', $validStatuses));
        }

        // Get all products belonging to the seller
        $sellerProductIds = Product::where('user_id', $sellerId)->pluck('id')->toArray();

        if (empty($sellerProductIds)) {
            throw new \Exception('No products found for this seller');
        }

        $order = Order::whereKey($orderId)
            ->whereHas('orderItems', function ($query) use ($sellerProductIds) {
                $query->whereIn('product_id', $sellerProductIds);
            })
            ->firstOrFail();

        // Start a database transaction
        return DB::transaction(function () use ($order, $sellerProductIds, $status) {
            $oldStatus = $order->status;

            if ($status !== 'cancelled' && !$order->paid_at) {
                throw new \Exception('Only paid orders can be processed');
            }

            if ($status === 'cancelled' && $order->paid_at) {
                throw new \Exception('Paid orders cannot be cancelled by sellers until refunds are supported');
            }

            $sellerItems = $order->orderItems()
                ->whereIn('product_id', $sellerProductIds)
                ->lockForUpdate()
                ->get();

            if ($sellerItems->isEmpty()) {
                throw new \Exception('This order does not contain any of your products');
            }

            foreach ($sellerItems as $item) {
                if (!$this->canTransition($item->status, $status)) {
                    throw new \Exception("Cannot change item status from {$item->status} to {$status}");
                }
            }

            // Update the status of order items for this seller's products
            $order->orderItems()
                ->whereIn('product_id', $sellerProductIds)
                ->update(['status' => $status]);

            // If status is 'shipped', update the shipped_at timestamp
            if ($status === 'shipped') {
                $order->orderItems()
                    ->whereIn('product_id', $sellerProductIds)
                    ->update(['shipped_at' => now()]);
            }

            // If status is 'delivered', update the delivered_at timestamp
            if ($status === 'delivered') {
                $order->orderItems()
                    ->whereIn('product_id', $sellerProductIds)
                    ->update(['delivered_at' => now()]);
            }

            // If status is 'cancelled', update the cancelled_at timestamp
            if ($status === 'cancelled') {
                $order->orderItems()
                    ->whereIn('product_id', $sellerProductIds)
                    ->update(['cancelled_at' => now()]);

                foreach ($sellerItems as $item) {
                    $stockItem = $item->variant_id
                        ? Variant::whereKey($item->variant_id)->lockForUpdate()->first()
                        : Product::whereKey($item->product_id)->lockForUpdate()->first();

                    if ($stockItem && $stockItem->manage_stock) {
                        $stockItem->increment('stock_quantity', $item->quantity);
                    }
                }
            }

            // Update overall order status if needed
            $this->updateOverallOrderStatus($order);

            // Reload the order with updated items
            $order->load(['orderItems' => function($query) use ($sellerProductIds) {
                $query->whereIn('product_id', $sellerProductIds);
            }]);

            // Fire order status changed event if overall status changed
            if ($order->status !== $oldStatus) {
                event(new OrderStatusChanged($order, $oldStatus, $order->status));
            }

            return $order;
        });
    }

    /**
     * Update overall order status based on item statuses
     *
     * @param Order $order
     * @return void
     */
    protected function updateOverallOrderStatus(Order $order)
    {
        $itemStatuses = $order->orderItems()->pluck('status')->unique();

        // If all items are delivered, mark order as delivered
        if ($itemStatuses->count() === 1 && $itemStatuses->first() === 'delivered') {
            $order->update(['status' => 'delivered', 'delivered_at' => now()]);
        }
        // If all items are cancelled, mark order as cancelled
        elseif ($itemStatuses->count() === 1 && $itemStatuses->first() === 'cancelled') {
            $order->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        }
        // If any items are shipped and none are pending, mark as shipped
        elseif ($itemStatuses->contains('shipped') && !$itemStatuses->contains('pending')) {
            $order->update(['status' => 'shipped', 'shipped_at' => now()]);
        }
        // If any items are processing, mark as processing
        elseif ($itemStatuses->contains('processing')) {
            $order->update(['status' => 'processing']);
        }
    }

    protected function canTransition(string $from, string $to): bool
    {
        $transitions = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['delivered'],
            'delivered' => [],
            'cancelled' => [],
        ];

        return in_array($to, $transitions[$from] ?? [], true);
    }
}
