<?php

namespace App\Services;

use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Services\FileService;

class MultistepFormServiceV2
{
    /**
     * Session prefix for form data
     */
    const SESSION_PREFIX = 'form_data_';

    /**
     * Form configuration
     */
    protected array $config;
    protected FileService $fileService;

    /**
     * Constructor
     *
     * @param FileService|null $fileService The file service (optional)
     */
    public function __construct(?FileService $fileService = null)
    {
        $this->config = config('forms');
        $this->fileService = $fileService ?? app(FileService::class);
    }

    /**
     * Generate a secure session ID for form processing
     *
     * @param string|null $formIdentifier Optional form identifier to initialize
     * @param array $initialData Optional initial data for the form
     * @return string A UUID v4 string
     */
    public function generateSessionId(?string $formIdentifier = null, array $initialData = []): string
    {
        $sessionId = (string) Str::uuid();

        // Register the session ID with the current user
        $this->registerSessionWithUser($sessionId);

        // If a form identifier is provided, initialize the form data
        if ($formIdentifier) {
            $this->validateFormType($formIdentifier);
            $formConfig = $this->config[$formIdentifier];
            $sessionKey = $this->getSessionKey($formIdentifier, $sessionId);

            $formData = [
                'created_at' => now(),
                'updated_at' => now(),
                'session_id' => $sessionId,
                'current_step' => 1,
                'form_type' => $formIdentifier,
                'steps' => [],
                'data' => $initialData
            ];

            Session::put($sessionKey, $formData);

            $this->logFormActivity('initialize_form', [
                'form_identifier' => $formIdentifier,
                'session_id' => $sessionId
            ]);
        }

        return $sessionId;
    }

