# Frontend Integration Guide

## Overview
This guide provides instructions for integrating the onboarding system with frontend applications. The onboarding system uses a step-by-step approach with both required and optional steps.

## Implementation Steps

### 1. Setup Onboarding Service

```typescript
// onboarding.service.ts
import axios from 'axios';

export class OnboardingService {
    private static instance: OnboardingService;

    private constructor() {}

    public static getInstance(): OnboardingService {
        if (!OnboardingService.instance) {
            OnboardingService.instance = new OnboardingService();
        }
        return OnboardingService.instance;
    }

    public async startOnboarding(data: {
        business_name: string;
        business_category: string;
        phone_number: string;
    }) {
        try {
            const response = await axios.post('/api/seller/start-onboarding', data);
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async getProgress() {
        try {
            const response = await axios.get('/api/seller/progress');
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async verifyPhone(data: {
        phone_number: string;
        verification_code: string;
    }) {
        try {
            const response = await axios.post('/api/seller/verify-phone', data);
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async updateProfile(data: {
        business_name: string;
        business_description: string;
        business_address: string;
        business_category: string;
        years_in_business: number;
        business_website: string;
    }) {
        try {
            const response = await axios.post('/api/seller/update-profile', data);
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async acceptAgreement() {
        try {
            const response = await axios.post('/api/seller/accept-agreement');
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async completeOnboarding() {
        try {
            const response = await axios.post('/api/seller/complete-onboarding');
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    private handleError(error: any) {
        if (error.response) {
            return error.response.data;
        }
        return new Error('An unexpected error occurred');
    }
}
```

### 2. Create Onboarding Context

```typescript
// OnboardingContext.tsx
import React, { createContext, useContext, useState, useEffect } from 'react';
import { OnboardingService } from './onboarding.service';

interface OnboardingContextType {
    progress: any;
    currentStep: string;
    startOnboarding: (data: any) => Promise<void>;
    verifyPhone: (data: any) => Promise<void>;
    updateProfile: (data: any) => Promise<void>;
    acceptAgreement: () => Promise<void>;
    completeOnboarding: () => Promise<void>;
}

const OnboardingContext = createContext<OnboardingContextType | undefined>(undefined);

export const OnboardingProvider: React.FC = ({ children }) => {
    const [progress, setProgress] = useState(null);
    const [currentStep, setCurrentStep] = useState('');
    const onboardingService = OnboardingService.getInstance();

    useEffect(() => {
        // Load initial progress
        loadProgress();
    }, []);

    const loadProgress = async () => {
        try {
            const response = await onboardingService.getProgress();
            setProgress(response.progress);
            setCurrentStep(determineCurrentStep(response.progress));
        } catch (error) {
            console.error('Error loading progress:', error);
        }
    };

    const determineCurrentStep = (progress: any) => {
        // Logic to determine current step based on progress
        return 'start';
    };

    const startOnboarding = async (data: any) => {
        const response = await onboardingService.startOnboarding(data);
        await loadProgress();
    };

    const verifyPhone = async (data: any) => {
        const response = await onboardingService.verifyPhone(data);
        await loadProgress();
    };

    const updateProfile = async (data: any) => {
        const response = await onboardingService.updateProfile(data);
        await loadProgress();
    };

    const acceptAgreement = async () => {
        const response = await onboardingService.acceptAgreement();
        await loadProgress();
    };

    const completeOnboarding = async () => {
        const response = await onboardingService.completeOnboarding();
        await loadProgress();
    };

    return (
        <OnboardingContext.Provider value={{
            progress,
            currentStep,
            startOnboarding,
            verifyPhone,
            updateProfile,
            acceptAgreement,
            completeOnboarding
        }}>
            {children}
        </OnboardingContext.Provider>
    );
};

export const useOnboarding = () => {
    const context = useContext(OnboardingContext);
    if (context === undefined) {
        throw new Error('useOnboarding must be used within an OnboardingProvider');
    }
    return context;
};
```

### 3. Create Onboarding Components

```typescript
// OnboardingProgress.tsx
import React from 'react';
import { useOnboarding } from './OnboardingContext';

export const OnboardingProgress: React.FC = () => {
    const { progress } = useOnboarding();

    if (!progress) return null;

    return (
        <div className="onboarding-progress">
            <h2>Onboarding Progress</h2>
            <div className="progress-bar">
                <div 
                    className="progress-fill"
                    style={{ width: `${progress.completion_summary.completion_percentage}%` }}
                />
            </div>
            <div className="steps">
                {progress.required_steps.map((step: any) => (
                    <div key={step.id} className={`step ${step.completed ? 'completed' : ''}`}>
                        <h3>{step.name}</h3>
                        <p>{step.benefit}</p>
                        <span>Estimated time: {step.estimated_time}</span>
                    </div>
                ))}
            </div>
        </div>
    );
};
```

## Best Practices

### 1. Progress Management
- Track completion status
- Validate required steps
- Monitor optional steps
- Handle timeouts

### 2. Error Handling
- Implement proper error handling
- Show user-friendly error messages
- Handle network errors gracefully
- Implement retry mechanism

### 3. User Experience
- Show loading states
- Implement proper form validation
- Provide clear feedback
- Handle session timeouts

### 4. Security
- Validate all inputs
- Secure sensitive data
- Implement proper error handling
- Follow security best practices

## Common Issues and Solutions

### 1. Progress Tracking
```typescript
// Handle progress updates
const updateProgress = async () => {
    try {
        await loadProgress();
    } catch (error) {
        // Handle error
        showError('Failed to update progress');
    }
};
```

### 2. Form Validation
```typescript
// Implement form validation
const validateForm = (values: any) => {
    const errors: any = {};
    
    if (!values.business_name) {
        errors.business_name = 'Business name is required';
    }
    
    if (!values.phone_number) {
        errors.phone_number = 'Phone number is required';
    } else if (!isValidPhoneNumber(values.phone_number)) {
        errors.phone_number = 'Invalid phone number';
    }
    
    return errors;
};
```

### 3. Error Handling
```typescript
// Handle API errors
try {
    await apiCall();
} catch (error) {
    if (error.response) {
        // Handle API error
        showError(error.response.data.message);
    } else {
        // Handle network error
        showError('Network error occurred');
    }
}
```

## Testing

### 1. Unit Tests
```typescript
// onboarding.service.test.ts
describe('OnboardingService', () => {
    it('should start onboarding successfully', async () => {
        const onboardingService = OnboardingService.getInstance();
        const response = await onboardingService.startOnboarding({
            business_name: 'Test Business',
            business_category: 'Retail',
            phone_number: '+1234567890'
        });
        expect(response.seller).toBeDefined();
    });
});
```

### 2. Integration Tests
```typescript
// onboarding.integration.test.ts
describe('Onboarding Flow', () => {
    it('should complete full onboarding flow', async () => {
        // Start onboarding
        const startResponse = await startOnboarding(data);
        expect(startResponse.seller).toBeDefined();
        
        // Verify phone
        const verifyResponse = await verifyPhone(verificationData);
        expect(verifyResponse.seller.phone_verified_at).toBeDefined();
        
        // Complete onboarding
        const completeResponse = await completeOnboarding();
        expect(completeResponse.seller.onboarding_completed_at).toBeDefined();
    });
});
```

## Next Steps
1. Review the [API Documentation](api.md) for endpoint details
2. Check the [Roadmap](roadmap.md) for upcoming features
3. Implement additional security measures
4. Add error tracking and monitoring 