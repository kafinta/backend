# Product Deletion Test - Complete Cascade Testing

## 🧪 **Complete Product Deletion Test Scenario**

This test verifies that when a product is deleted, ALL related data is properly cleaned up including variants, images, and attribute relationships.

### **Test Setup: Create Product with Variants**

#### **Step 1: Create Product**
```bash
POST /api/products/basic-info
{
  "name": "Test Product for Deletion",
  "description": "This product will be deleted to test cascade deletion",
  "price": 50.00,
  "subcategory_id": 5,
  "manage_stock": true,
  "stock_quantity": 100
}
# Response: product_id = 999
```

#### **Step 2: Set Product Attributes**
```bash
POST /api/products/999/attributes
{
  "attributes": [
    {"attribute_id": 1, "value_id": 10},  // Color: White
    {"attribute_id": 2, "value_id": 20}   // Size: Medium
  ]
}
```

#### **Step 3: Upload Product Images**
```bash
POST /api/products/999/images
Content-Type: multipart/form-data

images[]: product-main.jpg
images[]: product-side.jpg
# Response: image_ids = [101, 102]
```

#### **Step 4: Create Variant 1**
```bash
POST /api/products/999/variants
{
  "name": "Red Large Variant",
  "price": 55.00,
  "manage_stock": true,
  "stock_quantity": 50,
  "attributes": [
    {"attribute_id": 1, "value_id": 15},  // Color: Red
    {"attribute_id": 2, "value_id": 23}   // Size: Large
  ]
}
# Response: variant_id = 201
```

#### **Step 5: Upload Variant 1 Images**
```bash
POST /api/products/variants/201/images
Content-Type: multipart/form-data

images[]: variant1-red-front.jpg
images[]: variant1-red-back.jpg
# Response: image_ids = [103, 104]
```

#### **Step 6: Create Variant 2**
```bash
POST /api/products/999/variants
{
  "name": "Blue Small Variant",
  "price": 48.00,
  "manage_stock": true,
  "stock_quantity": 30,
  "attributes": [
    {"attribute_id": 1, "value_id": 16},  // Color: Blue
    {"attribute_id": 2, "value_id": 21}   // Size: Small
  ]
}
# Response: variant_id = 202
```

#### **Step 7: Upload Variant 2 Images**
```bash
POST /api/products/variants/202/images
Content-Type: multipart/form-data

images[]: variant2-blue-front.jpg
images[]: variant2-blue-back.jpg
# Response: image_ids = [105, 106]
```

### **Pre-Deletion Verification**

#### **Step 8: Verify Complete Setup**
```bash
# Check product exists
GET /api/products/999
# Expected: Product with attributes and images

# Check variants exist
GET /api/products/999/variants
# Expected: 2 variants with their attributes and images

# Check variant 1 details
GET /api/products/variants/201
# Expected: Variant with attributes and images

# Check variant 2 details
GET /api/products/variants/202
# Expected: Variant with attributes and images
```

#### **Step 9: Verify Database Records (Optional - for backend testing)**
```sql
-- Check product exists
SELECT * FROM products WHERE id = 999;

-- Check variants exist
SELECT * FROM variants WHERE product_id = 999;

-- Check product attributes
SELECT * FROM product_attribute_values WHERE product_id = 999;

-- Check variant attributes
SELECT * FROM variant_attribute_values WHERE variant_id IN (201, 202);

-- Check images
SELECT * FROM images WHERE imageable_type = 'App\\Models\\Product' AND imageable_id = 999;
SELECT * FROM images WHERE imageable_type = 'App\\Models\\Variant' AND imageable_id IN (201, 202);
```

### **Product Deletion Test**

#### **Step 10: Delete the Product**
```bash
DELETE /api/products/999
Authorization: Bearer {owner_token}
# Expected: 200 Success - "Product deleted successfully"
```

### **Post-Deletion Verification**

#### **Step 11: Verify Product is Gone**
```bash
GET /api/products/999
# Expected: 404 Not Found
```

#### **Step 12: Verify Variants are Gone**
```bash
GET /api/products/999/variants
# Expected: 404 Not Found (product doesn't exist)

GET /api/products/variants/201
# Expected: 404 Not Found

GET /api/products/variants/202
# Expected: 404 Not Found
```

#### **Step 13: Verify Images are Gone**
```bash
# Try to access product images
GET /storage/products/999/product-main.jpg
# Expected: 404 Not Found (file deleted)

# Try to access variant images
GET /storage/variants/variant1-red-front.jpg
# Expected: 404 Not Found (file deleted)
```

