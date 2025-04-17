<?php

namespace App\Events\Product;

use App\Models\Product;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttributeRemoved
{
    use Dispatchable, SerializesModels;

    public $product;
    public $attributeId;
    public $valueId;
    public $attributeName;
    public $valueName;

    /**
     * Create a new event instance.
     *
     * @param Product $product
     * @param int $attributeId
     * @param int|null $valueId
     * @param string|null $attributeName
     * @param string|null $valueName
     */
    public function __construct(
        Product $product, 
        int $attributeId, 
        ?int $valueId = null,
        ?string $attributeName = null,
        ?string $valueName = null
    ) {
        $this->product = $product;
        $this->attributeId = $attributeId;
        $this->valueId = $valueId;
        $this->attributeName = $attributeName;
        $this->valueName = $valueName;
        
        // If names weren't provided, try to fetch them
        if ($attributeName === null || ($valueId && $valueName === null)) {
            $this->loadNames();
        }
    }
    
    /**
     * Load attribute and value names if not provided
     */
    protected function loadNames(): void
    {
        if ($this->attributeName === null) {
            $attribute = Attribute::find($this->attributeId);
            $this->attributeName = $attribute ? $attribute->name : 'Unknown';
        }
        
        if ($this->valueId && $this->valueName === null) {
            $value = AttributeValue::find($this->valueId);
            $this->valueName = $value ? $value->name : 'Unknown';
        }
    }
}
