<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ImageController;
use Illuminate\Support\Facades\Log;
use App\Traits\MultiStepFormTrait;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Services\MultiStepFormService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use App\Http\Resources\AttributeResource;
use App\Services\ProductService;
use App\Services\ProductImageService;
use App\Services\ProductAttributeService;
use Illuminate\Validation\Rule;


class ProductController extends ImprovedController
{
    protected const FORM_TYPE = 'product_form';
    protected const PRESERVE_SESSION_ERRORS = [
        'ValidationException',
        'InvalidArgumentException',
        'Undefined array key',
        'No images provided',
        'Please complete step 1 first',
        'attribute_values',
        'basic_info'
    ];

    protected $formService;
    protected $attributeService;
    protected $imageService;
    protected $productService;

    public function __construct(
        MultiStepFormService $formService,
        ProductAttributeService $attributeService,
        ProductImageService $imageService,
        ProductService $productService
    ) {
        $this->middleware(['auth:sanctum', 'verified'])->except(['index', 'show']);
        $this->formService = $formService;
        $this->attributeService = $attributeService;
        $this->imageService = $imageService;
        $this->productService = $productService;
    }

    public function index()
    {
        try {
            $products = Product::with(['images', 'subcategory', 'attributeValues.attribute'])
                ->latest()
                ->paginate(10);

            $formattedProducts = $products->map(function ($product) {
                // Make a copy of the attributeValues for our custom formatting
                $attributeValues = $product->attributeValues;

                // Remove the attributeValues relation to prevent it from being serialized
                $product->unsetRelation('attributeValues');

                $productData = $product->toArray();

                // Format attributes
                $formattedAttributes = [];
                foreach ($attributeValues as $value) {
                    if ($value->attribute) {
                        $formattedAttributes[] = [
                            'id' => $value->attribute->id,
                            'name' => $value->attribute->name,
                            'value' => [
                                'id' => $value->id,
                                'name' => $value->name,
                                'representation' => $value->representation
                            ]
                        ];
                    }
                }

                // Create a clean product object without attribute_values
                return [
                    'id' => $productData['id'],
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'price' => $productData['price'],
                    'subcategory_id' => $productData['subcategory_id'],
                    'user_id' => $productData['user_id'],
                    'is_active' => $productData['is_active'],
                    'created_at' => $productData['created_at'],
                    'updated_at' => $productData['updated_at'],
                    'subcategory' => $productData['subcategory'] ?? null,
                    'images' => $productData['images'] ?? [],
                    'attributes' => $formattedAttributes
                ];
            });

            // Maintain pagination data
            $response = $products->toArray();
            $response['data'] = $formattedProducts;

            return $this->respondWithSuccess('Products retrieved successfully', 200, [
                'products' => $response
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving products', ['error' => $e->getMessage()]);
            return $this->respondWithError(['message' => 'Error retrieving products'], 500);
        }
    }

    public function show(Product $product)
    {
        try {
            $product->load(['images', 'subcategory', 'attributeValues.attribute']);

            // Make a copy of the attributeValues for our custom formatting
            $attributeValues = $product->attributeValues;

            // Remove the attributeValues relation to prevent it from being serialized
            $product->unsetRelation('attributeValues');

            $productData = $product->toArray();

            // Format attributes
            $formattedAttributes = [];
            foreach ($attributeValues as $value) {
                if ($value->attribute) {
                    $formattedAttributes[] = [
                        'id' => $value->attribute->id,
                        'name' => $value->attribute->name,
                        'value' => [
                            'id' => $value->id,
                            'name' => $value->name,
                            'representation' => $value->representation
                        ]
                    ];
                }
            }

            // Create a clean response without attribute_values
            $response = [
                'id' => $productData['id'],
                'name' => $productData['name'],
                'description' => $productData['description'],
                'price' => $productData['price'],
                'subcategory_id' => $productData['subcategory_id'],
                'user_id' => $productData['user_id'],
                'is_active' => $productData['is_active'],
                'created_at' => $productData['created_at'],
                'updated_at' => $productData['updated_at'],
                'subcategory' => $productData['subcategory'] ?? null,
                'images' => $productData['images'] ?? [],
                'attributes' => $formattedAttributes
            ];

            return $this->respondWithSuccess('Product retrieved successfully', 200, ['product' => $response]);
        } catch (\Exception $e) {
            Log::error('Error retrieving product', ['error' => $e->getMessage()]);
            return $this->respondWithError(['message' => 'Error retrieving product'], 500);
        }
    }

    public function destroy(Product $product)
    {
        try {
            if (!auth()->user()->hasRole('seller') && auth()->id() !== $product->user_id) {
                return $this->respondWithError('Unauthorized', 403);
            }

            $this->productService->deleteProduct($product);
            return $this->respondWithSuccess('Product deleted successfully', 200);

        } catch (\Exception $e) {
            return $this->respondWithError([
                'message' => 'Error deleting product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createStep(Request $request)
    {
        try {
            // Determine which form type to use
            $formType = self::FORM_TYPE;

            return match ((int)$request->step) {
                2 => $this->handleAttributeStep($request),
                3 => $this->handleImageStep($request),
                default => $this->handleDefaultStep($request),
            };
        } catch (\Exception $e) {
            return $this->handleStepError($e, $request);
        }
    }

    public function updateStep(Request $request, Product $product)
    {
        try {
            // Validate that the user owns this product
            if (!auth()->user()->hasRole('seller') && auth()->id() !== $product->user_id) {
                return $this->respondWithError('Unauthorized', 403);
            }

            // Add product ID to the request
            $request->merge(['product_id' => $product->id]);

            return match ((int)$request->step) {
                2 => $this->handleUpdateAttributeStep($request, $product),
                3 => $this->handleUpdateImageStep($request, $product),
                default => $this->handleUpdateDefaultStep($request, $product),
            };
        } catch (\Exception $e) {
            return $this->handleStepError($e, $request);
        }
    }

    protected function handleAttributeStep(Request $request)
    {
        $session_id = $request->session_id;
        if (!$session_id) {
            return $this->respondWithError(['message' => 'Session ID is required'], 400);
        }

        $sessionKey = $this->formService->getSessionKey(self::FORM_TYPE, $session_id);
        $formData = Session::get($sessionKey);

        // Validate step order
        if (empty($formData) || !isset($formData['data']['basic_info'])) {
            return $this->respondWithError(['message' => 'Please complete step 1 first'], 400);
        }

        try {
            // Store product_id in the form data if it exists
            if ($request->has('product_id')) {
                $formData['product_id'] = $request->product_id;
                Session::put($sessionKey, $formData);
            }

            // Validate request format first
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string',
                'step' => 'required|integer|in:2',
                'attributes' => 'required|array',
                'attributes.*.attribute_id' => 'required|integer|exists:attributes,id',
                'attributes.*.value_id' => 'required|integer|exists:attribute_values,id',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError(['errors' => $validator->errors()], 422);
            }

            // Format the raw attributes for storage
            $rawAttributes = [];

            // Check if we have attributes in the request
            if ($request->has('attributes') && !empty($request->input('attributes'))) {
                // Get attributes from the request input
                $requestAttributes = $request->input('attributes');
                foreach ($requestAttributes as $attribute) {
                    $rawAttributes[] = [
                        'attribute_id' => $attribute['attribute_id'],
                        'value_id' => $attribute['value_id']
                    ];
                }
            }

            // Get the subcategory from form data
            $subcategoryId = $formData['data']['basic_info']['subcategory_id'];
            $subcategory = Subcategory::with(['attributes.values'])->findOrFail($subcategoryId);

            // Format the attributes for the response
            $attributesForResponse = [];

            // Get attribute and value details for each attribute in the request
            if (!empty($rawAttributes)) {
                foreach ($rawAttributes as $attribute) {
                    // Find the attribute in the subcategory
                    $attributeModel = $subcategory->attributes
                        ->where('id', $attribute['attribute_id'])
                        ->first();

                    if ($attributeModel) {
                        // Find the attribute value
                        $attributeValue = $attributeModel->values()
                            ->where('id', $attribute['value_id'])
                            ->first();

                        if ($attributeValue) {
                            $attributesForResponse[] = [
                                'id' => $attributeModel->id,
                                'name' => $attributeModel->name,
                                'value' => [
                                    'id' => $attributeValue->id,
                                    'name' => $attributeValue->name,
                                    'representation' => $attributeValue->representation
                                ]
                            ];
                        }
                    }
                }
            }

            // Process attributes with the original request
            $result = $this->formService->process($request, self::FORM_TYPE);

            if (!$result['success']) {
                return $this->respondWithError($result, 400);
            }

            // Update the form data with both raw and formatted attributes
            $formData = Session::get($sessionKey);
            $formData['data']['attributes'] = $attributesForResponse;
            $formData['data']['raw_attributes'] = $rawAttributes;
            Session::put($sessionKey, $formData);

            // Create a result structure with the formatted attributes
            $result['data']['attributes'] = $attributesForResponse;

            // Log the result for debugging
            Log::info('Attribute step result', [
                'attribute_count' => count($attributesForResponse),
                'raw_attribute_count' => count($rawAttributes)
            ]);

            return $this->respondWithSuccess('Attributes saved successfully', 200, $result);
        } catch (\Exception $e) {
            return $this->handleStepError($e, $request);
        }
    }

    protected function handleImageStep(Request $request)
    {
        $session_id = $request->session_id;
        if (!$session_id) {
            return $this->respondWithError(['message' => 'Session ID is required'], 400);
        }

        $sessionKey = $this->formService->getSessionKey(self::FORM_TYPE, $session_id);
        $formData = Session::get($sessionKey);

        // Validate step order
        if (empty($formData) || !isset($formData['data']['basic_info'])) {
            return $this->respondWithError(['message' => 'Please complete step 1 first'], 400);
        }

        try {
            // Store product_id in the form data if it exists
            if ($request->has('product_id')) {
                $formData['product_id'] = $request->product_id;
                Session::put($sessionKey, $formData);
            }

            // Validate request format first
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string',
                'step' => 'required|integer|in:3',
                'images' => 'required|array|min:1',
                'images.*' => 'required|file|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError(['errors' => $validator->errors()], 422);
            }

            // Handle image uploads
            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $imagePaths[] = $path;
            }

            // Store images in the correct format for ProductService
            $formData['data']['images'] = $imagePaths;
            Session::put($sessionKey, $formData);

            // Create a clean response without raw_attributes
            $responseData = [
                'session_id' => $session_id,
                'step' => 3,
                'data' => [
                    'basic_info' => $formData['data']['basic_info'] ?? [],
                    'attributes' => $formData['data']['attributes'] ?? [],
                    'images' => $imagePaths
                ],
                'expires_at' => now()->addHours(24)->toDateTimeString()
            ];

            // Log the response data for debugging
            Log::info('Image step response data', [
                'has_attributes' => !empty($responseData['data']['attributes']),
                'image_count' => count($responseData['data']['images'] ?? [])
            ]);

            return $this->respondWithSuccess('Images saved successfully', 200, $responseData);

        } catch (\Exception $e) {
            return $this->handleStepError($e, $request);
        }
    }

    protected function handleDefaultStep(Request $request)
    {
        return $this->processFormStep($request);
    }


    protected function processFormStep(Request $request)
    {
        $session_id = $request->session_id;
        if (!$session_id) {
            return $this->respondWithError(['message' => 'Session ID is required'], 400);
        }

        $sessionKey = $this->formService->getSessionKey(self::FORM_TYPE, $session_id);
        $formData = Session::get($sessionKey);

        // Store product_id in the form data if it exists
        if ($request->has('product_id')) {
            $formData['product_id'] = $request->product_id;
            Session::put($sessionKey, $formData);
        }

        $result = $this->formService->process($request, self::FORM_TYPE);

        if (!$result['success']) {
            if ($this->shouldClearSession($result)) {
                $this->formService->clear(self::FORM_TYPE, $session_id);
            }
            return $this->respondWithError($result, 400);
        }

        // If this is the basic info step, validate product name uniqueness
        if ($request->step === 1 && isset($result['data']['basic_info'])) {
            // For updates, pass the product_id to be ignored in uniqueness check
            $product = null;
            if (isset($formData['product_id'])) {
                $product = Product::find($formData['product_id']);
            }

            $validationResult = $this->validateBasicInfo($result['data']['basic_info'], $product);
            if (!$validationResult['success']) {
                return $this->respondWithError($validationResult, 422);
            }
        }

        return $this->respondWithSuccess('Step saved successfully', 200, $result);
    }

    protected function validateBasicInfo(array $basicInfo, ?Product $product = null): array
    {
        // Build validation rules based on what fields are present
        $rules = [];

        if (isset($basicInfo['name'])) {
            $rules['name'] = [
                'required',
                'string',
                'max:255',
                Rule::unique('products')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                })->ignore($product?->id)
            ];
        }

        if (isset($basicInfo['description'])) {
            $rules['description'] = 'required|string';
        }

        if (isset($basicInfo['price'])) {
            $rules['price'] = 'required|numeric|min:0';
        }

        if (isset($basicInfo['subcategory_id'])) {
            $rules['subcategory_id'] = 'required|exists:subcategories,id';
        }

        $validator = Validator::make($basicInfo, $rules);

        if ($validator->fails()) {
            return [
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors()->toArray()
            ];
        }

        return ['success' => true];
    }

    protected function shouldClearSession(array $result): bool
    {
        return isset($result['error']) && $result['error'] !== 'Validation failed';
    }

    protected function updateSessionWithImages(string $sessionId, array $paths): void
    {
        $sessionKey = $this->formService->getSessionKey(self::FORM_TYPE, $sessionId);
        $formData = Session::get($sessionKey);
        $formData['data']['images'] = $paths;
        Session::put($sessionKey, $formData);
    }

    public function getFormMetadata()
    {
        try {
            $config = $this->formService->getFormConfig(self::FORM_TYPE);
            return $this->respondWithSuccess('Form metadata retrieved', 200, [
                'session_id' => (string) Str::uuid(),
                'total_steps' => $config['total_steps'],
                'steps' => collect($config['steps'])->map(fn($step) => [
                    'label' => $step['label'],
                    'description' => $step['description']
                ])
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving form metadata', ['error' => $e->getMessage()]);
            return $this->respondWithError(['message' => 'Error retrieving form metadata'], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $session_id = $request->input('session_id');
            if (!$session_id) {
                return $this->respondWithError(['message' => 'Session ID is required'], 400);
            }

            // Validate form data before any database operations
            $data = $this->getValidatedFormData($request);

            // This method is only for creating new products
            if (isset($data['product_id'])) {
                return $this->respondWithError(['message' => 'Use the update endpoint for existing products'], 400);
            }

            // Create product within transaction
            $product = $this->productService->createProduct($data);

            // Only commit and clear session if everything succeeds
            DB::commit();
            $this->formService->clear(self::FORM_TYPE, $session_id);

            return $this->respondWithSuccess('Product created successfully', 201, $product);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Product creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'session_id' => $session_id
            ]);

            return $this->handleError($e, $request);
        }
    }

    protected function getValidatedFormData(Request $request): array
    {
        $session_id = $request->session_id;
        if (!$session_id) {
            throw new \InvalidArgumentException('Session ID is required');
        }

        $sessionKey = $this->formService->getSessionKey(self::FORM_TYPE, $session_id);
        $data = Session::get($sessionKey);

        if (empty($data)) {
            throw new \InvalidArgumentException('No form data found or form has expired');
        }

        if (!is_array($data) || !array_key_exists('data', $data)) {
            throw new \InvalidArgumentException('Invalid form data structure: missing data key');
        }

        if (!is_array($data['data']) || !array_key_exists('basic_info', $data['data'])) {
            throw new \InvalidArgumentException('Invalid form data structure: missing basic_info');
        }

        return $data;
    }

    public function getFormData($sessionId)
    {
        $sessionKey = $this->formService->getSessionKey(self::FORM_TYPE, $sessionId);
        $formData = Session::get($sessionKey);

        return $this->respondWithSuccess('Form data retrieved', 200, [
            'session_id' => $sessionId,
            'data' => $formData
        ]);
    }

    public function getSubcategoryAttributes(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'subcategory_id' => 'required|exists:subcategories,id'
            ]);

            if ($validator->fails()) {
                return $this->respondWithError(['errors' => $validator->errors()], 422);
            }

            $subcategory = Subcategory::with([
                'attributes' => function($query) {
                    $query->orderBy('display_order');
                },
                'attributes.values'
            ])->findOrFail($request->subcategory_id);

            // Format the response to be more frontend-friendly
            $attributes = $subcategory->attributes->map(function($attribute) {
                return [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'type' => $attribute->type,
                    'is_required' => $attribute->pivot->is_required,
                    'display_order' => $attribute->pivot->display_order,
                    'values' => $attribute->values->map(function($value) {
                        return [
                            'id' => $value->id,
                            'name' => $value->name,
                            'representation' => $value->representation
                        ];
                    })
                ];
            });

            return $this->respondWithSuccess('Attributes retrieved successfully', 200, [
                'attributes' => $attributes,
                'required_attributes' => $subcategory->attributes
                    ->where('pivot.is_required', true)
                    ->pluck('name')
            ]);

        } catch (\Exception $e) {
            return $this->respondWithError([
                'message' => 'Error retrieving attributes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Product $product)
    {
        DB::beginTransaction();
        try {
            if (!auth()->user()->hasRole('seller') && auth()->id() !== $product->user_id) {
                return $this->respondWithError('Unauthorized', 403);
            }

            $session_id = $request->input('session_id');
            if (!$session_id) {
                return $this->respondWithError(['message' => 'Session ID is required'], 400);
            }

            // Get form data using the same form type as in the steps
            $data = $this->getValidatedFormData($request);

            // Add product ID to the data if not already present
            $data['product_id'] = $product->id;

            // Use the dedicated update method
            $productData = $this->productService->updateProduct($product, $data);

            // The ProductService already formats the data correctly, so we can use it directly
            $responseData = $productData;

            // Log the response data for debugging
            Log::info('Product update response data', [
                'product_id' => $product->id,
                'attribute_count' => count($responseData['attributes'] ?? []),
                'has_images' => !empty($responseData['images'])
            ]);

            DB::commit();
            $this->formService->clear(self::FORM_TYPE, $session_id);

            return $this->respondWithSuccess('Product updated successfully', 200, $responseData);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product update error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'product_id' => $product->id,
                'session_id' => $session_id
            ]);
            return $this->handleError($e, $request);
        }
    }

    protected function handleUpdateAttributeStep(Request $request, Product $product)
    {
        $session_id = $request->session_id;
        if (!$session_id) {
            return $this->respondWithError(['message' => 'Session ID is required'], 400);
        }

        $sessionKey = $this->formService->getSessionKey(self::FORM_TYPE, $session_id);
        $formData = Session::get($sessionKey);

        // Validate step order
        if (empty($formData) || !isset($formData['data']['basic_info'])) {
            return $this->respondWithError(['message' => 'Please complete step 1 first'], 400);
        }

        try {
            // Store product_id in the form data
            $formData['product_id'] = $product->id;
            Session::put($sessionKey, $formData);

            // Validate request format first
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string',
                'step' => 'required|integer|in:2',
                'attributes' => 'required|array',
                'attributes.*.attribute_id' => 'required|integer|exists:attributes,id',
                'attributes.*.value_id' => 'required|integer|exists:attribute_values,id',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError(['errors' => $validator->errors()], 422);
            }

            // Format the raw attributes for storage
            $rawAttributes = [];

            // Check if we have attributes in the request
            if ($request->has('attributes') && !empty($request->input('attributes'))) {
                // Get attributes from the request input
                $requestAttributes = $request->input('attributes');

                // Log the request attributes for debugging
                Log::info('Attributes from request', [
                    'product_id' => $product->id,
                    'attributes' => $requestAttributes
                ]);

                foreach ($requestAttributes as $attribute) {
                    if (isset($attribute['attribute_id']) && isset($attribute['value_id'])) {
                        $rawAttributes[] = [
                            'attribute_id' => $attribute['attribute_id'],
                            'value_id' => $attribute['value_id']
                        ];
                    } else {
                        Log::warning('Invalid attribute format in request', [
                            'product_id' => $product->id,
                            'attribute' => $attribute
                        ]);
                    }
                }
            } else {
                // If no attributes in request, log this
                Log::warning('No attributes found in request', [
                    'product_id' => $product->id,
                    'request_data' => $request->all()
                ]);
            }

            // Load the product with its subcategory to get attribute information
            $product->load(['subcategory.attributes', 'attributeValues.attribute']);

            // Format the attributes for the response
            $attributesForResponse = [];

            // Get attribute and value details for each attribute in the request
            if (!empty($rawAttributes)) {
                // Log the raw attributes for debugging
                Log::info('Processing raw attributes', [
                    'product_id' => $product->id,
                    'raw_attributes' => $rawAttributes
                ]);

                foreach ($rawAttributes as $attribute) {
                    // Find the attribute in the subcategory
                    $attributeModel = Attribute::find($attribute['attribute_id']);

                    if ($attributeModel) {
                        // Find the attribute value
                        $attributeValue = AttributeValue::find($attribute['value_id']);

                        if ($attributeValue) {
                            $attributesForResponse[] = [
                                'id' => $attributeModel->id,
                                'name' => $attributeModel->name,
                                'value' => [
                                    'id' => $attributeValue->id,
                                    'name' => $attributeValue->name,
                                    'representation' => $attributeValue->representation
                                ]
                            ];
                        } else {
                            Log::warning('Attribute value not found', [
                                'product_id' => $product->id,
                                'attribute_id' => $attribute['attribute_id'],
                                'value_id' => $attribute['value_id']
                            ]);
                        }
                    } else {
                        Log::warning('Attribute not found', [
                            'product_id' => $product->id,
                            'attribute_id' => $attribute['attribute_id']
                        ]);
                    }
                }
            } else {
                // If no attributes in request, use existing product attributes
                // Make sure to load the product with its attribute values and their attributes
                $product->load(['attributeValues.attribute']);

                if ($product->attributeValues->count() > 0) {
                    foreach ($product->attributeValues as $attributeValue) {
                        if ($attributeValue->attribute) {
                            $attributesForResponse[] = [
                                'id' => $attributeValue->attribute->id,
                                'name' => $attributeValue->attribute->name,
                                'value' => [
                                    'id' => $attributeValue->id,
                                    'name' => $attributeValue->name,
                                    'representation' => $attributeValue->representation ?? ''
                                ]
                            ];

                            // Also add to raw attributes
                            $rawAttributes[] = [
                                'attribute_id' => $attributeValue->attribute->id,
                                'value_id' => $attributeValue->id
                            ];
                        }
                    }

                    Log::info('Using existing product attributes', [
                        'product_id' => $product->id,
                        'attribute_count' => $product->attributeValues->count(),
                        'formatted_attributes' => $attributesForResponse
                    ]);
                } else {
                    Log::info('No existing product attributes found', [
                        'product_id' => $product->id
                    ]);
                }
            }

            // Update the form data with both raw and formatted attributes
            $formData['data']['attributes'] = $attributesForResponse;
            $formData['data']['raw_attributes'] = $rawAttributes;

            // Save the updated form data to the session
            Session::put($sessionKey, $formData);

            // Log the attribute data for debugging
            Log::info('Attribute update step data', [
                'product_id' => $product->id,
                'request_attributes' => $request->has('attributes') ? $request->input('attributes') : [],
                'raw_attributes' => $rawAttributes,
                'formatted_attributes' => $attributesForResponse
            ]);

            // Create a result structure similar to what the form service would return
            $result = [
                'success' => true,
                'session_id' => $session_id,
                'step' => 2,
                'data' => [
                    'basic_info' => $formData['data']['basic_info'] ?? [],
                    'attributes' => $attributesForResponse
                ],
                'expires_at' => now()->addHours(24)->toDateTimeString()
            ];

            // Store raw_attributes in the session but don't include in the response
            $formData['data']['raw_attributes'] = $rawAttributes;

            // Log the result for debugging
            Log::info('Attribute step result', [
                'product_id' => $product->id,
                'attribute_count' => count($attributesForResponse),
                'raw_attribute_count' => count($rawAttributes)
            ]);

            return $this->respondWithSuccess('Attributes saved successfully', 200, $result);
        } catch (\Exception $e) {
            return $this->handleStepError($e, $request);
        }
    }

