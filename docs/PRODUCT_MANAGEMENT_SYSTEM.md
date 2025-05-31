# Product Management System Documentation

## Overview

The Product Management System provides a comprehensive, step-by-step approach for creating and updating products in the marketplace. The system is designed with flexibility, security, and ease of use in mind, supporting both creation and update workflows through consistent API endpoints.

## Architecture

### Core Principles
- **Step-by-step approach** - Products are managed through discrete, logical steps
- **Unified create/update flow** - Same endpoints handle both creation and updates
- **Targeted product discovery** - Efficient search and browsing with required entry points
- **Security-first design** - Ownership validation and role-based access control

### Services Used
- **ProductController** - Main product management logic
- **ProductImageService** - Handles image uploads and management
- **ImageController** - Generic image deletion with ownership validation
- **ProductCreationResource** - Standardized API responses

## API Endpoints

### Product Discovery (Public)
```bash
GET /api/products                           # Targeted product listing
GET /api/products/{product}                 # Single product view
```

### Product Management (Protected - Seller/Admin)
```bash
# Step-by-step management
POST /api/products/basic-info               # Create basic info
PUT /api/products/{product}/basic-info      # Update basic info
POST /api/products/{product}/attributes     # Set/update attributes
POST /api/products/{product}/images         # Add images
POST /api/products/{product}/publish        # Publish product

# Product operations
DELETE /api/products/{product}              # Delete product
PATCH /api/products/{product}/status        # Update status

# Seller management
GET /api/products/my-products               # List seller's products
GET /api/products/my-stats                  # Product statistics
PATCH /api/products/bulk-status             # Bulk status updates
```

### Image Management (Protected)
```bash
DELETE /api/images/{imageId}                # Delete any image by ID
```

## Response Format

All endpoints follow the ImprovedController standard response structure:

```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    "product": {...},
    "additional_data": {...}
  }
}
```

## Step-by-Step Workflow

### Step 1: Basic Information

#### Create Basic Info
**Endpoint:** `POST /api/products/basic-info`

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
  "name": "iPhone 15 Pro",
  "description": "Latest iPhone with titanium design",
  "price": 999.99,
  "subcategory_id": 5,
  "location_id": 2,
  "manage_stock": true,
  "stock_quantity": 50
}
```

#### Update Basic Info
**Endpoint:** `PUT /api/products/{product}/basic-info`

**All fields are optional (partial updates supported):**
- `name` (string, max 255)
- `description` (string)
- `price` (numeric, min 0)
- `subcategory_id` (integer) - Triggers attribute resync if changed
- `location_id` (integer, nullable)
- `manage_stock` (boolean)
- `stock_quantity` (integer, min 0)

**Example Request:**
```json
{
  "name": "Updated iPhone 15 Pro",
  "price": 899.99,
  "stock_quantity": 75
}
```

### Step 2: Product Attributes

**Endpoint:** `POST /api/products/{product}/attributes`

**Required Fields:**
- `attributes` (array) - Array of attribute-value pairs
- `attributes.*.attribute_id` (integer) - Must exist and belong to product's subcategory
- `attributes.*.value_id` (integer) - Must exist and belong to the attribute

**Behavior:**
- **Completely replaces** existing attributes
- Validates attribute belongs to product's subcategory
- Validates value belongs to the attribute

**Example Request:**
```json
{
  "attributes": [
    {
      "attribute_id": 1,
      "value_id": 15
    },
    {
      "attribute_id": 2,
      "value_id": 23
    }
  ]
}
```

### Step 3: Product Images

**Endpoint:** `POST /api/products/{product}/images`

**Content-Type:** `multipart/form-data`

**Required Fields:**
- `images[]` (files) - Array of image files

**File Requirements:**
- Formats: jpeg, png, jpg, gif
- Max size: 2MB per file
- Max images per product: Configurable limit

**Behavior:**
- **Adds to existing** image collection
- Does not replace existing images
- Use DELETE endpoint to remove specific images

**Example Request:**
```bash
curl -X POST \
  -H "Authorization: Bearer {token}" \
  -F "images[]=@image1.jpg" \
  -F "images[]=@image2.jpg" \
  http://localhost:8000/api/products/123/images
```

### Step 4: Publish Product

**Endpoint:** `POST /api/products/{product}/publish`

**Requirements:**
- Product must have name, description, price, subcategory
- Product must have at least one attribute set
- Product must have at least one image uploaded

**Behavior:**
- Automatically sets status to 'active'
- Validates all required steps are completed
- Makes product visible in public listings

## Image Management

### Delete Image
**Endpoint:** `DELETE /api/images/{imageId}`

**Security:**
- Validates user owns the parent model (product, variant, etc.)
- Admins can delete any image
- Deletes both file and database record

**Example Request:**
```bash
curl -X DELETE \
  -H "Authorization: Bearer {token}" \
  http://localhost:8000/api/images/45
