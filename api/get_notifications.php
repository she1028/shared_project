<?php
header('Content-Type: application/json');
include __DIR__ . '/../connect.php';

$email = isset($_GET['email']) ? trim($_GET['email']) : '';
if ($email === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'missing_email']);
    exit;
}

$stmt = $conn->prepare("SELECT id, order_id, status, message, is_read, created_at FROM notifications WHERE recipient_email = ? ORDER BY created_at DESC");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
$stmt->close();

echo json_encode(['success' => true, 'notifications' => $rows]);
