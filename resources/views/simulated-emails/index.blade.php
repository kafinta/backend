<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulated Emails</title>
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
        .email-list {
            margin-top: 20px;
        }
        .email-item {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }
        .email-item h3 {
            margin-top: 0;
            color: #4a76a8;
        }
        .email-item p {
            margin: 5px 0;
        }
        .email-item a {
            display: inline-block;
            background-color: #4a76a8;
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 3px;
            margin-top: 10px;
        }
        .email-item a:hover {
            background-color: #3a5a8a;
        }
        .actions {
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .actions button {
            background-color: #d9534f;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 3px;
            cursor: pointer;
        }
        .actions button:hover {
            background-color: #c9302c;
        }
        .no-emails {
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
            text-align: center;
            color: #777;
        }
    </style>
</head>
<body>
    <h1>Simulated Emails</h1>

    <div class="actions">
        <button id="refresh-btn">Refresh</button>
        <button id="clear-all-btn">Clear All Emails</button>
    </div>

    <div id="email-list" class="email-list">
        <div class="no-emails">Loading emails...</div>
    </div>

    <script>
        // Initial emails data from server
        const initialEmails = @if(isset($initialEmails)) {!! $initialEmails !!} @else null @endif;

        // Function to fetch and display emails
        function fetchEmails() {
            // If we have initial data, use it
            if (initialEmails) {
                displayEmails(initialEmails);
                return;
            }

            // Otherwise fetch from API
            fetch('/api/simulated-emails')
                .then(response => response.json())
                .then(data => {
                    displayEmails(data);
                })
                .catch(error => {
                    console.error('Error fetching emails:', error);
                    document.getElementById('email-list').innerHTML =
                        '<div class="no-emails">Error loading emails</div>';
                });
        }

        // Function to display emails
        function displayEmails(data) {
            const emailList = document.getElementById('email-list');
            emailList.innerHTML = '';

            if (data.success && data.data.emails.length > 0) {
                data.data.emails.forEach(email => {
                    const emailItem = document.createElement('div');
                    emailItem.className = 'email-item';

                    // Extract user ID and timestamp from filename
                    const filenameParts = email.filename.split('_');
                    const userId = filenameParts[1] || 'Unknown';

                    emailItem.innerHTML = `
                        <h3>Email to User #${userId}</h3>
                        <p><strong>Created:</strong> ${email.created_at}</p>
                        <p><strong>Filename:</strong> ${email.filename}</p>
                        <a href="${email.url}" target="_blank">View Email</a>
                        <button class="delete-btn" data-filename="${email.filename}">Delete</button>
                    `;

                    emailList.appendChild(emailItem);
                });

                // Add event listeners to delete buttons
                document.querySelectorAll('.delete-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const filename = this.getAttribute('data-filename');
                        deleteEmail(filename);
                    });
                });
            } else {
                emailList.innerHTML = '<div class="no-emails">No simulated emails found</div>';
            }
        }

        // Function to delete a specific email
        function deleteEmail(filename) {
            if (confirm(`Are you sure you want to delete the email "${filename}"?`)) {
                fetch(`/api/simulated-emails/${filename}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchEmails();
                    } else {
                        alert('Error deleting email: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error deleting email:', error);
                    alert('Error deleting email');
                });
            }
        }

        // Function to clear all emails
        function clearAllEmails() {
            if (confirm('Are you sure you want to delete all simulated emails?')) {
                fetch('/api/simulated-emails', {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchEmails();
                    } else {
                        alert('Error clearing emails: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error clearing emails:', error);
                    alert('Error clearing emails');
                });
            }
        }

        // Add event listeners
        document.getElementById('refresh-btn').addEventListener('click', fetchEmails);
        document.getElementById('clear-all-btn').addEventListener('click', clearAllEmails);

        // Fetch emails on page load
        document.addEventListener('DOMContentLoaded', fetchEmails);
    </script>
</body>
</html>
