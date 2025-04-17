<?php

namespace App\Events\Product;

use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductUpdated
{
    use Dispatchable, SerializesModels;

    public $product;
    public $changedFields;

    /**
     * Create a new event instance.
     *
     * @param Product $product
     * @param array $changedFields
     */
    public function __construct(Product $product, array $changedFields = [])
    {
        $this->product = $product;
        $this->changedFields = $changedFields;
    }
}
