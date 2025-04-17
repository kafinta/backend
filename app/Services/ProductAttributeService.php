<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Attribute;
use App\Models\Subcategory;
use App\Models\AttributeValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Http\Resources\AttributeResource;
use Illuminate\Support\Facades\Log;

// Events
use App\Events\Product\AttributeAdded;
use App\Events\Product\AttributeUpdated;
use App\Events\Product\AttributeRemoved;
use App\Events\Product\ValidationFailed;

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

            // Check if the product's subcategory has changed recently
            $subcategoryChanged = $product->wasChanged('subcategory_id');

            // If subcategory has changed, we need to handle attributes differently
            if ($subcategoryChanged) {
                Log::info('Subcategory changed, handling attributes accordingly', [
                    'product_id' => $product->id,
                    'old_subcategory_id' => $product->getOriginal('subcategory_id'),
                    'new_subcategory_id' => $product->subcategory_id
                ]);

                // Get all existing attribute values
                $existingAttributeValues = $product->attributeValues()
                    ->with('attribute')
                    ->get();

                // Get all attributes for the new subcategory
                $newSubcategoryAttributes = $product->subcategory->attributes()
                    ->select('attributes.*')
                    ->with(['values' => function($query) {
                        $query->select('attribute_values.*');
                    }])
                    ->get();

                // Get attribute IDs that are valid for the new subcategory
                $validAttributeIds = $newSubcategoryAttributes->pluck('id')->toArray();

                // Identify attributes to keep (those that belong to both the old and new subcategory)
                $attributesToKeep = $existingAttributeValues->filter(function($value) use ($validAttributeIds) {
                    return in_array($value->attribute_id, $validAttributeIds);
                });

                // Remove all attribute values
                $product->attributeValues()->detach();

                // Re-attach the valid ones
                foreach ($attributesToKeep as $value) {
                    $product->attributeValues()->attach($value->id);

                    Log::info('Kept existing attribute after subcategory change', [
                        'product_id' => $product->id,
                        'attribute_id' => $value->attribute_id,
                        'value_id' => $value->id,
                        'attribute_name' => $value->attribute->name ?? 'Unknown'
                    ]);
                }

                // If we have new attributes to add, validate them against the new subcategory
                if (isset($changes['add'])) {
                    // Validate all new values against the new subcategory
                    $this->validateAttributeValues($changes['add'], $product->subcategory);

                    // Get existing attribute IDs after re-attaching valid ones
                    $existingAttributeIds = $product->attributeValues()
                        ->pluck('attribute_id')
                        ->toArray();

                    // Process each new attribute
                    foreach ($changes['add'] as $add) {
                        $attributeId = $add['attribute_id'];
                        $valueId = $add['value_id'];

                        // Check if this attribute already exists for the product
                        if (in_array($attributeId, $existingAttributeIds)) {
                            // Update the existing attribute value
                            $product->attributeValues()
                                ->where('attribute_id', $attributeId)
                                ->delete();
                            $product->attributeValues()->attach($valueId);

                            Log::info('Updated existing attribute after subcategory change', [
                                'product_id' => $product->id,
                                'attribute_id' => $attributeId,
                                'value_id' => $valueId
                            ]);
                        } else {
                            // Add new attribute value
                            $product->attributeValues()->attach($valueId);

                            // Get attribute and value details for the event
                            $attribute = Attribute::find($attributeId);
                            $attributeValue = AttributeValue::find($valueId);

                            // Dispatch event
                            event(new AttributeAdded(
                                $product,
                                $attributeId,
                                $valueId,
                                $attribute ? $attribute->name : null,
                                $attributeValue ? $attributeValue->name : null
                            ));

                            Log::info('Added new attribute after subcategory change', [
                                'product_id' => $product->id,
                                'attribute_id' => $attributeId,
                                'value_id' => $valueId
                            ]);
                        }
                    }
                }
            } else {
                // Normal attribute update flow (no subcategory change)

                // Get existing attribute values with their attribute info
                $existingAttributeValues = $product->attributeValues()
                    ->with('attribute')
                    ->get();

                // Create a map of attribute_id => value_id for easier lookup
                $existingAttributeMap = $existingAttributeValues->pluck('id', 'attribute_id')->toArray();

                // Get existing attribute IDs for checking duplicates
                $existingAttributeIds = array_keys($existingAttributeMap);

                // Combine update and add operations for simpler processing
                $attributesToProcess = [];

                if (isset($changes['update'])) {
                    $attributesToProcess = array_merge($attributesToProcess, $changes['update']);
                }

                if (isset($changes['add'])) {
                    $attributesToProcess = array_merge($attributesToProcess, $changes['add']);
                }

                // Validate all attributes against the subcategory
                $this->validateAttributeValues($attributesToProcess, $product->subcategory);

                // Process each attribute
                foreach ($attributesToProcess as $attribute) {
                    $attributeId = $attribute['attribute_id'];
                    $valueId = $attribute['value_id'];

                    // Check if this attribute already exists for the product
                    if (in_array($attributeId, $existingAttributeIds)) {
                        // Update the existing attribute value only if it's different
                        $currentValueId = $existingAttributeMap[$attributeId];

                        if ($currentValueId != $valueId) {
                            // Update the existing attribute value
                            $product->attributeValues()
                                ->where('attribute_id', $attributeId)
                                ->delete();
                            $product->attributeValues()->attach($valueId);

                            // Get attribute and value details for the event
                            $attribute = Attribute::find($attributeId);
                            $oldValue = AttributeValue::find($currentValueId);
                            $newValue = AttributeValue::find($valueId);

                            // Dispatch event
                            event(new AttributeUpdated(
                                $product,
                                $attributeId,
                                $currentValueId,
                                $valueId,
                                $attribute ? $attribute->name : null,
                                $oldValue ? $oldValue->name : null,
                                $newValue ? $newValue->name : null
                            ));

                            Log::info('Updated existing attribute', [
                                'product_id' => $product->id,
                                'attribute_id' => $attributeId,
                                'old_value_id' => $currentValueId,
                                'new_value_id' => $valueId
                            ]);
                        } else {
                            Log::info('Attribute value unchanged, skipping update', [
                                'product_id' => $product->id,
                                'attribute_id' => $attributeId,
                                'value_id' => $valueId
                            ]);
                        }
                    } else {
                        // Add new attribute value
                        $product->attributeValues()->attach($valueId);

                        // Get attribute and value details for the event
                        $attribute = Attribute::find($attributeId);
                        $attributeValue = AttributeValue::find($valueId);

                        // Dispatch event
                        event(new AttributeAdded(
                            $product,
                            $attributeId,
                            $valueId,
                            $attribute ? $attribute->name : null,
                            $attributeValue ? $attributeValue->name : null
                        ));

                        Log::info('Added new attribute', [
                            'product_id' => $product->id,
                            'attribute_id' => $attributeId,
                            'value_id' => $valueId
                        ]);
                    }
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

                    // Get the attribute and value details before removal
                    $attributeId = $remove['attribute_id'];
                    $attribute = Attribute::find($attributeId);

                    // Find the current value for this attribute
                    $currentValue = $product->attributeValues()
                        ->where('attribute_id', $attributeId)
                        ->first();

                    // Remove the attribute value
                    $product->attributeValues()
                        ->where('attribute_id', $attributeId)
                        ->delete();

                    // Dispatch event
                    event(new AttributeRemoved(
                        $product,
                        $attributeId,
                        $currentValue ? $currentValue->id : null,
                        $attribute ? $attribute->name : null,
                        $currentValue ? $currentValue->name : null
                    ));
                }
            }

            // Handle complete replacement of attributes
            if (isset($changes['replace'])) {
                // First validate all the new values
                $this->validateAttributeValues($changes['replace'], $product->subcategory);

                // Get all existing attribute IDs
                $existingAttributeIds = $product->attributeValues()
                    ->pluck('attribute_id')
                    ->toArray();

                // Detach all existing attribute values
                $product->attributeValues()->detach();

                // Attach the new values
                foreach ($changes['replace'] as $replace) {
                    $product->attributeValues()->attach($replace['value_id']);
                }

                Log::info('Replaced all product attributes', [
                    'product_id' => $product->id,
                    'old_attribute_ids' => $existingAttributeIds,
                    'new_attributes' => $changes['replace']
                ]);
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
     * Handle the attribute step in product creation
     */
    public function handleAttributeStep(array $validatedData)
    {
        try {
            if (!isset($validatedData['session_id'])) {
                throw new \InvalidArgumentException('Session ID is required');
            }

            // Get the form data
            $formType = 'product_form';
            $formData = $this->formService->getData($formType, $validatedData['session_id']);

            if (!$formData || !isset($formData['data']['basic_info'])) {
                throw new \InvalidArgumentException('Please complete step 1 first');
            }

            // Get the subcategory from form data
            $subcategory = Subcategory::with('attributes')
                ->findOrFail($formData['data']['basic_info']['subcategory_id']);

            return $this->processAttributeStep($validatedData, $formData, $subcategory);
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
     * Handle the attribute step in product update
     */
    public function handleUpdateAttributeStep(array $validatedData, Product $product)
    {
        try {
            if (!isset($validatedData['session_id'])) {
                throw new \InvalidArgumentException('Session ID is required');
            }

            // Get the form data
            $formType = 'product_form';
            $formData = $this->formService->getData($formType, $validatedData['session_id']);

            if (!$formData || !isset($formData['data']['basic_info'])) {
                throw new \InvalidArgumentException('Please complete step 1 first');
            }

            // Get the subcategory from the product
            $subcategory = $product->subcategory;

            // Add product ID to the validated data
            $validatedData['product_id'] = $product->id;

            return $this->processAttributeStep($validatedData, $formData, $subcategory);
        } catch (\Exception $e) {
            Log::error('Error in handleUpdateAttributeStep', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'product_id' => $product->id,
                'session_id' => $validatedData['session_id'] ?? null,
                'attributes' => $validatedData['attributes'] ?? []
            ]);
            throw $e;
        }
    }

    /**
     * Process attribute step data for both creation and update
     */
    protected function processAttributeStep(array $validatedData, array $formData, Subcategory $subcategory)
    {
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

        // Update form data with attributes and raw_attributes
        $formData['data']['attributes'] = $groupedAttributes;
        $formData['data']['raw_attributes'] = $rawAttributes;

        // Save updated form data
        $sessionKey = $this->formService->getSessionKey('product_form', $validatedData['session_id']);
        Session::put($sessionKey, $formData);

        return [
            'success' => true,
            'session_id' => $validatedData['session_id'],
            'attributes' => $groupedAttributes
        ];
    }

    /**
     * Validate attribute values for a subcategory
     */
    protected function validateAttributeValues(array $attributes, Subcategory $subcategory): array
    {
        // Get all attributes that belong to this subcategory
        $subcategoryAttributes = $subcategory->attributes()->pluck('attributes.id')->toArray();

        if (empty($subcategoryAttributes)) {
            $error = 'No attributes found for subcategory ID: ' . $subcategory->id;

            // Dispatch validation failed event
            event(new ValidationFailed(
                null, // No product context available
                'attribute_validation',
                ['message' => $error],
                ['subcategory_id' => $subcategory->id]
            ));

            throw new \InvalidArgumentException($error);
        }

        // Get all allowed values for this subcategory with their attributes
        $allowedValues = $subcategory->attributes()
            ->select('attributes.*') // Explicitly select all columns from attributes table
            ->with(['values' => function($query) {
                $query->select('attribute_values.*'); // Explicitly select all columns from attribute_values table
            }])
            ->get()
            ->mapWithKeys(function($attribute) {
                return [$attribute->id => $attribute->values->pluck('id')->toArray()];
            })
            ->toArray();

        if (empty($allowedValues)) {
            throw new \InvalidArgumentException('No allowed values found for subcategory ID: ' . $subcategory->id);
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
            if (!in_array($attributeId, $subcategoryAttributes)) {
                $error = 'Attribute ID: ' . $attributeId . ' does not belong to subcategory ID: ' . $subcategory->id;

                // Dispatch validation failed event
                event(new ValidationFailed(
                    isset($product) ? $product : null,
                    'attribute_validation',
                    ['message' => $error],
                    [
                        'subcategory_id' => $subcategory->id,
                        'attribute_id' => $attributeId
                    ]
                ));

                throw new \InvalidArgumentException($error);
            }

            // Get the attribute details
            try {
                $attribute = $this->findAttributeById($attributeId, $subcategory);
            } catch (\Exception $e) {
                Log::error('Error finding attribute', [
                    'attribute_id' => $attributeId,
                    'subcategory_id' => $subcategory->id,
                    'error' => $e->getMessage()
                ]);
                throw new \InvalidArgumentException('Error finding attribute ID: ' . $attributeId . ' - ' . $e->getMessage());
            }

            // Check if value is allowed for this attribute
            if (!isset($allowedValues[$attributeId]) || !in_array($valueId, $allowedValues[$attributeId])) {
                throw new \InvalidArgumentException('Invalid value ID: ' . $valueId . ' for attribute: ' . $attribute->name . ' in subcategory: ' . $subcategory->name);
            }

            $usedAttributes[] = $attributeId;

            // Log successful validation
            Log::info('Validated attribute value', [
                'subcategory_id' => $subcategory->id,
                'subcategory_name' => $subcategory->name,
                'attribute_id' => $attributeId,
                'attribute_name' => $attribute->name,
                'value_id' => $valueId
            ]);
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
            ->select('attributes.*') // Explicitly select all columns from attributes table
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

            // Get existing attribute IDs for this product
            $existingAttributeMap = $product->attributeValues()
                ->get()
                ->mapWithKeys(function($value) {
                    return [$value->attribute_id => $value->id];
                })
                ->toArray();

            // Process each attribute value
            foreach ($validatedAttributes as $attribute) {
                $attributeId = $attribute['attribute_id'];
                $valueId = $attribute['value_id'];

                // Check if this attribute already exists for the product
                if (isset($existingAttributeMap[$attributeId])) {
                    $currentValueId = $existingAttributeMap[$attributeId];

                    // Only update if the value is different
                    if ($currentValueId != $valueId) {
                        // Update the existing attribute value
                        $product->attributeValues()
                            ->where('attribute_id', $attributeId)
                            ->delete();
                        $product->attributeValues()->attach($valueId);

                        Log::info('Updated existing attribute value', [
                            'product_id' => $product->id,
                            'attribute_id' => $attributeId,
                            'old_value_id' => $currentValueId,
                            'new_value_id' => $valueId
                        ]);
                    } else {
                        Log::info('Attribute value unchanged, skipping update', [
                            'product_id' => $product->id,
                            'attribute_id' => $attributeId,
                            'value_id' => $valueId
                        ]);
                    }
                } else {
                    // Add new attribute value
                    $product->attributeValues()->attach($valueId);

                    Log::info('Attached new attribute value', [
                        'product_id' => $product->id,
                        'attribute_id' => $attributeId,
                        'value_id' => $valueId
                    ]);
                }
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
