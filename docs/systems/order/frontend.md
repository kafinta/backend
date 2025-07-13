# Order System Frontend Integration Guide

## How to Use This Guide
This document provides practical notes for integrating the order system API into any frontend application. For each endpoint, you’ll find:
- **Endpoint path and method**
- **Purpose/description**
- **Who can call it**
- **Required/optional fields**
- **Dependencies (what must be done before/after)**
- **Order in workflow**
- **Auth requirements**
- **Error handling notes**
- **Common pitfalls**

---

## Placing an Order
- Use the `/checkout` endpoint (POST) after the cart is ready.
- Required fields: shipping_name, shipping_address, city, state, postal_code, country, phone.
- Validate all fields before sending; show errors from API.
- If the cart is empty or user is a seller ordering their own product, show a clear error.

## Viewing Orders (User)
- Use `/orders` (GET) to list all orders for the logged-in user.
- Use `/orders/{id}` (GET) to view details of a specific order.
- Orders include all items, shipping info, and status.
- Only the order owner can view/cancel their order.

## Cancelling Orders (User)
- Use `/orders/{id}/cancel` (POST) to cancel a pending order.
- Only possible if status is `pending`.
- Show updated status and handle errors (already shipped/cancelled, not owner).

## Seller Fulfillment
- Use `/seller/orders` (GET) to list orders containing the seller's products.
- Use `/seller/orders/{id}` (GET) to view a specific order (only seller's items).
- Use `/seller/orders/{id}/status` (POST) to update item statuses (processing, shipped, delivered, cancelled).
- Only valid statuses are allowed; show errors for invalid transitions.

## Status Handling
- Orders and items have statuses: pending, processing, shipped, delivered, cancelled.
- Show status transitions clearly in the UI.
- Only allow actions that are valid for the current status.

## Error Handling & Pitfalls
- Always display API error messages to users (e.g., unauthorized, invalid status, empty cart).
- Handle permission errors gracefully (e.g., trying to view/cancel someone else’s order).
- For sellers, only their items are included in order views.

## Workflow Notes
- After placing an order, refresh the cart and show order confirmation.
- Poll or refresh order status for updates (e.g., shipped/delivered).
- For multi-seller orders, each seller only sees and updates their own items.

---

For detailed API fields and error codes, see `api.md` in this directory. 