# Paystack test setup

## Environment

Add these values to the project root `.env` file. Use Paystack test keys while testing:

```env
PAYSTACK_PUBLIC_KEY=pk_test_your_public_key
PAYSTACK_SECRET_KEY=sk_test_your_secret_key
PAYSTACK_CURRENCY=NGN
PAYSTACK_CALLBACK_URL="${APP_URL}/api/payments/paystack/callback"
```

The secret key is used only by Laravel. The public key may be exposed to the frontend when the frontend integrates Paystack Checkout. Never commit either key.

## Database

Run the payment migrations:

```bash
php artisan migrate
php artisan optimize:clear
```

This creates the `payments` table and adds `currency` to `orders`.

## API flow

1. `POST /api/checkout/place-order` creates the order and initializes Paystack.
2. The response contains `payment.authorization_url`, `payment.reference`, and the amount in minor units.
3. Redirect the customer to `payment.authorization_url`.
4. Paystack redirects to `PAYSTACK_CALLBACK_URL`.
5. Configure this webhook in Paystack:
   `http://your-public-host/api/payments/paystack/webhook`
6. The backend verifies the transaction with Paystack before setting `orders.paid_at`.

For local webhook testing, use a public HTTPS tunnel such as ngrok or Cloudflare Tunnel. Paystack cannot call `localhost` directly.

## Testing

Use Paystack test mode and test cards from the Paystack dashboard. Do not use real card details. Seller fulfillment is blocked until the order has been verified as paid.

## Queue

Order and payment notifications use the existing queue. Keep a worker running:

```bash
php artisan queue:work --queue=high,default --sleep=3 --tries=3 --timeout=120
```
