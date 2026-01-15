<?php
header('Content-Type: application/json');
include __DIR__ . '/../connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}

// Client-only: admins should not use client notifications
if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'forbidden']);
    exit;
}

// Use the logged-in user's email automatically
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
