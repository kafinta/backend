# Cart System Frontend Integration Guide

## How to Use This Guide
This document provides practical notes for integrating the cart system API into any frontend application. For each endpoint, you’ll find:
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

---

## Cart System Overview

### Guest vs. Authenticated Carts
- **Guest users**: Cart is tracked by a session ID (cookie `cart_session_id`).
- **Authenticated users**: Cart is tied to user account.
- The backend automatically manages which cart to use based on auth/session.

### Session Cookie Usage
- For guests, always send/receive the `cart_session_id` cookie.
- If the cookie is missing, a new guest cart is created and the cookie is set in the response.
- The cookie is HTTP-only and lasts 30 days.

### Cart Transfer After Login
- After login, call the `POST /cart/transfer` endpoint to merge the guest cart into the user cart.
- The backend will clear the guest cart and session cookie.
- Always call this after login if the user had a guest cart.

---

## Endpoints and Workflow

### View Cart
- **GET /cart**
- Returns the current cart for the user or guest (based on session/cookie).
- No auth required for guests; returns user cart if authenticated.

### Add Item to Cart
- **POST /cart/items**
- Required: `product_id` or `variant_id`, `quantity`
- Adds a product or variant to the cart. Updates quantity if already present.
- For guests, sets/refreshes the session cookie.

### Update Cart Item
- **PUT /cart/items/{id}**
- Required: `quantity`
- Updates the quantity of a specific cart item.

### Remove Cart Item
- **DELETE /cart/items/{id}**
- Removes a specific item from the cart.

### Clear Cart
- **DELETE /cart**
- Removes all items from the cart.

### Transfer Guest Cart After Login
- **POST /cart/transfer**
- Merges the guest cart into the authenticated user's cart after login.
- Always call this after login if a guest cart existed.

### Checkout (Order Placement)
- **POST /checkout/place-order**
- Requires authentication.
- Use only the `/checkout` endpoints for calculating totals and placing orders.
- Do NOT use any deprecated checkout logic in `/cart`.

---

## Error Handling & Common Pitfalls
- Always check for 401 errors (unauthenticated) and prompt login if needed.
- If a guest cart expires, a new cart will be created automatically.
- Do not attempt to place orders as a guest; require login before checkout.
- Always handle validation errors (422) for cart and checkout actions.
- If you see inconsistent cart state after login, ensure you are calling the cart transfer endpoint.

---

## Auth Requirements
- Cart CRUD endpoints are available to both guests and authenticated users.
- Checkout endpoints require authentication.

---

## Dependencies & Workflow Notes
- Cart transfer should be performed immediately after login if a guest cart was in use.
- Always use the session cookie for guest cart persistence.
- For order placement, always use the `/checkout` endpoints. 