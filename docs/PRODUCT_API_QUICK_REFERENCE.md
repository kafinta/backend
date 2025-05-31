# Product API Quick Reference

## 🚀 **Quick Start**

### **Product Discovery (Public)**
```bash
# Search products
GET /api/products?keyword=smartphone&location_id[]=2

# Browse by category
GET /api/products?subcategory_id[]=5&attributes[]=15

# Combined search + category
GET /api/products?keyword=iPhone&subcategory_id[]=5
```

### **Product Management (Protected)**
```bash
# Create new product
POST /api/products/basic-info
PUT /api/products/{id}/basic-info
POST /api/products/{id}/attributes
POST /api/products/{id}/images
POST /api/products/{id}/publish

# Update existing product
PUT /api/products/{id}/basic-info
POST /api/products/{id}/attributes
POST /api/products/{id}/images
DELETE /api/images/{imageId}
```

## 📋 **Complete API Reference**

### **Product Discovery**
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/products` | Targeted product listing | No |
| GET | `/api/products/{product}` | Single product view | No |

### **Product Management**
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/products/basic-info` | Create basic info | Seller/Admin |
| PUT | `/api/products/{product}/basic-info` | Update basic info | Owner/Admin |
| POST | `/api/products/{product}/attributes` | Set attributes | Owner/Admin |
| POST | `/api/products/{product}/images` | Add images | Owner/Admin |
| POST | `/api/products/{product}/publish` | Publish product | Owner/Admin |
| DELETE | `/api/products/{product}` | Delete product | Owner/Admin |
| PATCH | `/api/products/{product}/status` | Update status | Owner/Admin |

### **Image Management**
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| DELETE | `/api/images/{imageId}` | Delete image | Owner/Admin |

### **Seller Management**
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/products/my-products` | List seller's products | Seller/Admin |
| GET | `/api/products/my-stats` | Product statistics | Seller/Admin |
| PATCH | `/api/products/bulk-status` | Bulk status update | Seller/Admin |

## 🔧 **Request Examples**

### **Step 1: Create Basic Info**
```bash
POST /api/products/basic-info
Content-Type: application/json
Authorization: Bearer {token}

{
  "name": "iPhone 15 Pro",
  "description": "Latest iPhone with titanium design",
  "price": 999.99,
  "subcategory_id": 5,
  "location_id": 2,
  "manage_stock": true,
  "stock_quantity": 50
}
```

### **Step 2: Set Attributes**
```bash
POST /api/products/123/attributes
Content-Type: application/json
Authorization: Bearer {token}

{
  "attributes": [
    {"attribute_id": 1, "value_id": 15},
    {"attribute_id": 2, "value_id": 23},
    {"attribute_id": 3, "value_id": 41}
  ]
}
```

### **Step 3: Upload Images**
```bash
POST /api/products/123/images
Content-Type: multipart/form-data
Authorization: Bearer {token}

images[]: file1.jpg
images[]: file2.jpg
images[]: file3.jpg
```

### **Step 4: Publish**
```bash
POST /api/products/123/publish
Authorization: Bearer {token}
```

### **Update Basic Info**
```bash
PUT /api/products/123/basic-info
Content-Type: application/json
Authorization: Bearer {token}

{
  "name": "Updated iPhone 15 Pro",
  "price": 899.99,
  "stock_quantity": 75
}
```

### **Delete Image**
```bash
DELETE /api/images/45
Authorization: Bearer {token}
```

### **Product Search**
```bash
# Search with filters
GET /api/products?keyword=smartphone&location_id[]=2&min_price=500&max_price=1500&sort_by=relevance

# Category browsing
GET /api/products?subcategory_id[]=5&subcategory_id[]=6&attributes[]=15&location_id[]=2

# Combined approach
GET /api/products?keyword=iPhone&subcategory_id[]=5&location_id[]=2&sort_by=price&sort_direction=asc
```

## 📝 **Response Format**

### **Success Response**
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    "product": {
      "id": 123,
      "name": "iPhone 15 Pro",
      "price": "999.99",
      "status": "draft",
      "subcategory": {...},
      "images": [...],
      "attributes": [...]
    }
  }
}
```

### **Error Response**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."],
    "price": ["The price must be at least 0."]
  }
}
```

### **Product Discovery Response**
```json
{
  "success": true,
  "message": "Products retrieved successfully",
  "data": {
    "products": {
      "current_page": 1,
      "data": [...],
      "pagination": {...}
    },
    "filters": {
      "available_locations": [...],
      "available_attributes": [...],
      "price_range": {"min": 50, "max": 1500}
    }
  }
}
```

## ⚡ **Quick Tips**

### **Product Creation Flow**
1. **Create basic info** → Get product ID
2. **Set attributes** → Complete replacement
3. **Upload images** → Adds to collection
4. **Publish** → Makes product active

### **Product Update Flow**
1. **Update basic info** → Partial updates supported
2. **Update attributes** → Complete replacement
3. **Add images** → Keeps existing images
4. **Delete images** → Use image ID

### **Search Requirements**
- **Must provide:** `keyword` OR `subcategory_id`
- **Cannot:** Search without entry point
- **Can combine:** Search + category + filters

### **Image Management**
- **Upload:** Adds to existing collection
- **Delete:** Use `/api/images/{imageId}`
- **Security:** Owner/admin validation
- **Formats:** jpeg, png, jpg, gif (max 2MB)

### **Status Management**
- **Sellers:** active ↔ paused
- **Admins:** active, paused, denied, out_of_stock
- **Draft:** Only via publish endpoint

## 🔒 **Authentication**

### **Required Headers**
```bash
Authorization: Bearer {your_token}
Content-Type: application/json  # For JSON requests
Content-Type: multipart/form-data  # For file uploads
```

### **Permissions**
- **Public:** Product discovery only
- **Sellers:** Manage own products
- **Admins:** Manage all products

## 🚨 **Common Errors**

### **400 - Bad Request**
```json
{
  "success": false,
  "message": "Either keyword search or subcategory selection is required"
}
```

### **403 - Unauthorized**
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

### **422 - Validation Error**
```json
{
  "success": false,
  "message": "Attribute ID 15 does not belong to subcategory 'Smartphones'"
}
```

## 🎯 **Best Practices**

### **Frontend Implementation**
- Use same component for create/update
- Handle step progression gracefully
- Validate on client before API calls
- Show progress indicators

### **Error Handling**
- Check response.success before proceeding
- Display validation errors clearly
- Handle network failures gracefully
- Provide retry mechanisms

### **Performance**
- Use pagination for product lists
- Implement debounced search
- Cache category/attribute data
- Optimize image uploads

### **Security**
- Always validate ownership
- Sanitize user inputs
- Handle file uploads securely
- Use HTTPS in production
