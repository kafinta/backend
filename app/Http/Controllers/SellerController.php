<?php

namespace App\Http\Controllers;
use App\Http\Controllers\ImprovedController;

use App\Models\Role;
use App\Models\Seller;
use App\Services\FileService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Controller for handling seller registration and management
 *
 * This controller provides endpoints for the step-by-step seller onboarding process,
 * including phone verification, business profile updates, and KYC verification.
 */
class SellerController extends ImprovedController
{
    protected $fileService;
    protected $emailService;

    public function __construct(FileService $fileService, EmailService $emailService)
    {
        $this->middleware(['auth:sanctum']);
        $this->fileService = $fileService;
        $this->emailService = $emailService;
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

            // Get or create seller record
            $seller = $this->getOrCreateSeller(auth()->id());

            // In a real implementation, you would verify the code against a previously sent SMS
            // For now, we'll just assume the verification is successful if the code is "123456"
            if ($request->verification_code !== "123456") {
                return $this->respondWithError('Invalid verification code', 422);
            }

            // Update seller's phone number
            $seller->phone_number = $request->phone_number;
            $seller->phone_verified_at = now();
            $seller->save();

            // Update user's phone number if needed
            $user = auth()->user();
            if ($user->phone_number !== $request->phone_number) {
                $user->phone_number = $request->phone_number;
                $user->save();
            }

            // Calculate progress
            $progress = $this->calculateProgress($seller);

            return $this->respondWithSuccess('Phone number verified successfully', 200, [
                'progress' => $progress,
                'next_steps' => $this->getNextSteps($seller)
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

            // Get or create seller record
            $seller = $this->getOrCreateSeller(auth()->id());

            // Update seller profile
            $seller->business_name = $request->business_name;
            $seller->business_description = $request->business_description;
            $seller->business_address = $request->business_address;

            // Only update phone if not already verified
            if (!$seller->phone_verified_at) {
                $seller->phone_number = $request->phone_number;
            }

            // Mark profile as completed
            $seller->profile_completed_at = now();
            $seller->save();

            // Calculate progress
            $progress = $this->calculateProgress($seller);

            return $this->respondWithSuccess('Business profile updated successfully', 200, [
                'seller' => $seller,
                'progress' => $progress,
                'next_steps' => $this->getNextSteps($seller)
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

            // Get or create seller record
            $seller = $this->getOrCreateSeller(auth()->id());

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

            // Update KYC information
            $seller->id_type = $request->id_type;
            $seller->id_number = $request->id_number;
            $seller->id_document = $documentPath;

            // Mark KYC as verified
            $seller->kyc_verified_at = now();
            $seller->save();

            // Calculate progress
            $progress = $this->calculateProgress($seller);

            return $this->respondWithSuccess('KYC verification submitted successfully', 200, [
                'progress' => $progress,
                'next_steps' => $this->getNextSteps($seller)
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function completeOnboarding()
    {
        try {
            // Get seller record
            $seller = Seller::where('user_id', auth()->id())->first();

            if (!$seller) {
                return $this->respondWithError('Seller profile not found', 404);
            }

            // Check if all required steps are completed
            if (!$this->canCompleteOnboarding($seller)) {
                return $this->respondWithError('All verification steps must be completed before finalizing onboarding', 422);
            }

            // Mark onboarding as completed
            $seller->onboarding_completed_at = now();
            $seller->onboarding_progress = 100;
            $seller->is_verified = true;
            $seller->save();

            // Assign seller role to user
            $sellerRole = Role::where('slug', 'seller')->first();

            if (!$sellerRole) {
                return $this->respondWithError('Seller role not found', 500);
            }

            auth()->user()->roles()->syncWithoutDetaching([$sellerRole->id]);

            return $this->respondWithSuccess('Seller onboarding completed successfully', 200, [
                'seller' => $seller,
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
            // Get or create seller record
            $seller = $this->getOrCreateSeller(auth()->id());

            // Calculate progress
            $progress = $this->calculateProgress($seller);

            return $this->respondWithSuccess('Onboarding progress retrieved', 200, [
                'progress' => $progress,
                'completed_steps' => $this->getCompletedSteps($seller),
                'next_steps' => $this->getNextSteps($seller),
                'can_complete' => $this->canCompleteOnboarding($seller)
            ]);
        } catch (\Exception $e) {
            Log::error('Progress retrieval error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return $this->respondWithError('Error retrieving progress: ' . $e->getMessage(), 500);
        }
    }



    public function show(Seller $seller)
    {
        try {
            // Check if user has permission to view this seller
            if (!auth()->user()->hasRole('admin') && auth()->id() !== $seller->user_id) {
                return $this->respondWithError('Unauthorized access', 403);
            }

            // Load relationships
            $seller->load(['user:id,email']);

            return $this->respondWithSuccess('Seller profile retrieved', 200, $seller);

        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage(), 500);
        }
    }

    public function downloadDocument(Seller $seller)
    {
        try {
            // Check if user has permission to download document
            if (!auth()->user()->hasRole('admin') && auth()->id() !== $seller->user_id) {
                return $this->respondWithError('Unauthorized access', 403);
            }

            // Check if document exists
            if (!$seller->id_document) {
                return $this->respondWithError('No document found', 404);
            }

            // Get file info using the FileService
            $fileInfo = $this->fileService->getFileInfo($seller->id_document);

            if (!$fileInfo) {
                return $this->respondWithError('Document file not found', 404);
            }

            // Get the path without /storage/ prefix for proper file access
            $path = str_replace('/storage/', '', $seller->id_document);

            // Get file mime type from file info
            $mimeType = $fileInfo['mime_type'];

            // Generate a clean filename for download
            $filename = sprintf(
                '%s_ID_%s.%s',
                Str::slug($seller->business_name),
                $seller->id_type,
                pathinfo($path, PATHINFO_EXTENSION)
            );

            // Return file download response
            return Storage::disk('public')->download(
                $path,
                $filename,
                ['Content-Type' => $mimeType]
            );

        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage(), 500);
        }
    }

    /**
     * Get or create a seller record for a user
     *
     * @param int $userId
     * @return Seller
     */
    protected function getOrCreateSeller(int $userId): Seller
    {
        $seller = Seller::where('user_id', $userId)->first();

        if (!$seller) {
            $seller = Seller::create([
                'user_id' => $userId,
                'business_name' => '',
                'business_address' => '',
                'phone_number' => '',
                'id_type' => '',
                'id_number' => '',
                'onboarding_progress' => 0
            ]);
        }

        return $seller;
    }

    /**
     * Calculate seller onboarding progress
     *
     * @param Seller $seller
     * @return int
     */
    protected function calculateProgress(Seller $seller): int
    {
        $totalSteps = 4; // Email, Phone, Profile, KYC
        $completedSteps = 0;

        if ($seller->email_verified_at) $completedSteps++;
        if ($seller->phone_verified_at) $completedSteps++;
        if ($seller->profile_completed_at) $completedSteps++;
        if ($seller->kyc_verified_at) $completedSteps++;

        $progress = (int)(($completedSteps / $totalSteps) * 100);

        // Update progress in database
        $seller->onboarding_progress = $progress;
        $seller->save();

        return $progress;
    }

    /**
     * Check if all required steps are completed
     *
     * @param Seller $seller
     * @return bool
     */
    protected function canCompleteOnboarding(Seller $seller): bool
    {
        return $seller->email_verified_at &&
               $seller->phone_verified_at &&
               $seller->profile_completed_at &&
               $seller->kyc_verified_at;
    }

    /**
     * Get completed steps
     *
     * @param Seller $seller
     * @return array
     */
    protected function getCompletedSteps(Seller $seller): array
    {
        $completedSteps = [];

        if ($seller->email_verified_at) $completedSteps[] = 'email_verification';
        if ($seller->phone_verified_at) $completedSteps[] = 'phone_verification';
        if ($seller->profile_completed_at) $completedSteps[] = 'profile_completion';
        if ($seller->kyc_verified_at) $completedSteps[] = 'kyc_verification';

        return $completedSteps;
    }

    /**
     * Get next steps to complete
     *
     * @param Seller $seller
     * @return array
     */
    protected function getNextSteps(Seller $seller): array
    {
        $nextSteps = [];

        if (!$seller->email_verified_at) $nextSteps[] = 'email_verification';
        if (!$seller->phone_verified_at) $nextSteps[] = 'phone_verification';
        if (!$seller->profile_completed_at) $nextSteps[] = 'profile_completion';
        if (!$seller->kyc_verified_at) $nextSteps[] = 'kyc_verification';

        return $nextSteps;
    }
}
