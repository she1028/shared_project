<?php
/**
 * YMZM User Authentication API
 * 
 * This API allows external applications to authenticate users against the YMZM user database.
 * Enables "Login with YMZM" functionality across different platforms on the local network.
 * 
 * Endpoint: /api/login.php
 * Method: POST
 * Content-Type: application/json
 */

// Allow requests from any origin (for local network usage)
// Get the origin of the request
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';

// Set CORS headers to allow requests from any origin on the network
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400"); // Cache preflight for 24 hours
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit();
}

// Include database connection
require_once __DIR__ . '/../connect.php';

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate input
if (!$data || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Incomplete data. Provide email and password.'
    ]);
    exit();
}

$email = trim($data['email']);
$password = $data['password'];

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid email format.'
    ]);
    exit();
}

try {
    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Get user ID (handle different column name variations)
            $userId = null;
            if (isset($user['userId'])) $userId = $user['userId'];
            elseif (isset($user['userID'])) $userId = $user['userID'];
            elseif (isset($user['user_id'])) $userId = $user['user_id'];
            elseif (isset($user['id'])) $userId = $user['id'];
            else {
                // Fallback: find any id-like field
                foreach ($user as $k => $v) {
                    if (stripos($k, 'id') !== false && is_numeric($v)) {
                        $userId = $v;
                        break;
                    }
                }
            }

            // Return success response with user data (exclude sensitive info)
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Login successful.',
                'user' => [
                    'id' => (int)$userId,
                    'name' => $user['name'] ?? '',
                    'email' => $user['email'],
                    'role' => $user['role'] ?? 'user',
                    'created_at' => $user['created_at'] ?? null
                ]
            ]);
        } else {
            // Invalid password
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid email or password.'
            ]);
        }
    } else {
        // User not found
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid email or password.'
        ]);
    }

    $stmt->close();
} catch (Exception $e) {
    // Database error
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An internal error occurred. Please try again later.'
    ]);
    // Log the actual error (don't expose to client)
    error_log("YMZM API Login Error: " . $e->getMessage());
}

$conn->close();
?>
