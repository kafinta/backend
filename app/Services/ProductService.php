<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

// Events
use App\Events\Product\ProductCreated;
use App\Events\Product\ProductUpdated;
use App\Events\Product\AttributeAdded;
use App\Events\Product\AttributeUpdated;
use App\Events\Product\SubcategoryChanged;

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
            // Log the incoming form data for debugging
            Log::info('Creating product with form data', [
                'has_basic_info' => isset($formData['data']['basic_info']),
                'has_attributes' => isset($formData['data']['attributes']),
                'has_raw_attributes' => isset($formData['data']['raw_attributes']),
                'has_images' => isset($formData['data']['images'])
            ]);

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
            if (isset($formData['data']['raw_attributes']) && !empty($formData['data']['raw_attributes'])) {
                // If we have raw_attributes, use those directly
                $this->attachAttributes($product, ['raw_attributes' => $formData['data']['raw_attributes']]);
            } else if (isset($formData['data']['attributes']) && !empty($formData['data']['attributes'])) {
                // Otherwise use the formatted attributes
                $this->attachAttributes($product, $formData['data']['attributes']);
            }

            // Handle images if present
            if (isset($formData['data']['images'])) {
                $this->attachImages($product, $formData['data']['images']);
            }

            // Load relationships for response
            $product->load(['images', 'subcategory', 'attributes', 'attributeValues.attribute']);

            // Make a copy of the attributeValues for our custom formatting
            $attributeValues = $product->attributeValues;

            // Format attributes in the intuitive format
            $formattedAttributes = [];
            foreach ($attributeValues as $value) {
                if ($value->attribute) {
                    $formattedAttributes[] = [
                        'id' => $value->attribute->id,
                        'name' => $value->attribute->name,
                        'value' => [
                            'id' => $value->id,
                            'name' => $value->name,
                            'representation' => $value->representation
                        ]
                    ];
                }
            }

            // Remove the attributeValues relation to prevent it from being serialized
            $product->unsetRelation('attributeValues');

            // Get the base product data
            $productData = $product->toArray();

            // Add the formatted attributes
            $productData['attributes'] = $formattedAttributes;

            // Get the raw attributes and images for the event
            $rawAttributes = isset($formData['data']['raw_attributes']) ? $formData['data']['raw_attributes'] : [];
            $imagePaths = isset($formData['data']['images']) ? $formData['data']['images'] : [];

            // Dispatch event
            event(new ProductCreated($product, $rawAttributes, $imagePaths));

            return $productData;

        } catch (\Exception $e) {
            // Let the controller handle the rollback
            throw $e;
        }
    }

    protected function attachAttributes(Product $product, array $attributeData): void
    {
        try {
            // Log the incoming attribute data for debugging
            Log::info('Attaching product attributes', [
                'product_id' => $product->id,
                'attribute_data' => $attributeData
            ]);

            // Convert the attribute data to the format expected by handleAttributeUpdate
            $attributeValues = [];

            if (isset($attributeData['attribute_values'])) {
                $attributeValues = $attributeData['attribute_values'];
            } else if (isset($attributeData['raw_attributes']) && !empty($attributeData['raw_attributes'])) {
                // If we have raw_attributes, use those directly
                $attributeValues = [
                    'add' => collect($attributeData['raw_attributes'])->map(function($item) {
                        return [
                            'attribute_id' => $item['attribute_id'],
                            'value_id' => $item['value_id']
                        ];
                    })->toArray()
                ];
            } else if (is_array($attributeData) && isset($attributeData[0]) && isset($attributeData[0]['id']) && isset($attributeData[0]['value'])) {
                // If we have the new format with id and nested value object
                $attributeValues = [
                    'add' => collect($attributeData)->map(function($item) {
                        return [
                            'attribute_id' => $item['id'],
                            'value_id' => $item['value']['id']
                        ];
                    })->toArray()
                ];
            } else if (isset($attributeData[0]['attribute']) && isset($attributeData[0]['value'])) {
                // Format from session: [{"attribute":"Color","value":"Red"}]
                // Need to convert attribute/value names to IDs
                $attributeValues = [
                    'add' => collect($attributeData)->map(function($item) use ($product) {
                        $attribute = Attribute::where('name', $item['attribute'])->first();
                        $value = AttributeValue::where('value', $item['value'])
                            ->where('attribute_id', $attribute->id)
                            ->first();

                        return [
                            'attribute_id' => $attribute->id,
                            'value_id' => $value->id
                        ];
                    })->toArray()
                ];
            } else if (is_array($attributeData) && isset($attributeData[0]) && isset($attributeData[0]['attribute_id'])) {
                // If we have the old format with attribute_id and value_id
                $attributeValues = [
                    'add' => collect($attributeData)->map(function($item) {
                        return [
                            'attribute_id' => $item['attribute_id'],
                            'value_id' => $item['value_id']
                        ];
                    })->toArray()
                ];
            } else {
                // Log the format we received for debugging
                Log::warning('Unrecognized attribute data format', [
                    'product_id' => $product->id,
                    'attribute_data' => $attributeData
                ]);

                // Return early to avoid errors
                return;
            }

            // Use the attribute service to handle all attribute operations
            $this->attributeService->handleAttributeUpdate($product, $attributeValues);
        } catch (\Exception $e) {
            Log::error('Error attaching product attributes', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'attribute_data' => $attributeData
            ]);
            throw $e;
        }
    }

    protected function attachImages(Product $product, array $imagePaths): void
    {
        foreach ($imagePaths as $path) {
            // Standardize path to include /storage/ prefix
            $standardizedPath = $this->imageService->standardizeImagePath($path);
            $product->images()->create(['path' => $standardizedPath]);
        }
    }

    public function updateProduct(Product $product, array $formData): array
    {
        try {
            // Log the incoming form data for debugging
            Log::info('Updating product with form data', [
                'product_id' => $product->id,
                'has_basic_info' => isset($formData['data']['basic_info']),
                'has_attributes' => isset($formData['data']['attributes']),
                'has_images' => isset($formData['data']['images'])
            ]);

            // Update basic info if provided
            if (isset($formData['data']['basic_info'])) {
                $this->updateBasicInfo($product, $formData['data']['basic_info']);
            }

            // Update attributes if provided
            if (isset($formData['data']['attributes']) || isset($formData['data']['raw_attributes'])) {
                // Prepare attribute data for update
                $attributeData = [];

                // If we have raw_attributes, use those
                if (isset($formData['data']['raw_attributes']) && !empty($formData['data']['raw_attributes'])) {
                    $attributeData = ['raw_attributes' => $formData['data']['raw_attributes']];
                    Log::info('Using raw_attributes for product update', [
                        'product_id' => $product->id,
                        'raw_attributes' => $formData['data']['raw_attributes']
                    ]);
                } else if (isset($formData['data']['attributes']) && !empty($formData['data']['attributes'])) {
                    // Otherwise use the formatted attributes
                    $attributeData = $formData['data']['attributes'];
                    Log::info('Using formatted attributes for product update', [
                        'product_id' => $product->id,
                        'attribute_count' => count($attributeData)
                    ]);
                }

                // Update the attributes
                if (!empty($attributeData)) {
                    $this->updateAttributes($product, $attributeData);
                } else {
                    // If no attributes provided, log this but don't clear existing attributes
                    Log::info('Empty attributes data, preserving existing', [
                        'product_id' => $product->id,
                        'existing_attribute_count' => $product->attributeValues()->count()
                    ]);
                }
            } else {
                // If no attributes provided, log this but don't clear existing attributes
                Log::info('No attributes provided in update data, preserving existing', [
                    'product_id' => $product->id,
                    'existing_attribute_count' => $product->attributeValues()->count()
                ]);
            }

            // Update images if provided
            if (isset($formData['data']['images'])) {
                $this->updateImages($product, $formData['data']['images']);
            }

            // Refresh and load relationships
            $product->refresh()->load(['images', 'subcategory', 'attributes', 'attributeValues.attribute']);

            // Make a copy of the attributeValues for our custom formatting
            $attributeValues = $product->attributeValues;

            // Format attributes in the intuitive format
            $formattedAttributes = [];
            foreach ($attributeValues as $value) {
                if ($value->attribute) {
                    $formattedAttributes[] = [
                        'id' => $value->attribute->id,
                        'name' => $value->attribute->name,
                        'value' => [
                            'id' => $value->id,
                            'name' => $value->name,
                            'representation' => $value->representation
                        ]
                    ];
                }
            }

            // Remove the attributeValues relation to prevent it from being serialized
            $product->unsetRelation('attributeValues');

            // Product update is complete

            // Get the base product data
            $productData = $product->toArray();

            // Add the formatted attributes
            $productData['attributes'] = $formattedAttributes;

            // Track which fields were updated
            $changedFields = [];
            if (isset($formData['data']['basic_info'])) $changedFields[] = 'basic_info';
            if (isset($formData['data']['attributes']) || isset($formData['data']['raw_attributes'])) $changedFields[] = 'attributes';
            if (isset($formData['data']['images'])) $changedFields[] = 'images';

            // Dispatch event
            event(new ProductUpdated($product, $changedFields));

            return $productData;

        } catch (\Exception $e) {
            Log::error('Error updating product', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Update the basic information of a product
     *
     * @param Product $product The product to update
     * @param array $basicInfo The basic information data
     * @return Product The updated product
     */
    public function updateBasicInfo(Product $product, array $basicInfo): Product
    {
        $subcategoryChanged = false;
        $oldSubcategoryId = $product->subcategory_id;

        // Check if subcategory is changing
        if (isset($basicInfo['subcategory_id']) && $basicInfo['subcategory_id'] != $product->subcategory_id) {
            $subcategoryChanged = true;

            Log::info('Subcategory change detected', [
                'product_id' => $product->id,
                'old_subcategory_id' => $oldSubcategoryId,
                'new_subcategory_id' => $basicInfo['subcategory_id']
            ]);
        }

        $updateData = array_filter([
            'name' => $basicInfo['name'] ?? null,
            'description' => $basicInfo['description'] ?? null,
            'price' => $basicInfo['price'] ?? null,
            'subcategory_id' => $basicInfo['subcategory_id'] ?? null
        ], fn($value) => !is_null($value));

        if (!empty($updateData)) {
            $product->update($updateData);

            // If subcategory changed, dispatch event
            if ($subcategoryChanged) {
                // Get attribute IDs that were kept and removed
                $keptAttributeIds = [];
                $removedAttributeIds = [];

                // Dispatch the subcategory changed event
                event(new SubcategoryChanged(
                    $product,
                    $oldSubcategoryId,
                    $product->subcategory_id,
                    $keptAttributeIds,
                    $removedAttributeIds
                ));
            }
        }

        return $product;
    }

    protected function updateAttributes(Product $product, array $attributeData): void
    {
        try {
            // Log the incoming attribute data for debugging
            Log::info('Updating product attributes', [
                'product_id' => $product->id,
                'attribute_data' => $attributeData
            ]);

            // Check if we have any attributes to update
            if (empty($attributeData)) {
                Log::info('No attributes to update, preserving existing attributes', [
                    'product_id' => $product->id,
                    'existing_attribute_count' => $product->attributeValues()->count()
                ]);
                return; // Skip attribute updates if no attributes provided
            }

            // If we have raw_attributes, use those directly
            if (isset($attributeData['raw_attributes']) && !empty($attributeData['raw_attributes'])) {
                Log::info('Using raw_attributes from attribute data', [
                    'product_id' => $product->id,
                    'raw_attributes' => $attributeData['raw_attributes']
                ]);

                // Convert raw_attributes to the format expected by handleAttributeUpdate
                $attributeValues = [
                    'add' => collect($attributeData['raw_attributes'])->map(function($item) {
                        return [
                            'attribute_id' => $item['attribute_id'],
                            'value_id' => $item['value_id']
                        ];
                    })->toArray()
                ];

                // Use the attribute service to handle all attribute operations
                $this->attributeService->handleAttributeUpdate($product, $attributeValues);

                // Return early since we've handled the attributes
                return;
            }

            // If we have an empty raw array but existing attributes in the database, preserve them
            if ((isset($attributeData['raw']) && empty($attributeData['raw'])) && $product->attributeValues()->count() > 0) {
                Log::info('Empty raw attributes but existing attributes in database, preserving existing', [
                    'product_id' => $product->id,
                    'existing_attribute_count' => $product->attributeValues()->count(),
                    'existing_attribute_ids' => $product->attributeValues()->pluck('attribute_id')->toArray()
                ]);
                return; // Skip attribute updates to preserve existing attributes
            }

            // If we have an empty array but existing attributes, preserve them
            if (is_array($attributeData) && empty($attributeData) && $product->attributeValues()->count() > 0) {
                Log::info('Empty attributes array but existing attributes in database, preserving existing', [
                    'product_id' => $product->id,
                    'existing_attribute_count' => $product->attributeValues()->count(),
                    'existing_attribute_ids' => $product->attributeValues()->pluck('attribute_id')->toArray()
                ]);
                return; // Skip attribute updates to preserve existing attributes
            }

            // Convert the attribute data to the format expected by handleAttributeUpdate
            $attributeValues = [];

            if (isset($attributeData['attribute_values'])) {
                // If we have a structured format with attribute_values
                $attributeValues = $attributeData['attribute_values'];
            } else if (isset($attributeData['raw_attributes']) && !empty($attributeData['raw_attributes'])) {
                // If we have the raw_attributes in the attribute data
                $attributeValues = [
                    'update' => collect($attributeData['raw_attributes'])->map(function($item) {
                        return [
                            'attribute_id' => $item['attribute_id'],
                            'value_id' => $item['value_id']
                        ];
                    })->toArray()
                ];
            } else if (is_array($attributeData) && !empty($attributeData)) {
                // If we have an array of formatted attributes
                $rawAttributes = [];
                foreach ($attributeData as $attr) {
                    if (isset($attr['id']) && isset($attr['value']) && isset($attr['value']['id'])) {
                        $rawAttributes[] = [
                            'attribute_id' => $attr['id'],
                            'value_id' => $attr['value']['id']
                        ];
                    }
                }

                $attributeValues = [
                    'update' => $rawAttributes
                ];
            } else if (is_array($attributeData) && isset($attributeData[0]) && isset($attributeData[0]['attribute_id'])) {
                // If we have a direct array of attributes
                $attributeValues = [
                    'update' => collect($attributeData)->map(function($item) {
                        return [
                            'attribute_id' => $item['attribute_id'],
                            'value_id' => $item['value_id']
                        ];
                    })->toArray()
                ];
            }

            // Log the processed attribute values
            Log::info('Processed attribute values for update', [
                'product_id' => $product->id,
                'attribute_values' => $attributeValues
            ]);

            // Use the attribute service for updates
            $this->attributeService->handleAttributeUpdate($product, $attributeValues);

            // Reload the product to ensure we have the latest attribute data
            $product->refresh()->load(['attributeValues.attribute']);

            // Log the updated attribute values for debugging
            Log::info('Updated product attributes', [
                'product_id' => $product->id,
                'attribute_count' => $product->attributeValues->count(),
                'attribute_ids' => $product->attributeValues->pluck('attribute_id')->toArray()
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating product attributes', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Update images for a product
     *
     * @param Product $product The product to update
     * @param array $imageData The image data, can contain paths for new images and IDs to delete
     * @return array Array of image IDs
     */
    protected function updateImages(Product $product, array $imageData): array
    {
        $imagePaths = $imageData;
        $imageIdsToDelete = [];

        // Check if we have a structured format with paths and delete_ids
        if (isset($imageData['paths'])) {
            $imagePaths = $imageData['paths'];
            $imageIdsToDelete = $imageData['delete_ids'] ?? [];
        }

        // Use the image service to handle the update
        return $this->imageService->handleImageUpdate($product, $imagePaths, $imageIdsToDelete);
    }

    public function deleteProduct(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            foreach ($product->images as $image) {
                // Remove /storage/ prefix if present for proper deletion
                $storagePath = $this->imageService->getStoragePath($image->path);
                Storage::disk('public')->delete($storagePath);
                $image->delete();
            }

            $product->attributeValues()->detach();
            $product->delete();

            return true;
        });
    }
}
