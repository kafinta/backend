<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class MultistepFormService
{
    protected $sessionPrefix = 'form_data_';
    protected $expirationHours = 24;

    public function process(Request $request, string $formIdentifier)
    {
        try {
            $sessionKey = $this->getSessionKey($formIdentifier);
            $formData = cache()->get($sessionKey, []);
            
            // Add timestamp if starting new form
            if (empty($formData)) {
                $formData['created_at'] = now();
                $formData['session_id'] = Str::uuid(); // Generate unique ID for this form
            }
            
            $formData = array_merge($formData, $request->except(['session_id']));
            
            // Store in cache instead of session
            cache()->put(
                $sessionKey, 
                $formData, 
                now()->addHours($this->expirationHours)
            );
            
            return [
                'success' => true,
                'session_id' => $formData['session_id'],
                'completed' => $request->step == 2,
                'current_step' => $request->step,
                'data' => $formData,
                'expires_at' => now()->addHours($this->expirationHours)->toDateTimeString()
            ];
            
        } catch (\Exception $e) {
            \Log::error('Form processing error: ' . $e->getMessage(), [
                'formIdentifier' => $formIdentifier,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getData(string $formIdentifier, string $sessionId)
    {
        $sessionKey = $this->getSessionKey($formIdentifier);
        $data = cache()->get($sessionKey, []);
        
        // Verify session ID matches
        if (!empty($data) && ($data['session_id'] ?? '') !== $sessionId) {
            return [];
        }
        
        // Check expiration
        if (!empty($data) && isset($data['created_at'])) {
            $created = new \DateTime($data['created_at']);
            $expires = $created->modify("+{$this->expirationHours} hours");
            
            if ($expires < now()) {
                $this->clear($formIdentifier, $sessionId);
                return [];
            }
        }
        
        return $data;
    }

    public function clear(string $formIdentifier, string $sessionId)
    {
        $sessionKey = $this->getSessionKey($formIdentifier);
        cache()->forget($sessionKey);
    }

    protected function getSessionKey(string $formIdentifier): string
    {
        return $this->sessionPrefix . $formIdentifier;
    }

    // Helper method to check if form has expired
    public function hasExpired(array $data): bool
    {
        if (empty($data) || !isset($data['created_at'])) {
            return true;
        }

        $created = new \DateTime($data['created_at']);
        $expires = $created->modify("+{$this->expirationHours} hours");
        
        return $expires < now();
    }
}