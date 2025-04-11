<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Subcategory;
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
                $data = $product->toArray();
                
                // Format attributes
                $groupedAttributes = [];
                foreach ($product->attributeValues as $value) {
                    $groupedAttributes[] = [
                        'id' => $value->attribute->id,
                        'name' => $value->attribute->name,
                        'value' => [
                            'id' => $value->id,
                            'name' => $value->name,
                            'representation' => $value->representation
                        ]
                    ];
                }
                
                $data['attributes'] = $groupedAttributes;
                unset($data['attribute_values']);
                
                return $data;
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
            
            $response = $product->toArray();
            
            // Format attributes
            $groupedAttributes = [];
            foreach ($product->attributeValues as $value) {
                $groupedAttributes[] = [
                    'id' => $value->attribute->id,
                    'name' => $value->attribute->name,
                    'value' => [
                        'id' => $value->id,
                        'name' => $value->name,
                        'representation' => $value->representation
                    ]
                ];
            }
            
            $response['attributes'] = $groupedAttributes;
            unset($response['attribute_values']);
            
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

    public function saveStep(Request $request)
    {
        try {
            return match ((int)$request->step) {
                2 => $this->handleAttributeStep($request),
                3 => $this->handleImageStep($request),
                default => $this->handleDefaultStep($request),
            };
        } catch (\Exception $e) {
            return $this->handleStepError($e, $request);
        }
    }

    protected function handleAttributeStep(Request $request)
    {
        if (!$request->session_id) {
            return $this->respondWithError(['message' => 'Session ID is required'], 400);
        }

        $sessionKey = $this->formService->getSessionKey(self::FORM_TYPE, $request->session_id);
        $formData = Session::get($sessionKey);

        // Validate step order
        if (empty($formData) || !isset($formData['data']['basic_info'])) {
            return $this->respondWithError(['message' => 'Please complete step 1 first'], 400);
        }

        try {
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

            // Process attributes
            $result = $this->formService->process($request, self::FORM_TYPE);
            
            if (!$result['success']) {
                return $this->respondWithError($result, 400);
            }
            
            return $this->respondWithSuccess('Attributes saved successfully', 200, $result);
        } catch (\Exception $e) {
            return $this->handleStepError($e, $request);
        }
    }

    protected function handleImageStep(Request $request)
    {
        if (!$request->hasFile('images')) {
            return $this->respondWithError(['message' => 'No images provided'], 400);
        }

        $paths = $this->imageService->handleImageStep($request);
        $this->updateSessionWithImages($request->session_id, $paths);

        return $this->respondWithSuccess('Images uploaded successfully', 200, ['paths' => $paths]);
    }

    protected function handleDefaultStep(Request $request)
    {
        return $this->processFormStep($request);
    }

    protected function processFormStep(Request $request)
    {
        $result = $this->formService->process($request, self::FORM_TYPE);
        
        if (!$result['success']) {
            if ($this->shouldClearSession($result)) {
                $this->formService->clear(self::FORM_TYPE, $request->session_id);
            }
            return $this->respondWithError($result, 400);
        }

        // If this is the basic info step, validate product name uniqueness
        if ($request->step === 1 && isset($result['data']['basic_info'])) {
            $validationResult = $this->validateBasicInfo($result['data']['basic_info']);
            if (!$validationResult['success']) {
                return $this->respondWithError($validationResult, 422);
            }
        }

        return $this->respondWithSuccess('Step saved successfully', 200, $result);
    }

    protected function validateBasicInfo(array $basicInfo, ?Product $product = null): array
    {
        $validator = Validator::make($basicInfo, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                })->ignore($product?->id)
            ],
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'subcategory_id' => 'required|exists:subcategories,id'
        ]);

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
            // Validate form data before any database operations
            $data = $this->getValidatedFormData($request);
            
            // Create product within transaction
            $product = $this->productService->createProduct($data);
            
            // Only commit and clear session if everything succeeds
            DB::commit();
            $this->formService->clear(self::FORM_TYPE, $request->session_id);
            
            return $this->respondWithSuccess('Product created successfully', 201, $product);
            
        } catch (\Exception $e) {
            // Always rollback on any exception
            DB::rollBack();
            
            Log::error('Product creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'session_id' => $request->session_id
            ]);
            
            return $this->handleError($e, $request);
        }
    }

    protected function getValidatedFormData(Request $request): array
    {
        if (!$request->session_id) {
            throw new \InvalidArgumentException('Session ID is required');
        }

        $sessionKey = $this->formService->getSessionKey(self::FORM_TYPE, $request->session_id);
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

    public function updateStep(Request $request, Product $product)
    {
        try {
            if (!auth()->user()->hasRole('seller') && auth()->id() !== $product->user_id) {
                return $this->respondWithError('Unauthorized', 403);
            }

            $formType = 'product_update_' . $product->id;

            return match ((int)$request->step) {
                2 => $this->handleUpdateAttributeStep($request, $product),
                3 => $this->handleUpdateImageStep($request, $product),
                default => $this->handleUpdateDefaultStep($request, $product),
            };
        } catch (\Exception $e) {
            return $this->handleStepError($e, $request);
        }
    }

    protected function handleUpdateDefaultStep(Request $request, Product $product)
    {
        if (!$request->session_id) {
            return $this->respondWithError(['message' => 'Session ID is required'], 400);
        }

        $result = $this->formService->process($request, self::FORM_TYPE);

        if (!$result['success']) {
            if ($this->shouldClearSession($result)) {
                $this->formService->clear(self::FORM_TYPE, $request->session_id);
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

        return $this->respondWithSuccess('Basic info updated successfully', 200, $result);
    }

    protected function handleUpdateAttributeStep(Request $request, Product $product)
    {
        if (!$request->session_id) {
            return $this->respondWithError(['message' => 'Session ID is required'], 400);
        }

        try {
            $result = $this->attributeService->handleAttributeStep([
                'session_id' => $request->session_id,
                'attributes' => $request->input('attributes', []),
                'product_id' => $product->id
            ]);

            return $this->respondWithSuccess('Attributes updated successfully', 200, [
                'session_id' => $request->session_id,
                'attributes' => AttributeResource::collection($product->subcategory->attributes),
                'saved_values' => $result['saved_values'] ?? []
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating attribute step', [
                'error' => $e->getMessage(),
                'product_id' => $product->id,
                'session_id' => $request->session_id
            ]);
            return $this->respondWithError(['message' => $e->getMessage()], 400);
        }
    }

    protected function handleUpdateImageStep(Request $request, Product $product)
    {
        if (!$request->hasFile('images')) {
            return $this->respondWithError(['message' => 'No images provided'], 400);
        }

        $paths = $this->imageService->handleImageStep($request);
        $this->updateSessionWithImages($request->session_id, $paths, 'product_update_' . $product->id);

        return $this->respondWithSuccess('Images updated successfully', 200, ['paths' => $paths]);
    }

    public function update(Request $request, Product $product)
    {
        DB::beginTransaction();
        try {
            if (!auth()->user()->hasRole('seller') && auth()->id() !== $product->user_id) {
                return $this->respondWithError('Unauthorized', 403);
            }

            $formType = 'product_update_' . $product->id;
            $data = $this->formService->getData($formType, $request->session_id);

            if (empty($data)) {
                return $this->respondWithError(['message' => 'No form data found or form has expired'], 400);
            }

            $updatedProduct = $this->productService->updateProduct($product, $data);
            
            DB::commit();
            $this->formService->clear($formType, $request->session_id);

            return $this->respondWithSuccess('Product updated successfully', 200, $updatedProduct);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product update error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'product_id' => $product->id,
                'session_id' => $request->session_id
            ]);
            return $this->handleError($e, $request);
        }
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

    public function getFormData($sessionId)
    {
        $sessionKey = $this->formService->getSessionKey('product_form', $sessionId);
        $formData = Session::get($sessionKey);
        
        return $this->respondWithSuccess('Form data retrieved', 200, [
            'session_id' => $sessionId,
            'data' => $formData
        ]);
    }
}

