<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? [
                    'id' => $this->user->id,
                    'username' => $this->user->username,
                ] : null;
            }),
            'value_for_money' => $this->value_for_money,
            'true_to_description' => $this->true_to_description,
            'product_quality' => $this->product_quality,
            'shipping' => $this->shipping,
            'comment' => $this->comment,
            'images' => $this->images,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'replies' => ReviewResource::collection($this->whenLoaded('replies')),
            'flagged_by' => $this->flagged_by,
            'flag_reason' => $this->flag_reason,
            'overall_rating' => $this->hasAspectRatings() ? round((
                $this->value_for_money + $this->true_to_description + $this->product_quality + $this->shipping
            ) / 4, 2) : null,
            'verified_purchase' => true, // Always true for now
            'helpful_votes' => $this->helpful_votes_count,
        ];
    }

    /**
     * Helper to check if this review has aspect ratings (not a reply)
     */
    protected function hasAspectRatings()
    {
        return ($this->value_for_money > 0 || $this->true_to_description > 0 || $this->product_quality > 0 || $this->shipping > 0);
    }
} 