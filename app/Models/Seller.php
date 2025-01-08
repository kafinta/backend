<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'business_description',
        'business_address',
        'phone_number',
        'id_type',
        'id_number',
        'id_document',
        'is_verified',
        'rating'
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'rating' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
