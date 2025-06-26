<?php

namespace App\Http\Controllers;

use App\Events\ProductCreated;
use App\Events\ProductStatusChanged;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\ImprovedController;
use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Image;
use App\Services\ProductService;
use App\Services\FileService;
use App\Services\ProductAttributeService;
use App\Services\ProductImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ProductResource;

class ProductController extends ImprovedController
{
    protected $productService;
    protected $fileService;
    protected $imageController;
    protected $attributeService;
    protected $imageService;

    public function __construct(
        ProductService $productService,
        FileService $fileService,
        ImageController $imageController,
        ProductAttributeService $attributeService,
        ProductImageService $imageService
    ) {
        $this->middleware(['auth:sanctum'])->except(['index', 'show', 'byCategory', 'search']);
        $this->productService = $productService;
        $this->fileService = $fileService;
        $this->imageController = $imageController;
        $this->attributeService = $attributeService;
        $this->imageService = $imageService;
    }

    /**
     * Product discovery with required search or subcategory filtering
     * Entry points: keyword search OR subcategory selection
     */
    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'per_page' => 'sometimes|integer|min:1|max:100',
                'keyword' => 'sometimes|string|max:255',
                'category_id' => 'sometimes|integer|exists:categories,id',
                'subcategory_id' => 'sometimes|array',
                'subcategory_id.*' => 'integer|exists:subcategories,id',
                'min_price' => 'sometimes|numeric|min:0',
                'max_price' => 'sometimes|numeric|min:0',
                'is_featured' => 'sometimes|boolean',
                'seller_id' => 'sometimes|integer|exists:users,id',
                'location_id' => 'sometimes|array',
                'location_id.*' => 'integer|exists:locations,id',
                'sort_by' => 'sometimes|in:name,price,created_at,updated_at,relevance',
                'sort_direction' => 'sometimes|in:asc,desc',
                'stock_status' => 'sometimes|in:in_stock,out_of_stock',
                'attributes' => 'sometimes|array',
                'attributes.*' => 'integer|exists:attribute_values,id'
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors(), 422);
            }

            // Require either keyword search OR subcategory selection (or both)
            $hasKeyword = $request->filled('keyword') && !empty(trim($request->input('keyword')));
            $hasSubcategory = $request->filled('subcategory_id') && !empty($request->input('subcategory_id'));

            if (!$hasKeyword && !$hasSubcategory) {
                return $this->respondWithError('Either keyword search or subcategory selection is required', 400);
            }

            $perPage = $request->input('per_page', 15);
            $filters = $request->only([
                'keyword', 'category_id', 'subcategory_id',
                'min_price', 'max_price',
                'is_featured', 'seller_id', 'location_id',
                'sort_by', 'sort_direction', 'stock_status', 'attributes'
            ]);

            // Force only active products for public listing
            $filters['status'] = 'active';

            // Get products with targeted filtering
            $result = $this->productService->getTargetedProductListing($filters, $perPage);

            return $this->respondWithSuccess('Products retrieved successfully', 200, $result);
        } catch (\Exception $e) {
            Log::error('Error retrieving products', ['error' => $e->getMessage()]);
            return $this->respondWithError('Error retrieving products: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        try {
            $product->load([
                'category',
                'subcategory',
                'images',
                'attributeValues.attribute',
                'user' => function($query) {
                    $query->select('id');
                    $query->with(['seller' => function($query) {
                        $query->select('id', 'user_id', 'business_name');
                    }]);
                }
            ]);

            return $this->respondWithSuccess('Product retrieved successfully', 200, new ProductResource($product));
        } catch (\Exception $e) {
            Log::error('Error retrieving product', [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->respondWithError('Error retrieving product', 500);
        }
    }

    /**
     * Display the specified product by slug.
     */
    public function showBySlug($slug)
    {
        try {
            $product = Product::where('slug', $slug)
                ->with([
                    'category',
                    'subcategory',
                    'images',
                    'attributeValues.attribute',
                    'user' => function($query) {
                        $query->select('id');
                        $query->with(['seller' => function($query) {
                            $query->select('id', 'user_id', 'business_name');
                        }]);
                    }
                ])->first();

            if (!$product) {
                return $this->respondWithError('Product not found', 404);
            }

            return $this->respondWithSuccess('Product retrieved successfully', 200, new ProductResource($product));
        } catch (\Exception $e) {
            \Log::error('Error retrieving product by slug', [
                'error' => $e->getMessage(),
                'slug' => $slug,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->respondWithError('Error retrieving product', 500);
        }
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product)
    {
        try {
            if (!Auth::user()->hasRole('admin') && Auth::id() !== $product->user_id) {
                return $this->respondWithError('Unauthorized', 403);
            }

            $result = $this->productService->deleteProduct($product);

            if ($result) {
                return $this->respondWithSuccess('Product deleted successfully', 200);
            } else {
                return $this->respondWithError('Failed to delete product', 500);
            }
        } catch (\Exception $e) {
            Log::error('Error deleting product', ['error' => $e->getMessage()]);
            return $this->respondWithError('Error deleting product: ' . $e->getMessage(), 500);
        }
    }

    // ===== STEP-BY-STEP PRODUCT CREATION METHODS =====

    /**
     * Generate a unique slug for the product
     * Appends a number if the slug already exists
     * @param string $name
     * @return string
     */
    private function generateUniqueSlug($name)
    {
        $baseSlug = \Illuminate\Support\Str::slug($name);
        $slug = $baseSlug;
        $i = 2;
        // Check for global uniqueness
        while (\App\Models\Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i;
            $i++;
        }
        return $slug;
    }

    /**
     * Step 1: Create basic product information (including inventory)
     */
    public function createBasicInfo(Request $request)
    {
        // Use a DB transaction for safety
        \DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'subcategory_id' => 'required|exists:subcategories,id',
                'location_id' => 'nullable|exists:locations,id',
                'status' => 'sometimes|in:draft,active,inactive',
                'manage_stock' => 'required|boolean',
                'stock_quantity' => 'required_if:manage_stock,true|integer|min:0'
            ]);

            if ($validator->fails()) {
                \DB::rollBack();
                return $this->respondWithError($validator->errors(), 422);
            }

            $data = $validator->validated();
            $data['user_id'] = auth()->id();
            $data['status'] = $data['status'] ?? 'draft';
            // Use robust unique slug generation
            $data['slug'] = $this->generateUniqueSlug($data['name']);

            // Set default stock quantity if not managing stock
            if (!$data['manage_stock']) {
                $data['stock_quantity'] = 0;
            }

            // Create basic product with inventory
            $product = Product::create($data);

            // Sync attributes from subcategory
            $product->syncAttributesFromSubcategory();

            // Check if this is the seller's first product
            $isFirstProduct = Product::where('user_id', auth()->id())->count() === 1;

            // Fire product created event
            event(new ProductCreated($product, $isFirstProduct));

            \DB::commit();
            return $this->respondWithSuccess(
                'Basic product information and inventory saved successfully',
                201,
                new ProductResource($product->load(['subcategory', 'location', 'images', 'attributeValues']))
            );

        } catch (\Illuminate\Database\QueryException $e) {
            \DB::rollBack();
            // Check for duplicate slug error (SQLSTATE 23000, error code 1062)
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'products_slug_unique')) {
                return $this->respondWithError('You already have a product with a similar name. Please choose a different name.', 422);
            }
            return $this->respondWithError('Failed to create basic product info: ' . $e->getMessage(), 500);
        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->respondWithError('Failed to create basic product info: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update basic product information (including inventory)
     */
    public function updateBasicInfo(Request $request, Product $product)
    {
        try {
            // Check ownership
            if ($product->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
                return $this->respondWithError('Unauthorized', 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'price' => 'sometimes|numeric|min:0',
                'subcategory_id' => 'sometimes|exists:subcategories,id',
                'location_id' => 'nullable|exists:locations,id',
                'manage_stock' => 'sometimes|boolean',
                'stock_quantity' => 'sometimes|integer|min:0'
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors(), 422);
            }

            $data = $validator->validated();

            // Update slug if name changed
            if (isset($data['name'])) {
                $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
            }

            // Handle stock management logic
            if (isset($data['manage_stock'])) {
                if (!$data['manage_stock']) {
                    $data['stock_quantity'] = 0;
                }
            }

            // Check if subcategory changed
            $subcategoryChanged = isset($data['subcategory_id']) && $data['subcategory_id'] !== $product->subcategory_id;

            // Update the product
            $product->update($data);

            // If subcategory changed, sync new attributes
            if ($subcategoryChanged) {
                $product->syncAttributesFromSubcategory();
            }

            return $this->respondWithSuccess(
                'Basic product information updated successfully',
                200,
                new ProductResource($product->load(['subcategory', 'location', 'images', 'attributeValues']))
            );

        } catch (\Exception $e) {
            return $this->respondWithError('Failed to update basic product info: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Step 2: Add product attributes
     */
    public function addAttributes(Request $request, Product $product)
    {
        try {
            // Check ownership
            if ($product->user_id !== auth()->id()) {
                return $this->respondWithError('Unauthorized', 403);
            }

            $validator = Validator::make($request->all(), [
                'attributes' => 'required|array',
                'attributes.*.attribute_id' => 'required|exists:attributes,id',
                'attributes.*.value_id' => 'required|exists:attribute_values,id'
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors(), 422);
            }

            $attributePairs = $request->input('attributes');

            // Additional validation: ensure attribute-value pairs are valid for this subcategory
            foreach ($attributePairs as $index => $pair) {
                $attributeId = $pair['attribute_id'];
                $valueId = $pair['value_id'];

                // Check if attribute belongs to product's subcategory
                $attributeExists = $product->subcategory->attributes()
                    ->where('attributes.id', $attributeId)
                    ->exists();

                if (!$attributeExists) {
                    return $this->respondWithError(
                        "Attribute ID {$attributeId} does not belong to subcategory '{$product->subcategory->name}'",
                        422
                    );
                }

                // Check if value belongs to the attribute
                $valueExists = \App\Models\AttributeValue::where('id', $valueId)
                    ->where('attribute_id', $attributeId)
                    ->exists();

                if (!$valueExists) {
                    return $this->respondWithError(
                        "Value ID {$valueId} does not belong to attribute ID {$attributeId}",
                        422
                    );
                }
            }

            // Convert to attribute_id => value_id format for setAttributeValues method
            $attributeValues = [];
            foreach ($attributePairs as $pair) {
                $attributeValues[$pair['attribute_id']] = $pair['value_id'];
            }

            // Update product attributes using the correct method
            $product->setAttributeValues($attributeValues);

            return $this->respondWithSuccess(
                'Product attributes updated successfully',
                200,
                new ProductResource($product->load(['attributeValues.attribute', 'subcategory', 'location', 'images']))
            );

        } catch (\Exception $e) {
            return $this->respondWithError('Failed to update attributes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Step 3: Upload images
     */
    public function uploadImages(Request $request, Product $product)
    {
        try {
            // Check ownership
            if ($product->user_id !== auth()->id()) {
                return $this->respondWithError('Unauthorized', 403);
            }

            $validator = Validator::make($request->all(), [
                'images' => 'required|array',
                'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors(), 422);
            }

            $images = $request->file('images');
            $uploadedImages = $this->imageService->uploadImages($product, $images);

            return $this->respondWithSuccess(
                'Images uploaded successfully',
                200,
                new ProductResource($product->load(['images', 'subcategory', 'location', 'attributeValues.attribute']))
            );

        } catch (\Exception $e) {
            return $this->respondWithError('Failed to upload images: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Step 4: Review and publish (automatically sets to active)
     */
    public function reviewAndPublish(Request $request, Product $product)
    {
        try {
            // Check ownership
            if ($product->user_id !== auth()->id()) {
                return $this->respondWithError('Unauthorized', 403);
            }

            // Check if product is ready to publish (all steps completed)
            if (!$product->name || !$product->description || !$product->price || !$product->subcategory_id) {
                return $this->respondWithError('Product cannot be published: Missing required information', 422);
            }

            if (!$product->attributeValues()->exists()) {
                return $this->respondWithError('Product cannot be published: No attributes set', 422);
            }

            if (!$product->images()->exists()) {
                return $this->respondWithError('Product cannot be published: No images uploaded', 422);
            }

            // Automatically set status to active when publishing
            $product->update([
                'status' => 'active'
            ]);

            return $this->respondWithSuccess(
                'Product published successfully',
                200,
                new ProductResource($product->load(['images', 'attributeValues.attribute', 'subcategory', 'location']))
            );

        } catch (\Exception $e) {
            return $this->respondWithError('Failed to publish product: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update product status (for sellers and admins)
     */
    public function updateStatus(Request $request, Product $product)
    {
        try {
            $user = auth()->user();

            // Check ownership for sellers, allow all for admins
            if (!$user->hasRole('admin') && $product->user_id !== $user->id) {
                return $this->respondWithError('Unauthorized', 403);
            }

            // Define allowed statuses based on user role
            $allowedStatuses = $user->hasRole('admin')
                ? ['active', 'paused', 'denied', 'out_of_stock']  // Admin can set any status
                : ['active', 'paused'];  // Sellers can only activate/pause

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:' . implode(',', $allowedStatuses),
                'reason' => 'required_if:status,denied|string|max:500'  // Required when admin denies
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors(), 422);
            }

            $newStatus = $request->input('status');
            $oldStatus = $product->status;

            // Additional validation for sellers
            if (!$user->hasRole('admin')) {
                // Sellers can only change status of published products
                if ($product->status === 'draft') {
                    return $this->respondWithError('Cannot change status of draft products. Use publish endpoint instead.', 422);
                }

                if ($product->status === 'denied') {
                    return $this->respondWithError('Cannot change status of denied products. Contact admin for review.', 422);
                }
            }

            // Update product status
            $updateData = ['status' => $newStatus];
            $denialReason = '';

            // Add denial reason if provided
            if ($newStatus === 'denied' && $request->has('reason')) {
                $denialReason = $request->input('reason');
                $updateData['denial_reason'] = $denialReason;
            }

            $product->update($updateData);

            // Fire product status changed event if status actually changed
            if ($oldStatus !== $newStatus) {
                event(new ProductStatusChanged($product, $oldStatus, $newStatus, $denialReason));
            }

            $message = match($newStatus) {
                'active' => 'Product activated successfully',
                'paused' => 'Product paused successfully',
                'denied' => 'Product denied successfully',
                'out_of_stock' => 'Product marked as out of stock',
                default => 'Product status updated successfully'
            };

            return $this->respondWithSuccess(
                $message,
                200,
                new ProductResource($product->load(['images', 'attributeValues.attribute', 'subcategory', 'location']))
            );

        } catch (\Exception $e) {
            return $this->respondWithError('Failed to update product status: ' . $e->getMessage(), 500);
        }
    }

    // ===== SELLER-SPECIFIC PRODUCT MANAGEMENT =====

    /**
     * Get products for the authenticated seller
     */
    public function myProducts(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'per_page' => 'sometimes|integer|min:1|max:100',
                'status' => 'sometimes|in:draft,active,paused,denied,out_of_stock',
                'sort_by' => 'sometimes|in:name,price,created_at,updated_at,stock_quantity',
                'sort_direction' => 'sometimes|in:asc,desc',
                'stock_status' => 'sometimes|in:in_stock,out_of_stock,low_stock'
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors(), 422);
            }

            $perPage = $request->input('per_page', 15);
            $filters = $request->only(['status', 'sort_by', 'sort_direction', 'stock_status']);
            $filters['seller_id'] = auth()->id();

            $products = $this->productService->searchProducts($filters, $perPage, false);

            return $this->respondWithSuccess('Your products retrieved successfully', 200, $products);
        } catch (\Exception $e) {
            Log::error('Error retrieving seller products', ['error' => $e->getMessage()]);
            return $this->respondWithError('Error retrieving your products: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get product statistics for the authenticated seller
     */
    public function myProductStats()
    {
        try {
            $sellerId = auth()->id();
            $stats = $this->productService->getSellerProductStats($sellerId);

            return $this->respondWithSuccess('Product statistics retrieved successfully', 200, $stats);
        } catch (\Exception $e) {
            Log::error('Error retrieving seller product stats', ['error' => $e->getMessage()]);
            return $this->respondWithError('Error retrieving product statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk update product status for seller
     */
    public function bulkUpdateStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_ids' => 'required|array|min:1',
                'product_ids.*' => 'integer|exists:products,id',
                'status' => 'required|in:active,paused'
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors(), 422);
            }

            $productIds = $request->input('product_ids');
            $status = $request->input('status');
            $sellerId = auth()->id();

            $result = $this->productService->bulkUpdateStatus($productIds, $status, $sellerId);

            return $this->respondWithSuccess('Products status updated successfully', 200, $result);
        } catch (\Exception $e) {
            Log::error('Error bulk updating product status', ['error' => $e->getMessage()]);
            return $this->respondWithError('Error updating product status: ' . $e->getMessage(), 500);
        }
    }
}
