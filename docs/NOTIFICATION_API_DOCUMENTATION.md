# 🔔 **Notification System API Documentation**

## Overview
The notification system provides real-time notifications for orders, products, cart activities, and seller operations. It supports both in-app and email notifications with user-customizable preferences.

## Notification Types
- **Order Notifications**: placed, confirmed, processing, shipped, delivered, cancelled
- **Cart Notifications**: abandoned cart reminders
- **Product Notifications**: first submission, approval, denial, stock alerts
- **Seller Notifications**: new orders, product status changes
- **Admin Notifications**: new products, new sellers, system maintenance

## Base URL
```
http://localhost:8000/api
```

---

## 🔔 **NOTIFICATION API ENDPOINTS**

### 1. List User Notifications
```http
GET /api/notifications
```

**Description**: Get user notifications with pagination and filtering options.

**Headers**: 
- `Authorization: Bearer {token}` (required)

**Query Parameters**:
- `per_page` (optional): Number of notifications per page (1-100, default: 20)
- `unread_only` (optional): Boolean to filter only unread notifications

**Response**:
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Notifications retrieved successfully",
  "data": {
    "notifications": [
      {
        "id": 1,
        "type": "order_placed",
        "title": "Order Placed Successfully",
        "message": "Your order #ORD-2024-001 has been placed successfully. Total: ₦71.37",
        "data": {
          "order_id": 1,
          "order_number": "ORD-2024-001",
          "total": "71.37",
          "action_url": "/orders/1"
        },
        "is_read": false,
        "is_unread": true,
        "read_at": null,
        "sent_at": "2024-01-15T10:30:00Z",
        "created_at": "2024-01-15T10:30:00Z",
        "time_ago": "2 minutes ago",
        "icon": "shopping-cart",
        "color": "blue"
      },
      {
        "id": 2,
        "type": "seller_product_approved",
        "title": "First Product Submitted!",
        "message": "Congratulations! You've submitted your first product 'Cotton T-Shirt'. It's now under review by our team.",
        "data": {
          "product_id": 1,
          "product_name": "Cotton T-Shirt",
          "is_first_product": true,
          "action_url": "/seller/products/1"
        },
        "is_read": true,
        "is_unread": false,
        "read_at": "2024-01-15T10:35:00Z",
        "sent_at": "2024-01-15T10:30:00Z",
        "created_at": "2024-01-15T10:30:00Z",
        "time_ago": "5 minutes ago",
        "icon": "check",
        "color": "green"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 20,
      "total": 45,
      "has_more_pages": true
    },
    "unread_count": 12
  }
}
```

### 2. Get Unread Notification Count
```http
GET /api/notifications/unread-count
```

**Description**: Get the count of unread notifications for the authenticated user.

**Response**:
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Unread count retrieved successfully",
  "data": {
    "unread_count": 12
  }
}
```

### 3. Mark Notification as Read
```http
POST /api/notifications/{id}/read
```

**Description**: Mark a specific notification as read.

**Response**:
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Notification marked as read",
  "data": {
    "unread_count": 11
  }
}
```

### 4. Mark All Notifications as Read
```http
POST /api/notifications/mark-all-read
```

**Description**: Mark all notifications as read for the authenticated user.

**Response**:
```json
{
  "status": "success",
  "status_code": 200,
  "message": "All notifications marked as read",
  "data": {
    "marked_count": 12,
    "unread_count": 0
  }
}
```

### 5. Delete Notification
```http
DELETE /api/notifications/{id}
```

**Description**: Delete a specific notification.

**Response**:
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Notification deleted successfully",
  "data": {
    "unread_count": 11
  }
}
```

---

## ⚙️ **NOTIFICATION PREFERENCE ENDPOINTS**

### 1. Get Notification Preferences
```http
GET /api/notifications/preferences
```

**Description**: Get user's notification preferences for all notification types.

**Response**:
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Notification preferences retrieved successfully",
  "data": {
    "preferences": {
      "order_placed": {
        "email_enabled": true,
        "app_enabled": true
      },
      "order_confirmed": {
        "email_enabled": true,
        "app_enabled": true
      },
      "order_shipped": {
        "email_enabled": true,
        "app_enabled": true
      },
      "cart_abandoned": {
        "email_enabled": false,
        "app_enabled": true
      },
      "seller_new_order": {
        "email_enabled": true,
        "app_enabled": true
      },
      "seller_product_approved": {
        "email_enabled": true,
        "app_enabled": true
      },
      "seller_product_denied": {
        "email_enabled": true,
        "app_enabled": true
      },
      "product_low_stock": {
        "email_enabled": false,
        "app_enabled": true
      }
    }
  }
}
```

### 2. Update Notification Preferences
```http
PUT /api/notifications/preferences
```

**Description**: Update user's notification preferences.

**Request Body**:
```json
{
  "preferences": {
    "order_placed": {
      "email_enabled": true,
      "app_enabled": true
    },
    "cart_abandoned": {
      "email_enabled": false,
      "app_enabled": true
    },
    "seller_new_order": {
      "email_enabled": true,
      "app_enabled": true
    }
  }
}
```

**Validation Rules**:
- `preferences`: required|array
- `preferences.*.email_enabled`: required|boolean
- `preferences.*.app_enabled`: required|boolean

**Response**:
```json
{
  "status": "success",
  "status_code": 200,
  "message": "Notification preferences updated successfully",
  "data": {
    "preferences": {
      // Updated preferences object
    }
  }
}
```

---

## 🔧 **FRONTEND IMPLEMENTATION GUIDE**

### JavaScript/TypeScript Service Example

```javascript
class NotificationService {
  constructor(baseURL = 'http://localhost:8000/api') {
    this.baseURL = baseURL;
    this.token = localStorage.getItem('auth_token');
  }

