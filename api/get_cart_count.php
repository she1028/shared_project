<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}

require_once __DIR__ . '/../connect.php';

$getUserId = function () {
    return $_SESSION['userID'] ?? $_SESSION['userId'] ?? $_SESSION['user_id'] ?? null;
};

$ensureCartTable = function () use ($conn) {
    static $ready = false;
    if ($ready) return;
    $sql = "CREATE TABLE IF NOT EXISTS user_carts (
        user_id INT NOT NULL PRIMARY KEY,
        cart_json LONGTEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
    $ready = true;
};

$loadCartFromDb = function ($userId) use ($conn, $ensureCartTable) {
    $ensureCartTable();
    $stmt = $conn->prepare('SELECT cart_json FROM user_carts WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$row) return [];
    $decoded = json_decode($row['cart_json'], true);
    return is_array($decoded) ? $decoded : [];
};

$userId = $getUserId();
if ($userId) {
    $_SESSION['cart'] = $loadCartFromDb((int)$userId);
}

$cartLimit = 99;
$cart = $_SESSION['cart'] ?? [];
$cartCount = is_array($cart) ? min($cartLimit, count($cart)) : 0;

echo json_encode(['success' => true, 'cart_count' => $cartCount]);
