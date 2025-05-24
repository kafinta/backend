<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OAuth Users - Development Interface</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            border-left: 4px solid #667eea;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .users-table th,
        .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .users-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        .users-table tr:hover {
            background-color: #f8f9fa;
        }
        .provider-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }
        .provider-google {
            background-color: #db4437;
        }
        .provider-facebook {
            background-color: #3b5998;
        }
        .provider-apple {
            background-color: #000000;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-verified {
            background-color: #d4edda;
            color: #155724;
        }
        .status-unverified {
            background-color: #f8d7da;
            color: #721c24;
        }
        .has-password {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .no-password {
            background-color: #fff3cd;
            color: #856404;
        }
        .profile-picture {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
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
        .no-users {
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
        .provider-icons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .provider-icon {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 OAuth Users</h1>
            <p>Development Interface - Monitor Social Authentication Users</p>
            <div class="provider-icons">
                <div class="provider-icon provider-google">G</div>
                <div class="provider-icon provider-facebook">f</div>
                <div class="provider-icon provider-apple">🍎</div>
            </div>
        </div>
        
        <div class="content">
            <div class="warning">
                <strong>⚠️ Development Only:</strong> This interface shows users who have registered or logged in using OAuth providers (Google, Facebook, Apple).
            </div>

            <button class="refresh-btn" onclick="refreshUsers()">🔄 Refresh Users</button>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number" id="total-users">0</div>
                    <div class="stat-label">Total OAuth Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="google-users">0</div>
                    <div class="stat-label">Google Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="facebook-users">0</div>
                    <div class="stat-label">Facebook Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="apple-users">0</div>
                    <div class="stat-label">Apple Users</div>
                </div>
            </div>

            <div id="users-container">
                <div class="no-users">
                    Loading OAuth users...
                </div>
            </div>
        </div>
    </div>

    <script>
        let usersData = {!! $initialUsers !!};

        function displayUsers(data) {
            const container = document.getElementById('users-container');
            
            if (!data.success || !data.data.users || data.data.users.length === 0) {
                container.innerHTML = '<div class="no-users">No OAuth users found. Users will appear here after they sign up with Google, Facebook, or Apple.</div>';
                updateStats(0, 0, 0, 0);
                return;
            }

            const users = data.data.users;
            let googleCount = 0;
            let facebookCount = 0;
            let appleCount = 0;

            let html = `
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Profile</th>
                            <th>User Info</th>
                            <th>Provider</th>
                            <th>Email Status</th>
                            <th>Password</th>
                            <th>Roles</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            users.forEach(user => {
                // Count providers
                if (user.provider === 'google') googleCount++;
                else if (user.provider === 'facebook') facebookCount++;
                else if (user.provider === 'apple') appleCount++;

                const emailStatus = user.email_verified_at ? 'Verified' : 'Unverified';
                const emailStatusClass = user.email_verified_at ? 'status-verified' : 'status-unverified';
                const passwordStatus = user.has_password ? 'Has Password' : 'OAuth Only';
                const passwordStatusClass = user.has_password ? 'has-password' : 'no-password';

                html += `
                    <tr>
                        <td>
                            ${user.profile_picture ? 
                                `<img src="${user.profile_picture}" alt="Profile" class="profile-picture">` : 
                                '<div class="profile-picture" style="background: #ddd; display: flex; align-items: center; justify-content: center;">👤</div>'
                            }
                        </td>
                        <td>
                            <strong>${user.username}</strong><br>
                            <small>${user.email}</small>
                        </td>
                        <td>
                            <span class="provider-badge provider-${user.provider}">${user.provider_display}</span><br>
                            <small>ID: ${user.provider_id}</small>
                        </td>
                        <td>
                            <span class="status-badge ${emailStatusClass}">${emailStatus}</span><br>
                            <small>${user.email_verified_at || 'Not verified'}</small>
                        </td>
                        <td>
                            <span class="status-badge ${passwordStatusClass}">${passwordStatus}</span>
                        </td>
                        <td>${user.roles || 'No roles'}</td>
                        <td>${user.created_at}</td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            container.innerHTML = html;
            updateStats(users.length, googleCount, facebookCount, appleCount);
        }

        function updateStats(total, google, facebook, apple) {
            document.getElementById('total-users').textContent = total;
            document.getElementById('google-users').textContent = google;
            document.getElementById('facebook-users').textContent = facebook;
            document.getElementById('apple-users').textContent = apple;
        }

        function refreshUsers() {
            fetch('/oauth-users')
                .then(response => response.text())
                .then(html => {
                    // Extract the JSON data from the response
                    const match = html.match(/let usersData = ({.*?});/);
                    if (match) {
                        const newData = JSON.parse(match[1]);
                        displayUsers(newData);
                    }
                })
                .catch(error => {
                    console.error('Error refreshing users:', error);
                });
        }

        // Initial display
        displayUsers(usersData);

        // Auto-refresh every 30 seconds
        setInterval(refreshUsers, 30000);
    </script>
</body>
</html>
