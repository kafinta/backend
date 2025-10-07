# Kafinta Frontend Integration Guide

## 🚀 Quick Start

### 1. Base Configuration
```javascript
// api.js
export const API_BASE_URL = 'http://localhost:8000/api'

export const apiClient = {
  async request(endpoint, options = {}) {
    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
      credentials: 'include', // Important for cookie auth
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      ...options
    })
    
    const data = await response.json()
    if (!response.ok) throw new Error(data.message)
    return data
  }
}
```

### 2. Authentication Setup
```javascript
// auth.js
export const auth = {
  async register(userData) {
    return apiClient.request('/user/signup', {
      method: 'POST',
      body: JSON.stringify(userData)
    })
  },
  
  async login(credentials) {
    return apiClient.request('/user/login', {
      method: 'POST',
      body: JSON.stringify(credentials)
    })
  },
  
  async logout() {
    return apiClient.request('/user/logout', { method: 'POST' })
  },
  
  async verifyEmail(token) {
    return apiClient.request('/verify-email/token', {
      method: 'POST',
      body: JSON.stringify({ token })
    })
  }
}
```

## 📋 Core Features

### Authentication System
- **Cookie-based auth** - No token management needed
- **Email verification** - Both link and 6-digit code
- **Password reset** - Complete email flow
- **Guest cart merging** - Automatic on login

### Product Discovery
- **Search & filtering** - By category, location, price
- **Product details** - Full product information
- **Image galleries** - Multiple product images
- **Variants** - Size, color, etc. options

### Shopping Cart
- **Guest carts** - Works without login
- **Persistent carts** - Saved for logged-in users
- **Cart merging** - Guest → User seamless transition
- **Real-time updates** - Add, update, remove items

### Order Management
- **Checkout flow** - Complete purchase process
- **Order history** - View past orders
- **Order tracking** - Status updates
- **Email notifications** - Automatic confirmations

## 🔗 Essential API Endpoints

### Authentication
```javascript
// Register new user
POST /api/user/signup
{
  "name": "John Doe",
  "username": "johndoe", 
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}

// Login user
POST /api/user/login
{
  "login": "john@example.com", // email or username
  "password": "password123"
}

// Verify email with token (from email link)
POST /api/verify-email/token
{ "token": "verification_token" }

// Verify email with 6-digit code
POST /api/verify-email/code
{ "code": "123456" }

// Request password reset
POST /api/password/forgot
{ "email": "john@example.com" }

// Reset password with token
POST /api/password/reset/token
{
  "token": "reset_token",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

### User Profile
```javascript
// Get user profile
GET /api/user/profile

// Update profile
PUT /api/user/profile
{
  "name": "Updated Name",
  "bio": "Updated bio"
}

// Upload profile picture
POST /api/user/profile/upload-picture
// FormData with 'profile_picture' file
```

### Product Discovery
```javascript
// Get products with filtering
GET /api/products?subcategory_id=1&search=chair&min_price=50&max_price=500&per_page=20

// Get single product
GET /api/products/{id}
GET /api/products/slug/{slug}

// Get categories
GET /api/categories

// Get locations  
GET /api/locations

// Get subcategories
GET /api/subcategories?category_id=1
```

### Shopping Cart
```javascript
// View cart
GET /api/cart

// Add to cart
POST /api/cart/add
{
  "product_id": 1,
  "variant_id": 2, // optional
  "quantity": 1
}

// Update cart item
PUT /api/cart/items/{item_id}
{ "quantity": 3 }

// Remove from cart
DELETE /api/cart/items/{item_id}

// Clear cart
DELETE /api/cart/clear
```

### Orders
```javascript
// Checkout
POST /api/checkout
{
  "shipping_address": {
    "street": "123 Main St",
    "city": "City", 
    "state": "State",
    "postal_code": "12345",
    "country": "Country"
  }
}

// Get user orders
GET /api/orders

