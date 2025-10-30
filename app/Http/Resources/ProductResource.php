<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Location;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            // Discount info
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'discount_start' => $this->discount_start,
            'discount_end' => $this->discount_end,
            'has_active_discount' => $this->hasActiveDiscount(),
            'discount_amount' => $this->getDiscountAmount(),
            'discounted_price' => $this->getDiscountedPrice(),
            'seller' => optional($this->user && $this->user->relationLoaded('seller') ? $this->user->seller : ($this->user->seller ?? null))->business_name,
            'status' => $this->status,
            'denial_reason' => $this->when($this->denial_reason, $this->denial_reason),
            'is_featured' => $this->is_featured,
            'stock_quantity' => $this->stock_quantity,
            'manage_stock' => $this->manage_stock,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Inventory information
            'is_in_stock' => $this->isInStock(),
            'is_out_of_stock' => $this->isOutOfStock(),

            // Taxonomy relationships
            'category' => $this->when($this->relationLoaded('category'), function () {
                return new CategoryResource($this->category);
            }),
            'subcategory' => $this->when($this->relationLoaded('subcategory'), function () {
                return new SubcategoryResource($this->subcategory);
            }),
            'location' => $this->when($this->relationLoaded('location'), function () {
                return new LocationResource($this->location);
            }),

            // Images
            'images' => ImageResource::collection($this->whenLoaded('images')),

            // Attributes - show selected attribute values for this product
            'attributes' => $this->when($this->relationLoaded('attributeValues'), function () {
                return $this->attributeValues->map(function ($attributeValue) {
                    return [
                        'id' => $attributeValue->attribute->id,
                        'name' => $attributeValue->attribute->name,
                        'value' => [
                            'id' => $attributeValue->id,
                            'name' => $attributeValue->name,
                            'representation' => $attributeValue->representation,
                        ]
                    ];
                });
            }),

            // Progress tracking for draft products
            'completion_status' => $this->when($this->status === 'draft', function () {
                return $this->getCompletionStatus();
            }),
            'next_step' => $this->when($this->status === 'draft', function () {
                return $this->getNextStep();
            }),
            'progress_percentage' => $this->when($this->status === 'draft', function () {
                return $this->getProgressPercentage();
            }),

            // Review summary
            'review_summary' => [
                'overall_rating' => $this->overall_rating,
                'average_value_for_money' => $this->average_value_for_money,
                'average_true_to_description' => $this->average_true_to_description,
                'average_product_quality' => $this->average_product_quality,
                'average_shipping' => $this->average_shipping,
                'review_count' => $this->reviews()->where('status', 'active')->count(),
            ],
            'latest_reviews' => $this->whenLoaded('reviews', function () {
                return $this->reviews->where('status', 'active')->sortByDesc('created_at')->take(3)->values()->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'user' => $review->user ? [
                            'id' => $review->user->id,
                            'username' => $review->user->username,
                        ] : null,
                        'value_for_money' => $review->value_for_money,
                        'true_to_description' => $review->true_to_description,
                        'product_quality' => $review->product_quality,
                        'shipping' => $review->shipping,
                        'comment' => $review->comment,
                        'images' => $review->images,
                        'created_at' => $review->created_at,
                        'replies' => $review->replies->map(function ($reply) {
                            return [
                                'id' => $reply->id,
                                'user' => $reply->user ? [
                                    'id' => $reply->user->id,
                                    'username' => $reply->user->username,
                                ] : null,
                                'comment' => $reply->comment,
                                'created_at' => $reply->created_at,
                            ];
                        }),
                    ];
                });
            }),
        ];
    }
}
