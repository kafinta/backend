# Top Products & Featured Products API Documentation

## Overview

Two simple, dedicated endpoints for displaying curated products on the home page:
- **Top Products** - Best-selling products (10 items)
- **Featured Products** - Manually featured products (10 items)

Both endpoints are optimized for home page performance and require no query parameters.

---

## Endpoints

### 1. Top Products
**GET /api/products/top**

Retrieve the 10 best-selling products, ranked by sales volume.

**Authentication:** Not required (public endpoint)

**Response (200):**
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Top products retrieved successfully",
  "data": {
    "products": [
      {
        "id": 1,
        "name": "Premium Wireless Headphones",
        "slug": "premium-wireless-headphones",
        "description": "High-quality wireless headphones...",
        "price": "99.99",
        "is_featured": true,
        "status": "active",
        "sales_count": 245,
        "average_rating": 4.5,
        "review_count": 42,
        "category": {
          "id": 1,
          "name": "Electronics"
        },
        "subcategory": {
          "id": 5,
          "name": "Audio"
        },
        "images": [
          {
            "id": 1,
            "url": "https://example.com/image.jpg"
          }
        ],
        "user": {
          "id": 10,
          "seller": {
            "id": 1,
            "business_name": "TechStore"
          }
        }
      }
      // ... 9 more products
    ],
    "count": 10
  }
}
```

---

### 2. Featured Products
**GET /api/products/featured**

Retrieve the 10 featured products, ranked by sales volume.

**Authentication:** Not required (public endpoint)

**Response (200):**
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Featured products retrieved successfully",
  "data": {
    "products": [
      {
        "id": 5,
        "name": "Premium Wireless Headphones",
        "slug": "premium-wireless-headphones",
        "description": "High-quality wireless headphones...",
        "price": "99.99",
        "is_featured": true,
        "status": "active",
        "sales_count": 245,
        "average_rating": 4.5,
        "review_count": 42,
        "category": { ... },
        "subcategory": { ... },
        "images": [ ... ],
        "user": { ... }
      }
      // ... 9 more products
    ],
    "count": 10
  }
}
```

---

## Product Metrics

Each product includes the following metrics:

- **sales_count**: Total quantity sold (excludes cancelled orders)
- **average_rating**: Average rating from 1-5 (calculated from 4 dimensions)
- **review_count**: Number of approved reviews

---

## Error Responses

### Server Error (500)
```json
{
  "status": "error",
  "status_code": 500,
  "message": "Error retrieving top products: [error details]"
}
```

---

## Use Cases

### Home Page Hero Section
Display top 10 best-selling products:
```
GET /api/products/top
```

### Featured Products Carousel
Show featured products:
```
GET /api/products/featured
```

---

## Performance Notes

- Both endpoints return exactly 10 products
- No query parameters needed - simple and fast
- Results are optimized for home page performance
- Products are sorted by sales volume (secondary sort by creation date)
- Average rating calculation includes 4 dimensions:
  - Value for money
  - True to description
  - Product quality
  - Shipping experience

---

## Frontend Integration Example

```javascript
// Fetch top products
async function getTopProducts() {
  try {
    const response = await fetch('/api/products/top')
    const data = await response.json()

    if (data.status === 'success') {
      return data.data.products
    } else {
      console.error('Error:', data.message)
    }
  } catch (error) {
    console.error('Failed to fetch top products:', error)
  }
}

// Fetch featured products
async function getFeaturedProducts() {
  try {
    const response = await fetch('/api/products/featured')
    const data = await response.json()

    if (data.status === 'success') {
      return data.data.products
    } else {
      console.error('Error:', data.message)
    }
  } catch (error) {
    console.error('Failed to fetch featured products:', error)
  }
}

// Usage in home page
const topProducts = await getTopProducts()
const featuredProducts = await getFeaturedProducts()
```

---

## Vue/Nuxt Example

```vue
<template>
  <div>
    <!-- Top Products Section -->
    <section class="top-products">
      <h2>Best Sellers</h2>
      <div class="products-grid">
        <ProductCard
          v-for="product in topProducts"
          :key="product.id"
          :product="product"
        />
      </div>
    </section>

    <!-- Featured Products Section -->
    <section class="featured-products">
      <h2>Featured</h2>
      <div class="products-grid">
        <ProductCard
          v-for="product in featuredProducts"
          :key="product.id"
          :product="product"
        />
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const topProducts = ref([])
const featuredProducts = ref([])

onMounted(async () => {
  // Fetch both in parallel
  const [topRes, featuredRes] = await Promise.all([
    fetch('/api/products/top'),
    fetch('/api/products/featured')
  ])

  topProducts.value = (await topRes.json()).data.products
  featuredProducts.value = (await featuredRes.json()).data.products
})
</script>
```

