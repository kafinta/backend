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

    public function validateParentSchoolLogin() {

    }


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

        $data = [
            'token' => $createUser->createToken('UserAuthToken')->plainTextToken,
            'token_type' => 'Bearer'
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'New user created successfully',
            'data' => $data
        ], 200);

    }

    public function spaLogin(Request $request)
    {
        try {


            // $validator = $this->validateParentSchoolLogin();

            // if ($validator->fails()) {
            //     return $this->respondWithError($validator->messages()->first(), 422);
            // }

            $credentials = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);

            if (!$credentials) {
                return $this->respondWithError("Email and Password is Required", 422);
            }

            if (Auth::guard('users-web')->attempt($credentials)) {

                $user = auth()->guard('users-web');


                $dataToReturn = [
                    'account' => new UserAccountResource($user),
                ];

                $request->session()->regenerate();
                return $this->respondWithSuccess("Account Logged In Successfully", 200, $dataToReturn);
            }
            return $this->respondWithError("Email or Password is Incorrect", 403);
        } catch (\Exception $e) {
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

    public function getAccountDetails(){
        $data = Auth::user();
        return $data;
    }

    public function updateProfile(Request $request, Cloudinary $cloudinary)
    {
        $user = Auth::user();

        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_seller' => 'boolean', // Assuming is_seller is a boolean field
            'profile_picture' => 'image|mimes:jpeg,png,jpg|max:2048', // Adjust validation rules for the profile picture
        ]);

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $uploadedFile = $cloudinary->uploadApi()->upload($file->getPathname());

            // Save the Cloudinary public ID to the user's profile_picture column
            $user->profile_picture = $uploadedFile['public_id'];
        }

        $user->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully'
        ]);

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