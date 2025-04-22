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
        'price'
        // We'll add 'sku' and 'stock' in a future update
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

    /**
     * Check if the variant is in stock
     * Note: Currently always returns true as we're not tracking stock yet
     *
     * @return bool
     */
    public function isInStock()
    {
        // We'll implement proper stock tracking in a future update
        return true;
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
