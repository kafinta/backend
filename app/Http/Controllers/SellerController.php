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
            // Validate step parameter
            $request->validate([
                'step' => 'required|integer|in:1,2'
            ]);

            $rules = $this->getValidationRules($request->step);
            
            if (empty($rules)) {
                return $this->respondWithError('Invalid step', 422);
            }

            // Validate the request
            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
                return $this->respondWithError('Validation failed', 422, [
                    'errors' => $validator->errors()
                ]);
            }

            // Handle file upload after validation
            if ($request->hasFile('id_document')) {
                $file = $request->file('id_document');
                $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('seller-documents', $fileName, 'public');
                
                // Remove the file from request and add only the path
                $request = new Request(
                    $request->except('id_document') + 
                    ['id_document' => '/storage/' . $path]
                );
            }
            
            $result = $this->formService->process($request, 'seller_application');

            return $this->respondWithSuccess('Step saved successfully', 200, $result);

        } catch (\Exception $e) {
            return $this->respondWithError($e->getMessage(), 500);
        }
    }

    public function submit(Request $request)
    {
        try {
            $data = $this->formService->getData('seller_application', $request->session_id);

            // Check if session data exists
            if (empty($data)) {
                return $this->respondWithError('No application data found. Please complete the application form.', 422);
            }

            // Check if all required fields are present
            $requiredFields = [
                'business_name',
                'business_address',
                'phone_number',
                'id_type',
                'id_number',
                'id_document'
            ];

            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    return $this->respondWithError("Incomplete application data. Missing: {$field}", 422);
                }
            }

            return DB::transaction(function () use ($request, $data) {
                // Create seller profile
                $seller = Seller::create([
                    'user_id' => auth()->id(),
                    'business_name' => $data['business_name'],
                    'business_description' => $data['business_description'] ?? null,
                    'business_address' => $data['business_address'],
                    'phone_number' => $data['phone_number'],
                    'id_type' => $data['id_type'],
                    'id_number' => $data['id_number'],
                    'id_document' => $data['id_document'],
                ]);

                // Automatically assign seller role
                $sellerRole = Role::where('slug', 'seller')->first();
                auth()->user()->roles()->syncWithoutDetaching([$sellerRole->id]);

                // Clear temporary form data
                $this->formService->clear('seller_application', $request->session_id);

                return $this->respondWithSuccess('Seller application submitted and approved', 201, $seller);
            });

        } catch (\Exception $e) {
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

    protected function getValidationRules($step)
    {
        $rules = [
            1 => [
                'business_name' => 'required|string|max:255',
                'business_description' => 'nullable|string',
                'business_address' => 'required|string',
                'phone_number' => 'required|string',
            ],
            2 => [
                'id_type' => 'required|in:passport,national_id,nin',
                'id_number' => 'required|string',
                'id_document' => [
                    'required',
                    'file',
                    'max:2048', // 2MB max
                    'mimetypes:application/pdf,image/jpeg,image/png,image/jpg'
                ],
            ]
        ];

        return $rules[$step] ?? [];
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
