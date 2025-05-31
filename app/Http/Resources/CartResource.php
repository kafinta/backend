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
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'session_id' => $this->when(!$this->user_id, $this->session_id),
            'expires_at' => $this->expires_at,
            'is_expired' => $this->when(method_exists($this->resource, 'isExpired'), $this->isExpired()),
            'items' => CartItemResource::collection($this->whenLoaded('cartItems')),
            'totals' => $this->when(isset($this->totals), $this->totals),
            'item_count' => $this->when(isset($this->item_count), $this->item_count),
            'total_quantity' => $this->when(isset($this->total_quantity), $this->total_quantity),
        ];
    }
}
