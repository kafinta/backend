<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'reference' => $this->reference,
            'authorization_url' => $this->authorization_url,
            'amount' => $this->amount_minor,
            'currency' => $this->currency,
            'status' => $this->status,
            'paid_at' => $this->paid_at,
        ];
    }
}
