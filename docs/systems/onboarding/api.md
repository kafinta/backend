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
    "message": "Onboarding started successfully",
    "seller": {
        "id": "integer",
        "business_name": "string",
        "business_category": "string",
        "phone_number": "string",
        "onboarding_progress": "integer"
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
    "message": "Onboarding progress retrieved",
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
    "message": "Phone number verified successfully",
    "seller": {
        "id": "integer",
        "phone_verified_at": "datetime"
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
    "message": "KYC verification submitted successfully",
    "seller": {
        "id": "integer",
        "kyc_verified_at": "datetime"
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
    "message": "Business profile updated successfully",
    "seller": {
        "id": "integer",
        "profile_completed_at": "datetime"
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
    "message": "Seller agreement accepted successfully",
    "seller": {
        "id": "integer",
        "agreement_completed_at": "datetime"
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
    "message": "Payment information updated successfully",
    "seller": {
        "id": "integer",
        "payment_info_completed_at": "datetime"
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
    "message": "Social media information updated successfully",
    "seller": {
        "id": "integer",
        "social_media_completed_at": "datetime"
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
    "message": "Onboarding completed successfully",
    "seller": {
        "id": "integer",
        "onboarding_completed_at": "datetime",
        "is_verified": "boolean"
    }
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

### Not Found (404)
```json
{
    "message": "Seller profile not found"
}
```

### Unauthorized (401)
```json
{
    "message": "Unauthenticated"
}
```

### Forbidden (403)
```json
{
    "message": "Required steps must be completed"
} 