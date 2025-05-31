<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Get products that are out of stock
     *
     * @param int|null $sellerId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOutOfStockProducts($sellerId = null)
    {
        $query = Product::where('manage_stock', true)
            ->where('stock_quantity', 0);

        if ($sellerId) {
            $query->where('user_id', $sellerId);
        }

        return $query->get();
    }

    /**
     * Get variants that are out of stock
     *
     * @param int|null $sellerId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOutOfStockVariants($sellerId = null)
    {
        $query = Variant::where('manage_stock', true)
            ->where('stock_quantity', 0)
            ->with('product');

        if ($sellerId) {
            $query->whereHas('product', fn($q) => $q->where('user_id', $sellerId));
        }

        return $query->get();
    }

    /**
     * Bulk stock adjustment for multiple products/variants
     *
     * @param array $adjustments
     * @return bool
     */
    public function bulkStockAdjustment(array $adjustments)
    {
        return DB::transaction(function() use ($adjustments) {
            foreach ($adjustments as $adjustment) {
                if ($adjustment['type'] === 'product') {
                    $product = Product::find($adjustment['id']);
                    if ($product) {
                        $product->adjustStock($adjustment['quantity']);
                    }
                } else {
                    $variant = Variant::find($adjustment['id']);
                    if ($variant) {
                        $variant->adjustStock($adjustment['quantity']);
                    }
                }
            }
            return true;
        });
    }

    /**
     * Get inventory summary for a seller
     *
     * @param int $sellerId
     * @return array
     */
    public function getInventorySummary($sellerId)
    {
        $products = Product::where('user_id', $sellerId)->get();
        $variants = Variant::whereHas('product', fn($q) => $q->where('user_id', $sellerId))->get();

        return [
            'total_products' => $products->count(),
            'products_in_stock' => $products->where('manage_stock', false)->count() + 
                                  $products->where('manage_stock', true)->where('stock_quantity', '>', 0)->count(),
            'products_out_of_stock' => $products->where('manage_stock', true)->where('stock_quantity', 0)->count(),
            'total_variants' => $variants->count(),
            'variants_in_stock' => $variants->where('manage_stock', false)->count() + 
                                  $variants->where('manage_stock', true)->where('stock_quantity', '>', 0)->count(),
            'variants_out_of_stock' => $variants->where('manage_stock', true)->where('stock_quantity', 0)->count(),
        ];
    }

    /**
     * Process stock reduction for order items
     *
     * @param array $orderItems
     * @return bool
     * @throws \Exception
     */
    public function processOrderStockReduction(array $orderItems)
    {
        return DB::transaction(function() use ($orderItems) {
            foreach ($orderItems as $item) {
                if ($item['variant_id']) {
                    $variant = Variant::find($item['variant_id']);
                    if (!$variant || !$variant->reduceStock($item['quantity'])) {
                        throw new \Exception("Insufficient stock for variant ID: {$item['variant_id']}");
                    }
                } else {
                    $product = Product::find($item['product_id']);
                    if (!$product || !$product->reduceStock($item['quantity'])) {
                        throw new \Exception("Insufficient stock for product ID: {$item['product_id']}");
                    }
                }
            }
            return true;
        });
    }

    /**
     * Restore stock for cancelled/refunded orders
     *
     * @param array $orderItems
     * @return bool
     */
    public function restoreOrderStock(array $orderItems)
    {
        return DB::transaction(function() use ($orderItems) {
            foreach ($orderItems as $item) {
                if ($item['variant_id']) {
                    $variant = Variant::find($item['variant_id']);
                    if ($variant) {
                        $variant->adjustStock($item['quantity']);
                    }
                } else {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        $product->adjustStock($item['quantity']);
                    }
                }
            }
            return true;
        });
    }
}
