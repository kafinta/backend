<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Notification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read_at',
        'sent_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Notification types
     */
    const TYPE_ORDER_PLACED = 'order_placed';
    const TYPE_ORDER_CONFIRMED = 'order_confirmed';
    const TYPE_ORDER_PROCESSING = 'order_processing';
    const TYPE_ORDER_SHIPPED = 'order_shipped';
    const TYPE_ORDER_DELIVERED = 'order_delivered';
    const TYPE_ORDER_CANCELLED = 'order_cancelled';
    const TYPE_CART_ABANDONED = 'cart_abandoned';
    const TYPE_PRODUCT_LOW_STOCK = 'product_low_stock';
    const TYPE_PRODUCT_OUT_OF_STOCK = 'product_out_of_stock';
    const TYPE_SELLER_NEW_ORDER = 'seller_new_order';
    const TYPE_SELLER_PRODUCT_APPROVED = 'seller_product_approved';
    const TYPE_SELLER_PRODUCT_DENIED = 'seller_product_denied';
    const TYPE_ADMIN_NEW_PRODUCT = 'admin_new_product';
    const TYPE_ADMIN_NEW_SELLER = 'admin_new_seller';
    const TYPE_SYSTEM_MAINTENANCE = 'system_maintenance';

    /**
     * Get all available notification types
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_ORDER_PLACED,
            self::TYPE_ORDER_CONFIRMED,
            self::TYPE_ORDER_PROCESSING,
            self::TYPE_ORDER_SHIPPED,
            self::TYPE_ORDER_DELIVERED,
            self::TYPE_ORDER_CANCELLED,
            self::TYPE_CART_ABANDONED,
            self::TYPE_PRODUCT_LOW_STOCK,
            self::TYPE_PRODUCT_OUT_OF_STOCK,
            self::TYPE_SELLER_NEW_ORDER,
            self::TYPE_SELLER_PRODUCT_APPROVED,
            self::TYPE_SELLER_PRODUCT_DENIED,
            self::TYPE_ADMIN_NEW_PRODUCT,
            self::TYPE_ADMIN_NEW_SELLER,
            self::TYPE_SYSTEM_MAINTENANCE,
        ];
    }

    /**
     * Get the user that owns the notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include unread notifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope a query to only include read notifications.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope a query to filter by notification type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Mark the notification as read.
     *
     * @return bool
     */
    public function markAsRead()
    {
        if ($this->read_at) {
            return false;
        }

        return $this->update(['read_at' => now()]);
    }

    /**
     * Mark the notification as unread.
     *
     * @return bool
     */
    public function markAsUnread()
    {
        return $this->update(['read_at' => null]);
    }

    /**
     * Check if the notification is read.
     *
     * @return bool
     */
    public function isRead()
    {
        return !is_null($this->read_at);
    }

    /**
     * Check if the notification is unread.
     *
     * @return bool
     */
    public function isUnread()
    {
        return is_null($this->read_at);
    }

    /**
     * Get the time since the notification was created.
     *
     * @return string
     */
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get the notification icon based on type.
     *
     * @return string
     */
    public function getIconAttribute()
    {
        $icons = [
            self::TYPE_ORDER_PLACED => 'shopping-cart',
            self::TYPE_ORDER_CONFIRMED => 'check-circle',
            self::TYPE_ORDER_PROCESSING => 'clock',
            self::TYPE_ORDER_SHIPPED => 'truck',
            self::TYPE_ORDER_DELIVERED => 'package',
            self::TYPE_ORDER_CANCELLED => 'x-circle',
            self::TYPE_CART_ABANDONED => 'shopping-cart',
            self::TYPE_PRODUCT_LOW_STOCK => 'alert-triangle',
            self::TYPE_PRODUCT_OUT_OF_STOCK => 'alert-circle',
            self::TYPE_SELLER_NEW_ORDER => 'shopping-bag',
            self::TYPE_SELLER_PRODUCT_APPROVED => 'check',
            self::TYPE_SELLER_PRODUCT_DENIED => 'x',
            self::TYPE_ADMIN_NEW_PRODUCT => 'plus-circle',
            self::TYPE_ADMIN_NEW_SELLER => 'user-plus',
            self::TYPE_SYSTEM_MAINTENANCE => 'settings',
        ];

        return $icons[$this->type] ?? 'bell';
    }

    /**
     * Get the notification color based on type.
     *
     * @return string
     */
    public function getColorAttribute()
    {
        $colors = [
            self::TYPE_ORDER_PLACED => 'blue',
            self::TYPE_ORDER_CONFIRMED => 'green',
            self::TYPE_ORDER_PROCESSING => 'yellow',
            self::TYPE_ORDER_SHIPPED => 'purple',
            self::TYPE_ORDER_DELIVERED => 'green',
            self::TYPE_ORDER_CANCELLED => 'red',
            self::TYPE_CART_ABANDONED => 'orange',
            self::TYPE_PRODUCT_LOW_STOCK => 'yellow',
            self::TYPE_PRODUCT_OUT_OF_STOCK => 'red',
            self::TYPE_SELLER_NEW_ORDER => 'blue',
            self::TYPE_SELLER_PRODUCT_APPROVED => 'green',
            self::TYPE_SELLER_PRODUCT_DENIED => 'red',
            self::TYPE_ADMIN_NEW_PRODUCT => 'blue',
            self::TYPE_ADMIN_NEW_SELLER => 'green',
            self::TYPE_SYSTEM_MAINTENANCE => 'gray',
        ];

        return $colors[$this->type] ?? 'gray';
    }
}
