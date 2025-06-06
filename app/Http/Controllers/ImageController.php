<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use App\Http\Resources\ImageResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Image;
use Illuminate\Support\Facades\Log;

class ImageController extends ImprovedController
{

    public function index() {
        $images = Image::all();
        return $this->respondWithSuccess("Images Fetched Successfully", 200, ImageResource::collection($images));
    }

    public function store(Request $request, $parentModel)
    {
        if (!$request->hasFile('image') && !$request->hasFile('images')) {
            throw new \Exception('No image file provided');
        }

        // Handle both single 'image' and array 'images'
        $imageFile = $request->hasFile('image') ? $request->file('image') : $request->file('images');

        // Validate image
        if (!$imageFile->isValid()) {
            throw new \Exception('Invalid image file');
        }

        try {
            // Get the model type for the storage path
            $modelType = strtolower(class_basename($parentModel));

            // Store the image file
            $path = $imageFile->store($modelType, 'public');

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
                $this->deleteImage($oldPath);
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
        try {
            $image = Image::findOrFail($id);

            // Check ownership - user must own the parent model (product, variant, etc.)
            $parentModel = $image->imageable;

            if (!$parentModel) {
                return $this->respondWithError('Parent model not found', 404);
            }

            // Check if user owns the parent model or is admin
            $user = auth()->user();
            if (!$user->hasRole('admin') && $parentModel->user_id !== $user->id) {
                return $this->respondWithError('Unauthorized', 403);
            }

            // Delete the file
            $path = str_replace('/storage/', '', $image->path);
            $this->deleteImage($path);

            // Delete the database record
            $image->delete();

            return $this->respondWithSuccess('Image deleted successfully', 200);

        } catch (\Exception $e) {
            \Log::error('Image deletion failed: ' . $e->getMessage());
            return $this->respondWithError('Failed to delete image: ' . $e->getMessage(), 500);
        }
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
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            return true;
        }

        if ($path && file_exists(public_path($path))) {
            unlink(public_path($path));
            return true;
        }
        return false;
    }
}