```

## Product Discovery

### Targeted Product Listing
**Endpoint:** `GET /api/products`

**Required Entry Points (at least one):**
- `keyword` (string) - Search in name and description
- `subcategory_id` (array) - Filter by subcategory IDs

**Additional Filters:**
- `location_id` (array) - Filter by location IDs
- `min_price` / `max_price` (numeric) - Price range
- `attributes` (array) - Filter by attribute value IDs
- `stock_status` (string) - in_stock|out_of_stock
- `sort_by` (string) - name|price|created_at|relevance
- `sort_direction` (string) - asc|desc
- `per_page` (integer, 1-100) - Items per page

**Example Requests:**
```bash
# Search-driven discovery
GET /api/products?keyword=smartphone&location_id[]=2&min_price=500&max_price=1500

# Category-driven discovery
GET /api/products?subcategory_id[]=5&attributes[]=15&attributes[]=23

# Combined approach
GET /api/products?keyword=iPhone&subcategory_id[]=5&location_id[]=2
```

**Response includes:**
- Paginated product list
- Available filters based on current results
- Price range based on filtered results

## Status Management

### Update Product Status
**Endpoint:** `PATCH /api/products/{product}/status`

**Seller Permissions:**
- `active` - Make product visible
- `paused` - Hide product temporarily

**Admin Permissions:**
- `active` - Approve and activate
- `paused` - Temporarily disable
- `denied` - Reject with reason
- `out_of_stock` - Mark as unavailable

**Example Request:**
```json
{
  "status": "denied",
  "reason": "Product images do not meet quality standards"
}
```

## Security Features

### Ownership Validation
- Users can only manage their own products
- Admins can manage any product
- Image deletion validates parent model ownership

### Data Validation
- Attribute-subcategory relationship validation
- Value-attribute relationship validation
- File type and size validation
- Required field validation

### Role-Based Access
- **Sellers:** Create and manage own products
- **Admins:** Full product management capabilities
- **Public:** Read-only access to active products

## Error Handling

### Common Error Responses

**Validation Error (422):**
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

**Unauthorized (403):**
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

**Not Found (404):**
```json
{
  "success": false,
  "message": "Product not found"
}
```

**Business Logic Error (422):**
```json
{
  "success": false,
  "message": "Attribute ID 15 does not belong to subcategory 'Smartphones'"
}
```

## Frontend Implementation Guide

### Unified Product Form Component

```javascript
const ProductForm = ({ productId = null, mode = 'create' }) => {
  const [currentStep, setCurrentStep] = useState(1);
  const [product, setProduct] = useState(null);
  const isUpdate = mode === 'update' && productId;

  // Step 1: Basic Info Handler
  const handleBasicInfo = async (formData) => {
    const url = isUpdate
      ? `/api/products/${productId}/basic-info`
      : '/api/products/basic-info';

    const method = isUpdate ? 'PUT' : 'POST';

    const response = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(formData)
    });

    const result = await response.json();

    if (!isUpdate && result.success) {
      // New product created, switch to update mode
      setProduct(result.data.product);
      setProductId(result.data.product.id);
      setMode('update');
    }

    setCurrentStep(2);
  };

  // Step 2: Attributes Handler
  const handleAttributes = async (attributes) => {
    const response = await fetch(`/api/products/${productId}/attributes`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ attributes })
    });

    if (response.ok) {
      setCurrentStep(3);
    }
  };

  // Step 3: Images Handler
  const handleImageUpload = async (files) => {
    const formData = new FormData();
    files.forEach(file => formData.append('images[]', file));

    await fetch(`/api/products/${productId}/images`, {
      method: 'POST',
      body: formData
    });

    setCurrentStep(4);
  };

  // Image Deletion Handler
  const handleImageDelete = async (imageId) => {
    const response = await fetch(`/api/images/${imageId}`, {
      method: 'DELETE'
    });

    if (response.ok) {
      // Refresh product data or remove from state
      refreshProductImages();
    }
  };

  // Step 4: Publish Handler
  const handlePublish = async () => {
    const response = await fetch(`/api/products/${productId}/publish`, {
      method: 'POST'
    });

    if (response.ok) {
      // Product published successfully
      onPublishSuccess();
    }
  };
};
```

### Independent Step Updates

```javascript
// Update only product name
const updateProductName = async (productId, newName) => {
  await fetch(`/api/products/${productId}/basic-info`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ name: newName })
  });
};

// Update only price
const updateProductPrice = async (productId, newPrice) => {
  await fetch(`/api/products/${productId}/basic-info`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ price: newPrice })
  });
};

// Replace all attributes
const updateProductAttributes = async (productId, attributes) => {
  await fetch(`/api/products/${productId}/attributes`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ attributes })
  });
};
```

### Product Discovery Implementation

```javascript
// Search-driven discovery
const searchProducts = async (keyword, filters = {}) => {
  const params = new URLSearchParams({
    keyword,
    ...filters
  });

  const response = await fetch(`/api/products?${params}`);
  return response.json();
};

// Category-driven discovery
const browseBySubcategory = async (subcategoryIds, filters = {}) => {
  const params = new URLSearchParams(filters);
  subcategoryIds.forEach(id => params.append('subcategory_id[]', id));

  const response = await fetch(`/api/products?${params}`);
  return response.json();
};

