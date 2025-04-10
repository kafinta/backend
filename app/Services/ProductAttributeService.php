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
    public function handleAttributeUpdate(Product $product, array $changes): void
    {
        try {
            DB::beginTransaction();
            
            if (isset($changes['add'])) {
                $this->validateAttributeValues($changes['add'], $product->subcategory);
                $this->attachAttributeValues($product, $changes['add']);
            }
            
            if (isset($changes['remove'])) {
                $product->attributeValues()->detach(
                    collect($changes['remove'])->pluck('value_id')
                );
            }
            
            if (isset($changes['replace'])) {
                $this->validateAttributeValues($changes['replace'], $product->subcategory);
                $this->syncAttributeValues($product, $changes['replace']);
            }

            // Check required attributes after changes
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
            Log::info('Starting handleAttributeStep', [
                'session_id' => $validatedData['session_id'],
                'attributes' => $validatedData['attributes']
            ]);
            
            $sessionKey = $this->formService->getSessionKey('product_form', $validatedData['session_id']);
            $formData = Session::get($sessionKey);
            
            Log::info('Retrieved form data', [
                'sessionKey' => $sessionKey,
                'formData' => $formData
            ]);
            
            if (empty($formData) || !isset($formData['data']['basic_info'])) {
                throw new \InvalidArgumentException('Please complete step 1 first');
            }
            
            $subcategory = Subcategory::with('attributes.values')
                ->findOrFail($formData['data']['basic_info']['subcategory_id']);
            
            if (isset($validatedData['attributes'])) {
                // Validate attributes against subcategory
                $this->validateAttributeValues($validatedData['attributes'], $subcategory);
                
                // Store in session
                $formData['data']['attributes'] = array_map(function($attr) {
                    return [
                        'attribute_id' => (int)$attr['attribute_id'],
                        'value_id' => (int)$attr['value_id']
                    ];
                }, $validatedData['attributes']);
                
                Session::put($sessionKey, $formData);
            }
            
            return [
                'success' => true,
                'session_id' => $validatedData['session_id']
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
    public function validateAttributeValues(array $attributes, Subcategory $subcategory): void
    {
        // Get allowed attribute values for this subcategory
        $allowedValues = $subcategory->attributeValues()
            ->pluck('attribute_values.id')
            ->toArray();
        
        // Get required attribute IDs
        $requiredAttributeIds = $subcategory->attributes()
            ->wherePivot('is_required', true)
            ->pluck('attributes.id')
            ->toArray();

        // Get provided attribute IDs
        $providedAttributeIds = collect($attributes)->pluck('attribute_id')->toArray();

        // Check if all required attributes are provided
        $missingRequired = array_diff($requiredAttributeIds, $providedAttributeIds);
        if (!empty($missingRequired)) {
            $missingNames = $subcategory->attributes()
                ->whereIn('id', $missingRequired)
                ->pluck('name')
                ->implode(', ');
            throw new \InvalidArgumentException("Missing required attributes: {$missingNames}");
        }
        
        // Validate that all provided values are allowed for this subcategory
        $providedValueIds = collect($attributes)->pluck('value_id')->toArray();
        $invalidValues = array_diff($providedValueIds, $allowedValues);
        
        if (!empty($invalidValues)) {
            throw new \InvalidArgumentException(
                'Invalid attribute values: ' . implode(', ', $invalidValues)
            );
        }
    }

    /**
     * Validate and transform attribute data from the request
     */
    protected function validateAndTransformAttributes(array $attributes, Subcategory $subcategory)
    {
        $attributeValues = [];
        
        foreach ($attributes as $attributeData) {
            if (isset($attributeData['attribute_id'], $attributeData['value_id'])) {
                // Using IDs directly (preferred method)
                $attribute = $this->findAttributeById($attributeData['attribute_id'], $subcategory);
                $value = $this->findAttributeValueById($attributeData['value_id'], $attribute, $subcategory);
            } else {
                // Using names (fallback method)
                $attribute = $this->findAttribute($attributeData['attribute'], $subcategory);
                $value = $this->findAttributeValue($attribute, $attributeData['value'], $subcategory);
            }
            
            $attributeValues[$attribute->id] = $value->id;
        }
        
        return $attributeValues;
    }

    /**
     * Find an attribute by name or ID in a subcategory
     */
    protected function findAttribute(string $attributeName, Subcategory $subcategory)
    {
        $attribute = $subcategory->attributes()
            ->where(function($query) use ($attributeName) {
                $query->where('attributes.name', $attributeName)
                      ->orWhere('attributes.id', $attributeName);
            })
            ->first();
            
        if (!$attribute) {
            throw new \InvalidArgumentException("Invalid attribute: {$attributeName}");
        }
        
        return $attribute;
    }

    /**
     * Find an attribute value by name or ID for a specific attribute and subcategory
     */
    protected function findAttributeValue($attribute, $valueName, Subcategory $subcategory)
    {
        $value = $attribute->values()
            ->whereHas('subcategories', function ($query) use ($subcategory) {
                $query->where('subcategories.id', $subcategory->id);
            })
            ->where(function($query) use ($valueName) {
                $query->where('name', $valueName)
                      ->orWhere('id', $valueName);
            })
            ->first();
            
        if (!$value) {
            throw new \InvalidArgumentException(
                "Invalid value '{$valueName}' for attribute {$attribute->name}"
            );
        }
        
        return $value;
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

    protected function attachAttributeValues(Product $product, array $attributes): void
    {
        foreach ($attributes as $attribute) {
            $product->attributeValues()->attach($attribute['value_id']);
        }
    }

    protected function syncAttributeValues(Product $product, array $attributes): void
    {
        $valueIds = collect($attributes)->pluck('value_id')->toArray();
        $product->attributeValues()->sync($valueIds);
    }
}
