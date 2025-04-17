<?php

namespace App\Events\Product;

use App\Models\Product;
use App\Models\Subcategory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubcategoryChanged
{
    use Dispatchable, SerializesModels;

    public $product;
    public $oldSubcategoryId;
    public $newSubcategoryId;
    public $oldSubcategoryName;
    public $newSubcategoryName;
    public $keptAttributeIds;
    public $removedAttributeIds;

    /**
     * Create a new event instance.
     *
     * @param Product $product
     * @param int $oldSubcategoryId
     * @param int $newSubcategoryId
     * @param array $keptAttributeIds
     * @param array $removedAttributeIds
     * @param string|null $oldSubcategoryName
     * @param string|null $newSubcategoryName
     */
    public function __construct(
        Product $product, 
        int $oldSubcategoryId, 
        int $newSubcategoryId,
        array $keptAttributeIds = [],
        array $removedAttributeIds = [],
        ?string $oldSubcategoryName = null,
        ?string $newSubcategoryName = null
    ) {
        $this->product = $product;
        $this->oldSubcategoryId = $oldSubcategoryId;
        $this->newSubcategoryId = $newSubcategoryId;
        $this->keptAttributeIds = $keptAttributeIds;
        $this->removedAttributeIds = $removedAttributeIds;
        $this->oldSubcategoryName = $oldSubcategoryName;
        $this->newSubcategoryName = $newSubcategoryName;
        
        // If names weren't provided, try to fetch them
        if ($oldSubcategoryName === null || $newSubcategoryName === null) {
            $this->loadNames();
        }
    }
    
    /**
     * Load subcategory names if not provided
     */
    protected function loadNames(): void
    {
        if ($this->oldSubcategoryName === null) {
            $oldSubcategory = Subcategory::find($this->oldSubcategoryId);
            $this->oldSubcategoryName = $oldSubcategory ? $oldSubcategory->name : 'Unknown';
        }
        
        if ($this->newSubcategoryName === null) {
            $newSubcategory = Subcategory::find($this->newSubcategoryId);
            $this->newSubcategoryName = $newSubcategory ? $newSubcategory->name : 'Unknown';
        }
    }
}
