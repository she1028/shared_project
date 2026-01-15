<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}

$cart = $_SESSION['cart'] ?? [];
$cartCount = 0;
if (is_array($cart)) {
    foreach ($cart as $it) {
        $cartCount += (int)($it['qty'] ?? 1);
    }
}

echo json_encode(['success' => true, 'cart_count' => $cartCount]);
