# Cart System Frontend Integration Guide

## Overview

This guide provides practical integration notes for the Cart API. The cart system supports both guest and authenticated users with seamless transitions between them.

---

## Key Concepts

### Guest vs. Authenticated Carts
- **Guest users:** Cart tracked by session ID (cookie `cart_session_id`)
- **Authenticated users:** Cart tied to user account
- **Transition:** After login, call `POST /api/cart/transfer` to merge carts

### Session Cookie
- Cookie name: `cart_session_id`
- HTTP-only: Yes (cannot be accessed via JavaScript)
- Expiry: 30 days
- Automatically set on first cart view/add

---

## Workflow

### 1. Guest Shopping Flow
```
1. User views cart → GET /api/cart
   - If no cart exists, one is created
   - cart_session_id cookie is set
   
2. User adds items → POST /api/cart/items
   - Send product_id or variant_id + quantity
   - Cookie is refreshed
   
3. User updates/removes items → PUT/DELETE /api/cart/items/{id}
   - Cookie persists across requests
   
4. User clears cart → DELETE /api/cart
   - All items removed, cart still exists
```

### 2. Login & Cart Transfer
```
1. User logs in → POST /api/user/login
   - User is authenticated
   - Guest cart still exists (via cookie)
   
2. Transfer cart → POST /api/cart/transfer
   - Guest cart merged into user cart
   - Session cookie cleared
   - User now has all items in their account
   
3. Continue shopping → GET /api/cart
   - Returns user's cart (not guest cart)
```

### 3. Checkout Flow
```
1. User proceeds to checkout
   - Ensure user is authenticated
   - If not, redirect to login (with cart transfer after)
   
2. Calculate totals → POST /api/checkout/calculate
   - Provides shipping, tax, total
   
3. Place order → POST /api/checkout/place-order
   - Creates order from cart items
   - Cart is cleared after successful order
```

---

## Implementation Examples

### Nuxt.js Cart Store
```javascript
// stores/cart.js
export const useCartStore = defineStore('cart', {
  state: () => ({
    cart: null,
    loading: false,
    error: null
  }),

  actions: {
    async fetchCart() {
      this.loading = true
      try {
        const response = await $fetch('/api/cart')
        this.cart = response.data
      } catch (e) {
        this.error = e.message
      } finally {
        this.loading = false
      }
    },

    async addItem(product_id, quantity = 1) {
      try {
        const response = await $fetch('/api/cart/items', {
          method: 'POST',
          body: { product_id, quantity }
        })
        this.cart = response.data
      } catch (e) {
        this.error = e.message
      }
    },

    async updateItem(itemId, quantity) {
      try {
        const response = await $fetch(`/api/cart/items/${itemId}`, {
          method: 'PUT',
          body: { quantity }
        })
        this.cart = response.data
      } catch (e) {
        this.error = e.message
      }
    },

    async removeItem(itemId) {
      try {
        const response = await $fetch(`/api/cart/items/${itemId}`, {
          method: 'DELETE'
        })
        this.cart = response.data
      } catch (e) {
        this.error = e.message
      }
    },

    async clearCart() {
      try {
        const response = await $fetch('/api/cart', {
          method: 'DELETE'
        })
        this.cart = response.data
      } catch (e) {
        this.error = e.message
      }
    },

    async transferGuestCart() {
      try {
        const response = await $fetch('/api/cart/transfer', {
          method: 'POST'
        })
        this.cart = response.data
      } catch (e) {
        this.error = e.message
      }
    }
  }
})
```

### Login with Cart Transfer
```javascript
// In auth store or login component
async function loginAndTransferCart(credentials) {
  // 1. Login
  const authResponse = await $fetch('/api/user/login', {
    method: 'POST',
    body: credentials
  })
  
  // 2. Transfer guest cart if it exists
  const cartStore = useCartStore()
  try {
    await cartStore.transferGuestCart()
  } catch (e) {
    // Cart transfer failed, but login succeeded
    console.warn('Cart transfer failed:', e)
  }
  
  // 3. Fetch updated cart
  await cartStore.fetchCart()
}
```

---

## Common Pitfalls

1. **Forgetting cart transfer after login**
   - Always call `POST /api/cart/transfer` after login
   - Guest items will be lost if not transferred

2. **Not handling cookie persistence**
   - Ensure `credentials: 'include'` in fetch options
   - Cookie must be sent with every request

3. **Attempting guest checkout**
   - Checkout requires authentication
   - Redirect to login before checkout

4. **Not checking cart expiry**
   - Guest carts expire after 30 days
   - A new cart is created automatically if expired

5. **Mixing product_id and variant_id**
   - Send either `product_id` OR `variant_id`, not both
   - Variants take precedence if both provided

---

## Error Handling

```javascript
async function handleCartError(error) {
  if (error.status === 401) {
    // Not authenticated - redirect to login
    navigateTo('/login')
  } else if (error.status === 404) {
    // Item/cart not found - refresh cart
    await cartStore.fetchCart()
  } else if (error.status === 422) {
    // Validation error - show to user
    showError(error.data.message)
  } else {
    // Other error
    showError('An error occurred. Please try again.')
  }
}
```

---

## Best Practices

1. **Fetch cart on app load** - Restore cart state for returning users
2. **Debounce quantity updates** - Avoid rapid API calls
3. **Show loading states** - Provide feedback during API calls
4. **Handle offline gracefully** - Cache cart locally if needed
5. **Always transfer cart after login** - Don't lose guest items

