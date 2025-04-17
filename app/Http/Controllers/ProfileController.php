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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProfileController extends ImprovedController
{
    /**
     * Get the authenticated user's profile
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfile()
    {
        try {
            $user = auth()->user();

            // Check if profile exists
            if (!$user->profile) {
                return $this->respondWithError('Profile not found. Please create a profile first.', 404);
            }

            // Check if this is an empty profile (automatically created during registration)
            $isEmptyProfile = empty($user->profile->first_name) && empty($user->profile->last_name);
            $profileStatus = $isEmptyProfile ? 'empty' : 'complete';

            $data = [
                'profile' => $user->profile,
                'username' => $user->username,
                'email' => $user->email,
                'profile_status' => $profileStatus
            ];

            $message = $isEmptyProfile ?
                'Profile exists but is empty. Please complete your profile.' :
                'Profile fetched successfully';

            return $this->respondWithSuccess($message, 200, $data);
        } catch (\Exception $e) {
            return $this->respondWithError('Error fetching profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the authenticated user's profile
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();

            $validatedData = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'biography' => 'nullable|string',
            ]);

            // Check if profile exists
            if (!$user->profile) {
                // Create profile if it doesn't exist
                $profile = $user->profile()->create($validatedData);
                return $this->respondWithSuccess('Profile created successfully', 201, $profile);
            }

            // Check if this is an empty profile being completed for the first time
            $isEmptyProfile = empty($user->profile->first_name) && empty($user->profile->last_name);

            // Update existing profile
            $user->profile->update($validatedData);

            $message = $isEmptyProfile ?
                'Profile completed successfully' :
                'Profile updated successfully';

            return $this->respondWithSuccess($message, 200, $user->profile);
        } catch (\Exception $e) {
            return $this->respondWithError('Error updating profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Upload a profile picture
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadProfilePicture(Request $request)
    {
        try {
            $user = auth()->user();

            // Validate the request
            $request->validate([
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Check if profile exists
            if (!$user->profile) {
                // Create an empty profile if it doesn't exist
                $user->profile()->create([
                    'first_name' => '',
                    'last_name' => ''
                ]);

                Log::info('Created empty profile during profile picture upload', [
                    'user_id' => $user->id
                ]);
            }

            // Get the image file
            $image = $request->file('profile_picture');

            // Generate a unique filename
            $filename = 'profile_' . $user->id . '_' . time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();

            // Store the image
            $path = $image->storeAs('profile-pictures', $filename, 'public');

            // Delete old profile picture if it exists
            if ($user->profile->profile_picture) {
                $oldPath = str_replace('/storage/', '', $user->profile->profile_picture);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                    Log::info('Deleted old profile picture', [
                        'user_id' => $user->id,
                        'old_path' => $oldPath
                    ]);
                }
            }

            // Update the profile with the new image path
            $user->profile->update([
                'profile_picture' => '/storage/' . $path
            ]);

            return $this->respondWithSuccess('Profile picture uploaded successfully', 200, [
                'profile_picture' => '/storage/' . $path
            ]);

        } catch (\Exception $e) {
            Log::error('Error uploading profile picture', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return $this->respondWithError('Error uploading profile picture: ' . $e->getMessage(), 500);
        }
    }
}