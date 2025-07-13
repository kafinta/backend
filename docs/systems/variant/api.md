# Variant API

## Overview
Variants represent specific versions of a product (e.g., size, color). Each variant is linked to a product and can have its own price, stock, and attribute values. Only sellers or admins can manage variants.

---

## Endpoints

### 1. List Variants for a Product
- **GET /products/{productId}/variants**
- **Purpose:** List all variants for a given product.
- **Auth:** Seller or admin required.
- **Response:** Array of variant objects.

### 2. View a Variant
- **GET /products/variants/{id}**
- **Purpose:** Get details of a specific variant.
- **Auth:** Seller or admin required.
- **Response:** Variant object with attributes and images.
- **Errors:** 404 if not found or unauthorized.

### 3. Create a Variant
- **POST /products/{productId}/variants**
- **Purpose:** Create a new variant for a product.
- **Fields:**
  - `name` (required)
  - `price` (required)
  - `manage_stock` (optional, boolean)
  - `stock_quantity` (optional, integer)
  - `attributes` (required, array of `{ attribute_id, value_id }`)
  - `images` (optional, array of image files)
- **Auth:** Seller or admin required.
- **Response:** Created variant object.
- **Errors:** 422 for validation errors, 403 for unauthorized.

### 4. Update a Variant
- **PUT /products/variants/{id}**
- **Purpose:** Update an existing variant.
- **Fields:**
  - Any of the fields from creation (see above)
  - `delete_image_ids` (optional, array of image IDs to remove)
- **Auth:** Seller or admin required.
- **Response:** Updated variant object.
- **Errors:** 404 if not found, 422 for validation errors, 403 for unauthorized.

### 5. Delete a Variant
- **DELETE /products/variants/{id}**
- **Purpose:** Remove a variant from a product.
- **Auth:** Seller or admin required.
- **Response:** Success message.
- **Errors:** 404 if not found, 403 for unauthorized.

### 6. Batch Update Variants
- **POST /products/variants/batch/update**
- **Purpose:** Update multiple variants at once.
- **Fields:**
  - `variants` (required, array of variant update objects)
- **Auth:** Seller or admin required.
- **Response:** Array of updated variant objects.
- **Errors:** 422 for validation errors, 403 for unauthorized.

---

## General Notes
- Variants must have a unique combination of attribute values within a product.
- Stock management for variants is independent of the parent product.
- Only sellers or admins can create, update, or delete variants.
- All endpoints return JSON responses with a consistent structure: `{ success, status, status_code, message, data }`.
- Handle 401 (unauthenticated), 403 (forbidden), 404 (not found), and 422 (validation) errors appropriately. 