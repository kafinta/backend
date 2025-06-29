<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use App\Http\Resources\ImageResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Image;
use Illuminate\Support\Facades\Log;
use App\Services\FileService;
use Illuminate\Http\UploadedFile;

class ImageController extends ImprovedController
{
    protected $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index() {
        $images = Image::all();
        return $this->respondWithSuccess("Images Fetched Successfully", 200, ImageResource::collection($images));
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|file|image|mimes:jpeg,png,jpg,gif|max:2048',
            'imageable_id' => 'required|integer',
            'imageable_type' => 'required|string|in:product,variant'
        ]);

        $imageableTypeMap = [
            'product' => \App\Models\Product::class,
            'variant' => \App\Models\Variant::class,
        ];
        $imageableType = $request->input('imageable_type');
        $imageableId = $request->input('imageable_id');
        $modelClass = $imageableTypeMap[$imageableType] ?? null;
        if (!$modelClass) {
            return $this->respondWithError('Invalid imageable type', 422);
        }
        $parentModel = $modelClass::find($imageableId);
        if (!$parentModel) {
            return $this->respondWithError('Parent model not found', 404);
        }
        $imageFile = $request->file('image');
        if (!$imageFile instanceof UploadedFile || !$imageFile->isValid()) {
            return $this->respondWithError('Invalid image file', 422);
        }
        $directory = strtolower(class_basename($parentModel)) . 's/' . $parentModel->id;
        $path = $this->fileService->uploadFile($imageFile, $directory);
        if (!$path) {
            return $this->respondWithError('Failed to upload image', 500);
        }
        $imageRecord = $parentModel->images()->create([
            'path' => $path,
            'imageable_id' => $parentModel->id,
            'imageable_type' => $modelClass
        ]);
        return $this->respondWithSuccess('Image uploaded successfully', 201, new ImageResource($imageRecord));
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
            $parentModel = $image->imageable;
            if (!$parentModel) {
                return $this->respondWithError('Parent model not found', 404);
            }
            $user = auth()->user();
            if (!$user->hasRole('admin') && ($parentModel->user_id ?? null) !== $user->id) {
                return $this->respondWithError('Unauthorized', 403);
            }
            $this->fileService->deleteFile($image->path);
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