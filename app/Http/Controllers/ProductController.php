<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use Illuminate\Http\Request;
use App\Models\Product;
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

class ProductController extends ImprovedController
{
    protected $formService;

    public function __construct(MultistepFormService $formService) 
    {
        $this->middleware(['auth:sanctum', 'verified'])->except(['index', 'show']);
        $this->formService = $formService;
    }

    public function saveStep(Request $request)
    {
        try {
            $result = $this->formService->process($request, 'product_form');
            
            if (!$result['success']) {
                return $this->respondWithError($result, 400);
            }

            // Handle file uploads after validation
            if ($request->step == 2 && $request->hasFile('images')) {
                $uploadedFiles = [];
                foreach ($request->file('images') as $image) {
                    // Use putFile with public disk to ensure correct path
                    $path = Storage::disk('public')->putFile('products', $image);
                    $uploadedFiles[] = $path;
                }
                
                // Directly update session data
                $sessionKey = 'form_data_product_form';
                $formData = Session::get($sessionKey, []);
                
                $formData['data'] = $formData['data'] ?? [];
                $formData['data']['images'] = $uploadedFiles;
                
                Session::put($sessionKey, $formData);

                // Update result to reflect file paths
                $result['data']['data']['images'] = $uploadedFiles;
            }

            return $this->respondWithSuccess('Step saved successfully', 200, $result);
        } catch (\Exception $e) {
            return $this->respondWithError([
                'message'=> 'Error saving step', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFormMetadata()
    {
        $config = $this->formService->getFormConfig('product_form');
        
        return $this->respondWithSuccess('Form metadata retrieved', 200, [
            'total_steps' => $config['total_steps'],
            'steps' => collect($config['steps'])->map(function($step) {
                return [
                    'label' => $step['label'],
                    'description' => $step['description']
                ];
            })
        ]);
    }

    public function store(Request $request)
    {
        try {
            if (!$request->session_id) {
                return $this->respondWithError(['message' => 'Session ID is required'], 400);
            }

            // Get all form data
            $data = $this->formService->getData('product_form', $request->session_id);
            
            // Validate that we have the required data
            if (empty($data)) {
                return $this->respondWithError(['message' => 'No form data found or form has expired'], 400);
            }

            // Verify we have all required fields
            $requiredFields = ['name', 'description', 'price', 'subcategory_id'];
            $missingFields = array_filter($requiredFields, function($field) use ($data) {
                return !isset($data[$field]);
            });

            if (!empty($missingFields)) {
                return $this->respondWithError([
                    'message' => 'Missing required fields',
                    'fields' => $missingFields
                ], 400);
            }

            // Start DB transaction
            return DB::transaction(function () use ($data, $request) {
                // Create the product
                $product = Product::create([
                    'user_id' => auth()->id(),
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'subcategory_id' => $data['subcategory_id'],
                ]);

                // Handle images if they exist
                if (isset($data['images']) && is_array($data['images'])) {
                    $this->handleProductImagesFromTemp($data['images'], $product);
                }

                // Clear temporary form data
                $this->formService->clear('product_form', $request->session_id);

                return $this->respondWithSuccess('Product created successfully', 201, $product->fresh('images'));
            });

        } catch (\Exception $e) {
            \Log::error('Product creation error: ' . $e->getMessage(), [
                'session_id' => $request->session_id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->respondWithError([
                'message' => 'Error creating product', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStep(Request $request, Product $product)
    {
        try {
            if (!auth()->user()->hasRole('seller') && auth()->id() !== $product->user_id ) {
                return $this->respondWithError('Unauthorized', 403);
            }

            $rules = $this->getValidationRules($request->step);
            $result = $this->formService->process($request, 'product_update_' . $product->id, $rules);

            return $this->respondWithSuccess('Step saved successfully', 200, $result);

        } catch (\Exception $e) {
            return $this->respondWithError(['message' => 'Error saving step',  $e->getMessage()], 500,);
        }
    }

    public function update(Request $request, Product $product)
    {
        try {
            if (!auth()->user()->hasRole('seller') && auth()->id() !== $product->user_id ) {
                return $this->respondWithError('Unauthorized', 403);
            }

            // Get all form data
            $data = $this->formService->getData('product_update_' . $product->id, $request->session_id);

            // Start DB transaction
            return DB::transaction(function () use ($data, $product) {
                // Update the product
                $product->update([
                    'name' => $data['name'] ?? $product->name,
                    'description' => $data['description'] ?? $product->description,
                    'price' => $data['price'] ?? $product->price,
                    'subcategory_id' => $data['subcategory_id'] ?? $product->subcategory_id,
                ]);

                // Handle images if they exist
                if (isset($data['images'])) {
                    // Delete old images
                    $product->images()->delete();
                    $this->handleProductImages($data['images'], $product);
                }

                // Clear temporary form data
                $this->formService->clear('product_update_' . $product->id, $request->session_id);

                return $this->respondWithSuccess('Product updated successfully', 200, $product->fresh('images'));
            });

        } catch (\Exception $e) {
            return $this->respondWithError(['message' => 'Error updating product', $e->getMessage()], 500);
        }
    }

    protected function handleProductImages($images, Product $product)
    {
        foreach ($images as $image) {
            $tempPath = Storage::disk('temp')->path($image);
            $extension = $this->getFileType(new \Illuminate\Http\UploadedFile($tempPath, $image));
            
            // Store with proper extension
            $path = Storage::disk('public')->putFileAs(
                'product-images/' . $product->id, 
                $tempPath,
                'image_' . time() . '_' . uniqid() . '.' . $extension
            );

            // Create image record
            $product->images()->create(['path' => $path]);
        }
    }

    protected function getFileType($file)
    {
        $mimeType = $file->getMimeType();
        
        return match($mimeType) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            default => throw new \Exception('Unsupported file type')
        };
    }

    protected function handleFileUpload($images)
    {
        $uploadedFiles = [];
        foreach ($images as $image) {
            // Store the temporary file directly in public storage
            $path = $image->store('temp-product-images', 'public');
            $uploadedFiles[] = $path;
        }
        return $uploadedFiles;
    }

    protected function handleProductImagesFromTemp($tempImages, Product $product)
    {
        foreach ($tempImages as $tempImagePath) {
            // Ensure the path is relative to the public disk
            $fullTempPath = Storage::disk('public')->path($tempImagePath);
            $extension = pathinfo($fullTempPath, PATHINFO_EXTENSION);
            
            // Move file to final product images location
            $finalPath = Storage::disk('public')->putFileAs(
                'product-images/' . $product->id, 
                $fullTempPath,
                'image_' . time() . '_' . uniqid() . '.' . $extension
            );

            // Create image record
            $product->images()->create(['path' => $finalPath]);

            // Clean up temp file
            Storage::disk('public')->delete($tempImagePath);
        }
    }
}
