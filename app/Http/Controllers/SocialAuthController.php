<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ImprovedController;
use App\Http\Resources\UserAccountResource;
use App\Services\SocialAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends ImprovedController
{
    protected $socialAuthService;

    public function __construct(SocialAuthService $socialAuthService)
    {
        $this->socialAuthService = $socialAuthService;
    }

    /**
     * Redirect to OAuth provider
     *
     * @param string $provider
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToProvider(string $provider)
    {
        try {
            if (!$this->isValidProvider($provider)) {
                return $this->respondWithError('Invalid OAuth provider', 400);
            }

            $driver = \Laravel\Socialite\Facades\Socialite::driver($provider);

            // Set scopes based on provider
            if ($provider === 'google') {
                $driver->scopes(['openid', 'profile', 'email']);
            } elseif ($provider === 'facebook') {
                $driver->scopes(['email', 'public_profile']);
            } elseif ($provider === 'apple') {
                $driver->scopes(['name', 'email']);
            }

            $redirectUrl = $driver->redirect()->getTargetUrl();

            return $this->respondWithSuccess('Redirect URL generated', 200, [
                'redirect_url' => $redirectUrl
            ]);

        } catch (\Exception $e) {
            Log::error('OAuth redirect error', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            return $this->respondWithError('Failed to generate OAuth redirect URL', 500);
        }
    }

    /**
     * Handle OAuth callback
     *
     * @param string $provider
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleProviderCallback(string $provider, Request $request)
    {
        try {
            if (!$this->isValidProvider($provider)) {
                return $this->respondWithError('Invalid OAuth provider', 400);
            }

            // Log the callback request for debugging
            Log::info('OAuth callback received', [
                'provider' => $provider,
                'query_params' => $request->query(),
                'all_params' => $request->all()
            ]);

            // Check if we have the required parameters
            if (!$request->has('code')) {
                Log::error('OAuth callback missing authorization code', [
                    'provider' => $provider,
                    'params' => $request->all()
                ]);
                return $this->respondWithError('Authorization code not provided', 400);
            }

            if ($request->has('error')) {
                Log::error('OAuth callback received error', [
                    'provider' => $provider,
                    'error' => $request->get('error'),
                    'error_description' => $request->get('error_description')
                ]);
                return $this->respondWithError('OAuth authorization failed: ' . $request->get('error_description', $request->get('error')), 400);
            }

            // Get user from OAuth provider
            try {
                $socialiteUser = \Laravel\Socialite\Facades\Socialite::driver($provider)->user();
                Log::info('Socialite user retrieved successfully', [
                    'provider' => $provider,
                    'user_id' => $socialiteUser->getId(),
                    'email' => $socialiteUser->getEmail(),
                    'name' => $socialiteUser->getName()
                ]);
            } catch (\Exception $socialiteException) {
                Log::error('Socialite user retrieval failed', [
                    'provider' => $provider,
                    'error' => $socialiteException->getMessage(),
                    'trace' => $socialiteException->getTraceAsString()
                ]);
                throw $socialiteException;
            }

            if (!$socialiteUser->getEmail()) {
                return $this->respondWithError('Email is required for registration', 400);
            }

            // Handle OAuth user authentication/registration
            $result = $this->socialAuthService->handleOAuthUser($socialiteUser, $provider);

            if (!$result['success']) {
                return $this->respondWithError($result['message'], 400);
            }

            $user = $result['user'];

            // Start a new session and regenerate the ID
            $request->session()->regenerate();

            // Log the user in using the web guard
            Auth::guard('web')->login($user, true); // Remember the user

            // Create a token for API access
            $token = $user->createToken('oauth_auth_token')->plainTextToken;

            return $this->respondWithSuccess($result['message'], 200, [
                'user' => new UserAccountResource($user),
                'is_new_user' => $result['is_new_user'],
                'oauth_provider' => $user->getProviderDisplayName(),
                'email_verification_required' => false, // OAuth emails are pre-verified
                'token' => $token,
            ]);

        } catch (\Exception $e) {
            Log::error('OAuth callback error', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_params' => $request->all()
            ]);

            return $this->respondWithError('OAuth authentication failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Handle OAuth authentication with token (for mobile/SPA)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticateWithToken(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'provider' => 'required|string|in:google,facebook,apple',
                'access_token' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first(), 422);
            }

            $provider = $request->provider;
            $accessToken = $request->access_token;

            if (!$this->isValidProvider($provider)) {
                return $this->respondWithError('Invalid OAuth provider', 400);
            }

            // Get user from OAuth provider using access token
            $socialiteUser = \Laravel\Socialite\Facades\Socialite::driver($provider)->userFromToken($accessToken);

            if (!$socialiteUser->getEmail()) {
                return $this->respondWithError('Email is required for registration', 400);
            }

            // Handle OAuth user authentication/registration
            $result = $this->socialAuthService->handleOAuthUser($socialiteUser, $provider);

            if (!$result['success']) {
                return $this->respondWithError($result['message'], 400);
            }

            $user = $result['user'];

            // Start a new session and regenerate the ID
            $request->session()->regenerate();

            // Log the user in using the web guard
            Auth::guard('web')->login($user, true); // Remember the user

            // Create a token for API access
            $token = $user->createToken('oauth_auth_token')->plainTextToken;

            return $this->respondWithSuccess($result['message'], 200, [
                'user' => new UserAccountResource($user),
                'is_new_user' => $result['is_new_user'],
                'oauth_provider' => $user->getProviderDisplayName(),
                'email_verification_required' => false, // OAuth emails are pre-verified
                'token' => $token,
            ]);

        } catch (\Exception $e) {
            Log::error('OAuth token authentication error', [
                'provider' => $request->provider ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->respondWithError('OAuth authentication failed', 500);
        }
    }

    /**
     * Unlink OAuth provider from user account
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unlinkProvider(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user->isOAuthUser()) {
                return $this->respondWithError('No OAuth provider linked to this account', 400);
            }

            if (!$user->hasPassword()) {
                return $this->respondWithError('Cannot unlink OAuth provider. Please set a password first.', 400);
            }

            // Clear OAuth data
            $user->update([
                'provider' => null,
                'provider_id' => null,
                'provider_token' => null,
                'provider_refresh_token' => null,
                'provider_token_expires_at' => null,
            ]);

            return $this->respondWithSuccess('OAuth provider unlinked successfully', 200, [
                'user' => new UserAccountResource($user)
            ]);

        } catch (\Exception $e) {
            Log::error('OAuth unlink error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return $this->respondWithError('Failed to unlink OAuth provider', 500);
        }
    }

    /**
     * Get supported OAuth providers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSupportedProviders()
    {
        try {
            $providers = $this->socialAuthService->getSupportedProviders();

            return $this->respondWithSuccess('Supported providers retrieved', 200, [
                'providers' => $providers
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting supported providers', [
                'error' => $e->getMessage()
            ]);

            return $this->respondWithError('Failed to get supported providers', 500);
        }
    }

    /**
     * Check if provider is valid
     *
     * @param string $provider
     * @return bool
     */
    private function isValidProvider(string $provider): bool
    {
        return in_array($provider, ['google', 'facebook', 'apple']);
    }
}
