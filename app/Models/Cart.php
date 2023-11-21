<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = ['user_id'];

    /**
     * Define the relationship with cart items.
     */
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
    
    use HasFactory;
}
