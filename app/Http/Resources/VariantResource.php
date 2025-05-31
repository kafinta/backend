<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VariantResource extends JsonResource
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
            'price' => $this->price,
            'manage_stock' => $this->manage_stock,
            'stock_quantity' => $this->stock_quantity,
            'is_in_stock' => $this->isInStock(),
            'product_id' => $this->product_id,
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
            'images' => ImageResource::collection($this->whenLoaded('images')),
        ];
    }
}
