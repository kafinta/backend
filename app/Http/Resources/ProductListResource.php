<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $productImage = $this->whenLoaded('images') ? $this->images->first() : null;
        $sellerName = optional($this->whenLoaded('user') && $this->user->relationLoaded('seller') ? $this->user->seller : ($this->user->seller ?? null))->business_name;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => optional($this->description) ? strip_tags(substr($this->description, 0, 120)) : null,
            'price' => $this->price,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'has_active_discount' => $this->hasActiveDiscount(),
            'discount_amount' => $this->getDiscountAmount(),
            'discounted_price' => $this->getDiscountedPrice(),
            'is_featured' => $this->is_featured,
            'stock_quantity' => $this->stock_quantity,
            'manage_stock' => $this->manage_stock,
            'is_in_stock' => $this->isInStock(),
            'is_out_of_stock' => $this->isOutOfStock(),
            'image' => $productImage ? $productImage->path : null,
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'seller_name' => $sellerName,
            'category' => $this->when($this->relationLoaded('subcategory') && $this->subcategory, function () {
                return $this->subcategory->name;
            }),
            'subcategory' => $this->when($this->relationLoaded('subcategory') && $this->subcategory, function () {
                return $this->subcategory->name;
            }),
            'active_reviews_count' => $this->when(isset($this->active_reviews_count), $this->active_reviews_count),
            'average_rating' => $this->when(isset($this->average_rating), $this->average_rating),
            'review_count' => $this->when(isset($this->review_count), $this->review_count),
            'sales_count' => $this->when(isset($this->sales_count), $this->sales_count),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
