<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $totalPrice = $this->when(isset($this->totals['total_price']), $this->totals['total_price']);
        $itemCount = $this->when(isset($this->totals['item_count']), $this->totals['item_count']);
        $totalQuantity = $this->when(isset($this->totals['total_quantity']), $this->totals['total_quantity']);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'session_id' => $this->when(!$this->user_id, $this->session_id),
            'expires_at' => $this->expires_at,
            'is_expired' => $this->when(method_exists($this->resource, 'isExpired'), $this->isExpired()),
            'items' => CartItemResource::collection($this->whenLoaded('cartItems')),
            'totals' => $this->when(isset($this->totals), $this->totals),
            'total_price' => $totalPrice,
            'item_count' => $itemCount ?? $this->whenLoaded('cartItems', fn () => $this->cartItems->count()),
            'total_quantity' => $totalQuantity ?? $this->whenLoaded('cartItems', fn () => $this->cartItems->sum('quantity')),
        ];
    }
}
