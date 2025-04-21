<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ImageController;

use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Image;
use App\Services\ProductService;
use App\Services\FileService;
use App\Services\MultistepFormService;
use App\Services\ProductAttributeService;
use App\Services\ProductImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProductController extends ImprovedController
{
    protected $productService;
    protected $fileService;
    protected $imageController;
    protected $formService;
    protected $attributeService;
    protected $imageService;

    /**
     * Create a new controller instance.
     *
     * @param ProductService $productService
     * @param FileService $fileService
     * @param ImageController $imageController
     * @param MultistepFormService $formService
     * @param ProductAttributeService $attributeService
     * @param ProductImageService $imageService
     */
    public function __construct(
        ProductService $productService,
        FileService $fileService,
        ImageController $imageController,
        MultistepFormService $formService,
        ProductAttributeService $attributeService,
        ProductImageService $imageService
    ) {
        $this->middleware(['auth:sanctum'])->except(['index', 'show', 'byCategory', 'search']);
        $this->productService = $productService;
        $this->fileService = $fileService;
        $this->imageController = $imageController;
        $this->formService = $formService;
        $this->attributeService = $attributeService;
        $this->imageService = $imageService;
    }

    /**
     * Display a listing of the products.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $filters = $request->only([
                'keyword', 'category_id', 'subcategory_id',
                'min_price', 'max_price', 'status',
                'is_featured', 'seller_id', 'location_id',
                'sort_by', 'sort_direction'
            ]);

            // Use the optimized search method for index view (not detailed view)
            $products = $this->productService->searchProducts($filters, $perPage, false);

            return $this->respondWithSuccess('Products retrieved successfully', 200, [
                'products' => $products
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving products', ['error' => $e->getMessage()]);
            return $this->respondWithError('Error retrieving products', 500);
        }
    }

    /**
     * Display products by category.
     *
     * @param Category $category
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function byCategory(Category $category, Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $filters = $request->only([
                'keyword', 'subcategory_id',
                'min_price', 'max_price', 'status',
                'is_featured', 'seller_id', 'location_id',
                'sort_by', 'sort_direction'
            ]);

            // Add category ID to filters
            $filters['category_id'] = $category->id;

            // Use the optimized search method for index view (not detailed view)
            $products = $this->productService->searchProducts($filters, $perPage, false);

            return $this->respondWithSuccess('Products retrieved successfully', 200, [
                'category' => $category->name,
                'products' => $products
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving products by category', ['error' => $e->getMessage()]);
            return $this->respondWithError('Error retrieving products', 500);
        }
    }

    /**
     * Search for products.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $filters = $request->only([
                'keyword', 'category_id', 'subcategory_id',
                'min_price', 'max_price', 'status',
                'is_featured', 'seller_id', 'location_id',
                'sort_by', 'sort_direction'
            ]);

            // Use the optimized search method for index view (not detailed view)
            $products = $this->productService->searchProducts($filters, $perPage, false);

            return $this->respondWithSuccess('Search results', 200, [
                'products' => $products
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching products', ['error' => $e->getMessage()]);
            return $this->respondWithError('Error searching products', 500);
        }
    }

    /**
     * Display the specified product.
     *
     * @param Product $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Product $product)
    {
        try {
            // Load relationships including the new category relationship
            $product->load(['category', 'subcategory', 'user.seller', 'images', 'attributeValues.attribute']);

            // Format attributes in the consistent format
            $attributeValues = $product->attributeValues;
            $formattedAttributes = [];

            foreach ($attributeValues as $value) {
                if ($value->attribute) {
                    // If name is null, use the value from the representation or a default
                    $valueName = $value->name;
                    if ($valueName === null) {
                        // Try to get the name from the representation
                        if (is_array($value->representation) && isset($value->representation['value'])) {
                            $valueName = $value->representation['value'];
                        } else {
                            // Use a default value
                            $valueName = 'Unknown';
                        }
                    }

                    $formattedAttributes[] = [
                        'id' => $value->attribute->id,
                        'name' => $value->attribute->name,
                        'value' => [
                            'id' => $value->id,
                            'name' => $valueName,
                            'representation' => $value->representation
                        ]
                    ];
                }
            }

            // Remove the attributeValues relation to prevent it from being serialized
            $product->unsetRelation('attributeValues');

            // Add the formatted attributes
            $product->attributes = $formattedAttributes;

            return $this->respondWithSuccess('Product retrieved successfully', 200, [
                'product' => $product
            ]);
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
     * Store a newly created product.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Validate basic product information
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'stock_quantity' => 'required|integer|min:0',
                'category_id' => 'required|exists:categories,id',
                'subcategory_id' => 'required|exists:subcategories,id',
                'status' => 'sometimes|in:draft,active,inactive',
                'is_featured' => 'sometimes|boolean',
                'sku' => 'sometimes|string|max:50',
                'location_id' => 'sometimes|exists:locations,id',
                'attributes' => 'sometimes|array',
                'attributes.*.id' => 'required_with:attributes|exists:attributes,id',
                'attributes.*.value' => 'required_with:attributes|string',
                'images' => 'sometimes|array',
                'images.*' => 'required_with:images|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError(['errors' => $validator->errors()], 422);
            }

            // Get validated data
            $data = $validator->validated();

            // Process images if provided
            $images = $request->file('images') ?? [];

            // Create the product
            $product = $this->productService->createProduct($data, $images);

            return $this->respondWithSuccess('Product created successfully', 201, [
                'product' => $product
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating product', ['error' => $e->getMessage()]);
            return $this->respondWithError('Error creating product: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified product.
     *
     * @param Request $request
     * @param Product $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Product $product)
    {
        try {
            // Check if user has permission to update this product
            if (!Auth::user()->hasRole('admin') && Auth::id() !== $product->user_id) {
                return $this->respondWithError('Unauthorized', 403);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'price' => 'sometimes|numeric|min:0',
                'stock_quantity' => 'sometimes|integer|min:0',
                'category_id' => 'sometimes|exists:categories,id',
                'subcategory_id' => 'sometimes|exists:subcategories,id',
                'status' => 'sometimes|in:draft,active,inactive',
                'is_featured' => 'sometimes|boolean',
                'sku' => 'sometimes|string|max:50',
                'location_id' => 'sometimes|exists:locations,id',
                'attributes' => 'sometimes|array',
                'attributes.*.id' => 'required_with:attributes|exists:attributes,id',
                'attributes.*.value' => 'required_with:attributes|string',
                'images' => 'sometimes|array',
                'images.*' => 'required_with:images|image|mimes:jpeg,png,jpg,gif|max:2048',
                'delete_image_ids' => 'sometimes|array',
                'delete_image_ids.*' => 'required_with:delete_image_ids|exists:product_images,id',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError(['errors' => $validator->errors()], 422);
            }

            // Get validated data
            $data = $validator->validated();

            // Process images if provided
            $images = $request->file('images') ?? [];
            $deleteImageIds = $request->input('delete_image_ids', []);

            // Update the product
            $product = $this->productService->updateProduct($product, $data, $images, $deleteImageIds);

            return $this->respondWithSuccess('Product updated successfully', 200, [
                'product' => $product
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating product', ['error' => $e->getMessage()]);
            return $this->respondWithError('Error updating product: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified product.
     *
     * @param Product $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Product $product)
    {
        try {
            // Check if user has permission to delete this product
            if (!Auth::user()->hasRole('admin') && Auth::id() !== $product->user_id) {
                return $this->respondWithError('Unauthorized', 403);
            }

            // Delete the product
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

    /**
     * Upload images for a product.
     *
     * @param Request $request
     * @param Product $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImages(Request $request, Product $product)
    {
        try {
            // Check if user has permission to update this product
            if (!Auth::user()->hasRole('admin') && Auth::id() !== $product->user_id) {
                return $this->respondWithError('Unauthorized', 403);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'images' => 'required|array|min:1',
                'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'is_primary' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError(['errors' => $validator->errors()], 422);
            }

            // Use the ProductImageService to upload the images
            $isPrimary = $request->input('is_primary', false);
            $imageIds = $this->imageService->uploadImages($product, $request->file('images'), $isPrimary);

            // Get the uploaded images
            $uploadedImages = $product->images()->whereIn('id', $imageIds)->get();

            return $this->respondWithSuccess('Images uploaded successfully', 200, [
                'images' => $uploadedImages
            ]);
        } catch (\Exception $e) {
            Log::error('Error uploading product images', ['error' => $e->getMessage()]);
            return $this->respondWithError('Error uploading images: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a product image.
     *
     * @param Product $product
     * @param int $imageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteImage(Product $product, $imageId)
    {
        try {
            // Check if user has permission to update this product
            if (!Auth::user()->hasRole('admin') && Auth::id() !== $product->user_id) {
                return $this->respondWithError('Unauthorized', 403);
            }

            // Find the image
            $image = Image::find($imageId);
            if (!$image) {
                return $this->respondWithError('Image not found', 404);
            }

            // Check if image belongs to the product
            if ($image->imageable_id !== $product->id || $image->imageable_type !== get_class($product)) {
                return $this->respondWithError('Image does not belong to this product', 400);
            }

            // Use the ProductImageService to delete the image
            $this->imageService->deleteProductImages($product, [$imageId]);

            return $this->respondWithSuccess('Image deleted successfully', 200);
        } catch (\Exception $e) {
            Log::error('Error deleting product image', ['error' => $e->getMessage()]);
            return $this->respondWithError('Error deleting image: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate a session ID for multistep product form
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateSessionId()
    {
        try {
            // Generate a new session ID
            $sessionId = $this->formService->generateSessionId();

            // Initialize form data with empty steps
            $formData = [
                'created_at' => now(),
                'updated_at' => now(),
                'basic_info' => null,
                'attributes' => null,
                'images' => null,
                'product_id' => null, // For editing existing products
                'current_step' => 0,
                'total_steps' => 3,
                'form_type' => 'product_form' // Specify the form type
            ];

            // Save initial form data
            $this->formService->saveFormData($sessionId, $formData);

            // Verify the session was saved correctly
            $savedData = $this->formService->getFormData($sessionId);
            Log::info('Session initialization', [
                'session_id' => $sessionId,
                'data_saved' => !empty($savedData),
                'has_created_at' => isset($savedData['created_at']),
                'has_updated_at' => isset($savedData['updated_at']),
                'has_form_type' => isset($savedData['form_type'])
            ]);

            return $this->respondWithSuccess('Session created successfully', 200, [
                'session_id' => $sessionId
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating session ID', ['error' => $e->getMessage()]);
            return $this->respondWithError('Error generating session ID: ' . $e->getMessage(), 500);
        }
    }



    /**
     * Get form data for a session
     *
     * @param Request $request
     * @param string $sessionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFormData(Request $request, $sessionId)
    {
        try {
            // Get form data for the session
            $formData = $this->formService->getFormData($sessionId);

            if (!$formData) {
                // Initialize form data if it doesn't exist
                $formData = [
                    'created_at' => now(),
                    'updated_at' => now(),
                    'basic_info' => null,
                    'attributes' => null,
                    'images' => null,
                    'product_id' => null,
                    'current_step' => 0,
                    'total_steps' => 3,
                    'form_type' => 'product_form'
                ];
            }

            // Check if we should pre-populate with product data
            $productId = $request->query('product_id') ?? $formData['product_id'] ?? null;

            if ($productId) {
                $product = Product::with(['images', 'attributeValues.attribute'])->find($productId);

                if (!$product) {
                    return $this->respondWithError('Product not found', 404);
                }

                // Check if user has permission to edit this product
                if (!Auth::user()->hasRole('admin') && Auth::id() !== $product->user_id) {
                    return $this->respondWithError('Unauthorized', 403);
                }

                // Store product_id in form data
                $formData['product_id'] = $product->id;

                // If basic_info is not set or we're explicitly loading product data, initialize it from the product
                if (!$formData['basic_info'] || $request->has('product_id')) {
                    $formData['basic_info'] = [
                        'name' => $product->name,
                        'description' => $product->description,
                        'price' => $product->price,
                        'stock_quantity' => $product->stock_quantity ?? 0,
                        'subcategory_id' => $product->subcategory_id,
                        'status' => $product->status ?? 'draft',
                        'is_featured' => $product->is_featured ?? false,
                        'sku' => $product->sku,
                        'location_id' => $product->location_id
                    ];
                }

                // If attributes are not set or we're explicitly loading product data, initialize them from the product
                if (!$formData['attributes'] || $request->has('product_id')) {
                    $formData['attributes'] = $product->attributeValues->map(function($attributeValue) {
                        return [
                            'id' => $attributeValue->attribute->id,
                            'name' => $attributeValue->attribute->name,
                            'value' => [
                                'id' => $attributeValue->id,
                                'name' => $attributeValue->name
                            ]
                        ];
                    })->toArray();
                }

                // If images are not set or we're explicitly loading product data, initialize them from the product
                if (!$formData['images'] || $request->has('product_id')) {
                    $formData['images'] = $product->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'path' => $image->path,
                            'is_primary' => $image->is_primary ?? false
                        ];
                    })->toArray();
                }

                // Log that we're pre-populating with product data
                Log::info('Pre-populating form data with product', [
                    'session_id' => $sessionId,
                    'product_id' => $product->id,
                    'has_basic_info' => !empty($formData['basic_info']),
                    'has_attributes' => !empty($formData['attributes']),
                    'attributes_count' => count($formData['attributes'] ?? []),
                    'has_images' => !empty($formData['images']),
                    'images_count' => count($formData['images'] ?? [])
                ]);
            }

            // Save updated form data
            $this->formService->saveFormData($sessionId, $formData);

            return $this->respondWithSuccess('Form data retrieved successfully', 200, [
                'form_data' => $formData
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving form data', ['error' => $e->getMessage()]);
            return $this->respondWithError('Error retrieving form data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create or update a step in the multistep form
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createStep(Request $request)
    {
        try {
            // Get form configuration
            $formConfig = config('forms.product_form');

            // Basic validation for step and session_id
            $basicValidator = Validator::make($request->all(), [
                'session_id' => 'required|string',
                'step' => 'required|integer|min:1|max:' . $formConfig['total_steps'],
                'product_id' => 'sometimes|exists:products,id' // Optional product ID for updates
            ]);

            if ($basicValidator->fails()) {
                // Get the first validation error message
                $errors = $basicValidator->errors()->toArray();
                $firstErrorField = array_key_first($errors);
                $firstErrorMessage = $errors[$firstErrorField][0];

                return $this->respondWithError($firstErrorMessage, 422);
            }

            // Get step-specific validation rules from config
            $step = $request->step;
            if (!isset($formConfig['steps'][$step])) {
                return $this->respondWithError('Invalid step number', 422);
            }

            // Special case for step 3 with file uploads
            if ($step == 3 && $request->hasFile('images')) {
                // Skip data validation as we'll handle file uploads separately
            } else {
                // Validate step data using rules from config
                $stepValidator = Validator::make($request->all(), $formConfig['steps'][$step]['validation_rules']);

                if ($stepValidator->fails()) {
                    // Get the first validation error message
                    $errors = $stepValidator->errors()->toArray();
                    $firstErrorField = array_key_first($errors);
                    $firstErrorMessage = $errors[$firstErrorField][0];

                    return $this->respondWithError($firstErrorMessage, 422);
                }
            }

            // Log the session ID for debugging
            Log::info('Retrieving form data for step', [
                'session_id' => $request->session_id,
                'step' => $request->step
            ]);

            // Get form data for the session
            $formData = $this->formService->getFormData($request->session_id);

            // Log the result for debugging
            Log::info('Form data retrieval result', [
                'session_id' => $request->session_id,
                'data_found' => !empty($formData),
                'has_created_at' => isset($formData['created_at']),
                'has_updated_at' => isset($formData['updated_at']),
                'has_form_type' => isset($formData['form_type'])
            ]);

            // If product_id is provided in the request, store it in the form data
            if ($request->has('product_id')) {
                $productId = $request->product_id;
                $product = Product::find($productId);

                if (!$product) {
                    return $this->respondWithError('Product not found', 404);
                }

                // Check if user has permission to update this product
                if (!Auth::user()->hasRole('admin') && Auth::id() !== $product->user_id) {
                    return $this->respondWithError('Unauthorized', 403);
                }

                $formData['product_id'] = $productId;

                // Log that we're updating an existing product
                Log::info('Updating existing product', [
                    'session_id' => $request->session_id,
                    'product_id' => $productId
                ]);
            }

            if (!$formData) {
                // Check all session data for debugging
                $allSessionData = Session::all();
                Log::warning('Session not found or expired', [
                    'session_id' => $request->session_id,
                    'all_session_keys' => array_keys($allSessionData)
                ]);

                return $this->respondWithError('Session not found or expired. Please generate a new session and try again', 404);
            }

            // Get the data for this step
            $stepData = $request->data ?? [];

            // For step 3 (images), we might have file uploads instead of JSON data
            if ($request->step == 3 && $request->hasFile('images')) {
                // We'll handle this in the switch statement below
                $stepData = [];
            }

            // Update form data based on the step
            switch ($request->step) {
                case 1: // Basic info
                    // Validation already done using form config

                    // Store the data in the basic_info key for backward compatibility
                    $formData['basic_info'] = $stepData;

                    // Log the basic info for debugging
                    Log::info('Saving basic info for product', [
                        'session_id' => $request->session_id,
                        'basic_info' => $stepData,
                        'form_data' => $formData
                    ]);
                    break;

                case 2: // Attributes
                    // Attributes are optional, but if provided, validate them
                    if (is_array($stepData) && !empty($stepData)) {
                        // Check if we have the new format with id and nested value object
                        if (isset($stepData[0]['id']) && isset($stepData[0]['value'])) {
                            foreach ($stepData as $attribute) {
                                if (!isset($attribute['id']) || !isset($attribute['value']) ||
                                    !isset($attribute['value']['id']) || !isset($attribute['value']['name'])) {
                                    return $this->respondWithError('Invalid attribute format. Expected format: {id, name, value: {id, name}}', 422);
                                }
                            }
                        }
                        // Check if we have raw_attributes format
                        else if (isset($stepData[0]['attribute_id']) && isset($stepData[0]['value_id'])) {
                            // Convert to the expected format
                            $formattedAttributes = [];
                            foreach ($stepData as $rawAttribute) {
                                if (!isset($rawAttribute['attribute_id']) || !isset($rawAttribute['value_id'])) {
                                    return $this->respondWithError('Invalid raw attribute format. Expected format: {attribute_id, value_id}', 422);
                                }

                                // Get attribute and value details
                                $attribute = \App\Models\Attribute::find($rawAttribute['attribute_id']);
                                $value = \App\Models\AttributeValue::find($rawAttribute['value_id']);

                                // Log the attribute value for debugging
                                Log::info('Processing attribute value', [
                                    'attribute_id' => $rawAttribute['attribute_id'],
                                    'value_id' => $rawAttribute['value_id'],
                                    'attribute_found' => $attribute ? true : false,
                                    'value_found' => $value ? true : false,
                                    'attribute_name' => $attribute ? $attribute->name : null,
                                    'value_name' => $value ? $value->name : null
                                ]);

                                if (!$attribute || !$value) {
                                    continue;
                                }

                                $formattedAttributes[] = [
                                    'id' => $attribute->id,
                                    'name' => $attribute->name,
                                    'value' => [
                                        'id' => $value->id,
                                        'name' => $value->name
                                    ]
                                ];
                            }

                            $stepData = $formattedAttributes;
                        }
                        // Other formats - store as raw_attributes
                        else {
                            $formData['raw_attributes'] = $stepData;
                        }
                    }

                    // Store the data in the attributes key for backward compatibility
                    $formData['attributes'] = $stepData;
                    break;

                case 3: // Images
                    // Check if this is an update (product_id exists in form data)
                    $isUpdate = isset($formData['product_id']) && $formData['product_id'];

                    // For new products, require at least one image
                    if (!$isUpdate && !$request->hasFile('images')) {
                        return $this->respondWithError('Please upload at least one product image', 422);
                    }

                    // For updates, images are optional

                    // Process uploaded files if any
                    if ($request->hasFile('images')) {
                        $uploadedImages = [];
                        foreach ($request->file('images') as $image) {
                            // Use the FileService to upload the image
                            $path = $this->fileService->uploadFile($image, 'products');

                            if ($path) {
                                $uploadedImages[] = ['path' => $path];
                            }
                        }

                        // Log the uploaded images
                        Log::info('Uploaded images for product', [
                            'session_id' => $request->session_id,
                            'image_count' => count($uploadedImages),
                            'images' => $uploadedImages
                        ]);

                        // For updates, we need to add new images to existing ones, not replace them
                        if ($isUpdate) {
                            // Get existing images from the product
                            $product = Product::find($formData['product_id']);
                            if ($product) {
                                // If we already have images in the form data, add the new ones
                                if (isset($formData['images']) && is_array($formData['images'])) {
                                    $formData['images'] = array_merge($formData['images'], $uploadedImages);
                                } else {
                                    // If no images in form data yet, get existing images from the product
                                    $existingImages = $product->images->map(function($image) {
                                        return [
                                            'id' => $image->id,
                                            'path' => $image->path
                                        ];
                                    })->toArray();

                                    // Add new images to existing ones
                                    $formData['images'] = array_merge($existingImages, $uploadedImages);
                                }

                                Log::info('Added new images to existing ones', [
                                    'session_id' => $request->session_id,
                                    'product_id' => $formData['product_id'],
                                    'existing_image_count' => $product->images->count(),
                                    'new_image_count' => count($uploadedImages),
                                    'total_images_in_form' => count($formData['images'])
                                ]);
                            } else {
                                // If product not found, just use the uploaded images
                                $formData['images'] = $uploadedImages;
                            }
                        } else {
                            // For new products, just set the uploaded images
                            $formData['images'] = $uploadedImages;
                        }
                    } else if ($isUpdate) {
                        // For updates without new images, keep existing images
                        Log::info('No new images uploaded for product update', [
                            'session_id' => $request->session_id,
                            'product_id' => $formData['product_id']
                        ]);

                        // If no images exist in form data yet, initialize with empty array
                        if (!isset($formData['images'])) {
                            $formData['images'] = [];
                        }
                    }
                    break;

                default:
                    return $this->respondWithError('Invalid step. Step must be between 1 and ' . $formConfig['total_steps'], 422);
            }

            // Update current step if it's higher than the existing one
            if ($request->step > ($formData['current_step'] ?? 0)) {
                $formData['current_step'] = $request->step;
            }

            // Log the updated form data for debugging
            Log::info('Updated form data after step ' . $request->step, [
                'session_id' => $request->session_id,
                'current_step' => $formData['current_step'],
                'has_basic_info' => isset($formData['basic_info']),
                'has_attributes' => isset($formData['attributes']),
                'has_images' => isset($formData['images']),
                'attributes_count' => isset($formData['attributes']) ? count($formData['attributes']) : 0,
                'images_count' => isset($formData['images']) ? count($formData['images']) : 0
            ]);

            // Save updated form data
            $this->formService->saveFormData($request->session_id, $formData);

            return $this->respondWithSuccess('Step saved successfully', 200, [
                'form_data' => $formData
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving step', ['error' => $e->getMessage()]);
            return $this->respondWithError('Error saving step: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate a SKU for a product
     *
     * @param string $productName The product name
     * @return string The generated SKU
     */
    protected function generateSku(string $productName): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $productName), 0, 3));
        $randomPart = strtoupper(substr(md5(uniqid()), 0, 5));
        $timestamp = substr(time(), -4);

        return "{$prefix}-{$randomPart}-{$timestamp}";
    }

    /**
     * Submit the multistep form and create a new product
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submit(Request $request)
    {
        try {
            // Get form configuration
            $formConfig = config('forms.product_form');

            // Validate request
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string'
            ]);

            if ($validator->fails()) {
                // Get the first validation error message
                $errors = $validator->errors()->toArray();
                $firstErrorField = array_key_first($errors);
                $firstErrorMessage = $errors[$firstErrorField][0];

                return $this->respondWithError($firstErrorMessage, 422);
            }

            // Get form data for the session
            $formData = $this->formService->getFormData($request->session_id);

            // Log the form data for debugging
            Log::info('Form data for product submission', [
                'session_id' => $request->session_id,
                'form_data_exists' => !empty($formData),
                'has_basic_info' => isset($formData['basic_info']),
                'basic_info' => $formData['basic_info'] ?? null,
                'has_attributes' => isset($formData['attributes']),
                'attributes_count' => isset($formData['attributes']) ? count($formData['attributes']) : 0,
                'has_images' => isset($formData['images']),
                'images_count' => isset($formData['images']) ? count($formData['images']) : 0,
                'current_step' => $formData['current_step'] ?? null,
                'form_type' => $formData['form_type'] ?? null,
                'created_at' => $formData['created_at'] ?? null,
                'updated_at' => $formData['updated_at'] ?? null
            ]);

            // Dump the entire form data for debugging
            Log::debug('Complete form data', ['form_data' => $formData]);

            if (!$formData) {
                return $this->respondWithError('Session not found or expired. Please generate a new session and try again.', 404);
            }

            // Check if all required steps are completed
            if (!$formData['basic_info']) {
                return $this->respondWithError('Basic product information is required. Please complete step 1 first.', 422);
            }

            // Start transaction
            DB::beginTransaction();

            // Check if the form data already has a 'data' key
            if (isset($formData['data'])) {
                // Use the existing data structure
                $processedFormData = $formData;

                // Log that we're using the existing data structure
                Log::info('Using existing data structure', [
                    'has_data_key' => true,
                    'has_basic_info' => isset($processedFormData['data']['basic_info']),
                    'has_attributes' => isset($processedFormData['data']['attributes']),
                    'has_images' => isset($processedFormData['data']['images'])
                ]);
            } else {
                // Format the form data to match the expected structure for ProductService
                $processedFormData = [
                    'data' => [
                        'basic_info' => $formData['basic_info'] ?? null,
                        'images' => isset($formData['images']) && is_array($formData['images']) ?
                            array_map(function($image) {
                                return isset($image['path']) ? $image['path'] : null;
                            }, array_filter($formData['images'], function($image) {
                                return isset($image['path']);
                            })) : []
                    ]
                ];

                // Process attributes if they exist
                if (isset($formData['attributes']) && !empty($formData['attributes'])) {
                    // Check if we have the new format with id and nested value object
                    if (isset($formData['attributes'][0]['id']) && isset($formData['attributes'][0]['value'])) {
                        // Convert to raw_attributes format
                        $rawAttributes = [];
                        foreach ($formData['attributes'] as $attribute) {
                            if (isset($attribute['id']) && isset($attribute['value']) && isset($attribute['value']['id'])) {
                                $rawAttributes[] = [
                                    'attribute_id' => $attribute['id'],
                                    'value_id' => $attribute['value']['id']
                                ];
                            }
                        }

                        // Log the converted attributes
                        Log::info('Converted attributes to raw format', [
                            'original_count' => count($formData['attributes']),
                            'converted_count' => count($rawAttributes),
                            'raw_attributes' => $rawAttributes
                        ]);

                        $processedFormData['data']['raw_attributes'] = $rawAttributes;
                    }
                    // If we already have the raw_attributes format
                    else if (isset($formData['attributes'][0]['attribute_id']) && isset($formData['attributes'][0]['value_id'])) {
                        $processedFormData['data']['raw_attributes'] = $formData['attributes'];
                    }
                    // Otherwise, just pass the attributes as is
                    else {
                        $processedFormData['data']['attributes'] = $formData['attributes'];
                    }
                }

                // Log that we're creating a new data structure
                Log::info('Created new data structure', [
                    'has_data_key' => false,
                    'has_basic_info' => isset($formData['basic_info']),
                    'has_attributes' => isset($formData['attributes']),
                    'has_images' => isset($formData['images'])
                ]);
            }

            // Validate that we have the required data
            if (!isset($processedFormData['data']['basic_info']) || empty($processedFormData['data']['basic_info'])) {
                return $this->respondWithError('Basic product information is required. Please complete step 1 first.', 422);
            }

            // Check if this is a new product (not an update)
            $isUpdate = isset($formData['product_id']) && $formData['product_id'];

            // For new products, require at least one image
            if (!$isUpdate && (!isset($processedFormData['data']['images']) || empty($processedFormData['data']['images']))) {
                return $this->respondWithError('Product images are required for new products. Please complete step 3 first.', 422);
            }

            // Log the processed form data for debugging
            Log::info('Processed form data structure', [
                'has_data_key' => isset($processedFormData['data']),
                'has_basic_info' => isset($processedFormData['data']['basic_info']),
                'has_attributes' => isset($processedFormData['data']['attributes']),
                'has_raw_attributes' => isset($processedFormData['data']['raw_attributes']),
                'attributes_count' => isset($processedFormData['data']['attributes']) ? count($processedFormData['data']['attributes']) : 0,
                'raw_attributes_count' => isset($processedFormData['data']['raw_attributes']) ? count($processedFormData['data']['raw_attributes']) : 0,
                'has_images' => isset($processedFormData['data']['images']),
                'image_count' => isset($processedFormData['data']['images']) ? count($processedFormData['data']['images']) : 0
            ]);

            // If we have existing images that need to be deleted, add them to the form data
            if (isset($formData['product_id']) && $formData['product_id'] && isset($formData['images'])) {
                $product = Product::find($formData['product_id']);
                if ($product) {
                    // Check if user has permission to update this product
                    if (!Auth::user()->hasRole('admin') && Auth::id() !== $product->user_id) {
                        return $this->respondWithError('Unauthorized', 403);
                    }

                    // Get existing image IDs
                    $existingImageIds = $product->images->pluck('id')->toArray();

                    // Get image IDs from form data
                    $formImageIds = collect($formData['images'])->pluck('id')->filter()->toArray();

                    // Find images to delete
                    $imagesToDelete = array_diff($existingImageIds, $formImageIds);

                    if (!empty($imagesToDelete)) {
                        $processedFormData['data']['images'] = [
                            'paths' => $processedFormData['data']['images'],
                            'delete_ids' => $imagesToDelete
                        ];
                    }
                }
            }

            // Log the processed form data for debugging
            Log::info('Processed form data for product submission', [
                'session_id' => $request->session_id,
                'is_update' => isset($formData['product_id']) && $formData['product_id'],
                'product_id' => $formData['product_id'] ?? null,
                'has_basic_info' => isset($processedFormData['data']['basic_info']),
                'has_attributes' => isset($processedFormData['data']['attributes']) && !empty($processedFormData['data']['attributes']),
                'has_raw_attributes' => isset($processedFormData['data']['raw_attributes']) && !empty($processedFormData['data']['raw_attributes']),
                'attributes_count' => isset($processedFormData['data']['attributes']) ? count($processedFormData['data']['attributes']) : 0,
                'raw_attributes_count' => isset($processedFormData['data']['raw_attributes']) ? count($processedFormData['data']['raw_attributes']) : 0,
                'raw_attributes' => isset($processedFormData['data']['raw_attributes']) ? $processedFormData['data']['raw_attributes'] : [],
                'has_images' => isset($processedFormData['data']['images']) && !empty($processedFormData['data']['images']),
                'image_count' => isset($processedFormData['data']['images']) ? count($processedFormData['data']['images']) : 0
            ]);

            // This endpoint is only for creating new products
            // Use the ProductService to create the product
            $productData = $this->productService->createProduct($processedFormData);

            // Commit transaction
            DB::commit();

            // Clear session data
            $this->formService->clearFormData($request->session_id);

            return $this->respondWithSuccess('Product created successfully', 201, [
                'product' => $productData
            ]);
        } catch (\Exception $e) {
            // Rollback transaction
            DB::rollBack();

            Log::error('Error submitting product form', ['error' => $e->getMessage()]);
            return $this->respondWithError('Error submitting product form: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Submit the multistep form and update an existing product
     *
     * @param Request $request
     * @param Product $product The product to update
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitUpdate(Request $request, Product $product)
    {
        try {
            // Get form configuration
            $formConfig = config('forms.product_form');

            // Validate request
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string'
            ]);

            if ($validator->fails()) {
                // Get the first validation error message
                $errors = $validator->errors()->toArray();
                $firstErrorField = array_key_first($errors);
                $firstErrorMessage = $errors[$firstErrorField][0];

                return $this->respondWithError($firstErrorMessage, 422);
            }

            // Check if user has permission to update this product
            if (!Auth::user()->hasRole('admin') && Auth::id() !== $product->user_id) {
                return $this->respondWithError('Unauthorized', 403);
            }

            // Get form data for the session
            $formData = $this->formService->getFormData($request->session_id);

            // Log the form data for debugging
            Log::info('Form data for product update', [
                'session_id' => $request->session_id,
                'product_id' => $product->id,
                'form_data_exists' => !empty($formData),
                'has_basic_info' => isset($formData['basic_info']),
                'has_attributes' => isset($formData['attributes']),
                'has_images' => isset($formData['images'])
            ]);

            if (!$formData) {
                return $this->respondWithError('Session not found or expired. Please generate a new session and try again.', 404);
            }

            // Check if all required steps are completed
            if (!$formData['basic_info']) {
                return $this->respondWithError('Basic product information is required. Please complete step 1 first.', 422);
            }

            // Start transaction
            DB::beginTransaction();

            // Check if the form data already has a 'data' key
            if (isset($formData['data'])) {
                // Use the existing data structure
                $processedFormData = $formData;

                // Log that we're using the existing data structure
                Log::info('Using existing data structure for update', [
                    'has_data_key' => true,
                    'has_basic_info' => isset($processedFormData['data']['basic_info']),
                    'has_attributes' => isset($processedFormData['data']['attributes']),
                    'has_images' => isset($processedFormData['data']['images'])
                ]);
            } else {
                // Format the form data to match the expected structure for ProductService
                $processedFormData = [
                    'data' => [
                        'basic_info' => $formData['basic_info'] ?? null,
                        'images' => isset($formData['images']) && is_array($formData['images']) ?
                            array_map(function($image) {
                                return isset($image['path']) ? $image['path'] : null;
                            }, array_filter($formData['images'], function($image) {
                                return isset($image['path']);
                            })) : []
                    ]
                ];

                // Process attributes if they exist
                if (isset($formData['attributes']) && !empty($formData['attributes'])) {
                    // Check if we have the new format with id and nested value object
                    if (isset($formData['attributes'][0]['id']) && isset($formData['attributes'][0]['value'])) {
                        // Convert to raw_attributes format
                        $rawAttributes = [];
                        foreach ($formData['attributes'] as $attribute) {
                            if (isset($attribute['id']) && isset($attribute['value']) && isset($attribute['value']['id'])) {
                                $rawAttributes[] = [
                                    'attribute_id' => $attribute['id'],
                                    'value_id' => $attribute['value']['id']
                                ];
                            }
                        }

                        // Log the converted attributes
                        Log::info('Converted attributes to raw format for update', [
                            'original_count' => count($formData['attributes']),
                            'converted_count' => count($rawAttributes),
                            'raw_attributes' => $rawAttributes
                        ]);

                        $processedFormData['data']['raw_attributes'] = $rawAttributes;
                    }
                    // If we already have the raw_attributes format
                    else if (isset($formData['attributes'][0]['attribute_id']) && isset($formData['attributes'][0]['value_id'])) {
                        $processedFormData['data']['raw_attributes'] = $formData['attributes'];
                    }
                    // Otherwise, just pass the attributes as is
                    else {
                        $processedFormData['data']['attributes'] = $formData['attributes'];
                    }
                }

                // Log that we're creating a new data structure
                Log::info('Created new data structure for update', [
                    'has_data_key' => false,
                    'has_basic_info' => isset($formData['basic_info']),
                    'has_attributes' => isset($formData['attributes']),
                    'has_images' => isset($formData['images'])
                ]);
            }

            // Validate that we have the required data
            if (!isset($processedFormData['data']['basic_info']) || empty($processedFormData['data']['basic_info'])) {
                return $this->respondWithError('Basic product information is required. Please complete step 1 first.', 422);
            }

            // Log the processed form data for debugging
            Log::info('Processed form data structure for update', [
                'has_data_key' => isset($processedFormData['data']),
                'has_basic_info' => isset($processedFormData['data']['basic_info']),
                'has_attributes' => isset($processedFormData['data']['attributes']),
                'has_raw_attributes' => isset($processedFormData['data']['raw_attributes']),
                'attributes_count' => isset($processedFormData['data']['attributes']) ? count($processedFormData['data']['attributes']) : 0,
                'raw_attributes_count' => isset($processedFormData['data']['raw_attributes']) ? count($processedFormData['data']['raw_attributes']) : 0,
                'raw_attributes' => isset($processedFormData['data']['raw_attributes']) ? $processedFormData['data']['raw_attributes'] : [],
                'has_images' => isset($processedFormData['data']['images']),
                'image_count' => isset($processedFormData['data']['images']) ? count($processedFormData['data']['images']) : 0
            ]);

            // Handle images for the update
            if (isset($formData['images'])) {
                // Get existing image IDs from the database
                $existingImageIds = $product->images->pluck('id')->toArray();

                // Get image IDs from form data
                $formImageIds = collect($formData['images'])->pluck('id')->filter()->toArray();

                // Log image IDs for debugging
                Log::info('Image IDs for update', [
                    'product_id' => $product->id,
                    'existing_image_ids' => $existingImageIds,
                    'form_image_ids' => $formImageIds
                ]);

                // If form has no image IDs but there are existing images, we need to preserve them
                if (empty($formImageIds) && !empty($existingImageIds)) {
                    // No explicit image IDs in form data, so we're adding new images to existing ones
                    // Just ensure the paths are set correctly
                    if (isset($processedFormData['data']['images']) && !is_array($processedFormData['data']['images'])) {
                        // Convert to array if it's not already
                        $processedFormData['data']['images'] = [$processedFormData['data']['images']];
                    }
                } else {
                    // Find images to delete (images in database but not in form data)
                    $imagesToDelete = array_diff($existingImageIds, $formImageIds);

                    if (!empty($imagesToDelete)) {
                        // Structure the image data for the update
                        $processedFormData['data']['images'] = [
                            'paths' => isset($processedFormData['data']['images']) ? $processedFormData['data']['images'] : [],
                            'delete_ids' => $imagesToDelete
                        ];

                        Log::info('Images to delete during update', [
                            'product_id' => $product->id,
                            'delete_ids' => $imagesToDelete
                        ]);
                    }
                }
            } else {
                // No images in form data, preserve existing images
                Log::info('No images in form data, preserving existing images', [
                    'product_id' => $product->id,
                    'existing_image_count' => $product->images->count()
                ]);
            }

            // Log the processed form data for debugging
            Log::info('Processed form data for product update', [
                'session_id' => $request->session_id,
                'product_id' => $product->id,
                'has_basic_info' => isset($processedFormData['data']['basic_info']),
                'has_attributes' => isset($processedFormData['data']['attributes']) && !empty($processedFormData['data']['attributes']),
                'has_raw_attributes' => isset($processedFormData['data']['raw_attributes']) && !empty($processedFormData['data']['raw_attributes']),
                'has_images' => isset($processedFormData['data']['images']) && !empty($processedFormData['data']['images'])
            ]);

            // Use the ProductService to update the product
            $productData = $this->productService->updateProduct($product, $processedFormData);

            // Commit transaction
            DB::commit();

            // Clear session data
            $this->formService->clearFormData($request->session_id);

            return $this->respondWithSuccess('Product updated successfully', 200, [
                'product' => $productData
            ]);
        } catch (\Exception $e) {
            // Rollback transaction
            DB::rollBack();

            Log::error('Error updating product', ['error' => $e->getMessage(), 'product_id' => $product->id]);
            return $this->respondWithError('Error updating product: ' . $e->getMessage(), 500);
        }
    }
}
