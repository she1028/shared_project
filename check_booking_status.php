<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'connect.php';

function normalizePhone(string $num): string {
    $num = preg_replace('/\D/', '', $num);
    if (str_starts_with($num, '09')) {
        $num = '63' . substr($num, 1);
    }
    return '+' . $num;
}

// Accept JSON POST or query params
$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
if (!is_array($data)) {
    $data = [];
}

$booking_ref = $data['booking_ref'] ?? ($_GET['booking_ref'] ?? '');
$phone = $data['phone'] ?? ($_GET['phone'] ?? '');
$booking_ref = trim((string)$booking_ref);
$phone = trim((string)$phone);

if ($booking_ref === '') {
    echo json_encode(['success' => false, 'message' => 'Missing booking_ref']);
    exit;
}

// Optional phone check to avoid leaking status
$wherePhone = '';
$params = [$booking_ref];
$types = 's';
if ($phone !== '') {
    $wherePhone = ' AND phone = ?';
    $params[] = normalizePhone($phone);
    $types .= 's';
}

$stmt = $conn->prepare("SELECT booking_ref, phone, booking_status, confirmed_at, cancelled_at, created_at FROM bookings WHERE booking_ref = ?{$wherePhone} LIMIT 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'booking_ref' => $row['booking_ref'],
    'status' => $row['booking_status'],
    'confirmed_at' => $row['confirmed_at'],
    'cancelled_at' => $row['cancelled_at'],
    'created_at' => $row['created_at'],
]);