    /**
     * Initialize a new multi-step form session
     *
     * @param string $formIdentifier The form type identifier
     * @param array $initialData Optional initial data for the form
     * @return array Response with session ID and form metadata
     */
    public function initializeForm(string $formIdentifier, array $initialData = []): array
    {
        $this->validateFormType($formIdentifier);

        // Generate a new session ID
        $sessionId = $this->generateSessionId();
        $formConfig = $this->config[$formIdentifier];
        $sessionKey = $this->getSessionKey($formIdentifier, $sessionId);

        $formData = [
            'created_at' => now(),
            'updated_at' => now(),
            'session_id' => $sessionId,
            'current_step' => 1,
            'form_type' => $formIdentifier,
            'steps' => [],
            'data' => $initialData
        ];

        Session::put($sessionKey, $formData);

        // Register session with user
        $this->registerSessionWithUser($sessionId);

        $this->logFormActivity('initialize_form', [
            'form_identifier' => $formIdentifier,
            'session_id' => $sessionId
        ]);

        return [
            'success' => true,
            'session_id' => $sessionId,
            'form_type' => $formIdentifier,
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
    }

    /**
     * Process a form step
     *
     * @param Request $request The request containing form data
     * @param string $formIdentifier The form type identifier
     * @return array Response with processing result
     */
    public function process(Request $request, string $formIdentifier): array
    {
        try {
            $this->logFormActivity('process_start', [
                'form_identifier' => $formIdentifier,
                'session_id' => $request->session_id,
                'step' => $request->step
            ]);

            $this->validateFormType($formIdentifier);
            $this->validateSessionId($request, $formIdentifier);

            $formConfig = $this->config[$formIdentifier];
            $sessionKey = $this->getSessionKey($formIdentifier, $request->session_id);
            $formData = $this->getOrInitializeFormData($request, $formIdentifier, $sessionKey);

            // Validate session ownership
            if (!$this->validateSessionOwnership($request->session_id)) {
                throw new \InvalidArgumentException("Invalid session ownership");
            }

            $this->validateStep($request->step, $formConfig, $formData);

            if (!$this->validateStepData($request, $formConfig)) {
                return [
                    'success' => false,
                    'error' => 'Validation failed',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors' => $this->getValidationErrors($request, $formConfig)
                ];
            }

            $formData = $this->updateFormData($formData, $request);
            Session::put($sessionKey, $formData);

            $response = $this->prepareSuccessResponse($request, $formConfig, $formData);

            // If form is completed, clear the session
            if ($response['completed']) {
                $this->clear($formIdentifier, $request->session_id);
            }

            $this->logFormActivity('process_complete', [
                'form_identifier' => $formIdentifier,
                'session_id' => $request->session_id,
                'step' => $request->step,
                'success' => true
            ]);

            return $response;

        } catch (\Exception $e) {
            $this->logFormActivity('process_error', [
                'form_identifier' => $formIdentifier,
                'session_id' => $request->session_id ?? null,
                'step' => $request->step ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $this->getErrorCode($e)
            ];
        }
    }

    /**
     * Get the current state of a form
     *
     * @param string $formIdentifier The form type identifier
     * @param string $sessionId The session ID
     * @return array Form state data
     */
    public function getFormState(string $formIdentifier, string $sessionId): array
    {
        // Log the request for debugging
        Log::info('Getting form state', [
            'form_identifier' => $formIdentifier,
            'session_id' => $sessionId,
            'session_key' => $this->getSessionKey($formIdentifier, $sessionId)
        ]);

        // Check all session data for debugging
        $allSessionData = Session::all();
        Log::info('All session data keys', [
            'keys' => array_keys($allSessionData)
        ]);

        // Look for any session keys that might match our form
        foreach ($allSessionData as $key => $value) {
            if (strpos($key, 'form_data_' . $formIdentifier) !== false) {
                Log::info('Found potential form data', [
                    'key' => $key,
                    'value' => $value
                ]);
            }
        }

        $data = $this->getData($formIdentifier, $sessionId);

        if (empty($data)) {
            Log::warning('Form session not found or expired', [
                'form_identifier' => $formIdentifier,
                'session_id' => $sessionId
            ]);

            return [
                'success' => false,
                'error' => 'Form session not found or expired',
                'error_code' => 'SESSION_NOT_FOUND'
            ];
        }

        $formConfig = $this->config[$formIdentifier];
        $currentStep = $data['current_step'] ?? 1;

        $result = [
            'success' => true,
            'session_id' => $sessionId,
            'form_type' => $formIdentifier,
            'current_step' => $currentStep,
            'total_steps' => $formConfig['total_steps'],
            'completed_steps' => array_keys($data['steps'] ?? []),
            'data' => $data['data'] ?? [],
            'expires_at' => now()->addHours($formConfig['expiration_hours'])->toDateTimeString()
        ];

        Log::info('Form state retrieved successfully', ['result' => $result]);

        return $result;
    }

    /**
     * Resume an incomplete form
     *
     * @param string $formIdentifier The form type identifier
     * @param string $sessionId The session ID
     * @return array Response with form state
     */
    public function resumeForm(string $formIdentifier, string $sessionId): array
    {
        $data = $this->getData($formIdentifier, $sessionId);

        if (empty($data)) {
            return [
                'success' => false,
                'error' => 'No saved form data found or session expired',
                'error_code' => 'SESSION_EXPIRED'
            ];
        }

        // Validate session ownership
        if (!$this->validateSessionOwnership($sessionId)) {
            return [
                'success' => false,
                'error' => 'Invalid session ownership',
                'error_code' => 'INVALID_SESSION_OWNERSHIP'
            ];
        }

        $currentStep = $data['current_step'] ?? 1;
        $formConfig = $this->config[$formIdentifier];

        return [
            'success' => true,
            'session_id' => $sessionId,
            'current_step' => $currentStep,
            'total_steps' => $formConfig['total_steps'],
            'step_info' => $formConfig['steps'][$currentStep] ?? null,
            'data' => $data['data'] ?? [],
            'completed_steps' => array_keys($data['steps'] ?? []),
            'expires_at' => now()->addHours($formConfig['expiration_hours'])->toDateTimeString()
        ];
    }

    /**
     * Handle file upload for a form field
     *
     * @param Request $request The request containing the file
     * @param string $fieldName The name of the file field
     * @param string $directory The directory to store the file in
     * @return string|null The path to the stored file or null if no file
     */
    public function handleFileUpload(Request $request, string $fieldName, string $directory): ?string
    {
        if (!$request->hasFile($fieldName)) {
            $this->logFormActivity('file_upload_no_file', [
                'field_name' => $fieldName
            ]);
            return null;
        }

        $file = $request->file($fieldName);

        // Validate file
        if (!$file->isValid()) {
            $this->logFormActivity('file_upload_invalid', [
                'field_name' => $fieldName,
                'error' => $file->getError()
            ]);
            throw new \RuntimeException("Invalid file upload");
        }

        // Use the FileService to upload the file
        $fullPath = $this->fileService->uploadFile($file, $directory);

        if (!$fullPath) {
            throw new \RuntimeException("Failed to store file");
        }

        $this->logFormActivity('file_upload_success', [
            'field_name' => $fieldName,
            'path' => $fullPath
        ]);

        return $fullPath;
    }

    /**
     * Move temporary files to their final location
     *
     * @param array $tempPaths Array of temporary file paths
     * @param string $finalDirectory The final directory to move files to
     * @return array Array of final file paths
     */
    public function moveTemporaryFiles(array $tempPaths, string $finalDirectory): array
    {
        $finalPaths = [];

        foreach ($tempPaths as $tempPath) {
            // Use the FileService to move the file
            $newPath = $this->fileService->moveFile($tempPath, $finalDirectory);

            if ($newPath) {
                $finalPaths[] = $newPath;
            } else {
                $this->logFormActivity('file_move_failed', [
                    'from' => $tempPath,
                    'to' => $finalDirectory
                ]);
            }
        }

        return $finalPaths;
    }

    /**
     * Get form data from session
     *
     * @param string $formIdentifier The form type identifier
     * @param string $sessionId The session ID
     * @return array Form data
     */
    public function getData(string $formIdentifier, string $sessionId): array
    {
        $sessionKey = $this->getSessionKey($formIdentifier, $sessionId);
        $data = Session::get($sessionKey, []);

        $this->logFormActivity('get_data', [
            'form_identifier' => $formIdentifier,
            'session_id' => $sessionId,
            'session_key' => $sessionKey,
            'data_exists' => !empty($data)
        ]);

        // If data is empty, try to find it using alternative methods
        if (empty($data)) {
            // Try without the session ID prefix
            $fallbackKey = 'form_data_' . $formIdentifier;
            $fallbackData = Session::get($fallbackKey, []);

            if (!empty($fallbackData)) {
                $this->logFormActivity('found_data_with_fallback_key', [
                    'fallback_key' => $fallbackKey,
                    'fallback_data' => $fallbackData
                ]);

                $data = $fallbackData;
                // Copy to the correct key for future use
                Session::put($sessionKey, $data);
            } else {
                // Try to find any session key that might contain our form data
                $allSessionData = Session::all();
                foreach ($allSessionData as $key => $value) {
                    if (strpos($key, 'form_data_' . $formIdentifier) !== false) {
                        $this->logFormActivity('found_data_with_partial_key_match', [
                            'key' => $key,
                            'value' => $value
                        ]);

                        $data = $value;
                        // Copy to the correct key for future use
                        Session::put($sessionKey, $data);
                        break;
                    }
                }
            }
        }

        // Check if the data has expired
        if ($this->hasExpired($data, $formIdentifier)) {
            $this->logFormActivity('data_expired', [
                'form_identifier' => $formIdentifier,
                'session_id' => $sessionId,
                'created_at' => $data['created_at'] ?? null,
                'updated_at' => $data['updated_at'] ?? null
            ]);

            $this->clear($formIdentifier, $sessionId);
            return [];
        }

        return $data;
    }

    /**
     * Mark a session as used after form submission
     *
     * @param string $formType The form type identifier
     * @param string $sessionId The session ID
     * @return void
     */
    public function markSessionAsUsed(string $formType, string $sessionId): void
    {
        // Store the session ID in a list of used sessions
        $usedSessionsKey = 'used_sessions_' . $formType;
        $usedSessions = Session::get($usedSessionsKey, []);
        $usedSessions[$sessionId] = [
            'used_at' => now(),
            'user_id' => auth()->id() ?? null
        ];
        Session::put($usedSessionsKey, $usedSessions);

        $this->logFormActivity('mark_session_used', [
            'form_type' => $formType,
            'session_id' => $sessionId
        ]);
    }

    /**
     * Check if a session has been used
     *
     * @param string $formType The form type identifier
     * @param string $sessionId The session ID
     * @return bool Whether the session has been used
     */
    public function isSessionUsed(string $formType, string $sessionId): bool
    {
        $usedSessionsKey = 'used_sessions_' . $formType;
        $usedSessions = Session::get($usedSessionsKey, []);

        return isset($usedSessions[$sessionId]);
    }

    public function clear(string $formType, string $sessionId): void
    {
        $sessionKey = $this->getSessionKey($formType, $sessionId);
        Session::forget($sessionKey);

        // Remove the session ID from user's sessions
        $this->unregisterSessionFromUser($sessionId);

        // Mark the session as used
        $this->markSessionAsUsed($formType, $sessionId);

        $this->logFormActivity('clear_session', [
            'form_type' => $formType,
            'session_id' => $sessionId
        ]);
    }

    /**
     * Get session key for form data
     *
     * @param string $formType The form type identifier
     * @param string|null $sessionId The session ID
     * @return string Session key
     */
    public function getSessionKey(string $formType, ?string $sessionId = null): string
    {
        if (!$sessionId) {
            return self::SESSION_PREFIX . $formType;
        }

        // Validate session ID format
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $sessionId)) {
            throw new \InvalidArgumentException("Invalid session ID format");
        }

        return self::SESSION_PREFIX . "{$formType}_{$sessionId}";
    }

