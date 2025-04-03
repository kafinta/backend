<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Attribute;
use App\Models\Subcategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Http\Resources\AttributeResource;

class ProductAttributeService
{
    protected $formService;

    public function __construct(MultiStepFormService $formService)
    {
        $this->formService = $formService;
    }

    /**
     * Handle the attribute step in the product creation process
     */
    public function handleAttributeStep(array $validatedData)
    {
        $sessionKey = $this->formService->getSessionKey('product_form', $validatedData['session_id']);
        $formData = Session::get($sessionKey, []);
        
        if (empty($formData) || !isset($formData['data']['subcategory_id'])) {
            throw new \InvalidArgumentException('Please complete step 1 first');
        }
        
        $subcategory = Subcategory::with('attributes.values')
            ->findOrFail($formData['data']['subcategory_id']);
        
        $attributeValues = $this->validateAndTransformAttributes(
            $validatedData['attributes'], 
            $subcategory
        );
        
        // Store the transformed data
        $formData['data']['attribute_values'] = $attributeValues;
        Session::put($sessionKey, $formData);
        
        return [
            'success' => true,
            'session_id' => $validatedData['session_id'],
            'attributes' => AttributeResource::collection($subcategory->attributes)
        ];
    }

    /**
     * Validate and transform attribute data from the request
     */
    protected function validateAndTransformAttributes(array $attributes, Subcategory $subcategory)
    {
        $attributeValues = [];
        
        foreach ($attributes as $attributeData) {
            $attribute = $this->findAttribute($attributeData['attribute'], $subcategory);
            $value = $this->findAttributeValue($attribute, $attributeData['value'], $subcategory);
            
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

    /**
     * Validate attribute values for a product
     */
    public function validateAttributeValues(Product $product, array $attributeValues)
    {
        foreach ($attributeValues as $attributeId => $valueId) {
            $attribute = Attribute::findOrFail($attributeId);
            
            if (!$product->subcategory->attributes->contains($attribute)) {
                throw new \InvalidArgumentException(
                    "Attribute {$attribute->name} is not valid for this product's subcategory"
                );
            }

            $attribute->validateValuesForSubcategory($product->subcategory, [$valueId]);
        }

        return true;
    }
}