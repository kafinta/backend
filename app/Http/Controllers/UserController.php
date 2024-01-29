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

    public function register(Request $request){
        try{
            //Validate the user input
            $validator = $this->validateUserInfo();

            if($validator->fails()){
                return $this->respondWithValidationError($validator->messages()->first(), 422);
            }

            DB::beginTransaction();

            //Create a User
            $createUser  = User::create([
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
            ]);


            if($createUser){
                DB::commit();

                $data = [
                    'token' => $createUser->createToken('UserAuthToken')->plainTextToken,
                    'token_type' => "Bearer"
                ];

                return $this->respondWithSuccess("User Account Registered Successfully", 200, $data);
            }else {
                DB::rollBack();
                return $this->respondWithError("Something went wrong", 503);
            }
        }
        catch (\Exception $e){
            return $this->exceptionError($e->getMessage(), 500);
        }
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

    public function tokenLogin(Request $request)
    {
        try {
            //Validate the user input
            $validator = $this->validateUserInfo();

            if($validator->fails()){
                return $this->respondWithValidationError($validator->messages()->first(),422);
            }

            $user = Student::where('email', $request->email)->first();

            //Check if the email exist
            if($user){
                $checkIfPasswordMatch = Hash::check($request->password, $user->password, []);
                if (!$checkIfPasswordMatch) {
                    return $this->respondWithError("Email or Password does not match our record",403);
                }
                else {
                    $tokenResult = $user->createToken('UserAuthToken')->plainTextToken;

                    $data = [
                        'token' =>  $tokenResult,
                        'token_type' => 'Bearer'
                    ];

                    return $this->respondWithSuccess("User Logged in Successfully", 200, $data);
                }

            }else{
                return $this->respondWithError("Email or Password is Incorrect", 403);
            }

        } catch (\Exception $error) {
            return $this->exceptionError($error->getMessage(), 500);
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