    protected function handleUpdateImageStep(Request $request, Product $product)
    {
        $session_id = $request->session_id;
        if (!$session_id) {
            return $this->respondWithError(['message' => 'Session ID is required'], 400);
        }

        $sessionKey = $this->formService->getSessionKey(self::FORM_TYPE, $session_id);
        $formData = Session::get($sessionKey);

        // Validate step order
        if (empty($formData) || !isset($formData['data']['basic_info'])) {
            return $this->respondWithError(['message' => 'Please complete step 1 first'], 400);
        }

        try {
            // Store product_id in the form data
            $formData['product_id'] = $product->id;
            Session::put($sessionKey, $formData);

            // Validate request format first
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string',
                'step' => 'required|integer|in:3',
                'images' => 'required|array|min:1',
                'images.*' => 'required|file|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError(['errors' => $validator->errors()], 422);
            }

            // Handle image uploads
            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $imagePaths[] = $path;
            }

            // Store images in the correct format for ProductService
            $formData['data']['images'] = $imagePaths;

            // Create a clean data structure without raw_attributes
            $cleanData = [
                'basic_info' => $formData['data']['basic_info'] ?? [],
                'attributes' => $formData['data']['attributes'] ?? [],
                'images' => $imagePaths
            ];

            // Preserve raw_attributes in the session but not in the response
            // We don't need to do anything special here, just make sure not to include it in cleanData

