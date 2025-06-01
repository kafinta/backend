# 📦 **Product Management API Documentation**

## Overview
The product management system provides comprehensive product creation, management, and progress tracking. It supports step-by-step product creation with progress indicators for draft products and analytics for active products.

## Product Status Workflow
```
draft → active (published)
  ↓       ↓
denied  paused
```

## Base URL
```
http://localhost:8000/api
```

---

## 📦 **PRODUCT CREATION WORKFLOW**

### Step 1: Create Basic Product Information
```http
POST /api/products/basic-info
```

**Description**: Create a new product with basic information and inventory details.

**Headers**: 
- `Authorization: Bearer {token}` (required - seller role)
- `Content-Type: application/json`

**Request Body**:
```json
{
  "name": "Cotton T-Shirt",
  "description": "Comfortable cotton t-shirt perfect for everyday wear",
  "price": "29.99",
  "subcategory_id": 1,
  "location_id": 2,
  "manage_stock": true,
  "stock_quantity": 100
}
```

**Validation Rules**:
- `name`: required|string|max:255
- `description`: required|string
- `price`: required|numeric|min:0
- `subcategory_id`: required|exists:subcategories,id
- `location_id`: required|exists:locations,id
- `manage_stock`: boolean
- `stock_quantity`: required_if:manage_stock,true|integer|min:0

**Response (Draft Product)**:
```json
{
  "status": "success",
  "status_code": 201,
  "message": "Basic product information and inventory saved successfully",
  "data": {
    "id": 2,
    "name": "Cotton T-Shirt",
    "slug": "cotton-t-shirt",
    "description": "Comfortable cotton t-shirt perfect for everyday wear",
    "price": "29.99",
    "status": "draft",
    "is_featured": false,
    "manage_stock": true,
    "stock_quantity": 100,
    "is_in_stock": true,
    "is_out_of_stock": false,
    "subcategory": {
      "id": 1,
      "name": "T-Shirts"
    },
    "location": {
      "id": 2,
      "name": "Bedroom",
      "image_path": "/storage/locations/bedroom.jpg"
    },
    "images": [],
    "attributes": [],
    "completion_status": {
      "basic_info": true,
      "attributes": false,
      "images": false
    },
    "next_step": "attributes",
    "progress_percentage": 33
  }
}
```

### Step 2: Update Product Attributes
```http
PUT /api/products/{id}/attributes
```

**Description**: Set attribute values for the product based on subcategory requirements.

**Request Body**:
```json
{
  "attribute_values": [
    {
      "attribute_id": 1,
      "value_id": 5
    },
    {
      "attribute_id": 2,
      "value_id": 8
    }
  ]
}
```

**Response**:
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Product attributes updated successfully",
  "data": {
    "id": 2,
    "name": "Cotton T-Shirt",
    "status": "draft",
    "attributes": [
      {
        "id": 1,
        "name": "Size",
        "value": {
          "id": 5,
          "name": "Medium",
          "representation": null
        }
      },
      {
        "id": 2,
        "name": "Color",
        "value": {
          "id": 8,
          "name": "Blue",
          "representation": {"hex": "#0066cc"}
        }
      }
    ],
    "completion_status": {
      "basic_info": true,
      "attributes": true,
      "images": false
    },
    "next_step": "images",
    "progress_percentage": 67
  }
}
```

### Step 3: Upload Product Images
```http
POST /api/products/{id}/images
```

**Description**: Upload images for the product.

**Headers**: 
- `Authorization: Bearer {token}` (required)
- `Content-Type: multipart/form-data`

**Request Body** (Form Data):
```
images[]: file1.jpg
images[]: file2.jpg
images[]: file3.jpg
```

**Response**:
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Images uploaded successfully",
  "data": {
    "id": 2,
    "name": "Cotton T-Shirt",
    "status": "draft",
    "images": [
      {
        "id": 1,
        "path": "/storage/products/tshirt1.jpg",
        "url": "http://localhost:8000/storage/products/tshirt1.jpg",
        "alt_text": "Cotton T-Shirt",
        "is_primary": true
      },
      {
        "id": 2,
        "path": "/storage/products/tshirt2.jpg",
        "url": "http://localhost:8000/storage/products/tshirt2.jpg",
        "alt_text": "Cotton T-Shirt",
        "is_primary": false
      }
    ],
    "completion_status": {
      "basic_info": true,
      "attributes": true,
      "images": true
    },
    "next_step": "publish",
    "progress_percentage": 100
  }
}
```

### Step 4: Publish Product
```http
POST /api/products/{id}/publish
```

**Description**: Publish the completed product to make it live.

