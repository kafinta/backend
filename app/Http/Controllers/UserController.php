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


class UserController extends ImprovedController
{
    use ReferenceGeneratorTrait;
    protected $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
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

            DB::commit();

            $token = $user->createToken('auth_token')->plainTextToken;
            return $this->respondWithSuccess("Account Created Successfully", 200, [
                'account' => new UserAccountResource($user),
                'auth_token' => $token,
                'token_type' => 'Bearer'
            ]);

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


            $tokenExpiration = $request->remember_me ? now()->addDays(30) : now()->addDay();
            $token = $user->createToken('auth_token', ['*'], $tokenExpiration)->plainTextToken;

            return $this->respondWithSuccess("Login successful", 200, [
                'account' => new UserAccountResource($user),
                'auth_token' => $token,
                'token_type' => 'Bearer'
            ]);

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
        $request->user()->currentAccessToken()->delete();
        return $this->respondWithSuccess("Logout successful", 200);
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
}