    /**
     * Check if form data has expired
     *
     * @param array $data Form data
     * @param string $formIdentifier The form type identifier
     * @return bool Whether the data has expired
     */
    public function hasExpired(array $data, string $formIdentifier): bool
    {
        // Get the environment
        $env = config('app.env');

        // In local or testing environments, optionally bypass expiration
        if (($env === 'local' || $env === 'testing') && config('forms.bypass_expiration', false)) {
            $this->logFormActivity('expiration_check_bypassed', [
                'environment' => $env
            ]);
            return false;
        }

        // If no data or no timestamp, consider expired
        if (empty($data) || !isset($data['created_at'])) {
            $this->logFormActivity('session_expired_no_timestamp', [
                'data_empty' => empty($data),
                'has_created_at' => isset($data['created_at'])
            ]);
            return true;
        }

        // If no updated_at, use created_at
        $lastUpdated = $data['updated_at'] ?? $data['created_at'];
        $lastUpdatedTime = new DateTime($lastUpdated);

        // Get expiration hours from config
        $expirationHours = $this->config[$formIdentifier]['expiration_hours'] ?? 24;
        $expires = $lastUpdatedTime->modify("+{$expirationHours} hours");

        $isExpired = $expires < now();

        if ($isExpired) {
            $this->logFormActivity('session_expired', [
                'last_updated' => $lastUpdated,
                'expiration_hours' => $expirationHours,
                'expired_at' => $expires->format('Y-m-d H:i:s'),
                'current_time' => now()->format('Y-m-d H:i:s')
            ]);
        }

        return $isExpired;
    }

