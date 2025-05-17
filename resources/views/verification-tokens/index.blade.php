<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Tokens</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #4a76a8;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .token-list {
            margin-top: 20px;
        }
        .token-item {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }
        .token-item h3 {
            margin-top: 0;
            color: #4a76a8;
        }
        .token-item p {
            margin: 5px 0;
        }
        .token-item a {
            display: inline-block;
            background-color: #4a76a8;
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 3px;
            margin-top: 10px;
        }
        .token-item a:hover {
            background-color: #3a5a8a;
        }
        .token-item button {
            background-color: #d9534f;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            margin-left: 10px;
        }
        .token-item button:hover {
            background-color: #c9302c;
        }
        .actions {
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .actions button {
            background-color: #4a76a8;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 3px;
            cursor: pointer;
            margin-right: 10px;
        }
        .actions button:hover {
            background-color: #3a5a8a;
        }
        .actions button.danger {
            background-color: #d9534f;
        }
        .actions button.danger:hover {
            background-color: #c9302c;
        }
        .no-tokens {
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            text-align: center;
            color: #777;
        }
        .expired {
            color: #d9534f;
            font-weight: bold;
        }
        .token-value {
            font-family: monospace;
            background-color: #eee;
            padding: 5px;
            border-radius: 3px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <h1>Verification Tokens</h1>

    <div class="actions">
        <button id="refresh-btn">Refresh</button>
        <button id="clear-all-btn" class="danger">Clear All Tokens</button>
    </div>

    <div id="token-list" class="token-list">
        <div class="no-tokens">Loading tokens...</div>
    </div>

    <script>
        // Initial tokens data from server
        const initialTokens = @if(isset($initialTokens)) {!! $initialTokens !!} @else null @endif;

        // Function to fetch and display tokens
        function fetchTokens() {
            // If we have initial data, use it
            if (initialTokens) {
                displayTokens(initialTokens);
                return;
            }

            // Otherwise fetch from API
            fetch('/api/verification-tokens')
                .then(response => response.json())
                .then(data => {
                    displayTokens(data);
                })
                .catch(error => {
                    console.error('Error fetching tokens:', error);
                    document.getElementById('token-list').innerHTML =
                        '<div class="no-tokens">Error loading tokens</div>';
                });
        }

        // Function to display tokens
        function displayTokens(data) {
            const tokenList = document.getElementById('token-list');
            tokenList.innerHTML = '';

            if (data.success && data.data.tokens.length > 0) {
                data.data.tokens.forEach(token => {
                    const tokenItem = document.createElement('div');
                    tokenItem.className = 'token-item';

                    // Add verification code if available
                    const verificationCode = token.verification_code
                        ? `<p><strong>Verification Code:</strong> <span class="token-value">${token.verification_code}</span></p>`
                        : '';

                    tokenItem.innerHTML = `
                        <h3>Token for ${token.username} (User #${token.user_id})</h3>
                        <p><strong>Email:</strong> ${token.email}</p>
                        <p><strong>Token:</strong> <span class="token-value">${token.token}</span></p>
                        ${verificationCode}
                        <p><strong>Created:</strong> ${token.created_at}</p>
                        <p><strong>Expires:</strong> ${token.expires_at} ${token.is_expired ? '<span class="expired">(EXPIRED)</span>' : ''}</p>
                        <a href="${token.verification_url}" target="_blank">Verify Email</a>
                        <button class="delete-btn" data-token="${token.token}">Delete</button>
                    `;

                    tokenList.appendChild(tokenItem);
                });

                // Add event listeners to delete buttons
                document.querySelectorAll('.delete-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const token = this.getAttribute('data-token');
                        deleteToken(token);
                    });
                });
            } else {
                tokenList.innerHTML = '<div class="no-tokens">No verification tokens found</div>';
            }
        }

        // Function to delete a specific token
        function deleteToken(token) {
            if (confirm(`Are you sure you want to delete this token?`)) {
                fetch(`/api/verification-tokens/${token}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchTokens();
                    } else {
                        alert('Error deleting token: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error deleting token:', error);
                    alert('Error deleting token');
                });
            }
        }

        // Function to clear all tokens
        function clearAllTokens() {
            if (confirm('Are you sure you want to delete all verification tokens?')) {
                fetch('/api/verification-tokens', {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchTokens();
                    } else {
                        alert('Error clearing tokens: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error clearing tokens:', error);
                    alert('Error clearing tokens');
                });
            }
        }

        // Add event listeners
        document.getElementById('refresh-btn').addEventListener('click', fetchTokens);
        document.getElementById('clear-all-btn').addEventListener('click', clearAllTokens);

        // Fetch tokens on page load
        document.addEventListener('DOMContentLoaded', fetchTokens);
    </script>
</body>
</html>
