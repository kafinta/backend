<?php

namespace App\Listeners\Product;

use App\Events\Product\SubcategoryChanged;
use App\Models\Attribute;
use App\Models\Subcategory;
use Illuminate\Support\Facades\Log;

class HandleAttributesForSubcategoryChange
{
    /**
     * Handle the event.
     *
     * @param SubcategoryChanged $event
     * @return void
     */
    public function __invoke(SubcategoryChanged $event)
    {
        // This listener can be used for additional processing after a subcategory change
        // For example, you might want to check if there are any required attributes
        // in the new subcategory that need to be added to the product

        $newSubcategory = Subcategory::with(['attributes' => function($query) {
            $query->wherePivot('is_required', true);
        }])->find($event->newSubcategoryId);

        if (!$newSubcategory) {
            Log::warning('Could not find new subcategory for attribute handling', [
                'product_id' => $event->product->id,
                'subcategory_id' => $event->newSubcategoryId
            ]);
            return;
        }

        $requiredAttributes = $newSubcategory->attributes;
        $existingAttributeIds = $event->product->attributeValues()
            ->pluck('attribute_id')
            ->toArray();

        $missingRequiredAttributes = $requiredAttributes->filter(function($attribute) use ($existingAttributeIds) {
            return !in_array($attribute->id, $existingAttributeIds);
        });

        if ($missingRequiredAttributes->isNotEmpty()) {
            Log::warning('Product is missing required attributes after subcategory change', [
                'product_id' => $event->product->id,
                'subcategory_id' => $event->newSubcategoryId,
                'missing_attributes' => $missingRequiredAttributes->pluck('name')->toArray()
            ]);
        }
    }
}
