# Authentication System

## Overview
The authentication system provides secure user authentication and authorization for the application. It implements Laravel's built-in authentication features with Sanctum for cookie-based authentication, providing enhanced security and user experience.

## Quick Start Guide

### 1. User Registration
```php
POST /api/user/signup
{
    "username": "string",
    "email": "string",
    "password": "string",
    "password_confirmation": "string"
}
```

### 2. User Login
```php
POST /api/user/login
{
    "email": "string",
    "password": "string",
    "remember_me": "boolean"
}
```

## Testing with Postman

For general Postman testing guidelines, environment setup, and best practices, please refer to the [main documentation](../README.md#api-testing-with-postman).

### Authentication-Specific Testing

#### Test Cases
1. **Registration Flow**
   - Use "Register New User" request
   - Check simulated emails for verification token
   - Update `verification_token` environment variable
   - Use "Verify Email" request
   - Note: Session cookie will be automatically set

2. **Login Flow**
   - Use "Login User" request
   - Session cookie will be automatically set
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
- Cookie-based authentication with Sanctum

### User Management
- User registration with email verification
- Secure login with session management
- Password reset functionality
- Email verification system

### Session Management
- Secure session cookies
- Automatic CSRF protection
- Session lifetime configuration
- Remember me functionality

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

1. **Cookie Settings**
   - Enable cookies in Postman
   - Use the same domain for all requests
   - Check cookie settings in debug routes

2. **Security**
   - Always use HTTPS in production
   - Keep session lifetime reasonable
   - Implement rate limiting
   - Use secure cookie settings

3. **Testing**
   - Test authentication flows end-to-end
   - Verify session persistence
   - Check security headers
   - Test rate limiting

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

## Email Verification

The system provides two methods for email verification:

1. **Token-based Verification**
   - Endpoint: `POST /api/verify-email/token`
   - Body: `{ "token": "verification_token" }`
   - Description: Verify email using the token from the verification email

2. **Code-based Verification**
   - Endpoint: `POST /api/verify-email/code`
   - Body: `{ "code": "verification_code", "email": "user@example.com" }`
   - Description: Verify email using the 6-digit verification code from the email

Both methods are available in the verification email, and either can be used to verify the user's email address.

### Resending Verification Email
- Endpoint: `POST /api/user/resend-verification-email`
- Description: Resend the verification email to the authenticated user
- Requires: Authentication

### Development Mode
In development mode, emails are simulated and stored in the `storage/simulated-emails` directory. You can:
- View simulated emails at `GET /api/simulated-emails`
- View a specific email at `GET /api/simulated-emails/{filename}`
- Delete a specific email at `DELETE /api/simulated-emails/{filename}`
- Clear all emails at `DELETE /api/simulated-emails`