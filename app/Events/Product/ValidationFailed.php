<?php

namespace App\Events\Product;

use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ValidationFailed
{
    use Dispatchable, SerializesModels;

    public $product;
    public $context;
    public $errors;
    public $data;

    /**
     * Create a new event instance.
     *
     * @param Product|null $product
     * @param string $context
     * @param array $errors
     * @param array $data
     */
    public function __construct(
        ?Product $product, 
        string $context, 
        array $errors, 
        array $data = []
    ) {
        $this->product = $product;
        $this->context = $context;
        $this->errors = $errors;
        $this->data = $data;
    }
}
