<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\FileService;

class ProductImageService
{
    protected $disk = 'public';
    protected $path = 'products';
    protected $maxImagesPerProduct = 10; // Configurable maximum images per product
    protected $fileService;

    /**
     * Constructor
     *
     * @param FileService $fileService
     */
    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }







    /**
     * Handle image update for a product
     *
     * @param Product $product The product to update
     * @param array $imagePaths Array of image paths to add
     * @param array $imageIdsToDelete Array of image IDs to delete
     * @return array Array of image IDs
     */
    public function handleImageUpdate(Product $product, array $imagePaths, array $imageIdsToDelete = []): array
    {
        // Log the update operation
        Log::info('Handling image update for product', [
            'product_id' => $product->id,
            'existing_image_count' => $product->images()->count(),
            'new_image_paths_count' => count($imagePaths),
            'image_ids_to_delete_count' => count($imageIdsToDelete)
        ]);

        // Delete specified images if any
        if (!empty($imageIdsToDelete)) {
            $this->deleteProductImages($product, $imageIdsToDelete);
        }

        // Add new images if any
        $imageIds = [];

        // First, get all existing image IDs that weren't deleted
        if (empty($imageIdsToDelete)) {
            // If no images were deleted, get all existing image IDs
            $existingImageIds = $product->images()->pluck('id')->toArray();
        } else {
            // If some images were deleted, get the remaining image IDs
            $existingImageIds = $product->images()->whereNotIn('id', $imageIdsToDelete)->pluck('id')->toArray();
        }

        // Add existing image IDs to the result
        $imageIds = array_merge($imageIds, $existingImageIds);

        // Add new images
        foreach ($imagePaths as $path) {
            // Skip empty paths
            if (empty($path)) {
                continue;
            }

            // Standardize path to include /storage/ prefix
            $standardizedPath = $this->standardizeImagePath($path);

            // Create the image record
            $newImage = $product->images()->create(['path' => $standardizedPath]);
            $imageIds[] = $newImage->id;

            Log::info('Added new image to product', [
                'product_id' => $product->id,
                'image_id' => $newImage->id,
                'path' => $standardizedPath
            ]);
        }

        // Log the final result
        Log::info('Image update complete', [
            'product_id' => $product->id,
            'total_images_after_update' => $product->images()->count(),
            'returned_image_ids_count' => count($imageIds)
        ]);

        return $imageIds;
    }

    /**
     * Process image paths for a product
     *
     * @param array $imagePaths Array of image paths
     * @param Product $product The product to associate images with
     * @param bool $isPrimary Whether the first image should be set as primary
     * @return array Array of created image IDs
     */
    public function processProductImages(array $imagePaths, Product $product, bool $isPrimary = false): array
    {
        if ($product->images()->count() + count($imagePaths) > $this->maxImagesPerProduct) {
            throw new \InvalidArgumentException(
                "Cannot add images. Maximum limit of {$this->maxImagesPerProduct} would be exceeded."
            );
        }

        $imageIds = [];
        $firstImage = true;

        foreach ($imagePaths as $path) {
            // Standardize path to include /storage/ prefix
            $standardizedPath = $this->standardizeImagePath($path);

            // Create the image record
            $newImage = $product->images()->create([
                'path' => $standardizedPath,
                'is_primary' => ($firstImage && $isPrimary) ? true : false
            ]);

            $imageIds[] = $newImage->id;
            $firstImage = false;
        }

        return $imageIds;
    }

    /**
     * Upload images for a product
     *
     * @param Product $product The product to upload images for
     * @param array $images Array of uploaded files
     * @param bool $isPrimary Whether the first image should be set as primary
     * @return array Array of created image IDs
     */
    public function uploadImages(Product $product, array $images, bool $isPrimary = false): array
    {
        if ($product->images()->count() + count($images) > $this->maxImagesPerProduct) {
            throw new \InvalidArgumentException(
                "Cannot add images. Maximum limit of {$this->maxImagesPerProduct} would be exceeded."
            );
        }

        $imageIds = [];
        $firstImage = true;

        foreach ($images as $image) {
            if (!$image instanceof UploadedFile || !$image->isValid()) {
                Log::warning('Invalid image file', [
                    'product_id' => $product->id,
                    'error' => $image instanceof UploadedFile ? $image->getError() : 'Not an uploaded file'
                ]);
                continue;
            }

            // Use the FileService to upload the image
            $directory = "products/{$product->id}";
            $path = $this->fileService->uploadFile($image, $directory);

            if (!$path) {
                Log::error('Failed to upload product image', [
                    'product_id' => $product->id,
                    'original_name' => $image->getClientOriginalName()
                ]);
                continue;
            }

            // Create the image record
            $newImage = $product->images()->create([
                'path' => $path,
                'is_primary' => ($firstImage && $isPrimary) ? true : false
            ]);

            $imageIds[] = $newImage->id;
            $firstImage = false;
        }

        return $imageIds;
    }

    /**
     * Delete specific images from a product
     *
     * @param Product $product
     * @param array $imageIds Array of image IDs to delete. If empty, deletes all images.
     */
    public function deleteProductImages(Product $product, array $imageIds = [])
    {
        $query = $product->images();

        if (!empty($imageIds)) {
            $query->whereIn('id', $imageIds);
        }

        $images = $query->get();

        foreach ($images as $image) {
            // Delete the file using FileService
            $this->fileService->deleteFile($image->path);
            $image->delete();
        }
    }



    public function validateImage(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();
        return in_array($mimeType, ['image/jpeg', 'image/png']);
    }

    /**
     * Standardize image path to include /storage/ prefix
     *
     * @param string $path The image path to standardize
     * @return string The standardized path
     */
    public function standardizeImagePath(string $path): string
    {
        // If path doesn't start with /storage/, add it
        if (strpos($path, '/storage/') !== 0) {
            return '/storage/' . $path;
        }

        return $path;
    }

    /**
     * Get the storage path from a public path
     *
     * @param string $path The public path (with /storage/ prefix)
     * @return string The storage path (without /storage/ prefix)
     */
    public function getStoragePath(string $path): string
    {
        // If path starts with /storage/, remove it
        if (strpos($path, '/storage/') === 0) {
            return substr($path, 9); // Remove '/storage/' prefix
        }

        return $path;
    }
}
