<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
require_once 'connect.php';

function normalizePhone(string $num): string {
    $num = preg_replace('/\D/', '', $num);
    if (str_starts_with($num, '09')) {
        $num = '63' . substr($num, 1);
    }
    return '+' . $num;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$booking_ref = trim((string)($data['booking_ref'] ?? ''));
$phone = trim((string)($data['phone'] ?? ''));
if ($booking_ref === '' || $phone === '') {
    echo json_encode(['success' => false, 'message' => 'Missing booking_ref or phone']);
    exit;
}

$phoneNorm = normalizePhone($phone);

$stmt = $conn->prepare("SELECT booking_status FROM bookings WHERE booking_ref = ? AND phone = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}
$stmt->bind_param('ss', $booking_ref, $phoneNorm);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit;
}

if (strtoupper((string)$row['booking_status']) !== 'CONFIRMED') {
    echo json_encode(['success' => false, 'message' => 'Booking not confirmed']);
    exit;
}

$_SESSION['sms_confirmed'] = true;
$_SESSION['sms_booking_ref'] = $booking_ref;
$_SESSION['sms_phone'] = $phoneNorm;
$_SESSION['sms_confirmed_at'] = time();

echo json_encode(['success' => true]);
