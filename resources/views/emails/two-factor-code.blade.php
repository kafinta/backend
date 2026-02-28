<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication Code</title>
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
            background-color: #2196F3;
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
        .code-box {
            background-color: #fff;
            border: 2px dashed #2196F3;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            border-radius: 5px;
        }
        .code {
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #2196F3;
            font-family: 'Courier New', monospace;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔐 Two-Factor Authentication</h1>
    </div>
    <div class="content">
        <p>Hi {{ $username }},</p>
        
        <p>You are attempting to log in to your {{ $appName }} account. To complete the login process, please use the verification code below:</p>
        
        <div class="code-box">
            <div class="code">{{ $code }}</div>
            <p style="margin: 10px 0 0 0; font-size: 14px; color: #666;">This code expires in {{ $expiresIn }}</p>
        </div>
        
        <div class="warning">
            <strong>⚠️ Security Notice:</strong>
            <p style="margin: 5px 0 0 0;">If you did not attempt to log in, please ignore this email and consider changing your password immediately. Someone may be trying to access your account.</p>
        </div>
        
        <p><strong>Important:</strong></p>
        <ul>
            <li>Never share this code with anyone</li>
            <li>{{ $appName }} will never ask for this code via phone or email</li>
            <li>This code can only be used once</li>
            <li>The code will expire in {{ $expiresIn }}</li>
        </ul>
        
        <p>If you need assistance, please contact our support team.</p>
        
        <p>Best regards,<br>The {{ $appName }} Security Team</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        <p>This is an automated security message.</p>
    </div>
</body>
</html>

