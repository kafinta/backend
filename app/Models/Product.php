<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'subcategory_id',
        'user_id',
        'location_id',
        'status',
        'is_featured',
        // Inventory fields
        'stock_quantity',
        'manage_stock'
    ];

    /**
     * Get the validation rules for product creation/update
     *
     * @param int|null $productId ID of product being updated (null for creation)
     * @return array
     */
    public static function getValidationRules(?int $productId = null): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique per user, ignore current product if updating
                Rule::unique('products')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                })->ignore($productId)
            ],
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'subcategory_id' => 'required|exists:subcategories,id',
            'location_id' => 'nullable|exists:locations,id'
        ];
    }

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'manage_stock' => 'boolean',
    ];

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    /**
     * Get the category of the product through the subcategory relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
     */
    public function category()
    {
        return $this->hasOneThrough(
            \App\Models\Category::class,
            \App\Models\Subcategory::class,
            'id', // Foreign key on subcategories table
            'id', // Foreign key on categories table
            'subcategory_id', // Local key on products table
            'category_id' // Local key on subcategories table
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'product_attribute_values')
            ->withTimestamps();
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes')
            ->withTimestamps();
    }

    public function syncAttributesFromSubcategory()
    {
        $subcategoryAttributes = $this->subcategory->attributes()
            ->select('attributes.*')
            ->with(['values' => function($query) {
                $query->select('attribute_values.*');
            }])
            ->get();

        $this->attributes()->sync($subcategoryAttributes->pluck('id'));

        return $this;
    }

    public function setAttributeValues(array $attributeValues)
    {
        \Log::info('Setting attribute values', [
            'product_id' => $this->id,
            'subcategory_id' => $this->subcategory_id,
            'attribute_values' => $attributeValues
        ]);

        foreach ($attributeValues as $attributeId => $valueId) {
            // Verify the attribute belongs to the product's subcategory
            $attribute = $this->subcategory->attributes()
                ->select('attributes.*')
                ->where('attributes.id', $attributeId)
                ->first();

            if (!$attribute) {
                throw new \InvalidArgumentException("Invalid attribute ID: {$attributeId} for subcategory {$this->subcategory_id}");
            }

            // Log before validation
            \Log::info('Validating attribute value', [
                'attribute_id' => $attributeId,
                'value_id' => $valueId,
                'subcategory_id' => $this->subcategory_id
            ]);

            // Verify the value belongs to the attribute and is valid for the subcategory
            $attribute->validateValuesForSubcategory($this->subcategory, [$valueId]);
        }

        // Attach the values
        $this->attributeValues()->sync($attributeValues);

        // If any variant-generating attributes were updated, regenerate variants
        $variantAttributeIds = $this->attributes()
            ->where('is_variant_generator', true)
            ->pluck('attributes.id');

        if (!empty(array_intersect($variantAttributeIds->toArray(), array_keys($attributeValues)))) {
            $this->generateVariants();
        }

        return $this;
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for featured products
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for draft products
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for products by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if product is publishable
     */
    public function isPublishable()
    {
        return $this->name &&
               $this->description &&
               $this->price > 0 &&
               $this->subcategory_id &&
               $this->images()->exists();
    }

    public function generateVariants()
    {
        // Find variant-generating attributes for this product's subcategory
        $variantAttributes = $this->subcategory
            ->attributes()
            ->where('is_variant_generator', true)
            ->with('values')
            ->get();

        // Generate all possible combinations
        $combinations = $this->generateAttributeCombinations($variantAttributes);

        $generatedVariants = [];
        foreach ($combinations as $combination) {
            $variant = $this->createVariantFromCombination($combination);
            $generatedVariants[] = $variant;
        }

        return $generatedVariants;
    }

    private function generateAttributeCombinations($variantAttributes)
    {
        if ($variantAttributes->isEmpty()) {
            return [];
        }

        // Start with first attribute's values
        $combinations = $variantAttributes->first()->values;

        // Progressively combine with other attributes
        $variantAttributes->slice(1)->each(function ($attribute) use (&$combinations) {
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

        return $combinations;
    }

    private function createVariantFromCombination($combination)
    {
        // Calculate variant-specific price
        $basePrice = $this->price;
        $priceAdjustment = collect($combination)
            ->sum('price_adjustment');

        // Generate unique SKU
        $skuModifiers = collect($combination)
            ->map(fn($value) => $value->sku_modifier ?? $value->value)
            ->implode('-');

        $sku = "{$this->id}-{$skuModifiers}";

        // Create variant
        $variant = $this->variants()->create([
            'name' => $this->generateVariantName($combination),
            'sku' => $sku,
            'price' => $basePrice + $priceAdjustment,
            'stock' => $this->calculateVariantStock($combination)
        ]);

        // Attach attribute values
        $variant->attributeValues()->attach(
            collect($combination)->pluck('id')
        );

        return $variant;
    }

    private function generateVariantName($combination)
    {
        return collect($combination)
            ->map(fn($value) => $value->display_value ?? $value->value)
            ->implode(' ');
    }

    private function calculateVariantStock($combination)
    {
        // Calculate stock based on value stock adjustments
        $stockAdjustment = collect($combination)
            ->sum('stock_adjustment');

        // Use a default stock or minimum stock from values
        return max(0, $stockAdjustment ?? $this->default_stock);
    }

    // ===== INVENTORY MANAGEMENT METHODS =====

    /**
     * Check if product is in stock
     *
     * @param int $quantity
     * @return bool
     */
    public function isInStock($quantity = 1)
    {
        return !$this->manage_stock || $this->stock_quantity >= $quantity;
    }

    /**
     * Adjust stock quantity (can be positive or negative)
     *
     * @param int $quantity
     * @return bool
     */
    public function adjustStock($quantity)
    {
        if (!$this->manage_stock) return true;

        $this->stock_quantity += $quantity;
        $this->save();

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
     * Check if product is out of stock
     *
     * @return bool
     */
    public function isOutOfStock()
    {
        return $this->manage_stock && $this->stock_quantity <= 0;
    }
}
