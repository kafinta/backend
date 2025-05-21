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
        'rating',
        // Business category fields
        'business_category',
        'business_subcategory',
        'years_in_business',
        'business_website',
        'business_social_media',
        // Payment information fields
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'bank_routing_number',
        'payment_method',
        'paypal_email',
        // Agreement fields
        'agreement_accepted',
        'agreement_version',
        'agreement_ip_address',
        // Progress tracking fields
        'onboarding_progress'
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'agreement_accepted' => 'boolean',
        'rating' => 'decimal:2',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'profile_completed_at' => 'datetime',
        'kyc_verified_at' => 'datetime',
        'agreement_accepted_at' => 'datetime',
        'agreement_completed_at' => 'datetime',
        'payment_info_completed_at' => 'datetime',
        'onboarding_completed_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
