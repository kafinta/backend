# Variant System Testing Guide

## 🧪 **Complete Testing Scenarios**

### **Scenario 1: Basic Variant Creation**

#### **Step 1: Create Product**
```bash
POST /api/products/basic-info
{
  "name": "Basic T-Shirt",
  "description": "A comfortable cotton t-shirt",
  "price": 25.00,
  "subcategory_id": 5,
  "manage_stock": true,
  "stock_quantity": 100
}
# Response: product_id = 123
```

#### **Step 2: Set Product Attributes**
```bash
POST /api/products/123/attributes
{
  "attributes": [
    {"attribute_id": 1, "value_id": 10},  // Color: White
    {"attribute_id": 2, "value_id": 20}   // Size: Medium
  ]
}
```

#### **Step 3: Create Valid Variant**
```bash
POST /api/products/123/variants
{
  "name": "Red Large T-Shirt",
  "price": 29.99,
  "manage_stock": true,
  "stock_quantity": 50,
  "attributes": [
    {"attribute_id": 1, "value_id": 15},  // Color: Red
    {"attribute_id": 2, "value_id": 23}   // Size: Large
  ]
}
# Expected: 201 Created
```

#### **Step 4: Try Invalid Variant (Duplicate Product)**
```bash
POST /api/products/123/variants
{
  "name": "Duplicate Product Variant",
  "price": 27.99,
  "attributes": [
    {"attribute_id": 1, "value_id": 10},  // Color: White (same as product)
    {"attribute_id": 2, "value_id": 20}   // Size: Medium (same as product)
  ]
}
# Expected: 500 Error - "Variant cannot have the same attribute combination as the parent product"
```

#### **Step 5: Try Invalid Variant (Duplicate Variant)**
```bash
POST /api/products/123/variants
{
  "name": "Duplicate Variant",
  "price": 31.99,
  "attributes": [
    {"attribute_id": 1, "value_id": 15},  // Color: Red (same as first variant)
    {"attribute_id": 2, "value_id": 23}   // Size: Large (same as first variant)
  ]
}
# Expected: 500 Error - "Variant cannot have the same attribute combination as variant 'Red Large T-Shirt'"
```

---

### **Scenario 2: Variant Management**

#### **Step 1: Create Multiple Valid Variants**
```bash
# Variant 1: Blue Small
POST /api/products/123/variants
{
  "name": "Blue Small T-Shirt",
  "price": 24.99,
  "manage_stock": true,
  "stock_quantity": 30,
  "attributes": [
    {"attribute_id": 1, "value_id": 16},  // Color: Blue
    {"attribute_id": 2, "value_id": 21}   // Size: Small
  ]
}

# Variant 2: Green Extra Large
POST /api/products/123/variants
{
  "name": "Green XL T-Shirt",
  "price": 32.99,
  "manage_stock": true,
  "stock_quantity": 25,
  "attributes": [
    {"attribute_id": 1, "value_id": 17},  // Color: Green
    {"attribute_id": 2, "value_id": 24}   // Size: Extra Large
  ]
}
```

#### **Step 2: List All Variants**
```bash
GET /api/products/123/variants
# Expected: Array of all variants with their attributes, pricing, and stock info
```

#### **Step 3: Update Variant**
```bash
PUT /api/products/variants/456
{
  "name": "Red Large Premium T-Shirt",
  "price": 34.99,
  "stock_quantity": 75
}
# Expected: 200 Updated
```

#### **Step 4: Update Variant Attributes**
```bash
PUT /api/products/variants/456
{
  "attributes": [
    {"attribute_id": 1, "value_id": 18},  // Color: Black
    {"attribute_id": 2, "value_id": 23}   // Size: Large (keep same)
  ]
}
# Expected: 200 Updated (now Black Large instead of Red Large)
```

---

### **Scenario 3: Image Management**

#### **Step 1: Upload Variant Images**
```bash
POST /api/products/variants/456/images
Content-Type: multipart/form-data

images[]: red-tshirt-front.jpg
images[]: red-tshirt-back.jpg
images[]: red-tshirt-detail.jpg
# Expected: 200 Success with uploaded image details
```

#### **Step 2: Get Variant with Images**
```bash
GET /api/products/variants/456
# Expected: Variant data including images array
```

#### **Step 3: Delete Specific Image**
```bash
DELETE /api/products/variants/456/images/789
# Expected: 200 Success
```

---

### **Scenario 4: Stock Management**

#### **Step 1: Check Stock Status**
```bash
GET /api/products/variants/456
# Response should include:
{
  "manage_stock": true,
  "stock_quantity": 75,
  "is_in_stock": true
}
```

#### **Step 2: Update Stock**
```bash
PUT /api/products/variants/456
{
  "stock_quantity": 5
}
# Expected: Stock updated to 5
```

