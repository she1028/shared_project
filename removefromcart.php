<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}

require_once __DIR__ . '/connect.php';

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

// Load persistent cart if not already in session
$userId = $getUserId();
if ($userId && !isset($_SESSION['cart'])) {
    $_SESSION['cart'] = $loadCartFromDb((int)$userId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $idToRemove = $_POST['id'];
    $colorIdToRemove = $_POST['color_id'] ?? null;
    $colorNameToRemove = $_POST['color_name'] ?? '';
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $item) {
            $existingId = $item['id'] ?? $item['food_id'] ?? null;
            $existingColorId = $item['color_id'] ?? null;
            $existingColorName = $item['color_name'] ?? '';

            $colorMatches = ($colorIdToRemove !== null && $existingColorId !== null)
                ? ($existingColorId == $colorIdToRemove)
                : ($existingColorName === $colorNameToRemove);

            if ($existingId == $idToRemove && $colorMatches) {
                unset($_SESSION['cart'][$key]);
                // Reindex array
                $_SESSION['cart'] = array_values($_SESSION['cart']);
                break;
            }
        }
    }
}

if ($userId) {
    try {
        $saveCartToDb((int)$userId, $_SESSION['cart'] ?? []);
    } catch (Throwable $e) {
        error_log('save cart failed: ' . $e->getMessage());
    }
}

// Redirect back to cart
header('Location: cart.php');
exit;
