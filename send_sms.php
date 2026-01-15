<?php
header("Content-Type: application/json");
// Avoid emitting PHP warnings/notices into JSON responses
ini_set('display_errors', 0);

$raw = file_get_contents("php://input");
file_put_contents("debug_raw.txt", $raw);

require_once "connect.php"; 

if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}

// Ensure required tables exist (for fresh DBs)
$conn->query("CREATE TABLE IF NOT EXISTS bookings (
    booking_ref VARCHAR(32) PRIMARY KEY,
    phone VARCHAR(30) NOT NULL,
    booking_status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmed_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    sms_sent_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$conn->query("CREATE TABLE IF NOT EXISTS otp_requests (
    otp_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(30) NOT NULL,
    booking_ref VARCHAR(32) NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone_created (phone, created_at),
    INDEX idx_booking (booking_ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$conn->query("CREATE TABLE IF NOT EXISTS sms_logs (
    sms_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(30) NOT NULL,
    booking_ref VARCHAR(32) NULL,
    sms_direction VARCHAR(20) NOT NULL,
    sms_message TEXT NOT NULL,
    status VARCHAR(30) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_booking (booking_ref),
    INDEX idx_phone_created (phone, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Normalize phone
function normalizePhone($num) {
    $num = preg_replace('/\D/', '', $num);
    if (str_starts_with($num, '09')) {
        $num = '63' . substr($num, 1);
    }
    return '+' . $num;
}

// Read JSON input (php://input should be read once)
$data = json_decode($raw ?: '', true);
if (!$data || empty($data['phone'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

$phone = normalizePhone($data['phone']);

$forceNewBooking = !empty($data['force_new_booking']);

// Booking reference
// Default behavior: create a new booking per OTP request.
// If caller passes booking_ref and does not request a new booking, reuse it.
if (!$forceNewBooking && isset($data['booking_ref']) && !empty($data['booking_ref'])) {
    $booking_ref = (string)$data['booking_ref'];
} else {
    // Generate & insert a unique booking reference (retry on collision)
    $booking_ref = '';
    $created = false;
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $candidate = 'BOOK-' . strtoupper(bin2hex(random_bytes(3))); // e.g., BOOK-1A2B3C
        $stmt_booking = $conn->prepare("
            INSERT INTO bookings (booking_ref, phone, booking_status)
            VALUES (?, ?, 'PENDING')
        ");
        if (!$stmt_booking) {
            echo json_encode(["success" => false, "message" => "Failed to create booking"]);
            exit;
        }
        $stmt_booking->bind_param("ss", $candidate, $phone);
        if (@$stmt_booking->execute()) {
            $booking_ref = $candidate;
            $created = true;
            break;
        }
    }

    if (!$created) {
        echo json_encode(["success" => false, "message" => "Failed to create booking"]);
        exit;
    }
}

// Generate OTP
$otp = rand(100000, 999999);
$otp_hash = password_hash($otp, PASSWORD_DEFAULT);
$expires_at = date("Y-m-d H:i:s", time() + 300); // 5 minutes

// Save OTP in DB
$stmt = $conn->prepare("
    INSERT INTO otp_requests (phone, booking_ref, otp_hash, expires_at)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("ssss", $phone, $booking_ref, $otp_hash, $expires_at);

if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "Failed to save OTP"]);
    exit;
}

// SMS Gateway Settings
$gateway_url = "http://192.168.1.14:8080";
$username = "sms";
$password = "_1u9epAr";

$message = "Booking Ref: $booking_ref\nReply YES $otp to confirm.";

$payload = [
    "phoneNumbers" => [$phone],
    "message" => $message
];

$options = [
    "http" => [
        "method" => "POST",
        "header" => [
            "Content-Type: application/json",
            "Authorization: Basic " . base64_encode("$username:$password")
        ],
        "content" => json_encode($payload),
        "timeout" => 10
    ]
];

$context = stream_context_create($options);
$response = @file_get_contents(rtrim($gateway_url, '/') . "/messages", false, $context);

// Log SMS in DB
$status = $response !== false ? "SENT" : "FAILED";

$stmt_log = $conn->prepare("
    INSERT INTO sms_logs (phone, booking_ref, sms_direction, sms_message, status)
    VALUES (?, ?, 'SENT', ?, ?)
");
$stmt_log->bind_param("ssss", $phone, $booking_ref, $message, $status);
$stmt_log->execute();

// Return response
if ($response === false) {
    echo json_encode(["success" => false, "message" => "SMS Gateway unreachable", "booking_ref" => $booking_ref]);
} else {
    // Keep track of the current booking ref for this checkout attempt
    $_SESSION['sms_booking_ref'] = $booking_ref;
    $_SESSION['sms_phone'] = $phone;
    echo json_encode(["success" => true, "booking_ref" => $booking_ref]);
}
