<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAccountResource extends JsonResource
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
            'username' => $this->username,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'profile_picture' => $this->profile_picture,
            'email_verified_at' => $this->email_verified_at,

            // OAuth information
            'oauth_provider' => $this->provider,
            'oauth_provider_display' => $this->getProviderDisplayName(),
            'is_oauth_user' => $this->isOAuthUser(),
            'has_password' => $this->hasPassword(),

            // Role information
            'roles' => $this->roles->pluck('slug'),
            'is_seller' => $this->isSeller(),
            'is_admin' => $this->isAdmin(),
        ];
    }
}