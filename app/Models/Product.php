<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'subcategory_id',
        'user_id'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        // 'is_active' => 'boolean',
    ];

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
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
}
