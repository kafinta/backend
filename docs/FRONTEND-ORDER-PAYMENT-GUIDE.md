# Nuxt order and Paystack integration

## Local URLs

```env
NUXT_PUBLIC_API_BASE=http://localhost:8000/api
```

The Nuxt app may stay on `http://localhost:3000`. Laravel CORS already covers the API paths. Paystack webhooks cannot reach localhost, so the Laravel backend must be exposed with ngrok or Cloudflare Tunnel.

Update the backend `.env` whenever the tunnel URL changes:

```env
PAYSTACK_CALLBACK_URL="https://YOUR-TUNNEL/api/payments/paystack/callback"
```

Configure this Paystack webhook URL:

```text
https://YOUR-TUNNEL/api/payments/paystack/webhook
```

After changing `.env`:

```bash
php artisan optimize:clear
```

## Authentication

Protected endpoints require the same authentication method already used by the app:

```http
Authorization: Bearer YOUR_SANCTUM_TOKEN
Accept: application/json
Content-Type: application/json
```

For cookie/session authentication, send requests with credentials enabled. Do not expose `PAYSTACK_SECRET_KEY` in Nuxt. The public key is available as `PAYSTACK_PUBLIC_KEY` only if the frontend later uses Paystack Inline; the current backend flow uses the returned authorization URL.

## Checkout flow

### 1. Review cart

```http
GET /api/cart/
```

For guest cart support, preserve the `cart_session_id` cookie or send the cart session in `X-Cart-Session`.

### 2. Calculate totals

```http
POST /api/checkout/calculate
```

The request body is optional; the endpoint calculates the current cart totals. The server is authoritative for prices, discounts, stock, and the final total. Do not calculate the payment amount from frontend state.

### 3. Create order and initialize Paystack

```http
POST /api/checkout/place-order
```

Request:

```json
{
  "shipping_name": "Test Customer",
  "shipping_address": "1 Test Street",
  "shipping_city": "Lagos",
  "shipping_state": "Lagos",
  "shipping_postal_code": "100001",
  "shipping_country": "Nigeria",
  "shipping_phone": "+2348000000000",
  "notes": "Leave at reception"
}
```

Successful response shape:

```json
{
  "success": true,
  "data": {
    "id": "KOA7B2M9X4Q8ZP1C",
    "order_number": "KOA7B2M9X4Q8ZP1C",
    "status": "pending",
    "currency": "NGN",
    "subtotal": "5000.00",
    "total": "5000.00",
    "payment": {
      "provider": "paystack",
      "reference": "KAF-KOA7B2M9X4Q8ZP1C-AB12CD34",
      "authorization_url": "https://checkout.paystack.com/...",
      "amount": 500000,
      "currency": "NGN",
      "status": "pending",
      "paid_at": null
    },
    "items": []
  }
}
```

`payment.amount` is in kobo. `500000` means `NGN 5,000.00`.

Redirect the browser to `payment.authorization_url`:

```ts
window.location.assign(response.data.payment.authorization_url)
```

The current backend callback verifies the Paystack reference and returns JSON; it does not automatically navigate to Nuxt. The frontend should provide a payment result route and call the authenticated verify endpoint with the payment reference. If automatic navigation is required, add a frontend redirect URL and callback redirect behavior as a separate configuration change:

```text
http://localhost:3000/{username}/buying/payment?reference=KAF-KO...&order={order_number}
```

The frontend route calls `GET /api/payments/verify?reference=...` and treats the backend response as authoritative. It must not infer payment success from the browser redirect alone.

### 4. Payment completion

Paystack redirects to the configured backend callback. The backend verifies the reference with Paystack before setting `orders.paid_at`. The webhook also verifies successful charges and is the source of truth for asynchronous completion.

For a customer-facing result page, the Nuxt app can show a pending/payment-verifying state and then call:

```http
GET /api/payments/verify?reference=KAF-KO...
```

The reference must belong to the authenticated user. A successful payment returns `payment.status: "success"` and `paid_at`; failed or abandoned payments remain unpaid.

### 5. Refresh order state

```http
GET /api/orders/{order_number}
GET /api/orders
```

The order response includes:

```json
{
  "status": "pending",
  "paid_at": null,
  "is_paid": false,
  "payment": {
    "reference": "KAF-...",
    "status": "success",
    "paid_at": "2026-09-27T12:00:00.000000Z"
  }
}
```

Treat `order.paid_at` or `order.is_paid` as the paid-order signal. Do not mark an order paid from a frontend callback alone.

## Order actions

```http
POST /api/orders/{order_number}/cancel
```

Only pending orders can be cancelled. Cancellation restores reserved stock.

Customer order statuses are normally:

```text
pending -> processing -> shipped -> delivered
pending -> cancelled
processing -> cancelled
```

Seller fulfillment:

```http
GET /api/sellers/seller/orders
GET /api/sellers/seller/orders/{id}
PUT /api/sellers/seller/orders/{id}/status
```

Request:

```json
{ "status": "processing" }
```

Seller processing is rejected until the order is paid. Terminal statuses cannot be moved backwards.

## Local test checklist

1. Start MySQL and run `php artisan migrate`.
2. Put Paystack `pk_test_...` and `sk_test_...` values in the backend `.env`.
3. Start Laravel with `php artisan serve`.
4. Start the queue worker:
   `php artisan queue:work --queue=high,default --sleep=3 --tries=3 --timeout=120`.
5. Start Nuxt on port 3000.
6. Start a tunnel for Laravel: `ngrok http 8000`.
7. Update `PAYSTACK_CALLBACK_URL` and the Paystack webhook URL with the current HTTPS tunnel URL.
8. Add a product to the cart and place an order.
9. Redirect to Paystack's authorization URL and use a Paystack test card.
10. Confirm `GET /api/payments/verify` and `GET /api/orders/{order_number}` show the payment as successful.
11. Confirm seller status updates work only after payment verification.

## Backend requirements for the frontend order flow

The frontend now expects these authenticated endpoints and response fields:

- `POST /api/checkout/calculate` reads the current cart and returns a server-side calculation; its request body is optional.
- `POST /api/checkout/place-order` returns `data.id`, `data.payment.reference`, and `data.payment.authorization_url`.
- `GET /api/orders` returns an array, or `{ "orders": [] }`.
- `GET /api/orders/{order_number}` returns the order with `items`, `total`, payment state, and status. The order number is `KO` followed by 14 uppercase letters/numbers.
- `GET /api/payments/verify?reference=...` is scoped to the authenticated user and returns payment/order state.
- `POST /api/orders/{order_number}/cancel` enforces cancellation rules and restores reserved stock.
- `GET /api/sellers/seller/orders` returns seller-visible orders.
- `PUT /api/sellers/seller/orders/{id}/status` validates paid-order and status-transition rules.

The frontend must handle the JSON response from the Paystack callback or call the authenticated verify endpoint from its payment result page. CORS/session credentials, CSRF handling, authorization policies, stock reservation/release, webhook processing, and idempotency for repeated place-order requests are backend responsibilities.

Never commit `.env`, the Paystack secret key, or real card details.
