# Authentication API Documentation

## Endpoints

### Registration

#### Register New User
```http
POST /api/user/signup
```

**Request Body:**
```json
{
    "username": "string",
    "email": "string",
    "password": "string",
    "password_confirmation": "string"
}
```

**Response (201 Created):**
```json
{
    "message": "Account Created Successfully",
    "user": {
        "id": "integer",
        "username": "string",
        "email": "string",
        "created_at": "datetime"
    },
    "email_verification_required": true,
    "verification_email_sent": true
}
```

### Authentication

#### Login
```http
POST /api/user/login
```

**Request Body:**
```json
{
    "email": "string",
    "password": "string",
    "remember_me": "boolean"
}
```

**Response (200 OK):**
```json
{
    "message": "Login successful",
    "user": {
        "id": "integer",
        "username": "string",
        "email": "string"
    }
}
```

#### Logout
```http
POST /api/user/logout
```

**Response (200 OK):**
```json
{
    "message": "Logged out successfully"
}
```

### Password Management

#### Forgot Password
```http
POST /api/forgot-password
```

**Request Body:**
```json
{
    "email": "string"
}
```

**Response (200 OK):**
```json
{
    "message": "If an account with that email exists, a password reset link has been sent."
}
```

#### Reset Password
```http
POST /api/reset-password/token
```

**Request Body:**
```json
{
    "token": "string",
    "email": "string",
    "password": "string",
    "password_confirmation": "string"
}
```

**Response (200 OK):**
```json
{
    "message": "Password reset successfully"
}
```

## Error Responses

### Validation Errors (422 Unprocessable Entity)
```json
{
    "message": "The given data was invalid",
    "errors": {
        "field": [
            "error message"
        ]
    }
}
```

### Authentication Errors (401 Unauthorized)
```json
{
    "message": "Unauthenticated"
}
```

### Rate Limiting (429 Too Many Requests)
```json
{
    "message": "Too many login attempts. Please try again in X seconds."
}
```

## Rate Limiting

- Login attempts: 5 per minute
- Registration: 3 per hour
- Password reset: 3 per hour
- Token refresh: 60 per hour

## Security Headers

All responses include the following security headers:
```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

## Testing

### Test Credentials
```json
{
    "email": "test@example.com",
    "password": "password123"
}
```

### Test Token
```
Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

## Notes

1. All timestamps are in UTC
2. Token expiration is configurable via environment variables
3. Rate limiting can be adjusted based on requirements
4. All endpoints require HTTPS in production 