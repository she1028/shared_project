<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}

header('Content-Type: application/json');
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

$userId = $getUserId();

// Block guests from adding to cart
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please sign in to add items to cart.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Accept identifiers from food or rental payloads
$incomingId = $data['food_id'] ?? $data['item_id'] ?? $data['rental_id'] ?? $data['id'] ?? null;

if (!$data || !$incomingId || !isset($data['name'], $data['price'], $data['qty'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Basic qty validation
$incomingQty = (int)($data['qty'] ?? 0);
if ($incomingQty < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit;
}

// Load persistent cart for this user (once per session) so we don't mix users
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = $loadCartFromDb((int)$userId);
}

$cart = $_SESSION['cart'];
$cartLimit = 99;

// Normalize payload to keep legacy compatibility
$payload = [
    'id'         => $incomingId,
    'food_id'    => $incomingId, // keep key so existing views keep working
    'name'       => $data['name'],
    'price'      => (float) $data['price'],
    'qty'        => $incomingQty,
    'image'      => $data['image'] ?? '',
    'category'   => $data['category'] ?? '',
    'serving'    => $data['serving'] ?? '',
    'color_name' => $data['color_name'] ?? '',
    'color_id'   => $data['color_id'] ?? null,
    'color_stock'=> isset($data['color_stock']) ? (int)$data['color_stock'] : null,
    'type'       => $data['type'] ?? ($data['rental_id'] || $data['item_id'] ? 'rental' : 'food')
];

// Enforce rental stock (server-side) so users can't exceed available color stock.
if (($payload['type'] ?? '') === 'rental') {
    $maxStock = null;
    $colorId = $payload['color_id'];

    if ($colorId !== null && $colorId !== '') {
        $stmt = $conn->prepare('SELECT item_id, color_name, color_stock FROM rental_item_colors WHERE id = ? LIMIT 1');
        $cid = (int)$colorId;
        $stmt->bind_param('i', $cid);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!$row || (int)$row['item_id'] !== (int)$incomingId) {
            echo json_encode(['success' => false, 'message' => 'Invalid color selection']);
            exit;
        }

        $maxStock = (int)($row['color_stock'] ?? 0);
        $payload['color_name'] = $payload['color_name'] ?: ($row['color_name'] ?? '');
        $payload['color_stock'] = $maxStock;
    } else {
        // No color selected: fall back to item stock.
        $stmt = $conn->prepare('SELECT stock FROM rental_items WHERE id = ? LIMIT 1');
        $iid = (int)$incomingId;
        $stmt->bind_param('i', $iid);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Invalid rental item']);
            exit;
        }
        $maxStock = (int)($row['stock'] ?? 0);
    }

    if ($maxStock !== null && $maxStock <= 0) {
        echo json_encode(['success' => false, 'message' => 'Out of stock']);
        exit;
    }

    // Check against existing qty in cart for this same item+color.
    $existingQty = 0;
    foreach ($cart as $it) {
        $existingId = $it['id'] ?? $it['food_id'] ?? null;
        $existingColorId = $it['color_id'] ?? null;
        $existingColorName = $it['color_name'] ?? '';

        $colorMatches = ($colorId !== null && $existingColorId !== null)
            ? ((int)$existingColorId === (int)$colorId)
            : ($existingColorName === ($payload['color_name'] ?? ''));

        if ((string)$existingId === (string)$incomingId && $colorMatches) {
            $existingQty = (int)($it['qty'] ?? 0);
            break;
        }
    }

    if ($maxStock !== null && ($existingQty + $payload['qty']) > $maxStock) {
        $available = max(0, $maxStock - $existingQty);
        $cn = $payload['color_name'] ? (' in ' . $payload['color_name']) : '';
        $msg = $available > 0
            ? "Only {$available} more available{$cn}."
            : "You already have the maximum available{$cn}.";
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
}

// Merge quantities if item already exists
$found = false;
foreach ($cart as &$item) {
    $existingId = $item['id'] ?? $item['food_id'] ?? null;
    $existingColorId = $item['color_id'] ?? null;
    $existingColorName = $item['color_name'] ?? '';
    $incomingColorId = $payload['color_id'];
    $incomingColorName = $payload['color_name'];

    $colorMatches = ($incomingColorId !== null && $existingColorId !== null)
        ? ($existingColorId === $incomingColorId)
        : ($existingColorName === $incomingColorName);

    if ($existingId === $incomingId && $colorMatches) {
        $item['qty'] += $payload['qty'];
        $found = true;
        break;
    }
}
unset($item);

if (!$found) {
    if (count($cart) >= $cartLimit) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cart limit reached (99 items).']);
        exit;
    }
    $cart[] = $payload;
}

$_SESSION['cart'] = $cart;
$cartCount = min($cartLimit, count($cart));

// Persist cart for this user
try {
    $saveCartToDb((int)$userId, $cart);
} catch (Throwable $e) {
    // Do not block add-to-cart if persistence fails; log for later
    error_log('save cart failed: ' . $e->getMessage());
}

echo json_encode([
    'success' => true,
    'message' => "{$payload['qty']} x {$payload['name']} added to cart",
    'cart_count' => $cartCount
]);
