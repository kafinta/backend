<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ImprovedController;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class ProfileController extends ImprovedController
{
    public function createProfile(Request $request){
        try {
            $user = auth()->user();

            $validatedData = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'biography' => 'nullable|string',
                'profile_picture' => 'nullable|string'
            ]);

            // Check if profile already exists
            if ($user->profile) {
                return $this->respondWithError('Profile already exists. Use updateProfile instead.', 400);
            }

            $profile = $user->profile()->create($validatedData);

            return $this->respondWithSuccess('Profile created successfully', 201, $profile);
        } catch (\Exception $e) {
            return $this->respondWithError('Error creating profile: ' . $e->getMessage(), 500);
        }
    }

    public function getProfile(){
        try {
            $user = auth()->user();

            // Check if profile exists
            if (!$user->profile) {
                return $this->respondWithError('Profile not found. Please create a profile first.', 404);
            }

            $data = [
                'profile' => $user->profile,
                'username' => $user->username,
                'email' => $user->email
            ];

            return $this->respondWithSuccess('Profile fetched successfully', 200, $data);
        } catch (\Exception $e) {
            return $this->respondWithError('Error fetching profile: ' . $e->getMessage(), 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();

            $validatedData = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'biography' => 'nullable|string',
                'profile_picture' => 'nullable|string',
            ]);

            // Check if profile exists
            if (!$user->profile) {
                // Create profile if it doesn't exist
                $profile = $user->profile()->create($validatedData);
                return $this->respondWithSuccess('Profile created successfully', 201, $profile);
            }

            // Update existing profile
            $user->profile->update($validatedData);

            return $this->respondWithSuccess('Profile updated successfully', 200, $user->profile);
        } catch (\Exception $e) {
            return $this->respondWithError('Error updating profile: ' . $e->getMessage(), 500);
        }
    }
}