#### **Step 3: Disable Stock Management**
```bash
PUT /api/products/variants/456
{
  "manage_stock": false
}
# Expected: Stock management disabled, is_in_stock should be true regardless of quantity
```

---

### **Scenario 5: Error Handling**

#### **Step 1: Invalid Attribute for Subcategory**
```bash
POST /api/products/123/variants
{
  "name": "Invalid Attribute Variant",
  "price": 29.99,
  "attributes": [
    {"attribute_id": 99, "value_id": 15}  // Attribute 99 doesn't belong to subcategory
  ]
}
# Expected: 500 Error - "Attribute ID 99 does not belong to subcategory 'Clothing'"
```

#### **Step 2: Invalid Value for Attribute**
```bash
POST /api/products/123/variants
{
  "name": "Invalid Value Variant",
  "price": 29.99,
  "attributes": [
    {"attribute_id": 1, "value_id": 999}  // Value 999 doesn't belong to attribute 1
  ]
}
# Expected: 500 Error - "Value ID 999 does not belong to attribute ID 1"
```

#### **Step 3: Missing Required Fields**
```bash
POST /api/products/123/variants
{
  "price": 29.99,
  "attributes": [
    {"attribute_id": 1, "value_id": 15}
  ]
}
# Expected: 422 Validation Error - "name field is required"
```

#### **Step 4: Negative Price**
```bash
POST /api/products/123/variants
{
  "name": "Negative Price Variant",
  "price": -10.00,
  "attributes": [
    {"attribute_id": 1, "value_id": 15}
  ]
}
# Expected: 422 Validation Error - "price must be at least 0"
```

---

### **Scenario 6: Batch Operations**

#### **Step 1: Batch Update Multiple Variants**
```bash
POST /api/products/variants/batch/update
{
  "variants": [
    {
      "id": 456,
      "name": "Updated Red Large T-Shirt",
      "price": 35.99
    },
    {
      "id": 457,
      "name": "Updated Blue Small T-Shirt",
      "price": 26.99
    }
  ]
}
# Expected: 200 Success with count of updated variants
```

---

### **Scenario 7: Authorization Testing**

#### **Step 1: Unauthorized Access**
```bash
# Without authentication token
POST /api/products/123/variants
{
  "name": "Unauthorized Variant",
  "price": 29.99,
  "attributes": [...]
}
# Expected: 401 Unauthorized
```

#### **Step 2: Wrong Owner**
```bash
# With token from different user
POST /api/products/123/variants
{
  "name": "Wrong Owner Variant",
  "price": 29.99,
  "attributes": [...]
}
# Expected: 403 Forbidden
```

---

## 🔍 **Testing Checklist**

### **✅ Uniqueness Validation**
- [ ] Variant cannot duplicate parent product attributes
- [ ] Variant cannot duplicate other variant attributes
- [ ] Different attribute combinations are allowed

### **✅ Data Validation**
- [ ] Required fields validation
- [ ] Price validation (non-negative)
- [ ] Stock quantity validation (non-negative)
- [ ] Attribute-subcategory relationship validation
- [ ] Value-attribute relationship validation

### **✅ CRUD Operations**
- [ ] Create variant successfully
- [ ] Read single variant
- [ ] Read all product variants
- [ ] Update variant fields
- [ ] Update variant attributes
- [ ] Delete variant

### **✅ Image Management**
- [ ] Upload variant images
- [ ] View variant with images
- [ ] Delete specific variant image

### **✅ Stock Management**
- [ ] Create variant with stock settings
- [ ] Update stock quantity
- [ ] Toggle stock management
- [ ] Check stock status in responses

### **✅ Error Handling**
- [ ] Validation errors return 422
- [ ] Business logic errors return 500
- [ ] Authorization errors return 401/403
- [ ] Not found errors return 404

### **✅ Security**
- [ ] Authentication required for all operations
- [ ] Owner/admin authorization enforced
- [ ] No data leakage in error messages

---

## 📊 **Expected Results Summary**

### **Successful Operations**
- Variant creation with unique attributes: **201 Created**
- Variant updates: **200 OK**
- Image uploads: **200 OK**
- Variant listing: **200 OK**

### **Expected Errors**
- Duplicate attribute combinations: **500 Internal Server Error**
- Invalid attribute/value relationships: **500 Internal Server Error**
- Validation failures: **422 Unprocessable Entity**
- Unauthorized access: **401/403 Forbidden**

### **Response Structure**
All successful responses follow the ImprovedController format:
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    // Variant data or array of variants
  }
}
```

This comprehensive testing guide ensures the variant system works correctly with proper validation, security, and error handling! 🎯✨
