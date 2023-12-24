<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Http\Resources\UserAccountResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function signup(Request $request){

        $validator = $this->validateNewUser();

        if ($validator->fails()) {
            return response()-> json([
                'status' => 'fail',
                'message' => $validator->messages(),
            ], 422);
        }

        $createUser = User::create([
            'email' => $request->email,
            'username' => $request->username,
            'password' => Hash::make($request->password),
        ]);

        $request->session()->regenerate();

        return response()->json([
            'status' => 'success',
            'message' => 'Registration completed successfully.',
            // 'data' => $data
        ], 200);
    }

    public function spaLogin(Request $request)
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

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt to authenticate the user

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $data = [
                'token' => auth()->user()->createToken('UserAuthToken')->plainTextToken,
                'token_type' => 'Bearer',
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'User authentication successful',
                'data' => $data
            ], 200);
        }

        return response()-> json([
            'status' => 'fail',
            'message' => 'Invalid credentials',
        ], 422);
    }

    public function validateNewUser()
    {
        return Validator::make(request()->all(), [
            'email' => 'required|unique:users',
            'username' => 'required|unique:users',
            'password' => 'required|min:8'
        ]);
    }
}