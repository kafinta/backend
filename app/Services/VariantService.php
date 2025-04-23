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
     * @param string $name The name of the variant
     * @param float $price The price of the variant
     * @param array $attributeValues Array of attribute and value pairs
     * @return Variant
     */
    public function createVariant(Product $product, string $name, float $price, array $attributeValues)
    {
        try {
            DB::beginTransaction();

            // Create the variant
            $variant = $product->variants()->create([
                'name' => $name,
                'price' => $price
            ]);

            // Process attribute values
            foreach ($attributeValues as $attributeValue) {
                // Verify that the attribute value belongs to the attribute
                $valueExists = DB::table('attribute_values')
                    ->where('id', $attributeValue['value_id'])
                    ->where('attribute_id', $attributeValue['attribute_id'])
                    ->exists();

                if (!$valueExists) {
                    throw new \Exception('Invalid attribute value combination');
                }

                // Attach the attribute value to the variant
                $variant->attributeValues()->attach($attributeValue['value_id']);
            }

            // Load the attribute values relationship with their attributes
            $variant->load('attributeValues.attribute');

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

            // Update variant fields
            $updateFields = [];
            if (isset($data['name'])) {
                $updateFields['name'] = $data['name'];
            }
            if (isset($data['price'])) {
                $updateFields['price'] = $data['price'];
            }

            if (!empty($updateFields)) {
                $variant->update($updateFields);
            }

            // Update attribute values if provided
            if (isset($data['attribute_values']) && is_array($data['attribute_values'])) {
                // First detach all existing attribute values
                $variant->attributeValues()->detach();

                // Then attach the new attribute values
                foreach ($data['attribute_values'] as $attributeValue) {
                    // Verify that the attribute value belongs to the attribute
                    $valueExists = DB::table('attribute_values')
                        ->where('id', $attributeValue['value_id'])
                        ->where('attribute_id', $attributeValue['attribute_id'])
                        ->exists();

                    if (!$valueExists) {
                        throw new \Exception('Invalid attribute value combination');
                    }

                    // Attach the attribute value to the variant
                    $variant->attributeValues()->attach($attributeValue['value_id']);
                }
            }

            // Reload the variant with its attribute values
            $variant->load('attributeValues.attribute');

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
            ->with(['attributeValues.attribute'])
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
