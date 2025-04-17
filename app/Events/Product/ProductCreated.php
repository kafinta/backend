<?php

namespace App\Events\Product;

use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductCreated
{
    use Dispatchable, SerializesModels;

    public $product;
    public $attributes;
    public $images;

    /**
     * Create a new event instance.
     *
     * @param Product $product
     * @param array $attributes
     * @param array $images
     */
    public function __construct(Product $product, array $attributes = [], array $images = [])
    {
        $this->product = $product;
        $this->attributes = $attributes;
        $this->images = $images;
    }
}