// Combined search and category
const searchInCategory = async (keyword, subcategoryIds, filters = {}) => {
  const params = new URLSearchParams({
    keyword,
    ...filters
  });
  subcategoryIds.forEach(id => params.append('subcategory_id[]', id));

  const response = await fetch(`/api/products?${params}`);
  return response.json();
};
```

## Testing Guide

### Unit Testing Scenarios

#### Basic Info Management
```bash
# Test creation
POST /api/products/basic-info
{
  "name": "Test Product",
  "description": "Test description",
  "price": 99.99,
  "subcategory_id": 5,
  "manage_stock": true,
  "stock_quantity": 100
}

# Test update
PUT /api/products/123/basic-info
{
  "name": "Updated Product Name",
  "price": 89.99
}

# Test partial update
PUT /api/products/123/basic-info
{
  "stock_quantity": 150
}
```

#### Attribute Management
```bash
# Test valid attributes
POST /api/products/123/attributes
{
  "attributes": [
    {"attribute_id": 1, "value_id": 15},
    {"attribute_id": 2, "value_id": 23}
  ]
}

# Test invalid attribute (should fail)
POST /api/products/123/attributes
{
  "attributes": [
    {"attribute_id": 999, "value_id": 15}
  ]
}
```

#### Image Management
```bash
# Test image upload
POST /api/products/123/images
Content-Type: multipart/form-data
images[]: file1.jpg, file2.png

# Test image deletion
DELETE /api/images/45

# Test unauthorized deletion (should fail)
DELETE /api/images/46  # Image owned by different user
```

#### Product Discovery
```bash
# Test search requirement
GET /api/products  # Should return 400

# Test valid search
GET /api/products?keyword=smartphone

# Test valid category browsing
GET /api/products?subcategory_id[]=5

# Test combined approach
GET /api/products?keyword=iPhone&subcategory_id[]=5
```

### Integration Testing

#### Complete Product Creation Flow
1. Create basic info → Get product ID
2. Set attributes → Validate attribute sync
3. Upload images → Verify file storage
4. Publish → Confirm status change to 'active'

#### Complete Product Update Flow
1. Update basic info → Verify changes
2. Update attributes → Confirm replacement
3. Add new images → Verify addition to existing
4. Delete specific images → Confirm removal

#### Error Handling Tests
1. Unauthorized access attempts
2. Invalid data validation
3. Business logic violations
4. File upload errors

## Performance Considerations

### Database Optimization
- **Indexed fields:** subcategory_id, user_id, status, price
- **Eager loading:** Relationships loaded efficiently
- **Targeted queries:** No expensive full-table scans

### File Storage
- **Organized structure:** Files stored in product-specific directories
- **Cleanup:** Orphaned files removed on deletion
- **Validation:** File types and sizes validated before storage

### API Response Optimization
- **Relevant metadata only:** No unnecessary filter options
- **Pagination:** Configurable page sizes
- **Resource classes:** Consistent, optimized response format

## Security Best Practices

### Authentication & Authorization
- **Bearer token authentication** for protected endpoints
- **Role-based access control** (Seller/Admin permissions)
- **Ownership validation** on all product operations

### Data Validation
- **Server-side validation** for all inputs
- **Business logic validation** for relationships
- **File validation** for uploads

### Error Information
- **Sanitized error messages** - No sensitive data exposure
- **Appropriate HTTP status codes**
- **Detailed validation errors** for development

## Deployment Considerations

### Environment Configuration
- **File storage paths** - Configurable for different environments
- **Image processing** - Optional optimization pipelines
- **Database connections** - Optimized for production load

### Monitoring & Logging
- **Operation logging** - Track product management activities
- **Error logging** - Capture and monitor failures
- **Performance metrics** - Monitor API response times

### Backup & Recovery
- **Database backups** - Regular product data backups
- **File storage backups** - Image file backup strategies
- **Recovery procedures** - Documented restoration processes

## Summary

The Product Management System provides a comprehensive, secure, and flexible solution for managing products in a marketplace environment. Key benefits include:

### ✅ **Developer Benefits**
- **Consistent API patterns** - Predictable endpoints and responses
- **Clear separation of concerns** - Each step handles specific functionality
- **Comprehensive validation** - Server-side validation with detailed error messages
- **Security by design** - Built-in ownership and role validation

### ✅ **User Experience Benefits**
- **Flexible workflow** - Users can update any step independently
- **Progressive enhancement** - Start with basic info, add details incrementally
- **Efficient discovery** - Targeted search and browsing capabilities
- **Responsive design** - Optimized for both creation and update scenarios

### ✅ **Business Benefits**
- **Scalable architecture** - Handles growth efficiently
- **Performance optimized** - Targeted queries and relevant metadata
- **Maintainable codebase** - Clear structure and documentation
- **Production ready** - Comprehensive error handling and logging

This system successfully balances flexibility, performance, and security while providing an intuitive API for both frontend developers and end users.
