# 🛍️ **Order System API Documentation**

## Overview
The order system handles the complete order lifecycle from checkout to delivery. It supports both customer order management and seller order fulfillment with comprehensive status tracking.

## Order Status Workflow
```
pending → confirmed → processing → shipped → delivered
    ↓         ↓           ↓          ↓
cancelled cancelled  cancelled  cancelled
```

## Base URL
```
http://localhost:8000/api
```

---

## 🛍️ **CUSTOMER ORDER API ENDPOINTS**

### 1. List User Orders
```http
GET /api/orders
```

**Description**: Get all orders for the authenticated user.

**Headers**: 
- `Authorization: Bearer {token}` (required)

**Response**:
```json
{
  "success": true,
  "message": "Orders retrieved successfully",
  "data": [
    {
      "id": 1,
      "order_number": "ORD-2024-001",
      "status": "pending",
      "subtotal": "59.98",
      "tax": "5.40",
      "shipping_cost": "5.99",
      "total": "71.37",
      "shipping": {
        "name": "John Doe",
        "address": "123 Main St",
        "city": "Lagos",
        "state": "Lagos State",
        "postal_code": "100001",
        "country": "Nigeria",
        "phone": "+234..."
      },
      "notes": "Please deliver after 6 PM",
      "is_paid": false,
      "is_shipped": false,
      "is_delivered": false,
      "is_cancelled": false,
      "paid_at": null,
      "shipped_at": null,
      "delivered_at": null,
      "cancelled_at": null,
      "created_at": "2024-01-15T10:30:00Z",
      "items": [
        {
          "id": 1,
          "quantity": 2,
          "price": "29.99",
          "subtotal": "59.98",
          "status": "pending",
          "product_name": "Cotton T-Shirt",
          "variant_name": null,
          "product": {
            "id": 456,
            "name": "Cotton T-Shirt",
            "slug": "cotton-t-shirt",
            "price": "29.99",
            "status": "active"
          },
          "variant": null,
          "shipped_at": null,
          "delivered_at": null,
          "cancelled_at": null,
          "is_shipped": false,
          "is_delivered": false,
          "is_cancelled": false
        }
      ],
      "item_count": 1,
      "total_quantity": 2,
      "can_cancel": true
    }
  ]
}
```

### 2. View Order Details
```http
GET /api/orders/{orderId}
```

**Description**: Get detailed information about a specific order.

**Response**: Same structure as single order above with full item details.

### 3. Cancel Order
```http
POST /api/orders/{orderId}/cancel
```

**Description**: Cancel a pending order.

**Business Rules**:
- Only orders with status "pending" can be cancelled
- User can only cancel their own orders

**Response**:
```json
{
  "success": true,
  "message": "Order cancelled successfully",
  "data": {
    // Updated order object with status: "cancelled"
    "status": "cancelled",
    "cancelled_at": "2024-01-15T11:00:00Z",
    "can_cancel": false
  }
}
```

---

## 🏪 **SELLER ORDER API ENDPOINTS**

### 1. List Seller Orders
```http
GET /api/seller/orders
```

**Description**: Get all orders containing the seller's products.

**Headers**: 
- `Authorization: Bearer {token}` (required - seller role)

**Response**: Same structure as customer orders but includes customer information.

### 2. View Seller Order
```http
GET /api/seller/orders/{orderId}
```

**Description**: Get detailed information about an order containing seller's products.

**Response**: Full order details with customer information.

### 3. Update Order Status
```http
PUT /api/seller/orders/{orderId}/status
```

**Description**: Update the status of an order (seller can update their items).

**Request Body**:
```json
{
  "status": "confirmed"  // pending, confirmed, processing, shipped, delivered, cancelled
}
```

**Validation Rules**:
- `status`: required|in:pending,confirmed,processing,shipped,delivered,cancelled

**Response**: Updated order object with new status.

---

## 🛒 **CHECKOUT API ENDPOINTS**

### 1. Calculate Order Totals
```http
POST /api/checkout/calculate
```

**Description**: Calculate order totals including tax and shipping before placing order.

**Headers**: 
- `Content-Type: application/json`
- `Authorization: Bearer {token}` (optional for guests)

**Response**:
```json
{
  "success": true,
  "message": "Order totals calculated",
  "data": {
    "subtotal": "59.98",
    "tax": "0.00",
    "shipping": "0.00",
    "total": "59.98",
    "items": [
      {
        "id": 1,
        "quantity": 2,
        "product": {
          "id": 456,
          "name": "Cotton T-Shirt",
          "price": "29.99"
        },
        "price": "29.99",
        "subtotal": "59.98"
      }
    ],
    "item_count": 1,
    "total_quantity": 2,
    "can_checkout": true,
    "checkout_errors": []
  }
}
```

### 2. Place Order
```http
POST /api/checkout/place-order
```

**Description**: Create an order from the current cart.

**Request Body**:
```json
{
  "shipping_name": "John Doe",
  "shipping_address": "123 Main St",
  "shipping_city": "Lagos",
  "shipping_state": "Lagos State",
  "shipping_postal_code": "100001",
  "shipping_country": "Nigeria",
  "shipping_phone": "+234...",
  "notes": "Please deliver after 6 PM"
}
```