// Get single order
GET /api/orders/{id}
```

## 📱 Frontend Implementation Examples

### Nuxt.js Authentication Store
```javascript
// stores/auth.js
export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    isAuthenticated: false,
    isLoading: false
  }),

  actions: {
    async login(credentials) {
      this.isLoading = true
      try {
        const response = await auth.login(credentials)
        this.user = response.data.user
        this.isAuthenticated = true
        return response
      } finally {
        this.isLoading = false
      }
    },

    async logout() {
      await auth.logout()
      this.user = null
      this.isAuthenticated = false
      await navigateTo('/login')
    },

    async checkAuth() {
      try {
        const response = await apiClient.request('/user/profile')
        this.user = response.data
        this.isAuthenticated = true
      } catch {
        this.isAuthenticated = false
      }
    }
  }
})
```

### Product Listing Component
```vue
<!-- components/ProductList.vue -->
<template>
  <div class="product-grid">
    <div v-for="product in products" :key="product.id" class="product-card">
      <img :src="product.image_url" :alt="product.name" />
      <h3>{{ product.name }}</h3>
      <p class="price">${{ product.price }}</p>
      <button @click="addToCart(product)">Add to Cart</button>
    </div>
  </div>
</template>

<script setup>
const { data: products } = await $fetch('/api/products', {
  query: {
    subcategory_id: route.query.subcategory_id,
    search: route.query.search,
    per_page: 20
  }
})

const cartStore = useCartStore()

const addToCart = async (product) => {
  await cartStore.addItem({
    product_id: product.id,
    quantity: 1
  })
}
</script>
```

## 🛠️ Development Setup

### 1. Environment Variables
```env
# Nuxt.js .env
NUXT_API_BASE_URL=http://localhost:8000/api
NUXT_APP_BASE_URL=http://localhost:3000
```

### 2. Nuxt Configuration
```javascript
// nuxt.config.ts
export default defineNuxtConfig({
  runtimeConfig: {
    public: {
      apiBaseUrl: process.env.NUXT_API_BASE_URL || 'http://localhost:8000/api'
    }
  },
  
  ssr: false, // SPA mode for easier API integration
  
  css: ['~/assets/css/main.css'],
  
  modules: [
    '@pinia/nuxt',
    '@nuxtjs/tailwindcss'
  ]
})
```

## 🔒 Security & Best Practices

### 1. Authentication
- Always include `credentials: 'include'` in fetch requests
- Check authentication status on app initialization
- Handle 401 responses by redirecting to login
- Store minimal user data in frontend state

### 2. Error Handling
```javascript
// utils/api-error-handler.js
export const handleApiError = (error) => {
  if (error.status === 401) {
    // Redirect to login
    navigateTo('/login')
  } else if (error.status === 422) {
    // Validation errors
    return error.data.errors
  } else {
    // General error
    console.error('API Error:', error.message)
    throw error
  }
}
```

### 3. Loading States
```javascript
// composables/useApi.js
export const useApi = () => {
  const loading = ref(false)
  const error = ref(null)

  const request = async (endpoint, options = {}) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await apiClient.request(endpoint, options)
      return response
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  return { request, loading, error }
}
```

## 🎯 Next Steps

1. **Set up Nuxt.js project** with the provided configuration
2. **Implement authentication system** using the auth store example
3. **Create product listing pages** with search and filtering
4. **Build shopping cart functionality** with persistent state
5. **Implement checkout flow** with order management
6. **Add error handling and loading states** throughout the app
7. **Test all API integrations** with the backend

## 📞 Support

- **API Documentation**: See individual endpoint details in `/docs/systems/`
- **Backend Setup**: See `/docs/environment-setup.md`
- **Email System**: Automatic email verification and notifications work out of the box
- **Image Uploads**: Product and profile images handled by backend

The backend is production-ready and all APIs are tested. Focus on building a great user experience!
