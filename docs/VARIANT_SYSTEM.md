# Product Variant System Documentation

## Overview

The Product Variant System allows products to have multiple variations with unique attribute combinations, individual pricing, stock management, and images. Each variant must have a unique combination of attributes that differs from both the parent product and other variants.

## Core Features

### ✅ **Unique Attribute Combinations**
- No two variants can have identical attribute/value combinations
- Variants cannot duplicate the parent product's attribute combination
- Automatic validation prevents conflicts

### ✅ **Individual Management**
- **Pricing:** Each variant has its own price
- **Stock:** Independent inventory tracking per variant
- **Images:** Separate image collections for each variant
- **Naming:** Custom names for easy identification

### ✅ **Comprehensive Validation**
- Attribute-subcategory relationship validation
- Value-attribute relationship validation
- Uniqueness validation across product and variants
- Stock and pricing validation

## API Endpoints

### **Variant Management (Protected - Owner/Admin)**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/products/{productId}/variants` | List product variants |
| GET | `/api/products/variants/{id}` | Get single variant |
| POST | `/api/products/{productId}/variants` | Create variant |
| PUT | `/api/products/variants/{id}` | Update variant |
| DELETE | `/api/products/variants/{id}` | Delete variant |

### **Variant Image Management**
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/products/variants/{id}/images` | Upload variant images |
| DELETE | `/api/products/variants/{id}/images/{imageId}` | Delete variant image |

### **Batch Operations**
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/products/variants/batch/update` | Update multiple variants |

## Request/Response Examples

### **Create Variant**
```bash
POST /api/products/123/variants
Content-Type: application/json
Authorization: Bearer {token}

{
  "name": "Red Large T-Shirt",
  "price": 29.99,
  "manage_stock": true,
  "stock_quantity": 50,
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

**Response:**
```json
{
  "success": true,
  "message": "Variant created successfully",
  "data": {
    "id": 456,
    "name": "Red Large T-Shirt",
    "price": "29.99",
    "manage_stock": true,
    "stock_quantity": 50,
    "is_in_stock": true,
    "product_id": 123,
    "attributes": [
      {
        "id": 1,
        "name": "Color",
        "value": {
          "id": 15,
          "name": "Red",
          "representation": "#FF0000"
        }
      },
      {
        "id": 2,
        "name": "Size",
        "value": {
          "id": 23,
          "name": "Large",
          "representation": "L"
        }
      }
    ],
    "images": [],
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
  }
}
```

### **Update Variant**
```bash
PUT /api/products/variants/456
Content-Type: application/json
Authorization: Bearer {token}

{
  "name": "Red Large Premium T-Shirt",
  "price": 34.99,
  "stock_quantity": 75
}
```

### **Upload Variant Images**
```bash
POST /api/products/variants/456/images
Content-Type: multipart/form-data
Authorization: Bearer {token}

images[]: red-tshirt-front.jpg
images[]: red-tshirt-back.jpg
```

### **List Product Variants**
```bash
GET /api/products/123/variants
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Variants retrieved successfully",
  "data": [
    {
      "id": 456,
      "name": "Red Large T-Shirt",
      "price": "29.99",
      "manage_stock": true,
      "stock_quantity": 50,
      "is_in_stock": true,
      "attributes": [...],
      "images": [...]
    },
    {
      "id": 457,
      "name": "Blue Medium T-Shirt",
      "price": "27.99",
      "manage_stock": true,
      "stock_quantity": 30,
      "is_in_stock": true,
      "attributes": [...],
      "images": [...]
    }
  ]
}
```

## Validation Rules

### **Create Variant Validation**
```php
[
  'name' => 'required|string|max:255',
  'price' => 'required|numeric|min:0',
  'manage_stock' => 'sometimes|boolean',
  'stock_quantity' => 'sometimes|integer|min:0',
  'attributes' => 'required|array|min:1',
  'attributes.*.attribute_id' => 'required|exists:attributes,id',
  'attributes.*.value_id' => 'required|exists:attribute_values,id',
  'images' => 'sometimes|array',
  'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
]
```

### **Update Variant Validation**
```php
[
  'name' => 'sometimes|string|max:255',
  'price' => 'sometimes|numeric|min:0',
  'manage_stock' => 'sometimes|boolean',
  'stock_quantity' => 'sometimes|integer|min:0',
  'attributes' => 'sometimes|array',
  'attributes.*.attribute_id' => 'required_with:attributes|exists:attributes,id',
  'attributes.*.value_id' => 'required_with:attributes|exists:attribute_values,id'
]
```

## Business Logic Validation

### **Uniqueness Validation**
1. **Against Parent Product:** Variant attributes cannot match parent product attributes
2. **Against Other Variants:** No two variants can have identical attribute combinations
3. **Subcategory Validation:** All attributes must belong to product's subcategory
4. **Value Validation:** All values must belong to their respective attributes

### **Error Examples**
```json
{
  "success": false,
  "message": "Variant cannot have the same attribute combination as the parent product"
}
```

```json
{
  "success": false,
  "message": "Variant cannot have the same attribute combination as variant 'Blue Large T-Shirt'"
}
```

```json
{
  "success": false,
  "message": "Attribute ID 5 does not belong to subcategory 'Clothing'"
}
```

## Stock Management

### **Individual Stock Tracking**
- Each variant manages its own inventory
- Independent `manage_stock` and `stock_quantity` fields
- Stock adjustment methods available

### **Stock Methods**
```php
// Check if variant is in stock
$variant->isInStock($quantity = 1);

