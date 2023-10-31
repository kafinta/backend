<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

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
        $user = auth()->user();

        $data = $user->profile;
        return response()->json([
            'status' => 'success',
            'message' => 'Profile fetched successfully',
            'data' => $data
        ], 200);
    }

    public function updateProfile(Request $request, Cloudinary $cloudinary)
    {


        $user = auth()->user();
    
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'biography' => 'nullable|string',
            'is_seller' => 'nullable|boolean',
            'profile_picture' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);
    
        $user->profile->update($validatedData);
    
        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
        ]);
    }
}
