<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ImprovedController;
use App\Models\Role;
use App\Models\Seller;
use App\Models\User;
use App\Services\SellerVerificationService;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SellerVerificationController extends ImprovedController
{
    protected $verificationService;
    protected $fileService;

    public function __construct(SellerVerificationService $verificationService, FileService $fileService)
    {
        $this->middleware(['auth:sanctum']);
        $this->verificationService = $verificationService;
        $this->fileService = $fileService;
    }

    /**
     * Verify seller's email
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email,' . auth()->id(),
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first(), 422);
            }

            $result = $this->verificationService->verifyEmail(auth()->id(), $request->email);
            
            return $this->respondWithSuccess('Email verification initiated', 200, [
                'progress' => $result['progress'],
                'next_steps' => $result['next_steps']
            ]);
        } catch (\Exception $e) {
            Log::error('Email verification error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return $this->respondWithError('Error verifying email: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify seller's phone number
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPhone(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'phone_number' => 'required|string',
                'verification_code' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first(), 422);
            }

            $result = $this->verificationService->verifyPhone(
                auth()->id(), 
                $request->phone_number,
                $request->verification_code
            );
            
            return $this->respondWithSuccess('Phone number verified successfully', 200, [
                'progress' => $result['progress'],
                'next_steps' => $result['next_steps']
            ]);
        } catch (\Exception $e) {
            Log::error('Phone verification error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return $this->respondWithError('Error verifying phone: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update seller's business profile
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'business_name' => 'required|string|max:255',
                'business_description' => 'nullable|string',
                'business_address' => 'required|string',
                'phone_number' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first(), 422);
            }

            $profileData = $request->only([
                'business_name',
                'business_description',
                'business_address',
                'phone_number'
            ]);

            $result = $this->verificationService->updateProfile(auth()->id(), $profileData);
            
            return $this->respondWithSuccess('Business profile updated successfully', 200, [
                'seller' => $result['seller'],
                'progress' => $result['progress'],
                'next_steps' => $result['next_steps']
            ]);
        } catch (\Exception $e) {
            Log::error('Profile update error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return $this->respondWithError('Error updating profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify seller's KYC documents
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyKYC(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_type' => 'required|in:passport,national_id,nin',
                'id_number' => 'required|string',
                'id_document' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first(), 422);
            }

            // Handle file upload
            $documentPath = null;
            if ($request->hasFile('id_document')) {
                $documentPath = $this->fileService->uploadFile(
                    $request->file('id_document'),
                    'seller-documents'
                );

                if (!$documentPath) {
                    return $this->respondWithError('Failed to upload document', 500);
                }
            }

            $kycData = [
                'id_type' => $request->id_type,
                'id_number' => $request->id_number,
                'id_document' => $documentPath
            ];

            $result = $this->verificationService->verifyKYC(auth()->id(), $kycData);
            
            return $this->respondWithSuccess('KYC verification submitted successfully', 200, [
                'progress' => $result['progress'],
                'next_steps' => $result['next_steps']
            ]);
        } catch (\Exception $e) {
            Log::error('KYC verification error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return $this->respondWithError('Error verifying KYC: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Complete the seller onboarding process
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function completeOnboarding(Request $request)
    {
        try {
            $result = $this->verificationService->completeOnboarding(auth()->id());
            
            return $this->respondWithSuccess('Seller onboarding completed successfully', 200, [
                'seller' => $result['seller'],
                'is_seller' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Onboarding completion error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return $this->respondWithError('Error completing onboarding: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get seller onboarding progress
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProgress()
    {
        try {
            $result = $this->verificationService->getProgress(auth()->id());
            
            return $this->respondWithSuccess('Onboarding progress retrieved', 200, $result);
        } catch (\Exception $e) {
            Log::error('Progress retrieval error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return $this->respondWithError('Error retrieving progress: ' . $e->getMessage(), 500);
        }
    }
}