// Adjust stock (positive or negative)
$variant->adjustStock($quantity, $reason = 'Manual adjustment');
```

### **Stock Status in API**
```json
{
  "manage_stock": true,
  "stock_quantity": 50,
  "is_in_stock": true
}
```

## Image Management

### **Variant-Specific Images**
- Each variant can have its own image collection
- Images are separate from parent product images
- Standard image validation applies

### **Image Operations**
```bash
# Upload images
POST /api/products/variants/456/images

# Delete specific image
DELETE /api/products/variants/456/images/789
```

## Frontend Implementation

### **Variant Creation Form**
```javascript
const createVariant = async (productId, variantData) => {
  const response = await fetch(`/api/products/${productId}/variants`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      name: variantData.name,
      price: variantData.price,
      manage_stock: variantData.manageStock,
      stock_quantity: variantData.stockQuantity,
      attributes: variantData.selectedAttributes
    })
  });
  
  return response.json();
};
```

### **Variant Display Component**
```javascript
const VariantList = ({ productId }) => {
  const [variants, setVariants] = useState([]);
  
  useEffect(() => {
    fetchVariants();
  }, [productId]);
  
  const fetchVariants = async () => {
    const response = await fetch(`/api/products/${productId}/variants`);
    const result = await response.json();
    setVariants(result.data);
  };
  
  return (
    <div>
      {variants.map(variant => (
        <VariantCard key={variant.id} variant={variant} />
      ))}
    </div>
  );
};
```

### **Attribute Selection**
```javascript
const AttributeSelector = ({ subcategoryId, onAttributeChange }) => {
  const [attributes, setAttributes] = useState([]);
  const [selectedValues, setSelectedValues] = useState({});
  
  const handleValueChange = (attributeId, valueId) => {
    const newSelection = {
      ...selectedValues,
      [attributeId]: valueId
    };
    setSelectedValues(newSelection);
    
    // Convert to API format
    const attributePairs = Object.entries(newSelection).map(([attrId, valId]) => ({
      attribute_id: parseInt(attrId),
      value_id: parseInt(valId)
    }));
    
    onAttributeChange(attributePairs);
  };
  
  return (
    <div>
      {attributes.map(attribute => (
        <AttributeValueSelector
          key={attribute.id}
          attribute={attribute}
          selectedValue={selectedValues[attribute.id]}
          onValueChange={(valueId) => handleValueChange(attribute.id, valueId)}
        />
      ))}
    </div>
  );
};
```

## Testing Scenarios

### **Uniqueness Testing**
```bash
# Create product with attributes
POST /api/products/basic-info
POST /api/products/123/attributes
{
  "attributes": [
    {"attribute_id": 1, "value_id": 15},
    {"attribute_id": 2, "value_id": 23}
  ]
}

# Try to create variant with same attributes (should fail)
POST /api/products/123/variants
{
  "name": "Duplicate Variant",
  "price": 29.99,
  "attributes": [
    {"attribute_id": 1, "value_id": 15},
    {"attribute_id": 2, "value_id": 23}
  ]
}
# Expected: 500 error with uniqueness message

# Create valid variant with different attributes
POST /api/products/123/variants
{
  "name": "Valid Variant",
  "price": 29.99,
  "attributes": [
    {"attribute_id": 1, "value_id": 16},
    {"attribute_id": 2, "value_id": 23}
  ]
}
# Expected: 201 success
```

### **Stock Management Testing**
```bash
# Create variant with stock
POST /api/products/123/variants
{
  "name": "Stock Test Variant",
  "price": 29.99,
  "manage_stock": true,
  "stock_quantity": 100,
  "attributes": [...]
}

# Update stock
PUT /api/products/variants/456
{
  "stock_quantity": 75
}

# Disable stock management
PUT /api/products/variants/456
{
  "manage_stock": false
}
```

## Performance Considerations

### **Database Optimization**
- Indexed variant_attribute_values table for fast lookups
- Efficient uniqueness checking with sorted comparisons
- Eager loading of relationships

### **Validation Efficiency**
- Batch validation of attribute-subcategory relationships
- Optimized uniqueness queries
- Early validation failures to prevent unnecessary processing

## Security Features

### **Access Control**
- Owner/admin validation on all operations
- Product ownership verification for variant operations
- Image ownership validation through parent model

### **Data Integrity**
- Transaction-based operations
- Rollback on validation failures
- Comprehensive error logging

This variant system provides a robust, flexible foundation for managing product variations while maintaining data integrity and providing excellent user experience! 🎯✨
