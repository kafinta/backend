# Authentication Frontend Integration Guide

## How to Use This Guide
This document provides practical notes for integrating the authentication API into any frontend application. For each endpoint, you’ll find:
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

## Registration & Authentication

### Register New User
- **Endpoint:** `POST /api/user/signup`
- **Purpose:** Register a new user
- **Who can call:** Anyone (public)
- **Required fields:** `username`, `email`, `password`, `password_confirmation`
- **Notes:** Email verification is required after registration

### Login
- **Endpoint:** `POST /api/user/login`
- **Purpose:** Authenticate a user and start a session
- **Who can call:** Anyone (public)
- **Required fields:** `email`, `password`, `remember_me` (optional)
- **Notes:** Returns user object and session/cookie

### Logout
- **Endpoint:** `POST /api/user/logout`
- **Purpose:** Log out the authenticated user
- **Who can call:** Authenticated users
- **Notes:** Invalidates the session/cookie

---

## Password Management

### Forgot Password
- **Endpoint:** `POST /api/forgot-password`
- **Purpose:** Request a password reset link
- **Who can call:** Anyone (public)
- **Required fields:** `email`
- **Notes:** If the email exists, a reset link is sent (response is generic for security)

### Reset Password
- **Endpoint:** `POST /api/reset-password/token`
- **Purpose:** Reset password using a token
- **Who can call:** Anyone (public)
- **Required fields:** `token`, `email`, `password`, `password_confirmation`
- **Notes:** Token is sent via email

---

## Email Verification & Update

### Update Email
- **Endpoint:** `PATCH /api/user/update-email`
- **Purpose:** Update the authenticated user’s email address
- **Who can call:** Authenticated users
- **Required fields:** `email`, `password`
- **Notes:** Password re-entry required; triggers new verification email; old email notified if previously verified
- **Rate limiting:** Max 3 requests per hour
- **Common pitfalls:** Using the same email, or an email already in use, will result in a generic error

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
- Handle session and token expiration gracefully
- For password reset and email update, always require password re-entry
- Never expose whether an email is registered or not in the UI

---

## Special Notes
- All endpoints require HTTPS in production
- All timestamps are in UTC
- For request/response examples, see the API documentation 