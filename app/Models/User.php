<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'phone_number',
        'profile_picture',
        'provider',
        'provider_id',
        'provider_token',
        'provider_refresh_token',
        'provider_token_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'provider_token_expires_at' => 'datetime',
    ];

    // Profile functionality has been merged into the User model

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole($role)
    {
        return $this->roles()->where('slug', $role)->exists();
    }

    public function createToken(string $name, array $abilities = ['*'])
    {
        $token = $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken = Str::random(64)),
            'abilities' => $abilities,
        ]);

        return new NewAccessToken($token, $token->getKey() . '|' . $plainTextToken);
    }

    /**
     * Check if the user is a seller
     *
     * @return bool
     */
    public function isSeller()
    {
        return $this->hasRole('seller');
    }

    /**
     * Check if the user is an admin
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    /**
     * Get the seller profile associated with the user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function seller()
    {
        return $this->hasOne(Seller::class);
    }

    /**
     * Get the products that belong to the user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Check if the user is an OAuth user
     *
     * @return bool
     */
    public function isOAuthUser()
    {
        return !is_null($this->provider);
    }

    /**
     * Check if the user has a password set
     *
     * @return bool
     */
    public function hasPassword()
    {
        return !is_null($this->password);
    }

    /**
     * Check if the OAuth token is expired
     *
     * @return bool
     */
    public function isOAuthTokenExpired()
    {
        if (!$this->provider_token_expires_at) {
            return false;
        }

        return $this->provider_token_expires_at->isPast();
    }

    /**
     * Get the user's OAuth provider display name
     *
     * @return string|null
     */
    public function getProviderDisplayName()
    {
        return match($this->provider) {
            'google' => 'Google',
            'facebook' => 'Facebook',
            'apple' => 'Apple',
            default => $this->provider ? ucfirst($this->provider) : null,
        };
    }
}