# Order System Overview

## What is the Order System?
The order system manages the lifecycle of customer orders, from placement to fulfillment and delivery. It supports:
- **Order placement**: Users can place orders from their cart, providing shipping details.
- **Order management**: Users can view and cancel their own orders (if pending).
- **Seller fulfillment**: Sellers can view orders containing their products and update the status of their items (processing, shipped, delivered, cancelled).
- **Status transitions**: The system tracks and updates order and item statuses, ensuring only valid transitions.
- **Notifications**: Key events (order placed, status changed) trigger notifications for users and sellers.

## Key Features
- Secure, transactional order placement
- Prevention of sellers ordering their own products
- Atomic stock reduction to prevent overselling
- Seller-specific order views and item status updates
- Comprehensive API responses for frontend use
- Extensible for future features (refunds, returns, payment integration)

## Documentation
- **API Reference**: See `api.md` in this directory for all endpoints and workflows.
- **Frontend Integration**: See `frontend.md` for integration notes and workflow guidance.

## Permissions & Security
- Users can only access and cancel their own orders.
- Sellers can only view and update orders containing their products.
- Admin flows can be added as needed, following the same security principles.

## Error Handling & Extensibility
- Clear error messages for invalid actions (e.g., cancelling shipped orders, unauthorized access).
- Designed for easy extension to support refunds, returns, and advanced fulfillment flows. 