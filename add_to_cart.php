<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}

header('Content-Type: application/json');

// Block guests from adding to cart
$isLoggedIn = !empty($_SESSION['userID']) || !empty($_SESSION['userId']) || !empty($_SESSION['user_id']);
if (!$isLoggedIn) {
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

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart = $_SESSION['cart'];

// Normalize payload to keep legacy compatibility
$payload = [
    'id'         => $incomingId,
    'food_id'    => $incomingId, // keep key so existing views keep working
    'name'       => $data['name'],
    'price'      => (float) $data['price'],
    'qty'        => (int) $data['qty'],
    'image'      => $data['image'] ?? '',
    'category'   => $data['category'] ?? '',
    'serving'    => $data['serving'] ?? '',
    'color_name' => $data['color_name'] ?? '',
    'color_id'   => $data['color_id'] ?? null,
    'color_stock'=> isset($data['color_stock']) ? (int)$data['color_stock'] : null,
    'type'       => $data['type'] ?? ($data['rental_id'] || $data['item_id'] ? 'rental' : 'food')
];

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
    $cart[] = $payload;
}

$_SESSION['cart'] = $cart;

echo json_encode([
    'success' => true,
    'message' => "{$payload['qty']} x {$payload['name']} added to cart"
]);
