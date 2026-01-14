<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('admin_session');
    session_start();
}
include("../connect.php");

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../adminsignin.php");
    exit();
}

// Ensure tables/columns exist for management UI
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
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'pending'");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping DECIMAL(12,2) NOT NULL DEFAULT 0.00");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS total DECIMAL(12,2) NOT NULL DEFAULT 0.00");

// Columns for client notification join/schema
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS client_email VARCHAR(255) NOT NULL DEFAULT ''");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS items JSON NULL");
$conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS total_amount DECIMAL(10,2) NULL");
// Keep new columns synced for existing rows (best-effort)
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

// Notifications table for client-facing updates
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

// Backfill/upgrade legacy notifications schema if it exists
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

$alert = null;
$statusOptions = ['pending', 'paid', 'shipped'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Common inputs
    $fullName = trim($_POST['full_name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $payment = trim($_POST['payment_method'] ?? '');
    $delivery = trim($_POST['delivery_method'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? 'Batangas');
    $postal = trim($_POST['postal_code'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $subtotal = isset($_POST['subtotal']) ? (float)$_POST['subtotal'] : 0.00;
    $shipping = isset($_POST['shipping']) ? (float)$_POST['shipping'] : 0.00;
    $total = isset($_POST['total']) ? (float)$_POST['total'] : 0.00;
    $status = $_POST['status'] ?? 'pending';
    $notifyMessage = trim($_POST['notify_message'] ?? '');
    if (!in_array($status, $statusOptions, true)) {
        $status = 'pending';
    }

    if ($action === 'add_order') {
        $stmt = $conn->prepare("INSERT INTO orders (full_name, contact, email, payment_method, delivery_method, status, street, barangay, city, province, postal_code, notes, subtotal, shipping, total) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param(
            "ssssssssssssddd",
            $fullName, $contact, $email, $payment, $delivery, $status,
            $street, $barangay, $city, $province, $postal, $notes,
            $subtotal, $shipping, $total
        );
        if ($stmt->execute()) {
            $alert = ['type' => 'success', 'text' => 'Order added.'];
            $newOrderId = $stmt->insert_id;

            // Keep client_email/total_amount in sync (best-effort)
            try {
                $syncStmt = $conn->prepare("UPDATE orders SET client_email = ?, total_amount = ? WHERE id = ?");
                $syncStmt->bind_param('sdi', $email, $total, $newOrderId);
                $syncStmt->execute();
                $syncStmt->close();
            } catch (mysqli_sql_exception $e) {
                // ignore
            }

            // Create/update client notification for this order (email is derived from orders.client_email)
            $noteText = $notifyMessage !== '' ? $notifyMessage : "Order created. Status: {$status}.";
            $notifStmt = $conn->prepare("INSERT INTO notifications (order_id, message, status, is_read) VALUES (?,?,?,0)");
            $notifStmt->bind_param("iss", $newOrderId, $noteText, $status);
            $notifStmt->execute();
            $notifStmt->close();
            $alert['text'] .= ' Notification created.';
        } else {
            $alert = ['type' => 'danger', 'text' => 'Unable to add order.'];
        }
        $stmt->close();
    } elseif ($action === 'edit_order') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE orders SET full_name=?, contact=?, email=?, payment_method=?, delivery_method=?, status=?, street=?, barangay=?, city=?, province=?, postal_code=?, notes=?, subtotal=?, shipping=?, total=? WHERE id=?");
        $stmt->bind_param(
            "ssssssssssssdddi",
            $fullName, $contact, $email, $payment, $delivery, $status,
            $street, $barangay, $city, $province, $postal, $notes,
            $subtotal, $shipping, $total, $orderId
        );
        if ($stmt->execute()) {
            $alert = ['type' => 'success', 'text' => 'Order updated.'];

            // Keep client_email/total_amount in sync (best-effort)
            try {
                $syncStmt = $conn->prepare("UPDATE orders SET client_email = ?, total_amount = ? WHERE id = ?");
                $syncStmt->bind_param('sdi', $email, $total, $orderId);
                $syncStmt->execute();
                $syncStmt->close();
            } catch (mysqli_sql_exception $e) {
                // ignore
            }
        } else {
            $alert = ['type' => 'danger', 'text' => 'Unable to update order.'];
        }
        $stmt->close();
    } elseif ($action === 'delete_order') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM orders WHERE id=?");
        $stmt->bind_param("i", $orderId);
        if ($stmt->execute()) {
            $alert = ['type' => 'success', 'text' => 'Order deleted.'];
        } else {
            $alert = ['type' => 'danger', 'text' => 'Unable to delete order.'];
        }
        $stmt->close();
    } elseif ($action === 'update_status') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $orderId);
        if ($stmt->execute()) {
            $alert = ['type' => 'success', 'text' => 'Status updated.'];

            $finalMessage = $notifyMessage !== '' ? $notifyMessage : "Status updated to {$status}.";

            // Update the most recent notification for this order; if none exists, insert one.
            $updStmt = $conn->prepare("UPDATE notifications SET status=?, message=?, is_read=0 WHERE order_id=? ORDER BY updated_at DESC, created_at DESC LIMIT 1");
            $updStmt->bind_param("ssi", $status, $finalMessage, $orderId);
            $updStmt->execute();
            $affected = $updStmt->affected_rows;
            $updStmt->close();

            if ($affected === 0) {
                $insStmt = $conn->prepare("INSERT INTO notifications (order_id, message, status, is_read) VALUES (?,?,?,0)");
                $insStmt->bind_param("iss", $orderId, $finalMessage, $status);
                $insStmt->execute();
                $insStmt->close();
            }

            $alert['text'] .= ' Notification updated.';
        } else {
            $alert = ['type' => 'danger', 'text' => 'Unable to update status.'];
        }
        $stmt->close();
    }
}

// Fetch orders with aggregated item data
$sql = "
SELECT 
    o.id,
    o.full_name,
    o.contact,
    o.email,
    o.payment_method,
    o.delivery_method,
    o.status,
    o.street,
    o.barangay,
    o.city,
    o.province,
    o.postal_code,
    o.notes,
    IFNULL(o.subtotal, 0) AS subtotal,
    IFNULL(o.shipping, 0) AS shipping,
    IFNULL(o.total, 0) AS total,
    o.created_at,
    COUNT(oi.id) AS item_count,
    COALESCE(SUM(oi.line_total), 0) AS computed_total
FROM orders o
LEFT JOIN order_items oi ON oi.order_id = o.id
GROUP BY o.id
ORDER BY o.created_at DESC
";

$orders = $conn->query($sql);

// Preload items per order for quick display
$itemRows = $conn->query("SELECT order_id, product_name, quantity, price, line_total FROM order_items ORDER BY order_id, id");
$orderItems = [];
if ($itemRows) {
    while ($row = $itemRows->fetch_assoc()) {
        $orderItems[(int)$row['order_id']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="bg-blur"></div>
<div class="container mt-4">
    <a href="dashboard.php" class="btn btn-secondary mb-3">Back</a>
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="m-0">Orders</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addOrderModal">Add Order</button>
    </div>
    <?php if ($alert): ?>
        <div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($alert['text']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="card card-admin shadow w-100 p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Payment</th>
                        <th>Delivery</th>
                        <th>Status</th>
                        <th>Subtotal</th>
                        <th>Shipping</th>
                        <th>Total</th>
                        <th>Items</th>
                        <th>Created</th>
                        <th>Details</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders && $orders->num_rows > 0): ?>
                        <?php while ($o = $orders->fetch_assoc()): $oid = (int)$o['id']; ?>
                            <tr>
                                <td><?php echo $oid; ?></td>
                                <td class="text-start">
                                    <div class="fw-semibold"><?php echo htmlspecialchars($o['full_name']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($o['email']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($o['contact']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars(ucfirst($o['payment_method'])); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($o['delivery_method'])); ?></td>
                                <td>
                                    <?php
                                    $badge = 'secondary';
                                    if ($o['status'] === 'pending') $badge = 'warning';
                                    if ($o['status'] === 'paid') $badge = 'success';
                                    if ($o['status'] === 'shipped') $badge = 'info';
                                    ?>
                                    <span class="badge bg-<?php echo $badge; ?> text-uppercase"><?php echo htmlspecialchars($o['status']); ?></span>
                                </td>
                                <td>₱<?php echo number_format((float)$o['subtotal'], 2); ?></td>
                                <td>₱<?php echo number_format((float)$o['shipping'], 2); ?></td>
                                <td>₱<?php echo number_format((float)$o['total'], 2); ?></td>
                                <td><?php echo (int)$o['item_count']; ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($o['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#items-<?php echo $oid; ?>">View</button>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <button
                                            class="btn btn-sm btn-outline-secondary edit-order"
                                            data-id="<?php echo $oid; ?>"
                                            data-full_name="<?php echo htmlspecialchars($o['full_name'], ENT_QUOTES); ?>"
                                            data-contact="<?php echo htmlspecialchars($o['contact'], ENT_QUOTES); ?>"
                                            data-email="<?php echo htmlspecialchars($o['email'], ENT_QUOTES); ?>"
                                            data-payment_method="<?php echo htmlspecialchars($o['payment_method'], ENT_QUOTES); ?>"
                                            data-delivery_method="<?php echo htmlspecialchars($o['delivery_method'], ENT_QUOTES); ?>"
                                            data-status="<?php echo htmlspecialchars($o['status'], ENT_QUOTES); ?>"
                                            data-street="<?php echo htmlspecialchars($o['street'], ENT_QUOTES); ?>"
                                            data-barangay="<?php echo htmlspecialchars($o['barangay'], ENT_QUOTES); ?>"
                                            data-city="<?php echo htmlspecialchars($o['city'], ENT_QUOTES); ?>"
                                            data-province="<?php echo htmlspecialchars($o['province'], ENT_QUOTES); ?>"
                                            data-postal_code="<?php echo htmlspecialchars($o['postal_code'], ENT_QUOTES); ?>"
                                            data-notes="<?php echo htmlspecialchars($o['notes'], ENT_QUOTES); ?>"
                                            data-subtotal="<?php echo (float)$o['subtotal']; ?>"
                                            data-shipping="<?php echo (float)$o['shipping']; ?>"
                                            data-total="<?php echo (float)$o['total']; ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editOrderModal"
                                        >Edit</button>
                                        <form method="post" onsubmit="return confirm('Delete this order?');">
                                            <input type="hidden" name="action" value="delete_order">
                                            <input type="hidden" name="order_id" value="<?php echo $oid; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                        <form method="post" class="d-flex gap-1 align-items-center flex-wrap">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="order_id" value="<?php echo $oid; ?>">
                                            <select name="status" class="form-select form-select-sm">
                                                <?php foreach ($statusOptions as $opt): ?>
                                                    <option value="<?php echo $opt; ?>" <?php echo $o['status'] === $opt ? 'selected' : ''; ?>><?php echo ucfirst($opt); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="text" name="notify_message" class="form-control form-control-sm" placeholder="Message (optional)">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Update</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse" id="items-<?php echo $oid; ?>">
                                <td colspan="12" class="text-start">
                                    <div class="fw-semibold mb-2">Items</div>
                                    <?php if (!empty($orderItems[$oid])): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th class="text-center">Qty</th>
                                                        <th class="text-end">Price</th>
                                                        <th class="text-end">Line Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($orderItems[$oid] as $it): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($it['product_name']); ?></td>
                                                            <td class="text-center"><?php echo (int)$it['quantity']; ?></td>
                                                            <td class="text-end">₱<?php echo number_format((float)$it['price'], 2); ?></td>
                                                            <td class="text-end">₱<?php echo number_format((float)$it['line_total'], 2); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-muted">No items found.</div>
                                    <?php endif; ?>
                                    <div class="small text-muted">Computed total: ₱<?php echo number_format((float)$o['computed_total'], 2); ?></div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="12" class="text-muted">No orders yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Order Modal -->
<div class="modal fade" id="addOrderModal" tabindex="-1" aria-labelledby="addOrderLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addOrderLabel">Add Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" class="modal-body">
                <input type="hidden" name="action" value="add_order">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact</label>
                        <input type="text" class="form-control" name="contact" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Payment</label>
                        <select class="form-select" name="payment_method" required>
                            <option value="" disabled selected>Select</option>
                            <option value="full">Full Payment</option>
                            <option value="cash">Cash</option>
                            <option value="paypal">PayPal</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Delivery</label>
                        <select class="form-select" name="delivery_method" required>
                            <option value="" disabled selected>Select</option>
                            <option value="ship">Ship</option>
                            <option value="pickup">Pickup</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <?php foreach ($statusOptions as $opt): ?>
                                <option value="<?php echo $opt; ?>"><?php echo ucfirst($opt); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Notify Message (optional)</label>
                        <input type="text" class="form-control" name="notify_message" placeholder="Message to send with status">
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Street</label>
                        <input type="text" class="form-control" name="street">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Barangay</label>
                        <input type="text" class="form-control" name="barangay">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input type="text" class="form-control" name="city">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Province</label>
                        <input type="text" class="form-control" name="province" value="Batangas">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Postal Code</label>
                        <input type="text" class="form-control" name="postal_code">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Notes</label>
                        <input type="text" class="form-control" name="notes">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Subtotal</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="subtotal" value="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Shipping</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="shipping" value="0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Total</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="total" value="0" required>
                    </div>
                </div>
                <div class="text-end mt-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Order Modal -->
<div class="modal fade" id="editOrderModal" tabindex="-1" aria-labelledby="editOrderLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editOrderLabel">Edit Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" class="modal-body" id="editOrderForm">
                <input type="hidden" name="action" value="edit_order">
                <input type="hidden" name="order_id" id="edit-order-id">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name" id="edit-full-name" required readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact</label>
                        <input type="text" class="form-control" name="contact" id="edit-contact" required readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="edit-email" required readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Payment</label>
                        <input type="text" class="form-control" name="payment_method" id="edit-payment" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Delivery</label>
                        <input type="text" class="form-control" name="delivery_method" id="edit-delivery" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="edit-status" required>
                            <?php foreach ($statusOptions as $opt): ?>
                                <option value="<?php echo $opt; ?>"><?php echo ucfirst($opt); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Street</label>
                        <input type="text" class="form-control" name="street" id="edit-street" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Barangay</label>
                        <input type="text" class="form-control" name="barangay" id="edit-barangay" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input type="text" class="form-control" name="city" id="edit-city" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Province</label>
                        <input type="text" class="form-control" name="province" id="edit-province" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Postal Code</label>
                        <input type="text" class="form-control" name="postal_code" id="edit-postal" readonly>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Notes</label>
                        <input type="text" class="form-control" name="notes" id="edit-notes" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Subtotal</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="subtotal" id="edit-subtotal" required readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Shipping</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="shipping" id="edit-shipping" required readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Total</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="total" id="edit-total" required readonly>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Notify Message (optional)</label>
                        <input type="text" class="form-control" name="notify_message" placeholder="Message to send with status">
                    </div>
                </div>
                <div class="text-end mt-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const editButtons = document.querySelectorAll('.edit-order');
    const mapValue = (el, val) => { if (el) el.value = val ?? ''; };

    editButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            mapValue(document.getElementById('edit-order-id'), btn.dataset.id);
            mapValue(document.getElementById('edit-full-name'), btn.dataset.full_name);
            mapValue(document.getElementById('edit-contact'), btn.dataset.contact);
            mapValue(document.getElementById('edit-email'), btn.dataset.email);
            mapValue(document.getElementById('edit-payment'), btn.dataset.payment_method);
            mapValue(document.getElementById('edit-delivery'), btn.dataset.delivery_method);
            mapValue(document.getElementById('edit-status'), btn.dataset.status);
            mapValue(document.getElementById('edit-street'), btn.dataset.street);
            mapValue(document.getElementById('edit-barangay'), btn.dataset.barangay);
            mapValue(document.getElementById('edit-city'), btn.dataset.city);
            mapValue(document.getElementById('edit-province'), btn.dataset.province);
            mapValue(document.getElementById('edit-postal'), btn.dataset.postal_code);
            mapValue(document.getElementById('edit-notes'), btn.dataset.notes);
            mapValue(document.getElementById('edit-subtotal'), parseFloat(btn.dataset.subtotal || 0).toFixed(2));
            mapValue(document.getElementById('edit-shipping'), parseFloat(btn.dataset.shipping || 0).toFixed(2));
            mapValue(document.getElementById('edit-total'), parseFloat(btn.dataset.total || 0).toFixed(2));
        });
    });
});
</script>
</body>
</html>
