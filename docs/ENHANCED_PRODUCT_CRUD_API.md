# Targeted Product Discovery API Documentation

## Overview

The product discovery system uses a targeted approach with **two entry points only**: keyword search or subcategory selection. This eliminates the inefficient "show all products" approach while providing focused, relevant results.

## Response Format

All endpoints return responses in this format:
```json
{
  "status": "success|fail|error",
  "status_code": 200,
  "message": "Success message",
  "data": {
    // Response data
  }
}
```

## Product Discovery Endpoint

### **Targeted Product Discovery**
**Endpoint:** `GET /api/products`

**Required Entry Points (at least one must be provided):**
- ✅ **Keyword search** - User searches for something specific
- ✅ **Subcategory selection** - User browses by subcategory
- ✅ **Combined approach** - Search within a specific subcategory

**Additional Filtering Options:**
- ✅ **Location-based filtering**
- ✅ **Dynamic attribute filtering** (when subcategory selected)
- ✅ **Price range and stock filtering**
- ✅ **Sorting and pagination**

### **Query Parameters:**
- `per_page` (integer, 1-100) - Items per page (default: 15)
- `keyword` (string) - Search in name and description
- `category_id` (integer) - Filter by category
- `subcategory_id` (array|integer) - Filter by subcategory(ies)
- `location_id` (array|integer) - Filter by location(s)
- `min_price` (numeric) - Minimum price filter
- `max_price` (numeric) - Maximum price filter
- `is_featured` (boolean) - Filter featured products
- `seller_id` (integer) - Filter by seller
- `sort_by` (string) - Sort field: name|price|created_at|updated_at|relevance
- `sort_direction` (string) - Sort direction: asc|desc
- `stock_status` (string) - Filter by stock availability: in_stock|out_of_stock
- `attributes` (array) - Filter by attribute value IDs

**Security Note:** Only `active` products are shown to public users. Internal status filtering is not available.

### **Targeted Response with Relevant Metadata**

The endpoint returns products and only relevant filter metadata:

```json
{
  "status": "success",
  "status_code": 200,
  "message": "Products retrieved successfully",
  "data": {
    "products": {
      "current_page": 1,
      "data": [
        {
          "id": 1,
          "name": "iPhone 15",
          "price": "999.99",
          "stock_quantity": 50,
          "manage_stock": true,
          "seller_name": "Tech Store Inc",
          "subcategory": {...},
          "images": [...],
          "attributes": [...]
        }
      ],
      "pagination": {...}
    },
    "filters": {
      "available_locations": [...],        // Always available
      "available_attributes": [...],       // Only when subcategory selected
      "price_range": {"min": 50, "max": 1500}  // Based on current results
    }
  }
}
```

## Entry Point Examples

### 1. Search-Driven Discovery
```bash
# User searches for something
GET /api/products?keyword=smartphone

# Search with additional filters
GET /api/products?keyword=iPhone&location_id=2&min_price=500&max_price=1500&stock_status=in_stock&sort_by=relevance
```

### 2. Subcategory-Driven Discovery
```bash
# User selects a subcategory (primary navigation method)
GET /api/products?subcategory_id=5

# Subcategory with attribute filtering
GET /api/products?subcategory_id=5&attributes[]=15&attributes[]=23&location_id=2
```

### 3. Multi-Subcategory Browsing
```bash
# Browse multiple related subcategories
GET /api/products?subcategory_id[]=5&subcategory_id[]=6&attributes[]=15
```

### 4. **Combined Search + Subcategory (Powerful!)**
```bash
# Search within a specific subcategory - best of both worlds
GET /api/products?keyword=wireless&subcategory_id=5&location_id=2&min_price=100&max_price=500

# Search for "iPhone" within "Smartphones" subcategory
GET /api/products?keyword=iPhone&subcategory_id=5&attributes[]=15&attributes[]=23

# Search across multiple subcategories
GET /api/products?keyword=gaming&subcategory_id[]=5&subcategory_id[]=8&location_id=2
```

### 5. Frontend Navigation Flow
```bash
# User clicks "Electronics > Smartphones" in navigation
GET /api/products?subcategory_id=5

# Then searches within that subcategory
GET /api/products?keyword=Samsung&subcategory_id=5

# Then applies attribute filters
GET /api/products?keyword=Samsung&subcategory_id=5&attributes[]=15&attributes[]=23&location_id=2
```

