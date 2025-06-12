# Frontend Integration Guide

## Overview
This guide provides instructions for integrating the authentication system with frontend applications. The authentication system uses token-based authentication with JWT tokens.

## Implementation Steps

### 1. Setup Authentication Service

```typescript
// auth.service.ts
import axios from 'axios';

export class AuthService {
    private static instance: AuthService;
    private token: string | null = null;

    private constructor() {
        this.token = localStorage.getItem('token');
    }

    public static getInstance(): AuthService {
        if (!AuthService.instance) {
            AuthService.instance = new AuthService();
        }
        return AuthService.instance;
    }

    public async login(email: string, password: string) {
        try {
            const response = await axios.post('/api/auth/login', {
                email,
                password
            });
            
            this.setToken(response.data.token.access_token);
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async register(userData: {
        name: string;
        email: string;
        password: string;
        password_confirmation: string;
    }) {
        try {
            const response = await axios.post('/api/auth/register', userData);
            this.setToken(response.data.token.access_token);
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async logout() {
        try {
            await axios.post('/api/auth/logout', {}, {
                headers: this.getAuthHeader()
            });
            this.clearToken();
        } catch (error) {
            throw this.handleError(error);
        }
    }

    private setToken(token: string) {
        this.token = token;
        localStorage.setItem('token', token);
    }

    private clearToken() {
        this.token = null;
        localStorage.removeItem('token');
    }

    public getAuthHeader() {
        return {
            Authorization: `Bearer ${this.token}`
        };
    }

    private handleError(error: any) {
        if (error.response) {
            return error.response.data;
        }
        return new Error('An unexpected error occurred');
    }
}
```

### 2. Setup Axios Interceptor

```typescript
// axios.config.ts
import axios from 'axios';
import { AuthService } from './auth.service';

// Add request interceptor
axios.interceptors.request.use(
    (config) => {
        const authService = AuthService.getInstance();
        const token = authService.getAuthHeader();
        
        if (token) {
            config.headers = {
                ...config.headers,
                ...token
            };
        }
        
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Add response interceptor
axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config;

        // Handle token refresh
        if (error.response.status === 401 && !originalRequest._retry) {
            originalRequest._retry = true;
            
            try {
                const authService = AuthService.getInstance();
                const response = await axios.post('/api/auth/refresh');
                authService.setToken(response.data.access_token);
                
                return axios(originalRequest);
            } catch (refreshError) {
                // Handle refresh token failure
                authService.clearToken();
                window.location.href = '/login';
                return Promise.reject(refreshError);
            }
        }

        return Promise.reject(error);
    }
);
```

### 3. Create Authentication Context

```typescript
// AuthContext.tsx
import React, { createContext, useContext, useState, useEffect } from 'react';
import { AuthService } from './auth.service';

interface AuthContextType {
    isAuthenticated: boolean;
    user: any | null;
    login: (email: string, password: string) => Promise<void>;
    logout: () => Promise<void>;
    register: (userData: any) => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC = ({ children }) => {
    const [isAuthenticated, setIsAuthenticated] = useState(false);
    const [user, setUser] = useState(null);
    const authService = AuthService.getInstance();

    useEffect(() => {
        // Check authentication status on mount
        const token = localStorage.getItem('token');
        if (token) {
            setIsAuthenticated(true);
            // Fetch user data
        }
    }, []);

    const login = async (email: string, password: string) => {
        const response = await authService.login(email, password);
        setUser(response.user);
        setIsAuthenticated(true);
    };

    const logout = async () => {
        await authService.logout();
        setUser(null);
        setIsAuthenticated(false);
    };

    const register = async (userData: any) => {
        const response = await authService.register(userData);
        setUser(response.user);
        setIsAuthenticated(true);
    };

    return (
        <AuthContext.Provider value={{
            isAuthenticated,
            user,
            login,
            logout,
            register
        }}>
            {children}
        </AuthContext.Provider>
    );
};

export const useAuth = () => {
    const context = useContext(AuthContext);
    if (context === undefined) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
};
```

## Best Practices

### 1. Token Management
- Store tokens securely (localStorage for web, secure storage for mobile)
- Implement token refresh mechanism
- Clear tokens on logout
- Handle token expiration

### 2. Error Handling
- Implement proper error handling for all API calls
- Show user-friendly error messages
- Handle network errors gracefully
- Implement retry mechanism for failed requests

### 3. Security
- Use HTTPS for all API calls
- Implement CSRF protection
- Sanitize user inputs
- Implement rate limiting on the frontend
- Clear sensitive data on logout

### 4. User Experience
- Show loading states during authentication
- Implement proper form validation
- Provide clear feedback for user actions
- Handle session timeouts gracefully

## Common Issues and Solutions

### 1. Token Expiration
```typescript
// Handle token expiration
if (error.response.status === 401) {
    // Attempt to refresh token
    try {
        const response = await axios.post('/api/auth/refresh');
        // Update token and retry request
    } catch (refreshError) {
        // Redirect to login
    }
}
```

### 2. Network Errors
```typescript
// Handle network errors
try {
    await apiCall();
} catch (error) {
    if (!error.response) {
        // Network error
        showNetworkError();
    }
}
```

### 3. Form Validation
```typescript
// Implement form validation
const validateForm = (values: any) => {
    const errors: any = {};
    
    if (!values.email) {
        errors.email = 'Email is required';
    } else if (!/\S+@\S+\.\S+/.test(values.email)) {
        errors.email = 'Email is invalid';
    }
    
    if (!values.password) {
        errors.password = 'Password is required';
    } else if (values.password.length < 8) {
        errors.password = 'Password must be at least 8 characters';
    }
    
    return errors;
};
```

## Testing

### 1. Unit Tests
```typescript
// auth.service.test.ts
describe('AuthService', () => {
    it('should login successfully', async () => {
        const authService = AuthService.getInstance();
        const response = await authService.login('test@example.com', 'password');
        expect(response.token).toBeDefined();
    });
});
```

### 2. Integration Tests
```typescript
// auth.integration.test.ts
describe('Authentication Flow', () => {
    it('should complete full authentication flow', async () => {
        // Register
        const registerResponse = await register(userData);
        expect(registerResponse.user).toBeDefined();
        
        // Login
        const loginResponse = await login(credentials);
        expect(loginResponse.token).toBeDefined();
        
        // Logout
        await logout();
        expect(localStorage.getItem('token')).toBeNull();
    });
});
```

## Next Steps
1. Review the [API Documentation](api.md) for endpoint details
2. Check the [Roadmap](roadmap.md) for upcoming features
3. Implement additional security measures as needed
4. Add error tracking and monitoring 