  // Get notifications with pagination
  async getNotifications(page = 1, perPage = 20, unreadOnly = false) {
    const params = new URLSearchParams({
      per_page: perPage,
      ...(unreadOnly && { unread_only: 'true' })
    });

    const response = await fetch(`${this.baseURL}/notifications?${params}`, {
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      }
    });
    return response.json();
  }

  // Get unread count
  async getUnreadCount() {
    const response = await fetch(`${this.baseURL}/notifications/unread-count`, {
      headers: {
        'Authorization': `Bearer ${this.token}`
      }
    });
    return response.json();
  }

  // Mark as read
  async markAsRead(notificationId) {
    const response = await fetch(`${this.baseURL}/notifications/${notificationId}/read`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      }
    });
    return response.json();
  }

  // Mark all as read
  async markAllAsRead() {
    const response = await fetch(`${this.baseURL}/notifications/mark-all-read`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      }
    });
    return response.json();
  }

  // Delete notification
  async deleteNotification(notificationId) {
    const response = await fetch(`${this.baseURL}/notifications/${notificationId}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${this.token}`
      }
    });
    return response.json();
  }

  // Get preferences
  async getPreferences() {
    const response = await fetch(`${this.baseURL}/notifications/preferences`, {
      headers: {
        'Authorization': `Bearer ${this.token}`
      }
    });
    return response.json();
  }

  // Update preferences
  async updatePreferences(preferences) {
    const response = await fetch(`${this.baseURL}/notifications/preferences`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ preferences })
    });
    return response.json();
  }
}
```

### Vue.js Composable Example

```javascript
// composables/useNotifications.js
import { ref, computed } from 'vue'

export function useNotifications() {
  const notifications = ref([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const error = ref(null)

  const notificationService = new NotificationService()

  const hasUnread = computed(() => unreadCount.value > 0)

  const fetchNotifications = async (page = 1, unreadOnly = false) => {
    loading.value = true
    try {
      const response = await notificationService.getNotifications(page, 20, unreadOnly)
      if (response.success) {
        notifications.value = response.data.notifications
        unreadCount.value = response.data.unread_count
      } else {
        error.value = response.message
      }
    } catch (err) {
      error.value = 'Failed to fetch notifications'
    } finally {
      loading.value = false
    }
  }

  const markAsRead = async (notificationId) => {
    try {
      const response = await notificationService.markAsRead(notificationId)
      if (response.success) {
        // Update local state
        const notification = notifications.value.find(n => n.id === notificationId)
        if (notification) {
          notification.is_read = true
          notification.is_unread = false
          notification.read_at = new Date().toISOString()
        }
        unreadCount.value = response.data.unread_count
      }
    } catch (err) {
      error.value = 'Failed to mark notification as read'
    }
  }

  const markAllAsRead = async () => {
    try {
      const response = await notificationService.markAllAsRead()
      if (response.success) {
        // Update all notifications to read
        notifications.value.forEach(notification => {
          notification.is_read = true
          notification.is_unread = false
          notification.read_at = new Date().toISOString()
        })
        unreadCount.value = 0
      }
    } catch (err) {
      error.value = 'Failed to mark all notifications as read'
    }
  }

  return {
    notifications,
    unreadCount,
    loading,
    error,
    hasUnread,
    fetchNotifications,
    markAsRead,
    markAllAsRead
  }
}
```

---

## 📋 **NOTIFICATION TYPES REFERENCE**

### Order Notifications
- `order_placed` - Order successfully placed
- `order_confirmed` - Order confirmed by seller
- `order_processing` - Order being processed
- `order_shipped` - Order shipped
- `order_delivered` - Order delivered
- `order_cancelled` - Order cancelled

### Cart Notifications
- `cart_abandoned` - Cart abandoned reminder

### Product Notifications (Sellers)
- `seller_product_approved` - Product approved (also used for first product)
- `seller_product_denied` - Product denied
- `product_low_stock` - Product running low on stock
- `product_out_of_stock` - Product out of stock

### Seller Notifications
- `seller_new_order` - New order received

### Admin Notifications
- `admin_new_product` - New product submitted
- `admin_new_seller` - New seller registered
- `system_maintenance` - System maintenance alerts

---

## ⚠️ **IMPORTANT NOTES FOR FRONTEND**

### Real-time Updates
- Poll `/api/notifications/unread-count` every 30-60 seconds for real-time updates
- Consider implementing WebSocket connections for instant notifications
- Update notification badge/counter immediately after marking as read

### Error Handling
- Handle 404 errors for notifications that don't exist
- Handle 403 errors for unauthorized access
- Implement retry logic for failed API calls

### Performance Tips
- Cache notifications in state management
- Implement infinite scrolling for notification lists
- Debounce mark-as-read calls to avoid excessive API requests
- Use optimistic updates for better UX

### Testing Endpoints
```bash
# Get notifications
curl -X GET http://localhost:8000/api/notifications \
  -H "Authorization: Bearer {token}"

# Get unread count
curl -X GET http://localhost:8000/api/notifications/unread-count \
  -H "Authorization: Bearer {token}"

# Mark notification as read
curl -X POST http://localhost:8000/api/notifications/1/read \
  -H "Authorization: Bearer {token}"

# Update preferences
curl -X PUT http://localhost:8000/api/notifications/preferences \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "preferences": {
      "order_placed": {
        "email_enabled": true,
        "app_enabled": true
      }
    }
  }'
```

The notification system is fully functional and ready for frontend integration! 🔔✨
