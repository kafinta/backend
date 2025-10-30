# Cart System API Documentation

## Overview
The Cart API allows both guests and authenticated users to manage their shopping cart. It supports session-based carts for guests (using a session cookie) and user-based carts for authenticated users. After login, a guest cart can be merged into the user's cart. All order placement and checkout flows are handled via the Checkout API.

---

## Route Structure

All cart endpoints follow a consistent REST pattern:

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| GET | `/api/cart` | View cart | Optional |
| POST | `/api/cart/items` | Add item | Optional |
| PUT | `/api/cart/items/{id}` | Update item | Optional |
| DELETE | `/api/cart/items/{id}` | Remove item | Optional |
| DELETE | `/api/cart` | Clear cart | Optional |
| POST | `/api/cart/transfer` | Transfer guest cart | Required |

---

## Endpoints

### 1. View Cart
**GET /api/cart**

Retrieve the current cart for the user or guest (based on session/cookie).

**Authentication:** Not required for guests; returns user cart if authenticated.

**Response (200):**
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Cart retrieved successfully",
  "data": {
    "id": 1,
    "user_id": null,
    "session_id": "abc123...",
    "expires_at": "2025-11-29T10:30:00Z",
    "is_expired": false,
    "items": [
      {
        "id": 1,
        "quantity": 2,
        "product": { ... },
        "price": 100.00,
        "discounted_price": 80.00,
        "subtotal": 160.00
      }
    ],
    "totals": {
      "subtotal": 160.00,
      "tax": 16.00,
      "shipping": 10.00,
      "total": 186.00
    },
    "item_count": 2
  }
}
```

**Notes:**
- For guests, a `cart_session_id` cookie is set if not present (HTTP-only, 30-day expiry).

---

### 2. Add Item to Cart
**POST /api/cart/items**

Add a product or variant to the cart. Updates quantity if item already exists.

**Request Body:**
```json
{
  "product_id": 5,
  "quantity": 2
}
```

**Fields:**
- `product_id` (integer, required unless `variant_id` provided)
- `variant_id` (integer, required unless `product_id` provided)
- `quantity` (integer, required, >= 1)

**Response (201):**
```json
{
  "status": "success",
  "status_code": 201,
  "message": "Item added to cart successfully",
  "data": { ... }
}
```

**Errors:**
- `422` - Validation error
- `404` - Product/variant not found
- `400` - Cannot add own product (seller restriction)

---

### 3. Update Cart Item
**PUT /api/cart/items/{id}**

Update the quantity of a specific cart item.

**Request Body:**
```json
{
  "quantity": 5
}
```

**Response (200):**
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Cart item updated successfully",
  "data": { ... }
}
```

**Errors:**
- `404` - Cart item not found
- `422` - Validation error

---

### 4. Remove Cart Item
**DELETE /api/cart/items/{id}**

Remove a specific item from the cart.

**Response (200):**
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Item removed from cart successfully",
  "data": { ... }
}
```

**Errors:**
- `404` - Cart item not found

---

### 5. Clear Cart
**DELETE /api/cart**

Remove all items from the cart.

**Response (200):**
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Cart cleared successfully",
  "data": { ... }
}
```

---

### 6. Transfer Guest Cart After Login
**POST /api/cart/transfer**

Merge the guest cart into the authenticated user's cart after login.

**Authentication:** Required

**Request Body (Optional):**
```json
{
  "session_id": "abc123..."
}
```

**Response (200):**
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Guest cart transferred successfully",
  "data": { ... }
}
```

**Errors:**
- `401` - Not authenticated
- `404` - Guest cart not found

**Notes:**
- If `session_id` not provided, uses `cart_session_id` cookie.
- Guest cart is cleared and session cookie removed after transfer.
- Items are merged (quantities added if items already exist in user cart).

---

## General Notes

- **Session Management:** For guests, always use the `cart_session_id` cookie for cart persistence.
- **Response Format:** All endpoints return JSON with consistent structure: `{ status, status_code, message, data }`.
- **Error Handling:** Handle 401 (unauthenticated), 404 (not found), and 422 (validation) errors appropriately.
- **Cart Expiry:** Guest carts expire after 30 days. A new cart is created automatically if expired.
- **Checkout:** Do not place orders via cart endpoints. Use the Checkout API (`/checkout/place-order`).

