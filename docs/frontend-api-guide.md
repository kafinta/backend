# Frontend API Integration Guide

## Base Configuration

### API Base URL
```javascript
const API_BASE_URL = 'http://localhost:8000/api'
```

### Authentication Headers
```javascript
// For cookie-based auth (recommended)
const headers = {
  'Content-Type': 'application/json',
  'Accept': 'application/json',
  'X-Requested-With': 'XMLHttpRequest'
}

// Include credentials for cookie auth
const fetchOptions = {
  credentials: 'include',
  headers
}
```

## Core API Endpoints

### 1. Authentication

#### Register
```javascript
POST /api/user/signup
{
  "name": "John Doe",
  "username": "johndoe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

#### Login
```javascript
POST /api/user/login
{
  "login": "john@example.com", // email or username
  "password": "password123"
}
```

#### Logout
```javascript
POST /api/user/logout
// Requires authentication
```

#### Email Verification
```javascript
// Verify with token (from email link)
POST /api/verify-email/token
{
  "token": "verification_token_from_email"
}

// Verify with 6-digit code
POST /api/verify-email/code
{
  "code": "123456"
}

// Resend verification email
POST /api/user/resend-verification-email
// Requires authentication
```

#### Password Reset
```javascript
// Request reset
POST /api/password/forgot
{
  "email": "john@example.com"
}

// Reset with token
POST /api/password/reset/token
{
  "token": "reset_token_from_email",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}

// Reset with code
POST /api/password/reset/code
{
  "code": "123456",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

### 2. User Profile

#### Get Profile
```javascript
GET /api/user/profile
// Requires authentication
```

#### Update Profile
```javascript
PUT /api/user/profile
{
  "name": "John Doe Updated",
  "bio": "Updated bio"
}
// Requires authentication
```

#### Upload Profile Picture
```javascript
POST /api/user/profile/upload-picture
// FormData with 'profile_picture' file
// Requires authentication
```

### 3. Product Discovery

#### Get Products (with filtering)
```javascript
GET /api/products?subcategory_id=1&per_page=20&page=1
// Optional filters: search, location_id, min_price, max_price, sort_by, sort_order
```

#### Get Single Product
```javascript
GET /api/products/{id}
// or
GET /api/products/slug/{slug}
```

#### Get Product Attributes
```javascript
GET /api/products/{id}/attributes
```

### 4. Categories & Locations

#### Get Categories
```javascript
GET /api/categories
```

#### Get Locations
```javascript
GET /api/locations
```

#### Get Subcategories
```javascript
GET /api/subcategories
// Optional: ?category_id=1&location_id=2
```

### 5. Cart Management

#### View Cart
```javascript
GET /api/cart
// Works for both guests and authenticated users
```

#### Add to Cart
```javascript
POST /api/cart/add
{
  "product_id": 1,
  "variant_id": 2, // optional
  "quantity": 1
}
```

#### Update Cart Item
```javascript
PUT /api/cart/items/{item_id}
{
  "quantity": 3
}
```

#### Remove from Cart
```javascript
DELETE /api/cart/items/{item_id}
```

#### Clear Cart
```javascript
DELETE /api/cart/clear
```

### 6. Checkout & Orders

#### Checkout
```javascript
POST /api/checkout
{
  "shipping_address": {
    "street": "123 Main St",
    "city": "City",
    "state": "State",
    "postal_code": "12345",
    "country": "Country"
  },
  "payment_method": "stripe", // or other methods
  "payment_details": {
    // Payment-specific details
  }
}
// Requires authentication
```

#### Get Orders
```javascript
GET /api/orders
// Requires authentication
```

#### Get Single Order
```javascript
GET /api/orders/{id}
// Requires authentication
```

## Response Format

All API responses follow this structure:

### Success Response
```javascript
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Operation successful",
  "data": {
    // Response data
  }
}
```

### Error Response
```javascript
{
  "success": false,
  "status": "fail",
  "status_code": 400,
  "message": "Error message",
  "errors": {
    // Validation errors (if applicable)
  }
}
```

## Error Handling

### Common HTTP Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request (validation errors)
- `401` - Unauthorized (not logged in)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found
- `422` - Unprocessable Entity (validation failed)
- `500` - Internal Server Error

### Frontend Error Handling Example
```javascript
async function apiCall(endpoint, options = {}) {
  try {
    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      ...options
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'API request failed');
    }

    return data;
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
}
```

## Authentication Flow

### 1. Registration Flow
1. User submits registration form
2. API creates user account
3. Verification email sent automatically
4. User clicks email link or enters 6-digit code
5. Account verified and user logged in

### 2. Login Flow
1. User submits login credentials
2. API validates and creates session
3. Cookie set automatically
4. Subsequent requests authenticated via cookie

### 3. Guest Cart to User Cart
1. Guest adds items to cart (session-based)
2. User registers/logs in
3. Guest cart automatically merged with user cart

## Rate Limiting

- Login/Register: 6 requests per minute
- Email updates: 3 requests per hour
- General API: Standard rate limits apply

## CORS Configuration

The API is configured to accept requests from:
- `http://localhost:3000` (Nuxt.js dev server)
- `https://kafinta.com` (production frontend)
- `https://admin.kafinta.com` (admin frontend)

## Next Steps

1. Set up your Nuxt.js project with these API endpoints
2. Implement authentication state management
3. Create reusable API service functions
4. Test with the provided endpoints
5. Implement error handling and loading states
