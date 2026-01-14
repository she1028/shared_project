<?php
include __DIR__ . '/../connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}

// Client-only: admins should not use client notifications
if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    http_response_code(403);
    echo 'forbidden';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'method_not_allowed';
    exit;
}

$idsRaw = isset($_POST['ids']) ? trim($_POST['ids']) : '';

$email = trim($_SESSION['email'] ?? '');
if ($email === '') {
    $userId = $_SESSION['userID'] ?? ($_SESSION['userId'] ?? ($_SESSION['user_id'] ?? null));
    if ($userId) {
        $stmtEmail = $conn->prepare('SELECT email FROM users WHERE userID = ? LIMIT 1');
        $stmtEmail->bind_param('i', $userId);
        $stmtEmail->execute();
        $resEmail = $stmtEmail->get_result();
        $rowEmail = $resEmail ? $resEmail->fetch_assoc() : null;
        $stmtEmail->close();
        if ($rowEmail && !empty($rowEmail['email'])) {
            $email = trim($rowEmail['email']);
        }
    }
}

if ($email === '') {
    http_response_code(401);
    echo 'not_logged_in';
    exit;
}

if ($idsRaw === '') {
    $stmt = $conn->prepare("UPDATE notifications n\nJOIN orders o ON n.order_id = o.id\nSET n.is_read = 1\nWHERE (o.client_email = ? OR o.email = ?)");
    $stmt->bind_param('ss', $email, $email);
    $stmt->execute();
    $stmt->close();
    echo 'ok';
    exit;
}

$idParts = array_filter(array_map('intval', explode(',', $idsRaw)), function ($n) { return $n > 0; });
if (empty($idParts)) {
    echo 'ok';
    exit;
}

$placeholders = implode(',', array_fill(0, count($idParts), '?'));
$types = str_repeat('i', count($idParts));
$sql = "UPDATE notifications n\nJOIN orders o ON n.order_id = o.id\nSET n.is_read = 1\nWHERE (o.client_email = ? OR o.email = ?) AND n.id IN ($placeholders)";
$stmt = $conn->prepare($sql);
$params = array_merge([$email, $email], $idParts);

// mysqli_stmt::bind_param requires references
$bind = [];
$bind[] = 'ss' . $types;
for ($i = 0; $i < count($params); $i++) {
    $bind[] = &$params[$i];
}
call_user_func_array([$stmt, 'bind_param'], $bind);
$stmt->execute();
$stmt->close();

echo 'ok';
