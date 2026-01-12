<?php
session_start(); // ✅ Must be at the top

header('Content-Type: application/json');

// Get POST data (JSON)
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['food_id'], $data['name'], $data['price'], $data['qty'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart = $_SESSION['cart'];

// Check if item already exists in cart
$found = false;
foreach ($cart as &$item) {
    if ($item['food_id'] === $data['food_id']) {
        $item['qty'] += $data['qty']; // increase quantity
        $found = true;
        break;
    }
}
unset($item);

if (!$found) {
    $cart[] = $data; // add new item
}

$_SESSION['cart'] = $cart;

echo json_encode([
    'success' => true,
    'message' => "{$data['qty']} x {$data['name']} added to cart"
]);
