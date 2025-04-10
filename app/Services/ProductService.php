<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    protected $imageService;
    protected $attributeService;

    public function __construct(
        ProductImageService $imageService,
        ProductAttributeService $attributeService
    ) {
        $this->imageService = $imageService;
        $this->attributeService = $attributeService;
    }

    public function createProduct(array $formData): array
    {
        try {
            // Extract data
            $basicInfo = $formData['data']['basic_info'] ?? null;
            if (!$basicInfo) {
                throw new \InvalidArgumentException('Missing basic product information');
            }

            // Create product
            $product = Product::create([
                'name' => $basicInfo['name'],
                'description' => $basicInfo['description'],
                'price' => $basicInfo['price'],
                'subcategory_id' => $basicInfo['subcategory_id'],
                'user_id' => auth()->id()
            ]);

            // Handle attributes if present
            if (isset($formData['data']['attributes'])) {
                $this->attachAttributes($product, $formData['data']['attributes']);
            }

            // Handle images if present
            if (isset($formData['data']['images'])) {
                $this->attachImages($product, $formData['data']['images']);
            }

            // Load relationships for response
            $product->load(['images', 'subcategory', 'attributes', 'attributeValues']);

            return $product->toArray();

        } catch (\Exception $e) {
            // Let the controller handle the rollback
            throw $e;
        }
    }

    protected function attachAttributes(Product $product, array $attributeData): void
    {
        // Use the attribute service to handle all attribute operations
        $this->attributeService->handleAttributeUpdate($product, $attributeData['attribute_values'] ?? []);
    }

    protected function attachImages(Product $product, array $imagePaths): void
    {
        foreach ($imagePaths as $path) {
            $product->images()->create(['path' => $path]);
        }
    }

    public function updateProduct(Product $product, array $formData): array
    {
        try {
            // Update basic info if provided
            if (isset($formData['data']['basic_info'])) {
                $basicInfo = $formData['data']['basic_info'];
                $updateData = array_filter([
                    'name' => $basicInfo['name'] ?? null,
                    'description' => $basicInfo['description'] ?? null,
                    'price' => $basicInfo['price'] ?? null,
                    'subcategory_id' => $basicInfo['subcategory_id'] ?? null
                ], fn($value) => !is_null($value));

                if (!empty($updateData)) {
                    $product->update($updateData);
                }
            }

            // Update attributes if provided
            if (isset($formData['data']['attributes'])) {
                $this->updateAttributes($product, $formData['data']['attributes']);
            }

            // Update images if provided
            if (isset($formData['data']['images'])) {
                $this->updateImages($product, $formData['data']['images']);
            }

            // Refresh and load relationships
            $product->refresh()->load(['images', 'subcategory', 'attributes', 'attributeValues']);
            return $product->toArray();

        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function updateAttributes(Product $product, array $attributeData): void
    {
        // Use the attribute service for updates as well
        $this->attributeService->handleAttributeUpdate($product, $attributeData['attribute_values'] ?? []);
    }

    protected function updateImages(Product $product, array $imagePaths): void
    {
        // Only update images if new ones are provided
        foreach ($imagePaths as $path) {
            $product->images()->create(['path' => $path]);
        }
    }

    public function deleteProduct(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            }

            $product->attributeValues()->detach();
            $product->delete();

            return true;
        });
    }
}