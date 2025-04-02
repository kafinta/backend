<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeResource extends JsonResource
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
            'type' => $this->type,
            'is_variant_generator' => $this->is_variant_generator,
            'values' => AttributeValueResource::collection($this->whenLoaded('values')),
            'selected_value' => $this->when(isset($this->pivot?->selected_value), function() {
                return new AttributeValueResource($this->selectedValue);
            })
        ];
    }
}
