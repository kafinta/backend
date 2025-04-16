<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductImageService
{
    protected $tempDisk = 'public';
    protected $finalDisk = 'public';
    protected $tempPath = 'temp-product-images';
    protected $finalPath = 'product-images';
    protected $maxImagesPerProduct = 10; // Configurable maximum images per product
    protected $formService;

    /**
     * Handle initial image upload in multi-step form
     *
     * @param Request $request
     * @return array Array of temporary image paths
     */
    public function __construct(MultiStepFormService $formService)
    {
        $this->formService = $formService;
    }

    public function handleImageStep(Request $request): array
    {
        $formType = 'product_form';
        $sessionKey = $this->formService->getSessionKey($formType, $request->session_id);

        // First validate and upload images before form processing
        $tempPaths = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                if (!$this->validateImage($image)) {
                    throw new \InvalidArgumentException('Invalid image format. Only JPEG and PNG are allowed.');
                }

                // Store in temp location
                $path = $image->store($this->tempPath, $this->tempDisk);
                $tempPaths[] = $path;
            }
        }

        // Create a clean request with just the paths
        $cleanRequest = new Request([
            'image_paths' => $tempPaths,
            'session_id' => $request->session_id,
            'step' => 3
        ]);

        // Process the form with the clean request
        $result = $this->formService->process($cleanRequest, $formType);

        if (!$result['success']) {
            // Clean up uploaded files if form validation fails
            foreach ($tempPaths as $path) {
                Storage::disk($this->tempDisk)->delete($path);
            }
            throw new \Exception($result['error']);
        }

        return $tempPaths;
    }

    /**
     * Process temporary images and move them to final location
     *
     * @param array $tempPaths Array of temporary image paths
     * @param Product $product
     * @return array Array of created image IDs
     */
    public function processProductImages(array $tempPaths, Product $product): array
    {
        if ($product->images()->count() + count($tempPaths) > $this->maxImagesPerProduct) {
            throw new \InvalidArgumentException(
                "Cannot add images. Maximum limit of {$this->maxImagesPerProduct} would be exceeded."
            );
        }

        $imageIds = [];

        foreach ($tempPaths as $tempPath) {
            // Move from temp to final location
            $finalPath = Storage::disk($this->finalDisk)->putFileAs(
                $this->finalPath,
                Storage::disk($this->tempDisk)->path($tempPath),
                'image_' . time() . '_' . uniqid() . '.' . pathinfo($tempPath, PATHINFO_EXTENSION)
            );

            // Create image record
            $newImage = $product->images()->create(['path' => $finalPath]);
            $imageIds[] = $newImage->id;

            // Clean up temp file
            Storage::disk($this->tempDisk)->delete($tempPath);
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
            $newImage = $product->images()->create(['path' => $path]);
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
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }
    }



    public function validateImage(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();
        return in_array($mimeType, ['image/jpeg', 'image/png']);
    }
}
