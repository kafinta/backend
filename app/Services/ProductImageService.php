<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductImageService
{
    protected $disk = 'public';
    protected $path = 'products';
    protected $maxImagesPerProduct = 10; // Configurable maximum images per product
    protected $formService;

    /**
     * Constructor
     *
     * @param MultiStepFormService $formService
     */
    public function __construct(MultiStepFormService $formService)
    {
        $this->formService = $formService;
    }

    /**
     * Process images for a product
     *
     * @param array $imagePaths Array of image paths
     * @param Product $product
     * @return array Array of created image IDs
     */
    public function processProductImages(array $imagePaths, Product $product): array
    {
        if ($product->images()->count() + count($imagePaths) > $this->maxImagesPerProduct) {
            throw new \InvalidArgumentException(
                "Cannot add images. Maximum limit of {$this->maxImagesPerProduct} would be exceeded."
            );
        }

        $imageIds = [];

        foreach ($imagePaths as $path) {
            // Create image record with /storage/ prefix
            $standardizedPath = $this->standardizeImagePath($path);
            $newImage = $product->images()->create(['path' => $standardizedPath]);
            $imageIds[] = $newImage->id;
        }

        return $imageIds;
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
        // Delete specified images if any
        if (!empty($imageIdsToDelete)) {
            $this->deleteProductImages($product, $imageIdsToDelete);
        }

        // Add new images if any
        $imageIds = [];
        foreach ($imagePaths as $path) {
            // Standardize path to include /storage/ prefix
            $standardizedPath = $this->standardizeImagePath($path);
            $newImage = $product->images()->create(['path' => $standardizedPath]);
            $imageIds[] = $newImage->id;
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
            // Remove /storage/ prefix if present for proper deletion
            $storagePath = $this->getStoragePath($image->path);
            Storage::disk('public')->delete($storagePath);
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
