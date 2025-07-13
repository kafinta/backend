<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            // 'user_id' => $this->user_id, // Remove user_id from response
            'seller_name' => optional($this->user && $this->user->relationLoaded('seller') ? $this->user->seller : ($this->user->seller ?? null))->business_name,
            'status' => $this->status,
            'denial_reason' => $this->when($this->denial_reason, $this->denial_reason),
            'is_featured' => $this->is_featured,
            'stock_quantity' => $this->stock_quantity,
            'manage_stock' => $this->manage_stock,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Inventory information
            'is_in_stock' => $this->isInStock(),
            'is_out_of_stock' => $this->isOutOfStock(),

            // Relationships (always include as objects)
            'subcategory' => new SubcategoryResource($this->subcategory),
            'category' => new CategoryResource($this->category),
            'location' => new LocationResource($this->location),
            'seller_name' => $this->when(
                isset($this->seller_name) && auth()->id() !== $this->user_id,
                $this->seller_name
            ),

            // Images
            'images' => ImageResource::collection($this->whenLoaded('images')),

            // Attributes - show selected attribute values for this product
            'attributes' => $this->when($this->relationLoaded('attributeValues'), function () {
                return $this->attributeValues->map(function ($attributeValue) {
                    return [
                        'id' => $attributeValue->attribute->id,
                        'name' => $attributeValue->attribute->name,
                        'value' => [
                            'id' => $attributeValue->id,
                            'name' => $attributeValue->name,
                            'representation' => $attributeValue->representation,
                        ]
                    ];
                });
            }),

            // Progress tracking for draft products
            'completion_status' => $this->when($this->status === 'draft', function () {
                return $this->getCompletionStatus();
            }),
            'next_step' => $this->when($this->status === 'draft', function () {
                return $this->getNextStep();
            }),
            'progress_percentage' => $this->when($this->status === 'draft', function () {
                return $this->getProgressPercentage();
            }),

            // Analytics for active products
            'analytics' => $this->when($this->status === 'active', function () {
                return [
                    'views' => $this->views ?? 0,
                    'orders' => $this->orders_count ?? 0,
                    'revenue' => $this->total_revenue ?? '0.00',
                    'conversion_rate' => $this->getConversionRate(),
                ];
            }),
        ];
    }
}
