<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ImprovedController;
use App\Models\EmailVerificationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerificationTokenController extends ImprovedController
{
    /**
     * List all active verification tokens
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $tokens = EmailVerificationToken::with('user')
                ->orderBy('created_at', 'desc')
                ->get();

            \Log::info('Found verification tokens', ['count' => $tokens->count()]);

            $tokenData = $tokens->map(function ($token) {
                return [
                    'id' => $token->id,
                    'user_id' => $token->user_id,
                    'username' => $token->user->username,
                    'email' => $token->email,
                    'token' => $token->token,
                    'verification_code' => $token->verification_code,
                    'verification_url' => "http://localhost:3000/auth/verify-email/token?token=" . $token->token,
                    'created_at' => $token->created_at->format('Y-m-d H:i:s'),
                    'expires_at' => $token->expires_at->format('Y-m-d H:i:s'),
                    'is_expired' => $token->isExpired(),
                ];
            });

            return $this->respondWithSuccess('Verification tokens retrieved successfully', 200, [
                'tokens' => $tokenData,
                'token_count' => $tokens->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error retrieving verification tokens', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->respondWithError('Error retrieving verification tokens: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get a specific verification token
     *
     * @param string $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($token)
    {
        try {
            $verificationToken = EmailVerificationToken::where('token', $token)->first();

            if (!$verificationToken) {
                return $this->respondWithError('Verification token not found', 404);
            }

            $tokenData = [
                'id' => $verificationToken->id,
                'user_id' => $verificationToken->user_id,
                'username' => $verificationToken->user->username,
                'email' => $verificationToken->email,
                'token' => $verificationToken->token,
                'verification_url' => "http://localhost:3000/auth/verify-email/token?token=" . $verificationToken->token,
                'created_at' => $verificationToken->created_at->format('Y-m-d H:i:s'),
                'expires_at' => $verificationToken->expires_at->format('Y-m-d H:i:s'),
                'is_expired' => $verificationToken->isExpired(),
            ];

            return $this->respondWithSuccess('Verification token retrieved successfully', 200, [
                'token' => $tokenData,
            ]);
        } catch (\Exception $e) {
            return $this->respondWithError('Error retrieving verification token: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a specific verification token
     *
     * @param string $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($token)
    {
        try {
            $verificationToken = EmailVerificationToken::where('token', $token)->first();

            if (!$verificationToken) {
                return $this->respondWithError('Verification token not found', 404);
            }

            $verificationToken->delete();

            return $this->respondWithSuccess('Verification token deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->respondWithError('Error deleting verification token: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete all verification tokens
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroyAll()
    {
        try {
            DB::table('email_verification_tokens')->delete();

            return $this->respondWithSuccess('All verification tokens deleted successfully', 200);
        } catch (\Exception $e) {
            return $this->respondWithError('Error deleting verification tokens: ' . $e->getMessage(), 500);
        }
    }
}
