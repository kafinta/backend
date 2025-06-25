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
  "data": { /* product fields */ }
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
  "data": { /* product fields */ }
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
**Description:** List products for the authenticated seller. Supports filters: per_page, status, sort_by, sort_direction, stock_status.

**Query Parameters:**
- `per_page`, `status`, `sort_by`, `sort_direction`, `stock_status`

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