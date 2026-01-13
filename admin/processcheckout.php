<?php
// Persist checkout details posted from checkout.php and items stored in the PHP session cart.
// The table names match the simple "orders" / "order_items" schema used by the existing checkout flow.

session_start();
include "../connect.php"; // DB connection (one level up from /admin)

header('Content-Type: text/plain; charset=utf-8');

// Ensure tables exist to avoid conflicts when seeding against a fresh DB
$conn->query("CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    contact VARCHAR(50) NOT NULL,
    email VARCHAR(150) NOT NULL,
    payment_method VARCHAR(30) NOT NULL,
    delivery_method VARCHAR(30) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    street VARCHAR(255) NULL,
    barangay VARCHAR(120) NULL,
    city VARCHAR(120) NULL,
    province VARCHAR(120) NULL,
    postal_code VARCHAR(20) NULL,
    notes TEXT NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    shipping DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Backfill columns on legacy tables if they exist without new fields
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'pending'");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping DECIMAL(12,2) NOT NULL DEFAULT 0.00");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS total DECIMAL(12,2) NOT NULL DEFAULT 0.00");

$conn->query("CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$conn->query("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS line_total DECIMAL(12,2) NOT NULL DEFAULT 0.00");

// Notifications table for client-facing updates (needed by checkout flow even if admin page wasn't opened yet)
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    recipient_email VARCHAR(150) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    message TEXT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$cart = $_SESSION['cart'] ?? [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = $_POST['name'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $email = $_POST['email'] ?? '';
    $payment = $_POST['payment'] ?? '';
    $delivery = $_POST['delivery'] ?? '';
    $street = $_POST['street'] ?? '';
    $barangay = $_POST['barangay'] ?? '';
    $city = $_POST['city'] ?? '';
    $province = $_POST['province'] ?? 'Batangas';
    $postal = $_POST['postal'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $shipping = isset($_POST['shipping']) ? (float) $_POST['shipping'] : 0.00;
    $clientTotal = isset($_POST['total']) ? (float) $_POST['total'] : 0.00;
    $status = 'pending';

        if (empty($cart)) {
            echo "error:empty_cart";
            exit;
        }

    // Calculate subtotal from session cart to validate client total
    $computedSubtotal = 0.0;
    foreach ($cart as $item) {
        $qty = isset($item['qty']) ? (int)$item['qty'] : 1;
        $price = isset($item['price']) ? (float)$item['price'] : 0.0;
        $computedSubtotal += ($price * $qty);
    }
    $computedTotal = $computedSubtotal + $shipping;

    // If client total mismatches computed, prefer computed to keep DB consistent
    $finalSubtotal = $computedSubtotal;
    $finalTotal = $computedTotal;

    $stmt = $conn->prepare("
        INSERT INTO orders 
        (full_name, contact, email, payment_method, delivery_method, status, street, barangay, city, province, postal_code, notes, subtotal, shipping, total)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

        $stmt->bind_param(
            "ssssssssssssddd",
            $name, $contact, $email, $payment, $delivery, $status,
            $street, $barangay, $city, $province, $postal, $notes,
            $finalSubtotal, $shipping, $finalTotal
        );

    $stmt->execute();
    $order_id = $stmt->insert_id;

    // Create an initial notification for the client (best-effort; do not fail checkout if notifications aren't available)
    $notifyStmt = $conn->prepare("INSERT INTO notifications (order_id, recipient_email, status, message) VALUES (?,?,?,?)");
    if ($notifyStmt) {
        $initialMsg = "Order placed. Status: pending.";
        $notifyStmt->bind_param("isss", $order_id, $email, $status, $initialMsg);
        $notifyStmt->execute();
        $notifyStmt->close();
    }

    // Insert items
    foreach ($cart as $item) {
        $qty = isset($item['qty']) ? (int)$item['qty'] : 1;
        $price = isset($item['price']) ? (float)$item['price'] : 0.0;
        $lineTotal = $price * $qty;

        $stmtItem = $conn->prepare("
            INSERT INTO order_items (order_id, product_name, quantity, price, line_total)
            VALUES (?,?,?,?,?)
        ");
            $stmtItem->bind_param(
                "isidd",
                $order_id,
                $item['name'],
                $qty,
                $price,
                $lineTotal
            );
            $stmtItem->execute();
            $stmtItem->close();
    }

    // Clear cart
    unset($_SESSION['cart']);

    echo "success";
    exit;
}

echo "error:invalid_request";


