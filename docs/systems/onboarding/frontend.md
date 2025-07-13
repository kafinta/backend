# Onboarding Frontend Integration Guide

## How to Use This Guide
This document provides practical notes for integrating the onboarding API into any frontend application. For each endpoint, you’ll find:
- **Endpoint path and method**
- **Purpose/description**
- **Who can call it**
- **Required/optional fields**
- **Dependencies (what must be done before/after)**
- **Order in workflow**
- **Auth requirements**
- **Error handling notes**
- **Common pitfalls**
- **Special notes for frontend devs**

No code samples are included—use your preferred HTTP client. For request/response examples, see the API documentation.

---

## Onboarding Management

### Start Onboarding
- **Endpoint:** `POST /api/seller/start-onboarding`
- **Purpose:** Begin the onboarding process for a new seller
- **Who can call:** Anyone (public)
- **Required fields:** `business_name`, `business_category`, `phone_number`
- **Order:** First step in onboarding

### Get Onboarding Progress
- **Endpoint:** `GET /api/seller/progress`
- **Purpose:** Retrieve current onboarding progress and required/optional steps
- **Who can call:** Authenticated sellers
- **Notes:** Use to show progress bars or step lists in UI

---

## Verification

### Phone Verification
- **Endpoint:** `POST /api/seller/verify-phone`
- **Purpose:** Verify seller’s phone number
- **Who can call:** Authenticated sellers
- **Required fields:** `phone_number`, `verification_code`
- **Dependencies:** Must have started onboarding
- **Order:** After starting onboarding, before profile completion

### KYC Verification
- **Endpoint:** `POST /api/seller/verify-kyc`
- **Purpose:** Submit KYC documents for verification
- **Who can call:** Authenticated sellers
- **Required fields:** `id_type`, `id_number`, `id_document` (file)
- **Order:** Optional, but may be required for full verification

---

## Business Profile

### Update Business Profile
- **Endpoint:** `POST /api/seller/update-profile`
- **Purpose:** Update seller’s business profile information
- **Who can call:** Authenticated sellers
- **Required fields:** See API docs
- **Order:** After phone verification, before agreement

---

## Agreement

### Accept Seller Agreement
- **Endpoint:** `POST /api/seller/accept-agreement`
- **Purpose:** Accept the seller agreement
- **Who can call:** Authenticated sellers
- **Order:** After profile completion, before onboarding completion

---

## Payment Information

### Update Payment Information
- **Endpoint:** `POST /api/seller/update-payment-info`
- **Purpose:** Add or update seller’s payment information
- **Who can call:** Authenticated sellers
- **Required fields:** See API docs
- **Order:** Optional, but recommended before completing onboarding

---

## Social Media

### Update Social Media
- **Endpoint:** `POST /api/seller/update-social-media`
- **Purpose:** Add or update seller’s social media links
- **Who can call:** Authenticated sellers
- **Order:** Optional

---

## Completion

### Complete Onboarding
- **Endpoint:** `POST /api/seller/complete-onboarding`
- **Purpose:** Mark onboarding as complete
- **Who can call:** Authenticated sellers
- **Dependencies:** All required steps must be completed
- **Order:** Final step

---

## Error Handling & Response Structure
- All API responses follow a standard structure: see API docs for details
- Always check `success` and `status` fields
- For validation errors, display the `errors` object
- For other errors, display the `message` field
- For authentication errors, handle session expiration and redirect to login as needed

---

## Best Practices
- Validate user input before sending to API
- Show loading and error states for all API calls
- Use the `message` field for user feedback
- Track onboarding progress and required steps in the UI
- For file uploads, validate file type and size before sending

---

## Special Notes
- Onboarding is a multi-step process; required and optional steps may vary
- Use `/api/seller/progress` to dynamically build onboarding UI
- For request/response examples, see the API documentation 