### 6. Invalid Requests (Will Return 400 Error)
```bash
# No entry point provided
GET /api/products

# Empty keyword
GET /api/products?keyword=

# Category without subcategory (categories are just UI containers)
GET /api/products?category_id=1
```

## Single Product View
**Endpoint:** `GET /api/products/{product}`

**Response:**
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Product retrieved successfully",
  "data": {
    "product": {
      "id": 1,
      "name": "iPhone 15",
      "description": "Latest iPhone model",
      "price": "999.99",
      "status": "active",
      "stock_quantity": 50,
      "manage_stock": true,
      "seller_name": "Tech Store Inc",
      "subcategory": {...},
      "images": [...],
      "attributes": [
        {
          "attribute_name": "Color",
          "value_name": "Space Gray",
          "representation": {"hex": "#333333"}
        }
      ]
    }
  }
}
```

## Protected Endpoints (Authentication Required)

### 5. Create Product (Direct Method)
**Endpoint:** `POST /api/products`
**Auth:** Bearer token, Seller/Admin role

**Request Body:**
```json
{
  "name": "iPhone 15",
  "description": "Latest iPhone model",
  "price": 999.99,
  "subcategory_id": 1,
  "location_id": 1,
  "manage_stock": true,
  "stock_quantity": 100,
  "attributes": [15, 23, 41],
  "images": [file1, file2, file3]
}
```

### 6. Update Product
**Endpoint:** `PUT /api/products/{product}`
**Auth:** Bearer token, Owner/Admin

**Request Body:** Same as create (all fields optional)

### 7. Delete Product
**Endpoint:** `DELETE /api/products/{product}`
**Auth:** Bearer token, Owner/Admin

**Response:**
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Product deleted successfully"
}
```

### 8. Update Product Status
**Endpoint:** `PATCH /api/products/{product}/status`
**Auth:** Bearer token, Owner/Admin

**Seller Permissions:** Can change between `active` and `paused`
**Admin Permissions:** Can set any status including `denied` with reason

**Request Body:**
```json
{
  "status": "paused"
}
```

**Admin Denial:**
```json
{
  "status": "denied",
  "reason": "Product images do not meet quality standards"
}
```

## Seller-Specific Endpoints

### 9. Get My Products
**Endpoint:** `GET /api/products/my-products`
**Auth:** Bearer token, Seller/Admin role

**Query Parameters:**
- `per_page` (integer) - Items per page
- `status` (string) - Filter by status
- `sort_by` (string) - Sort field
- `sort_direction` (string) - Sort direction
- `stock_status` (string) - Filter by stock status

**Example Request:**
```bash
GET /api/products/my-products?status=active&stock_status=low_stock&sort_by=stock_quantity&sort_direction=asc
```

### 10. Get My Product Statistics
**Endpoint:** `GET /api/products/my-stats`
**Auth:** Bearer token, Seller/Admin role

**Response:**
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Product statistics retrieved successfully",
  "data": {
    "total_products": 25,
    "active_products": 20,
    "draft_products": 3,
    "paused_products": 1,
    "denied_products": 1,
    "out_of_stock_products": 2,
    "products_with_stock": 23,
    "low_stock_products": 5,
    "zero_stock_products": 2,
    "avg_stock_quantity": 47.5,
    "total_stock_quantity": 1425,
    "completion_rate": 80.0,
    "stock_health": 78.3
  }
}
```

#### Statistics Explanation:

**Basic Counts:**
- `total_products` - Total number of products owned by seller
- `active_products` - Products with status 'active' (publicly visible)
- `draft_products` - Products with status 'draft' (being created)
- `paused_products` - Products with status 'paused' (seller paused)
- `denied_products` - Products with status 'denied' (admin rejected)
- `out_of_stock_products` - Products with status 'out_of_stock' (auto-set when stock = 0)

**Stock Analytics:**
- `products_with_stock` - Products either not managing stock OR have stock > 0
- `low_stock_products` - Products managing stock with quantity 1-5
- `zero_stock_products` - Products managing stock with quantity = 0
- `avg_stock_quantity` - Average stock quantity across products that manage stock
- `total_stock_quantity` - Sum of all stock quantities

**Calculated Metrics:**
- `completion_rate` - Percentage of products that are active (active/total * 100)
- `stock_health` - Percentage of stocked products that are NOT low stock

**Performance Note:** All statistics are calculated in a single optimized database query using conditional aggregation.

### 11. Bulk Update Product Status
**Endpoint:** `PATCH /api/products/bulk-status`
**Auth:** Bearer token, Seller/Admin role

**Request Body:**
```json
{
  "product_ids": [1, 2, 3, 4, 5],
  "status": "paused"
}
```

**Response:**
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Products status updated successfully",
  "data": {
    "updated_count": 5,
    "requested_count": 5,
    "status": "paused"
  }
}
```

