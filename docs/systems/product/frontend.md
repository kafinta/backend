# Product Management Frontend Integration Guide

## Table of Contents
- [Overview](#overview)
- [API Service Setup](#api-service-setup)
- [Product Listing & Details](#product-listing--details)
- [Product Management (Seller/Admin)](#product-management-selleradmin)
  - [Create/Update/Delete Product](#createupdatedelete-product)
  - [Attributes & Images](#attributes--images)
  - [Publish & Status](#publish--status)
  - [Seller-Specific Endpoints](#seller-specific-endpoints)
- [Product Variants](#product-variants)
- [Inventory Management](#inventory-management)
- [Error Handling & Response Structure](#error-handling--response-structure)
- [Best Practices](#best-practices)
- [Testing](#testing)

---

## Overview
This guide explains how to integrate the product management system with frontend applications, using the standardized API response structure and all available endpoints.

---

## API Service Setup

```typescript
// product.service.ts
import axios from 'axios';

export class ProductService {
    private static instance: ProductService;
    private baseURL: string;

    private constructor() {
        this.baseURL = '/api/products';
    }

    public static getInstance(): ProductService {
        if (!ProductService.instance) {
            ProductService.instance = new ProductService();
        }
        return ProductService.instance;
    }

    // --- Product Listing ---
    public async list(params: any) {
        const response = await axios.get(this.baseURL, { params });
        return response.data;
    }

    public async get(productId: number) {
        const response = await axios.get(`${this.baseURL}/${productId}`);
        return response.data;
    }

    // --- Product Management ---
    public async createBasicInfo(data: any) {
        const response = await axios.post(`${this.baseURL}/basic-info`, data);
        return response.data;
    }

    public async updateBasicInfo(productId: number, data: any) {
        const response = await axios.put(`${this.baseURL}/${productId}/basic-info`, data);
        return response.data;
    }

    public async delete(productId: number) {
        const response = await axios.delete(`${this.baseURL}/${productId}`);
        return response.data;
    }

    public async addAttributes(productId: number, attributes: any[]) {
        const response = await axios.post(`${this.baseURL}/${productId}/attributes`, { attributes });
        return response.data;
    }

    public async uploadImages(productId: number, images: File[]) {
        const formData = new FormData();
        images.forEach(image => formData.append('images[]', image));
        const response = await axios.post(`${this.baseURL}/${productId}/images`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        return response.data;
    }

    public async publish(productId: number) {
        const response = await axios.post(`${this.baseURL}/${productId}/publish`);
        return response.data;
    }

    public async updateStatus(productId: number, status: string, reason?: string) {
        const response = await axios.patch(`${this.baseURL}/${productId}/status`, { status, reason });
        return response.data;
    }

    // --- Seller-Specific ---
    public async myProducts(params: any) {
        const response = await axios.get(`${this.baseURL}/my-products`, { params });
        return response.data;
    }

    public async myStats() {
        const response = await axios.get(`${this.baseURL}/my-stats`);
        return response.data;
    }

    public async bulkStatus(productIds: number[], status: string) {
        const response = await axios.patch(`${this.baseURL}/bulk-status`, { product_ids: productIds, status });
        return response.data;
    }

    // --- Variants ---
    public async createVariant(productId: number, data: any) {
        const response = await axios.post(`${this.baseURL}/${productId}/variants`, data);
        return response.data;
    }

    public async updateVariant(variantId: number, data: any) {
        const response = await axios.put(`${this.baseURL}/variants/${variantId}`, data);
        return response.data;
    }

    public async deleteVariant(variantId: number) {
        const response = await axios.delete(`${this.baseURL}/variants/${variantId}`);
        return response.data;
    }

    public async uploadVariantImages(variantId: number, images: File[]) {
        const formData = new FormData();
        images.forEach(image => formData.append('images[]', image));
        const response = await axios.post(`${this.baseURL}/variants/${variantId}/images`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        return response.data;
    }

    public async deleteVariantImage(variantId: number, imageId: number) {
        const response = await axios.delete(`${this.baseURL}/variants/${variantId}/images/${imageId}`);
        return response.data;
    }

    public async batchUpdateVariants(variants: any[]) {
        const response = await axios.post(`${this.baseURL}/variants/batch/update`, { variants });
        return response.data;
    }

    // --- Inventory ---
    public async updateStock(productId: number, quantity: number, reason?: string) {
        const response = await axios.patch(`${this.baseURL}/${productId}/stock`, { quantity, reason });
        return response.data;
    }
}
```

---

## Product Listing & Details

### Listing Products
```typescript
const { data, status, message } = await productService.list({ keyword: 'shoes', per_page: 10 });
if (status === 'success') {
    // data.data is the array of products
    // data.meta contains pagination info
}
```

### Get Single Product
```typescript
const { data, status } = await productService.get(productId);
if (status === 'success') {
    // data is the product object
}
```

---

## Product Management (Seller/Admin)

### Create/Update/Delete Product
```typescript
// Create
const res = await productService.createBasicInfo({ name, description, price, subcategory_id, location_id, manage_stock, stock_quantity });
if (res.success) { /* handle success */ }

// Update
const res2 = await productService.updateBasicInfo(productId, { ... });
if (res2.success) { /* handle success */ }

// Delete
const res3 = await productService.delete(productId);
if (res3.success) { /* handle success */ }
```

### Attributes & Images
```typescript
// Add/Update Attributes
await productService.addAttributes(productId, [ { attribute_id, value_id } ]);

// Upload Images
await productService.uploadImages(productId, [file1, file2]);
```

### Publish & Status
```typescript
// Publish
await productService.publish(productId);

// Update Status
await productService.updateStatus(productId, 'active');
```

### Seller-Specific Endpoints
```typescript
// My Products (paginated)
const { data, meta } = (await productService.myProducts({ per_page: 10, status: 'active' })).data;

// My Stats
const stats = (await productService.myStats()).data;

// Bulk Status
await productService.bulkStatus([1,2,3], 'paused');
```

---

## Product Variants
```typescript
// Create Variant
await productService.createVariant(productId, { name, price, attributes: [{ attribute_id, value_id }], manage_stock, stock_quantity });

// Update Variant
await productService.updateVariant(variantId, { ... });

// Delete Variant
await productService.deleteVariant(variantId);

// Upload Variant Images
await productService.uploadVariantImages(variantId, [file1, file2]);

// Delete Variant Image
await productService.deleteVariantImage(variantId, imageId);

// Batch Update Variants
await productService.batchUpdateVariants([ { id: 1, name: 'Red', ... }, { id: 2, name: 'Blue', ... } ]);
```

---

## Inventory Management
```typescript
// Update Stock
await productService.updateStock(productId, 100, 'Restock');
```

---

## Error Handling & Response Structure
All API responses follow this structure:
```json
{
  "success": true,
  "status": "success",
  "status_code": 200,
  "message": "...",
  "data": { /* ... */ }
}
```
- On error:
```json
{
  "success": false,
  "status": "fail",
  "status_code": 422,
  "message": "Validation failed",
  "errors": { "field": ["error message"] }
}
```

### Handling in Frontend
```typescript
try {
    const res = await productService.createBasicInfo(data);
    if (res.success) {
        // Success: show res.message, use res.data
    } else {
        // Failure: show res.message or res.errors
    }
} catch (err) {
    // Network or unexpected error
    showError('An unexpected error occurred');
}
```
- Always check `success` and `status` fields.
- For paginated endpoints, use `data.data` (array) and `data.meta` (pagination info).
- For validation errors, display `errors` object.
- For other errors, display `message`.

---

## Best Practices
- Always validate user input before sending to API.
- Show loading and error states for all API calls.
- Use the `message` field for user feedback.
- Handle paginated data and allow navigation.
- For file uploads, validate file type and size before sending.
- For variant management, check for duplicate attribute combinations before creating.
- Use seller-specific endpoints for dashboard/product management views.
- Use batch/bulk endpoints for mass updates.

---

## Testing
- Write unit tests for all service methods.
- Write integration tests for product creation, update, attribute, image, variant, and inventory flows.
- Mock API responses using the documented structure for frontend tests.

---

## Example: Handling Validation Errors
```typescript
try {
    await productService.createBasicInfo({ ... });
} catch (error) {
    if (error.response && error.response.data && error.response.data.errors) {
        // Show validation errors
        showValidationErrors(error.response.data.errors);
    } else {
        showError('An unexpected error occurred');
    }
}
```

---

## Example: Paginated Product List
```typescript
const { data, meta } = (await productService.myProducts({ per_page: 10 })).data;
// data: array of products
// meta: pagination info (current_page, last_page, per_page, total)
```

---

## Example: UI Feedback
- On success: show `message` from response.
- On error: show `message` or validation `errors`.
- For status changes, show confirmation and update UI accordingly.

---

For full API details, see the [API Documentation](api.md).

---

## Subcategory Attribute Fetching (IMPORTANT)
When creating a product, after the seller selects a subcategory, fetch the attributes for that subcategory using:

```http
GET /api/attributes/subcategory/{subcategoryId}
```

This should be done before showing the attribute selection UI. Example integration:

```typescript
// After subcategory selection
const { data, status } = await axios.get(`/api/attributes/subcategory/${subcategoryId}`);
if (status === 200 && data.success) {
    // data.data is the array of attributes (with values)
    // Show attribute selection UI
}
```

**Why?**
- Each subcategory can have different attributes.
- This ensures sellers only see relevant attributes for the selected subcategory.

---

## Product Object Structure (Updated)
- The product object now always includes `subcategory`, `category`, and `location` as objects (with at least `id` and `name`).
- The fields `subcategory_id`, `category_id`, and `location_id` are no longer present as top-level fields.
- Example:

```json
{
  "id": 1,
  "name": "Test Product",
  "slug": "test-product",
  "description": "A sample product.",
  "price": 100.0,
  "subcategory": { "id": 2, "name": "Shoes" },
  "category": { "id": 1, "name": "Fashion" },
  "location": { "id": 3, "name": "New York" },
  // ... other product fields ...
}
```

**Note:** Always use the `subcategory`, `category`, and `location` objects for display and logic. Do not expect the old *_id fields at the top level.
