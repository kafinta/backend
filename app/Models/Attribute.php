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

    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function subcategories()
    {
        return $this->belongsToMany(Subcategory::class, 'subcategory_attributes')
            ->withPivot('is_required', 'display_order')
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
            throw new \InvalidArgumentException(
                "Invalid values for subcategory: " . $invalidValues->implode(', ')
            );
        }

        return true;
    }
}
