# Variant System Frontend Integration Guide

## How to Use This Guide
This document provides practical notes for integrating the variant system API into any frontend application. For each endpoint, you’ll find:
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

## Variant System Overview
- Variants are specific versions of a product (e.g., size, color).
- Each variant is linked to a product and must have a unique combination of attribute values.
- Only sellers or admins can manage variants.

---

## Endpoints and Workflow

### List Variants for a Product
- **GET /products/{productId}/variants**
- Lists all variants for a product.
- Requires seller or admin auth.

### View a Variant
- **GET /products/variants/{id}**
- Shows details of a specific variant, including attributes and images.
- Requires seller or admin auth.

### Create a Variant
- **POST /products/{productId}/variants**
- Required: `name`, `price`, `attributes` (array of `{ attribute_id, value_id }`)
- Optional: `manage_stock`, `stock_quantity`, `images`
- Requires seller or admin auth.
- Each variant must have a unique attribute combination within the product.

### Update a Variant
- **PUT /products/variants/{id}**
- Any fields from creation can be updated.
- Optional: `delete_image_ids` (array of image IDs to remove)
- Requires seller or admin auth.

### Delete a Variant
- **DELETE /products/variants/{id}**
- Removes a variant from a product.
- Requires seller or admin auth.

### Batch Update Variants
- **POST /products/variants/batch/update**
- Update multiple variants at once.
- Requires seller or admin auth.

---

## Error Handling & Common Pitfalls
- Always check for 401 (unauthenticated), 403 (forbidden), 404 (not found), and 422 (validation) errors.
- Variants must have a unique combination of attribute values within a product.
- Stock and price for variants are independent of the parent product.
- Only sellers or admins can manage variants.

---

## Integration Notes
- Variants are managed in the context of a product, but have their own endpoints.
- Cart and order systems reference variants by ID when present.
- For more details, see the API documentation in this directory. 