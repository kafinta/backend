<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function createProfile(){
        $user = auth()->user();

        $profile = new Profile();

        $profileData = [
            'first_name' => '',
            'last_name' => '',
            'profile_picture' => '',
            'biography' => '',
            'is_seller' => false
        ];

        $profile = $user->profile()->create($profileData);

        $profile->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Profile created successfully',
        ], 200);
    }

    public function getProfile(){
        \Log::info('Request Headers: ' . json_encode(request()->headers->all()));
        \Log::info('Request Details: ' . json_encode(request()->all()));
        $user = auth()->user();
        \Log::info('Authenticated User: ' . json_encode($user));

        $data = [$user->profile, 'username'=>$user->username];
        return response()->json([
            'status' => 'success',
            'message' => 'Profile fetched successfully',
            'data' => $data
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();
    
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'biography' => 'nullable|string',
            'is_seller' => 'nullable|boolean',
        ]);
    
        $user->profile->update([$validatedData, 'is_seller'=> 'true']);
    
        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
        ]);
    }
}