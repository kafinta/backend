# Onboarding API Documentation

## Endpoints

### Onboarding Management

#### Start Onboarding
```http
POST /api/seller/start-onboarding
```

**Request Body:**
```json
{
    "business_name": "string",
    "business_category": "string",
    "phone_number": "string"
}
```

**Response (201 Created):**
```json
{
    "success": true,
    "status": "success",
    "status_code": 201,
    "message": "Onboarding started successfully",
    "data": {
        "seller": {
            "id": 1,
            "business_name": "string",
            "business_category": "string",
            "phone_number": "string",
            "onboarding_progress": 1
        }
    }
}
```

#### Get Onboarding Progress
```http
GET /api/seller/progress
```

**Response (200 OK):**
```json
{
    "success": true,
    "status": "success",
    "status_code": 200,
    "message": "Onboarding progress retrieved",
    "data": {
        "progress": {
            "can_complete": "boolean",
            "required_steps": [
                {
                    "id": "string",
                    "name": "string",
                    "completed": "boolean",
                    "required": "boolean",
                    "benefit": "string",
                    "estimated_time": "string"
                }
            ],
            "optional_steps": [
                {
                    "id": "string",
                    "name": "string",
                    "completed": "boolean",
                    "required": "boolean",
                    "benefit": "string",
                    "estimated_time": "string"
                }
            ],
            "completion_summary": {
                "required_completed": "integer",
                "required_total": "integer",
                "optional_completed": "integer",
                "optional_total": "integer",
                "total_completed": "integer",
                "total_steps": "integer",
                "completion_percentage": "integer"
            }
        }
    }
}
```

### Verification

#### Phone Verification
```http
POST /api/seller/verify-phone
```

**Request Body:**
```json
{
    "phone_number": "string",
    "verification_code": "string"
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "status": "success",
    "status_code": 200,
    "message": "Phone number verified successfully",
    "data": {
        "seller": {
            "id": 1,
            "phone_verified_at": "datetime"
        }
    }
}
```

#### KYC Verification
```http
POST /api/seller/verify-kyc
```

**Request Body:**
```json
{
    "id_type": "string",
    "id_number": "string",
    "id_document": "file"
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "status": "success",
    "status_code": 200,
    "message": "KYC verification submitted successfully",
    "data": {
        "seller": {
            "id": 1,
            "kyc_verified_at": "datetime"
        }
    }
}
```

### Business Profile

#### Update Business Profile
```http
POST /api/seller/update-profile
```

**Request Body:**
```json
{
    "business_name": "string",
    "business_description": "string",
    "business_address": "string",
    "business_category": "string",
    "years_in_business": "integer",
    "business_website": "string"
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "status": "success",
    "status_code": 200,
    "message": "Business profile updated successfully",
    "data": {
        "seller": {
            "id": 1,
            "profile_completed_at": "datetime"
        }
    }
}
```

### Agreement

#### Accept Seller Agreement
```http
POST /api/seller/accept-agreement
```

**Response (200 OK):**
```json
{
    "success": true,
    "status": "success",
    "status_code": 200,
    "message": "Seller agreement accepted successfully",
    "data": {
        "seller": {
            "id": 1,
            "agreement_completed_at": "datetime"
        }
    }
}
```

### Payment Information

#### Update Payment Information
```http
POST /api/seller/update-payment-info
```

**Request Body:**
```json
{
    "bank_name": "string",
    "bank_account_number": "string",
    "bank_account_name": "string",
    "bank_routing_number": "string",
    "payment_method": "string",
    "paypal_email": "string"
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "status": "success",
    "status_code": 200,
    "message": "Payment information updated successfully",
    "data": {
        "seller": {
            "id": 1,
            "payment_info_completed_at": "datetime"
        }
    }
}
```

### Social Media

#### Update Social Media
```http
POST /api/seller/update-social-media
```

**Request Body:**
```json
{
    "instagram_handle": "string",
    "facebook_page": "string",
    "twitter_handle": "string",
    "linkedin_page": "string",
    "tiktok_handle": "string",
    "youtube_channel": "string"
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "status": "success",
    "status_code": 200,
    "message": "Social media information updated successfully",
    "data": {
        "seller": {
            "id": 1,
            "social_media_completed_at": "datetime"
        }
    }
}
```

### Completion

#### Complete Onboarding
```http
POST /api/seller/complete-onboarding
```

**Response (200 OK):**
```json
{
    "success": true,
    "status": "success",
    "status_code": 200,
    "message": "Onboarding completed successfully",
    "data": {
        "seller": {
            "id": 1,
            "onboarding_completed_at": "datetime",
            "is_verified": true
        }
    }
}
```

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

### Not Found (404)
```json
{
    "success": false,
    "status": "fail",
    "status_code": 404,
    "message": "Seller profile not found"
}
```

### Unauthorized (401)
```json
{
    "success": false,
    "status": "fail",
    "status_code": 401,
    "message": "Unauthenticated"
}
```

### Forbidden (403)
```json
{
    "success": false,
    "status": "fail",
    "status_code": 403,
    "message": "Required steps must be completed"
} 