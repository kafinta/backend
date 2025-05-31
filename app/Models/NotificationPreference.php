<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
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
        'email_enabled',
        'app_enabled',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_enabled' => 'boolean',
        'app_enabled' => 'boolean',
    ];

    /**
     * Get the user that owns the notification preference.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get default preferences for a user
     *
     * @param int $userId
     * @return array
     */
    public static function getDefaultPreferences(int $userId): array
    {
        $types = Notification::getTypes();
        $defaults = [];

        foreach ($types as $type) {
            $defaults[] = [
                'user_id' => $userId,
                'type' => $type,
                'email_enabled' => self::getDefaultEmailSetting($type),
                'app_enabled' => self::getDefaultAppSetting($type),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $defaults;
    }

    /**
     * Get default email setting for notification type
     *
     * @param string $type
     * @return bool
     */
    private static function getDefaultEmailSetting(string $type): bool
    {
        // Email enabled by default for important notifications
        $emailEnabledTypes = [
            Notification::TYPE_ORDER_PLACED,
            Notification::TYPE_ORDER_CONFIRMED,
            Notification::TYPE_ORDER_SHIPPED,
            Notification::TYPE_ORDER_DELIVERED,
            Notification::TYPE_ORDER_CANCELLED,
            Notification::TYPE_SELLER_NEW_ORDER,
            Notification::TYPE_SELLER_PRODUCT_APPROVED,
            Notification::TYPE_SELLER_PRODUCT_DENIED,
            Notification::TYPE_SYSTEM_MAINTENANCE,
        ];

        return in_array($type, $emailEnabledTypes);
    }

    /**
     * Get default app setting for notification type
     *
     * @param string $type
     * @return bool
     */
    private static function getDefaultAppSetting(string $type): bool
    {
        // App notifications enabled by default for most types
        $appDisabledTypes = [
            Notification::TYPE_SYSTEM_MAINTENANCE, // Only email for maintenance
        ];

        return !in_array($type, $appDisabledTypes);
    }

    /**
     * Create default preferences for a user
     *
     * @param int $userId
     * @return void
     */
    public static function createDefaultPreferences(int $userId): void
    {
        $preferences = self::getDefaultPreferences($userId);
        self::insert($preferences);
    }

    /**
     * Get user preferences as associative array
     *
     * @param int $userId
     * @return array
     */
    public static function getUserPreferences(int $userId): array
    {
        $preferences = self::where('user_id', $userId)->get();
        
        if ($preferences->isEmpty()) {
            self::createDefaultPreferences($userId);
            $preferences = self::where('user_id', $userId)->get();
        }

        $result = [];
        foreach ($preferences as $preference) {
            $result[$preference->type] = [
                'email_enabled' => $preference->email_enabled,
                'app_enabled' => $preference->app_enabled,
            ];
        }

        return $result;
    }

    /**
     * Update user preferences
     *
     * @param int $userId
     * @param array $preferences
     * @return bool
     */
    public static function updateUserPreferences(int $userId, array $preferences): bool
    {
        try {
            foreach ($preferences as $type => $settings) {
                self::updateOrCreate(
                    ['user_id' => $userId, 'type' => $type],
                    [
                        'email_enabled' => $settings['email_enabled'] ?? false,
                        'app_enabled' => $settings['app_enabled'] ?? true,
                    ]
                );
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if email notifications are enabled for user and type
     *
     * @param int $userId
     * @param string $type
     * @return bool
     */
    public static function isEmailEnabled(int $userId, string $type): bool
    {
        $preference = self::where('user_id', $userId)
            ->where('type', $type)
            ->first();

        if (!$preference) {
            return self::getDefaultEmailSetting($type);
        }

        return $preference->email_enabled;
    }

    /**
     * Check if app notifications are enabled for user and type
     *
     * @param int $userId
     * @param string $type
     * @return bool
     */
    public static function isAppEnabled(int $userId, string $type): bool
    {
        $preference = self::where('user_id', $userId)
            ->where('type', $type)
            ->first();

        if (!$preference) {
            return self::getDefaultAppSetting($type);
        }

        return $preference->app_enabled;
    }
}