**Validation Rules**:
- `shipping_name`: required|string|max:255
- `shipping_address`: required|string|max:255
- `shipping_city`: required|string|max:255
- `shipping_state`: required|string|max:255
- `shipping_postal_code`: required|string|max:20
- `shipping_country`: required|string|max:255
- `shipping_phone`: required|string|max:20
- `notes`: nullable|string|max:1000

**Response**: Complete order object (same as order details).

### 3. Get Shipping Methods
```http
GET /api/checkout/shipping-methods
```

**Response**:
```json
{
  "success": true,
  "message": "Shipping methods retrieved",
  "data": {
    "shipping_methods": [
      {
        "id": "standard",
        "name": "Standard Shipping",
        "description": "3-5 business days",
        "price": 5.99
      },
      {
        "id": "express",
        "name": "Express Shipping",
        "description": "1-2 business days",
        "price": 12.99
      },
      {
        "id": "free",
        "name": "Free Shipping",
        "description": "5-7 business days",
        "price": 0.00
      }
    ]
  }
}
```

### 4. Get Payment Methods
```http
GET /api/checkout/payment-methods
```

**Response**:
```json
{
  "success": true,
  "message": "Payment methods retrieved",
  "data": {
    "payment_methods": [
      {
        "id": "cash",
        "name": "Cash on Delivery",
        "description": "Pay when you receive your order"
      },
      {
        "id": "bank_transfer",
        "name": "Bank Transfer",
        "description": "Pay via bank transfer"
      }
    ]
  }
}
```

---

## 🔧 **FRONTEND IMPLEMENTATION GUIDE**

### JavaScript/TypeScript Service Example

```javascript
class OrderService {
  constructor(baseURL = 'http://localhost:8000/api') {
    this.baseURL = baseURL;
    this.token = localStorage.getItem('auth_token');
  }

  // Get user orders
  async getOrders() {
    const response = await fetch(`${this.baseURL}/orders`, {
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      }
    });
    return response.json();
  }

  // Get order details
  async getOrder(orderId) {
    const response = await fetch(`${this.baseURL}/orders/${orderId}`, {
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      }
    });
    return response.json();
  }

  // Cancel order
  async cancelOrder(orderId) {
    const response = await fetch(`${this.baseURL}/orders/${orderId}/cancel`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      }
    });
    return response.json();
  }

  // Calculate checkout totals
  async calculateTotals() {
    const response = await fetch(`${this.baseURL}/checkout/calculate`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      },
      credentials: 'include'
    });
    return response.json();
  }

  // Place order
  async placeOrder(shippingData) {
    const response = await fetch(`${this.baseURL}/checkout/place-order`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      },
      credentials: 'include',
      body: JSON.stringify(shippingData)
    });
    return response.json();
  }

  // Get shipping methods
  async getShippingMethods() {
    const response = await fetch(`${this.baseURL}/checkout/shipping-methods`);
    return response.json();
  }

  // Get payment methods
  async getPaymentMethods() {
    const response = await fetch(`${this.baseURL}/checkout/payment-methods`);
    return response.json();
  }
}

// Seller Order Service
class SellerOrderService {
  constructor(baseURL = 'http://localhost:8000/api') {
    this.baseURL = baseURL;
    this.token = localStorage.getItem('auth_token');
  }

  // Get seller orders
  async getSellerOrders() {
    const response = await fetch(`${this.baseURL}/seller/orders`, {
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      }
    });
    return response.json();
  }

  // Update order status
  async updateOrderStatus(orderId, status) {
    const response = await fetch(`${this.baseURL}/seller/orders/${orderId}/status`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ status })
    });
    return response.json();
  }
}
```

---

## ⚠️ **IMPORTANT NOTES FOR FRONTEND**

### Order Status Management
- **Customer View**: Can only cancel pending orders
- **Seller View**: Can update status of orders containing their products
- **Status Flow**: Follow the defined workflow (pending → confirmed → processing → shipped → delivered)

### Error Handling
- **404 Errors**: Order not found or user doesn't have permission
- **400 Errors**: Business logic violations (e.g., cancelling shipped order)
- **422 Errors**: Validation failures in checkout

### Authentication Requirements
- **Customer Orders**: Require authentication token
- **Seller Orders**: Require seller role authentication
- **Checkout**: Works for both authenticated and guest users

### Data Preservation
- **Product Names**: Stored at order time, preserved even if product is deleted
- **Pricing**: Locked at order time, not affected by price changes
- **Order History**: Complete audit trail with timestamps

### Testing Endpoints
```bash
# Get user orders
curl -X GET http://localhost:8000/api/orders \
  -H "Authorization: Bearer {token}"

# Calculate checkout totals
curl -X POST http://localhost:8000/api/checkout/calculate \
  -H "Authorization: Bearer {token}" \
  --cookie-jar cookies.txt

# Place order
curl -X POST http://localhost:8000/api/checkout/place-order \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  --cookie cookies.txt \
  -d '{
    "shipping_name": "John Doe",
    "shipping_address": "123 Main St",
    "shipping_city": "Lagos",
    "shipping_state": "Lagos State",
    "shipping_postal_code": "100001",
    "shipping_country": "Nigeria",
    "shipping_phone": "+234...",
    "notes": "Please deliver after 6 PM"
  }'
```

The order system is fully functional and ready for frontend integration! 🛍️✨
