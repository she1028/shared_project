<?php
require_once "connect.php";
header("Content-Type: application/json");

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


// Read JSON from SMS Forwarder
$raw = file_get_contents("php://input");
file_put_contents("sms_raw.log", date("Y-m-d H:i:s") . " " . $raw . PHP_EOL, FILE_APPEND);

$data = json_decode($raw, true);

if (!$data || empty($data['from']) || empty($data['text'])) {
    echo json_encode(["success" => false, "message" => "Invalid SMS payload"]);
    exit;
}

// Normalize phone
function normalizePhone($num) {
    $num = preg_replace('/\D/', '', $num);
    if (str_starts_with($num, '09')) {
        $num = '63' . substr($num, 1);
    }
    return '+' . $num;
}

$phone = normalizePhone($data['from']);
$text  = trim($data['text']);
$time = isset($data['receivedStamp']) 
    ? date("Y-m-d H:i:s", $data['receivedStamp'] / 1000) 
    : date("Y-m-d H:i:s");
file_put_contents("debug.log", "PHONE: ".$phone.PHP_EOL, FILE_APPEND);

// Get latest booking_ref for this phone
// (OTP first, fallback to pending booking)
$stmt = $conn->prepare("
    SELECT booking_ref 
    FROM otp_requests 
    WHERE phone = ? 
    ORDER BY otp_id DESC 
    LIMIT 1
");
$stmt->bind_param("s", $phone);
$stmt->execute();
$res = $stmt->get_result();
$row_booking = $res->fetch_assoc();
$booking_ref = $row_booking['booking_ref'] ?? null;

// If no OTP exists, fallback to latest PENDING booking
if (!$booking_ref) {
    $stmt = $conn->prepare("
        SELECT booking_ref 
        FROM bookings
        WHERE phone = ?
        AND booking_status = 'PENDING'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $booking_ref = $row['booking_ref'] ?? null;
}

// Log SMS to database
$stmt = $conn->prepare("
    INSERT INTO sms_logs (phone, booking_ref, sms_direction, sms_message, status)
    VALUES (?, ?, 'RECEIVED', ?, 'RECEIVED')
");
$stmt->bind_param("sss", $phone, $booking_ref, $text);
$stmt->execute();

// Check if user replied NO (cancel booking)
if (preg_match('/^\s*(no|n|nope)[\s\.\!\?]*$/i', $text)) {
    if ($booking_ref) {
        $stmt = $conn->prepare("
            UPDATE bookings
            SET booking_status = 'CANCELLED',
                cancelled_at = NOW()
            WHERE booking_ref = ?
        ");
        $stmt->bind_param("s", $booking_ref);
        $stmt->execute();

        // Optional: mark SMS log as caused cancellation
        $stmt = $conn->prepare("
            UPDATE sms_logs
            SET status = 'CANCELLED'
            WHERE phone = ? AND booking_ref = ? AND sms_direction = 'RECEIVED'
            ORDER BY sms_id DESC
            LIMIT 1
        ");
        $stmt->bind_param("ss", $phone, $booking_ref);
        $stmt->execute();

        echo json_encode([
            "success" => true,
            "booking_ref" => $booking_ref,
            "status" => "CANCELLED"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No active booking found for this phone"
        ]);
    }
    exit;
}

// Check for OTP in message (accept any form of yes/y/yeah/yea)
if (!preg_match('/^(?:\s*(?:yes|y|yeah|yea)\s*)?(\d{6})/i', $text, $m)) {
    echo json_encode(["success" => true, "message" => "SMS logged"]);
    exit;
}

$otp = $m[1];

// Get latest OTP request
$stmt = $conn->prepare("
    SELECT otp_id, booking_ref, otp_hash, expires_at 
    FROM otp_requests 
    WHERE phone = ? 
    ORDER BY otp_id DESC 
    LIMIT 1
");
$stmt->bind_param("s", $phone);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row) {
    echo json_encode(["success" => false, "message" => "No OTP found"]);
    exit;
}

// Check if OTP expired
if (strtotime($row['expires_at']) < time()) {
    echo json_encode(["success" => false, "message" => "OTP expired"]);
    exit;
}

// Verify OTP
if (!password_verify($otp, $row['otp_hash'])) {
    echo json_encode(["success" => false, "message" => "OTP incorrect"]);
    exit;
}

// Confirm booking
$stmt = $conn->prepare("
    UPDATE bookings
    SET booking_status = 'CONFIRMED',
        confirmed_at = NOW()
    WHERE booking_ref = ?
");
$stmt->bind_param("s", $row['booking_ref']);
$stmt->execute();

// Mark OTP as used
$stmt = $conn->prepare("
    UPDATE otp_requests 
    SET used = 1 
    WHERE otp_id = ?
");
$stmt->bind_param("i", $row['otp_id']);
$stmt->execute();

// Return response
echo json_encode([
    "success" => true,
    "booking_ref" => $row['booking_ref'],
    "status" => "CONFIRMED"
]);

$stmt = $conn->prepare("
    UPDATE bookings
    SET sms_sent_at = NOW()
    WHERE booking_ref = ?
");
$stmt->bind_param("s", $booking_ref);
$stmt->execute();