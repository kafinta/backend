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
            
            $token = $user->createToken('auth_token')->plainTextToken;
            Log::info('Token generated', [
                'user_id' => $user->id,
                'token_type' => explode('|', $token)[0],
                'token_length' => strlen($token)
            ]);
            return response()->json([
                'message' => "Account Created Successfully",
                'data' => [
                    'account' => new UserAccountResource($user),
                    'access_token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->exceptionError($e->getMessage(), 500);
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
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => "Account Logged In Successfully",
                'data' => [
                    'account' => new UserAccountResource($user),
                    'auth_token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 200);

        } catch (\Exception $e) {
            return $this->exceptionError($e->getMessage(), 500);
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
}