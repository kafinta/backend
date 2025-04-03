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
        if (!isset($attributeData['attribute_values']) || !is_array($attributeData['attribute_values'])) {
            throw new \InvalidArgumentException('Invalid attribute values format');
        }

        $product->attributeValues()->sync($attributeData['attribute_values']);
    }

    protected function attachImages(Product $product, array $imagePaths): void
    {
        foreach ($imagePaths as $path) {
            $product->images()->create(['path' => $path]);
        }
    }

    public function updateProduct(Product $product, array $data)
    {
        return DB::transaction(function () use ($product, $data) {
            $formData = $data['data'] ?? $data;
            
            $this->updateBasicInfo($product, $formData);
            $this->updateAttributes($product, $formData);
            $this->updateImages($product, $formData);

            return $product->load(['images', 'subcategory', 'attributeValues']);
        });
    }

    protected function updateBasicInfo(Product $product, array $formData): void
    {
        if (!isset($formData['basic_info'])) {
            return;
        }

        $product->update([
            'name' => $formData['basic_info']['name'],
            'description' => $formData['basic_info']['description'],
            'price' => $formData['basic_info']['price'],
            'subcategory_id' => $formData['basic_info']['subcategory_id']
        ]);
    }

    protected function updateAttributes(Product $product, array $formData): void
    {
        if (!isset($formData['attributes']) || !is_array($formData['attributes'])) {
            return;
        }

        $product->attributeValues()->detach();
        $this->attachAttributes($product, $formData);
    }

    protected function updateImages(Product $product, array $formData): void
    {
        if (!isset($formData['images']) || !is_array($formData['images'])) {
            return;
        }

        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }

        $this->attachImages($product, $formData);
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
