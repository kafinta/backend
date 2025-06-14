# Onboarding System

## Overview
The onboarding system provides a comprehensive step-by-step process for seller registration and verification. It implements a flexible, secure, and user-friendly approach to seller onboarding with both required and optional steps.

## Quick Start Guide

### 1. Start Onboarding
```php
POST /api/seller/start-onboarding
{
    "business_name": "string",
    "business_category": "string",
    "phone_number": "string"
}
```

### 2. Verify Phone
```php
POST /api/seller/verify-phone
{
    "phone_number": "string",
    "verification_code": "string"
}
```

## Testing with Postman

For general Postman testing guidelines, environment setup, and best practices, please refer to the [main documentation](../README.md#api-testing-with-postman).

### Onboarding-Specific Testing

#### Test Cases
1. **Basic Onboarding Flow**
   - Use "Start Onboarding" request
   - Complete phone verification
   - Update business profile
   - Accept seller agreement
   - Complete onboarding

2. **Optional Steps Flow**
   - Complete KYC verification
   - Add payment information
   - Link social media accounts

3. **Progress Tracking**
   - Check onboarding progress
   - Verify step completion
   - Monitor completion percentage

#### Onboarding-Specific Debug Routes
- `/api/seller/progress`: Check onboarding progress
- `/api/seller/status`: Verify seller status
- `/api/seller/verification-status`: Check verification status

## Key Features

### Security
- Multi-step verification process
- KYC integration
- Secure data storage
- Agreement version tracking
- IP address logging

### User Management
- Step-by-step onboarding
- Progress tracking
- Flexible completion order
- Optional steps support
- Social media integration

### Business Profile
- Comprehensive business information
- Category management
- Payment integration
- Social media linking
- Document verification

## Dependencies

### Core Dependencies
- Laravel Framework
- Laravel Sanctum
- KYC Service
- Payment Gateway
- Email Service

### Development Dependencies
- PHPUnit
- Laravel Dusk
- Postman Collection

## Configuration

### Environment Variables
```env
ONBOARDING_REQUIRED_STEPS=email,phone,profile,agreement
ONBOARDING_OPTIONAL_STEPS=kyc,payment,social
KYC_PROVIDER=default
PAYMENT_GATEWAY=default
```

### Security Settings
- Phone verification timeout: 10 minutes
- KYC verification timeout: 24 hours
- Maximum retry attempts: 3
- Document size limit: 5MB

## Best Practices

1. **Progress Management**
   - Track completion status
   - Validate required steps
   - Monitor optional steps
   - Handle timeouts

2. **Security**
   - Verify all documents
   - Validate business information
   - Secure payment details
   - Protect sensitive data

3. **Testing**
   - Test all onboarding flows
   - Verify progress tracking
   - Check validation rules
   - Test error handling

## Common Issues and Solutions

1. **Verification Timeouts**
   - Solution: Implement retry mechanism
   - Solution: Extend timeout period

2. **Document Upload**
   - Solution: Validate file types
   - Solution: Check file size

3. **Progress Tracking**
   - Solution: Implement session storage
   - Solution: Add progress persistence

## Next Steps
1. Review the [API Documentation](api.md) for detailed endpoint information
2. Check the [Frontend Integration Guide](frontend.md) for implementation details
3. See the [Roadmap](roadmap.md) for planned features and improvements

## Verification Process

The system provides multiple verification methods:

1. **Phone Verification**
   - Endpoint: `POST /api/seller/verify-phone`
   - Process: Send code, verify, update status
   - Timeout: 10 minutes

2. **KYC Verification**
   - Endpoint: `POST /api/seller/verify-kyc`
   - Process: Upload documents, verify, update status
   - Timeout: 24 hours

3. **Business Profile Verification**
   - Endpoint: `POST /api/seller/verify-profile`
   - Process: Validate information, check documents
   - Timeout: 48 hours

### Resending Verification
- Phone: `POST /api/seller/resend-phone-verification`
- KYC: `POST /api/seller/resend-kyc-verification`

### Development Mode
In development mode, verifications are simulated:
- View verification status at `GET /api/seller/verification-status`
- Simulate verification at `POST /api/seller/simulate-verification`
- Clear verification data at `DELETE /api/seller/clear-verification` 