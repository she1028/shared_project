<?php
header('Content-Type: application/json');
include __DIR__ . '/../connect.php';

$email = isset($_GET['email']) ? trim($_GET['email']) : '';
if ($email === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'missing_email']);
    exit;
}

$stmt = $conn->prepare("SELECT n.id, n.order_id, n.status, n.message, n.is_read, n.created_at, n.updated_at\nFROM notifications n\nJOIN orders o ON n.order_id = o.id\nWHERE (o.client_email = ? OR o.email = ?)\nORDER BY n.updated_at DESC, n.created_at DESC");
$stmt->bind_param('ss', $email, $email);
$stmt->execute();
$result = $stmt->get_result();
$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
$stmt->close();

echo json_encode(['success' => true, 'notifications' => $rows]);
