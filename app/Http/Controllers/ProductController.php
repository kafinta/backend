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


class ProductController extends ImprovedController
{

    public function index()
    {
        $products = Product::with('images')->get();
        
        if ($products->isEmpty()) {
            return $this->respondWithError('No products found', 404);
        }
        
        return $this->respondWithSuccess('Products fetched successfully', 200, $products);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'subcategory_id' => 'required|exists:subcategories,id',
        ]);

        DB::beginTransaction();
        try {
            $product = Product::create($validated);
            $imageResults = [
                'messages' => ['No images were uploaded'],
                'images' => []
            ];
    
            if ($request->hasFile('images')) {
                $imageResults = $this->storeImages($request, $product);
            }
    
            DB::commit();

            $product = $product->fresh(['images']);
            return $this->respondWithSuccess(
                [
                    'product' => 'Product created successfully',
                    'images' => $imageResults['messages']
                ], 
                201, 
                $product
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respondWithError('Product creation failed', 500, $e->getMessage());
        }
    }


    public function show($id)
    {
        // Use findOrFail to get a proper 404 if product doesn't exist
        try {
            $product = Product::with('images')->findOrFail($id);
            return $this->respondWithSuccess('Product fetched successfully', 200, $product);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->respondWithError('Product not found', 404);
        }
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return $this->respondWithError('Product not found', 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'subcategory_id' => 'sometimes|exists:subcategories,id',
            'image.*' => 'sometimes|file|image|max:2048'
        ]);

        DB::beginTransaction();
        try {
            // Update product details
            $data = $request->except(['image', 'image.*']);
            $product->update($data);

            $imageResults = [
                'messages' => ['No images were updated'],
                'images' => []
            ];
            
            // Handle image updates - works with both PUT and POST
            if ($request->hasFile('image')) {
                $messages = [];
                $successCount = 0;
                $failureCount = 0;
                
                foreach ($request->file('image') as $imageId => $imageFile) {
                    try {
                        $image = $product->images()->find($imageId);
                        if (!$image) {
                            throw new \Exception("Image not found");
                        }

                        $newRequest = new Request();
                        $newRequest->files->set('image', $imageFile);
                        
                        $imageController = new ImageController();
                        $imageController->update($newRequest, $image);
                        
                        $successCount++;
                        $messages[] = [
                            'status' => 'success',
                            'index' => $imageId,
                            'message' => "Image {$imageId} updated successfully"
                        ];
                    } catch (\Exception $e) {
                        $failureCount++;
                        $messages[] = [
                            'status' => 'error',
                            'index' => $imageId,
                            'message' => "Failed to update image {$imageId}: " . $e->getMessage()
                        ];
                    }
                }

                $imageResults['messages'] = array_merge([
                    [
                        'status' => 'summary',
                        'message' => "Processed " . count($request->file('image')) . " images: " .
                                    $successCount . " succeeded, " .
                                    $failureCount . " failed"
                    ]
                ], $messages);
            }

            DB::commit();
            
            $product = $product->fresh(['images']);
            return $this->respondWithSuccess(
                [
                    'product' => 'Product updated successfully',
                    'images' => $imageResults['messages']
                ], 
                200, 
                $product
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respondWithError('Product update failed', 500, $e->getMessage());
        }
    }


    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return $this->respondWithError('Product not found', 404);
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













    private function storeImages(Request $request, Product $product)
    {
        $messages = [];
        $images = [];
        $successCount = 0;
        $failureCount = 0;

        if (!$request->hasFile('images')) {
            return [
                'messages' => ['No images found in request'],
                'images' => []
            ];
        }

        $imageController = new ImageController();
        
        foreach ($request->file('images') as $index => $imageFile) {
            try {
                $newRequest = new Request();
                $newRequest->files->set('image', $imageFile);
                
                $storedImage = $imageController->store($newRequest, $product);
                $images[] = $storedImage;
                $successCount++;
                $messages[] = [
                    'status' => 'success',
                    'index' => $index,
                    'message' => "Image uploaded successfully"
                ];
            } catch (\Exception $e) {
                $failureCount++;
                $messages[] = [
                    'status' => 'error',
                    'index' => $index,
                    'message' => "Failed to upload image: " . $e->getMessage()
                ];
            }
        }

        // Add summary message
        array_unshift($messages, [
            'status' => 'summary',
            'message' => "Processed " . count($request->file('images')) . " images: " .
                        $successCount . " succeeded, " .
                        $failureCount . " failed"
        ]);

        return [
            'messages' => $messages,
            'images' => $images
        ];
    }

    private function updateImages(Request $request, Product $product)
    {
        $imageController = new ImageController();
        
        // Delete any existing images if requested
        if ($request->has('delete_images')) {
            foreach ($product->images as $image) {
                $path = str_replace('/storage/', '', $image->path);
                $imageController->deleteImage($path);
                $image->delete();
            }
        }

        // Handle new image uploads
        if ($request->hasFile('images')) {
            return $imageController->store($request, $product);
        }

        return response()->json(['message' => 'No new images to update']);
    }
}
