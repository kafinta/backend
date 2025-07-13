# Product Management Frontend Integration Guide

## How to Use This Guide
This document provides practical notes for integrating the product management API into any frontend application. For each endpoint, you’ll find:
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

No code samples are included—use your preferred HTTP client. For request/response examples, see the API documentation.

---

## Product Listing & Details

### List Products
- **Endpoint:** `GET /api/products`
- **Purpose:** List products with filters (keyword, subcategory, etc.)
- **Who can call:** Anyone (public)
- **Required:** At least one of `keyword` or `subcategory_id`
- **Notes:** Supports pagination, sorting, and attribute filtering. See API docs for all filters.

### Get Single Product
- **Endpoint:** `GET /api/products/{product}`
- **Purpose:** Get a single product by ID
- **Who can call:** Anyone (public)
- **Notes:** Always returns `subcategory`, `category`, and `location` objects (not just IDs).

### Get Product by Slug
- **Endpoint:** `GET /api/products/slug/{slug}`
- **Purpose:** Get a product by its slug (SEO-friendly)
- **Who can call:** Anyone (public)

### Get Product Attributes
- **Endpoint:** `GET /api/products/{product}/attributes`
- **Purpose:** Get all attributes and their values for a product
- **Who can call:** Anyone (public)
- **Notes:** Useful for displaying product details or edit forms.

---

## Product Management (Seller/Admin)

### Create Basic Product Info
- **Endpoint:** `POST /api/products/basic-info`
- **Purpose:** Create a new product (step 1)
- **Who can call:** Authenticated sellers
- **Required fields:** `name`, `description`, `price`, `subcategory_id`, `location_id`, `manage_stock`, `stock_quantity`
- **Order:** Must be called before adding attributes or images
- **Notes:** Returns the new product object

### Update Basic Product Info
- **Endpoint:** `PUT /api/products/{product}/basic-info`
- **Purpose:** Update an existing product’s basic info
- **Who can call:** Seller (their own product), Admin
- **Dependencies:** Product must exist

### Delete Product
- **Endpoint:** `DELETE /api/products/{product}`
- **Purpose:** Delete a product
- **Who can call:** Seller (their own product), Admin
- **Notes:** Deleting a product also deletes its variants and images

### Add/Update Attributes
- **Endpoint:** `POST /api/products/{product}/attributes`
- **Purpose:** Add or update product attributes (step 2)
- **Who can call:** Seller (their own product), Admin
- **Dependencies:** Product must exist
- **Order:** After creating basic info, before publishing
- **Notes:** Attributes must match those available for the product’s subcategory

### Upload Images
- **Endpoint:** `POST /api/products/{product}/images`
- **Purpose:** Upload product images (step 3)
- **Who can call:** Seller (their own product), Admin
- **Dependencies:** Product must exist
- **Order:** After attributes, before publishing
- **Notes:** Max 10 images, 2MB each, allowed types: jpg, jpeg, png, gif

### Publish Product
- **Endpoint:** `POST /api/products/{product}/publish`
- **Purpose:** Publish a product (sets status to active)
- **Who can call:** Seller (their own product), Admin
- **Dependencies:** Product must have basic info, attributes, and at least one image
- **Order:** Final step before product is visible to customers

### Update Product Status
- **Endpoint:** `PATCH /api/products/{product}/status`
- **Purpose:** Update product status (active, paused, denied, out_of_stock)
- **Who can call:** Seller (their own product, only active/paused), Admin (all statuses)
- **Required fields:** `status` (and `reason` if status is denied)
- **Dependencies:** Product must not be draft/denied for sellers
- **Notes:** Sellers cannot set status to draft or denied
- **Common pitfalls:** Trying to pause a draft/denied product will fail

### Get My Products
- **Endpoint:** `GET /api/products/my-products`
- **Purpose:** List products for the authenticated seller
- **Who can call:** Seller
- **Notes:** Supports filters: status, sort_by, sort_direction, stock_status, keyword, category_id, location_id, subcategory_id (only if both category and location)
- **Pagination:** Fixed page size (10)

### Get My Product Stats
- **Endpoint:** `GET /api/products/my-stats`
- **Purpose:** Get product statistics for the authenticated seller
- **Who can call:** Seller

### Bulk Update Product Status
- **Endpoint:** `PATCH /api/products/bulk-status`
- **Purpose:** Bulk update product status for seller
- **Who can call:** Seller
- **Required fields:** `product_ids` (array), `status` (active/paused)
- **Notes:** All products must belong to the seller

### Get Subcategory Attributes
- **Endpoint:** `GET /api/attributes/subcategory/{subcategoryId}`
- **Purpose:** Get all attributes (and their values) for a subcategory
- **Who can call:** Anyone
- **When to call:** After subcategory selection, before showing attribute selection UI
- **Notes:** Each subcategory can have different attributes

---

## Product Variants

### Create Variant
- **Endpoint:** `POST /api/products/{productId}/variants`
- **Purpose:** Create a new product variant
- **Who can call:** Seller (their own product), Admin
- **Dependencies:** Product must exist
- **Required fields:** See API docs

### Update Variant
- **Endpoint:** `PUT /api/products/variants/{id}`
- **Purpose:** Update a product variant
- **Who can call:** Seller (their own product), Admin

### Delete Variant
- **Endpoint:** `DELETE /api/products/variants/{id}`
- **Purpose:** Delete a product variant
- **Who can call:** Seller (their own product), Admin

### Upload Variant Images
- **Endpoint:** `POST /api/products/variants/{id}/images`
- **Purpose:** Upload images for a product variant
- **Who can call:** Seller (their own product), Admin
- **Notes:** Same image rules as product images

### Delete Variant Image
- **Endpoint:** `DELETE /api/products/variants/{id}/images/{imageId}`
- **Purpose:** Delete an image from a product variant
- **Who can call:** Seller (their own product), Admin

### Batch Update Variants
- **Endpoint:** `POST /api/products/variants/batch/update`
- **Purpose:** Batch update product variants
- **Who can call:** Seller (their own product), Admin

---

## Inventory Management

### Update Product Stock
- **Endpoint:** `PATCH /api/products/{product}/stock`
- **Purpose:** Update the stock quantity for a product
- **Who can call:** Seller (their own product), Admin
- **Required fields:** `quantity`, `reason` (optional)
- **Notes:** Use for restocking or adjusting inventory

---

## Error Handling & Response Structure
- All API responses follow a standard structure: see API docs for details
- Always check `success` and `status` fields
- For paginated endpoints, use `data.data` (array) and `data.meta` (pagination info)
- For validation errors, display the `errors` object
- For other errors, display the `message` field

---

## Best Practices
- Validate user input before sending to API
- Show loading and error states for all API calls
- Use the `message` field for user feedback
- Handle paginated data and allow navigation
- For file uploads, validate file type and size before sending
- For variant management, check for duplicate attribute combinations before creating
- Use seller-specific endpoints for dashboard/product management views
- Use batch/bulk endpoints for mass updates

---

## Special Notes
- The product object always includes `subcategory`, `category`, and `location` as objects (with at least `id` and `name`)
- Do not expect the old *_id fields at the top level
- For request/response examples, see the API documentation
