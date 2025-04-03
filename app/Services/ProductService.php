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

    public function createProduct(array $data)
    {
        return DB::transaction(function () use ($data) {
            $formData = $data['data'] ?? $data;
            
            $product = $this->createBasicProduct($formData);
            $this->attachAttributes($product, $formData);
            $this->attachImages($product, $formData);

            return $product->load(['images', 'subcategory', 'attributeValues']);
        });
    }

    protected function createBasicProduct(array $formData): Product
    {
        return Product::create([
            'name' => $formData['basic_info']['name'],
            'description' => $formData['basic_info']['description'],
            'price' => $formData['basic_info']['price'],
            'subcategory_id' => $formData['basic_info']['subcategory_id'],
            'user_id' => auth()->id()
        ]);
    }

    protected function attachAttributes(Product $product, array $formData): void
    {
        if (!isset($formData['attributes']) || !is_array($formData['attributes'])) {
            return;
        }

        foreach ($formData['attributes'] as $attributeData) {
            $attribute = Attribute::where('name', $attributeData['attribute'])->first();
            if (!$attribute) {
                continue;
            }

            $attributeValue = AttributeValue::where([
                'attribute_id' => $attribute->id,
                'name' => $attributeData['value']
            ])->first();

            if ($attributeValue) {
                $product->attributeValues()->attach($attributeValue->id);
            }
        }
    }

    protected function attachImages(Product $product, array $formData): void
    {
        if (!isset($formData['images']) || !is_array($formData['images'])) {
            return;
        }

        foreach ($formData['images'] as $imagePath) {
            $product->images()->create(['path' => $imagePath]);
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
