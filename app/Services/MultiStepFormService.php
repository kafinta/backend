<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MultiStepFormService
{
    /**
     * Manage multi-step form submission
     * 
     * @param array $data Current step data
     * @param string $currentStep Current step identifier
     * @param array $stepRules Validation rules for steps
     * @param array $stepSequence Sequence of steps
     * @return array
     */
    public function handleFormStep(
        array $data, 
        string $currentStep, 
        array $stepRules, 
        array $stepSequence
    ) {
        // Validate current step
        $validator = Validator::make($data, $stepRules[$currentStep] ?? []);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Store step data in session
        $formData = session('multistep_form', []);
        $formData[$currentStep] = $validator->validated();
        session(['multistep_form' => $formData]);

        // Determine next step
        $currentIndex = array_search($currentStep, $stepSequence);
        $nextStep = $stepSequence[$currentIndex + 1] ?? null;

        return [
            'status' => 'success',
            'message' => 'Step validated successfully',
            'next_step' => $nextStep
        ];
    }

    /**
     * Complete the multi-step form submission
     * 
     * @param array $stepRules Validation rules for all steps
     * @param callable $finalProcessor Callback to process final submission
     * @return mixed
     */
    public function submitMultiStepForm(
        array $stepRules, 
        callable $finalProcessor
    ) {
        // Retrieve all form data from session
        $formData = session('multistep_form', []);

        // Prepare complete validation rules
        $allRules = [];
        foreach ($stepRules as $stepRulesSet) {
            $allRules = array_merge($allRules, $stepRulesSet);
        }

        // Initialize complete data array
        $completeData = [];

        // Merge all step data dynamically
        foreach ($formData as $stepData) {
            $completeData = array_merge($completeData, $stepData);
        }

        // Final validation
        $finalValidator = Validator::make($completeData, $allRules);

        if ($finalValidator->fails()) {
            throw new ValidationException($finalValidator);
        }

        // Process the final submission
        $result = $finalProcessor($formData);

        // Clear session
        session()->forget('multistep_form');

        return $result;
    }
}