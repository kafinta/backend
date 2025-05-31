# 🛒 **Cart System API Documentation**

## Overview
The cart system supports both authenticated users and guest users with automatic cart transfer upon login. All responses use clean Laravel Resources without timestamps.

## Authentication
- **Guest Users**: Cart managed via `cart_session_id` cookie (30-day expiration)
- **Authenticated Users**: Cart tied to user account
- **Auto-Transfer**: Guest cart automatically transfers to user account upon login

## Base URL
```
http://localhost:8000/api
```

---

## 🛒 **CART API ENDPOINTS**

### 1. View Cart
```http
GET /api/cart
```

**Description**: Get current cart contents with items, totals, and metadata.

**Headers**: 
- `Content-Type: application/json`
- `Authorization: Bearer {token}` (optional for guests)

**Response**:
```json
{
  "success": true,
  "message": "Cart retrieved successfully",
  "data": {
    "id": 1,
    "user_id": 123,
    "expires_at": "2024-02-15T10:30:00Z",
    "is_expired": false,
    "items": [
      {
        "id": 1,
        "quantity": 2,
        "product": {
          "id": 456,
          "name": "Cotton T-Shirt",
          "slug": "cotton-t-shirt",
          "price": "29.99",
          "status": "active",
          "subcategory": {
            "id": 1,
            "name": "T-Shirts"
          },
          "images": [
            {
              "id": 1,
              "path": "/storage/products/tshirt.jpg",
              "url": "http://localhost:8000/storage/products/tshirt.jpg",
              "alt_text": "Cotton T-Shirt",
              "is_primary": true
            }
          ]
        },
        "variant": null,
        "price": "29.99",
        "subtotal": "59.98",
        "is_available": true,
        "stock_status": "in_stock",
        "max_quantity": 100
      }
    ],
    "totals": {
      "subtotal": "59.98",
      "tax": "5.40",
      "shipping": "0.00",
      "total": "65.38"
    },
    "item_count": 1,
    "total_quantity": 2
  }
}
```

### 2. Add Item to Cart
```http
POST /api/cart/items
```

**Description**: Add a product or variant to the cart.

**Request Body**:
```json
{
  "product_id": 456,     // Required if no variant_id
  "variant_id": 789,     // Required if no product_id  
  "quantity": 2          // Required, minimum 1
}
```

**Validation Rules**:
- `product_id`: required_without:variant_id|exists:products,id
- `variant_id`: required_without:product_id|exists:variants,id
- `quantity`: required|integer|min:1

**Response**:
```json
{
  "success": true,
  "message": "Product added to cart",
  "data": {
    "id": 1,
    "quantity": 2,
    "product": {
      "id": 456,
      "name": "Cotton T-Shirt",
      "price": "29.99"
    },
    "variant": null,
    "price": "29.99",
    "subtotal": "59.98",
    "is_available": true
  }
}
```

### 3. Update Cart Item
```http
PUT /api/cart/items/{cartItemId}
```

**Description**: Update the quantity of a cart item.

**Request Body**:
```json
{
  "quantity": 3
}
```

**Validation Rules**:
- `quantity`: required|integer|min:1

**Response**: Same as Add Item response with updated quantity.

### 4. Remove Cart Item
```http
DELETE /api/cart/items/{cartItemId}
```

**Description**: Remove a specific item from the cart.

**Response**:
```json
{
  "success": true,
  "message": "Cart item removed successfully",
  "data": {
    // Updated cart object (same as View Cart response)
  }
}
```

### 5. Clear Cart
```http
DELETE /api/cart
```

**Description**: Remove all items from the cart.

**Response**:
```json
{
  "success": true,
  "message": "Cart cleared successfully",
  "data": {
    "id": 1,
    "user_id": 123,
    "items": [],
    "totals": {
      "subtotal": "0.00",
      "total": "0.00"
    },
    "item_count": 0,
    "total_quantity": 0
  }
}
```

### 6. Transfer Guest Cart (Protected)
```http
POST /api/cart/transfer
```

