<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\ImageResource;

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
        $sellerName = optional(optional($this->whenLoaded('user') ? $this->user : null)->seller)->business_name;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->description ? trim(strip_tags(substr($this->description, 0, 120))) : null,
            'price' => $this->getDiscountedPrice(),
            'previous_price' => $this->hasActiveDiscount() ? (float) $this->price : null,
            'discountPrice' => $this->getDiscountedPrice(),
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
            'image' => $productImage ? $productImage->url : null,
            'images' => $this->whenLoaded('images', function () {
                return ImageResource::collection($this->images);
            }),
            'seller_name' => $sellerName,
            'category' => $this->when($this->relationLoaded('subcategory') && $this->subcategory, $this->subcategory->name),
            'subcategory' => $this->when($this->relationLoaded('subcategory') && $this->subcategory, $this->subcategory->name),
            'active_reviews_count' => $this->when(isset($this->active_reviews_count), $this->active_reviews_count),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
