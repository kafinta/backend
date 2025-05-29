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
            'is_variant_generator' => $this->is_variant_generator,
            'help_text' => $this->help_text,
            'sort_order' => $this->sort_order,
            'values' => AttributeValueResource::collection($this->whenLoaded('values')),
        ];
    }
}
