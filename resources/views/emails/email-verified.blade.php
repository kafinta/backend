<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verified</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 5px 5px;
        }
        .success-icon {
            font-size: 48px;
            text-align: center;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $appName }}</h1>
    </div>
    <div class="content">
        <div class="success-icon">✅</div>
        <h2>Email Verified Successfully!</h2>
        <p>Hello {{ $username }},</p>
        <p>Great news! Your email address <strong>{{ $email }}</strong> has been successfully verified.</p>
        <p>You now have full access to all features of {{ $appName }}. You can:</p>
        <ul>
            <li>Browse and purchase products</li>
            <li>Manage your orders</li>
            <li>Update your profile settings</li>
            <li>Enable two-factor authentication for extra security</li>
        </ul>
        <p>Thank you for joining {{ $appName }}!</p>
        <div style="text-align: center;">
            <a href="{{ config('app.frontend_url', 'http://localhost:3000') }}" class="button">Go to Dashboard</a>
        </div>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        <p>This is an automated message, please do not reply to this email.</p>
    </div>
</body>
</html>

