<?php

namespace App\Events\Product;

use App\Models\Product;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttributeUpdated
{
    use Dispatchable, SerializesModels;

    public $product;
    public $attributeId;
    public $oldValueId;
    public $newValueId;
    public $attributeName;
    public $oldValueName;
    public $newValueName;

    /**
     * Create a new event instance.
     *
     * @param Product $product
     * @param int $attributeId
     * @param int|null $oldValueId
     * @param int $newValueId
     * @param string|null $attributeName
     * @param string|null $oldValueName
     * @param string|null $newValueName
     */
    public function __construct(
        Product $product, 
        int $attributeId, 
        ?int $oldValueId, 
        int $newValueId,
        ?string $attributeName = null,
        ?string $oldValueName = null,
        ?string $newValueName = null
    ) {
        $this->product = $product;
        $this->attributeId = $attributeId;
        $this->oldValueId = $oldValueId;
        $this->newValueId = $newValueId;
        $this->attributeName = $attributeName;
        $this->oldValueName = $oldValueName;
        $this->newValueName = $newValueName;
        
        // If names weren't provided, try to fetch them
        if ($attributeName === null || $oldValueName === null || $newValueName === null) {
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
        
        if ($this->oldValueId && $this->oldValueName === null) {
            $oldValue = AttributeValue::find($this->oldValueId);
            $this->oldValueName = $oldValue ? $oldValue->name : 'Unknown';
        }
        
        if ($this->newValueName === null) {
            $newValue = AttributeValue::find($this->newValueId);
            $this->newValueName = $newValue ? $newValue->name : 'Unknown';
        }
    }
}
