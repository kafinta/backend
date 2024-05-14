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

class UserController extends ImprovedController
{
    use ReferenceGeneratorTrait;

    public function signup(Request $request){


        $validator = $this->validateUserInfo();

        if($validator->fails()){
            return $this->respondWithValidationError($validator->messages()->first(), 422);
        }

        $createUser = User::create([
            'email' => $request->email,
            'username' => $request->username,
            'password' => Hash::make($request->password),
        ]);

        if (Auth::guard('users-web')->attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = auth()->guard('users-web')->user();
            
            $dataToReturn = [
                'account' => new UserAccountResource($user),
            ]; 
    
            $request->session()->regenerate();
            return response()->json([
                'message' => "Account Created Successfully",
                'data' => $dataToReturn
            ], 200);
        }
        return $this->respondWithError("Unable to register now", 403);

    }

    public function login(Request $request)
    {

        try {
            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);

            if (!$credentials) {
                return $this->respondWithError("Email or Password is Required", 422);
            }

            if (Auth::guard('users-web')->attempt($credentials)) {
                $user = auth()->guard('users-web')->user();
                
                $dataToReturn = [
                    'account' => new UserAccountResource($user),
                ]; 
        
                $request->session()->regenerate();
                return response()->json([
                    'message' => "Account Logged In Successfully",
                    'data' => $dataToReturn
                ], 200);
            }
            return $this->respondWithError("Email or Password is Incorrect", 403);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
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