# Product Management API Documentation

## Table of Contents
- [Product Listing (Public)](#product-listing-public)
- [Get Single Product (Public)](#get-single-product-public)
- [Product Management (Seller/Admin)](#product-management-selleradmin)
  - [Create Basic Product Info](#create-basic-product-info)
  - [Update Basic Product Info](#update-basic-product-info)
  - [Delete Product](#delete-product)
  - [Add/Update Attributes](#addupdate-attributes)
  - [Upload Images](#upload-images)
  - [Publish Product](#publish-product)
  - [Update Product Status](#update-product-status)
  - [Get My Products](#get-my-products)
  - [Get My Product Stats](#get-my-product-stats)
  - [Bulk Update Product Status](#bulk-update-product-status)
  - [Get Subcategory Attributes](#get-subcategory-attributes)
- [Product Variants](#product-variants)
- [Inventory Management](#inventory-management)
- [Error Responses](#error-responses)

---

## Product Listing (Public)

### List Products
```http
GET /api/products
```
**Description:** List products with filters (keyword, subcategory, etc.). At least one of `keyword` or `subcategory_id` is required.

**Query Parameters:**
- `per_page` (integer, default 15)
- `keyword` (string)
- `category_id` (integer)
- `subcategory_id` (array of integers)
- `min_price`, `max_price` (numeric)
- `is_featured` (boolean)
- `seller_id` (integer)
- `location_id` (array of integers)
- `sort_by` (name|price|created_at|updated_at|relevance)
- `sort_direction` (asc|desc)
- `stock_status` (in_stock|out_of_stock)
- `attributes` (array of attribute_value ids)

**Example:**
```
GET /api/products?keyword=shoes&per_page=10&sort_by=price&sort_direction=asc
```

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Products retrieved successfully",
  "data": {
    "data": [
      { /* product fields */ }
    ],
    "meta": { "current_page": 1, "last_page": 2, "per_page": 10, "total": 15 }
  }
}
```

---

## Get Single Product (Public)

### Get Product by ID
```http
GET /api/products/{product}
```
**Description:** Get a single product by its ID.

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Product retrieved successfully",
  "data": {
    "id": 1,
    "name": "Test Product",
    "slug": "test-product",
    "description": "A sample product.",
    "price": 100.0,
    "subcategory": { "id": 2, "name": "Shoes" },
    "category": { "id": 1, "name": "Fashion" },
    "location": { "id": 3, "name": "New York" },
    // ... other product fields ...
  }
}
```

**Note:**
- The `subcategory`, `category`, and `location` objects are always included for convenience. Their IDs and names are always present; additional fields may be included as needed.

### Get Product by Slug
```http
GET /api/products/slug/{slug}
```
**Description:** Get a single product by its slug. Useful for SEO-friendly URLs and product detail pages.

**Example:**
```
GET /api/products/slug/test-product-2
```

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Product retrieved successfully",
  "data": {
    "id": 1,
    "name": "Test Product",
    "slug": "test-product",
    "description": "A sample product.",
    "price": 100.0,
    "subcategory": { "id": 2, "name": "Shoes" },
    "category": { "id": 1, "name": "Fashion" },
    "location": { "id": 3, "name": "New York" },
    // ... other product fields ...
  }
}
```

**Error (404):**
```json
{
  "success": false,
  "status": "fail",
  "status_code": 404,
  "message": "Product not found"
}
```

### Get Product Attributes
```http
GET /api/products/{product}/attributes
```
**Description:** Get all attributes and their values for a specific product.

**Example:**
```
GET /api/products/1/attributes
```

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Product attributes retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "Color",
      "value": { "id": 10, "name": "Red", "representation": null }
    },
    {
      "id": 2,
      "name": "Size",
      "value": { "id": 20, "name": "Large", "representation": null }
    }
  ]
}
```

---

## Product Management (Seller/Admin)

### Create Basic Product Info
```http
POST /api/products/basic-info
```
**Description:** Create a new product (step 1).

**Request Body:**
```json
{
  "name": "string",
  "description": "string",
  "price": "numeric",
  "subcategory_id": "integer",
  "location_id": "integer",
  "manage_stock": "boolean",
  "stock_quantity": "integer"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 201,
  "message": "Basic product information and inventory saved successfully",
  "data": {
    "id": 1,
    "name": "Test Product",
    "slug": "test-product",
    "description": "A sample product.",
    "price": 100.0,
    "subcategory": { "id": 2, "name": "Shoes" },
    "category": { "id": 1, "name": "Fashion" },
    "location": { "id": 3, "name": "New York" },
    // ... other product fields ...
  }
}
```

### Update Basic Product Info
```http
PUT /api/products/{product}/basic-info
```
**Description:** Update an existing product's basic info.

**Request Body:**
```json
{
  "name": "string",
  "description": "string",
  "price": "numeric",
  "subcategory_id": "integer",
  "location_id": "integer",
  "manage_stock": "boolean",
  "stock_quantity": "integer"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Basic product information updated successfully",
  "data": { /* product fields */ }
}
```

### Delete Product
```http
DELETE /api/products/{product}
```
**Description:** Delete a product (seller or admin).

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Product deleted successfully"
}
```

---

### Add/Update Attributes
```http
POST /api/products/{product}/attributes
```
**Description:** Add or update product attributes (step 2).

**Request Body:**
```json
{
  "attributes": [
    { "attribute_id": "integer", "value_id": "integer" }
  ]
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Product attributes updated successfully",
  "data": { /* product fields */ }
}
```

---

### Upload Images
```http
POST /api/products/{product}/images
```
**Description:** Upload product images (step 3).

**Content-Type:** `multipart/form-data`

**Request Body:**
- `images[]`: file(s)

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Images uploaded successfully",
  "data": { /* product fields */ }
}
```

---

### Publish Product
```http
POST /api/products/{product}/publish
```
**Description:** Review and publish a product (step 4, sets status to active).

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Product published successfully",
  "data": { /* product fields */ }
}
```

---

### Update Product Status
```http
PATCH /api/products/{product}/status
```
**Description:** Update product status (active, paused, denied, out_of_stock). Sellers can only activate/pause their own products.

**Request Body:**
```json
{
  "status": "active|paused|denied|out_of_stock",
  "reason": "string" // required if status is denied
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Product status updated successfully",
  "data": { /* product fields */ }
}
```

---

### Get My Products
```http
GET /api/products/my-products
```
**Description:** List products for the authenticated seller. Supports filters: status, sort_by, sort_direction, stock_status, keyword (search by name only), category_id, location_id, subcategory_id (only if both category_id and location_id are present).

**Query Parameters:**
- `status`, `sort_by`, `sort_direction`, `stock_status`, `keyword`, `category_id`, `location_id`, `subcategory_id`

- The page size is fixed at 10 products per page.
- The `keyword` filter only searches the product name (not description).
- Sellers cannot filter by is_featured or attributes.
- The `subcategory_id` filter is only applied if both `category_id` and `location_id` are present in the request.

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Your products retrieved successfully",
  "data": {
    "data": [ /* product list */ ],
    "meta": { "current_page": 1, "last_page": 3, "per_page": 10, "total": 25 }
  }
}
```

---

### Get My Product Stats
```http
GET /api/products/my-stats
```
**Description:** Get product statistics for the authenticated seller.

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Product statistics retrieved successfully",
  "data": { /* stats fields */ }
}
```

---

### Bulk Update Product Status
```http
PATCH /api/products/bulk-status
```
**Description:** Bulk update product status for seller.

**Request Body:**
```json
{
  "product_ids": [1, 2, 3],
  "status": "active|paused"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Products status updated successfully",
  "data": { /* result fields */ }
}
```

---

### Get Subcategory Attributes
```http
GET /api/attributes/subcategory/{subcategoryId}
```
**Description:** Get all attributes (and their values) for a given subcategory. Use this endpoint before the seller selects product attributes.

**Example:**
```
GET /api/attributes/subcategory/5
```

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Attributes fetched successfully",
  "data": [
    {
      "id": 1,
      "name": "Color",
      "values": [
        { "id": 10, "name": "Red" },
        { "id": 11, "name": "Blue" }
      ]
    },
    {
      "id": 2,
      "name": "Size",
      "values": [
        { "id": 20, "name": "Small" },
        { "id": 21, "name": "Large" }
      ]
    }
  ]
}
```

**Note:**
- The frontend should call this endpoint after the seller selects a subcategory and before displaying the attribute selection UI.
- This ensures the correct attributes and values are shown for the selected subcategory.

---

## Product Variants

### Create Variant
```http
POST /api/products/{productId}/variants
```
**Description:** Create a new product variant.

**Request Body:**
```json
{
  "name": "string",
  "price": "numeric",
  "attributes": [
    { "attribute_id": "integer", "value_id": "integer" }
  ],
  "manage_stock": "boolean",
  "stock_quantity": "integer"
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 201,
  "message": "Variant created successfully",
  "data": { /* variant fields */ }
}
```

### Update Variant
```http
PUT /api/products/variants/{id}
```
**Description:** Update a product variant.

**Request Body:**
```json
{
  "name": "string",
  "price": "numeric",
  "attributes": [
    { "attribute_id": "integer", "value_id": "integer" }
  ],
  "manage_stock": "boolean",
  "stock_quantity": "integer"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Variant updated successfully",
  "data": { /* variant fields */ }
}
```

### Delete Variant
```http
DELETE /api/products/variants/{id}
```
**Description:** Delete a product variant.

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Variant deleted successfully"
}
```

### Upload Variant Images
```http
POST /api/products/variants/{id}/images
```
**Description:** Upload images for a product variant.

**Content-Type:** `multipart/form-data`

**Request Body:**
- `images[]`: file(s)

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Variant images uploaded successfully",
  "data": { /* variant fields */ }
}
```

### Delete Variant Image
```http
DELETE /api/products/variants/{id}/images/{imageId}
```
**Description:** Delete an image from a product variant.

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Variant image deleted successfully"
}
```

### Batch Update Variants
```http
POST /api/products/variants/batch/update
```
**Description:** Batch update product variants.

**Request Body:**
```json
{
  "variants": [ /* array of variant update objects */ ]
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Variants updated successfully",
  "data": { /* result fields */ }
}
```

---

## Inventory Management

### Update Product Stock
```http
PATCH /api/products/{product}/stock
```
**Description:** Update the stock quantity for a product.

**Request Body:**
```json
{
  "quantity": "integer",
  "reason": "string"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "Stock updated successfully",
  "data": { /* product fields */ }
}
```

---

## Error Responses

### Validation Errors (422 Unprocessable Entity)
```json
{
  "success": false,
  "status": "fail",
  "status_code": 422,
  "message": "Validation failed",
  "errors": {
    "field": ["error message"]
  }
}
```

### Not Found (404)
```json
{
  "success": false,
  "status": "fail",
  "status_code": 404,
  "message": "Product not found"
}
```

### Unauthorized (401)
```json
{
  "success": false,
  "status": "fail",
  "status_code": 401,
  "message": "Unauthenticated"
}
```

### Forbidden (403)
```json
{
  "success": false,
  "status": "fail",
  "status_code": 403,
  "message": "You do not have permission to perform this action"
}
```

### Conflict (409)
```json
{
  "success": false,
  "status": "fail",
  "status_code": 409,
  "message": "Variant with these attributes already exists"
}
```

## Product Discount API

### 1. Product Creation/Update (Step 1)
Discount fields can be included in the product creation and update requests:

**Fields:**
- `discount_type`: `percent` or `fixed` (optional)
- `discount_value`: decimal (optional, required if `discount_type` is set)
- `discount_start`: datetime (optional)
- `discount_end`: datetime (optional, must be after or equal to `discount_start`)

**Example Request (POST /products/basic-info):**
```json
{
  "name": "Sample Product",
  "description": "A great product.",
  "price": 100.00,
  "subcategory_id": 1,
  "manage_stock": true,
  "stock_quantity": 10,
  "discount_type": "percent",
  "discount_value": 20,
  "discount_start": "2024-07-01T00:00:00Z",
  "discount_end": "2024-07-10T23:59:59Z"
}
```

**Validation:**
- If `discount_type` is set, `discount_value` is required.
- `percent` discounts: 0–100.
- `fixed` discounts: must not exceed product price.
- `discount_end` must not be before `discount_start`.

---

### 2. Standalone Discount Endpoints

#### Set/Update Discount
- **PUT /products/{product}/discount**
- **Body:**
```json
{
  "discount_type": "fixed",
  "discount_value": 10,
  "discount_start": "2024-07-01T00:00:00Z",
  "discount_end": "2024-07-10T23:59:59Z"
}
```
- **Permissions:** Only the product owner or admin.
- **Validation:** Same as above.
- **Response:** Returns the updated product resource with discount info.

#### Remove Discount
- **DELETE /products/{product}/discount**
- **Permissions:** Only the product owner or admin.
- **Response:** Returns the updated product resource with discount fields set to null.

---

### 3. API Response Fields

**Product Resource:**
```json
{
  "id": 1,
  "name": "Sample Product",
  "price": 100.00,
  "discount_type": "percent",
  "discount_value": 20,
  "discount_start": "2024-07-01T00:00:00Z",
  "discount_end": "2024-07-10T23:59:59Z",
  "has_active_discount": true,
  "discount_amount": 20.00,
  "discounted_price": 80.00,
  ...
}
```

**Cart Item Resource:**
```json
{
  "id": 1,
  "product": { ... },
  "price": 80.00,
  "original_price": 100.00,
  "discounted_price": 80.00,
  "discount_amount": 20.00,
  "has_active_discount": true,
  ...
}
```

---

### 4. Permissions & Error Responses
- Only the product owner or admin can set/update/remove discounts.
- Validation errors return 422 with details.
- Unauthorized access returns 403.

---

### 5. Notes
- Discounts are always product-specific and automatic (no coupon required).
- Discounts are reflected in all relevant API responses. 