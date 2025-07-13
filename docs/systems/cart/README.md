# Cart System Overview

## What is the Cart System?
The cart system manages shopping carts for both guest and authenticated users. It supports:
- **Guest carts**: Session-based, tracked via the `cart_session_id` cookie.
- **Authenticated carts**: Linked to user accounts.
- **Cart transfer**: After login, a guest cart can be merged into the user's cart.
- **Checkout integration**: All order placement is handled via the Checkout API.

## Key Features
- Add, update, remove, and clear cart items (products or variants)
- View current cart contents and totals
- Seamless transition from guest to user cart after login
- Robust error handling and session management
- Checkout endpoints are separate (see Checkout API)

## Documentation
- **API Reference:** See [`api.md`](./api.md) for all cart endpoints and details.
- **Frontend Integration:** See [`frontend.md`](./frontend.md) for workflow and integration notes.

## Error Handling & Session Notes
- All endpoints return JSON with clear status and error messages.
- For guests, always use the `cart_session_id` cookie for cart persistence.
- Cart transfer (`POST /cart/transfer`) should be called after login if a guest cart was in use.
- Do not attempt to place orders as a guest; require login before checkout.

## Integration with Checkout
- The cart system does not handle order placement directly.
- Use the Checkout API (`/checkout`) for calculating totals and placing orders.

---

For more details, see the API and frontend documentation in this directory. 