**Description**: Transfer guest cart to authenticated user after login.

**Headers**: 
- `Authorization: Bearer {token}` (required)

**Response**:
```json
{
  "success": true,
  "message": "Guest cart transferred successfully",
  "data": {
    // Updated user cart object
  }
}
```

---

## 🛍️ **CART ITEM API ENDPOINTS**

### 1. List Cart Items
```http
GET /api/cart-items
```

**Response**:
```json
{
  "success": true,
  "message": "Cart items retrieved successfully",
  "data": [
    {
      "id": 1,
      "quantity": 2,
      "product": {...},
      "variant": null,
      "price": "29.99",
      "subtotal": "59.98"
    }
  ]
}
```

### 2. Show Cart Item
```http
GET /api/cart-items/{id}
```

**Response**: Single cart item object.

---

## 🔧 **FRONTEND IMPLEMENTATION GUIDE**

### JavaScript/TypeScript Service Example

```javascript
class CartService {
  constructor(baseURL = 'http://localhost:8000/api') {
    this.baseURL = baseURL;
  }

  // Get cart contents
  async getCart() {
    const response = await fetch(`${this.baseURL}/cart`, {
      credentials: 'include' // Important for cookies
    });
    return response.json();
  }

  // Add item to cart
  async addItem(productId, variantId = null, quantity = 1) {
    const body = { quantity };
    if (variantId) {
      body.variant_id = variantId;
    } else {
      body.product_id = productId;
    }

    const response = await fetch(`${this.baseURL}/cart/items`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      credentials: 'include',
      body: JSON.stringify(body)
    });
    return response.json();
  }

  // Update cart item quantity
  async updateItem(cartItemId, quantity) {
    const response = await fetch(`${this.baseURL}/cart/items/${cartItemId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      credentials: 'include',
      body: JSON.stringify({ quantity })
    });
    return response.json();
  }

  // Remove cart item
  async removeItem(cartItemId) {
    const response = await fetch(`${this.baseURL}/cart/items/${cartItemId}`, {
      method: 'DELETE',
      credentials: 'include'
    });
    return response.json();
  }

  // Clear entire cart
  async clearCart() {
    const response = await fetch(`${this.baseURL}/cart`, {
      method: 'DELETE',
      credentials: 'include'
    });
    return response.json();
  }

  // Transfer guest cart after login
  async transferGuestCart(token) {
    const response = await fetch(`${this.baseURL}/cart/transfer`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`
      },
      credentials: 'include'
    });
    return response.json();
  }
}
```

---

## ⚠️ **IMPORTANT NOTES FOR FRONTEND**

### Cookie Management
- **Always include** `credentials: 'include'` in fetch requests
- Cart session cookies are HTTP-only and secure
- Cookies automatically managed by browser

### Error Handling
- All endpoints return consistent error format
- Check `response.success` before accessing `response.data`
- Handle 404 errors for cart items not found
- Handle 422 errors for validation failures

### Authentication Integration
- Call `transferGuestCart()` immediately after successful login
- No need to manually manage session IDs
- Cart automatically switches from guest to user mode

### Performance Tips
- Cache cart data in state management (Vuex/Redux/Zustand)
- Debounce quantity updates to avoid excessive API calls
- Use optimistic updates for better UX
- Implement loading states for all cart operations

### Testing Endpoints
Use these curl commands to test the API:

```bash
# View cart
curl -X GET http://localhost:8000/api/cart \
  -H "Content-Type: application/json" \
  --cookie-jar cookies.txt

# Add product to cart
curl -X POST http://localhost:8000/api/cart/items \
  -H "Content-Type: application/json" \
  --cookie cookies.txt \
  -d '{"product_id": 1, "quantity": 2}'

# Update cart item
curl -X PUT http://localhost:8000/api/cart/items/1 \
  -H "Content-Type: application/json" \
  --cookie cookies.txt \
  -d '{"quantity": 3}'
```

The cart system is fully functional and ready for frontend integration! 🛒✨
