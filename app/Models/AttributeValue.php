<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'attribute_id', 
        'name', 
        'representation'
    ];

    protected $casts = [
        'representation' => 'array'
    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_attribute_values')
            ->withTimestamps();
    }

    public function variants()
    {
        return $this->belongsToMany(Variant::class, 'variant_attribute_values')
            ->withTimestamps();
    }

    public function subcategories()
    {
        return $this->belongsToMany(Subcategory::class, 'subcategory_attribute_values')
            ->withPivot('attribute_id');
    }

    public function getParsedRepresentationAttribute()
    {
        if (empty($this->representation)) {
            return [
                'type' => 'text',
                'value' => $this->name
            ];
        }
        
        return $this->representation;
    }

    // Specific accessors for common use cases
    public function getColorHexAttribute()
    {
        return $this->representation['hex'] ?? null;
    }

    public function getImagePathAttribute()
    {
        return $this->representation['image_path'] ?? null;
    }
}
