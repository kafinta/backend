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
                // Business category fields
                'business_category' => 'required|string|max:255',
                'business_subcategory' => 'nullable|string|max:255',
                'years_in_business' => 'nullable|integer|min:0',
                'business_website' => 'nullable|url|max:255',
                'business_social_media' => 'nullable|string|max:255',
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

            // Update business category information
            $seller->business_category = $request->business_category;
            $seller->business_subcategory = $request->business_subcategory;
            $seller->years_in_business = $request->years_in_business;
            $seller->business_website = $request->business_website;
            $seller->business_social_media = $request->business_social_media;

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
                'id_number' => 'nullable|string',
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
     * Accept seller agreement
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function acceptAgreement(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'agreement_accepted' => 'required|boolean|in:1,true',
                'agreement_version' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first(), 422);
            }

            // Get or create seller record
            $seller = $this->getOrCreateSeller(auth()->id());

            // Update agreement information
            $seller->agreement_accepted = true;
            $seller->agreement_version = $request->agreement_version;
            $seller->agreement_ip_address = $request->ip();
            $seller->agreement_accepted_at = now();
            $seller->agreement_completed_at = now();
            $seller->save();

            // Calculate progress
            $progress = $this->calculateProgress($seller);

            return $this->respondWithSuccess('Seller agreement accepted successfully', 200, [
                'progress' => $progress,
                'next_steps' => $this->getNextSteps($seller)
            ]);
        } catch (\Exception $e) {
            Log::error('Agreement acceptance error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return $this->respondWithError('Error accepting agreement: ' . $e->getMessage(), 500);
        }
    }



    /**
     * Update payment information
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePaymentInfo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_method' => 'required|string|in:bank_transfer,paypal',
                'bank_name' => 'required_if:payment_method,bank_transfer|string|max:255',
                'bank_account_number' => 'required_if:payment_method,bank_transfer|string|max:255',
                'bank_account_name' => 'required_if:payment_method,bank_transfer|string|max:255',
                'bank_routing_number' => 'required_if:payment_method,bank_transfer|string|max:255',
                'paypal_email' => 'required_if:payment_method,paypal|email|max:255',
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors()->first(), 422);
            }

            // Get or create seller record
            $seller = $this->getOrCreateSeller(auth()->id());

            // Update payment information based on payment method
            $seller->payment_method = $request->payment_method;

            if ($request->payment_method === 'bank_transfer') {
                $seller->bank_name = $request->bank_name;
                $seller->bank_account_number = $request->bank_account_number;
                $seller->bank_account_name = $request->bank_account_name;
                $seller->bank_routing_number = $request->bank_routing_number;
            } else if ($request->payment_method === 'paypal') {
                $seller->paypal_email = $request->paypal_email;
            }

            // Mark payment info as completed
            $seller->payment_info_completed_at = now();
            $seller->save();

            // Calculate progress
            $progress = $this->calculateProgress($seller);

            return $this->respondWithSuccess('Payment information updated successfully', 200, [
                'progress' => $progress,
                'next_steps' => $this->getNextSteps($seller)
            ]);
        } catch (\Exception $e) {
            Log::error('Payment info update error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return $this->respondWithError('Error updating payment information: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Complete the seller onboarding process
     * Requires at least 80% completion and all critical steps
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

            // Calculate current progress
            $progress = $this->calculateProgress($seller);

            // Check for critical steps
            $missingCriticalSteps = [];
            if (!$seller->email_verified_at) $missingCriticalSteps[] = 'email verification';
            if (!$seller->phone_verified_at) $missingCriticalSteps[] = 'phone verification';
            if (!$seller->profile_completed_at || !$seller->business_category) $missingCriticalSteps[] = 'business profile';
            if (!$seller->agreement_completed_at) $missingCriticalSteps[] = 'seller agreement';

            // If missing critical steps, return detailed error
            if (!empty($missingCriticalSteps)) {
                return $this->respondWithError(
                    'Critical steps must be completed: ' . implode(', ', $missingCriticalSteps),
                    422
                );
            }

            // Check if minimum progress threshold is met
            if ($progress < 80) {
                return $this->respondWithError(
                    'At least 80% of onboarding must be completed (current: ' . $progress . '%)',
                    422
                );
            }

            // Mark onboarding as completed
            $seller->onboarding_completed_at = now();
            $seller->is_verified = true;
            $seller->save();

            // Assign seller role to user
            $sellerRole = Role::where('slug', 'seller')->first();

            if (!$sellerRole) {
                return $this->respondWithError('Seller role not found', 500);
            }

            auth()->user()->roles()->syncWithoutDetaching([$sellerRole->id]);

            // Determine if any optional steps are still pending
            $pendingSteps = [];
            if (!$seller->kyc_verified_at) $pendingSteps[] = 'KYC verification';
            if (!$seller->payment_info_completed_at) $pendingSteps[] = 'payment information';

            $message = 'Seller onboarding completed successfully';
            if (!empty($pendingSteps)) {
                $message .= '. Note: You still have pending steps to complete: ' . implode(', ', $pendingSteps);
            }

            return $this->respondWithSuccess($message, 200, [
                'seller' => $seller,
                'is_seller' => true,
                'progress' => $progress,
                'pending_steps' => $pendingSteps
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
     * Get seller onboarding progress with detailed step information
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

            // Get step details with weights
            $stepDetails = [
                [
                    'id' => 'email_verification',
                    'name' => 'Email Verification',
                    'weight' => 15,
                    'completed' => $seller->email_verified_at ? true : false,
                    'is_critical' => true
                ],
                [
                    'id' => 'phone_verification',
                    'name' => 'Phone Verification',
                    'weight' => 10,
                    'completed' => $seller->phone_verified_at ? true : false,
                    'is_critical' => true
                ],
                [
                    'id' => 'profile_completion',
                    'name' => 'Business Profile',
                    'weight' => 25,
                    'completed' => ($seller->profile_completed_at && $seller->business_category) ? true : false,
                    'is_critical' => true
                ],
                [
                    'id' => 'agreement_acceptance',
                    'name' => 'Seller Agreement',
                    'weight' => 30,
                    'completed' => $seller->agreement_completed_at ? true : false,
                    'is_critical' => true
                ],
                [
                    'id' => 'kyc_verification',
                    'name' => 'KYC Verification',
                    'weight' => 10,
                    'completed' => $seller->kyc_verified_at ? true : false,
                    'is_critical' => false
                ],
                [
                    'id' => 'payment_information',
                    'name' => 'Payment Information',
                    'weight' => 10,
                    'completed' => $seller->payment_info_completed_at ? true : false,
                    'is_critical' => false
                ]
            ];

            // Check if seller can complete onboarding
            $canComplete = $this->canCompleteOnboarding($seller);

            // If can't complete, determine what's missing
            $missingRequirements = [];
            if (!$canComplete) {
                // Check for critical steps
                if (!$seller->email_verified_at) $missingRequirements[] = 'Email verification is required';
                if (!$seller->phone_verified_at) $missingRequirements[] = 'Phone verification is required';
                if (!$seller->profile_completed_at || !$seller->business_category) $missingRequirements[] = 'Business profile must be completed';
                if (!$seller->agreement_completed_at) $missingRequirements[] = 'Seller agreement must be accepted';

                // Check progress threshold
                if ($progress < 80) $missingRequirements[] = 'At least 80% completion is required (current: ' . $progress . '%)';
            }

            return $this->respondWithSuccess('Onboarding progress retrieved', 200, [
                'progress' => $progress,
                'completed_steps' => $this->getCompletedSteps($seller),
                'next_steps' => $this->getNextSteps($seller),
                'can_complete' => $canComplete,
                'missing_requirements' => $missingRequirements,
                'step_details' => $stepDetails,
                'completion_threshold' => 80
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
                $seller->id_type ?? 'document',
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
                'id_type' => null,  // Add this with null value
                'onboarding_progress' => 0
            ]);
        }

        return $seller;
    }

    /**
     * Calculate seller onboarding progress with weighted steps
     *
     * @param Seller $seller
     * @return int
     */
    protected function calculateProgress(Seller $seller): int
    {
        $progress = 0;

        // Critical steps with higher weights
        if ($seller->email_verified_at) $progress += 15; // 15% - Critical for communication
        if ($seller->phone_verified_at) $progress += 10; // 10% - Important for security
        if ($seller->profile_completed_at && $seller->business_category) $progress += 25; // 25% - Essential for marketplace presence
        if ($seller->agreement_completed_at) $progress += 30; // 30% - Legally required

        // Less critical steps with lower weights
        if ($seller->kyc_verified_at) $progress += 10; // 10% - Can be completed later
        if ($seller->payment_info_completed_at) $progress += 10; // 10% - Only needed when receiving payments

        // Update progress in database
        $seller->onboarding_progress = $progress;
        $seller->save();

        return $progress;
    }

    /**
     * Check if seller can complete onboarding (80% or more completion)
     *
     * Critical steps (email, phone, profile, agreement) must be completed
     * At least 80% of total progress must be achieved
     *
     * @param Seller $seller
     * @return bool
     */
    protected function canCompleteOnboarding(Seller $seller): bool
    {
        // Critical steps must be completed regardless of percentage
        $criticalStepsCompleted =
            $seller->email_verified_at &&
            $seller->phone_verified_at &&
            $seller->profile_completed_at &&
            $seller->business_category &&
            $seller->agreement_completed_at;

        // Calculate current progress
        $progress = $this->calculateProgress($seller);

        // Require at least 80% completion and all critical steps
        return $criticalStepsCompleted && $progress >= 80;
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
        if ($seller->agreement_completed_at) $completedSteps[] = 'agreement_acceptance';
        if ($seller->payment_info_completed_at) $completedSteps[] = 'payment_information';

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
        if (!$seller->profile_completed_at || !$seller->business_category) $nextSteps[] = 'profile_completion';
        if (!$seller->kyc_verified_at) $nextSteps[] = 'kyc_verification';
        if (!$seller->agreement_completed_at) $nextSteps[] = 'agreement_acceptance';
        if (!$seller->payment_info_completed_at) $nextSteps[] = 'payment_information';

        return $nextSteps;
    }
}
