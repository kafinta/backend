# API Quick Reference

## 🔧 Base Setup
```javascript
const API_BASE_URL = 'http://localhost:8000/api'

// Always include credentials for cookie auth
const fetchOptions = {
  credentials: 'include',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }
}
```

## 🔐 Authentication

### Register
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

### Login
```javascript
POST /api/user/login
{
  "login": "john@example.com", // email or username
  "password": "password123"
}
```

### Logout
```javascript
POST /api/user/logout
```

### Email Verification
```javascript
// With token (from email link)
POST /api/verify-email/token
{ "token": "verification_token" }

// With 6-digit code
POST /api/verify-email/code  
{ "code": "123456" }

// Resend verification
POST /api/user/resend-verification-email
```

### Password Reset
```javascript
// Request reset
POST /api/password/forgot
{ "email": "john@example.com" }

// Reset with token
POST /api/password/reset/token
{
  "token": "reset_token",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

## 👤 User Profile

### Get Profile
```javascript
GET /api/user/profile
```

### Update Profile
```javascript
PUT /api/user/profile
{
  "name": "Updated Name",
  "bio": "Updated bio"
}
```

### Upload Profile Picture
```javascript
POST /api/user/profile/upload-picture
// FormData with 'profile_picture' file
```

## 🛍️ Products

### Get Products (with filtering)
```javascript
GET /api/products?subcategory_id=1&search=chair&min_price=50&max_price=500&per_page=20&page=1

// Available filters:
// - subcategory_id: Filter by subcategory
// - search: Search in name/description
// - location_id: Filter by location
// - min_price, max_price: Price range
// - sort_by: price, created_at, name
// - sort_order: asc, desc
// - per_page: Items per page (default: 15)
// - page: Page number
```

### Get Single Product
```javascript
GET /api/products/{id}
// or
GET /api/products/slug/{slug}
```

### Get Product Attributes
```javascript
GET /api/products/{id}/attributes
```

## 📂 Categories & Locations

### Get Categories
```javascript
GET /api/categories
```

### Get Locations
```javascript
GET /api/locations
```

### Get Subcategories
```javascript
GET /api/subcategories
// Optional filters: ?category_id=1&location_id=2
```

## 🛒 Shopping Cart

### View Cart
```javascript
GET /api/cart
// Works for both guests and authenticated users
```

### Add to Cart
```javascript
POST /api/cart/add
{
  "product_id": 1,
  "variant_id": 2, // optional
  "quantity": 1
}
```

### Update Cart Item
```javascript
PUT /api/cart/items/{item_id}
{ "quantity": 3 }
```

### Remove from Cart
```javascript
DELETE /api/cart/items/{item_id}
```

### Clear Cart
```javascript
DELETE /api/cart/clear
```

## 📦 Orders

### Checkout
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
  "payment_method": "stripe", // optional
  "payment_details": {} // payment-specific data
}
```

### Get User Orders
```javascript
GET /api/orders
```

### Get Single Order
```javascript
GET /api/orders/{id}
```

## 📝 Response Format

### Success Response
```javascript
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Operation successful",
  "data": {
    // Response data here
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

## 🚨 Common HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request (validation errors)
- `401` - Unauthorized (not logged in)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found
- `422` - Unprocessable Entity (validation failed)
- `500` - Internal Server Error

## 🔄 Rate Limits

- **Login/Register**: 6 requests per minute
- **Email updates**: 3 requests per hour
- **General API**: Standard rate limits

## 💡 Tips

### 1. Always Include Credentials
```javascript
fetch('/api/endpoint', {
  credentials: 'include', // Essential for cookie auth
  // ... other options
})
```

### 2. Handle Authentication Errors
```javascript
if (response.status === 401) {
  // Redirect to login page
  window.location.href = '/login'
}
```

### 3. Guest Cart Handling
- Cart works without authentication
- Automatically merges with user cart on login
- Use session-based cart for guests

### 4. Image URLs
- Product images: `/storage/products/{id}/{filename}`
- Category images: `/storage/categories/{filename}`
- Profile pictures: `/storage/profile-pictures/{filename}`

### 5. Pagination
```javascript
// Response includes pagination info
{
  "data": [...],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 73
  }
}
```

## 🔗 Related Documentation

- **[Complete Frontend Guide](FRONTEND-GUIDE.md)** - Detailed implementation examples
- **[Environment Setup](environment-setup.md)** - Development configuration
- **[Detailed API Docs](frontend-api-guide.md)** - Full endpoint documentation
