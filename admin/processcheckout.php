<?php
// Persist checkout details posted from checkout.php and items stored in the PHP session cart.
// The table names match the simple "orders" / "order_items" schema used by the existing checkout flow.

if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}
include "../connect.php"; // DB connection (one level up from /admin)

header('Content-Type: text/plain; charset=utf-8');

// Guests cannot place orders
$isLoggedIn = !empty($_SESSION['userID']) || !empty($_SESSION['userId']) || !empty($_SESSION['user_id']);
if (!$isLoggedIn) {
    echo 'error:not_logged_in';
    exit;
}

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

// Columns for client notification join/schema
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS client_email VARCHAR(255) NOT NULL DEFAULT ''");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS items JSON NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS total_amount DECIMAL(10,2) NULL");
try {
    $conn->query("UPDATE orders SET client_email = email WHERE (client_email = '' OR client_email IS NULL) AND email <> ''");
    $conn->query("UPDATE orders SET total_amount = total WHERE total_amount IS NULL");
} catch (mysqli_sql_exception $e) {
    // ignore
}

$conn->query("CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    item_type VARCHAR(20) NULL,
    item_ref VARCHAR(50) NULL,
    product_name VARCHAR(200) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,
    color_id INT NULL,
    color_name VARCHAR(100) NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$conn->query("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS line_total DECIMAL(12,2) NOT NULL DEFAULT 0.00");
$conn->query("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS item_type VARCHAR(20) NULL");
$conn->query("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS item_ref VARCHAR(50) NULL");
$conn->query("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS color_id INT NULL");
$conn->query("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS color_name VARCHAR(100) NULL");

// Notifications table for client-facing updates (needed by checkout flow even if admin page wasn't opened yet)
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending','paid','shipped') NOT NULL DEFAULT 'pending',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$conn->query("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
try {
    // Clean legacy rows that would violate the stricter schema
    $conn->query("DELETE FROM notifications WHERE order_id IS NULL OR order_id = 0");
    $conn->query("UPDATE notifications SET message = '' WHERE message IS NULL");
    $conn->query("UPDATE notifications SET status = 'pending' WHERE status IS NULL OR status NOT IN ('pending','paid','shipped')");

    $conn->query("ALTER TABLE notifications MODIFY COLUMN message TEXT NOT NULL");
    $conn->query("ALTER TABLE notifications MODIFY COLUMN status ENUM('pending','paid','shipped') NOT NULL DEFAULT 'pending'");
} catch (mysqli_sql_exception $e) {
    // ignore
}

$checkoutCart = $_SESSION['checkout_cart'] ?? null;
$usingCheckoutCart = is_array($checkoutCart);
$cart = $usingCheckoutCart ? $checkoutCart : ($_SESSION['cart'] ?? []);

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

    // Store client_email/items/total_amount for the simplified notification join/query
    try {
        $itemsForJson = [];
        foreach ($cart as $item) {
            $qty = isset($item['qty']) ? (int)$item['qty'] : 1;
            $productName = $item['name'] ?? ($item['product_name'] ?? '');
            $itemsForJson[] = ['item' => $productName, 'qty' => $qty];
        }
        $itemsJson = json_encode($itemsForJson, JSON_UNESCAPED_UNICODE);
        $syncStmt = $conn->prepare("UPDATE orders SET client_email = ?, items = ?, total_amount = ? WHERE id = ?");
        $syncStmt->bind_param('ssdi', $email, $itemsJson, $finalTotal, $order_id);
        $syncStmt->execute();
        $syncStmt->close();
    } catch (mysqli_sql_exception $e) {
        // ignore
    }

    // Create an initial notification for the client (best-effort; do not fail checkout if notifications aren't available)
    $notifyStmt = $conn->prepare("INSERT INTO notifications (order_id, message, status, is_read) VALUES (?,?,?,0)");
    if ($notifyStmt) {
        $initialMsg = "Order placed. Status: pending.";
        $notifyStmt->bind_param("iss", $order_id, $initialMsg, $status);
        $notifyStmt->execute();
        $notifyStmt->close();
    }

    // Insert items (supports both food + rentals, from the same session cart)
    foreach ($cart as $item) {
        $qty = isset($item['qty']) ? (int)$item['qty'] : 1;
        $price = isset($item['price']) ? (float)$item['price'] : 0.0;
        $lineTotal = $price * $qty;

        $productName = $item['name'] ?? ($item['product_name'] ?? '');
        $itemType = $item['type'] ?? null;
        $itemRef = $item['id'] ?? ($item['food_id'] ?? ($item['item_id'] ?? null));
        if ($itemType === null) {
            $itemType = isset($item['item_id']) ? 'rental' : 'food';
        }

        $colorId = isset($item['color_id']) && $item['color_id'] !== '' ? (int)$item['color_id'] : null;
        $colorName = $item['color_name'] ?? null;

        $stmtItem = $conn->prepare("
            INSERT INTO order_items (order_id, item_type, item_ref, product_name, quantity, price, line_total, color_id, color_name)
            VALUES (?,?,?,?,?,?,?,?,?)
        ");
        if (!$stmtItem) {
            continue;
        }
        $stmtItem->bind_param(
            "isssiddis",
            $order_id,
            $itemType,
            $itemRef,
            $productName,
            $qty,
            $price,
            $lineTotal,
            $colorId,
            $colorName
        );
        $stmtItem->execute();
        $stmtItem->close();
    }

    // Clear cart / selection
    if ($usingCheckoutCart) {
        // Remove only checked-out items from the main cart
        $remaining = $_SESSION['cart'] ?? [];

        $makeKey = function ($it) {
            $id = $it['id'] ?? ($it['food_id'] ?? ($it['item_id'] ?? ''));
            $colorId = $it['color_id'] ?? '';
            $colorName = $it['color_name'] ?? '';
            return (string)$id . '|' . (string)$colorId . '|' . (string)$colorName;
        };

        $removeKeys = [];
        foreach ($cart as $it) {
            $removeKeys[$makeKey($it)] = true;
        }

        $filtered = [];
        foreach ($remaining as $it) {
            if (!isset($removeKeys[$makeKey($it)])) {
                $filtered[] = $it;
            }
        }
        $_SESSION['cart'] = $filtered;
        unset($_SESSION['checkout_cart']);
    } else {
        unset($_SESSION['cart']);
    }

    echo "success";
    exit;
}

echo "error:invalid_request";


