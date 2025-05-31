<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'subtotal' => $this->subtotal,
            'status' => $this->status,
            
            // Product information (stored at time of order)
            'product_name' => $this->product_name,
            'variant_name' => $this->variant_name,
            
            // Current product/variant data (if still exists)
            'product' => $this->when($this->relationLoaded('product') && $this->product, function () {
                return new ProductResource($this->product);
            }),
            'variant' => $this->when($this->relationLoaded('variant') && $this->variant, function () {
                return new VariantResource($this->variant);
            }),
            
            // Status timestamps
            'shipped_at' => $this->shipped_at,
            'delivered_at' => $this->delivered_at,
            'cancelled_at' => $this->cancelled_at,
            
            // Computed fields
            'is_shipped' => $this->shipped_at !== null,
            'is_delivered' => $this->delivered_at !== null,
            'is_cancelled' => $this->cancelled_at !== null,
            'seller_info' => $this->when(isset($this->seller_info), $this->seller_info),
        ];
    }
}