#### **Step 14: Verify Database Cleanup (Backend Testing)**
```sql
-- Verify product is deleted
SELECT * FROM products WHERE id = 999;
-- Expected: 0 rows

-- Verify variants are deleted (cascade)
SELECT * FROM variants WHERE product_id = 999;
-- Expected: 0 rows

-- Verify product attributes are cleaned up
SELECT * FROM product_attribute_values WHERE product_id = 999;
-- Expected: 0 rows

-- Verify variant attributes are cleaned up (cascade)
SELECT * FROM variant_attribute_values WHERE variant_id IN (201, 202);
-- Expected: 0 rows

-- Verify images are deleted
SELECT * FROM images WHERE imageable_type = 'App\\Models\\Product' AND imageable_id = 999;
-- Expected: 0 rows

SELECT * FROM images WHERE imageable_type = 'App\\Models\\Variant' AND imageable_id IN (201, 202);
-- Expected: 0 rows
```

## 🔍 **What Gets Deleted**

### ✅ **Database Records (Automatic)**
1. **Product record** - Deleted by application
2. **Variant records** - Cascade deleted by foreign key constraint
3. **Product attribute relationships** - Cleaned by application
4. **Variant attribute relationships** - Cascade deleted by foreign key constraint
5. **Image records** - Deleted by application

### ✅ **File System (Manual Cleanup)**
1. **Product image files** - Deleted by application via FileService
2. **Variant image files** - Deleted by application via FileService

## 🚨 **Potential Issues to Watch For**

### **File System Cleanup**
- **Issue:** Files might remain if FileService fails
- **Test:** Check storage directories after deletion
- **Solution:** Verify files are actually removed from disk

### **Transaction Rollback**
- **Issue:** Partial deletion if transaction fails
- **Test:** Simulate failure during deletion process
- **Solution:** Ensure transaction rollback restores all data

### **Permission Errors**
- **Issue:** File deletion might fail due to permissions
- **Test:** Check file system permissions
- **Solution:** Ensure web server has delete permissions

## 📋 **Testing Checklist**

### **Pre-Deletion Setup**
- [ ] Product created successfully
- [ ] Product attributes set
- [ ] Product images uploaded
- [ ] Multiple variants created with unique attributes
- [ ] Variant images uploaded for each variant
- [ ] All records verified in database

### **Deletion Process**
- [ ] Product deletion returns success response
- [ ] No errors in application logs
- [ ] Transaction completes successfully

### **Post-Deletion Verification**
- [ ] Product API returns 404
- [ ] Variant APIs return 404
- [ ] Product image files deleted from storage
- [ ] Variant image files deleted from storage
- [ ] Database records completely removed
- [ ] No orphaned records in any related tables

### **Error Scenarios**
- [ ] Unauthorized deletion returns 403
- [ ] Non-existent product returns 404
- [ ] File deletion errors are handled gracefully

## 🎯 **Expected Results**

### **Successful Deletion**
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Product deleted successfully"
}
```

### **Complete Cleanup**
- ✅ **0 product records** remaining
- ✅ **0 variant records** remaining  
- ✅ **0 attribute relationship records** remaining
- ✅ **0 image records** remaining
- ✅ **0 image files** remaining in storage

### **Error Handling**
```json
{
  "status": "fail",
  "status_code": 403,
  "message": "Unauthorized access"
}
```

## 🔧 **Implementation Details**

### **Enhanced ProductService::deleteProduct()**
```php
public function deleteProduct(Product $product): bool
{
    return DB::transaction(function () use ($product) {
        // 1. Delete variant images and files
        foreach ($product->variants as $variant) {
            foreach ($variant->images as $image) {
                $this->fileService->deleteFile($image->path);
                $image->delete();
            }
            $variant->attributeValues()->detach();
        }

        // 2. Delete product images and files
        foreach ($product->images as $image) {
            $this->fileService->deleteFile($image->path);
            $image->delete();
        }

        // 3. Clean up product attributes
        $product->attributeValues()->detach();
        
        // 4. Delete product (cascades to variants)
        $product->delete();

        return true;
    });
}
```

### **Database Cascade Configuration**
```php
// variants table
$table->foreignId('product_id')->constrained()->onDelete('cascade');

// variant_attribute_values table
$table->foreignId('variant_id')->constrained()->onDelete('cascade');
```

This comprehensive test ensures that product deletion properly cleans up ALL related data, preventing orphaned records and storage bloat! 🎯✨
