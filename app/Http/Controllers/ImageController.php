<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Image;
use Illuminate\Support\Facades\Log;

class ImageController extends ImprovedController
{

    public function index() {
        $images = Image::all();
        return response()->json($images);
    }

    public function store(Request $request, $parentModel)
    {
        if (!$request->hasFile('image')) {
            throw new \Exception('No image file provided');
        }

        $image = $request->file('image');
        
        // Validate image
        if (!$image->isValid()) {
            throw new \Exception('Invalid image file');
        }

        try {
            // Get the model type for the storage path
            $modelType = strtolower(class_basename($parentModel));
            
            // Store the image file
            $path = $image->store($modelType, 'public');
            
            // Create image record
            $imageRecord = $parentModel->images()->create([
                'path' => '/storage/' . $path,
                'imageable_id' => $parentModel->id,
                'imageable_type' => get_class($parentModel)
            ]);

            return $imageRecord;

        } catch (\Exception $e) {
            \Log::error('Image storage failed: ' . $e->getMessage());
            throw new \Exception('Failed to store image: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $image)
    {
        try {
            // Delete old image if it exists
            if ($image->path) {
                $oldPath = str_replace('/storage/', '', $image->path);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            if (!$request->hasFile('image')) {
                throw new \Exception('No new image file provided');
            }

            $newImage = $request->file('image');
            if (!$newImage->isValid()) {
                throw new \Exception('Invalid image file');
            }

            // Get the model type for the storage path
            $modelType = strtolower(class_basename($image->imageable_type));
            
            // Store new image
            $path = $newImage->store($modelType, 'public');
            
            // Update image record
            $image->update([
                'path' => '/storage/' . $path
            ]);

            return $image;

        } catch (\Exception $e) {
            \Log::error('Image update failed: ' . $e->getMessage());
            throw new \Exception('Failed to update image: ' . $e->getMessage());
        }
    }


    public function destroy($id)
    {
        $image = Image::findOrFail($id);
        $path = str_replace('/storage/', '', $image->path); // Remove /storage/ prefix to get relative path
        $this->deleteImage($path);
        $image->delete();

        return response()->json(['message' => 'Image deleted successfully']);
    }

    private function uploadImage($image)
    {
        try {            
            if (!$image->isValid()) {
                throw new \Exception('Invalid image file');
            }
            
            $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            
            $path = $image->storeAs('products', $filename);
            
            return '/storage/' . $path;

        } catch (\Exception $e) {
            \Log::error('Image upload failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function deleteImage($path)
    {
        if ($path && Storage::exists($path)) {
            Storage::delete($path);
            return true;
        }
        return false;
    }
}
