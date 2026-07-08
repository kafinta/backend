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
        $product = $this->relationLoaded('product') ? $this->product : null;
        $variant = $this->relationLoaded('variant') ? $this->variant : null;

        $price = $this->price ?? ($variant ? $variant->price : optional($product)->price);
        $originalPrice = $this->original_price ?? ($variant ? $variant->price : optional($product)->price);
        $discountAmount = $this->discount_amount ?? (!$variant && $product && method_exists($product, 'hasActiveDiscount') && $product->hasActiveDiscount() ? $product->getDiscountAmount() : 0);
        $discountedPrice = $this->discounted_price ?? ($variant ? $variant->price : ($product && method_exists($product, 'getDiscountedPrice') ? $product->getDiscountedPrice() : $price));
        $subtotal = $this->subtotal ?? ($price * $this->quantity);

        return [
            'id' => $this->id,
            'cart_id' => $this->cart_id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'quantity' => $this->quantity,
            'price' => $price,
            'original_price' => $originalPrice,
            'discounted_price' => $discountedPrice,
            'discount_amount' => $discountAmount,
            'subtotal' => $subtotal,
            'has_active_discount' => $product && method_exists($product, 'hasActiveDiscount') ? $product->hasActiveDiscount() : false,
            'discount_type' => optional($product)->discount_type,
            'discount_value' => optional($product)->discount_value,
            'discount_start' => optional($product)->discount_start,
            'discount_end' => optional($product)->discount_end,
            'product' => $this->when($product, function () use ($product) {
                return new ProductResource($product);
            }),
            'variant' => $this->when($variant, function () use ($variant) {
                return new VariantResource($variant);
            }),
            'is_available' => $this->when(isset($this->is_available), $this->is_available),
            'stock_status' => $this->when(isset($this->stock_status), $this->stock_status),
            'max_quantity' => $this->when(isset($this->max_quantity), $this->max_quantity),
        ];
    }
}
