<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class MultistepFormService
{
    protected $sessionKey;
    protected $steps;
    protected $currentStep;
    
    public function __construct(string $formIdentifier = 'default_form')
    {
        $this->sessionKey = "form_data_{$formIdentifier}";
        $this->currentStep = Session::get("{$this->sessionKey}_step", 1);
        $this->steps = [];
    }
    
    public function addStep(string $name, array $validationRules, ?callable $beforeCallback = null, ?callable $afterCallback = null)
    {
        $this->steps[] = [
            'name' => $name,
            'rules' => $validationRules,
            'beforeCallback' => $beforeCallback,
            'afterCallback' => $afterCallback
        ];
        
        return $this;
    }
    
    public function process(Request $request)
    {
        // Get current step from request or session
        $currentStepIndex = $request->input('step', $this->currentStep) - 1;
        if (!isset($this->steps[$currentStepIndex])) {
            throw new \Exception('Invalid step');
        }
        
        $currentStep = $this->steps[$currentStepIndex];
        
        // Run before callback if exists
        if ($currentStep['beforeCallback']) {
            call_user_func($currentStep['beforeCallback'], $request);
        }
        
        // Validate only the current step's rules
        $validator = Validator::make(
            $request->all(),
            $currentStep['rules']
        );
        
        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors()
            ];
        }
        
        // Store the current step's data
        $formData = Session::get($this->sessionKey, []);
        
        // Handle file uploads differently
        if ($request->hasFile('images')) {
            // Store file information instead of file objects
            $images = [];
            foreach ($request->file('images') as $image) {
                // Store the file temporarily and save its path
                $path = $image->store('temp/product-images', 'public');
                $images[] = [
                    'path' => $path,
                    'original_name' => $image->getClientOriginalName(),
                    'mime_type' => $image->getMimeType(),
                    'size' => $image->getSize()
                ];
            }
            $formData[$currentStep['name']] = [
                'images' => $images
            ];
        } else {
            $formData[$currentStep['name']] = $request->only(array_keys($currentStep['rules']));
        }
        
        Session::put($this->sessionKey, $formData);
        Session::put("{$this->sessionKey}_step", $currentStepIndex + 1);
        
        // Run after callback if exists
        if ($currentStep['afterCallback']) {
            call_user_func($currentStep['afterCallback'], $request, $formData);
        }
        
        // Move to next step or complete
        if ($currentStepIndex + 1 < count($this->steps)) {
            return [
                'success' => true,
                'completed' => false,
                'nextStep' => $currentStepIndex + 2,
                'current_data' => $formData
            ];
        }
        
        // Form completed
        $finalData = $formData;
        $this->reset(); // Clear the session data
        
        return [
            'success' => true,
            'completed' => true,
            'data' => $finalData
        ];
    }
    
    public function getCurrentStep(): int
    {
        return $this->currentStep;
    }
    
    public function getStoredData(): array
    {
        return Session::get($this->sessionKey, []);
    }
    
    public function reset()
    {
        Session::forget($this->sessionKey);
        Session::forget("{$this->sessionKey}_step");
    }
}