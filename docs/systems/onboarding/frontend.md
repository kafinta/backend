# Frontend Integration Guide

## Overview
This guide provides instructions for integrating the onboarding system with frontend applications. The system implements a step-by-step approach for seller registration with required and optional steps.

## Implementation Steps

### 1. Setup Onboarding Service

```typescript
// onboarding.service.ts
export class OnboardingService {
    private baseURL: string;

    constructor() {
        this.baseURL = '/api/seller';
    }

    public async startOnboarding(data: {
        business_name: string;
        business_category: string;
        phone_number: string;
    }) {
        try {
            const response = await fetch(`${this.baseURL}/start-onboarding`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async checkProgress() {
        try {
            const response = await fetch(`${this.baseURL}/progress`);
            return await response.json();
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async verifyPhone(data: {
        phone_number: string;
        verification_code: string;
    }) {
        try {
            const response = await fetch(`${this.baseURL}/verify-phone`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async updateProfile(data: {
        business_name?: string;
        business_description?: string;
        business_category?: string;
        business_address?: string;
        business_phone?: string;
        business_email?: string;
    }) {
        try {
            const response = await fetch(`${this.baseURL}/update-profile`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async acceptAgreement() {
        try {
            const response = await fetch(`${this.baseURL}/accept-agreement`, {
                method: 'POST'
            });
            return await response.json();
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async updatePaymentInfo(data: {
        bank_name: string;
        account_number: string;
        account_name: string;
        payment_methods: string[];
    }) {
        try {
            const response = await fetch(`${this.baseURL}/update-payment-info`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async updateSocialMedia(data: {
        facebook?: string;
        instagram?: string;
        twitter?: string;
        website?: string;
    }) {
        try {
            const response = await fetch(`${this.baseURL}/update-social-media`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async completeOnboarding() {
        try {
            const response = await fetch(`${this.baseURL}/complete-onboarding`, {
                method: 'POST'
            });
            return await response.json();
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

### 2. State Management
The onboarding service can be integrated with any state management solution. Here's an example using a simple store pattern:

```typescript
// onboarding.store.ts
export class OnboardingStore {
    private progress: any = null;
    private loading: boolean = false;
    private error: any = null;
    private onboardingService: OnboardingService;

    constructor() {
        this.onboardingService = new OnboardingService();
    }

    async startOnboarding(data: any) {
        try {
            this.loading = true;
            const response = await this.onboardingService.startOnboarding(data);
            this.progress = response.progress;
            this.error = null;
            return response;
        } catch (error) {
            this.error = error;
            throw error;
        } finally {
            this.loading = false;
        }
    }

    // ... similar methods for other operations
}
```

### 3. Component Example
Here's a framework-agnostic example of an onboarding form component:

```typescript
// OnboardingForm.ts
export class OnboardingForm {
    private formData = {
        business_name: '',
        business_category: '',
        phone_number: ''
    };

    constructor(private onboardingStore: OnboardingStore) {}

    async handleSubmit(event: Event) {
        event.preventDefault();
        try {
            await this.onboardingStore.startOnboarding(this.formData);
            // Handle success
        } catch (error) {
            // Handle error
        }
    }

    updateField(field: string, value: any) {
        this.formData[field] = value;
    }
}
```

## Best Practices

### 1. Progress Management
- Track completion status
- Save progress automatically
- Handle session timeouts
- Implement proper validation

### 2. Error Handling
- Show clear error messages
- Handle validation errors
- Implement retry mechanism
- Log errors properly

### 3. User Experience
- Show clear progress
- Provide helpful guidance
- Handle loading states
- Implement proper validation

### 4. Security
- Validate all inputs
- Handle sensitive data
- Implement proper error handling
- Follow security best practices

## Common Issues and Solutions

### 1. Progress Tracking
```typescript
// Handle progress tracking
const handleProgressTracking = async () => {
    try {
        const progress = await checkProgress();
        if (progress.required_steps.some(step => !step.completed)) {
            // Handle incomplete required steps
            showIncompleteSteps(progress.required_steps);
        }
    } catch (error) {
        // Handle error
        showError(error.message);
    }
};
```

### 2. Phone Verification
```typescript
// Handle phone verification
const handlePhoneVerification = async (phoneNumber: string, code: string) => {
    try {
        if (!phoneNumber || !code) {
            throw new Error('Phone number and verification code are required.');
        }

        await verifyPhone({ phone_number: phoneNumber, verification_code: code });
    } catch (error) {
        // Handle error
        showError(error.message);
    }
};
```

### 3. Profile Update
```typescript
// Handle profile update
const handleProfileUpdate = async (profileData: any) => {
    try {
        // Validate required fields
        const requiredFields = ['business_name', 'business_category'];
        const missingFields = requiredFields.filter(field => !profileData[field]);
        
        if (missingFields.length > 0) {
            throw new Error(`Missing required fields: ${missingFields.join(', ')}`);
        }

        await updateProfile(profileData);
    } catch (error) {
        // Handle error
        showError(error.message);
    }
};
```

## Testing

### 1. Unit Tests
```typescript
// onboarding.service.test.ts
describe('OnboardingService', () => {
    it('should start onboarding successfully', async () => {
        const onboardingService = new OnboardingService();
        const response = await onboardingService.startOnboarding({
            business_name: 'Test Business',
            business_category: 'Retail',
            phone_number: '+1234567890'
        });
        expect(response.progress).toBeDefined();
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
        expect(startResponse.progress).toBeDefined();
        
        // Verify phone
        const verifyResponse = await verifyPhone(phoneData);
        expect(verifyResponse.verified).toBe(true);
        
        // Update profile
        const profileResponse = await updateProfile(profileData);
        expect(profileResponse.updated).toBe(true);
        
        // Complete onboarding
        const completeResponse = await completeOnboarding();
        expect(completeResponse.completed).toBe(true);
    });
});
```

## Next Steps
1. Review the [API Documentation](api.md) for endpoint details
2. Check the [Roadmap](roadmap.md) for upcoming features
3. Implement additional security measures
4. Add error tracking and monitoring 