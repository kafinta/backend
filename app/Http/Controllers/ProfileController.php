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
use App\Services\FileService;

class ProfileController extends ImprovedController
{
    protected $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }
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
     * This endpoint expects a PUT request with JSON data containing:
     * - first_name (required): The user's first name
     * - last_name (required): The user's last name
     * - biography (optional): The user's biography
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();

            // Log the request for debugging
            Log::info('Profile update request', [
                'content_type' => $request->header('Content-Type'),
                'has_first_name' => $request->has('first_name'),
                'has_last_name' => $request->has('last_name'),
                'all_data' => $request->all()
            ]);

            // Create validator manually to get more control
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'biography' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError('Validation failed: ' . $validator->errors()->first(), 422);
            }

            $validatedData = $validator->validated();

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
     * This endpoint expects a POST request with multipart/form-data containing:
     * - profile_picture (required): An image file (jpeg, png, jpg, gif) max 2MB
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadProfilePicture(Request $request)
    {
        try {
            $user = auth()->user();

            // Log the request for debugging
            Log::info('Profile picture upload request', [
                'content_type' => $request->header('Content-Type'),
                'has_file' => $request->hasFile('profile_picture')
            ]);

            // Validate the request
            $validator = Validator::make($request->all(), [
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError('Validation failed: ' . $validator->errors()->first(), 422);
            }

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

            // Use the FileService to upload the profile picture
            $filePath = $this->fileService->uploadFile(
                $request->file('profile_picture'),
                'profile-pictures'
            );

            if (!$filePath) {
                return $this->respondWithError('Failed to upload profile picture', 500);
            }

            // Delete old profile picture if it exists
            if ($user->profile->profile_picture) {
                $this->fileService->deleteFile($user->profile->profile_picture);
                Log::info('Deleted old profile picture', [
                    'user_id' => $user->id,
                    'old_path' => $user->profile->profile_picture
                ]);
            }

            // Update the profile with the new image path
            $user->profile->update([
                'profile_picture' => $filePath
            ]);

            return $this->respondWithSuccess('Profile picture uploaded successfully', 200, [
                'profile_picture' => $filePath
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