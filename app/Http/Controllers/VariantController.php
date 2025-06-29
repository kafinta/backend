<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ImprovedController;
use App\Models\Product;
use App\Models\Variant;
use App\Services\VariantService;
use App\Http\Resources\VariantResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

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

            return $this->respondWithSuccess('Variants retrieved successfully', 200, VariantResource::collection($variants));
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
            $variant = Variant::with(['attributeValues.attribute', 'images'])->findOrFail($id);
            $product = $variant->product;

            // Check if user has permission to view this variant
            if (!auth()->user()->hasRole('admin') && auth()->id() !== $product->user_id) {
                return $this->respondWithError('Unauthorized access', 403);
            }

            // Format the variant attributes using the service
            $variant = $this->variantService->formatVariantAttributes($variant);

            return $this->respondWithSuccess('Variant retrieved successfully', 200, new VariantResource($variant));
        } catch (\Exception $e) {
            Log::error('Error retrieving variant', [
                'variant_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->respondWithError($e->getMessage(), 500);
        }
    }

    /**
     * Create a new variant for a product.
     *
     * @param Request $request
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $productId)
    {
        try {
            $product = Product::findOrFail($productId);
            if (!auth()->user()->hasRole('admin') && auth()->id() !== $product->user_id) {
                return $this->respondWithError('Unauthorized access', 403);
            }
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'manage_stock' => 'sometimes|boolean',
                'stock_quantity' => 'sometimes|integer|min:0',
                'attributes' => 'required|array|min:1',
                'attributes.*.attribute_id' => 'required|exists:attributes,id',
                'attributes.*.value_id' => 'required|exists:attribute_values,id',
                'images' => 'sometimes|array',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors(), 422);
            }
            $variant = $this->variantService->createVariant($product, $validator->validated());
            // Handle image upload
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) {
                    $directory = 'variants/' . $variant->id;
                    $path = app(\App\Services\FileService::class)->uploadFile($imageFile, $directory);
                    if ($path) {
                        $variant->images()->create(['path' => $path]);
                    }
                }
            }
            return $this->respondWithSuccess('Variant created successfully', 201, new VariantResource($variant->load('images')));
        } catch (\Exception $e) {
            Log::error('Error creating variant', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
            return $this->respondWithError('Error creating variant: ' . $e->getMessage(), 500);
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
            if (!auth()->user()->hasRole('admin') && auth()->id() !== $product->user_id) {
                return $this->respondWithError('Unauthorized access', 403);
            }
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'price' => 'sometimes|numeric|min:0',
                'manage_stock' => 'sometimes|boolean',
                'stock_quantity' => 'sometimes|integer|min:0',
                'attributes' => 'sometimes|array',
                'attributes.*.attribute_id' => 'required_with:attributes|exists:attributes,id',
                'attributes.*.value_id' => 'required_with:attributes|exists:attribute_values,id',
                'images' => 'sometimes|array',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                'delete_image_ids' => 'sometimes|array',
                'delete_image_ids.*' => 'integer|exists:images,id',
            ]);
            if ($validator->fails()) {
                return $this->respondWithError($validator->errors(), 422);
            }
            $variant = $this->variantService->updateVariant($variant, $validator->validated());
            // Handle image upload
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) {
                    $directory = 'variants/' . $variant->id;
                    $path = app(\App\Services\FileService::class)->uploadFile($imageFile, $directory);
                    if ($path) {
                        $variant->images()->create(['path' => $path]);
                    }
                }
            }
            // Handle image deletion
            if ($request->has('delete_image_ids') && is_array($request->input('delete_image_ids'))) {
                foreach ($request->input('delete_image_ids') as $imageId) {
                    $image = $variant->images()->find($imageId);
                    if ($image) {
                        app(\App\Services\FileService::class)->deleteFile($image->path);
                        $image->delete();
                    }
                }
            }
            $variant->load('images');
            return $this->respondWithSuccess('Variant updated successfully', 200, new VariantResource($variant));
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

    /**
     * Update multiple variants at once
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchUpdate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'variants' => 'required|array',
                'variants.*.id' => 'required|exists:variants,id',
                'variants.*.name' => 'sometimes|string|max:255',
                'variants.*.price' => 'sometimes|numeric|min:0',
                'variants.*.attributes' => 'sometimes|array',
                'variants.*.attributes.*.attribute_id' => 'required_with:variants.*.attributes|exists:attributes,id',
                'variants.*.attributes.*.value_id' => 'required_with:variants.*.attributes|exists:attribute_values,id'
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors(), 422);
            }

            $updatedVariants = [];
            foreach ($request->variants as $variantData) {
                $variant = Variant::findOrFail($variantData['id']);
                $product = $variant->product;

                // Check if user has permission to update this variant
                if (!auth()->user()->hasRole('admin') && auth()->id() !== $product->user_id) {
                    continue; // Skip unauthorized variants
                }

                $updateData = [];

                // Add basic fields
                if (isset($variantData['name'])) {
                    $updateData['name'] = $variantData['name'];
                }
                if (isset($variantData['price'])) {
                    $updateData['price'] = $variantData['price'];
                }

                // Process attribute values if provided
                if (isset($variantData['attributes'])) {
                    $updateData['attribute_values'] = $variantData['attributes'];
                }

                if (!empty($updateData)) {
                    $updatedVariant = $this->variantService->updateVariant($variant, $updateData);
                    $updatedVariants[] = $updatedVariant;
                }
            }

            return $this->respondWithSuccess(count($updatedVariants) . ' variants updated successfully', 200, VariantResource::collection($updatedVariants));
        } catch (\Exception $e) {
            Log::error('Error batch updating variants', [
                'error' => $e->getMessage()
            ]);

            return $this->respondWithError($e->getMessage(), 500);
        }
    }
}
