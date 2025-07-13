# Cart System API Documentation

## Overview
The Cart API allows both guests and authenticated users to manage their shopping cart. It supports session-based carts for guests (using a session cookie) and user-based carts for authenticated users. After login, a guest cart can be merged into the user's cart. All order placement and checkout flows are handled via the Checkout API.

---

## Endpoints

### 1. View Cart
- **GET /cart**
- **Purpose:** Retrieve the current cart for the user or guest (based on session/cookie).
- **Auth:** Not required for guests; returns user cart if authenticated.
- **Response:**
  - Cart object with items, totals, item count, and session info (for guests).
- **Notes:**
  - For guests, a `cart_session_id` cookie is set if not present.

### 2. Add Item to Cart
- **POST /cart/items**
- **Purpose:** Add a product or variant to the cart. Updates quantity if already present.
- **Fields:**
  - `product_id` (required unless `variant_id` is provided)
  - `variant_id` (required unless `product_id` is provided)
  - `quantity` (required, integer >= 1)
- **Auth:** Not required for guests.
- **Response:**
  - The added/updated cart item.
- **Errors:**
  - 422 for validation errors, 404 if product/variant not found.
- **Notes:**
  - For guests, sets/refreshes the session cookie.

### 3. Update Cart Item
- **PUT /cart/items/{id}**
- **Purpose:** Update the quantity of a specific cart item.
- **Fields:**
  - `quantity` (required, integer >= 1)
- **Auth:** Not required for guests.
- **Response:**
  - The updated cart item.
- **Errors:**
  - 404 if item not found, 422 for validation errors.

### 4. Remove Cart Item
- **DELETE /cart/items/{id}**
- **Purpose:** Remove a specific item from the cart.
- **Auth:** Not required for guests.
- **Response:**
  - The updated cart object.
- **Errors:**
  - 404 if item not found.

### 5. Clear Cart
- **DELETE /cart**
- **Purpose:** Remove all items from the cart.
- **Auth:** Not required for guests.
- **Response:**
  - The updated (empty) cart object.

### 6. Transfer Guest Cart After Login
- **POST /cart/transfer**
- **Purpose:** Merge the guest cart into the authenticated user's cart after login.
- **Auth:** Required (user must be logged in).
- **Response:**
  - The merged user cart object.
- **Errors:**
  - 401 if not authenticated.
- **Notes:**
  - Always call this after login if a guest cart existed.

---

## Checkout Reference
- **Order placement and checkout are handled via the Checkout API.**
- See `/checkout/calculate`, `/checkout/place-order`, etc. in the Checkout API documentation.
- Do not use any deprecated checkout logic in `/cart`.

---

## General Notes
- For guests, always use the `cart_session_id` cookie for cart persistence.
- Cart endpoints are available to both guests and authenticated users, except for cart transfer (requires auth).
- All endpoints return JSON responses with a consistent structure: `{ success, status, status_code, message, data }`.
- Handle 401 (unauthenticated), 404 (not found), and 422 (validation) errors appropriately.
- If a guest cart expires, a new cart will be created automatically.
- Do not attempt to place orders as a guest; require login before checkout. 