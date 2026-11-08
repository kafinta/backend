<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->order_number,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'currency' => $this->currency,
            'shipping_method' => $this->shipping_method,
            'is_paid' => $this->isPaid(),
            'paid_at' => $this->paid_at,
            'shipped_at' => $this->shipped_at,
            'delivered_at' => $this->delivered_at,
            'cancelled_at' => $this->cancelled_at,
            'created_at' => $this->created_at,
            'customer' => [
                'name' => $this->shipping_name,
                'phone' => $this->shipping_phone,
            ],
            'shipping' => [
                'name' => $this->shipping_name,
                'address' => $this->shipping_address,
                'city' => $this->shipping_city,
                'state' => $this->shipping_state,
                'postal_code' => $this->shipping_postal_code,
                'country' => $this->shipping_country,
                'phone' => $this->shipping_phone,
            ],
            'notes' => $this->notes,
            'items' => SellerOrderItemResource::collection($this->whenLoaded('orderItems')),
            'seller_subtotal' => $this->when($this->relationLoaded('orderItems'), function () {
                return $this->orderItems->sum('subtotal');
            }),
            'item_count' => $this->when($this->relationLoaded('orderItems'), function () {
                return $this->orderItems->count();
            }),
            'total_quantity' => $this->when($this->relationLoaded('orderItems'), function () {
                return $this->orderItems->sum('quantity');
            }),
        ];
    }
}
