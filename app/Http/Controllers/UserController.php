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

            DB::commit();
            
            $token = $user->createToken('auth_token', ['*'])->plainTextToken;
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
            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);

            if (!Auth::attempt($credentials)) {
                return $this->respondWithError("Email or Password is Incorrect", 401);
            }

            $user = User::where('email', $request->email)->firstOrFail();
            $token = $user->createToken('auth_token', ['*'])->plainTextToken;

            return $this->respondWithSuccess("Account Logged In Successfully", 200, [
                'account' => new UserAccountResource($user),
                'auth_token' => $token,
                'token_type' => 'Bearer'
            ]);

        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage(), 500);
        }
    }

    public function validateUserInfo()
    {
        return Validator::make(request()->all(), [
            'email' => 'required|unique:users',
            'username' => 'required|unique:users',
            'password' => 'required|min:8'
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