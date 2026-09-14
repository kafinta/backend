<!doctype html>
<html lang="en">
<body>
	<h1>Seller application update</h1>
	<p>Hello {{ $user->username ?? $user->email }},</p>
	<p>Your seller application status is now <strong>{{ ucfirst($status) }}</strong>.</p>
	@if ($reason)
		<p>Reason: {{ $reason }}</p>
	@endif
	<p>Regards,<br>{{ $appName }}</p>
</body>
</html>