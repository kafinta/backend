<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Http\Controllers\Controller;
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

class UserController extends ImprovedController
{
    use ReferenceGeneratorTrait;

    public function register(Request $request)
    {
        try {

            $validator = $this->validateUserInfo();

            if ($validator->fails()) {
                return $this->respondWithValidationError($validator->messages()->first(), 422);
            }

            DB::beginTransaction();

            $user = User::create([
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
            ]);

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
            'password' => 'required|min:8'
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
}