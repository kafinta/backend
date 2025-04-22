<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ImprovedController;
use App\Models\Product;
use App\Models\Variant;
use App\Services\VariantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VariantController extends ImprovedController
{
    protected $variantService;

    /**
     * Create a new controller instance.
     *
     * @param VariantService $variantService
     */
    public function __construct(VariantService $variantService)
    {
        $this->middleware(['auth:sanctum', 'role:seller|admin']);
        $this->variantService = $variantService;
    }

    /**
     * Display a listing of the variants for a product.
     *
     * @param Request $request
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $productId)
    {
        try {
            $product = Product::findOrFail($productId);

            // Check if user has permission to view this product's variants
            if (!auth()->user()->hasRole('admin') && auth()->id() !== $product->user_id) {
                return $this->respondWithError('Unauthorized access', 403);
            }

            $variants = $this->variantService->getVariantsForProduct($product);

            return $this->respondWithSuccess('Variants retrieved successfully', 200, $variants);
        } catch (\Exception $e) {
            Log::error('Error retrieving variants', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return $this->respondWithError($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified variant.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $variant = Variant::with('attributeValues')->findOrFail($id);
            $product = $variant->product;

            // Check if user has permission to view this variant
            if (!auth()->user()->hasRole('admin') && auth()->id() !== $product->user_id) {
                return $this->respondWithError('Unauthorized access', 403);
            }

            return $this->respondWithSuccess('Variant retrieved successfully', 200, $variant);
        } catch (\Exception $e) {
            Log::error('Error retrieving variant', [
                'variant_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->respondWithError($e->getMessage(), 500);
        }
    }

    /**
     * Generate variants for a product.
     *
     * @param Request $request
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate(Request $request, $productId)
    {
        try {
            $product = Product::findOrFail($productId);

            // Check if user has permission to generate variants for this product
            if (!auth()->user()->hasRole('admin') && auth()->id() !== $product->user_id) {
                return $this->respondWithError('Unauthorized access', 403);
            }

            $variants = $this->variantService->generateVariantsForProduct($product);

            return $this->respondWithSuccess(
                count($variants) . ' variants generated successfully',
                200,
                $variants
            );
        } catch (\Exception $e) {
            Log::error('Error generating variants', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);

            return $this->respondWithError($e->getMessage(), 500);
        }
    }

    /**
     * Update the specified variant.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $variant = Variant::findOrFail($id);
            $product = $variant->product;

            // Check if user has permission to update this variant
            if (!auth()->user()->hasRole('admin') && auth()->id() !== $product->user_id) {
                return $this->respondWithError('Unauthorized access', 403);
            }

            $validator = Validator::make($request->all(), [
                'price' => 'sometimes|numeric|min:0',
                'attribute_values' => 'sometimes|array',
                'attribute_values.*' => 'exists:attribute_values,id'
                // We'll add SKU and stock validation in a future update
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors(), 422);
            }

            $variant = $this->variantService->updateVariant($variant, $validator->validated());

            return $this->respondWithSuccess('Variant updated successfully', 200, $variant);
        } catch (\Exception $e) {
            Log::error('Error updating variant', [
                'variant_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->respondWithError($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified variant.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $variant = Variant::findOrFail($id);
            $product = $variant->product;

            // Check if user has permission to delete this variant
            if (!auth()->user()->hasRole('admin') && auth()->id() !== $product->user_id) {
                return $this->respondWithError('Unauthorized access', 403);
            }

            $this->variantService->deleteVariant($variant);

            return $this->respondWithSuccess('Variant deleted successfully', 200);
        } catch (\Exception $e) {
            Log::error('Error deleting variant', [
                'variant_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->respondWithError($e->getMessage(), 500);
        }
    }
}
