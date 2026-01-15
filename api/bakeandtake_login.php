<?php
/**
 * Bake & Take Login Proxy
 * 
 * This proxy handles authentication against the Bake & Take API.
 * Since the Bake & Take API only accepts requests from bakeandtake.xyz,
 * this server-side proxy makes the API call on behalf of the client.
 */

include("../connect.php");

if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($input['email']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Incomplete data. Provide email and password.'
    ]);
    exit;
}

$email = trim($input['email']);
$password = $input['password'];

// Make request to Bake & Take API
$apiUrl = 'https://bakeandtake.xyz/api/login.php';

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Origin: https://bakeandtake.xyz'
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'email' => $email,
        'password' => $password
    ]),
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Handle cURL errors
if ($curlError) {
    http_response_code(503);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unable to connect to Bake & Take authentication service.'
    ]);
    exit;
}

// Parse API response
$apiData = json_decode($response, true);

// If authentication failed, return the error
if ($httpCode !== 200 || ($apiData['status'] ?? '') !== 'success') {
    http_response_code($httpCode ?: 401);
    echo json_encode([
        'status' => 'error',
        'message' => $apiData['message'] ?? 'Invalid email or password.'
    ]);
    exit;
}

// Authentication successful - get user data from API response
$bakeAndTakeUser = $apiData['user'];

// Check if this Bake & Take user already exists in our database
$email = $conn->real_escape_string($bakeAndTakeUser['email']);
$checkQuery = "SELECT * FROM users WHERE email = '$email'";
$result = executeQuery($checkQuery);

if ($result && mysqli_num_rows($result) > 0) {
    // User exists - fetch their local data
    $localUser = mysqli_fetch_assoc($result);
    
    // Get user ID (handle different column names)
    $dbUserId = $localUser['userId'] ?? $localUser['userID'] ?? $localUser['id'] ?? null;
    
    // Don't allow admin accounts to login via Bake & Take
    if (($localUser['role'] ?? '') === 'admin') {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Admin accounts cannot use Bake & Take login.'
        ]);
        exit;
    }
    
    // Set session variables
    $_SESSION['userID'] = $dbUserId;
    $_SESSION['userId'] = $dbUserId;
    $_SESSION['user_id'] = $dbUserId;
    $_SESSION['name'] = $localUser['name'] ?? '';
    $_SESSION['role'] = $localUser['role'] ?? 'user';
    $_SESSION['email'] = $localUser['email'] ?? '';
    $_SESSION['bakeandtake_user'] = true;
    
} else {
    // User doesn't exist - create a new account
    $firstName = $conn->real_escape_string($bakeAndTakeUser['first_name'] ?? '');
    $lastName = $conn->real_escape_string($bakeAndTakeUser['last_name'] ?? '');
    $fullName = trim("$firstName $lastName");
    $phone = $conn->real_escape_string($bakeAndTakeUser['phone'] ?? '');
    $role = 'user';
    
    // Generate a random password hash (user will use Bake & Take to login)
    $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $fullName, $email, $randomPassword, $role);
    
    if ($stmt->execute()) {
        $newUserId = $stmt->insert_id ?: $conn->insert_id;
        
        // Set session variables
        $_SESSION['userID'] = $newUserId;
        $_SESSION['userId'] = $newUserId;
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['name'] = $fullName;
        $_SESSION['role'] = $role;
        $_SESSION['email'] = $email;
        $_SESSION['bakeandtake_user'] = true;
        
        $stmt->close();
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to create local account.'
        ]);
        exit;
    }
}

// Return success response
echo json_encode([
    'status' => 'success',
    'message' => 'Login successful.',
    'user' => [
        'id' => $_SESSION['userID'],
        'name' => $_SESSION['name'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role']
    ],
    'redirect' => 'index.php'
]);
