# YMZM User Authentication API

This API allows external applications to authenticate users against the YMZM user database. This enables "Login with YMZM" functionality across different platforms on your local network.

## Base URL
```
http://<YOUR_SERVER_IP>/api
```

To find your server IP on the local network, run `ipconfig` in Command Prompt and look for your IPv4 Address (e.g., `192.168.1.100`).

## CORS Policy
This API accepts requests from **any origin** on the local network, making it perfect for development and cross-site authentication within your network.

---

## Authentication Endpoint

**URL:** `/login.php`  
**Method:** `POST`  
**Content-Type:** `application/json`

### Description
Verifies user credentials (email and password) and returns user details if successful.

### Request Body
The request must contain a JSON object with the following fields:

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `email` | string | Yes | The user's registered email address |
| `password` | string | Yes | The user's password |

**Example Request:**
```json
{
    "email": "user@example.com",
    "password": "your_password"
}
```

### Success Response
**Code:** `200 OK`

**Example Response:**
```json
{
    "status": "success",
    "message": "Login successful.",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "role": "user",
        "created_at": "2024-01-15 10:30:00"
    }
}
```

### Error Responses

**1. Invalid Credentials**  
**Code:** `401 Unauthorized`
```json
{
    "status": "error",
    "message": "Invalid email or password."
}
```

**2. Missing Parameters**  
**Code:** `400 Bad Request`
```json
{
    "status": "error",
    "message": "Incomplete data. Provide email and password."
}
```

**3. Invalid Email Format**  
**Code:** `400 Bad Request`
```json
{
    "status": "error",
    "message": "Invalid email format."
}
```

**4. Method Not Allowed**  
**Code:** `405 Method Not Allowed`
```json
{
    "status": "error",
    "message": "Method not allowed. Use POST."
}
```

**5. Server Error**  
**Code:** `500 Internal Server Error`
```json
{
    "status": "error",
    "message": "An internal error occurred. Please try again later."
}
```

---

## Usage Examples

### JavaScript (Fetch API)
```javascript
const YMZM_API_URL = 'http://192.168.1.100/api'; // Replace with your server IP

const loginWithYMZM = async (email, password) => {
    try {
        const response = await fetch(`${YMZM_API_URL}/login.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (response.ok) {
            console.log('User logged in:', data.user);
            // Store user data in localStorage or session
            localStorage.setItem('ymzm_user', JSON.stringify(data.user));
            return data.user;
        } else {
            console.error('Login failed:', data.message);
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
};

// Usage
loginWithYMZM('user@example.com', 'mypassword')
    .then(user => {
        console.log('Welcome,', user.name);
    })
    .catch(err => {
        alert('Login failed: ' + err.message);
    });
```

### HTML Login Form Example
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login with YMZM</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
        .error { color: red; margin-top: 10px; }
        .success { color: green; margin-top: 10px; }
    </style>
</head>
<body>
    <h2>Login with YMZM Account</h2>
    <form id="loginForm">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">Login with YMZM</button>
        <div id="message"></div>
    </form>

    <script>
        const YMZM_API_URL = 'http://192.168.1.100/api'; // Replace with your server IP

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const messageDiv = document.getElementById('message');

            try {
                const response = await fetch(`${YMZM_API_URL}/login.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (response.ok) {
                    messageDiv.className = 'success';
                    messageDiv.textContent = `Welcome, ${data.user.name}!`;
                    localStorage.setItem('ymzm_user', JSON.stringify(data.user));
                } else {
                    messageDiv.className = 'error';
                    messageDiv.textContent = data.message;
                }
            } catch (error) {
                messageDiv.className = 'error';
                messageDiv.textContent = 'Connection error. Please try again.';
            }
        });
    </script>
</body>
</html>
```

### cURL
```bash
curl -X POST http://192.168.1.100/api/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com", "password":"mypassword"}'
```

### PHP (from another website)
```php
<?php
$apiUrl = 'http://192.168.1.100/api/login.php'; // Replace with your server IP

$data = [
    'email' => 'user@example.com',
    'password' => 'mypassword'
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($httpCode === 200) {
    echo "Login successful! Welcome, " . $result['user']['name'];
    // Start session and store user data
    session_start();
    $_SESSION['ymzm_user'] = $result['user'];
} else {
    echo "Login failed: " . $result['message'];
}
?>
```

### Python
```python
import requests

YMZM_API_URL = 'http://192.168.1.100/api'  # Replace with your server IP

def login_with_ymzm(email, password):
    response = requests.post(
        f'{YMZM_API_URL}/login.php',
        json={'email': email, 'password': password},
        headers={'Content-Type': 'application/json'}
    )
    
    data = response.json()
    
    if response.status_code == 200:
        print(f"Welcome, {data['user']['name']}!")
        return data['user']
    else:
        print(f"Login failed: {data['message']}")
        return None

# Usage
user = login_with_ymzm('user@example.com', 'mypassword')
```

---

## Network Setup

### Finding Your Server IP
Run this command in Command Prompt to find your local IP:
```cmd
ipconfig
```
Look for **IPv4 Address** under your active network adapter (e.g., `192.168.1.100`).

### Apache/XAMPP Configuration
Make sure your Apache server is configured to listen on all network interfaces:

1. Open `httpd.conf` (usually in `C:\xampp\apache\conf\`)
2. Ensure `Listen 80` is set (not `Listen 127.0.0.1:80`)
3. Restart Apache

### Windows Firewall
If other devices can't connect, you may need to allow Apache through Windows Firewall:

1. Open **Windows Defender Firewall**
2. Click **Allow an app or feature through Windows Defender Firewall**
3. Click **Change settings** → **Allow another app**
4. Browse to `C:\xampp\apache\bin\httpd.exe` and add it
5. Enable both **Private** and **Public** networks

---

## Security Considerations

1. **HTTPS in Production**: For production environments, always use HTTPS to encrypt credentials in transit.

2. **Rate Limiting**: Consider implementing rate limiting to prevent brute-force attacks.

3. **Token-Based Auth**: For enhanced security, consider implementing JWT tokens instead of returning user data directly.

4. **Network Restriction**: This API is designed for local network use. For public deployment, restrict CORS origins to specific trusted domains.
