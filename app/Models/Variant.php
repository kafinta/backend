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
