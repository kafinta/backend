<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Variant;
use App\Models\AttributeValue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VariantService
{
    /**
     * Create a variant for a product with specific attribute values
     *
     * @param Product $product The product to create a variant for
     * @param array $data Variant data including name, price, stock info, and attributes
     * @return Variant
     */
    public function createVariant(Product $product, array $data)
    {
        try {
            DB::beginTransaction();

            // Validate required fields
            $this->validateVariantData($data);

            // Validate attributes belong to product's subcategory
            $this->validateAttributesForSubcategory($product, $data['attributes']);

            // Create the variant with inventory fields
            $variant = $product->variants()->create([
                'name' => $data['name'],
                'price' => $data['price'],
                'manage_stock' => $data['manage_stock'] ?? true,
                'stock_quantity' => $data['stock_quantity'] ?? 0
            ]);

            // Convert attribute pairs to the format expected by setAttributeValues
            $attributeValues = [];
            foreach ($data['attributes'] as $attributePair) {
                $attributeValues[$attributePair['attribute_id']] = $attributePair['value_id'];
            }

            // Set attributes with uniqueness validation
            $variant->setAttributeValues($attributeValues);

            // Load relationships for response
            $variant->load(['attributeValues.attribute', 'images']);

            // Format the variant attributes
            $variant = $this->formatVariantAttributes($variant);

            DB::commit();
            return $variant;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating variant', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Validate variant data
     *
     * @param array $data
     * @throws \Exception
     */
    protected function validateVariantData(array $data)
    {
        if (empty($data['name'])) {
            throw new \Exception('Variant name is required');
        }

        if (!isset($data['price']) || $data['price'] < 0) {
            throw new \Exception('Valid variant price is required');
        }

        if (empty($data['attributes']) || !is_array($data['attributes'])) {
            throw new \Exception('Variant attributes are required');
        }

        // Validate attribute structure
        foreach ($data['attributes'] as $attribute) {
            if (!isset($attribute['attribute_id']) || !isset($attribute['value_id'])) {
                throw new \Exception('Each attribute must have attribute_id and value_id');
            }
        }
    }

    /**
     * Validate that attributes belong to product's subcategory
     *
     * @param Product $product
     * @param array $attributes
     * @throws \Exception
     */
    protected function validateAttributesForSubcategory(Product $product, array $attributes)
    {
        $subcategoryAttributeIds = $product->subcategory->attributes()->pluck('attributes.id')->toArray();

        foreach ($attributes as $attribute) {
            // Check if attribute belongs to subcategory
            if (!in_array($attribute['attribute_id'], $subcategoryAttributeIds)) {
                throw new \Exception("Attribute ID {$attribute['attribute_id']} does not belong to subcategory '{$product->subcategory->name}'");
            }

            // Check if value belongs to attribute
            $valueExists = DB::table('attribute_values')
                ->where('id', $attribute['value_id'])
                ->where('attribute_id', $attribute['attribute_id'])
                ->exists();

            if (!$valueExists) {
                throw new \Exception("Value ID {$attribute['value_id']} does not belong to attribute ID {$attribute['attribute_id']}");
            }
        }
    }

    // We've removed the automatic variant generation methods since we're using manual variant creation

    /**
     * Update a variant
     *
     * @param Variant $variant
     * @param array $data
     * @return Variant
     */
    public function updateVariant(Variant $variant, array $data)
    {
        try {
            DB::beginTransaction();

            // Update basic variant fields
            $updateFields = [];
            if (isset($data['name'])) {
                $updateFields['name'] = $data['name'];
            }
            if (isset($data['price'])) {
                if ($data['price'] < 0) {
                    throw new \Exception('Price cannot be negative');
                }
                $updateFields['price'] = $data['price'];
            }
            if (isset($data['manage_stock'])) {
                $updateFields['manage_stock'] = $data['manage_stock'];
            }
            if (isset($data['stock_quantity'])) {
                if ($data['stock_quantity'] < 0) {
                    throw new \Exception('Stock quantity cannot be negative');
                }
                $updateFields['stock_quantity'] = $data['stock_quantity'];
            }

            if (!empty($updateFields)) {
                $variant->update($updateFields);
            }

            // Update attribute values if provided
            if (isset($data['attributes']) && is_array($data['attributes'])) {
                // Validate attributes belong to product's subcategory
                $this->validateAttributesForSubcategory($variant->product, $data['attributes']);

                // Convert attribute pairs to the format expected by setAttributeValues
                $attributeValues = [];
                foreach ($data['attributes'] as $attributePair) {
                    $attributeValues[$attributePair['attribute_id']] = $attributePair['value_id'];
                }

                // Set attributes with uniqueness validation
                $variant->setAttributeValues($attributeValues);
            }

            // Reload the variant with its attribute values and images
            $variant->load(['attributeValues.attribute', 'images']);

            // Format the variant attributes
            $variant = $this->formatVariantAttributes($variant);

            DB::commit();
            return $variant;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating variant', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get variants for a product with their attribute values
     *
     * @param Product $product
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVariantsForProduct(Product $product)
    {
        return $product->variants()
            ->with(['attributeValues.attribute', 'images'])
            ->get()
            ->map(function ($variant) {
                // Format the variant attributes
                return $this->formatVariantAttributes($variant);
            });
    }

    /**
     * Delete a variant
     *
     * @param Variant $variant
     * @return bool
     */
    public function deleteVariant(Variant $variant)
    {
        try {
            DB::beginTransaction();

            // Delete variant attribute values
            $variant->attributeValues()->detach();

            // Delete variant
            $result = $variant->delete();

            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting variant', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Format variant attributes to match the product attribute format
     *
     * @param Variant $variant
     * @return Variant
     */
    public function formatVariantAttributes(Variant $variant)
    {
        // Format the attributes to match the product attribute format
        $attributes = $variant->attributeValues->map(function ($attributeValue) {
            // If name is null, use the value from the representation or a default
            $valueName = $attributeValue->name;
            if ($valueName === null) {
                // Try to get the name from the representation
                if (is_array($attributeValue->representation) && isset($attributeValue->representation['value'])) {
                    $valueName = $attributeValue->representation['value'];
                } else {
                    // Use a default value
                    $valueName = 'Unknown';
                }
            }

            return [
                'id' => $attributeValue->attribute->id,
                'name' => $attributeValue->attribute->name,
                'value' => [
                    'id' => $attributeValue->id,
                    'name' => $valueName,
                    'representation' => $attributeValue->representation
                ]
            ];
        });

        // Add the formatted attributes to the variant using the same key as products
        $variant->setAttribute('attributes', $attributes);

        // Remove the raw attributeValues relation to prevent it from being serialized
        $variant->unsetRelation('attributeValues');

        return $variant;
    }
}
