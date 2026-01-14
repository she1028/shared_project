<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
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

// Redirect back to cart
header('Location: cart.php');
exit;
