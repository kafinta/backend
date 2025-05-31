<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Variant;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryController extends ImprovedController
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Get inventory summary for authenticated seller
     */
    public function getSummary(Request $request)
    {
        try {
            $sellerId = auth()->id();
            $summary = $this->inventoryService->getInventorySummary($sellerId);

            return $this->successResponse($summary, 'Inventory summary retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get inventory summary: ' . $e->getMessage());
        }
    }

    /**
     * Get out of stock products for authenticated seller
     */
    public function getOutOfStockProducts(Request $request)
    {
        try {
            $sellerId = auth()->id();
            $products = $this->inventoryService->getOutOfStockProducts($sellerId);

            return $this->successResponse($products, 'Out of stock products retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get out of stock products: ' . $e->getMessage());
        }
    }

    /**
     * Get out of stock variants for authenticated seller
     */
    public function getOutOfStockVariants(Request $request)
    {
        try {
            $sellerId = auth()->id();
            $variants = $this->inventoryService->getOutOfStockVariants($sellerId);

            return $this->successResponse($variants, 'Out of stock variants retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get out of stock variants: ' . $e->getMessage());
        }
    }

    /**
     * Adjust stock for a specific product
     */
    public function adjustProductStock(Request $request, Product $product)
    {
        try {
            // Check if user owns this product
            if ($product->user_id !== auth()->id()) {
                return $this->errorResponse('Unauthorized', 403);
            }

            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer',
                'reason' => 'sometimes|string|max:255'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }

            $quantity = $request->input('quantity');
            $reason = $request->input('reason', 'Manual adjustment');

            $result = $product->adjustStock($quantity);

            if ($result) {
                return $this->successResponse([
                    'product_id' => $product->id,
                    'new_stock_quantity' => $product->fresh()->stock_quantity,
                    'adjustment' => $quantity,
                    'reason' => $reason
                ], 'Stock adjusted successfully');
            } else {
                return $this->errorResponse('Failed to adjust stock');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to adjust product stock: ' . $e->getMessage());
        }
    }

    /**
     * Adjust stock for a specific variant
     */
    public function adjustVariantStock(Request $request, Variant $variant)
    {
        try {
            // Check if user owns this variant's product
            if ($variant->product->user_id !== auth()->id()) {
                return $this->errorResponse('Unauthorized', 403);
            }

            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer',
                'reason' => 'sometimes|string|max:255'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }

            $quantity = $request->input('quantity');
            $reason = $request->input('reason', 'Manual adjustment');

            $result = $variant->adjustStock($quantity);

            if ($result) {
                return $this->successResponse([
                    'variant_id' => $variant->id,
                    'new_stock_quantity' => $variant->fresh()->stock_quantity,
                    'adjustment' => $quantity,
                    'reason' => $reason
                ], 'Stock adjusted successfully');
            } else {
                return $this->errorResponse('Failed to adjust stock');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to adjust variant stock: ' . $e->getMessage());
        }
    }

    /**
     * Bulk stock adjustment
     */
    public function bulkAdjustment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'adjustments' => 'required|array',
                'adjustments.*.type' => 'required|in:product,variant',
                'adjustments.*.id' => 'required|integer',
                'adjustments.*.quantity' => 'required|integer'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }

            $adjustments = $request->input('adjustments');
            
            // Verify ownership of all items before processing
            foreach ($adjustments as $adjustment) {
                if ($adjustment['type'] === 'product') {
                    $product = Product::find($adjustment['id']);
                    if (!$product || $product->user_id !== auth()->id()) {
                        return $this->errorResponse('Unauthorized access to product ID: ' . $adjustment['id'], 403);
                    }
                } else {
                    $variant = Variant::find($adjustment['id']);
                    if (!$variant || $variant->product->user_id !== auth()->id()) {
                        return $this->errorResponse('Unauthorized access to variant ID: ' . $adjustment['id'], 403);
                    }
                }
            }

            $result = $this->inventoryService->bulkStockAdjustment($adjustments);

            if ($result) {
                return $this->successResponse([
                    'processed_count' => count($adjustments)
                ], 'Bulk stock adjustment completed successfully');
            } else {
                return $this->errorResponse('Failed to process bulk adjustment');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to process bulk adjustment: ' . $e->getMessage());
        }
    }

    /**
     * Set stock management for a product
     */
    public function setProductStockManagement(Request $request, Product $product)
    {
        try {
            // Check if user owns this product
            if ($product->user_id !== auth()->id()) {
                return $this->errorResponse('Unauthorized', 403);
            }

            $validator = Validator::make($request->all(), [
                'manage_stock' => 'required|boolean',
                'stock_quantity' => 'sometimes|integer|min:0'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors());
            }

            $product->manage_stock = $request->input('manage_stock');
            
            if ($request->has('stock_quantity')) {
                $product->stock_quantity = $request->input('stock_quantity');
            }

            $product->save();

            return $this->successResponse([
                'product_id' => $product->id,
                'manage_stock' => $product->manage_stock,
                'stock_quantity' => $product->stock_quantity
            ], 'Stock management settings updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update stock management: ' . $e->getMessage());
        }
    }
}
