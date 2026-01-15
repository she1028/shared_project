<?php
header('Content-Type: application/json; charset=utf-8');
include __DIR__ . '/../connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}

// Client-only
if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'forbidden']);
    exit;
}

$orderIdRaw = $_GET['order_id'] ?? '';
$orderId = (int)$orderIdRaw;
if ($orderId < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'invalid_order_id']);
    exit;
}

// Resolve current user's email
$email = trim($_SESSION['email'] ?? '');
if ($email === '') {
    $userId = $_SESSION['userID'] ?? ($_SESSION['userId'] ?? ($_SESSION['user_id'] ?? null));
    if ($userId) {
        $uid = (int)$userId;
        try {
            $stmtEmail = $conn->prepare('SELECT email FROM users WHERE userId = ? LIMIT 1');
            $stmtEmail->bind_param('i', $uid);
            $stmtEmail->execute();
            $resEmail = $stmtEmail->get_result();
            $rowEmail = $resEmail ? $resEmail->fetch_assoc() : null;
            $stmtEmail->close();
            if ($rowEmail && !empty($rowEmail['email'])) {
                $email = trim($rowEmail['email']);
            }
        } catch (mysqli_sql_exception $e) {
            // Fallback for legacy schema
            try {
                $stmtEmail = $conn->prepare('SELECT email FROM users WHERE userID = ? LIMIT 1');
                $stmtEmail->bind_param('i', $uid);
                $stmtEmail->execute();
                $resEmail = $stmtEmail->get_result();
                $rowEmail = $resEmail ? $resEmail->fetch_assoc() : null;
                $stmtEmail->close();
                if ($rowEmail && !empty($rowEmail['email'])) {
                    $email = trim($rowEmail['email']);
                }
            } catch (mysqli_sql_exception $e2) {
                // ignore
            }
        }
    }
}

if ($email === '') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

// Ensure order belongs to this user
$stmt = $conn->prepare('SELECT id, full_name, contact, email, client_email, payment_method, delivery_method, status, event_date, delivery_time, street, barangay, city, province, postal_code, notes, subtotal, shipping, total, created_at FROM orders WHERE id = ? AND (email = ? OR client_email = ?) LIMIT 1');
$stmt->bind_param('iss', $orderId, $email, $email);
$stmt->execute();
$res = $stmt->get_result();
$order = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$order) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'order_not_found']);
    exit;
}

$stmtItems = $conn->prepare('SELECT id, order_id, item_type, item_ref, product_name, quantity, price, line_total, color_id, color_name FROM order_items WHERE order_id = ? ORDER BY id ASC');
$stmtItems->bind_param('i', $orderId);
$stmtItems->execute();
$resItems = $stmtItems->get_result();
$items = [];
while ($row = $resItems->fetch_assoc()) {
    $items[] = $row;
}
$stmtItems->close();

echo json_encode(['success' => true, 'order' => $order, 'items' => $items]);
