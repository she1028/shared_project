    <?php
    // Persist checkout details posted from checkout.php and items stored in the PHP session cart.
    // The table names match the simple "orders" / "order_items" schema used by the existing checkout flow.

    if (session_status() === PHP_SESSION_NONE) {
        session_name('client_session');
        session_start();
    }
    include "../connect.php"; // DB connection (one level up from /admin)

    header('Content-Type: application/json; charset=utf-8');


    // Guests cannot place orders
    $isLoggedIn = !empty($_SESSION['userID']) || !empty($_SESSION['userId']) || !empty($_SESSION['user_id']);
    if (!$isLoggedIn) {
        echo 'error:not_logged_in';
        exit;
    }

    // Require SMS confirmation for this checkout session
    $smsConfirmed = !empty($_SESSION['sms_confirmed'])
        && !empty($_SESSION['sms_booking_ref'])
        && !empty($_SESSION['sms_phone'])
        && !empty($_SESSION['sms_confirmed_at'])
        && (time() - (int)$_SESSION['sms_confirmed_at'] <= 15 * 60);

    if (!$smsConfirmed) {
        echo 'error:sms_not_confirmed';
        exit;
    }

    $bookingRef = (string)($_SESSION['sms_booking_ref'] ?? '');
    $smsPhone = (string)($_SESSION['sms_phone'] ?? '');
    if ($bookingRef === '' || $smsPhone === '') {
        echo 'error:sms_not_confirmed';
        exit;
    }

    // Verify the booking is actually confirmed in DB
    try {
        $stmtSms = $conn->prepare("SELECT booking_status FROM bookings WHERE booking_ref = ? AND phone = ? LIMIT 1");
        if ($stmtSms) {
            $stmtSms->bind_param('ss', $bookingRef, $smsPhone);
            $stmtSms->execute();
            $resSms = $stmtSms->get_result();
            $rowSms = $resSms ? $resSms->fetch_assoc() : null;
            $statusSms = strtoupper((string)($rowSms['booking_status'] ?? ''));
            if ($statusSms !== 'CONFIRMED') {
                echo 'error:sms_not_confirmed';
                exit;
            }
        }
    } catch (mysqli_sql_exception $e) {
        echo 'error:sms_not_confirmed';
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
        event_date DATE NULL,
        delivery_time TIME NULL,
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
    $conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS event_date DATE NULL");
    $conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_time TIME NULL");
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

    $tableHasColumn = function (mysqli $conn, string $table, string $column): bool {
        try {
            $dbRes = $conn->query('SELECT DATABASE() AS db');
            $dbRow = $dbRes ? $dbRes->fetch_assoc() : null;
            $dbName = (string)($dbRow['db'] ?? '');
            if ($dbName === '') return false;

            $stmt = $conn->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
            if (!$stmt) return false;
            $stmt->bind_param('sss', $dbName, $table, $column);
            $stmt->execute();
            $res = $stmt->get_result();
            $ok = $res && $res->fetch_row();
            $stmt->close();
            return (bool)$ok;
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    };

    $checkoutCart = $_SESSION['checkout_cart'] ?? null;
    $usingCheckoutCart = is_array($checkoutCart);
    $cart = $usingCheckoutCart ? $checkoutCart : ($_SESSION['cart'] ?? []);

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $name = $_POST['name'] ?? '';
        $contact = $_POST['contact'] ?? '';
        $email = $_POST['email'] ?? '';

        // Link this order to the logged-in account email for notifications/payment access.
        // Users sometimes type a different email in the form; we still want the logged-in user
        // to see their order in the notifications widget and on the payment page.
        $clientEmail = '';
        if (!empty($_SESSION['email'])) {
            $clientEmail = trim((string)$_SESSION['email']);
        }
        if ($clientEmail === '') {
            $userId = $_SESSION['userID'] ?? ($_SESSION['userId'] ?? ($_SESSION['user_id'] ?? null));
            if ($userId) {
                try {
                    $stmtEmail = $conn->prepare('SELECT email FROM users WHERE userId = ? LIMIT 1');
                    if ($stmtEmail) {
                        $uid = (int)$userId;
                        $stmtEmail->bind_param('i', $uid);
                        $stmtEmail->execute();
                        $resEmail = $stmtEmail->get_result();
                        $rowEmail = $resEmail ? $resEmail->fetch_assoc() : null;
                        $stmtEmail->close();
                        if ($rowEmail && !empty($rowEmail['email'])) {
                            $clientEmail = trim((string)$rowEmail['email']);
                        }
                    }
                } catch (mysqli_sql_exception $e) {
                    // Fallback for legacy schema
                    try {
                        $stmtEmail = $conn->prepare('SELECT email FROM users WHERE userID = ? LIMIT 1');
                        if ($stmtEmail) {
                            $uid = (int)$userId;
                            $stmtEmail->bind_param('i', $uid);
                            $stmtEmail->execute();
                            $resEmail = $stmtEmail->get_result();
                            $rowEmail = $resEmail ? $resEmail->fetch_assoc() : null;
                            $stmtEmail->close();
                            if ($rowEmail && !empty($rowEmail['email'])) {
                                $clientEmail = trim((string)$rowEmail['email']);
                            }
                        }
                    } catch (mysqli_sql_exception $e2) {
                        // ignore
                    }
                }
            }
        }
        if ($clientEmail === '') {
            // Fallback to whatever the user typed.
            $clientEmail = trim((string)$email);
        }
        $payment = $_POST['payment'] ?? '';

        // Only allow Cash or PayPal from client checkout.
        // Backward-compat: older UI used "full" which maps to PayPal.
        if ($payment === 'full') {
            $payment = 'paypal';
        }
        if ($payment !== 'cash' && $payment !== 'paypal') {
            echo 'error:invalid_payment_method';
            exit;
        }
        $delivery = $_POST['delivery'] ?? '';
        $eventDate = trim((string)($_POST['event_date'] ?? ''));
        if ($eventDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
            $eventDate = '';
        }

        $deliveryTime = trim((string)($_POST['delivery_time'] ?? ''));
        if ($deliveryTime !== '' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $deliveryTime)) {
            $deliveryTime = '';
        }
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
            echo "error: empty_cart";
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

        // Begin transaction so stock updates + order insert stay consistent.
        $conn->begin_transaction();

        // Validate and reserve stock for rentals (decrement only after validation).
        foreach ($cart as $item) {
            $itemType = $item['type'] ?? null;
            if ($itemType === null) {
                $itemType = isset($item['item_id']) ? 'rental' : 'food';
            }
            if ($itemType !== 'rental') {
                continue;
            }

            $qty = isset($item['qty']) ? (int)$item['qty'] : 1;
            if ($qty < 1) $qty = 1;

            $itemId = $item['id'] ?? ($item['item_id'] ?? null);
            $itemId = $itemId !== null ? (int)$itemId : null;

            $colorId = isset($item['color_id']) && $item['color_id'] !== '' ? (int)$item['color_id'] : null;

            if ($itemId === null) {
                $conn->rollback();
                echo 'Invalid rental item.';
                exit;
            }

            if ($colorId !== null) {
                // Atomically decrement color stock if enough remains.
                $upd = $conn->prepare('UPDATE rental_item_colors SET color_stock = color_stock - ? WHERE id = ? AND item_id = ? AND color_stock >= ?');
                if (!$upd) {
                    $conn->rollback();
                    echo 'Unable to reserve rental stock.';
                    exit;
                }
                $upd->bind_param('iiii', $qty, $colorId, $itemId, $qty);
                $upd->execute();
                $affected = $upd->affected_rows;
                $upd->close();

                if ($affected < 1) {
                    $conn->rollback();
                    echo 'Some rental items are out of stock. Please update your cart and try again.';
                    exit;
                }
            } else {
                // Fallback: decrement item-level stock.
                $upd = $conn->prepare('UPDATE rental_items SET stock = stock - ? WHERE id = ? AND stock >= ?');
                if (!$upd) {
                    $conn->rollback();
                    echo 'Unable to reserve rental stock.';
                    exit;
                }
                $upd->bind_param('iii', $qty, $itemId, $qty);
                $upd->execute();
                $affected = $upd->affected_rows;
                $upd->close();

                if ($affected < 1) {
                    $conn->rollback();
                    echo 'Some rental items are out of stock. Please update your cart and try again.';
                    exit;
                }
            }
        }

        $stmt = $conn->prepare("
            INSERT INTO orders 
            (full_name, contact, email, payment_method, delivery_method, status, event_date, delivery_time, street, barangay, city, province, postal_code, notes, subtotal, shipping, total)
            VALUES (?,?,?,?,?,?,NULLIF(?,''),NULLIF(?,''),?,?,?,?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "ssssssssssssssddd",
            $name,
            $contact,
            $email,
            $payment,
            $delivery,
            $status,
            $eventDate,
            $deliveryTime,
            $street,
            $barangay,
            $city,
            $province,
            $postal,
            $notes,
            $finalSubtotal,
            $shipping,
            $finalTotal
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
            $syncStmt->bind_param('ssdi', $clientEmail, $itemsJson, $finalTotal, $order_id);
            $syncStmt->execute();
            $syncStmt->close();
        } catch (mysqli_sql_exception $e) {
            // ignore
        }

        // Create an initial notification for the client (best-effort; support both schemas)
        $initialMsg = "Order placed. Status: pending.";
        if ($tableHasColumn($conn, 'notifications', 'order_id')) {
            $notifyStmt = $conn->prepare("INSERT INTO notifications (order_id, message, status, is_read) VALUES (?,?,?,0)");
            if ($notifyStmt) {
                $notifyStmt->bind_param("iss", $order_id, $initialMsg, $status);
                $notifyStmt->execute();
                $notifyStmt->close();
            }
        } elseif ($tableHasColumn($conn, 'notifications', 'or_derid') && $tableHasColumn($conn, 'notifications', 'recipient_email')) {
            $notifyStmt = $conn->prepare("INSERT INTO notifications (or_derid, recipient_email, message, status, is_read) VALUES (?,?,?,?,0)");
            if ($notifyStmt) {
                $notifyStmt->bind_param('isss', $order_id, $clientEmail, $initialMsg, $status);
                $notifyStmt->execute();
                $notifyStmt->close();
            }
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

        $conn->commit();

        unset($_SESSION['sms_confirmed'], $_SESSION['sms_booking_ref'], $_SESSION['sms_phone'], $_SESSION['sms_confirmed_at']);
        unset($_SESSION['checkout_event_date']);
        unset($_SESSION['checkout_delivery_time']);

        echo json_encode([
            "status"   => "success",
            "order_id" => $order_id,
            "total"    => $finalTotal,
            "payment"  => $payment
        ]);
        exit;
    }

    echo "error:invalid_request";
