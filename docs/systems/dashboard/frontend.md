# Dashboard Implementation Guide

## Overview

The dashboard is **not a single aggregated endpoint**. Instead, it's composed of multiple independent API endpoints that the frontend calls in parallel. This approach provides:

- **Better Performance**: Parallel requests load faster than sequential ones
- **Flexibility**: Frontend controls what data to display and when
- **Maintainability**: Each endpoint is independently testable and cacheable
- **Scalability**: Easy to add new dashboard sections without backend changes

## Buyer Dashboard

The buyer dashboard should call these endpoints when the page loads:

### 1. User Profile
```
GET /api/user/profile
```
Returns user information (username, email, phone, profile picture, verification status)

### 2. Orders
```
GET /api/orders
```
Returns paginated list of user's orders with statuses

### 3. Notifications
```
GET /api/notifications?per_page=5
```
Returns recent notifications with unread count available via:
```
GET /api/notifications/unread-count
```

### 4. Reviews Written
```
GET /api/reviews?user_id={userId}
```
Returns reviews written by the user (if this endpoint exists, or fetch from product reviews)

### Recommended Frontend Implementation

```javascript
// Load all dashboard data in parallel
const [userProfile, orders, notifications, unreadCount] = await Promise.all([
  fetch('/api/user/profile').then(r => r.json()),
  fetch('/api/orders?per_page=5').then(r => r.json()),
  fetch('/api/notifications?per_page=5').then(r => r.json()),
  fetch('/api/notifications/unread-count').then(r => r.json()),
]);
```

## Seller Dashboard

The seller dashboard should call these endpoints when the page loads:

### 1. Seller Profile
```
GET /api/sellers/{id}
```
Returns seller business information and verification status

### 2. Product Statistics
```
GET /api/products/my-stats
```
Returns product counts by status (active, draft, paused, denied)

### 3. Seller's Orders
```
GET /api/seller/orders?per_page=5
```
Returns recent orders containing seller's products

### 4. Inventory Summary
```
GET /api/inventory/summary
```
Returns inventory statistics (total stock, low stock items, out of stock items)

### 5. My Products
```
GET /api/products/my-products?per_page=5
```
Returns seller's products (can be used for top products or recent products)

### Recommended Frontend Implementation

```javascript
// Load all dashboard data in parallel
const [sellerProfile, productStats, orders, inventory, products] = await Promise.all([
  fetch('/api/sellers/{sellerId}').then(r => r.json()),
  fetch('/api/products/my-stats').then(r => r.json()),
  fetch('/api/seller/orders?per_page=5').then(r => r.json()),
  fetch('/api/inventory/summary').then(r => r.json()),
  fetch('/api/products/my-products?per_page=5').then(r => r.json()),
]);
```

## Benefits of This Approach

1. **Parallel Loading**: All requests happen simultaneously, not sequentially
2. **Partial Updates**: If one endpoint fails, others still load
3. **Caching**: Each endpoint can be cached independently
4. **Reusability**: Same endpoints used for detailed pages and dashboard
5. **Frontend Control**: Display data in any order or format needed
6. **Scalability**: Add new dashboard sections by calling new endpoints

## Error Handling

Each endpoint should be wrapped in error handling:

```javascript
try {
  const response = await fetch('/api/endpoint');
  if (!response.ok) throw new Error(`HTTP ${response.status}`);
  return await response.json();
} catch (error) {
  console.error('Failed to load data:', error);
  // Show error state or retry
}
```

## Loading States

Show loading states for each section independently:

```javascript
const [loading, setLoading] = useState({
  profile: true,
  orders: true,
  notifications: true,
  inventory: true,
});

// Update loading state as each request completes
Promise.all([...]).then(([profile, orders, ...]) => {
  setLoading({ profile: false, orders: false, ... });
});
```

## Pagination

Most endpoints support pagination:
- `?page=1` - Page number
- `?per_page=10` - Items per page

Example:
```
GET /api/orders?page=1&per_page=5
```

## Notes

- All dashboard endpoints require authentication (`auth:sanctum`)
- Seller dashboard endpoints additionally require `role:seller` middleware
- Timestamps are included in responses but can be formatted on the frontend
- All responses follow the standard format: `{ success, status, status_code, message, data }`

