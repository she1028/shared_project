<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $idToRemove = $_POST['id'];
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $key => $item) {
            $existingId = $item['id'] ?? $item['food_id'] ?? null;
            if ($existingId == $idToRemove) {
                unset($_SESSION['cart'][$key]);
                // Reindex array
                $_SESSION['cart'] = array_values($_SESSION['cart']);
                break;
            }
        }
    }
}

// Redirect back to cart
header('Location: cart.php');
exit;
