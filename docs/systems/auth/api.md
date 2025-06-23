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
    "success": true,
    "status": "success",
    "status_code": 201,
    "message": "Account Created Successfully",
    "data": {
        "user": {
            "id": 1,
            "username": "string",
            "email": "string",
            "created_at": "datetime"
        },
        "email_verification_required": true,
        "verification_email_sent": true
    }
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
    "success": true,
    "status": "success",
    "status_code": 200,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "username": "string",
            "email": "string"
        }
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
    "success": true,
    "status": "success",
    "status_code": 200,
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
    "success": true,
    "status": "success",
    "status_code": 200,
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
    "success": true,
    "status": "success",
    "status_code": 200,
    "message": "Password reset successfully"
}
```

### Email Verification & Update

#### Update Email (Verified or Unverified Users)
```http
PATCH /api/user/update-email
```

**Description:**
Allows authenticated users to update their email address, whether they are verified or unverified. Password re-entry is required. If the user was previously verified, they will need to verify the new email and the old email will be notified (when real email is enabled).

**Request Headers:**
- `Authorization: Bearer <token>` (or session cookie)

**Request Body:**
```json
{
    "email": "new@email.com",
    "password": "user_password"
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "status": "success",
    "status_code": 200,
    "message": "Email updated successfully. Please check your new email for a verification link.",
    "data": {
        "verification_email_sent": true,
        "email": "new@email.com"
    }
}
```

**Error Responses:**
- If the new email is the same as the current email:
```json
{
    "success": false,
    "status": "fail",
    "status_code": 400,
    "message": "New email is the same as the current email."
}
```
- If the new email is already in use by a verified account:
```json
{
    "success": false,
    "status": "fail",
    "status_code": 400,
    "message": "Unable to update email."
}
```
- If the password is incorrect:
```json
{
    "success": false,
    "status": "fail",
    "status_code": 401,
    "message": "Incorrect password."
}
```
- Validation errors:
```json
{
    "success": false,
    "status": "fail",
    "status_code": 422,
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

**Rate Limiting:**
- Max 3 requests per 60 minutes per user.

**Security & UX Safeguards:**
- Password re-entry required for all users.
- All previous verification tokens are invalidated when the email is changed.
- A new verification email is sent to the updated address.
- If the user was previously verified, the old email will be notified (when real email is enabled).
- No information is disclosed about whether the new email is already registered/verified (generic error message).
- All changes are logged for audit purposes.

## Error Responses

### Validation Errors (422 Unprocessable Entity)
```json
{
    "success": false,
    "status": "fail",
    "status_code": 422,
    "message": "Validation failed",
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
    "success": false,
    "status": "fail",
    "status_code": 401,
    "message": "Unauthenticated"
}
```

### Rate Limiting (429 Too Many Requests)
```json
{
    "success": false,
    "status": "fail",
    "status_code": 429,
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