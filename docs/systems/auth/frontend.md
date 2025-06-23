# Frontend Integration Guide

## Overview
This guide provides instructions for integrating the authentication system with frontend applications. The system implements secure user authentication with support for various authentication methods and session management.

## Implementation Steps

### 1. Setup Auth Service

```typescript
// auth.service.ts
export class AuthService {
    private baseURL: string;

    constructor() {
        this.baseURL = '/api/auth';
    }

    public async login(data: {
        email: string;
        password: string;
    }) {
        try {
            const response = await fetch(`${this.baseURL}/login`, {
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

    public async register(data: {
        name: string;
        email: string;
        password: string;
        password_confirmation: string;
    }) {
        try {
            const response = await fetch(`${this.baseURL}/register`, {
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

    public async logout() {
        try {
            const response = await fetch(`${this.baseURL}/logout`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            return await response.json();
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async refreshToken() {
        try {
            const response = await fetch(`${this.baseURL}/refresh`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });
            return await response.json();
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async forgotPassword(email: string) {
        try {
            const response = await fetch(`${this.baseURL}/forgot-password`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email })
            });
            return await response.json();
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async resetPassword(data: {
        token: string;
        email: string;
        password: string;
        password_confirmation: string;
    }) {
        try {
            const response = await fetch(`${this.baseURL}/reset-password`, {
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

    private handleError(error: any) {
        if (error.response) {
            return error.response.data;
        }
        return new Error('An unexpected error occurred');
    }
}
```

### 2. State Management
The auth service can be integrated with any state management solution. Here's an example using a simple store pattern:

```typescript
// auth.store.ts
export class AuthStore {
    private user: any = null;
    private token: string | null = null;
    private loading: boolean = false;
    private error: any = null;
    private authService: AuthService;

    constructor() {
        this.authService = new AuthService();
        this.token = localStorage.getItem('token');
    }

    async login(data: any) {
        try {
            this.loading = true;
            const response = await this.authService.login(data);
            this.user = response.user;
            this.token = response.token;
            localStorage.setItem('token', response.token);
            this.error = null;
            return response;
        } catch (error) {
            this.error = error;
            throw error;
        } finally {
            this.loading = false;
        }
    }

    async logout() {
        try {
            this.loading = true;
            await this.authService.logout();
            this.user = null;
            this.token = null;
            localStorage.removeItem('token');
            this.error = null;
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
Here's a framework-agnostic example of a login form component:

```typescript
// LoginForm.ts
export class LoginForm {
    private formData = {
        email: '',
        password: ''
    };

    constructor(private authStore: AuthStore) {}

    async handleSubmit(event: Event) {
        event.preventDefault();
        try {
            await this.authStore.login(this.formData);
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

### 1. Authentication
- Implement proper token management
- Handle token refresh
- Secure token storage
- Implement proper logout

### 2. Error Handling
- Show clear error messages
- Handle validation errors
- Implement retry mechanism
- Log errors properly

### 3. User Experience
- Show loading states
- Implement proper validation
- Provide clear feedback
- Handle session timeouts

### 4. Security
- Validate all inputs
- Secure sensitive data
- Implement proper error handling
- Follow security best practices

## Common Issues and Solutions

### 1. Token Management
```typescript
// Handle token refresh
const handleTokenRefresh = async () => {
    try {
        const response = await refreshToken();
        localStorage.setItem('token', response.token);
        return response.token;
    } catch (error) {
        // Handle error
        showError(error.message);
        // Redirect to login if refresh fails
        redirectToLogin();
    }
};
```

### 2. Password Reset
```typescript
// Handle password reset
const handlePasswordReset = async (email: string) => {
    try {
        if (!email) {
            throw new Error('Email is required.');
        }

        await forgotPassword(email);
        showSuccess('Password reset instructions sent to your email.');
    } catch (error) {
        // Handle error
        showError(error.message);
    }
};
```

### 3. Form Validation
```typescript
// Handle form validation
const validateForm = (formData: any) => {
    const errors: any = {};
    
    if (!formData.email) {
        errors.email = 'Email is required';
    } else if (!isValidEmail(formData.email)) {
        errors.email = 'Invalid email format';
    }
    
    if (!formData.password) {
        errors.password = 'Password is required';
    } else if (formData.password.length < 8) {
        errors.password = 'Password must be at least 8 characters';
    }
    
    return errors;
};
```

## Handling Email Update (Verified or Unverified Users)

If a user wants to change their email address (whether verified or unverified), provide an option in the UI to update their email. Password re-entry is required for security.

### When to Show This Option
- User is authenticated and wants to change their email (e.g., typo, lost access, or new address)

### Example UI/UX Flow
1. Show a message: "Want to change your email? Enter your new email and current password."
2. Provide inputs for the new email and password, and a button to submit the change.
3. On submit, call the API endpoint below.

### Example API Call
```typescript
// PATCH /api/user/update-email
const updateEmail = async (newEmail: string, password: string, token: string) => {
    const response = await fetch('/api/user/update-email', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ email: newEmail, password })
    });
    return await response.json();
};
```

### Handling Responses
- On success, show: "Email updated successfully. Please check your new email for a verification link."
- On error, display the error message from the API (e.g., already verified, email in use, validation error, incorrect password).
- If successful, update the email in your local user state/profile.

**Response Structure:**
- Success:
```json
{
    "message": "Email updated successfully. Please check your new email for a verification link.",
    "status": 200,
    "data": {
        "verification_email_sent": true,
        "email": "new@email.com"
    }
}
```
- Error (validation):
```json
{
    "message": "The given data was invalid",
    "status": 422,
    "errors": {
        "email": ["The email field is required."]
    }
}
```
- Error (other):
```json
{
    "message": "Incorrect password.",
    "status": 401
}
```

### Security & UX Notes
- This action is rate-limited (max 3 per hour).
- Password re-entry is required for all users.
- All previous verification links/codes are invalidated when the email is changed.
- If the user was previously verified, the old email will be notified (when real email is enabled).
- No information is disclosed about whether the new email is already registered/verified (generic error message).

For full request/response details, see the [API Documentation](api.md#email-verification--update).

## Testing

### 1. Unit Tests
```typescript
// auth.service.test.ts
describe('AuthService', () => {
    it('should login successfully', async () => {
        const authService = new AuthService();
        const response = await authService.login({
            email: 'test@example.com',
            password: 'password123'
        });
        expect(response.token).toBeDefined();
    });
});
```

### 2. Integration Tests
```typescript
// auth.integration.test.ts
describe('Auth Flow', () => {
    it('should complete full authentication flow', async () => {
        // Register
        const registerResponse = await register(userData);
        expect(registerResponse.user).toBeDefined();
        
        // Login
        const loginResponse = await login(credentials);
        expect(loginResponse.token).toBeDefined();
        
        // Refresh token
        const refreshResponse = await refreshToken();
        expect(refreshResponse.token).toBeDefined();
        
        // Logout
        const logoutResponse = await logout();
        expect(logoutResponse.success).toBe(true);
    });
});
```

## Next Steps
1. Review the [API Documentation](api.md) for endpoint details
2. Check the [Roadmap](roadmap.md) for upcoming features
3. Implement additional security measures
4. Add error tracking and monitoring 