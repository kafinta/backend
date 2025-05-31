<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubcategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image_path' => $this->image_path,
            'category' => $this->when($this->relationLoaded('category'), function () {
                return new CategoryResource($this->category);
            }),
            'locations' => LocationResource::collection($this->whenLoaded('locations')),
            'attributes' => AttributeResource::collection($this->whenLoaded('attributes')),
        ];
    }
}
