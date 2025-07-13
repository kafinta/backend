# Order System API Documentation

## Overview
The Order API manages the creation, viewing, and management of orders for both users and sellers. It ensures secure, transactional order placement and supports seller-specific fulfillment workflows.

---

## Endpoints

### 1. Place Order
- **POST /checkout**
- **Purpose:** Place an order from the current cart.
- **Auth:** User required.
- **Fields:**
  - shipping_name (string, required)
  - shipping_address (string, required)
  - shipping_city (string, required)
  - shipping_state (string, required)
  - shipping_postal_code (string, required)
  - shipping_country (string, required)
  - shipping_phone (string, required)
  - notes (string, optional)
- **Response:** OrderResource (full order details)
- **Errors:** 400 (empty cart), 422 (validation), 403 (seller ordering own product)

### 2. View User Orders
- **GET /orders**
- **Purpose:** List all orders for the authenticated user.
- **Auth:** User required.
- **Response:** Array of OrderResource

### 3. View Single Order
- **GET /orders/{id}**
- **Purpose:** View details of a specific order (must belong to user).
- **Auth:** User required.
- **Response:** OrderResource
- **Errors:** 403 (not owner), 404 (not found)

### 4. Cancel Order
- **POST /orders/{id}/cancel**
- **Purpose:** Cancel a pending order (user only).
- **Auth:** User required.
- **Response:** OrderResource (updated status)
- **Errors:** 400 (not pending), 403 (not owner)

### 5. Seller: List Orders
- **GET /seller/orders**
- **Purpose:** List all orders containing the seller's products.
- **Auth:** Seller required.
- **Response:** Array of OrderResource (only seller's items included)

### 6. Seller: View Order
- **GET /seller/orders/{id}**
- **Purpose:** View a specific order with only the seller's items.
- **Auth:** Seller required.
- **Response:** OrderResource
- **Errors:** 404 (not found), 403 (order does not contain seller's products)

### 7. Seller: Update Item Status
- **POST /seller/orders/{id}/status**
- **Purpose:** Update the status of the seller's items in an order (processing, shipped, delivered, cancelled).
- **Auth:** Seller required.
- **Fields:**
  - status (string, required: processing, shipped, delivered, cancelled)
- **Response:** OrderResource (updated items)
- **Errors:** 422 (invalid status), 403 (not seller's items)

---

## Notes
- All endpoints return clear error messages and appropriate status codes.
- Order and item statuses are strictly validated.
- See the frontend guide for workflow and integration notes. 