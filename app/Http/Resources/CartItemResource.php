<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
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
            'product' => $this->when($this->relationLoaded('product') && $this->product, function () {
                return new ProductResource($this->product);
            }),
            'variant' => $this->when($this->relationLoaded('variant') && $this->variant, function () {
                return new VariantResource($this->variant);
            }),
            'price' => $this->when(isset($this->price), $this->price),
            'subtotal' => $this->when(isset($this->subtotal), $this->subtotal),
            'is_available' => $this->when(isset($this->is_available), $this->is_available),
            'stock_status' => $this->when(isset($this->stock_status), $this->stock_status),
            'max_quantity' => $this->when(isset($this->max_quantity), $this->max_quantity),
        ];
    }
}
