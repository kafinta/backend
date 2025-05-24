<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Tokens - Development Interface</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: #dc3545;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #dc3545;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #dc3545;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .tokens-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .tokens-table th,
        .tokens-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .tokens-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        .tokens-table tr:hover {
            background-color: #f8f9fa;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-valid {
            background-color: #d4edda;
            color: #155724;
        }
        .status-expired {
            background-color: #f8d7da;
            color: #721c24;
        }
        .reset-code {
            font-family: 'Courier New', monospace;
            background-color: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .reset-url {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .copy-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 5px;
        }
        .copy-btn:hover {
            background: #c82333;
        }
        .refresh-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .refresh-btn:hover {
            background: #218838;
        }
        .no-tokens {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Password Reset Tokens</h1>
            <p>Development Interface - Monitor Password Reset Requests</p>
        </div>
        
        <div class="content">
            <div class="warning">
                <strong>⚠️ Development Only:</strong> This interface is for development purposes only. 
                Password reset tokens are displayed here to help with testing the forgot password flow.
            </div>

            <button class="refresh-btn" onclick="refreshTokens()">🔄 Refresh Tokens</button>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number" id="total-tokens">0</div>
                    <div class="stat-label">Total Reset Requests</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="valid-tokens">0</div>
                    <div class="stat-label">Valid Tokens</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="expired-tokens">0</div>
                    <div class="stat-label">Expired Tokens</div>
                </div>
            </div>

            <div id="tokens-container">
                <div class="no-tokens">
                    Loading password reset tokens...
                </div>
            </div>
        </div>
    </div>

    <script>
        let tokensData = {!! $initialTokens !!};

        function displayTokens(data) {
            const container = document.getElementById('tokens-container');
            
            if (!data.success || !data.data.tokens || data.data.tokens.length === 0) {
                container.innerHTML = '<div class="no-tokens">No password reset tokens found.</div>';
                updateStats(0, 0, 0);
                return;
            }

            const tokens = data.data.tokens;
            let validCount = 0;
            let expiredCount = 0;

            let html = `
                <table class="tokens-table">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Reset Code</th>
                            <th>Reset URL</th>
                            <th>Created</th>
                            <th>Expires</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            tokens.forEach(token => {
                const isExpired = token.is_expired;
                if (isExpired) {
                    expiredCount++;
                } else {
                    validCount++;
                }

                const statusClass = isExpired ? 'status-expired' : 'status-valid';
                const statusText = isExpired ? 'Expired' : 'Valid';

                html += `
                    <tr>
                        <td>${token.email}</td>
                        <td>
                            <span class="reset-code">${token.reset_code}</span>
                            <button class="copy-btn" onclick="copyToClipboard('${token.reset_code}')">Copy</button>
                        </td>
                        <td>
                            <div class="reset-url" title="${token.reset_url}">${token.reset_url}</div>
                            <button class="copy-btn" onclick="copyToClipboard('${token.reset_url}')">Copy URL</button>
                        </td>
                        <td>${token.created_at}</td>
                        <td>${token.expires_at}</td>
                        <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            container.innerHTML = html;
            updateStats(tokens.length, validCount, expiredCount);
        }

        function updateStats(total, valid, expired) {
            document.getElementById('total-tokens').textContent = total;
            document.getElementById('valid-tokens').textContent = valid;
            document.getElementById('expired-tokens').textContent = expired;
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // You could add a toast notification here
                console.log('Copied to clipboard:', text);
            });
        }

        function refreshTokens() {
            fetch('/password-reset-tokens')
                .then(response => response.text())
                .then(html => {
                    // Extract the JSON data from the response
                    const match = html.match(/let tokensData = ({.*?});/);
                    if (match) {
                        const newData = JSON.parse(match[1]);
                        displayTokens(newData);
                    }
                })
                .catch(error => {
                    console.error('Error refreshing tokens:', error);
                });
        }

        // Initial display
        displayTokens(tokensData);

        // Auto-refresh every 30 seconds
        setInterval(refreshTokens, 30000);
    </script>
</body>
</html>
