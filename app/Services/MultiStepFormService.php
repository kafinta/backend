<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;

class MultistepFormService
{
    protected $sessionPrefix = 'form_data_';
    protected $config;

    public function __construct()
    {
        $this->config = Config::get('forms');
    }

    public function process(Request $request, string $formIdentifier)
    {
        try {
            // Validate form type exists
            if (!isset($this->config[$formIdentifier])) {
                throw new \Exception("Invalid form type: {$formIdentifier}");
            }

            $formConfig = $this->config[$formIdentifier];
            $sessionKey = $this->getSessionKey($formIdentifier);
            
            // Try to get existing session data
            $formData = Session::get($sessionKey, []);
            
            // Initialize new form if no existing data
            if (empty($formData)) {
                $formData = $this->initializeForm($formIdentifier, $request->step);
            }

            // Validate step exists
            if (!isset($formConfig['steps'][$request->step])) {
                throw new \Exception("Invalid step number");
            }

            // Validate current step data
            $stepConfig = $formConfig['steps'][$request->step];
            $validator = Validator::make(
                $request->all(),
                $stepConfig['validation_rules']
            );

            if ($validator->fails()) {
                return [
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ];
            }

            // Get current step data, excluding session and step
            $stepData = $request->except(['session_id', 'step']);
            
            // Merge with existing data
            $formData['data'] = array_merge(
                $formData['data'] ?? [], 
                $stepData
            );
            
            $formData['step'] = $request->step;
            
            // Store in session
            Session::put($sessionKey, $formData);
            
            return [
                'success' => true,
                'session_id' => $formData['session_id'],
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
            
        } catch (\Exception $e) {            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function initializeForm(string $formIdentifier, int $step): array
    {
        return [
            'created_at' => now(),
            'session_id' => Str::uuid(),
            'step' => $step,
            'form_type' => $formIdentifier,
            'data' => []
        ];
    }

    public function getStepInfo(string $formIdentifier, int $step)
    {
        if (!isset($this->config[$formIdentifier]['steps'][$step])) {
            return null;
        }

        return $this->config[$formIdentifier]['steps'][$step];
    }

    public function getFormConfig(string $formIdentifier)
    {
        return $this->config[$formIdentifier] ?? null;
    }

    public function getData(string $formIdentifier, string $sessionId)
    {
        $sessionKey = $this->getSessionKey($formIdentifier);
        $data = Session::get($sessionKey, []);

        // Verify session ID matches
        // Note the special handling for UUID object
        $storedSessionId = $data['session_id'] instanceof \Ramsey\Uuid\Lazy\LazyUuidFromString 
            ? (string)$data['session_id'] 
            : ($data['session_id'] ?? null);

        if (!empty($data) && $storedSessionId !== $sessionId) {
            return [];
        }
        
        // Check expiration
        if (!empty($data) && isset($data['created_at'])) {
            $created = new \DateTime($data['created_at']);
            $expires = $created->modify("+{$this->config[$formIdentifier]['expiration_hours']} hours");
            
            if ($expires < now()) {
                $this->clear($formIdentifier, $sessionId);
                return [];
            }
        }
        
        // Return the full data, ensuring we return the actual data array
        return $data['data'] ?? [];
    }

    public function clear(string $formIdentifier, string $sessionId)
    {
        $sessionKey = $this->getSessionKey($formIdentifier);

        Session::forget($sessionKey);
    }

    protected function getSessionKey(string $formIdentifier): string
    {
        return $this->sessionPrefix . $formIdentifier;
    }

    // Restored helper method to check if form has expired
    public function hasExpired(array $data, string $formIdentifier): bool
    {
        if (empty($data) || !isset($data['created_at'])) {
            return true;
        }

        $created = new \DateTime($data['created_at']);
        $expires = $created->modify("+{$this->config[$formIdentifier]['expiration_hours']} hours");
        
        return $expires < now();
    }
}