<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use DateTime;

class MultistepFormService
{
    protected const SESSION_PREFIX = 'form_data_';

    protected array $config;

    public function __construct()
    {
        $this->config = Config::get('forms');
    }

    public function process(Request $request, string $formIdentifier): array
    {
        try {
            $this->validateFormType($formIdentifier);
            $this->validateSessionId($request);

            $formConfig = $this->config[$formIdentifier];
            $sessionKey = $this->getSessionKey($formIdentifier, $request->session_id);
            $formData = $this->getOrInitializeFormData($request, $formIdentifier, $sessionKey);
            
            $this->validateStep($request->step, $formConfig, $formData);
            
            if (!$this->validateStepData($request, $formConfig)) {
                return [
                    'success' => false,
                    'error' => 'Validation failed',                    
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

            return $response;

        } catch (\Exception $e) {
            Log::error('Form processing error', [
                'error' => $e->getMessage(),
                'formIdentifier' => $formIdentifier,
                'session_id' => $request->session_id ?? null
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function validateFormType(string $formIdentifier): void
    {
        if (!isset($this->config[$formIdentifier])) {
            throw new \InvalidArgumentException("Invalid form type: {$formIdentifier}");
        }
    }

    protected function validateSessionId(Request $request): void
    {
        if (!$request->session_id) {
            throw new \InvalidArgumentException("Session ID is required");
        }
    }

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

        // For other steps, check if the previous step was completed
        $currentStep = $formData['step'] ?? 0;
        $previousStep = $step - 1;

        if ($currentStep < $previousStep) {
            $stepName = $formConfig['steps'][$previousStep]['label'] ?? "Step {$previousStep}";
            throw new \InvalidArgumentException("Please complete {$stepName} first");
        }
    }

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
                'session_id' => $request->session_id,
                'step' => $request->step,
                'form_type' => $formIdentifier,
                'data' => []
            ];
        }

        return $formData;
    }

    protected function validateStepData(Request $request, array $formConfig): bool
    {
        $stepConfig = $formConfig['steps'][$request->step];
        $validator = Validator::make(
            $request->all(),
            $stepConfig['validation_rules']
        );

        return !$validator->fails();
    }

    protected function getValidationErrors(Request $request, array $formConfig): array
    {
        $stepConfig = $formConfig['steps'][$request->step];
        $validator = Validator::make(
            $request->all(),
            $stepConfig['validation_rules']
        );

        return $validator->errors()->toArray();
    }

    protected function updateFormData(array $formData, Request $request): array
    {
        $stepData = $request->except(['session_id', 'step']);
        $formData['data'] = array_merge($formData['data'] ?? [], $stepData);
        $formData['step'] = $request->step;
        
        return $formData;
    }

    protected function prepareSuccessResponse(Request $request, array $formConfig, array $formData): array
    {
        $stepConfig = $formConfig['steps'][$request->step];
        
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
            'data' => $formData['data'],
            'expires_at' => now()->addHours($formConfig['expiration_hours'])->toDateTimeString()
        ];
    }

    public function getData(string $formIdentifier, string $sessionId): array
    {
        $sessionKey = $this->getSessionKey($formIdentifier, $sessionId);
        $data = Session::get($sessionKey, []);

        Log::info('Getting form data', [
            'formIdentifier' => $formIdentifier,
            'sessionId' => $sessionId,
            'sessionKey' => $sessionKey,
            'data' => $data
        ]);

        if ($this->hasExpired($data, $formIdentifier)) {
            Log::info('Form data has expired', [
                'created_at' => $data['created_at'] ?? null,
                'formIdentifier' => $formIdentifier
            ]);
            $this->clear($formIdentifier, $sessionId);
            return [];
        }

        return $data;
    }

    public function clear(string $formType, string $sessionId): void
    {
        $sessionKey = $this->getSessionKey($formType, $sessionId);
        Session::forget($sessionKey);
        
        // Remove the session ID from the session
        Session::forget('session_id_' . $sessionId);
    }

    public function getSessionKey(string $formType, ?string $sessionId = null): string
    {
        return $sessionId 
            ? self::SESSION_PREFIX . "{$formType}_{$sessionId}" 
            : self::SESSION_PREFIX . $formType;
    }

    public function hasExpired(array $data, string $formIdentifier): bool
    {
        if (empty($data) || !isset($data['created_at'])) {
            return true;
        }

        $created = new DateTime($data['created_at']);
        $expires = $created->modify("+{$this->config[$formIdentifier]['expiration_hours']} hours");
        
        return $expires < now();
    }

    public function getFormConfig(string $formIdentifier): ?array
    {
        return $this->config[$formIdentifier] ?? null;
    }

    public function clearAllSessions(?string $formType = null): void
    {
        $pattern = $formType 
            ? self::SESSION_PREFIX . "{$formType}_*" 
            : self::SESSION_PREFIX . "*";

        // Clear all form data sessions
        Session::forget($pattern);
        
        // Clear all session IDs
        $sessionIdsPattern = 'session_id_*';
        Session::forget($sessionIdsPattern);
    }
}