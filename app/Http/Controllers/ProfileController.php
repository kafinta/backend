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
    
        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
    
            // Check if the file exists
            if (!file_exists($file->getPathname())) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The profile picture file does not exist.',
                ]);
            }
    
            // Check if the file is empty
            if (filesize($file->getPathname()) === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The profile picture file is empty.',
                ]);
            }
    
            try {
                // Upload the file to Cloudinary
                $uploadedFile = $cloudinary->uploadApi()->upload($file->getPathname());
                // $uploadedFileUrl = Cloudinary::upload($file->getRealPath())->getSecurePath();
    
                // Save the Cloudinary public ID to the profiles's profile_picture column
                $profile->profile_picture = $uploadedFileUrl['public_id'];
            } catch (CloudinaryApiException $e) {
                // Handle the Cloudinary API error here
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getHttpStatusCode() . ': ' . $e->getMessage(),
                ]);
            } catch (Exception $e) {
                // Handle the general error here
                return response()->json([
                    'status' => 'error',
                    'message' => 'Image upload failed',
                ]);
            }
        }
    
        $user->profile->update($validatedData);
    
        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
        ]);
    }
}
