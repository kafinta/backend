<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Http\Controllers\ImprovedController;
use App\Http\Resources\UserAccountResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Traits\ReferenceGeneratorTrait;
use Illuminate\Support\Facades\Log;
use App\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\FileService;
use App\Services\EmailService;


class UserController extends ImprovedController
{
    use ReferenceGeneratorTrait;
    protected $fileService;
    protected $emailService;

    public function __construct(FileService $fileService, EmailService $emailService)
    {
        $this->fileService = $fileService;
        $this->emailService = $emailService;
    }

    public function register(Request $request)
    {
        try {

            $validator = $this->validateUserInfo();

            if ($validator->fails()) {
                return $this->respondWithValidationError($validator->messages()->first(), 422);
            }

            DB::beginTransaction();

            $userData = [
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
            ];

            // Add phone number if provided
            if ($request->has('phone_number')) {
                $userData['phone_number'] = $request->phone_number;
            }

            $user = User::create($userData);

            // Assign default role to user (customer by default)
            $defaultRole = Role::where('slug', 'customer')->first();
            if ($defaultRole) {
                $user->roles()->attach($defaultRole->id);
            }

            // Generate verification token and send email
            $tokenData = $this->emailService->generateVerificationToken($user);
            $emailSent = $this->emailService->sendVerificationEmail(
                $user,
                $tokenData['verification_url'],
                $tokenData['verification_code']
            );

            DB::commit();

            // Create a token but don't return it (for backward compatibility)
            $user->createToken('auth_token')->plainTextToken;

            // Create a response with cookie
            $response = $this->respondWithSuccess("Account Created Successfully", 200, [
                'user' => new UserAccountResource($user),
                'email_verification_required' => true,
                'verification_email_sent' => $emailSent,
            ]);

            // Set cookie for authentication
            $response->cookie('laravel_session', session()->getId(), 60 * 24, null, null, true, true, false, 'lax');

            return $response;

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respondWithError($e->getMessage(), 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = $this->validateLoginCredentials();

            if ($validator->fails()) {
                return $this->respondWithValidationError($validator->messages()->first(), 422);
            }

            $throttleKey = Str::lower($request->email) . '|' . $request->ip();

            if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                $seconds = RateLimiter::availableIn($throttleKey);
                return $this->respondWithError(
                    "Too many login attempts. Please try again in {$seconds} seconds.",
                    429
                );
            }

            $credentials = $request->only('email', 'password');
            $user = User::where('email', $credentials['email'])->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                RateLimiter::hit($throttleKey, 60); // Add to rate limiter
                return $this->respondWithError("Invalid credentials", 401);
            }

            // Reset rate limiter on successful login
            RateLimiter::clear($throttleKey);


            // Create a token but don't return it (for backward compatibility)
            $tokenExpiration = $request->remember_me ? now()->addDays(30) : now()->addDay();
            $user->createToken('auth_token', ['*'], $tokenExpiration)->plainTextToken;

            // Set session lifetime based on remember_me
            $minutes = $request->remember_me ? 60 * 24 * 30 : 60 * 24;
            config(['session.lifetime' => $minutes]);

            // Create a response with cookie
            $response = $this->respondWithSuccess("Login successful", 200, [
                'user' => new UserAccountResource($user),
            ]);

            // Set cookie for authentication
            $response->cookie('laravel_session', session()->getId(), $minutes, null, null, true, true, false, 'lax');

            return $response;

        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage(), 500);
        }
    }

    public function uploadProfilePicture(Request $request)
    {
        try {
            $user = auth()->user();

            // Log the request for debugging
            Log::info('Profile picture upload request', [
                'content_type' => $request->header('Content-Type'),
                'has_file' => $request->hasFile('profile_picture')
            ]);

            // Validate the request
            $validator = Validator::make($request->all(), [
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError('Validation failed: ' . $validator->errors()->first(), 422);
            }

            // Use the FileService to upload the profile picture
            $filePath = $this->fileService->uploadFile(
                $request->file('profile_picture'),
                'profile-pictures'
            );

            if (!$filePath) {
                return $this->respondWithError('Failed to upload profile picture', 500);
            }

            // Delete old profile picture if it exists
            if ($user->profile_picture) {
                $this->fileService->deleteFile($user->profile_picture);
                Log::info('Deleted old profile picture', [
                    'user_id' => $user->id,
                    'old_path' => $user->profile_picture
                ]);
            }

            // Update the profile with the new image path
            $user->update([
                'profile_picture' => $filePath
            ]);

            return $this->respondWithSuccess('Profile picture uploaded successfully', 200, [
                'profile_picture' => $filePath
            ]);

        } catch (\Exception $e) {
            Log::error('Error uploading profile picture', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return $this->respondWithError('Error uploading profile picture: ' . $e->getMessage(), 500);
        }
    }

    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        // Invalidate the session
        $request->session()->invalidate();

        // Regenerate the CSRF token
        $request->session()->regenerateToken();

        // Create a response
        $response = $this->respondWithSuccess("Logout successful", 200);

        // Clear the cookie
        $response->cookie('laravel_session', '', -1);

        return $response;
    }

    public function validateUserInfo()
    {
        return Validator::make(request()->all(), [
            'email' => 'required|unique:users',
            'username' => 'required|unique:users',
            'password' => 'required|min:8',
            'phone_number' => 'nullable|string|max:20'
        ]);
    }

    protected function validateLoginCredentials()
    {
        return Validator::make(request()->all(), [
            'email' => ['required', 'email'],
            'password' => ['required'],
            'remember_me' => ['sometimes', 'boolean']
        ]);
    }

    public function rotateToken(Request $request)
    {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        // Create new token
        $token = $request->user()->createToken('auth_token', ['*'])->plainTextToken;
        return $this->respondWithSuccess('Token rotated successfully', 200, [
            'auth_token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    /**
     * Update user profile information
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();

            // Validate the request
            $validator = Validator::make($request->all(), [
                'username' => 'sometimes|required|string|max:255|unique:users,username,' . $user->id,
                'phone_number' => 'nullable|string|max:20',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError('Validation failed: ' . $validator->errors()->first(), 422);
            }

            // Update user data
            $userData = [];

            if ($request->has('username')) {
                $userData['username'] = $request->username;
            }

            if ($request->has('phone_number')) {
                $userData['phone_number'] = $request->phone_number;
            }

            if (!empty($userData)) {
                $user->update($userData);
            }

            return $this->respondWithSuccess('Profile updated successfully', 200, [
                'user' => new UserAccountResource($user)
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating profile', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return $this->respondWithError('Error updating profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get user profile information
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfile()
    {
        try {
            $user = auth()->user();

            return $this->respondWithSuccess('Profile retrieved successfully', 200, [
                'user' => new UserAccountResource($user)
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving profile', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return $this->respondWithError('Error retrieving profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get user roles
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRoles()
    {
        try {
            $user = auth()->user();
            $roles = $user->roles()->get(['roles.id', 'roles.name', 'roles.slug'])->makeHidden(['pivot']);

            return $this->respondWithSuccess('User roles retrieved successfully', 200, [
                'roles' => $roles
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving user roles', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return $this->respondWithError('Error retrieving user roles: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Resend verification email
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resendVerificationEmail(Request $request)
    {
        try {
            $user = auth()->user();

            // Check if email is already verified
            if ($user->email_verified_at) {
                return $this->respondWithSuccess('Email already verified', 200);
            }

            // Generate new token and send email
            $result = $this->emailService->resendVerificationEmail($user);

            if (!$result['success']) {
                return $this->respondWithError($result['message'], 500);
            }

            return $this->respondWithSuccess('Verification email sent successfully', 200, [
                'verification_email_sent' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Error resending verification email', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return $this->respondWithError('Error resending verification email: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify email with token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyEmailToken(Request $request)
    {
        try {
            // Get token from request parameters or route parameter
            $token = $request->token ?? $request->route('token');

            if (!$token) {
                return $this->respondWithError('Verification token is required', 400);
            }

            // Verify the token
            $result = $this->emailService->verifyToken($token);

            if (!$result['success']) {
                return $this->respondWithError($result['message'], 400);
            }

            return $this->respondWithSuccess('Email verified successfully', 200, [
                'email_verified' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Email token verification error', [
                'error' => $e->getMessage(),
                'token' => $request->token ?? $request->route('token') ?? null
            ]);

            return $this->respondWithError('Error verifying email token: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check email verification status
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkEmailVerification()
    {
        try {
            $user = auth()->user();

            return $this->respondWithSuccess('Email verification status retrieved', 200, [
                'email_verified' => $user->email_verified_at !== null,
                'email' => $user->email
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking email verification status', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return $this->respondWithError('Error checking email verification status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify email with verification code
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyEmailCode(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'required|string|size:6'
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first(), 422);
            }

            // Verify the code without requiring email
            $result = $this->emailService->verifyCodeOnly($request->code);

            if (!$result['success']) {
                return $this->respondWithError($result['message'], 400);
            }

            return $this->respondWithSuccess('Email verified successfully', 200, [
                'email_verified' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Email code verification error', [
                'error' => $e->getMessage(),
                'code' => $request->code ?? null
            ]);

            return $this->respondWithError('Error verifying email code: ' . $e->getMessage(), 500);
        }
    }
}