            // Update the form data with the clean data structure
            $formData['data'] = array_merge($formData['data'], $cleanData);
            Session::put($sessionKey, $formData);

            // Create the response without raw_attributes
            $responseData = [
                'session_id' => $session_id,
                'step' => 3,
                'data' => $cleanData,
                'expires_at' => now()->addHours(24)->toDateTimeString()
            ];

            // Log the response data for debugging
            Log::info('Image step response data', [
                'has_raw_attributes' => isset($responseData['data']['raw_attributes']),
                'has_attributes' => !empty($responseData['data']['attributes']),
                'image_count' => count($responseData['data']['images'] ?? [])
            ]);

            return $this->respondWithSuccess('Images saved successfully', 200, $responseData);

        } catch (\Exception $e) {
            return $this->handleStepError($e, $request);
        }
    }

    protected function handleUpdateDefaultStep(Request $request, Product $product)
    {
        $session_id = $request->session_id;
        if (!$session_id) {
            return $this->respondWithError(['message' => 'Session ID is required'], 400);
        }

        $sessionKey = $this->formService->getSessionKey(self::FORM_TYPE, $session_id);
        $formData = Session::get($sessionKey);

        // Store product_id in the form data
        $formData['product_id'] = $product->id;
        Session::put($sessionKey, $formData);

        $result = $this->formService->process($request, self::FORM_TYPE);

        if (!$result['success']) {
            if ($this->shouldClearSession($result)) {
                $this->formService->clear(self::FORM_TYPE, $session_id);
            }
            return $this->respondWithError($result, 400);
        }

        // If this is the basic info step, validate product name uniqueness
        if ($request->step === 1 && isset($result['data']['basic_info'])) {
            $validationResult = $this->validateBasicInfo($result['data']['basic_info'], $product);
            if (!$validationResult['success']) {
                return $this->respondWithError($validationResult, 422);
            }
        }

        // Make sure the response doesn't include raw_attributes
        if (isset($result['data']) && isset($result['data']['raw_attributes'])) {
            unset($result['data']['raw_attributes']);
        }

        return $this->respondWithSuccess('Step saved successfully', 200, $result);
    }

    protected function handleStepError(\Exception $e, Request $request)
    {
        Log::error('Step processing error', [
            'error' => $e->getMessage(),
            'step' => $request->step,
            'session_id' => $request->session_id
        ]);

        // Only clear session for truly critical errors
        $shouldClearSession = false; // Default to not clearing

        // List of errors that should NOT clear the session
        $preserveSessionErrors = [
            'ValidationException',
            'InvalidArgumentException',
            'Undefined array key',
            'No images provided',
            'Please complete step 1 first'
        ];

        // Check if this is an error that should preserve the session
        foreach ($preserveSessionErrors as $errorType) {
            if ($e instanceof ValidationException ||
                str_contains($e->getMessage(), $errorType)) {
                $shouldClearSession = false;
                break;
            }
        }

        if ($shouldClearSession) {
            $this->formService->clear('product_form', $request->session_id);
        }

        return $this->respondWithError([
            'message' => 'Error processing step',
            'error' => $e->getMessage()
        ], $e instanceof ValidationException ? 422 : 500);
    }

    protected function handleError(\Exception $e, Request $request)
    {
        Log::error('Product error', [
            'error' => $e->getMessage(),
            'session_id' => $request->session_id,
            'trace' => $e->getTraceAsString()
        ]);

        // List of errors that should NOT clear the session
        $preserveSessionErrors = [
            'ValidationException',
            'InvalidArgumentException',
            'Undefined array key',
            'attribute_values',
            'basic_info'
        ];

        $shouldClearSession = true; // Default to clearing

        // Check if this is an error that should preserve the session
        foreach ($preserveSessionErrors as $errorType) {
            if ($e instanceof ValidationException ||
                str_contains($e->getMessage(), $errorType)) {
                $shouldClearSession = false;
                break;
            }
        }

        if ($shouldClearSession && $request->session_id) {
            $this->formService->clear('product_form', $request->session_id);
        }

        return $this->respondWithError([
            'message' => 'Error processing request',
            'error' => $e->getMessage()
        ], $e instanceof ValidationException ? 422 : 500);
    }
}
