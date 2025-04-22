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
     * Generate variants for a product based on its variant-generating attributes
     *
     * @param Product $product
     * @return array
     */
    public function generateVariantsForProduct(Product $product)
    {
        try {
            DB::beginTransaction();

            // Find variant-generating attributes for this product's subcategory
            $variantAttributes = $product->subcategory
                ->attributes()
                ->where('is_variant_generator', true)
                ->with(['values' => function($query) use ($product) {
                    // Only include values that are assigned to this product
                    $query->whereHas('products', function($q) use ($product) {
                        $q->where('products.id', $product->id);
                    });
                }])
                ->get();

            // If no variant-generating attributes or no values, return empty array
            if ($variantAttributes->isEmpty() || $variantAttributes->pluck('values')->flatten()->isEmpty()) {
                DB::commit();
                return [];
            }

            // Clear existing variants
            $product->variants()->delete();

            // Generate all possible combinations
            $combinations = $this->generateAttributeCombinations($variantAttributes);

            $generatedVariants = [];
            foreach ($combinations as $combination) {
                $variant = $this->createVariantFromCombination($product, $combination);
                if ($variant) {
                    $generatedVariants[] = $variant;
                }
            }

            DB::commit();
            return $generatedVariants;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error generating variants', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Generate all possible combinations of attribute values
     *
     * @param \Illuminate\Support\Collection $variantAttributes
     * @return array
     */
    protected function generateAttributeCombinations($variantAttributes)
    {
        if ($variantAttributes->isEmpty()) {
            return [];
        }

        // Start with first attribute's values
        $firstAttribute = $variantAttributes->first();
        $combinations = collect($firstAttribute->values->all());

        // Skip if no values for the first attribute
        if ($combinations->isEmpty()) {
            return [];
        }

        // Progressively combine with other attributes
        $variantAttributes->slice(1)->each(function ($attribute) use (&$combinations) {
            // Skip if this attribute has no values
            if ($attribute->values->isEmpty()) {
                return;
            }

            $newCombinations = collect();

            $combinations->each(function ($existingCombination) use ($attribute, &$newCombinations) {
                $attribute->values->each(function ($value) use ($existingCombination, &$newCombinations) {
                    $newCombination = is_array($existingCombination)
                        ? array_merge($existingCombination, [$value])
                        : [$existingCombination, $value];

                    $newCombinations->push($newCombination);
                });
            });

            $combinations = $newCombinations;
        });

        return $combinations->all();
    }

    /**
     * Create a variant from a combination of attribute values
     *
     * @param Product $product
     * @param array $combination
     * @return Variant|null
     */
    protected function createVariantFromCombination(Product $product, $combination)
    {
        try {
            // Calculate variant-specific price (default to product price)
            $basePrice = $product->price;

            // Generate variant name from combination
            $name = $this->generateVariantName($combination);

            // Generate unique SKU
            $sku = $this->generateSku($product, $combination);

            // Create variant
            $variant = $product->variants()->create([
                'name' => $name,
                'price' => $basePrice
                // We'll add SKU and stock tracking in a future update
            ]);

            // Attach attribute values
            $attributeValueIds = collect($combination)->pluck('id')->all();
            $variant->attributeValues()->attach($attributeValueIds);

            return $variant;
        } catch (\Exception $e) {
            Log::error('Error creating variant', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generate a variant name from a combination of attribute values
     *
     * @param array $combination
     * @return string
     */
    protected function generateVariantName($combination)
    {
        return collect($combination)
            ->map(function($value) {
                return $value->name;
            })
            ->implode(' / ');
    }

    /**
     * Generate a unique SKU for a variant
     *
     * @param Product $product
     * @param array $combination
     * @return string
     */
    protected function generateSku(Product $product, $combination)
    {
        $productId = $product->id;
        $attributeValueIds = collect($combination)
            ->map(function($value) {
                return $value->id;
            })
            ->sort()
            ->implode('-');

        return "P{$productId}-{$attributeValueIds}";
    }

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
            $variant->update([
                'price' => $data['price'] ?? $variant->price
                // We'll add SKU and stock tracking in a future update
            ]);

            // Update attribute values if provided
            if (isset($data['attribute_values']) && is_array($data['attribute_values'])) {
                $variant->attributeValues()->sync($data['attribute_values']);
            }

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
            ->with('attributeValues')
            ->get();
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
}
