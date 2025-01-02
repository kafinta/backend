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

class ProductController extends ImprovedController
{

    protected $multiStepFormService;

    public function __construct(MultistepFormService $formService) 
    {
        $this->formService = $formService;
        $this->middleware(['auth:sanctum', 'verified'])->except(['index', 'show']);
        
        $this->formService->addStep('details', [
            'name' => 'sometimes|string',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'subcategory_id' => 'sometimes|exists:subcategories,id',
        ])
        ->addStep('images', [
            'images' => 'sometimes|array',
            'images.*' => 'image|max:2048'
        ]);
    }









    public function index()
    {
        $products = Product::with('images')->get();
        
        if ($products->isEmpty()) {
            return $this->respondWithError('No products found', 404);
        }
        
        return $this->respondWithSuccess('Products fetched successfully', 200, $products);
    }

    public function show($id)
    {
        try {
            $product = Product::with('images')->findOrFail($id);
            return $this->respondWithSuccess('Product fetched successfully', 200, $product);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->respondWithError('Product not found', 404);
        }
    }

    public function store(Request $request)
    {
        try {
            if (!auth()->user()->can('create', Product::class)) {
                return $this->respondWithError('Unauthorized', 403);
            }

            $result = $this->formService->process($request);
            
            if (!$result['success']) {
                return $this->respondWithError('Validation failed', 422, $result['errors']);
            }
            
            if (!$result['completed']) {
                return $this->respondWithSuccess('Step completed successfully', 200, [
                    'next_step' => $result['nextStep']
                ]);
            }
            
            // If we reach here, the form is completed and we can create the product
            $formData = $result['data'];
            
            // Validate that we have the required data
            if (!isset($formData['details'])) {
                return $this->respondWithError('Product details are missing', 422);
            }
            
            DB::beginTransaction();
            try {
                // Create the product with null coalescing operators for safety
                $product = Product::create([
                    'name' => $formData['details']['name'] ?? null,
                    'description' => $formData['details']['description'] ?? null,
                    'price' => $formData['details']['price'] ?? null,
                    'subcategory_id' => $formData['details']['subcategory_id'] ?? null,
                    'user_id' => auth()->id()
                ]);
                
                // Handle images using existing storeImages method
                if (isset($formData['images']['images'])) {
                    $files = [];
                    foreach ($formData['images']['images'] as $imageData) {
                        $path = Storage::disk('public')->path($imageData['path']);
                        $files[] = new \Illuminate\Http\UploadedFile(
                            $path,
                            $imageData['original_name'],
                            $imageData['mime_type'],
                            null,
                            true
                        );
                    }
                    
                    // Create a new request with the reconstructed files
                    $imageRequest = new Request();
                    $imageRequest->files->set('images', $files);
                    
                    $imageResult = $this->storeImages($imageRequest, $product);
                    
                    // Clean up temporary files
                    foreach ($formData['images']['images'] as $imageData) {
                        Storage::disk('public')->delete($imageData['path']);
                    }
                } else {
                    return $this->respondWithError('Upload at least one image', 422);
                }
                
                DB::commit();
                
                return $this->respondWithSuccess('Product created successfully', 201, [
                    'data' => $product->fresh(['images']),
                    'images' => $imageResult ?? ['messages' => ['No images processed']]
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->respondWithError('Failed to create product', 500, $e->getMessage());
        }
    }



    public function update(Request $request, Product $product)
    {
        try {
            if (!auth()->user()->can('update', $product)) {
                return $this->respondWithError('Unauthorized', 403);
            }

            $result = $this->formService->process($request);
            
            if (!$result['success']) {
                return $this->respondWithError('Validation failed', 422, $result['errors']);
            }
            
            if (!$result['completed']) {
                return $this->respondWithSuccess('Step completed successfully', 200, [
                    'next_step' => $result['nextStep']
                ]);
            }
            
            // If we reach here, the form is completed and we can update the product
            $formData = $result['data'];
            
            DB::beginTransaction();
            try {
                // Update only the provided fields
                if (isset($formData['details'])) {
                    $updateData = [];
                    if (isset($formData['details']['name'])) {
                        $updateData['name'] = $formData['details']['name'];
                    }
                    if (isset($formData['details']['description'])) {
                        $updateData['description'] = $formData['details']['description'];
                    }
                    if (isset($formData['details']['price'])) {
                        $updateData['price'] = $formData['details']['price'];
                    }
                    if (isset($formData['details']['subcategory_id'])) {
                        $updateData['subcategory_id'] = $formData['details']['subcategory_id'];
                    }
                    
                    if (!empty($updateData)) {
                        $product->update($updateData);
                    }
                }
                
                // Handle images only if new images are provided
                if (isset($formData['images']['images'])) {
                    // Delete old images if needed
                    $product->images()->delete();
                    
                    $files = [];
                    foreach ($formData['images']['images'] as $imageData) {
                        $path = Storage::disk('public')->path($imageData['path']);
                        $files[] = new \Illuminate\Http\UploadedFile(
                            $path,
                            $imageData['original_name'],
                            $imageData['mime_type'],
                            null,
                            true
                        );
                    }
                    
                    // Create a new request with the reconstructed files
                    $imageRequest = new Request();
                    $imageRequest->files->set('images', $files);
                    
                    $imageResult = $this->updateImages($imageRequest, $product);
                    
                    // Clean up temporary files
                    foreach ($formData['images']['images'] as $imageData) {
                        Storage::disk('public')->delete($imageData['path']);
                    }
                }
                
                DB::commit();
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Product updated successfully',
                    'data' => $product->fresh(['images']),
                    'images' => $imageResult ?? ['messages' => ['No images processed']]
                ], 200);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function destroy($id)
    {
        $product = Product::find($id);
        
        if (!$product) {
            return $this->respondWithError('Product not found', 404);
        }

        if (!auth()->user()->can('delete', $product)) {
            return $this->respondWithError('Unauthorized', 403);
        }

        DB::beginTransaction();
        try {
            // Delete associated images from storage
            foreach ($product->images as $image) {
                try {
                    $path = str_replace('/storage/', '', $image->path);
                    $this->deleteOldImage($path);
                    $image->delete();
                } catch (\Exception $e) {
                    Log::error("Failed to delete image {$image->id}: " . $e->getMessage());
                    // Continue with other images even if one fails
                }
            }
            
            $product->delete();
            DB::commit();
            
            return $this->respondWithSuccess('Product deleted successfully', 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respondWithError('Failed to delete product', 500, $e->getMessage());
        }
    }








    public function resumeForm()
    {
        $currentStep = $this->formService->getCurrentStep();
        $storedData = $this->formService->getStoredData();
        
        if ($storedData) {
            return $this->respondWithSuccess('Form resumed successfully', 200, [
                'current_step' => $currentStep,
                'form_data' => $storedData
            ]);
        }
        
        return $this->respondWithError('No form in progress', 404);
    }
    
    

    // Method to clear form progress
    public function clearFormProgress()
    {
        $this->multiStepFormService->clearMultiStepForm();
        return $this->respondWithSuccess('Form progress cleared', 200);
    }




    private function storeImages(Request $request, Product $product)
    {
        if (!$request->hasFile('images')) {
            return [
                'messages' => ['No images found in request'],
                'images' => []
            ];
        }

        $imageController = new ImageController();
        $images = [];
        
        foreach ($request->file('images') as $imageFile) {
            $newRequest = new Request();
            $newRequest->files->set('images', $imageFile);
            
            $storedImage = $imageController->store($newRequest, $product);
            $images[] = $storedImage;
        }

        return [
            'messages' => ['Images uploaded successfully'],
            'images' => $images
        ];
    }

    private function updateImages(Request $request, Product $product)
    {
        $imageController = new ImageController();
        $images = [];
        
        $imageFiles = $request->allFiles();
        
        if (isset($imageFiles['images'])) {
            foreach ($imageFiles['images'] as $imageFile) {
                $newRequest = new Request();
                $newRequest->files->set('image', $imageFile);
                
                $newImage = $imageController->store($newRequest, $product);
                $images[] = $newImage;
            }
        }

        return [
            'messages' => ['Images updated successfully'],
            'images' => $images
        ];
    }


}
