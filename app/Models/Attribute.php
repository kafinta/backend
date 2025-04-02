<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'type', 
        'is_variant_generator', 
        'is_required', 
        'display_order'
    ];

    protected $casts = [
        'is_variant_generator' => 'boolean'
    ];

    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function subcategories()
    {
        return $this->belongsToMany(Subcategory::class, 'subcategory_attributes')
            ->withTimestamps();
    }

    public function scopeVariantGenerators($query)
    {
        return $query->where('is_variant_generator', true);
    }

    public function getCachedValuesForSubcategory(Subcategory $subcategory)
    {
        $cacheKey = "attribute_{$this->id}_subcategory_{$subcategory->id}_values";
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($subcategory) {
            return $this->values()
                ->whereHas('subcategories', function ($query) use ($subcategory) {
                    $query->where('subcategories.id', $subcategory->id);
                })
                ->get();
        });
    }

    public function validateValuesForSubcategory(Subcategory $subcategory, array $valueIds)
    {
        $validValues = $this->getCachedValuesForSubcategory($subcategory);
        
        $invalidValues = collect($valueIds)->diff($validValues->pluck('id'));
        
        if ($invalidValues->isNotEmpty()) {
            \Log::error('Invalid attribute values', [
                'subcategory_id' => $subcategory->id,
                'attribute_id' => $this->id,
                'invalid_values' => $invalidValues->toArray(),
                'valid_values' => $validValues->pluck('id')->toArray()
            ]);
            
            throw new \InvalidArgumentException(
                "Invalid values for subcategory. Attribute: {$this->name}, Invalid values: " . $invalidValues->implode(', ')
            );
        }

        return true;
    }
}
