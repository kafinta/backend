<?php

namespace App\Http\Controllers;
use App\Http\Controllers\ImprovedController;

use App\Models\Role;
use App\Models\Seller;
use App\Services\MultistepFormService;
use App\Services\FileService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

/**
 * Controller for handling seller registration and management
 *
 * This controller provides endpoints for the multi-step seller registration process,
 * including file uploads and form submission. It uses a session-based approach to
 * store form data between steps.
 */
class SellerController extends ImprovedController
{
    protected $formService;
    protected $fileService;
    protected $emailService;

    public function __construct(MultistepFormService $formService, FileService $fileService, EmailService $emailService)
    {
        $this->middleware(['auth:sanctum']);
        $this->formService = $formService;
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

    /**
     * Generate a new session ID and return form metadata
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateSessionId()
    {
        try {
            // Generate a session ID and initialize the form
            // This will also register the session with the current user
            $sessionId = $this->formService->generateSessionId('seller_form');

            // Initialize the form with this session ID
            $formConfig = $this->formService->getFormConfig('seller_form');

            // Prepare the response with form metadata
            $response = [
                'session_id' => $sessionId,
                'form_type' => 'seller_form',
                'total_steps' => $formConfig['total_steps'],
                'current_step' => 1,
                'steps' => collect($formConfig['steps'])->map(function($step) {
                    return [
                        'label' => $step['label'],
                        'description' => $step['description']
                    ];
                }),
                'expires_at' => now()->addHours($formConfig['expiration_hours'])->toDateTimeString()
            ];

            return $this->respondWithSuccess('Session initialized', 200, $response);
        } catch (\Exception $e) {
            Log::error('Error initializing session', ['error' => $e->getMessage()]);
            return $this->respondWithError('Error initializing session', 500);
        }
    }



    /**
     * Process a step in the seller registration form
     *
     * Handles form validation, file uploads, and session storage for each step
     * of the seller registration process.
     *
     * @param Request $request The request containing form data
     * @return \Illuminate\Http\JsonResponse
     */
    public function createStep(Request $request)
    {
        try {
            // Validate basic request parameters
            if (!$request->has('session_id') || !$request->has('step')) {
                return $this->respondWithError('Session ID and step are required', 400);
            }

            // If step 2 and file is present, handle file upload before processing the form
            if ($request->step == 2 && $request->hasFile('id_document')) {
                // Validate the file
                $validator = Validator::make($request->all(), [
                    'id_document' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
                ]);

                if ($validator->fails()) {
                    return $this->respondWithError([
                        'message' => 'Invalid file upload',
                        'errors' => $validator->errors()->toArray()
                    ], 422);
                }

                // Handle file upload using the FileService
                $fullPath = $this->fileService->uploadFile(
                    $request->file('id_document'),
                    'seller-documents'
                );

                if (!$fullPath) {
                    return $this->respondWithError('File upload failed', 500);
                }

                // Add the path to the request so it's included in form processing
                $request->merge(['id_document' => $fullPath]);
            }

            // Process form step
            $result = $this->formService->process($request, 'seller_form');

            if (!$result['success']) {
                // If we have validation errors, use the first one as the message
                if (isset($result['errors']) && !empty($result['errors'])) {
                    // Get the first validation error message
                    $firstErrorField = array_key_first($result['errors']);
                    $firstErrorMessage = $result['errors'][$firstErrorField][0] ?? 'Validation failed';

                    return $this->respondWithError($firstErrorMessage, 400);
                }

                // Otherwise, use the general error message
                return $this->respondWithError($result['error'], 400);
            }

            // For step 2, ensure the file path is included in the response
            if ($request->step == 2 && isset($fullPath)) {
                $result['data']['id_document'] = $fullPath;
            }

            return $this->respondWithSuccess('Step saved successfully', 200, $result);

        } catch (\Exception $e) {
            Log::error('Error in createStep', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->respondWithError([
                'message'=> 'Error saving step',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    /**
     * Get saved form data for a specific session
     *
     * @param string $sessionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFormData($sessionId)
    {
        try {
            $formState = $this->formService->getFormState('seller_form', $sessionId);

            if (!$formState['success']) {
                return $this->respondWithError($formState['error'], 404);
            }

            return $this->respondWithSuccess('Form data retrieved', 200, $formState);
        } catch (\Exception $e) {
            return $this->respondWithError('Error retrieving form data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Submit the completed seller registration form
     *
     * Retrieves all form data from the session, validates required fields,
     * creates a new seller profile, and assigns the seller role to the user.
     *
     * @param Request $request The request containing the session ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function submit(Request $request)
    {
        try {
            // Validate that session_id is provided
            if (!$request->has('session_id')) {
                return $this->respondWithError('Session ID is required', 400);
            }

            // Log the request data
            Log::info('Seller Submit Request', [
                'session_id' => $request->session_id
            ]);

            // Get the form state
            $formState = $this->formService->getFormState('seller_form', $request->session_id);

            // Check if form state is valid
            if (!$formState['success']) {
                return $this->respondWithError('Invalid or expired session. Please start the application process again.', 400);
            }

            // Check if all steps are completed
            $totalSteps = $formState['total_steps'] ?? 2;
            $completedSteps = $formState['completed_steps'] ?? [];

            if (count($completedSteps) < $totalSteps) {
                $missingSteps = array_diff(range(1, $totalSteps), $completedSteps);
                return $this->respondWithError([
                    'message' => 'Please complete all steps before submitting',
                    'missing_steps' => $missingSteps
                ], 422);
            }

            // Get the form data
            $formData = $formState['data'];

            // Merge with any data provided in the request
            $completeData = array_merge([
                'business_name' => $request->business_name ?? null,
                'business_description' => $request->business_description ?? null,
                'business_address' => $request->business_address ?? null,
                'phone_number' => $request->phone_number ?? null,
                'id_type' => $request->id_type ?? null,
                'id_number' => $request->id_number ?? null,
            ], $formData);

            // Check if all required fields are present
            $requiredFields = [
                'business_name',
                'business_address',
                'phone_number',
                'id_type',
                'id_number',
                'id_document'
            ];

            // Validate required fields
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($completeData[$field]) || empty($completeData[$field])) {
                    $missingFields[] = $field;
                }
            }

            // Check if id_document is missing
            if (in_array('id_document', $missingFields)) {
                // Check both session keys
                $directKey = 'form_data_seller_form_' . $request->session_id;
                $fallbackKey = 'form_data_seller_form';

                $directData = Session::get($directKey, []);
                $fallbackData = Session::get($fallbackKey, []);

                $idDocument = null;

                // Try direct key first
                if (isset($directData['data']) && isset($directData['data']['id_document'])) {
                    $idDocument = $directData['data']['id_document'];
                }

                // Try fallback key if needed
                if (!$idDocument && isset($fallbackData['data']) && isset($fallbackData['data']['id_document'])) {
                    $idDocument = $fallbackData['data']['id_document'];
                }

                // If we found the document, add it to completeData
                if ($idDocument) {
                    $completeData['id_document'] = $idDocument;
                    $missingFields = array_diff($missingFields, ['id_document']);
                } else {
                    return $this->respondWithError([
                        'message' => 'ID document is required',
                        'missing_fields' => ['id_document']
                    ], 422);
                }
            }

            // If there are still missing fields, return an error
            if (!empty($missingFields)) {
                return $this->respondWithError([
                    'message' => 'Please complete all required fields',
                    'missing_fields' => $missingFields
                ], 422);
            }

            return DB::transaction(function () use ($request, $completeData) {
                // Log the final data before creating the seller
                Log::info('Final data for seller creation', ['data' => $completeData]);

                // Create seller profile
                $seller = Seller::create([
                    'user_id' => auth()->id(),
                    'business_name' => $completeData['business_name'],
                    'business_description' => $completeData['business_description'] ?? null,
                    'business_address' => $completeData['business_address'],
                    'phone_number' => $completeData['phone_number'],
                    'id_type' => $completeData['id_type'],
                    'id_number' => $completeData['id_number'],
                    'id_document' => $completeData['id_document'],
                    'is_verified' => true, // Auto-verify sellers for now
                ]);

                // Automatically assign seller role
                $sellerRole = Role::where('slug', 'seller')->first();
                if ($sellerRole) {
                    // Assign seller role
                    auth()->user()->roles()->syncWithoutDetaching([$sellerRole->id]);

                    Log::info('Seller role assigned', [
                        'user_id' => auth()->id(),
                        'seller_role_id' => $sellerRole->id
                    ]);
                } else {
                    Log::error('Seller role not found in database');
                }

                // Clear form data
                $this->formService->clear('seller_form', $request->session_id);

                // Log the application submission
                Log::info('Seller application submitted and approved', [
                    'user_id' => auth()->id(),
                    'seller_id' => $seller->id,
                    'business_name' => $seller->business_name
                ]);

                return $this->respondWithSuccess('Seller application submitted and approved. You can now start selling!', 201, $seller);
            });

        } catch (\Exception $e) {
            Log::error('Seller Submit Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->respondWithError($e->getMessage(), 500);
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