**Response (Active Product)**:
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Product published successfully",
  "data": {
    "id": 2,
    "name": "Cotton T-Shirt",
    "slug": "cotton-t-shirt",
    "description": "Comfortable cotton t-shirt perfect for everyday wear",
    "price": "29.99",
    "status": "active",
    "is_featured": false,
    "manage_stock": true,
    "stock_quantity": 100,
    "is_in_stock": true,
    "is_out_of_stock": false,
    "subcategory": {
      "id": 1,
      "name": "T-Shirts"
    },
    "location": {
      "id": 2,
      "name": "Bedroom"
    },
    "images": [
      {
        "id": 1,
        "path": "/storage/products/tshirt1.jpg",
        "url": "http://localhost:8000/storage/products/tshirt1.jpg",
        "alt_text": "Cotton T-Shirt",
        "is_primary": true
      }
    ],
    "attributes": [
      {
        "id": 1,
        "name": "Size",
        "value": {
          "id": 5,
          "name": "Medium",
          "representation": null
        }
      }
    ],
    "analytics": {
      "views": 0,
      "orders": 0,
      "revenue": "0.00",
      "conversion_rate": "0.00"
    }
  }
}
```

---

## 📊 **PRODUCT MANAGEMENT ENDPOINTS**

### 1. List User Products
```http
GET /api/products
```

**Description**: Get all products for the authenticated seller.

**Query Parameters**:
- `status` (optional): Filter by status (draft, active, paused, denied)
- `per_page` (optional): Number of products per page (default: 15)

**Response**:
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Products retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Draft Product",
        "status": "draft",
        "completion_status": {
          "basic_info": true,
          "attributes": false,
          "images": false
        },
        "next_step": "attributes",
        "progress_percentage": 33
      },
      {
        "id": 2,
        "name": "Published Product",
        "status": "active",
        "price": "99.99",
        "analytics": {
          "views": 150,
          "orders": 5,
          "revenue": "499.95",
          "conversion_rate": "3.33"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 15,
      "total": 45
    }
  }
}
```

### 2. View Single Product
```http
GET /api/products/{id}
```

**Description**: Get detailed information about a specific product.

**Response**: Same structure as product creation responses above, depending on product status.

### 3. Update Product Status
```http
PATCH /api/products/{id}/status
```

**Description**: Update product status (admin can approve/deny, sellers can pause/activate).

**Request Body**:
```json
{
  "status": "active",
  "reason": "Product meets all requirements" // Optional, for denial
}
```

**Response**: Updated product object with new status.

---

## 🔧 **PROGRESS TRACKING FEATURES**

### Completion Status
Draft products include a `completion_status` object showing which steps are completed:
```json
{
  "completion_status": {
    "basic_info": true,    // Name, description, price, subcategory set
    "attributes": false,   // Required attributes not yet set
    "images": false        // No images uploaded yet
  }
}
```

### Next Step Guidance
The `next_step` field tells sellers what to do next:
- `"basic_info"` - Complete basic product information
- `"attributes"` - Set required product attributes
- `"images"` - Upload product images
- `"publish"` - Product ready to publish

### Progress Percentage
Visual progress indicator from 0-100% based on completed steps:
```json
{
  "progress_percentage": 67  // 2 out of 3 steps completed
}
```

### Analytics for Active Products
Active products include analytics data:
```json
{
  "analytics": {
    "views": 150,           // Total product views
    "orders": 5,            // Number of orders containing this product
    "revenue": "499.95",    // Total revenue from this product
    "conversion_rate": "3.33" // (orders/views) * 100
  }
}
```

---

## 🔧 **FRONTEND IMPLEMENTATION GUIDE**

### JavaScript Service Example

```javascript
class ProductService {
  constructor(baseURL = 'http://localhost:8000/api') {
    this.baseURL = baseURL;
    this.token = localStorage.getItem('auth_token');
  }

  // Create basic product info
  async createBasicInfo(productData) {
    const response = await fetch(`${this.baseURL}/products/basic-info`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(productData)
    });
    return response.json();
  }

  // Update product attributes
  async updateAttributes(productId, attributeValues) {
    const response = await fetch(`${this.baseURL}/products/${productId}/attributes`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ attribute_values: attributeValues })
    });
    return response.json();
  }

  // Upload product images
  async uploadImages(productId, images) {
    const formData = new FormData();
    images.forEach(image => {
      formData.append('images[]', image);
    });

    const response = await fetch(`${this.baseURL}/products/${productId}/images`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`
      },
      body: formData
    });
    return response.json();
  }

  // Publish product
  async publishProduct(productId) {
    const response = await fetch(`${this.baseURL}/products/${productId}/publish`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      }
    });
    return response.json();
  }

  // Get user products
  async getProducts(status = null, page = 1) {
    const params = new URLSearchParams({ page });
    if (status) params.append('status', status);

    const response = await fetch(`${this.baseURL}/products?${params}`, {
      headers: {
        'Authorization': `Bearer ${this.token}`
      }
    });
    return response.json();
  }
}
```

The product management system is fully functional with comprehensive progress tracking! 📦✨
