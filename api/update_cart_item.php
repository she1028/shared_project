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

$saveCartToDb = function ($userId, $cart) use ($conn, $ensureCartTable) {
    $ensureCartTable();
    $json = json_encode(array_values($cart ?? []));
    $stmt = $conn->prepare('INSERT INTO user_carts (user_id, cart_json, updated_at) VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE cart_json = VALUES(cart_json), updated_at = VALUES(updated_at)');
    $stmt->bind_param('is', $userId, $json);
    $stmt->execute();
    $stmt->close();
};

$userId = $getUserId();
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not signed in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$incomingId = $input['id'] ?? null;
$colorId = $input['color_id'] ?? null;
$colorName = $input['color_name'] ?? '';
$newQty = isset($input['qty']) ? (int)$input['qty'] : 0;

if (!$incomingId || $newQty < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}

$cartLimit = 99;

// Refresh cart from DB to ensure latest state
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = $loadCartFromDb((int)$userId);
}
$cart = $_SESSION['cart'];

$found = false;
foreach ($cart as &$item) {
    $existingId = $item['id'] ?? $item['food_id'] ?? null;
    $existingColorId = $item['color_id'] ?? null;
    $existingColorName = $item['color_name'] ?? '';

    $colorMatches = ($colorId !== null && $existingColorId !== null)
        ? ((string)$existingColorId === (string)$colorId)
        : ((string)$existingColorName === (string)$colorName);

    if ((string)$existingId === (string)$incomingId && $colorMatches) {
        $item['qty'] = max(1, min($cartLimit, $newQty));
        $found = true;
        break;
    }
}
unset($item);

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
    exit;
}

$_SESSION['cart'] = $cart;
try {
    $saveCartToDb((int)$userId, $cart);
} catch (Throwable $e) {
    error_log('update cart qty failed: ' . $e->getMessage());
}

$cartCount = min($cartLimit, is_array($cart) ? count($cart) : 0);

echo json_encode(['success' => true, 'cart_count' => $cartCount]);
