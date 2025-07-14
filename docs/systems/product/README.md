# Product Management System

## Overview
The Product Management System provides a comprehensive, step-by-step approach for creating and managing products in the marketplace. It implements a flexible, secure, and user-friendly system for product management with support for variants, inventory tracking, and image management.

## Quick Start Guide

### 1. Create Basic Product
```php
POST /api/products/basic-info
{
    "name": "string",
    "description": "string",
    "price": "numeric",
    "subcategory_id": "integer",
    "manage_stock": "boolean",
    "stock_quantity": "integer"
}
```

### 2. Add Product Attributes
```php
POST /api/products/{product}/attributes
{
    "attributes": [
        {
            "attribute_id": "integer",
            "value_id": "integer"
        }
    ]
}
```

## Testing with Postman

For general Postman testing guidelines, environment setup, and best practices, please refer to the [main documentation](../README.md#api-testing-with-postman).

### Product-Specific Testing

#### Test Cases
1. **Basic Product Flow**
   - Create basic product info
   - Add attributes
   - Upload images
   - Publish product

2. **Variant Management**
   - Create product variants
   - Update variant details
   - Manage variant inventory
   - Handle variant images

3. **Inventory Management**
   - Track stock levels
   - Update quantities
   - Handle out-of-stock scenarios
   - Bulk stock updates

#### Product-Specific Debug Routes
- `/api/products/my-products`: List seller's products (filters: status, sort_by, sort_direction, stock_status, keyword [name only], category_id, location_id, subcategory_id [if both category and location], fixed page size 10)
- `/api/products/my-stats`: View product statistics
- `/api/products/{product}/status`: Check product status

## Key Features

### Product Management
- Step-by-step product creation
- Flexible update workflow
- Category organization
- Location-based listing
- Status management

### Inventory System
- Optional stock tracking
- Automatic status updates
- Bulk adjustments
- Stock movement logging
- Low stock alerts

### Variant System
- Multiple variations
- Unique combinations
- Individual pricing
- Separate inventory
- Variant images

### Image Management
- Multiple images
- Image optimization
- Secure storage
- Ownership validation
- Variant-specific images

## Dependencies

### Core Dependencies
- Laravel Framework
- Laravel Sanctum
- Image Processing Library
- Storage Service
- Event System

### Development Dependencies
- PHPUnit
- Laravel Dusk
- Postman Collection

## Configuration

### Environment Variables
```env
PRODUCT_IMAGE_MAX_SIZE=2048
PRODUCT_IMAGE_ALLOWED_TYPES=jpg,jpeg,png,gif
PRODUCT_MAX_IMAGES=10
PRODUCT_LOW_STOCK_THRESHOLD=10
```

### Security Settings
- Image size limit: 2MB
- Allowed image types: jpg, jpeg, png, gif
- Maximum images per product: 10
- Low stock threshold: 10 units

## Best Practices

1. **Product Management**
   - Validate all inputs
   - Handle file uploads securely
   - Implement proper error handling
   - Use appropriate status codes

2. **Inventory Management**
   - Track stock movements
   - Implement low stock alerts
   - Handle concurrent updates
   - Maintain audit logs

3. **Testing**
   - Test all product flows
   - Verify inventory updates
   - Check image handling
   - Validate variant creation

## Common Issues and Solutions

1. **Image Upload**
   - Solution: Validate file types
   - Solution: Check file size
   - Solution: Implement retry mechanism

2. **Inventory Updates**
   - Solution: Use transactions
   - Solution: Implement locking
   - Solution: Handle conflicts

3. **Variant Management**
   - Solution: Validate combinations
   - Solution: Check uniqueness
   - Solution: Handle dependencies

## Next Steps
1. Review the [API Documentation](api.md) for detailed endpoint information
2. Check the [Frontend Integration Guide](frontend.md) for implementation details
3. See the [Roadmap](roadmap.md) for planned features and improvements

## Product Status Workflow

The system supports multiple product statuses:

1. **Draft**
   - Initial state
   - Not visible to customers
   - Can be edited
   - Requires completion

2. **Active**
   - Visible to customers
   - Can be purchased
   - Can be paused
   - Monitored for compliance

3. **Paused**
   - Temporarily hidden
   - Can be reactivated
   - Maintains data
   - Quick status change

4. **Denied**
   - Failed compliance
   - Requires admin review
   - Needs correction
   - Can be appealed

5. **Out of Stock**
   - No inventory
   - Auto-updated
   - Can be restocked
   - Maintains visibility

### Status Transitions
```
draft → active (published)
  ↓       ↓
denied  paused
  ↓       ↓
  └───────┘
```

### Development Mode
In development mode, status changes are logged:
- View status history at `GET /api/products/{product}/status-history`
- Simulate status change at `POST /api/products/{product}/simulate-status`
- Clear status history at `DELETE /api/products/{product}/status-history` 

## Product Discounts

### Overview
The Product Discount System allows sellers to set automatic, product-specific discounts (sales) on their products. Discounts can be set during product creation, updated later, or managed via dedicated endpoints. Discounts are automatically applied in the cart and reflected in all relevant API responses.

### Supported Discount Types
- **Percent**: e.g., 20% off
- **Fixed**: e.g., $5 off

### How Sellers Set Discounts
- **During Product Creation (Step 1):**
  - Sellers can include discount fields when creating a product.
- **During Product Update:**
  - Sellers can update discount fields as part of the product update flow.
- **Standalone Endpoints:**
  - Sellers can update or remove a product's discount using dedicated endpoints:
    - `PUT /products/{product}/discount` (set/update)
    - `DELETE /products/{product}/discount` (remove)

### Discount Fields
- `discount_type`: `percent` or `fixed`
- `discount_value`: decimal, the value of the discount
- `discount_start`: datetime, when the discount becomes active (optional)
- `discount_end`: datetime, when the discount expires (optional)

### Permissions & Validation
- Only the product owner (seller) or an admin can set, update, or remove discounts.
- Validation ensures:
  - Percent discounts are between 0 and 100.
  - Fixed discounts do not exceed the product price.
  - End date is not before start date.

### How Discounts Are Reflected
- **Product API Responses:**
  - Discount fields and the calculated discounted price are included in all product API responses.
- **Cart API Responses:**
  - If a product in the cart has an active discount, the discounted price is used for calculations and shown in the cart API response.
- **Order/Checkout:**
  - Discounts are applied at checkout and reflected in order totals.

### Example Use Cases
- Sellers can run sales on specific products for a limited time.
- Buyers see discounted prices automatically in product listings and their cart. 