## Advanced Filtering Examples

### Filter by Multiple Attributes
```bash
GET /api/products?attributes[]=15&attributes[]=23&attributes[]=41
# Returns products that have ALL specified attribute values
```

### Stock Status Filtering
```bash
# In stock products (either not managing stock or have stock > 0)
GET /api/products?stock_status=in_stock

# Out of stock products (managing stock and stock = 0)
GET /api/products?stock_status=out_of_stock

# Note: low_stock filtering is only available for authenticated sellers
```

### Relevance-Based Search
```bash
# Search with relevance sorting (prioritizes exact matches in name)
GET /api/products/search?keyword=iPhone&sort_by=relevance
```

### Complex Filtering
```bash
# Electronics category, price range, in stock, featured, sorted by price
GET /api/products?category_id=1&min_price=100&max_price=1000&stock_status=in_stock&is_featured=true&sort_by=price&sort_direction=asc

# Note: All public endpoints automatically filter to show only 'active' products
```

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

**Authorization Errors (403):**
```json
{
  "status": "fail",
  "status_code": 403,
  "message": "Unauthorized"
}
```

**Not Found Errors (404):**
```json
{
  "status": "fail",
  "status_code": 404,
  "message": "Product not found"
}
```

## Testing Checklist

### Public Endpoints (No Auth Required)
- [ ] `GET /api/products` - List products with various filters
- [ ] `GET /api/products/search` - Search with keyword and relevance sorting
- [ ] `GET /api/products/category/{category}` - Products by category
- [ ] `GET /api/products/{product}` - Get single product details
- [ ] Test filtering: attributes, stock status, price range, location
- [ ] Test sorting: name, price, created_at, stock_quantity, relevance
- [ ] Test pagination with different per_page values

### Protected Endpoints (Auth Required)
- [ ] `POST /api/products` - Create product (direct method)
- [ ] `PUT /api/products/{product}` - Update product
- [ ] `DELETE /api/products/{product}` - Delete product
- [ ] `PATCH /api/products/{product}/status` - Update status
- [ ] Test authorization (owner vs non-owner vs admin)

### Seller-Specific Endpoints
- [ ] `GET /api/products/my-products` - Get seller's products
- [ ] `GET /api/products/my-stats` - Get product statistics
- [ ] `PATCH /api/products/bulk-status` - Bulk status update
- [ ] Test seller ownership validation

### Error Handling & Edge Cases
- [ ] Invalid filters and parameters
- [ ] Unauthorized access attempts
- [ ] Non-existent product IDs
- [ ] Validation errors for create/update
- [ ] Bulk operations with mixed ownership

## Postman Collection Examples

### 1. Basic Product Listing
```bash
GET {{base_url}}/api/products?per_page=10&sort_by=price&sort_direction=asc
```

### 2. Advanced Search
```bash
GET {{base_url}}/api/products/search?keyword=phone&category_id=1&min_price=100&max_price=1000&stock_status=in_stock&sort_by=relevance
```

### 3. Attribute Filtering
```bash
GET {{base_url}}/api/products?attributes[]=15&attributes[]=23&subcategory_id=1
```

### 4. Seller Products
```bash
GET {{base_url}}/api/products/my-products?status=active&stock_status=low_stock
Authorization: Bearer {{token}}
```

### 5. Product Statistics
```bash
GET {{base_url}}/api/products/my-stats
Authorization: Bearer {{token}}
```

### 6. Bulk Status Update
```bash
PATCH {{base_url}}/api/products/bulk-status
Authorization: Bearer {{token}}
Content-Type: application/json

{
  "product_ids": [1, 2, 3],
  "status": "paused"
}
```

## Performance Considerations

- **Pagination**: Always use reasonable per_page limits (max 100)
- **Indexing**: Database indexes are optimized for filtering and sorting
- **Caching**: Consider implementing Redis caching for popular searches
- **Eager Loading**: Relationships are properly eager loaded to avoid N+1 queries

## Next Steps

1. **Test all endpoints** in Postman using the examples above
2. **Verify response formats** match the documented structure
3. **Test edge cases** and error scenarios
4. **Performance test** with larger datasets
5. **Frontend integration** once backend is validated
