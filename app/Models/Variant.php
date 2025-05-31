<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'price',
        // Inventory fields
        'stock_quantity',
        'manage_stock'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'variant_attribute_values')
            ->withTimestamps();
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    protected $casts = [
        'price' => 'decimal:2',
        'manage_stock' => 'boolean',
    ];

    // ===== ATTRIBUTE COMBINATION METHODS =====

    /**
     * Set attribute values for this variant with uniqueness validation
     *
     * @param array $attributeValues Array of ['attribute_id' => value_id] pairs
     * @return void
     * @throws \Exception
     */
    public function setAttributeValues(array $attributeValues)
    {
        // Validate uniqueness before setting
        $this->validateUniqueAttributeCombination($attributeValues);

        // Detach all existing attributes
        $this->attributeValues()->detach();

        // Attach new attributes
        foreach ($attributeValues as $attributeId => $valueId) {
            $this->attributeValues()->attach($valueId, [
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Get attribute combination as sorted array for comparison
     *
     * @return array
     */
    public function getAttributeCombination()
    {
        return $this->attributeValues()
            ->orderBy('attribute_id')
            ->pluck('attribute_values.id', 'attribute_id')
            ->toArray();
    }

    /**
     * Validate that attribute combination is unique within the product
     *
     * @param array $attributeValues
     * @return void
     * @throws \Exception
     */
    protected function validateUniqueAttributeCombination(array $attributeValues)
    {
        // Sort by attribute_id for consistent comparison
        ksort($attributeValues);

        // Check against parent product's attributes
        $productAttributes = $this->product->attributeValues()
            ->orderBy('attribute_id')
            ->pluck('attribute_values.id', 'attribute_id')
            ->toArray();

        if ($productAttributes === $attributeValues) {
            throw new \Exception('Variant cannot have the same attribute combination as the parent product');
        }

        // Check against other variants of the same product
        $existingVariants = $this->product->variants()
            ->where('id', '!=', $this->id) // Exclude current variant for updates
            ->with('attributeValues')
            ->get();

        foreach ($existingVariants as $variant) {
            $variantAttributes = $variant->attributeValues()
                ->orderBy('attribute_id')
                ->pluck('attribute_values.id', 'attribute_id')
                ->toArray();

            if ($variantAttributes === $attributeValues) {
                throw new \Exception("Variant cannot have the same attribute combination as variant '{$variant->name}'");
            }
        }
    }

    // ===== INVENTORY MANAGEMENT METHODS =====

    /**
     * Check if variant is in stock
     *
     * @param int $quantity
     * @return bool
     */
    public function isInStock($quantity = 1)
    {
        return !$this->manage_stock || $this->stock_quantity >= $quantity;
    }

    /**
     * Adjust stock quantity (can be positive or negative) - Enhanced version
     *
     * @param int $quantity
     * @param string $reason
     * @return bool
     */
    public function adjustStock($quantity, $reason = 'Manual adjustment')
    {
        if (!$this->manage_stock) return true;

        $previousStock = $this->stock_quantity;
        $this->stock_quantity += $quantity;
        $this->save();

        // Log stock movement for audit trail
        \Log::info('Variant stock adjusted', [
            'variant_id' => $this->id,
            'product_id' => $this->product_id,
            'previous_stock' => $previousStock,
            'adjustment' => $quantity,
            'new_stock' => $this->stock_quantity,
            'reason' => $reason
        ]);

        return true;
    }

    /**
     * Reduce stock quantity (for sales)
     *
     * @param int $quantity
     * @return bool
     */
    public function reduceStock($quantity)
    {
        if (!$this->manage_stock) return true;

        if ($this->stock_quantity < $quantity) {
            return false; // Not enough stock
        }

        $this->stock_quantity -= $quantity;
        $this->save();

        return true;
    }

    /**
     * Check if variant is out of stock
     *
     * @return bool
     */
    public function isOutOfStock()
    {
        return $this->manage_stock && $this->stock_quantity <= 0;
    }

    /**
     * Decrease the stock of the variant
     * Note: Currently a placeholder as we're not tracking stock yet
     *
     * @param int $quantity
     * @return bool
     */
    public function decreaseStock($quantity)
    {
        // We'll implement proper stock tracking in a future update
        return true;
    }
}
