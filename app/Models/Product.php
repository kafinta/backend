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
        'denial_reason',
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

        // Validation is already done in the controller, so we can directly sync the values
        // Sync the attribute values (this will replace all existing values)
        $this->attributeValues()->sync($attributeValues);

        \Log::info('Successfully synced attribute values', [
            'product_id' => $this->id,
            'synced_values' => $attributeValues
        ]);

        // TODO: Variant generation will be implemented in the next phase
        // For now, we're focusing on basic product creation without variants
        \Log::info('Attribute values set successfully. Variant generation disabled for now.', [
            'product_id' => $this->id
        ]);

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
     * Scope for paused products
     */
    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    /**
     * Scope for denied products
     */
    public function scopeDenied($query)
    {
        return $query->where('status', 'denied');
    }

    /**
     * Scope for out of stock products
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('status', 'out_of_stock');
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

        // Auto-restore status if stock was replenished
        $previousStatus = $this->status;
        if ($previousStock <= 0 && $this->stock_quantity > 0 && $this->status === 'out_of_stock') {
            $this->status = 'active'; // Restore to active when stock replenished
        }

        // Auto-set out_of_stock if stock depleted
        if ($this->stock_quantity <= 0 && $this->status === 'active') {
            $this->status = 'out_of_stock';
        }

        $this->save();

        // Log stock movement for audit trail
        \Log::info('Stock adjusted', [
            'product_id' => $this->id,
            'previous_stock' => $previousStock,
            'adjustment' => $quantity,
            'new_stock' => $this->stock_quantity,
            'reason' => $reason,
            'status_changed' => $previousStatus !== $this->status ? "from {$previousStatus} to {$this->status}" : 'no change'
        ]);

        return true;
    }

    /**
     * Reduce stock quantity (for sales) - Enhanced version
     *
     * @param int $quantity
     * @param string $reason
     * @return bool
     * @throws \Exception
     */
    public function reduceStock($quantity, $reason = 'Order fulfillment')
    {
        if (!$this->manage_stock) return true;

        if ($this->stock_quantity < $quantity) {
            throw new \Exception(
                "Insufficient stock. Available: {$this->stock_quantity}, Requested: {$quantity}"
            );
        }

        $previousStock = $this->stock_quantity;
        $this->stock_quantity -= $quantity;

        // Auto-set out_of_stock status if stock reaches 0
        $previousStatus = $this->status;
        if ($this->stock_quantity <= 0 && $this->status === 'active') {
            $this->status = 'out_of_stock';
        }

        $this->save();

        // Log stock movement for audit trail
        \Log::info('Stock reduced', [
            'product_id' => $this->id,
            'previous_stock' => $previousStock,
            'quantity_reduced' => $quantity,
            'new_stock' => $this->stock_quantity,
            'reason' => $reason,
            'status_changed' => $previousStatus !== $this->status ? "from {$previousStatus} to {$this->status}" : 'no change'
        ]);

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

    /**
     * Get completion status for draft products
     */
    public function getCompletionStatus()
    {
        return [
            'basic_info' => $this->hasBasicInfo(),
            'attributes' => $this->hasAttributes(),
            'images' => $this->hasImages(),
        ];
    }

    /**
     * Get next step for draft products
     */
    public function getNextStep()
    {
        if (!$this->hasBasicInfo()) {
            return 'basic_info';
        }

        if (!$this->hasAttributes()) {
            return 'attributes';
        }

        if (!$this->hasImages()) {
            return 'images';
        }

        return 'publish';
    }

    /**
     * Get progress percentage for draft products
     */
    public function getProgressPercentage()
    {
        $completionStatus = $this->getCompletionStatus();
        $completedSteps = array_sum($completionStatus);
        $totalSteps = count($completionStatus);

        return round(($completedSteps / $totalSteps) * 100);
    }

    /**
     * Check if product has basic info
     */
    public function hasBasicInfo()
    {
        return !empty($this->name) &&
               !empty($this->description) &&
               !empty($this->price) &&
               !empty($this->subcategory_id);
    }

    /**
     * Check if product has attributes
     */
    public function hasAttributes()
    {
        // Load attribute values if not already loaded
        if (!$this->relationLoaded('attributeValues')) {
            $this->load('attributeValues');
        }

        return $this->attributeValues->count() > 0;
    }

    /**
     * Check if product has images
     */
    public function hasImages()
    {
        // Load images if not already loaded
        if (!$this->relationLoaded('images')) {
            $this->load('images');
        }

        return $this->images->count() > 0;
    }

    /**
     * Get conversion rate for active products
     */
    public function getConversionRate()
    {
        $views = $this->views ?? 0;
        $orders = $this->orders_count ?? 0;

        if ($views === 0) {
            return '0.00';
        }

        return number_format(($orders / $views) * 100, 2);
    }
}
