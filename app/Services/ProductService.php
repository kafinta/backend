<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

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
                $this->attributeService->attachAttributeValues($product, $formData['data']['attributes']);
            }

            // Handle images if present
            if (isset($formData['data']['images'])) {
                $this->attachImages($product, $formData['data']['images']);
            }

            // Load relationships for response
            $product->load(['images', 'subcategory', 'attributeValues.attribute']);

            // Format the response
            $response = $product->toArray();
            
            // Format attributes
            $groupedAttributes = [];
            foreach ($product->attributeValues as $value) {
                $groupedAttributes[] = [
                    'id' => $value->attribute->id,
                    'name' => $value->attribute->name,
                    'value' => [
                        'id' => $value->id,
                        'name' => $value->name,
                        'representation' => $value->representation
                    ]
                ];
            }
            
            $response['attributes'] = $groupedAttributes;
            unset($response['attribute_values']);

            return $response;

        } catch (\Exception $e) {
            // Let the controller handle the rollback
            throw $e;
        }
    }

    protected function attachAttributes(Product $product, array $attributes): void
    {
        // Use the attribute service to handle all attribute operations
        $this->attributeService->attachAttributeValues($product, $attributes);
    }

    protected function attachImages(Product $product, array $imagePaths): void
    {
        // Move images from temp to final location
        $this->imageService->processProductImages($imagePaths, $product);
    }

    public function updateProduct(Product $product, array $formData, Request $request = null): array
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

            // If request object is provided, handle any additional image uploads
            if ($request && $request->hasFile('images')) {
                $this->updateImages($product, $request);
            }

            // Refresh and load relationships
            $product->refresh()->load(['images', 'subcategory', 'attributeValues.attribute']);

            // Format the response
            $response = $product->toArray();
            
            // Format attributes
            $groupedAttributes = [];
            foreach ($product->attributeValues as $value) {
                $groupedAttributes[] = [
                    'id' => $value->attribute->id,
                    'name' => $value->attribute->name,
                    'value' => [
                        'id' => $value->id,
                        'name' => $value->name,
                        'representation' => $value->representation
                    ]
                ];
            }
            
            $response['attributes'] = $groupedAttributes;
            unset($response['attribute_values']);

            return $response;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function updateAttributes(Product $product, array $attributeData): void
    {
        // Use the attribute service for updates as well
        $this->attributeService->handleAttributeUpdate($product, $attributeData['attribute_values'] ?? []);
    }

    /**
     * Handle image updates for a product
     * 
     * @param Product $product
     * @param mixed $input Request or array containing image data
     *                        Format:
     *                        [
     *                            'delete' => [array of image IDs],
     *                            'images' => [array of uploaded files]
     *                        ]
     */
    protected function updateImages(Product $product, $input): void
    {
        // Handle image deletions first
        if (is_array($input)) {
            // Handle array input format
            if (isset($input['delete'])) {
                $this->imageService->deleteProductImages($product, $input['delete']);
            }
            
            // Handle new image uploads
            if (isset($input['images']) && is_array($input['images'])) {
                $this->imageService->handleImageUpload($input['images'], $product);
            }
        } else {
            // Handle Request object
            if ($input->has('delete')) {
                $this->imageService->deleteProductImages($product, $input->input('delete'));
            }
            
            if ($input->hasFile('images')) {
                $this->imageService->handleImageUpload($input, $product);
            }
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