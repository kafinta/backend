<?php

namespace App\Http\Controllers;
use App\Http\Controllers\ImprovedController;

use App\Models\Role;
use App\Models\Seller;
use App\Services\MultistepFormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;

class SellerController extends ImprovedController
{
    protected $formService;

    public function __construct(MultistepFormService $formService)
    {
        $this->middleware(['auth:sanctum']);
        $this->formService = $formService;
    }

    public function saveStep(Request $request)
    {
        try {
            // Process form step first
            $result = $this->formService->process($request, 'seller_form');
            
            if (!$result['success']) {
                return $this->respondWithError($result, 400);
            }

            // If step 2 and file is present, handle file upload
            if ($request->step == 2 && $request->hasFile('id_document')) {
                $file = $request->file('id_document');
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('seller-documents', $fileName, 'public');
                
                // Update session data with file path
                $sessionKey = 'form_data_seller_form';
                $formData = Session::get($sessionKey, []);
                
                // Ensure data exists and update id_document with full path
                $formData['data'] = $formData['data'] ?? [];
                $formData['data']['id_document'] = '/storage/' . $path;
                
                Session::put($sessionKey, $formData);

                // Update the result data to reflect the file path
                $result['data']['data']['id_document'] = '/storage/' . $path;
            }
            
            return $this->respondWithSuccess('Step saved successfully', 200, $result);

        } catch (\Exception $e) {
            return $this->respondWithError([
                'message'=> 'Error saving step', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFormMetadata()
    {
        $config = $this->formService->getFormConfig('seller_form');
        
        return $this->respondWithSuccess('Form metadata retrieved', 200, [
            'total_steps' => $config['total_steps'],
            'steps' => collect($config['steps'])->map(function($step) {
                return [
                    'label' => $step['label'],
                    'description' => $step['description']
                ];
            })
        ]);
    }

    public function submit(Request $request)
    {
        try {
            // Log the session ID and form identifier
            \Log::info('Seller Submit Request', [
                'session_id' => $request->session_id,
                'form_identifier' => 'seller_form'
            ]);

            $data = $this->formService->getData('seller_form', $request->session_id);

            // Log the retrieved data with more details
            \Log::info('Retrieved Form Data', [
                'data' => $data,
                'is_empty' => empty($data),
                'data_keys' => array_keys($data)
            ]);

            // Check if session data exists
            if (empty($data)) {
                // Log session contents for debugging
                $sessionKey = 'form_data_seller_form';
                $fullSessionData = Session::get($sessionKey, []);
                
                \Log::info('Full Session Data', [
                    'session_key' => $sessionKey,
                    'full_session_data' => $fullSessionData
                ]);

                return $this->respondWithError('No application data found. Please complete the application form.', 422);
            }

            // Merge with request data to fill in missing fields
            $completeData = array_merge([
                'business_name' => $request->business_name ?? null,
                'business_description' => $request->business_description ?? null,
                'business_address' => $request->business_address ?? null,
                'phone_number' => $request->phone_number ?? null,
            ], $data);

            // Check if all required fields are present
            $requiredFields = [
                'business_name',
                'business_address',
                'phone_number',
                'id_type',
                'id_number',
                'id_document'
            ];

            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($completeData[$field]) || empty($completeData[$field])) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                return $this->respondWithError([
                    'message' => 'Incomplete application data',
                    'missing_fields' => $missingFields
                ], 422);
            }

            return DB::transaction(function () use ($request, $completeData) {
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
                ]);

                // Automatically assign seller role
                $sellerRole = Role::where('slug', 'seller')->first();
                auth()->user()->roles()->syncWithoutDetaching([$sellerRole->id]);

                // Clear temporary form data
                $this->formService->clear('seller_form', $request->session_id);

                return $this->respondWithSuccess('Seller application submitted and approved', 201, $seller);
            });

        } catch (\Exception $e) {
            \Log::error('Seller Submit Error', [
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

            // If the path starts with /storage/, remove it for proper file access
            $path = str_replace('/storage/', '', $seller->id_document);

            // Check if file exists in storage
            if (!Storage::disk('public')->exists($path)) {
                return $this->respondWithError('Document file not found', 404);
            }

            // Get file mime type
            $mimeType = Storage::disk('public')->mimeType($path);
            
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

    protected function getFileType($file)
    {
        $mimeType = $file->getMimeType();
        
        return match($mimeType) {
            'application/pdf' => 'pdf',
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            default => throw new \Exception('Unsupported file type')
        };
    }

}
