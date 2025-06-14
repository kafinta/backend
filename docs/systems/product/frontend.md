# Frontend Integration Guide

## Overview
This guide provides instructions for integrating the product management system with frontend applications. The system uses a step-by-step approach for product creation and management, with support for variants, inventory tracking, and image management.

## Implementation Steps

### 1. Setup Product Service

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

    public async createBasicInfo(data: {
        name: string;
        description: string;
        price: number;
        subcategory_id: number;
        location_id?: number;
        manage_stock: boolean;
        stock_quantity?: number;
    }) {
        try {
            const response = await axios.post(`${this.baseURL}/basic-info`, data);
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async updateBasicInfo(productId: number, data: {
        name?: string;
        description?: string;
        price?: number;
        subcategory_id?: number;
        location_id?: number;
        manage_stock?: boolean;
        stock_quantity?: number;
    }) {
        try {
            const response = await axios.put(`${this.baseURL}/${productId}/basic-info`, data);
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async addAttributes(productId: number, attributes: Array<{
        attribute_id: number;
        value_id: number;
    }>) {
        try {
            const response = await axios.post(`${this.baseURL}/${productId}/attributes`, { attributes });
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async uploadImages(productId: number, images: File[]) {
        try {
            const formData = new FormData();
            images.forEach(image => formData.append('images[]', image));

            const response = await axios.post(`${this.baseURL}/${productId}/images`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async updateStatus(productId: number, status: string, reason?: string) {
        try {
            const response = await axios.patch(`${this.baseURL}/${productId}/status`, {
                status,
                reason
            });
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async createVariant(productId: number, data: {
        name: string;
        price: number;
        attributes: Array<{
            attribute_id: number;
            value_id: number;
        }>;
        manage_stock: boolean;
        stock_quantity?: number;
    }) {
        try {
            const response = await axios.post(`${this.baseURL}/${productId}/variants`, data);
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    public async updateStock(productId: number, quantity: number, reason?: string) {
        try {
            const response = await axios.patch(`${this.baseURL}/${productId}/stock`, {
                quantity,
                reason
            });
            return response.data;
        } catch (error) {
            throw this.handleError(error);
        }
    }

    private handleError(error: any) {
        if (error.response) {
            return error.response.data;
        }
        return new Error('An unexpected error occurred');
    }
}
```

### 2. Create Product Context

```typescript
// ProductContext.tsx
import React, { createContext, useContext, useState, useEffect } from 'react';
import { ProductService } from './product.service';

interface ProductContextType {
    product: any;
    loading: boolean;
    error: any;
    createBasicInfo: (data: any) => Promise<void>;
    updateBasicInfo: (data: any) => Promise<void>;
    addAttributes: (attributes: any[]) => Promise<void>;
    uploadImages: (images: File[]) => Promise<void>;
    updateStatus: (status: string, reason?: string) => Promise<void>;
    createVariant: (data: any) => Promise<void>;
    updateStock: (quantity: number, reason?: string) => Promise<void>;
}

const ProductContext = createContext<ProductContextType | undefined>(undefined);

export const ProductProvider: React.FC = ({ children }) => {
    const [product, setProduct] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const productService = ProductService.getInstance();

    const createBasicInfo = async (data: any) => {
        try {
            setLoading(true);
            const response = await productService.createBasicInfo(data);
            setProduct(response.product);
            setError(null);
        } catch (error) {
            setError(error);
        } finally {
            setLoading(false);
        }
    };

    const updateBasicInfo = async (data: any) => {
        try {
            setLoading(true);
            const response = await productService.updateBasicInfo(product.id, data);
            setProduct(response.product);
            setError(null);
        } catch (error) {
            setError(error);
        } finally {
            setLoading(false);
        }
    };

    const addAttributes = async (attributes: any[]) => {
        try {
            setLoading(true);
            const response = await productService.addAttributes(product.id, attributes);
            setProduct(response.product);
            setError(null);
        } catch (error) {
            setError(error);
        } finally {
            setLoading(false);
        }
    };

    const uploadImages = async (images: File[]) => {
        try {
            setLoading(true);
            const response = await productService.uploadImages(product.id, images);
            setProduct(response.product);
            setError(null);
        } catch (error) {
            setError(error);
        } finally {
            setLoading(false);
        }
    };

    const updateStatus = async (status: string, reason?: string) => {
        try {
            setLoading(true);
            const response = await productService.updateStatus(product.id, status, reason);
            setProduct(response.product);
            setError(null);
        } catch (error) {
            setError(error);
        } finally {
            setLoading(false);
        }
    };

    const createVariant = async (data: any) => {
        try {
            setLoading(true);
            const response = await productService.createVariant(product.id, data);
            setProduct(response.product);
            setError(null);
        } catch (error) {
            setError(error);
        } finally {
            setLoading(false);
        }
    };

    const updateStock = async (quantity: number, reason?: string) => {
        try {
            setLoading(true);
            const response = await productService.updateStock(product.id, quantity, reason);
            setProduct(response.product);
            setError(null);
        } catch (error) {
            setError(error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <ProductContext.Provider value={{
            product,
            loading,
            error,
            createBasicInfo,
            updateBasicInfo,
            addAttributes,
            uploadImages,
            updateStatus,
            createVariant,
            updateStock
        }}>
            {children}
        </ProductContext.Provider>
    );
};

export const useProduct = () => {
    const context = useContext(ProductContext);
    if (context === undefined) {
        throw new Error('useProduct must be used within a ProductProvider');
    }
    return context;
};
```

### 3. Create Product Components

```typescript
// ProductForm.tsx
import React, { useState } from 'react';
import { useProduct } from './ProductContext';

export const ProductForm: React.FC = () => {
    const { createBasicInfo, loading, error } = useProduct();
    const [formData, setFormData] = useState({
        name: '',
        description: '',
        price: '',
        subcategory_id: '',
        location_id: '',
        manage_stock: false,
        stock_quantity: ''
    });

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        await createBasicInfo(formData);
    };

    return (
        <form onSubmit={handleSubmit}>
            <div>
                <label>Name</label>
                <input
                    type="text"
                    value={formData.name}
                    onChange={e => setFormData({ ...formData, name: e.target.value })}
                    required
                />
            </div>
            <div>
                <label>Description</label>
                <textarea
                    value={formData.description}
                    onChange={e => setFormData({ ...formData, description: e.target.value })}
                    required
                />
            </div>
            <div>
                <label>Price</label>
                <input
                    type="number"
                    value={formData.price}
                    onChange={e => setFormData({ ...formData, price: e.target.value })}
                    required
                />
            </div>
            <div>
                <label>Subcategory</label>
                <select
                    value={formData.subcategory_id}
                    onChange={e => setFormData({ ...formData, subcategory_id: e.target.value })}
                    required
                >
                    <option value="">Select a subcategory</option>
                    {/* Add subcategory options */}
                </select>
            </div>
            <div>
                <label>Location</label>
                <select
                    value={formData.location_id}
                    onChange={e => setFormData({ ...formData, location_id: e.target.value })}
                >
                    <option value="">Select a location</option>
                    {/* Add location options */}
                </select>
            </div>
            <div>
                <label>
                    <input
                        type="checkbox"
                        checked={formData.manage_stock}
                        onChange={e => setFormData({ ...formData, manage_stock: e.target.checked })}
                    />
                    Manage Stock
                </label>
            </div>
            {formData.manage_stock && (
                <div>
                    <label>Stock Quantity</label>
                    <input
                        type="number"
                        value={formData.stock_quantity}
                        onChange={e => setFormData({ ...formData, stock_quantity: e.target.value })}
                        required
                    />
                </div>
            )}
            <button type="submit" disabled={loading}>
                {loading ? 'Creating...' : 'Create Product'}
            </button>
            {error && <div className="error">{error.message}</div>}
        </form>
    );
};
```

## Best Practices

### 1. Form Validation
- Implement client-side validation
- Show clear error messages
- Handle server-side validation errors
- Validate file types and sizes

### 2. Error Handling
- Implement proper error handling
- Show user-friendly error messages
- Handle network errors gracefully
- Implement retry mechanism

### 3. User Experience
- Show loading states
- Implement proper form validation
- Provide clear feedback
- Handle session timeouts

### 4. Security
- Validate all inputs
- Secure file uploads
- Implement proper error handling
- Follow security best practices

## Common Issues and Solutions

### 1. Image Upload
```typescript
// Handle image upload
const handleImageUpload = async (files: File[]) => {
    try {
        // Validate file types
        const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
        const invalidFiles = files.filter(file => !validTypes.includes(file.type));
        
        if (invalidFiles.length > 0) {
            throw new Error('Invalid file type. Only JPG, PNG, and GIF are allowed.');
        }

        // Validate file size
        const maxSize = 2 * 1024 * 1024; // 2MB
        const oversizedFiles = files.filter(file => file.size > maxSize);
        
        if (oversizedFiles.length > 0) {
            throw new Error('File size exceeds 2MB limit.');
        }

        await uploadImages(files);
    } catch (error) {
        // Handle error
        showError(error.message);
    }
};
```

### 2. Inventory Management
```typescript
// Handle stock update
const handleStockUpdate = async (quantity: number) => {
    try {
        if (quantity < 0) {
            throw new Error('Stock quantity cannot be negative.');
        }

        await updateStock(quantity, 'Manual adjustment');
    } catch (error) {
        // Handle error
        showError(error.message);
    }
};
```

### 3. Variant Management
```typescript
// Handle variant creation
const handleVariantCreation = async (variantData: any) => {
    try {
        // Validate attribute combinations
        const existingCombinations = product.variants.map(v => 
            v.attributes.map(a => `${a.attribute_id}:${a.value_id}`).sort().join(',')
        );
        
        const newCombination = variantData.attributes
            .map(a => `${a.attribute_id}:${a.value_id}`)
            .sort()
            .join(',');
            
        if (existingCombinations.includes(newCombination)) {
            throw new Error('Variant with these attributes already exists.');
        }

        await createVariant(variantData);
    } catch (error) {
        // Handle error
        showError(error.message);
    }
};
```

## Testing

### 1. Unit Tests
```typescript
// product.service.test.ts
describe('ProductService', () => {
    it('should create basic product info successfully', async () => {
        const productService = ProductService.getInstance();
        const response = await productService.createBasicInfo({
            name: 'Test Product',
            description: 'Test Description',
            price: 99.99,
            subcategory_id: 1,
            manage_stock: true,
            stock_quantity: 100
        });
        expect(response.product).toBeDefined();
    });
});
```

### 2. Integration Tests
```typescript
// product.integration.test.ts
describe('Product Flow', () => {
    it('should complete full product creation flow', async () => {
        // Create basic info
        const basicInfoResponse = await createBasicInfo(data);
        expect(basicInfoResponse.product).toBeDefined();
        
        // Add attributes
        const attributesResponse = await addAttributes(attributes);
        expect(attributesResponse.product.attributes).toBeDefined();
        
        // Upload images
        const imagesResponse = await uploadImages(images);
        expect(imagesResponse.product.images).toBeDefined();
        
        // Create variant
        const variantResponse = await createVariant(variantData);
        expect(variantResponse.variant).toBeDefined();
    });
});
```

## Next Steps
1. Review the [API Documentation](api.md) for endpoint details
2. Check the [Roadmap](roadmap.md) for upcoming features
3. Implement additional security measures
4. Add error tracking and monitoring 