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
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'denial_reason' => $this->when($this->denial_reason, $this->denial_reason),

            // Inventory information
            'manage_stock' => $this->manage_stock,
            'stock_quantity' => $this->stock_quantity,
            'is_in_stock' => $this->isInStock(),
            'is_out_of_stock' => $this->isOutOfStock(),

            // Relationships
            'subcategory' => new SubcategoryResource($this->whenLoaded('subcategory')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'seller_name' => $this->when(isset($this->seller_name), $this->seller_name),

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
        ];
    }
}
