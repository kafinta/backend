<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Cart extends Model
{
    protected $fillable = ['user_id', 'session_id', 'expires_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Define the relationship with cart items.
     */
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include expired carts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                     ->where('expires_at', '<', Carbon::now());
    }

    /**
     * Scope a query to only include guest carts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGuest($query)
    {
        return $query->whereNull('user_id')
                     ->whereNotNull('session_id');
    }

    /**
     * Check if the cart is expired.
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Set the expiration date for the cart.
     *
     * @param  int  $days
     * @return $this
     */
    public function setExpiration($days = 30)
    {
        $this->expires_at = Carbon::now()->addDays($days);
        return $this;
    }

    /**
     * Extend the expiration date for the cart.
     *
     * @param  int  $days
     * @return $this
     */
    public function extendExpiration($days = 30)
    {
        // Always set expiration to $days from now
        // This prevents the expiration date from being pushed too far into the future
        $this->expires_at = Carbon::now()->addDays($days);

        return $this;
    }

    use HasFactory;
}
