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
            'original_price' => $this->when(isset($this->original_price), $this->original_price),
            'discounted_price' => $this->when(isset($this->discounted_price), $this->discounted_price ?? $this->price),
            'discount_amount' => $this->when(isset($this->discount_amount), $this->discount_amount),
            'has_active_discount' => $this->when(isset($this->product) && method_exists($this->product, 'hasActiveDiscount'), $this->product->hasActiveDiscount()),
            'discount_type' => $this->when(isset($this->product) && isset($this->product->discount_type), $this->product->discount_type),
            'discount_value' => $this->when(isset($this->product) && isset($this->product->discount_value), $this->product->discount_value),
            'discount_start' => $this->when(isset($this->product) && isset($this->product->discount_start), $this->product->discount_start),
            'discount_end' => $this->when(isset($this->product) && isset($this->product->discount_end), $this->product->discount_end),
            'subtotal' => $this->when(isset($this->subtotal), $this->subtotal),
            'is_available' => $this->when(isset($this->is_available), $this->is_available),
            'stock_status' => $this->when(isset($this->stock_status), $this->stock_status),
            'max_quantity' => $this->when(isset($this->max_quantity), $this->max_quantity),
        ];
    }
}
