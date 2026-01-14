<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);
file_put_contents("debug_raw.txt", file_get_contents("php://input"));

require_once "connect.php"; 

// Normalize phone
function normalizePhone($num) {
    $num = preg_replace('/\D/', '', $num);
    if (str_starts_with($num, '09')) {
        $num = '63' . substr($num, 1);
    }
    return '+' . $num;
}

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);
if (!$data || empty($data['phone'])) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

$phone = normalizePhone($data['phone']);

// Booking reference
if (!isset($data['booking_ref']) || empty($data['booking_ref'])) {
    // Generate a unique booking reference
    $booking_ref = 'BOOK-' . strtoupper(bin2hex(random_bytes(3))); // e.g., BOOK-1A2B3C

    // Insert new booking into bookings table
    $stmt_booking = $conn->prepare("
        INSERT INTO bookings (booking_ref, phone, booking_status)
        VALUES (?, ?, 'PENDING')
    ");
    $stmt_booking->bind_param("ss", $booking_ref, $phone);
    if (!$stmt_booking->execute()) {
        echo json_encode(["success" => false, "message" => "Failed to create booking"]);
        exit;
    }
} else {
    $booking_ref = $data['booking_ref'];
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
$gateway_url = "http://192.168.1.7:8080";
$username = "sms";
$password = "esTLpEP4";

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
    echo json_encode(["success" => false, "message" => "SMS Gateway unreachable"]);
} else {
    echo json_encode(["success" => true]);
}
