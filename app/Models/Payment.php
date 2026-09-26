<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'provider',
        'reference',
        'access_code',
        'authorization_url',
        'amount_minor',
        'currency',
        'status',
        'gateway_transaction_id',
        'gateway_response',
        'paid_at',
    ];

    protected $casts = [
        'amount_minor' => 'integer',
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
