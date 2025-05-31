<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutResource extends JsonResource
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
            // Order totals
            'subtotal' => $this->when(isset($this->subtotal), $this->subtotal),
            'tax' => $this->when(isset($this->tax), $this->tax),
            'shipping' => $this->when(isset($this->shipping), $this->shipping),
            'total' => $this->when(isset($this->total), $this->total),
            
            // Cart items for checkout
            'items' => $this->when(isset($this->items), function () {
                return CartItemResource::collection(collect($this->items));
            }),
            
            // Checkout metadata
            'item_count' => $this->when(isset($this->item_count), $this->item_count),
            'total_quantity' => $this->when(isset($this->total_quantity), $this->total_quantity),
            
            // Available options
            'shipping_methods' => $this->when(isset($this->shipping_methods), $this->shipping_methods),
            'payment_methods' => $this->when(isset($this->payment_methods), $this->payment_methods),
            
            // Validation info
            'can_checkout' => $this->when(isset($this->can_checkout), $this->can_checkout),
            'checkout_errors' => $this->when(isset($this->checkout_errors), $this->checkout_errors),
            
            // Order creation result
            'order' => $this->when(isset($this->order), function () {
                return new OrderResource($this->order);
            }),
        ];
    }
}
