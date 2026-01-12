<?php
session_start();

header('Content-Type: application/json');

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
    'id'       => $incomingId,
    'food_id'  => $incomingId, // keep key so existing views keep working
    'name'     => $data['name'],
    'price'    => (float) $data['price'],
    'qty'      => (int) $data['qty'],
    'image'    => $data['image'] ?? '',
    'category' => $data['category'] ?? '',
    'serving'  => $data['serving'] ?? '',
    'type'     => $data['type'] ?? ($data['rental_id'] || $data['item_id'] ? 'rental' : 'food')
];

// Merge quantities if item already exists
$found = false;
foreach ($cart as &$item) {
    $existingId = $item['id'] ?? $item['food_id'] ?? null;
    if ($existingId === $incomingId) {
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
