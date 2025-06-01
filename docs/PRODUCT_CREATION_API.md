# Product Creation API Documentation

⚠️ **DEPRECATED**: This documentation has been superseded by the comprehensive [PRODUCT_API_DOCUMENTATION.md](./PRODUCT_API_DOCUMENTATION.md) which includes progress tracking, analytics, and updated response formats.

---

## Overview

The product creation system has been simplified to a clean, 3-step process that eliminates complex session management and provides a straightforward API for creating products.

## Architecture

### Services Used
- ✅ **ProductImageService** - Handles image uploads in Step 3
- ✅ **ProductAttributeService** - Available but not directly used (Product model handles attributes)
- ✅ **ProductService** - Used for traditional CRUD operations
- ✅ **FileService** - Available for file operations

### Response Format
All endpoints follow the ImprovedController standard response structure:
```json
{
  "success": true,
  "message": "Success message",
  "data": {
    "product": {...},
    "next_step": "attributes",
    "progress": {...}
  }
}
```

## Step-by-Step Process

### Step 1: Basic Information + Inventory
**Endpoint:** `POST /api/product-creation/basic-info`

**Required Fields:**
- `name` (string, max 255) - Product name
- `description` (string) - Product description
- `price` (numeric, min 0) - Product price
- `subcategory_id` (integer) - Must exist in subcategories table
- `manage_stock` (boolean) - Whether to track inventory
- `stock_quantity` (integer, min 0) - Required if manage_stock is true

**Optional Fields:**
- `location_id` (integer) - Must exist in locations table
- `status` (string) - draft|active|inactive (defaults to 'draft')

**Example Request:**
```json
{
  "name": "Wireless Headphones",
  "description": "High-quality wireless headphones with noise cancellation",
  "price": 199.99,
  "subcategory_id": 5,
  "location_id": 1,
  "manage_stock": true,
  "stock_quantity": 50,
  "status": "draft",
  "is_featured": false
}
```

**Response:**
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Basic product information and inventory saved successfully",
  "data": {
    "id": 123,
    "name": "Wireless Headphones",
    "slug": "wireless-headphones",
    "description": "High-quality wireless headphones...",
    "price": 199.99,
    "status": "draft",
    "is_featured": false,
    "manage_stock": true,
    "stock_quantity": 50,
    "subcategory": {
      "id": 1,
      "name": "Electronics"
    },
    "location": {
      "id": 1,
      "name": "Lagos"
    }
  }
}
```

### Step 2: Product Attributes
**Endpoint:** `POST /api/product-creation/{product}/attributes`

**Required Fields:**
- `attributes` (array) - Array of attribute-value pairs

**Validation:**
- Each `attribute_id` must belong to the product's subcategory
- Each `value_id` must belong to the specified `attribute_id`
- Provides clear error messages for invalid combinations

**Example Request:**
```json
{
  "attributes": [
    {
      "attribute_id": 1,
      "value_id": 15
    },
    {
      "attribute_id": 3,
      "value_id": 23
    },
    {
      "attribute_id": 8,
      "value_id": 41
    }
  ]
}
```

**Response:**
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Product attributes updated successfully",
  "data": {
    "id": 123,
    "name": "Wireless Headphones",
    "slug": "wireless-headphones",
    "description": "High-quality wireless headphones...",
    "price": 199.99,
    "status": "draft",
    "is_featured": false,
    "manage_stock": true,
    "stock_quantity": 50,
    "subcategory": {
      "id": 1,
      "name": "Electronics"
    },
    "location": {
      "id": 1,
      "name": "Lagos"
    },
    "attributes": [
      {
        "id": 1,
        "name": "Color",
        "value": {
          "id": 15,
          "name": "Black"
        }
      },
      {
        "id": 3,
        "name": "Brand",
        "value": {
          "id": 23,
          "name": "Sony"
        }
      }
    ]
  }
}
```

### Step 3: Upload Images
**Endpoint:** `POST /api/product-creation/{product}/images`

**Content-Type:** `multipart/form-data`

**Required Fields:**
- `images[]` (files) - Array of image files (jpeg,png,jpg,gif, max 2MB each)

**Example Request:**
```bash
curl -X POST \
  -H "Authorization: Bearer {token}" \
  -F "images[]=@image1.jpg" \
  -F "images[]=@image2.jpg" \
  http://localhost:8000/api/product-creation/123/images
```

