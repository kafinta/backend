# Variant System Overview

## What is the Variant System?
Variants represent specific versions of a product (e.g., size, color, material). Each variant can have its own price, stock, images, and attribute values. Variants are always linked to a parent product.

## Key Features
- Unique attribute combinations per variant (no duplicates within a product)
- Independent stock and price management
- Image support for each variant
- Batch update and management endpoints
- Only sellers or admins can create, update, or delete variants

## Documentation
- **API Reference:** See [`api.md`](./api.md) for all variant endpoints and details.
- **Frontend Integration:** See [`frontend.md`](./frontend.md) for workflow and integration notes.

## Permissions & Error Handling
- Only sellers or admins can manage variants
- All endpoints return JSON with clear status and error messages
- Handle 401 (unauthenticated), 403 (forbidden), 404 (not found), and 422 (validation) errors appropriately

## Integration Notes
- Variants are managed in the context of a product, but have their own endpoints
- Stock and price for variants are independent of the parent product
- Cart and order systems reference variants by ID when present

---

For more details, see the API and frontend documentation in this directory. 