    /**
     * Get form configuration
     *
     * @param string $formIdentifier The form type identifier
     * @return array|null Form configuration
     */
    public function getFormConfig(string $formIdentifier): ?array
    {
        return $this->config[$formIdentifier] ?? null;
    }

    /**
     * Clear all form sessions
     *
     * @param string|null $formType Optional form type to clear
     * @return void
     */
    public function clearAllSessions(?string $formType = null): void
    {
        $pattern = $formType
            ? self::SESSION_PREFIX . "{$formType}_*"
            : self::SESSION_PREFIX . "*";

        // Clear all form data sessions
        Session::forget($pattern);

        $this->logFormActivity('clear_all_sessions', [
            'form_type' => $formType ?? 'all'
        ]);
    }

    /**
     * Validate form type exists in configuration
     *
     * @param string $formIdentifier The form type identifier
     * @return void
     * @throws \InvalidArgumentException If form type is invalid
     */
    protected function validateFormType(string $formIdentifier): void
    {
        if (!isset($this->config[$formIdentifier])) {
            throw new \InvalidArgumentException("Invalid form type: {$formIdentifier}");
        }
    }

    /**
     * Validate the session ID in the request
     *
     * @param Request $request The request containing the session ID
     * @param string|null $formType The form type to validate against
     * @throws \InvalidArgumentException If the session ID is invalid
     * @return void
     */
    protected function validateSessionId(Request $request, ?string $formType = null): void
    {
        if (!$request->session_id) {
            throw new \InvalidArgumentException("Session ID is required");
        }

        // Check if session_id is a placeholder like "{session_id}"
        if (preg_match('/^\{.*\}$/', $request->session_id)) {
            throw new \InvalidArgumentException("Invalid session ID format");
        }

        // Validate that the session_id is a valid UUID
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $request->session_id)) {
            throw new \InvalidArgumentException("Session ID must be a valid UUID");
        }

        // If form type is provided, perform additional validations
        if ($formType) {
            // Check if the session has been used
            if ($this->isSessionUsed($formType, $request->session_id)) {
                throw new \InvalidArgumentException("This session has already been used");
            }

            // Check if the session exists for this form type
            $sessionKey = $this->getSessionKey($formType, $request->session_id);
            $formData = Session::get($sessionKey);

            if (empty($formData)) {
                throw new \InvalidArgumentException("Invalid session ID for form type: {$formType}");
            }

            // Check if the form type matches
            if (isset($formData['form_type']) && $formData['form_type'] !== $formType) {
                throw new \InvalidArgumentException("Session ID was created for a different form type");
            }
        }
    }

    /**
     * Validate step is valid and in sequence
     *
     * @param int $step The step number
     * @param array $formConfig Form configuration
     * @param array $formData Form data
     * @return void
     * @throws \InvalidArgumentException If step is invalid
     */
    protected function validateStep(int $step, array $formConfig, array $formData): void
    {
        // Check if step exists in config
        if (!isset($formConfig['steps'][$step])) {
            throw new \InvalidArgumentException("Invalid step number");
        }

        // For step 1, no validation needed as it's the starting point
        if ($step === 1) {
            return;
        }

        // For other steps, check if all previous steps were completed
        $currentStep = $formData['current_step'] ?? 0;

        // Ensure steps are completed in sequence
        if ($step > 1 && $step > $currentStep + 1) {
            throw new \InvalidArgumentException("Please complete all previous steps first");
        }

        // Validate that all previous steps have data
        for ($i = 1; $i < $step; $i++) {
            if (!isset($formData['steps'][$i]) || empty($formData['steps'][$i])) {
                $stepName = $formConfig['steps'][$i]['label'] ?? "Step {$i}";
                throw new \InvalidArgumentException("Please complete {$stepName} first");
            }
        }
    }

    /**
     * Get or initialize form data
     *
     * @param Request $request The request
     * @param string $formIdentifier The form type identifier
     * @param string $sessionKey Session key
     * @return array Form data
     */
    protected function getOrInitializeFormData(Request $request, string $formIdentifier, string $sessionKey): array
    {
        $formData = Session::get($sessionKey, []);

        if (empty($formData)) {
            $step = (int) $request->step;
            if ($step !== 1) {
                throw new \InvalidArgumentException("Session expired or invalid. Please start from step 1");
            }

            return [
                'created_at' => now(),
                'updated_at' => now(),
                'session_id' => $request->session_id,
                'current_step' => $request->step,
                'form_type' => $formIdentifier,
                'steps' => [],
                'data' => []
            ];
        }

        return $formData;
    }

    /**
     * Validate step data against validation rules
     *
     * @param Request $request The request
     * @param array $formConfig Form configuration
     * @return bool Whether validation passed
     */
    protected function validateStepData(Request $request, array $formConfig): bool
    {
        $stepConfig = $formConfig['steps'][$request->step];
        $validator = Validator::make(
            $request->all(),
            $stepConfig['validation_rules']
        );

        return !$validator->fails();
    }

    /**
     * Get validation errors
     *
     * @param Request $request The request
     * @param array $formConfig Form configuration
     * @return array Validation errors
     */
    protected function getValidationErrors(Request $request, array $formConfig): array
    {
        $stepConfig = $formConfig['steps'][$request->step];
        $validator = Validator::make(
            $request->all(),
            $stepConfig['validation_rules']
        );

        return $validator->errors()->toArray();
    }

    /**
     * Update form data with request data
     *
     * @param array $formData Existing form data
     * @param Request $request The request
     * @return array Updated form data
     */
    protected function updateFormData(array $formData, Request $request): array
    {
        $stepData = $request->except(['session_id', 'step']);
        $currentStep = $request->step;

        // Log the step data for debugging
        $this->logFormActivity('update_form_data', [
            'step' => $currentStep,
            'step_data' => $stepData
        ]);

        // Initialize step data if not exists
        if (!isset($formData['steps'])) {
            $formData['steps'] = [];
        }

        // Store data for the current step
        $formData['steps'][$currentStep] = $stepData;

        // Update current step
        $formData['current_step'] = $currentStep;

        // Flatten all step data for easy access
        $allStepData = [];
        foreach ($formData['steps'] as $stepData) {
            $allStepData = array_merge($allStepData, $stepData);
        }

        // Special handling for file uploads
        // Check for any file paths in the current step data
        foreach ($stepData as $key => $value) {
            // If the value is a string and looks like a file path
            if (is_string($value) && (strpos($value, '/storage/') === 0 || strpos($value, 'storage/') === 0)) {
                $allStepData[$key] = $value;
                $this->logFormActivity('added_file_path_to_data', [
                    'key' => $key,
                    'path' => $value
                ]);
            }
        }

        $formData['data'] = $allStepData;

        // Update timestamp
        $formData['updated_at'] = now();

        // Log the updated form data
        $this->logFormActivity('form_data_updated', [
            'form_data' => $formData
        ]);

        return $formData;
    }

    /**
     * Prepare success response
     *
     * @param Request $request The request
     * @param array $formConfig Form configuration
     * @param array $formData Form data
     * @return array Success response
     */
    protected function prepareSuccessResponse(Request $request, array $formConfig, array $formData): array
    {
        $stepConfig = $formConfig['steps'][$request->step];

        // Get the data to include in the response
        $responseData = $formData['data'] ?? [];

        // Special handling for file uploads in step 2
        if ($request->step == 2 && $request->has('id_document') && is_string($request->id_document)) {
            $responseData['id_document'] = $request->id_document;
            $this->logFormActivity('added_file_to_response', [
                'id_document' => $request->id_document
            ]);
        }

        // Log the response data
        $this->logFormActivity('prepare_success_response', [
            'step' => $request->step,
            'response_data' => $responseData
        ]);

        return [
            'success' => true,
            'session_id' => $request->session_id,
            'completed' => $request->step >= $formConfig['total_steps'],
            'current_step' => $request->step,
            'total_steps' => $formConfig['total_steps'],
            'step_info' => [
                'label' => $stepConfig['label'],
                'description' => $stepConfig['description']
            ],
            'data' => $responseData,
            'completed_steps' => array_keys($formData['steps'] ?? []),
            'expires_at' => now()->addHours($formConfig['expiration_hours'])->toDateTimeString()
        ];
    }

    /**
     * Log form activity
     *
     * @param string $action The action being performed
     * @param array $data Additional data to log
     * @return void
     */
    protected function logFormActivity(string $action, array $data = []): void
    {
        $context = array_merge([
            'action' => $action,
            'timestamp' => now()->toDateTimeString(),
            'user_id' => auth()->id() ?? 'guest'
        ], $data);

        Log::info('Form activity', $context);
    }

    /**
     * Get error code for exception
     *
     * @param \Exception $e The exception
     * @return string Error code
     */
    protected function getErrorCode(\Exception $e): string
    {
        if ($e instanceof \InvalidArgumentException) {
            return 'INVALID_INPUT';
        } elseif ($e instanceof \RuntimeException) {
            return 'RUNTIME_ERROR';
        } else {
            return 'UNKNOWN_ERROR';
        }
    }

    /**
     * Validate that the current user owns the session
     *
     * @param string $sessionId The session ID to validate
     * @return bool Whether the session belongs to the current user
     */
    public function validateSessionOwnership(string $sessionId): bool
    {
        // Get the environment
        $env = config('app.env');

        // In local or testing environments, bypass validation for easier testing
        if ($env === 'local' || $env === 'testing') {
            $this->logFormActivity('session_ownership_bypassed', [
                'session_id' => $sessionId,
                'environment' => $env
            ]);
            return true;
        }

        // Get user ID
        $userId = auth()->id();

        // If not authenticated, check against guest session
        if (!$userId) {
            $guestSessionId = Session::get('guest_session_id');
            $isValid = $sessionId === $guestSessionId;

            $this->logFormActivity('guest_session_validation', [
                'session_id' => $sessionId,
                'guest_session_id' => $guestSessionId,
                'is_valid' => $isValid
            ]);

            return $isValid;
        }

        // Get user's sessions
        $userSessions = Session::get('user_sessions_' . $userId, []);

        // Check if session ID is in user's sessions
        $isValid = in_array($sessionId, $userSessions);

        $this->logFormActivity('user_session_validation', [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'user_sessions' => $userSessions,
            'is_valid' => $isValid
        ]);

        return $isValid;
    }

    /**
     * Register a session with the current user
     *
     * @param string $sessionId The session ID to register
     * @return void
     */
    public function registerSessionWithUser(string $sessionId): void
    {
        // Get user ID
        $userId = auth()->id();

        // If not authenticated, store as guest session
        if (!$userId) {
            Session::put('guest_session_id', $sessionId);
            return;
        }

        // Get user's sessions
        $userSessions = Session::get('user_sessions_' . $userId, []);

        // Add session ID if not already present
        if (!in_array($sessionId, $userSessions)) {
            $userSessions[] = $sessionId;
            Session::put('user_sessions_' . $userId, $userSessions);
        }
    }

    /**
     * Unregister a session from the current user
     *
     * @param string $sessionId The session ID to unregister
     * @return void
     */
    public function unregisterSessionFromUser(string $sessionId): void
    {
        // Get user ID
        $userId = auth()->id();

        // If not authenticated, clear guest session
        if (!$userId) {
            $guestSessionId = Session::get('guest_session_id');
            if ($sessionId === $guestSessionId) {
                Session::forget('guest_session_id');
            }
            return;
        }

        // Get user's sessions
        $userSessions = Session::get('user_sessions_' . $userId, []);

        // Remove session ID if present
        if (in_array($sessionId, $userSessions)) {
            $userSessions = array_diff($userSessions, [$sessionId]);
            Session::put('user_sessions_' . $userId, $userSessions);
        }
    }
}
