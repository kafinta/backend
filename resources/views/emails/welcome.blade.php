<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to {{ $appName }}</title>
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
            background-color: #4a76a8;
            color: white;
            padding: 20px;
            border-radius: 5px 5px 0 0;
            text-align: center;
        }
        .content {
            border: 1px solid #ddd;
            border-top: none;
            padding: 30px;
            border-radius: 0 0 5px 5px;
            background-color: #f9f9f9;
        }
        .button {
            display: inline-block;
            background-color: #4a76a8;
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to {{ $appName }}!</h1>
    </div>
    <div class="content">
        <h2>Hello {{ $user->name ?? $user->username }},</h2>
        
        <p>Welcome to {{ $appName }}! We're excited to have you join our community.</p>
        
        <p>Your account has been successfully created and you can now:</p>
        <ul>
            <li>Browse and purchase products</li>
            <li>Manage your profile and preferences</li>
            <li>Track your orders</li>
            <li>Apply to become a seller</li>
        </ul>
        
        <p style="text-align: center;">
            <a href="{{ $appUrl }}" class="button">Start Shopping</a>
        </p>
        
        <p>If you have any questions, feel free to contact our support team.</p>
        
        <p>Thank you for choosing {{ $appName }}!</p>
        
        <div class="footer">
            <p>© {{ $appName }} - {{ date('Y') }}</p>
            <p>This email was sent to {{ $user->email }}</p>
        </div>
    </div>
</body>
</html>
