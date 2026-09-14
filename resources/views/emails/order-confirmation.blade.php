<!doctype html>
<html lang="en">
<body>
	<h1>Order confirmed</h1>
	<p>Hello {{ $user->username ?? $user->email }},</p>
	<p>Thank you for your order. Your order number is <strong>{{ $order->order_number }}</strong>.</p>
	<p><strong>Total:</strong> {{ number_format((float) $order->total, 2) }}</p>
	<p>We will send another update when your order status changes.</p>
	<p>Regards,<br>{{ $appName }}</p>
</body>
</html>