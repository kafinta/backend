# Two-Factor Authentication (2FA) Documentation

## Overview

The Two-Factor Authentication (2FA) system adds an extra layer of security to user accounts by requiring a verification code sent via email in addition to the standard email/password login.

## Table of Contents

1. [Features](#features)
2. [Architecture](#architecture)
3. [Database Schema](#database-schema)
4. [API Endpoints](#api-endpoints)
5. [Implementation Details](#implementation-details)
6. [Security Considerations](#security-considerations)
7. [User Flow](#user-flow)
8. [Testing](#testing)

## Features

- **Email-based 2FA**: 6-digit verification codes sent via email
- **Recovery Codes**: 8 unique recovery codes for account access if email is unavailable
- **Rate Limiting**: Maximum 5 verification attempts per code
- **Time-based Expiration**: Codes expire after 10 minutes
- **Password Protection**: Sensitive operations require password confirmation
- **Seamless Integration**: Works with existing authentication system

## Architecture

### Components

```
┌─────────────────┐
│  User Login     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Check 2FA       │
│ Enabled?        │
└────┬───────┬────┘
     │       │
    No      Yes
     │       │
     │       ▼
     │  ┌─────────────────┐
     │  │ Generate Code   │
     │  │ Send Email      │
     │  └────────┬────────┘
     │           │
     │           ▼
     │  ┌─────────────────┐
     │  │ User Enters     │
     │  │ Code            │
     │  └────────┬────────┘
     │           │
     │           ▼
     │  ┌─────────────────┐
     │  │ Verify Code     │
     │  └────────┬────────┘
     │           │
     ▼           ▼
┌─────────────────┐
│ Login Success   │
└─────────────────┘
```

### File Structure

```
app/
├── Http/Controllers/
│   └── UserController.php          # 2FA controller methods
├── Mail/
│   ├── EmailVerifiedEmail.php      # Email verification success
│   └── TwoFactorCodeEmail.php      # 2FA code email
├── Models/
│   └── User.php                    # User model with 2FA fields
└── Services/
    └── EmailService.php            # 2FA code generation & verification

database/migrations/
└── 2026_02_28_135901_add_two_factor_fields_to_users_table.php

resources/views/emails/
├── email-verified.blade.php        # Email verification template
└── two-factor-code.blade.php       # 2FA code email template

routes/
└── api.php                         # 2FA API routes
```

## Database Schema

### Users Table Additions

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `two_factor_enabled` | boolean | No | false | Whether 2FA is enabled for the user |
| `two_factor_secret` | text | Yes | null | Reserved for future TOTP implementation |
| `two_factor_recovery_codes` | text | Yes | null | JSON-encoded array of recovery codes |

### Migration

```php
Schema::table('users', function (Blueprint $table) {
    $table->boolean('two_factor_enabled')->default(false)->after('password');
    $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');
    $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
});
```

## API Endpoints

### Enable 2FA

**Endpoint:** `POST /api/2fa/enable`

**Authentication:** Required (Bearer Token or Session)

**Request:**
```json
{}
```

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Two-factor authentication enabled successfully",
  "data": {
    "recovery_codes": [
      "ABCDEFGHIJ",
      "KLMNOPQRST",
      "UVWXYZ1234",
      "567890ABCD",
      "EFGHIJKLMN",
      "OPQRSTUVWX",
      "YZ12345678",
      "90ABCDEFGH"
    ],
    "message": "Please save these recovery codes in a safe place. They can be used to access your account if you lose your device."
  }
}
```

**Response (Error - 400):**
```json
{
  "success": false,
  "message": "Two-factor authentication is already enabled"
}
```

### Disable 2FA

**Endpoint:** `POST /api/2fa/disable`

**Authentication:** Required

**Request:**
```json
{
  "password": "user_password"
}
```

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Two-factor authentication disabled successfully"
}
```

**Response (Error - 401):**
```json
{
  "success": false,
  "message": "Invalid password"
}
```

### Verify 2FA Code

**Endpoint:** `POST /api/2fa/verify`

**Authentication:** Required (Partial - user must be logged in from step 1)

**Request:**
```json
{
  "code": "123456"
}
```

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Two-factor authentication successful",
  "data": {
    "user": {
      "id": 1,
      "username": "johndoe",
      "email": "john@example.com"
    }
  }
}
```

**Response (Error - 400):**
```json
{
  "success": false,
  "message": "Invalid 2FA code",
  "data": {
    "attempts_remaining": 3
  }
}
```

### Get Recovery Codes

**Endpoint:** `POST /api/2fa/recovery-codes`

**Authentication:** Required

**Request:**
```json
{
  "password": "user_password"
}
```

**Response (Success - 200):**
```json
{
  "success": true,
  "message": "Recovery codes retrieved successfully",
  "data": {
    "recovery_codes": [
      "ABCDEFGHIJ",
      "KLMNOPQRST"
    ]
  }
}
```

## Implementation Details

### Code Generation

The system generates a 6-digit numeric code using PHP's `random_int()` function:

```php
$code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
```

### Code Storage

Codes are stored in Laravel's cache system with the following structure:

- **Key:** `2fa_code_{user_id}`
- **Value:** The 6-digit code
- **TTL:** 10 minutes (600 seconds)

### Verification Attempts

Failed verification attempts are tracked using:

- **Key:** `2fa_attempts_{user_id}`
- **Value:** Number of failed attempts
- **TTL:** 10 minutes (same as code)
- **Max Attempts:** 5

After 5 failed attempts, the code is invalidated and the user must request a new one.

### Recovery Codes

When 2FA is enabled, 8 recovery codes are generated:

```php
$recoveryCodes = [];
for ($i = 0; $i < 8; $i++) {
    $recoveryCodes[] = strtoupper(Str::random(10));
}
```

These codes are:
- Stored as JSON in the `two_factor_recovery_codes` column
- 10 characters long
- Uppercase alphanumeric
- Single-use (future enhancement)

## Security Considerations

### 1. Rate Limiting
- Maximum 5 verification attempts per code
- Codes expire after 10 minutes
- Failed attempts are tracked per user

### 2. Password Protection
- Disabling 2FA requires password confirmation
- Viewing recovery codes requires password confirmation
- Prevents unauthorized changes to security settings

### 3. Cache-based Storage
- Codes are stored in cache, not database
- Automatic expiration after TTL
- No persistent storage of verification codes

### 4. Email Security
- Codes sent only to verified email addresses
- Email templates clearly indicate security nature
- Includes expiration time in email

### 5. Session Management
- User is partially authenticated during 2FA verification
- Full authentication granted only after code verification
- Session regeneration on successful login

## User Flow

### Enabling 2FA

1. User navigates to security settings
2. User clicks "Enable Two-Factor Authentication"
3. System generates 8 recovery codes
4. User saves recovery codes in a secure location
5. 2FA is now enabled for the account

### Login with 2FA

1. User enters email and password
2. System validates credentials
3. If 2FA is enabled:
   - System generates 6-digit code
   - Code is sent to user's email
   - Response includes `requires_2fa: true`
4. User checks email and retrieves code
5. User enters code in application
6. System verifies code
7. If valid, user is fully authenticated
8. If invalid, user has 4 more attempts

### Disabling 2FA

1. User navigates to security settings
2. User clicks "Disable Two-Factor Authentication"
3. System prompts for password confirmation
4. User enters password
5. System validates password
6. 2FA is disabled
7. Recovery codes are deleted

## Testing

### Manual Testing

#### Test Enable 2FA

```bash
curl -X POST http://localhost:8000/api/2fa/enable \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

#### Test Login with 2FA

```bash
# Step 1: Login
curl -X POST http://localhost:8000/api/user/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'

# Step 2: Verify 2FA Code
curl -X POST http://localhost:8000/api/2fa/verify \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "123456"
  }'
```

#### Test Disable 2FA

```bash
curl -X POST http://localhost:8000/api/2fa/disable \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "password": "password123"
  }'
```

### Test Scenarios

1. **Enable 2FA**
   - ✅ User can enable 2FA
   - ✅ Recovery codes are generated
   - ✅ Cannot enable if already enabled

2. **Login with 2FA**
   - ✅ Code is sent to email
   - ✅ Valid code allows login
   - ✅ Invalid code is rejected
   - ✅ Expired code is rejected
   - ✅ Rate limiting after 5 attempts

3. **Disable 2FA**
   - ✅ Requires password confirmation
   - ✅ Invalid password is rejected
   - ✅ Recovery codes are deleted
   - ✅ Cannot disable if not enabled

4. **Recovery Codes**
   - ✅ Requires password to view
   - ✅ Returns all unused codes
   - ✅ Invalid password is rejected

## Future Enhancements

1. **TOTP Support**: Add support for authenticator apps (Google Authenticator, Authy)
2. **Recovery Code Usage**: Track and invalidate used recovery codes
3. **Backup Methods**: SMS or phone call verification
4. **Trusted Devices**: Remember devices for 30 days
5. **2FA Enforcement**: Admin option to require 2FA for all users
6. **Audit Logging**: Log all 2FA-related events
7. **Backup Email**: Allow secondary email for 2FA codes

## Troubleshooting

### Code Not Received

1. Check spam/junk folder
2. Verify email address is correct
3. Check email service logs
4. Ensure cache is working properly

### Code Invalid

1. Verify code hasn't expired (10 minutes)
2. Check for typos in code entry
3. Ensure code hasn't been used already
4. Verify user hasn't exceeded 5 attempts

### Cannot Disable 2FA

1. Verify password is correct
2. Check user is authenticated
3. Verify 2FA is actually enabled

## Support

For issues or questions, please contact the development team or create an issue in the project repository.

---

**Last Updated:** 2026-02-28
**Version:** 1.0.0

## Implementation Details

### Code Generation

The system generates a 6-digit numeric code using PHP's `random_int()` function:

`php
$code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
`

### Code Storage

Codes are stored in Laravel's cache system with the following structure:

- **Key:** `2fa_code_{user_id}`
- **Value:** The 6-digit code
- **TTL:** 10 minutes (600 seconds)

### Verification Attempts

Failed verification attempts are tracked using:

- **Key:** `2fa_attempts_{user_id}`
- **Value:** Number of failed attempts
- **TTL:** 10 minutes (same as code)
- **Max Attempts:** 5

After 5 failed attempts, the code is invalidated and the user must request a new one.

### Recovery Codes

When 2FA is enabled, 8 recovery codes are generated. These codes are:
- Stored as JSON in the `two_factor_recovery_codes` column
- 10 characters long
- Uppercase alphanumeric
- Single-use (future enhancement)

## Security Considerations

### 1. Rate Limiting
- Maximum 5 verification attempts per code
- Codes expire after 10 minutes
- Failed attempts are tracked per user

### 2. Password Protection
- Disabling 2FA requires password confirmation
- Viewing recovery codes requires password confirmation
- Prevents unauthorized changes to security settings

### 3. Cache-based Storage
- Codes are stored in cache, not database
- Automatic expiration after TTL
- No persistent storage of verification codes

### 4. Email Security
- Codes sent only to verified email addresses
- Email templates clearly indicate security nature
- Includes expiration time in email

## User Flow

### Enabling 2FA
1. User navigates to security settings
2. User clicks "Enable Two-Factor Authentication"
3. System generates 8 recovery codes
4. User saves recovery codes in a secure location
5. 2FA is now enabled for the account

### Login with 2FA
1. User enters email and password
2. System validates credentials
3. If 2FA is enabled, system generates and sends 6-digit code
4. User checks email and retrieves code
5. User enters code in application
6. System verifies code
7. If valid, user is fully authenticated

### Disabling 2FA
1. User navigates to security settings
2. User clicks "Disable Two-Factor Authentication"
3. System prompts for password confirmation
4. User enters password and 2FA is disabled

## Future Enhancements

1. **TOTP Support**: Add support for authenticator apps
2. **Recovery Code Usage**: Track and invalidate used recovery codes
3. **Trusted Devices**: Remember devices for 30 days
4. **2FA Enforcement**: Admin option to require 2FA for all users

---

**Last Updated:** 2026-02-28  
**Version:** 1.0.0
