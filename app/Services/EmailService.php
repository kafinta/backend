<?php

namespace App\Services;

use App\Models\EmailVerificationToken;
use App\Models\User;
use App\Jobs\SendEmailJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class EmailService
{
    /**
     * Dispatch email via queue system
     *
     * @param \Illuminate\Mail\Mailable $mailable
     * @param string $recipient
     * @param string $emailType
     * @param int|null $userId
     * @param bool $immediate - Send immediately without queue (for critical emails)
     * @return bool
     */
    private function dispatchEmail($mailable, string $recipient, string $emailType, int $userId = null, bool $immediate = false): bool
    {
        try {
            if ($immediate || config('queue.default') === 'sync') {
                // Send immediately
                Mail::to($recipient)->send($mailable);

                Log::info('Email sent immediately', [
                    'email_type' => $emailType,
                    'recipient' => $recipient,
                    'user_id' => $userId
                ]);
            } else {
                // Queue the email
                SendEmailJob::dispatch($mailable, $recipient, $emailType, $userId);

                Log::info('Email queued for delivery', [
                    'email_type' => $emailType,
                    'recipient' => $recipient,
                    'user_id' => $userId
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to dispatch email', [
                'email_type' => $emailType,
                'recipient' => $recipient,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    /**
     * Generate a verification token for a user's email
     * Returns data including a verification URL that points to the frontend application
     *
     * @param User $user
     * @param string|null $email
     * @return array
     */
    public function generateVerificationToken(User $user, ?string $email = null): array
    {
        // Use provided email or user's current email
        $email = $email ?? $user->email;

        // Delete any existing tokens for this user and email
        EmailVerificationToken::where('user_id', $user->id)
            ->where('email', $email)
            ->delete();

        // Generate a new token with more entropy
        $token = Str::random(64);

        // Add a hash to make it more secure
        $hashedToken = hash('sha256', $token);

        // Generate a 6-digit verification code
        $verificationCode = sprintf('%06d', mt_rand(100000, 999999));

        // Create a new token record with the hashed token and verification code
        $verificationToken = EmailVerificationToken::create([
            'user_id' => $user->id,
            'email' => $email,
            'token' => $hashedToken,
            'verification_code' => $verificationCode,
            'expires_at' => now()->addHours(24), // Token expires in 24 hours
        ]);

        // Generate verification URL pointing to the frontend
        $verificationUrl = "http://localhost:3000/auth/verify-email/token?token=" . $token;

        // Store token and code in cache for 24 hours
        Cache::put("verification_token_{$token}", [
            'user_id' => $user->id,
            'code' => $verificationCode,
            'expires_at' => now()->addHours(24)
        ], now()->addHours(24));

        return [
            'token' => $token,
            'verification_url' => $verificationUrl,
            'verification_code' => $verificationCode,
            'expires_at' => $verificationToken->expires_at,
        ];
    }



    /**
     * Send a verification email to the user using Brevo API
     *
     * @param User $user
     * @param string $verificationUrl
     * @param string|null $verificationCode
     * @return bool
     */
    public function sendVerificationEmail(User $user, string $verificationUrl, string $verificationCode = null): bool
    {
        $mailable = new \App\Mail\VerificationEmail($user, $verificationUrl, $verificationCode);
        return $this->dispatchEmail($mailable, $user->email, 'verification', $user->id);
    }





    /**
     * Verify a token and mark the user's email as verified
     *
     * @param string $token
     * @return array
     */
    public function verifyToken(string $token): array
    {
        $tokenData = Cache::get("verification_token_{$token}");

        if (!$tokenData) {
            return [
                'success' => false,
                'message' => 'Invalid or expired verification token'
            ];
        }

        if (now()->isAfter($tokenData['expires_at'])) {
            Cache::forget("verification_token_{$token}");
            return [
                'success' => false,
                'message' => 'Verification token has expired'
            ];
        }

        $user = User::find($tokenData['user_id']);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }

        $user->email_verified_at = now();
        $user->save();

        // Clear both token and code from cache
        Cache::forget("verification_token_{$token}");
        Cache::forget("verification_code_{$tokenData['code']}");

        return [
            'success' => true,
            'user' => $user
        ];
    }

    /**
     * Verify a user's email using the verification code
     *
     * @param string $code
     * @param string $email
     * @return array
     */
    public function verifyCode(string $code, string $email): array
    {
        // Find the token using the verification code and email
        $verificationToken = EmailVerificationToken::where('verification_code', $code)
            ->where('email', $email)
            ->first();

        // Check if token exists
        if (!$verificationToken) {
            return [
                'success' => false,
                'message' => 'Invalid verification code',
            ];
        }

        // Check if token is expired
        if ($verificationToken->isExpired()) {
            // Delete the token
            $verificationToken->delete();

            return [
                'success' => false,
                'message' => 'Verification code has expired',
            ];
        }

        // Get the user
        $user = $verificationToken->user;

        // Update user's email if it's different
        if ($user->email !== $verificationToken->email) {
            $user->email = $verificationToken->email;
        }

        // Mark email as verified
        $user->email_verified_at = now();
        $user->save();

        // Clear both token and code from cache
        Cache::forget("verification_token_{$verificationToken->token}");
        Cache::forget("verification_code_{$code}");

        // Delete the token
        $verificationToken->delete();

        return [
            'success' => true,
            'message' => 'Email verified successfully',
            'user' => $user,
        ];
    }

    /**
     * Verify a user's email using only the verification code
     * This is a more user-friendly approach that doesn't require the email
     *
     * @param string $code
     * @return array
     */
    public function verifyCodeOnly(string $code): array
    {
        // First check the cache
        $token = Cache::get("verification_code_{$code}");

        // If not in cache, check the database
        if (!$token) {
            $verificationToken = EmailVerificationToken::where('verification_code', $code)
                ->where('expires_at', '>', now())
                ->first();

            if (!$verificationToken) {
                return [
                    'success' => false,
                    'message' => 'Invalid or expired verification code'
                ];
            }

            // Get the user
            $user = $verificationToken->user;
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }

            // Mark email as verified
            $user->email_verified_at = now();
            $user->save();

            // Delete the token
            $verificationToken->delete();

            return [
                'success' => true,
                'user' => $user
            ];
        }

        return $this->verifyToken($token);
    }

    /**
     * Resend verification email
     *
     * @param User $user
     * @return array
     */
    public function resendVerificationEmail(User $user): array
    {
        // Generate a new verification token
        $tokenData = $this->generateVerificationToken($user);

        // Send verification email
        $emailSent = $this->sendVerificationEmail(
            $user,
            $tokenData['verification_url'],
            $tokenData['verification_code']
        );

        if (!$emailSent) {
            return [
                'success' => false,
                'message' => 'Failed to send verification email',
            ];
        }

        return [
            'success' => true,
            'message' => 'Verification email sent successfully',
            'expires_at' => $tokenData['expires_at'],
        ];
    }

    /**
     * Generate a password reset token for a user
     *
     * @param User $user
     * @return array
     */
    public function generatePasswordResetToken(User $user): array
    {
        // Delete any existing tokens for this user
        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->delete();

        // Generate a new token with more entropy
        $token = Str::random(64);

        // Generate a 6-digit verification code
        $resetCode = sprintf('%06d', mt_rand(100000, 999999));

        // Create a new token record
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'reset_code' => $resetCode,
            'created_at' => now(),
        ]);

        // Generate reset URL pointing to the frontend
        $resetUrl = "http://localhost:3000/auth/reset-password?token=" . $token;

        // Store token and code in cache for 1 hour
        Cache::put("password_reset_{$token}", [
            'user_id' => $user->id,
            'code' => $resetCode,
            'expires_at' => now()->addHours(1)
        ], now()->addHours(1));

        return [
            'token' => $token,
            'reset_url' => $resetUrl,
            'reset_code' => $resetCode,
            'expires_at' => now()->addMinutes(60), // Token expires in 60 minutes
        ];
    }

    /**
     * Send a password reset email to the user
     *
     * @param User $user
     * @param string $resetUrl
     * @param string $resetCode
     * @return bool
     */
    public function sendPasswordResetEmail(User $user, string $resetUrl, string $resetCode): bool
    {
        $mailable = new \App\Mail\PasswordResetEmail($user, $resetUrl, $resetCode);
        return $this->dispatchEmail($mailable, $user->email, 'password_reset', $user->id);
    }

    /**
     * Reset user password using token
     *
     * @param string $token
     * @param string $password
     * @return array
     */
    public function resetPasswordWithToken(string $token, string $password): array
    {
        $tokenData = Cache::get("password_reset_{$token}");

        if (!$tokenData) {
            return [
                'success' => false,
                'message' => 'Invalid or expired reset token'
            ];
        }

        if (now()->isAfter($tokenData['expires_at'])) {
            Cache::forget("password_reset_{$token}");
            return [
                'success' => false,
                'message' => 'Reset token has expired'
            ];
        }

        $user = User::find($tokenData['user_id']);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }

        // Update the password
        $user->password = Hash::make($password);
        $user->save();

        // Delete the reset token
        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->delete();



        Cache::forget("password_reset_{$token}");

        return [
            'success' => true,
            'message' => 'Password reset successfully',
            'user' => $user,
        ];
    }

    /**
     * Reset user password using reset code
     *
     * @param string $code
     * @param string $password
     * @return array
     */
    public function resetPasswordWithCode(string $code, string $password): array
    {
        $token = Cache::get("password_reset_code_{$code}");

        if (!$token) {
            return [
                'success' => false,
                'message' => 'Invalid or expired reset code'
            ];
        }

        return $this->resetPasswordWithToken($token, $password);
    }



    /**
     * Verify a password reset token
     *
     * @param string $token
     * @return array
     */
    public function verifyPasswordResetToken(string $token): array
    {
        $tokenData = Cache::get("password_reset_{$token}");

        if (!$tokenData) {
            return [
                'success' => false,
                'message' => 'Invalid or expired reset token'
            ];
        }

        if (now()->isAfter($tokenData['expires_at'])) {
            Cache::forget("password_reset_{$token}");
            return [
                'success' => false,
                'message' => 'Reset token has expired'
            ];
        }

        $user = User::find($tokenData['user_id']);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }

        return [
            'success' => true,
            'email' => $user->email
        ];
    }

    /**
     * Verify a password reset code
     *
     * @param string $code
     * @return array
     */
    public function verifyPasswordResetCode(string $code): array
    {
        $token = Cache::get("password_reset_code_{$code}");

        if (!$token) {
            return [
                'success' => false,
                'message' => 'Invalid or expired reset code'
            ];
        }

        return $this->verifyPasswordResetToken($token);
    }

    /**
     * Send welcome email after successful registration
     */
    public function sendWelcomeEmail(User $user): bool
    {
        $mailable = new \App\Mail\WelcomeEmail($user);
        return $this->dispatchEmail($mailable, $user->email, 'welcome', $user->id);
    }

    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation(User $user, $order): bool
    {
        $mailable = new \App\Mail\OrderConfirmationEmail($user, $order);
        return $this->dispatchEmail($mailable, $user->email, 'order_confirmation', $user->id);
    }

    /**
     * Send notification email (general purpose)
     */
    public function sendNotification(User $user, string $subject, string $template, array $data = []): bool
    {
        $mailable = new \App\Mail\NotificationEmail($user, $subject, $template, $data);
        return $this->dispatchEmail($mailable, $user->email, 'notification', $user->id);
    }

    /**
     * Send seller application status email
     */
    public function sendSellerApplicationStatus(User $user, string $status, string $reason = null): bool
    {
        $mailable = new \App\Mail\SellerApplicationStatusEmail($user, $status, $reason);
        return $this->dispatchEmail($mailable, $user->email, 'seller_status', $user->id);
    }
}
