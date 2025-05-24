<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SocialAuthService
{
    /**
     * Handle OAuth user authentication/registration
     *
     * @param SocialiteUser $socialiteUser
     * @param string $provider
     * @return array
     */
    public function handleOAuthUser(SocialiteUser $socialiteUser, string $provider): array
    {
        try {
            DB::beginTransaction();

            // First, try to find user by provider and provider ID
            $user = User::where('provider', $provider)
                       ->where('provider_id', $socialiteUser->getId())
                       ->first();

            if ($user) {
                // Update existing OAuth user
                $user = $this->updateOAuthUser($user, $socialiteUser, $provider);
                DB::commit();
                
                return [
                    'success' => true,
                    'user' => $user,
                    'is_new_user' => false,
                    'message' => 'Login successful'
                ];
            }

            // Check if user exists with same email but different provider
            $existingUser = User::where('email', $socialiteUser->getEmail())->first();

            if ($existingUser) {
                // Link OAuth account to existing user
                $user = $this->linkOAuthToExistingUser($existingUser, $socialiteUser, $provider);
                DB::commit();
                
                return [
                    'success' => true,
                    'user' => $user,
                    'is_new_user' => false,
                    'message' => 'OAuth account linked successfully'
                ];
            }

            // Create new user
            $user = $this->createOAuthUser($socialiteUser, $provider);
            DB::commit();

            return [
                'success' => true,
                'user' => $user,
                'is_new_user' => true,
                'message' => 'Account created successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('OAuth authentication error', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'OAuth authentication failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update existing OAuth user
     *
     * @param User $user
     * @param SocialiteUser $socialiteUser
     * @param string $provider
     * @return User
     */
    private function updateOAuthUser(User $user, SocialiteUser $socialiteUser, string $provider): User
    {
        $updateData = [
            'provider_token' => $socialiteUser->token,
            'provider_refresh_token' => $socialiteUser->refreshToken,
        ];

        // Update email if it changed
        if ($user->email !== $socialiteUser->getEmail()) {
            $updateData['email'] = $socialiteUser->getEmail();
            $updateData['email_verified_at'] = now(); // OAuth emails are considered verified
        }

        // Update profile picture if not set or if it's a default one
        if (!$user->profile_picture || $this->isDefaultProfilePicture($user->profile_picture)) {
            $avatar = $socialiteUser->getAvatar();
            if ($avatar) {
                $updateData['profile_picture'] = $avatar;
            }
        }

        // Set token expiration if available
        if ($socialiteUser->expiresIn) {
            $updateData['provider_token_expires_at'] = now()->addSeconds($socialiteUser->expiresIn);
        }

        $user->update($updateData);

        return $user;
    }

    /**
     * Link OAuth account to existing user
     *
     * @param User $existingUser
     * @param SocialiteUser $socialiteUser
     * @param string $provider
     * @return User
     */
    private function linkOAuthToExistingUser(User $existingUser, SocialiteUser $socialiteUser, string $provider): User
    {
        $updateData = [
            'provider' => $provider,
            'provider_id' => $socialiteUser->getId(),
            'provider_token' => $socialiteUser->token,
            'provider_refresh_token' => $socialiteUser->refreshToken,
            'email_verified_at' => $existingUser->email_verified_at ?? now(), // Mark as verified if not already
        ];

        // Update profile picture if not set
        if (!$existingUser->profile_picture) {
            $avatar = $socialiteUser->getAvatar();
            if ($avatar) {
                $updateData['profile_picture'] = $avatar;
            }
        }

        // Set token expiration if available
        if ($socialiteUser->expiresIn) {
            $updateData['provider_token_expires_at'] = now()->addSeconds($socialiteUser->expiresIn);
        }

        $existingUser->update($updateData);

        return $existingUser;
    }

    /**
     * Create new OAuth user
     *
     * @param SocialiteUser $socialiteUser
     * @param string $provider
     * @return User
     */
    private function createOAuthUser(SocialiteUser $socialiteUser, string $provider): User
    {
        // Generate unique username
        $username = $this->generateUniqueUsername($socialiteUser->getName() ?? $socialiteUser->getEmail());

        $userData = [
            'username' => $username,
            'email' => $socialiteUser->getEmail(),
            'email_verified_at' => now(), // OAuth emails are considered verified
            'provider' => $provider,
            'provider_id' => $socialiteUser->getId(),
            'provider_token' => $socialiteUser->token,
            'provider_refresh_token' => $socialiteUser->refreshToken,
            'profile_picture' => $socialiteUser->getAvatar(),
        ];

        // Set token expiration if available
        if ($socialiteUser->expiresIn) {
            $userData['provider_token_expires_at'] = now()->addSeconds($socialiteUser->expiresIn);
        }

        $user = User::create($userData);

        // Assign default role
        $defaultRole = Role::where('slug', 'customer')->first();
        if ($defaultRole) {
            $user->roles()->attach($defaultRole->id);
        }

        return $user;
    }

    /**
     * Generate unique username from name or email
     *
     * @param string $name
     * @return string
     */
    private function generateUniqueUsername(string $name): string
    {
        // Clean the name to create a base username
        $baseUsername = Str::slug(Str::before($name, '@'), '');
        $baseUsername = Str::limit($baseUsername, 20, '');
        
        if (empty($baseUsername)) {
            $baseUsername = 'user';
        }

        $username = $baseUsername;
        $counter = 1;

        // Keep trying until we find a unique username
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Check if profile picture is a default/placeholder
     *
     * @param string $profilePicture
     * @return bool
     */
    private function isDefaultProfilePicture(string $profilePicture): bool
    {
        $defaultPatterns = [
            'default',
            'placeholder',
            'avatar',
            'gravatar'
        ];

        foreach ($defaultPatterns as $pattern) {
            if (Str::contains(strtolower($profilePicture), $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get supported OAuth providers
     *
     * @return array
     */
    public function getSupportedProviders(): array
    {
        return [
            'google' => [
                'name' => 'Google',
                'icon' => 'fab fa-google',
                'color' => '#db4437'
            ],
            'facebook' => [
                'name' => 'Facebook',
                'icon' => 'fab fa-facebook-f',
                'color' => '#3b5998'
            ],
            'apple' => [
                'name' => 'Apple',
                'icon' => 'fab fa-apple',
                'color' => '#000000'
            ]
        ];
    }
}
