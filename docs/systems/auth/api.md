# Authentication API Documentation

## Endpoints

### Registration

#### Register New User
```http
POST /api/auth/register
```

**Request Body:**
```json
{
    "name": "string",
    "email": "string",
    "password": "string",
    "password_confirmation": "string"
}
```

**Response (201 Created):**
```json
{
    "message": "User registered successfully",
    "user": {
        "id": "integer",
        "name": "string",
        "email": "string",
        "created_at": "datetime"
    }
}
```

### Authentication

#### Login
```http
POST /api/auth/login
```

**Request Body:**
```json
{
    "email": "string",
    "password": "string"
}
```

**Response (200 OK):**
```json
{
    "message": "Login successful",
    "user": {
        "id": "integer",
        "name": "string",
        "email": "string"
    }
}
```

#### Logout
```http
POST /api/auth/logout
```

**Response (200 OK):**
```json
{
    "message": "Successfully logged out"
}
```

### Password Management

#### Forgot Password
```http
POST /api/auth/forgot-password
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
    "message": "Password reset link sent to your email"
}
```

#### Reset Password
```http
POST /api/auth/reset-password
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
    "message": "Password has been reset successfully"
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
    "message": "Too many attempts",
    "retry_after": "integer"
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