**Response:**
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Images uploaded successfully",
  "data": {
    "id": 123,
    "name": "Wireless Headphones",
    "slug": "wireless-headphones",
    "description": "High-quality wireless headphones...",
    "price": 199.99,
    "status": "draft",
    "is_featured": false,
    "manage_stock": true,
    "stock_quantity": 50,
    "subcategory": {
      "id": 1,
      "name": "Electronics"
    },
    "location": {
      "id": 1,
      "name": "Lagos"
    },
    "attributes": [
      {
        "id": 1,
        "name": "Color",
        "value": {
          "id": 15,
          "name": "Black"
        }
      }
    ],
    "images": [
      {
        "id": 1,
        "url": "/storage/products/image1.jpg"
      },
      {
        "id": 2,
        "url": "/storage/products/image2.jpg"
      }
    ]
  }
}
```

### Step 4: Review and Publish
**Endpoint:** `POST /api/product-creation/{product}/publish`

**Description:** Automatically publishes the product (sets status to 'active') if all required steps are completed.

**Required Fields:** None - Status is automatically set to 'active'

**Validation:**
- All basic info must be complete
- At least one attribute must be set
- At least one image must be uploaded

**Example Request:**
```json
{}
```

**Response:**
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Product published successfully",
  "data": {
    "id": 123,
    "name": "Wireless Headphones",
    "slug": "wireless-headphones",
    "description": "High-quality wireless headphones...",
    "price": 199.99,
    "status": "active",
    "is_featured": false,
    "manage_stock": true,
    "stock_quantity": 50,
    "subcategory": {
      "id": 1,
      "name": "Electronics"
    },
    "location": {
      "id": 1,
      "name": "Lagos"
    },
    "attributes": [
      {
        "id": 1,
        "name": "Color",
        "value": {
          "id": 15,
          "name": "Black"
        }
      }
    ],
    "images": [
      {
        "id": 1,
        "url": "/storage/products/image1.jpg"
      }
    ]
  }
}
```

## Product Status Management

### Update Product Status
**Endpoint:** `PATCH /api/products/{product}/status`

**Description:** Allows sellers to pause/activate their products, and admins to moderate products.

**Seller Permissions:**
- Can change status between `active` and `paused`
- Cannot change `draft` products (must use publish endpoint)
- Cannot change `denied` products (admin review required)

**Admin Permissions:**
- Can set any status: `active`, `paused`, `denied`, `out_of_stock`
- Can deny products with reason

**Required Fields:**
- `status` (string) - For sellers: `active|paused`, For admins: `active|paused|denied|out_of_stock`
- `reason` (string, max 500) - Required when admin sets status to `denied`

**Example Requests:**

**Seller pausing product:**
```json
{
  "status": "paused"
}
```

**Admin denying product:**
```json
{
  "status": "denied",
  "reason": "Product images do not meet quality standards. Please upload clearer photos."
}
```

**Response:**
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Product paused successfully",
  "data": {
    "id": 123,
    "name": "Wireless Headphones",
    "status": "paused",
    "denial_reason": null,
    ...
  }
}
```

**Status Workflow:**
1. **draft** → (publish endpoint) → **active**
2. **active** ↔ **paused** (seller control)
3. **active/paused** → **denied** (admin only)
4. **denied** → **active** (admin review and approval)

## Authentication & Authorization

All endpoints require:
- **Authentication:** Bearer token via `Authorization: Bearer {token}`
- **Authorization:** User must have `seller` or `admin` role
- **Ownership:** For steps 2-4, user must own the product

## Error Handling

**Validation Errors (422):**
```json
{
  "status": "fail",
  "status_code": 422,
  "message": "Validation failed",
  "data": {
    "name": ["The name field is required."],
    "price": ["The price must be at least 0."]
  }
}
```

**Attribute Validation Errors (422):**
```json
{
  "status": "fail",
  "status_code": 422,
  "message": "Attribute ID 5 does not belong to subcategory 'Electronics'"
}
```

```json
{
  "status": "fail",
  "status_code": 422,
  "message": "Value ID 99 does not belong to attribute ID 3"
}
```

**Authorization Errors (403):**
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

## Next Steps

1. **Test with Postman** - Use the provided examples to test each step
2. **Frontend Integration** - Update frontend to use these 3 steps
3. **Variant System** - Implement product variants after testing
4. **Additional Features** - Add bulk operations, templates, etc.

## Testing Checklist

- [ ] Step 1: Create basic product with inventory
- [ ] Step 2: Add attributes to product
- [ ] Step 3: Upload product images
- [ ] Step 4: Publish product
- [ ] Clean response format without timestamps
- [ ] Error handling for invalid data
- [ ] Authorization checks work
- [ ] Product appears in listings when published
