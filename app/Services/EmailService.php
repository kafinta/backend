<?php

namespace App\Services;

use App\Models\EmailVerificationToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class EmailService
{
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
        $verificationUrl = "http://localhost:3000/auth/verify?token=" . $token;

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
     * Send a verification email to the user
     *
     * @param User $user
     * @param string $verificationUrl
     * @param string|null $verificationCode
     * @return bool
     */
    public function sendVerificationEmail(User $user, string $verificationUrl, string $verificationCode = null): bool
    {
        try {
            Mail::to($user->email)->send(new \App\Mail\VerificationEmail($user, $verificationUrl, $verificationCode));
            return true;
        } catch (\Exception $e) {
            Log::error('Error sending verification email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete the email files associated with a user
     *
     * @param EmailVerificationToken $token
     * @return bool
     */
    private function deleteEmailFile(EmailVerificationToken $token): bool
    {
        // Find all email files for this user
        $pattern = 'verification_' . $token->user_id . '_*.html';
        $files = glob(storage_path('simulated-emails/' . $pattern));

        if (!empty($files)) {
            foreach ($files as $file) {
                unlink($file);
                Log::info('Deleted email file', ['file' => basename($file)]);
            }
            return true;
        }

        return false;
    }

    /**
     * Create an HTML email for verification
     *
     * @param User $user
     * @param string $verificationUrl Frontend URL for email verification
     * @param string|null $verificationCode
     * @return string
     */
    private function createHtmlEmail(User $user, string $verificationUrl, string $verificationCode = null): string
    {
        $appName = config('app.name', 'Laravel');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verify Your Email Address</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4a76a8;
            color: white;
            padding: 10px 20px;
            border-radius: 5px 5px 0 0;
        }
        .content {
            border: 1px solid #ddd;
            border-top: none;
            padding: 20px;
            border-radius: 0 0 5px 5px;
        }
        .button {
            display: inline-block;
            background-color: #4a76a8;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>{$appName}</h2>
    </div>
    <div class="content">
        <h3>Hello {$user->username},</h3>
        <p>Thank you for registering with {$appName}. Please click the button below to verify your email address.</p>
        <p><a href="{$verificationUrl}" class="button">Verify Email Address</a></p>

        " . ($verificationCode ? "
        <div style='margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-radius: 5px; text-align: center;'>
            <p style='margin-bottom: 10px;'>Or enter this verification code:</p>
            <h2 style='letter-spacing: 5px; font-size: 24px; margin: 0;'>{$verificationCode}</h2>
        </div>
        " : "") . "

        <p>If you did not create an account, no further action is required.</p>
        <p>If you're having trouble clicking the button, copy and paste the URL below into your web browser:</p>
        <p>{$verificationUrl}</p>
        <div class="footer">
            <p>This is a simulated email for development purposes.</p>
            <p>© {$appName} - " . date('Y') . "</p>
        </div>
    </div>
</body>
</html>
HTML;
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

        Cache::forget("verification_token_{$token}");

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
            // Delete the email file for expired token
            $this->deleteEmailFile($verificationToken);

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

        // Delete the email file
        $this->deleteEmailFile($verificationToken);

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
        $token = Cache::get("verification_code_{$code}");

        if (!$token) {
            return [
                'success' => false,
                'message' => 'Invalid or expired verification code'
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
        try {
            Mail::to($user->email)->send(new \App\Mail\PasswordResetEmail($user, $resetUrl, $resetCode));
            return true;
        } catch (\Exception $e) {
            Log::error('Error sending password reset email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
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

        // Delete any password reset email files
        $this->deletePasswordResetEmailFiles($user);

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
     * Delete password reset email files for a user
     *
     * @param User $user
     * @return bool
     */
    private function deletePasswordResetEmailFiles(User $user): bool
    {
        // Find all password reset email files for this user
        $pattern = 'password_reset_' . $user->id . '_*.html';
        $files = glob(storage_path('simulated-emails/' . $pattern));

        if (!empty($files)) {
            foreach ($files as $file) {
                unlink($file);
                Log::info('Deleted password reset email file', ['file' => basename($file)]);
            }
            return true;
        }

        return false;
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
}
