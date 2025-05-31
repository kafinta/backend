<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            
            // Status indicators
            'is_read' => $this->isRead(),
            'is_unread' => $this->isUnread(),
            
            // Timestamps
            'read_at' => $this->read_at,
            'sent_at' => $this->sent_at,
            'created_at' => $this->created_at,
            
            // Computed fields
            'time_ago' => $this->time_ago,
            'icon' => $this->icon,
            'color' => $this->color,
            
            // User information (when needed)
            'user' => $this->when($this->relationLoaded('user'), function () {
                return new UserAccountResource($this->user);
            }),
        ];
    }
}
