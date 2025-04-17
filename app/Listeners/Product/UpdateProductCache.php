<?php

namespace App\Listeners\Product;

use App\Events\Product\ProductCreated;
use App\Events\Product\ProductUpdated;
use App\Events\Product\AttributeUpdated;
use App\Events\Product\SubcategoryChanged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UpdateProductCache
{
    /**
     * Handle the event.
     *
     * @param mixed $event
     * @return void
     */
    public function __invoke($event)
    {
        // Dispatch to the appropriate handler based on event type
        if ($event instanceof ProductCreated) {
            $this->handleProductCreated($event);
        } elseif ($event instanceof ProductUpdated) {
            $this->handleProductUpdated($event);
        } elseif ($event instanceof AttributeUpdated) {
            $this->handleAttributeUpdated($event);
        } elseif ($event instanceof SubcategoryChanged) {
            $this->handleSubcategoryChanged($event);
        }
    }

    /**
     * Handle product created event.
     *
     * @param ProductCreated $event
     * @return void
     */
    protected function handleProductCreated(ProductCreated $event)
    {
        $this->clearProductCache($event->product->id);
        $this->clearCategoryCache($event->product->subcategory_id);

        Log::info('Product cache cleared after creation', [
            'product_id' => $event->product->id
        ]);
    }

    /**
     * Handle product updated event.
     *
     * @param ProductUpdated $event
     * @return void
     */
    public function handleProductUpdated(ProductUpdated $event)
    {
        $this->clearProductCache($event->product->id);
        $this->clearCategoryCache($event->product->subcategory_id);

        Log::info('Product cache cleared after update', [
            'product_id' => $event->product->id,
            'changed_fields' => $event->changedFields
        ]);
    }

    /**
     * Handle attribute updated event.
     *
     * @param AttributeUpdated $event
     * @return void
     */
    public function handleAttributeUpdated(AttributeUpdated $event)
    {
        $this->clearProductCache($event->product->id);

        Log::info('Product cache cleared after attribute update', [
            'product_id' => $event->product->id,
            'attribute_id' => $event->attributeId
        ]);
    }

    /**
     * Handle subcategory changed event.
     *
     * @param SubcategoryChanged $event
     * @return void
     */
    public function handleSubcategoryChanged(SubcategoryChanged $event)
    {
        $this->clearProductCache($event->product->id);
        $this->clearCategoryCache($event->oldSubcategoryId);
        $this->clearCategoryCache($event->newSubcategoryId);

        Log::info('Product and category caches cleared after subcategory change', [
            'product_id' => $event->product->id,
            'old_subcategory_id' => $event->oldSubcategoryId,
            'new_subcategory_id' => $event->newSubcategoryId
        ]);
    }

    /**
     * Clear product-related cache
     *
     * @param int $productId
     * @return void
     */
    protected function clearProductCache(int $productId): void
    {
        Cache::forget("product_{$productId}");
        Cache::forget("product_attributes_{$productId}");
        Cache::forget("product_images_{$productId}");
    }

    /**
     * Clear category-related cache
     *
     * @param int $subcategoryId
     * @return void
     */
    protected function clearCategoryCache(int $subcategoryId): void
    {
        Cache::forget("subcategory_{$subcategoryId}");
        Cache::forget("subcategory_products_{$subcategoryId}");
        Cache::forget("subcategory_attributes_{$subcategoryId}");
    }
}
