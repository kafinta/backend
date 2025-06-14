# Product Management API Documentation

## Endpoints

### Product Management

#### Create Basic Product Info
```http
POST /api/products/basic-info
```

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
    "message": "Basic product information saved successfully",
    "product": {
        "id": "integer",
        "name": "string",
        "description": "string",
        "price": "numeric",
        "status": "draft",
        "manage_stock": "boolean",
        "stock_quantity": "integer"
    }
}
```

#### Update Basic Product Info
```http
PUT /api/products/{product}/basic-info
```

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
    "message": "Product information updated successfully",
    "product": {
        "id": "integer",
        "name": "string",
        "description": "string",
        "price": "numeric",
        "status": "string",
        "manage_stock": "boolean",
        "stock_quantity": "integer"
    }
}
```

### Product Attributes

#### Add/Update Attributes
```http
POST /api/products/{product}/attributes
```

**Request Body:**
```json
{
    "attributes": [
        {
            "attribute_id": "integer",
            "value_id": "integer"
        }
    ]
}
```

**Response (200 OK):**
```json
{
    "message": "Product attributes updated successfully",
    "product": {
        "id": "integer",
        "attributes": [
            {
                "id": "integer",
                "name": "string",
                "value": {
                    "id": "integer",
                    "name": "string"
                }
            }
        ]
    }
}
```

### Product Images

#### Upload Images
```http
POST /api/products/{product}/images
```

**Content-Type:** `multipart/form-data`

**Request Body:**
```
images[]: file
```

**Response (200 OK):**
```json
{
    "message": "Images uploaded successfully",
    "product": {
        "id": "integer",
        "images": [
            {
                "id": "integer",
                "url": "string",
                "thumbnail_url": "string"
            }
        ]
    }
}
```

### Product Status

#### Update Status
```http
PATCH /api/products/{product}/status
```

**Request Body:**
```json
{
    "status": "active|paused|denied|out_of_stock",
    "reason": "string"
}
```

**Response (200 OK):**
```json
{
    "message": "Product status updated successfully",
    "product": {
        "id": "integer",
        "status": "string",
        "denial_reason": "string"
    }
}
```

### Product Variants

#### Create Variant
```http
POST /api/products/{product}/variants
```

**Request Body:**
```json
{
    "name": "string",
    "price": "numeric",
    "attributes": [
        {
            "attribute_id": "integer",
            "value_id": "integer"
        }
    ],
    "manage_stock": "boolean",
    "stock_quantity": "integer"
}
```

**Response (201 Created):**
```json
{
    "message": "Variant created successfully",
    "variant": {
        "id": "integer",
        "name": "string",
        "price": "numeric",
        "attributes": [
            {
                "id": "integer",
                "name": "string",
                "value": {
                    "id": "integer",
                    "name": "string"
                }
            }
        ],
        "manage_stock": "boolean",
        "stock_quantity": "integer"
    }
}
```

### Inventory Management

#### Update Stock
```http
PATCH /api/products/{product}/stock
```

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
    "message": "Stock updated successfully",
    "product": {
        "id": "integer",
        "stock_quantity": "integer",
        "status": "string"
    }
}
```

## Error Responses

### Validation Errors (422 Unprocessable Entity)
```json
{
    "message": "The given data was invalid",
    "errors": {
        "field": [
            "error message"
        ]
    }
}
```

### Not Found (404)
```json
{
    "message": "Product not found"
}
```

### Unauthorized (401)
```json
{
    "message": "Unauthenticated"
}
```

### Forbidden (403)
```json
{
    "message": "You do not have permission to perform this action"
}
```

### Conflict (409)
```json
{
    "message": "Variant with these attributes already exists"
} 