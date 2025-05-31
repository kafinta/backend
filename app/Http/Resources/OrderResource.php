<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'shipping_cost' => $this->shipping_cost,
            'total' => $this->total,
            
            // Shipping information
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
            
            // Status indicators
            'is_paid' => $this->when(method_exists($this->resource, 'isPaid'), $this->isPaid()),
            'is_shipped' => $this->when(method_exists($this->resource, 'isShipped'), $this->isShipped()),
            'is_delivered' => $this->when(method_exists($this->resource, 'isDelivered'), $this->isDelivered()),
            'is_cancelled' => $this->when(method_exists($this->resource, 'isCancelled'), $this->isCancelled()),
            
            // Timestamps
            'paid_at' => $this->paid_at,
            'shipped_at' => $this->shipped_at,
            'delivered_at' => $this->delivered_at,
            'cancelled_at' => $this->cancelled_at,
            'created_at' => $this->created_at,
            
            // Relationships
            'user' => $this->when($this->relationLoaded('user'), function () {
                return new UserAccountResource($this->user);
            }),
            'items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            
            // Computed fields
            'item_count' => $this->when(isset($this->item_count), $this->item_count),
            'total_quantity' => $this->when(isset($this->total_quantity), $this->total_quantity),
            'can_cancel' => $this->when(isset($this->can_cancel), $this->can_cancel),
            'tracking_info' => $this->when(isset($this->tracking_info), $this->tracking_info),
        ];
    }
}
