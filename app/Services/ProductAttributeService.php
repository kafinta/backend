<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Attribute;
use App\Models\Subcategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Http\Resources\AttributeResource;
use Illuminate\Support\Facades\Log;

class ProductAttributeService
{
    protected $formService;

    public function __construct(MultiStepFormService $formService)
    {
        $this->formService = $formService;
    }

    /**
     * Handle attribute updates with support for add/remove/replace operations
     */
    /**
     * Handle attribute updates for a product
     * 
     * @param Product $product
     * @param array $changes Array containing attribute changes
     *                      Format:
     *                      [
     *                          'update' => [
     *                              ['attribute_id' => 1, 'value_id' => 2],  // Update existing attribute to new value
     *                          ],
     *                          'add' => [
     *                              ['attribute_id' => 3, 'value_id' => 4],  // Add new attribute-value pair
     *                          ],
     *                          'remove' => [
     *                              ['attribute_id' => 5]  // Remove attribute entirely
     *                          ]
     *                      ]
     */
    public function handleAttributeUpdate(Product $product, array $changes): void
    {
        try {
            DB::beginTransaction();
            
            // First handle updates to existing attributes
            if (isset($changes['update'])) {
                foreach ($changes['update'] as $update) {
                    // Validate the new value
                    $this->validateAttributeValues([$update], $product->subcategory);
                    
                    // Update the value
                    $product->attributeValues()
                        ->where('attribute_id', $update['attribute_id'])
                        ->delete();
                    $product->attributeValues()->attach($update['value_id']);
                }
            }
            
            // Handle new attributes
            if (isset($changes['add'])) {
                // Validate all new values
                $this->validateAttributeValues($changes['add'], $product->subcategory);
                
                // Check for duplicates
                $existingAttributeIds = $product->attributeValues()
                    ->pluck('attribute_id')
                    ->toArray();
                    
                foreach ($changes['add'] as $add) {
                    if (in_array($add['attribute_id'], $existingAttributeIds)) {
                        throw new \InvalidArgumentException(
                            "Attribute ID {$add['attribute_id']} already exists. Use 'update' instead."
                        );
                    }
                }
                
                // Attach new values
                foreach ($changes['add'] as $add) {
                    $product->attributeValues()->attach($add['value_id']);
                }
            }
            
            // Handle attribute removals (if allowed by business rules)
            if (isset($changes['remove'])) {
                foreach ($changes['remove'] as $remove) {
                    // Check if this is a required attribute
                    $attribute = $product->subcategory->attributes()
                        ->where('attributes.id', $remove['attribute_id'])
                        ->wherePivot('is_required', true)
                        ->first();
                        
                    if ($attribute) {
                        throw new \InvalidArgumentException(
                            "Cannot remove required attribute: {$attribute->name}"
                        );
                    }
                    
                    // Remove the attribute value
                    $product->attributeValues()
                        ->where('attribute_id', $remove['attribute_id'])
                        ->delete();
                }
            }

            // Validate that all required attributes are still present
            $this->validateRequiredAttributes($product);
            
            // Handle variant generation if needed
            $this->handleVariantGeneration($product);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating product attributes', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Validate required attributes for a product
     */
    protected function validateRequiredAttributes(Product $product): void
    {
        $requiredAttributes = $product->subcategory->attributes()
            ->wherePivot('is_required', true)
            ->get();

        $missingAttributes = $requiredAttributes->filter(function ($attribute) use ($product) {
            return !$product->attributeValues()
                ->where('attribute_id', $attribute->id)
                ->exists();
        });

        if ($missingAttributes->isNotEmpty()) {
            throw new \InvalidArgumentException(
                'Missing required attributes: ' . 
                $missingAttributes->pluck('name')->implode(', ')
            );
        }
    }

    /**
     * Handle variant generation for variant-generating attributes
     */
    protected function handleVariantGeneration(Product $product): void
    {
        $variantAttributes = $product->attributeValues()
            ->whereHas('attribute', function ($query) {
                $query->where('is_variant_generator', true);
            })
            ->get()
            ->groupBy('attribute_id');

        if ($variantAttributes->isEmpty()) {
            return;
        }

        // Clear existing variants
        $product->variants()->delete();

        // Generate new variants
        $combinations = $this->generateAttributeCombinations($variantAttributes);
        foreach ($combinations as $combination) {
            $product->variants()->create([
                'name' => $this->generateVariantName($combination),
                'price' => $product->price
            ]);
        }
    }

    /**
     * Generate attribute combinations for variants
     */
    protected function generateAttributeCombinations($groupedAttributes): array
    {
        $result = [[]];
        
        foreach ($groupedAttributes as $attributeId => $values) {
            $temp = [];
            foreach ($result as $existing) {
                foreach ($values as $value) {
                    $temp[] = array_merge($existing, [$attributeId => $value]);
                }
            }
            $result = $temp;
        }

        return $result;
    }

    /**
     * Generate variant name from combination of attributes
     */
    protected function generateVariantName(array $combination): string
    {
        return collect($combination)
            ->map(fn($value) => $value->name)
            ->implode(' - ');
    }

    /**
     * Handle the attribute step in product creation/update
     */
    public function handleAttributeStep(array $validatedData)
    {
        try {
            if (!isset($validatedData['session_id'])) {
                throw new \InvalidArgumentException('Session ID is required');
            }

            // Get the form data
            $formData = $this->formService->getData('product_form', $validatedData['session_id']);
            
            if (!$formData || !isset($formData['data']['basic_info'])) {
                throw new \InvalidArgumentException('Please complete step 1 first');
            }

            // Get the subcategory with its attributes
            $subcategory = Subcategory::with('attributes')->findOrFail($formData['data']['basic_info']['subcategory_id']);

            // Validate attribute values
            $rawAttributes = $this->validateAttributeValues(
                $validatedData['attributes'],
                $subcategory
            );

            // Group values by attribute
            $groupedAttributes = [];
            foreach ($subcategory->attributes as $attribute) {
                // Find the value for this attribute
                $attributeValue = collect($rawAttributes)
                    ->where('attribute_id', $attribute->id)
                    ->first();
                
                if ($attributeValue) {
                    // Load the value details
                    $value = $attribute->values()
                        ->where('id', $attributeValue['value_id'])
                        ->first();
                    
                    if ($value) {
                        $groupedAttributes[] = [
                            'id' => $attribute->id,
                            'name' => $attribute->name,
                            'value' => [
                                'id' => $value->id,
                                'name' => $value->name,
                                'representation' => $value->representation
                            ]
                        ];
                    }
                }
            }

            // Update form data with both raw and grouped formats
            $formData['data']['attributes'] = [
                'raw' => $rawAttributes,
                'grouped' => $groupedAttributes
            ];

            // Save updated form data
            $this->formService->save('product_form', $validatedData['session_id'], $formData);

            return [
                'success' => true,
                'session_id' => $validatedData['session_id'],
                'attributes' => $groupedAttributes
            ];

        } catch (\Exception $e) {
            Log::error('Error in handleAttributeStep', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'session_id' => $validatedData['session_id'] ?? null,
                'attributes' => $validatedData['attributes'] ?? []
            ]);
            throw $e;
        }
    }

    /**
     * Validate attribute values for a subcategory
     */
    protected function validateAttributeValues(array $attributes, Subcategory $subcategory): array
    {
        // Get all allowed values for this subcategory with their attributes
        $allowedValues = $subcategory->attributeValues()
            ->with('attribute')
            ->get()
            ->groupBy('attribute_id')
            ->map(function($values) {
                return $values->pluck('id')->toArray();
            })
            ->toArray();
        
        if (empty($allowedValues)) {
            throw new \InvalidArgumentException('No allowed values found for this subcategory');
        }
        
        // Track used attributes to ensure no duplicates
        $usedAttributes = [];
        
        // Validate each attribute value
        foreach ($attributes as $attributeData) {
            if (!isset($attributeData['attribute_id'], $attributeData['value_id'])) {
                throw new \InvalidArgumentException('Each attribute must contain attribute_id and value_id');
            }
            
            $attributeId = $attributeData['attribute_id'];
            $valueId = $attributeData['value_id'];
            
            // Check for duplicate attributes
            if (in_array($attributeId, $usedAttributes)) {
                throw new \InvalidArgumentException('Duplicate attribute ID: ' . $attributeId . '. Only one value per attribute is allowed.');
            }
            
            // Check if attribute exists in subcategory
            $attribute = $this->findAttributeById($attributeId, $subcategory);
            
            // Check if value is allowed for this attribute
            if (!isset($allowedValues[$attributeId]) || !in_array($valueId, $allowedValues[$attributeId])) {
                throw new \InvalidArgumentException('Invalid value ID: ' . $valueId . ' for attribute: ' . $attribute->name);
            }
            
            $usedAttributes[] = $attributeId;
        }
        
        return $attributes;
    }

    /**
     * Sync product attributes from its subcategory
     */
    public function syncProductAttributes(Product $product)
    {
        return DB::transaction(function () use ($product) {
            return $product->syncAttributesFromSubcategory();
        });
    }

    /**
     * Update attribute values for a product
     */
    public function updateAttributeValues(Product $product, array $attributeValues)
    {
        return DB::transaction(function () use ($product, $attributeValues) {
            return $product->setAttributeValues($attributeValues);
        });
    }

    protected function findAttributeById(int $attributeId, Subcategory $subcategory)
    {
        $attribute = $subcategory->attributes()
            ->where('attributes.id', $attributeId)
            ->first();
            
        if (!$attribute) {
            throw new \InvalidArgumentException("Invalid attribute ID: {$attributeId}");
        }
        
        return $attribute;
    }

    protected function findAttributeValueById(int $valueId, $attribute, Subcategory $subcategory)
    {
        $value = $attribute->values()
            ->whereHas('subcategories', function ($query) use ($subcategory) {
                $query->where('subcategories.id', $subcategory->id);
            })
            ->where('id', $valueId)
            ->first();
            
        if (!$value) {
            throw new \InvalidArgumentException(
                "Invalid value ID '{$valueId}' for attribute {$attribute->name}"
            );
        }
        
        return $value;
    }

    public function attachAttributeValues(Product $product, array $attributes): void
    {
        try {
            DB::beginTransaction();
            
            // Validate and transform the attributes
            $validatedAttributes = $this->validateAttributeValues($attributes, $product->subcategory);
            
            // Attach each attribute value
            foreach ($validatedAttributes as $attribute) {
                $product->attributeValues()->attach($attribute['value_id']);
            }
            
            // Check required attributes after changes
            $this->validateRequiredAttributes($product);
            
            // Handle variant generation if needed
            $this->handleVariantGeneration($product);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error attaching product attributes', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function syncAttributeValues(Product $product, array $attributes): void
    {
        $valueIds = collect($attributes)->pluck('value_id')->toArray();
        $product->attributeValues()->sync($valueIds);
    }
}
