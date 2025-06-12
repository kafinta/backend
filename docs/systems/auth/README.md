# Authentication System

## Overview
The authentication system provides secure user authentication and authorization for the application. It implements Laravel's built-in authentication features with additional customizations for enhanced security and user experience.

## Quick Start Guide

### 1. User Registration
```php
POST /api/auth/register
{
    "name": "string",
    "email": "string",
    "password": "string",
    "password_confirmation": "string"
}
```

### 2. User Login
```php
POST /api/auth/login
{
    "email": "string",
    "password": "string"
}
```

### 3. Token Management
- Access tokens are issued upon successful login
- Tokens are automatically refreshed when valid
- Tokens can be revoked manually or automatically expire

## Testing with Postman

For general Postman testing guidelines, environment setup, and best practices, please refer to the [main documentation](../README.md#api-testing-with-postman).

### Authentication-Specific Testing

#### Test Cases
1. **Registration Flow**
   - Use "Register New User" request
   - Check simulated emails for verification token
   - Update `verification_token` environment variable
   - Use "Verify Email" request

2. **Login Flow**
   - Use "Login User" request
   - Check debug routes to verify authentication
   - Verify cookie settings

3. **Password Reset Flow**
   - Use "Forgot Password" request
   - Check simulated emails for reset token
   - Update `reset_token` environment variable
   - Use "Reset Password" request

#### Auth-Specific Debug Routes
- `/api/debug/auth-test`: Check authentication state
- `/api/debug/cookie-settings`: Verify cookie configuration
- `/api/simulated-emails`: View development emails

#### Auth-Specific Environment Variables
```json
{
    "verification_token": "",
    "reset_token": ""
}
```

## Key Features

### Security
- Password hashing using bcrypt
- CSRF protection
- Rate limiting on authentication endpoints
- Secure session management
- Token-based authentication

### User Management
- User registration with email verification
- Password reset functionality
- Account deactivation
- Session management
- Role-based access control

### Token System
- JWT-based authentication
- Token refresh mechanism
- Token revocation
- Automatic token expiration

## Dependencies

### Core Dependencies
- Laravel Framework
- Laravel Sanctum (for API authentication)
- Laravel Mail (for email verification)

### Development Dependencies
- PHPUnit (for testing)
- Laravel Dusk (for browser testing)

## Configuration

### Environment Variables
```env
AUTH_DRIVER=sanctum
TOKEN_EXPIRY=3600
REFRESH_TOKEN_EXPIRY=604800
```

### Security Settings
- Password minimum length: 8 characters
- Password requirements: uppercase, lowercase, numbers, special characters
- Maximum login attempts: 5
- Lockout duration: 15 minutes

## Best Practices
1. Always use HTTPS in production
2. Implement rate limiting on authentication endpoints
3. Use secure password hashing
4. Implement proper session management
5. Regular security audits
6. Keep dependencies updated

## Common Issues and Solutions
1. Token expiration
   - Solution: Implement refresh token mechanism
2. Rate limiting
   - Solution: Implement exponential backoff
3. Session management
   - Solution: Use secure session configuration

## Next Steps
1. Review the [API Documentation](api.md) for detailed endpoint information
2. Check the [Frontend Integration Guide](frontend.md) for implementation details
3. See the [Roadmap](roadmap.md) for planned features and improvements 