<?php
session_start();
include("../connect.php");

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth.php");
    exit();
}

// Ensure notifications table exists (keep it compatible even if orders table isn't created yet)
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending','paid','shipped') NOT NULL DEFAULT 'pending',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Backfill/upgrade legacy schema (best-effort)
$conn->query("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
try {
    // Clean legacy rows that would violate the stricter schema
    $conn->query("DELETE FROM notifications WHERE order_id IS NULL OR order_id = 0");
    $conn->query("UPDATE notifications SET message = '' WHERE message IS NULL");
    $conn->query("UPDATE notifications SET status = 'pending' WHERE status IS NULL OR status NOT IN ('pending','paid','shipped')");

    $conn->query("ALTER TABLE notifications MODIFY COLUMN order_id INT UNSIGNED NOT NULL");
    $conn->query("ALTER TABLE notifications MODIFY COLUMN message TEXT NOT NULL");
    $conn->query("ALTER TABLE notifications MODIFY COLUMN status ENUM('pending','paid','shipped') NOT NULL DEFAULT 'pending'");
} catch (mysqli_sql_exception $e) {
    // ignore
}

// Ensure orders has the minimal fields used for admin/client notification joins
try {
    $conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS client_email VARCHAR(255) NOT NULL DEFAULT ''");
    $conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS items JSON NULL");
    $conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS total_amount DECIMAL(10,2) NULL");
    $conn->query("UPDATE orders SET client_email = email WHERE (client_email = '' OR client_email IS NULL) AND email <> ''");
    $conn->query("UPDATE orders SET total_amount = total WHERE total_amount IS NULL");
} catch (mysqli_sql_exception $e) {
    // ignore
}

$statusOptions = ['pending', 'paid', 'shipped'];
$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_notification') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'pending');
        $message = trim($_POST['message'] ?? '');

        if (!in_array($status, $statusOptions, true)) {
            $status = 'pending';
        }

        if ($orderId <= 0) {
            $alert = ['type' => 'danger', 'text' => 'Order is required.'];
        } elseif ($message === '') {
            $alert = ['type' => 'danger', 'text' => 'Message is required.'];
        } else {
            $stmt = $conn->prepare("INSERT INTO notifications (order_id, message, status, is_read) VALUES (?,?,?,0)");
            $stmt->bind_param('iss', $orderId, $message, $status);
            $stmt->execute();
            $stmt->close();
            $alert = ['type' => 'success', 'text' => 'Notification created.'];
        }
    } elseif ($action === 'update_notification') {
        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? 'pending');
        $message = trim($_POST['message'] ?? '');
        if (!in_array($status, $statusOptions, true)) {
            $status = 'pending';
        }
        if ($id <= 0) {
            $alert = ['type' => 'danger', 'text' => 'Invalid notification id.'];
        } elseif ($message === '') {
            $alert = ['type' => 'danger', 'text' => 'Message is required.'];
        } else {
            $stmt = $conn->prepare("UPDATE notifications SET status=?, message=?, is_read=0 WHERE id=?");
            $stmt->bind_param('ssi', $status, $message, $id);
            $stmt->execute();
            $stmt->close();
            $alert = ['type' => 'success', 'text' => 'Notification updated.'];
        }
    } elseif ($action === 'delete_notification') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $alert = ['type' => 'success', 'text' => 'Notification deleted.'];
        }
    } elseif ($action === 'mark_unread') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 0 WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $alert = ['type' => 'success', 'text' => 'Notification marked as unread.'];
        }
    }
}

// Filters
$filterEmail = trim($_GET['email'] ?? '');
$filterOrderId = (int)($_GET['order_id'] ?? 0);

$where = [];
$params = [];
$types = '';
if ($filterEmail !== '') {
    $where[] = '(o.client_email = ? OR o.email = ?)';
    $params[] = $filterEmail;
    $params[] = $filterEmail;
    $types .= 'ss';
}
if ($filterOrderId > 0) {
    $where[] = 'n.order_id = ?';
    $params[] = $filterOrderId;
    $types .= 'i';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Fetch notifications
$notifications = [];
try {
     $sql = "SELECT n.id, n.order_id, n.status, n.message, n.is_read, n.created_at,
             n.updated_at,
             COALESCE(NULLIF(o.client_email,''), o.email) AS client_email,
             o.items,
             COALESCE(o.total_amount, o.total) AS total_amount,
             o.full_name
            FROM notifications n
         JOIN orders o ON o.id = n.order_id
            {$whereSql}
         ORDER BY n.updated_at DESC, n.created_at DESC
            LIMIT 500";

    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        // bind_param requires references
        $bind = [];
        $bind[] = $types;
        for ($i = 0; $i < count($params); $i++) {
            $bind[] = &$params[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    $notifications = [];
}

// Enrich items from order_items if orders.items is empty (best-effort)
$itemsByOrder = [];
try {
    $orderIds = [];
    foreach ($notifications as $n) {
        $orderIds[] = (int)$n['order_id'];
    }
    $orderIds = array_values(array_unique(array_filter($orderIds, fn($v) => $v > 0)));
    if (!empty($orderIds)) {
        $ph = implode(',', array_fill(0, count($orderIds), '?'));
        $t = str_repeat('i', count($orderIds));
        $stmt = $conn->prepare("SELECT order_id, product_name, quantity FROM order_items WHERE order_id IN ($ph) ORDER BY order_id, id");
        $bind = [];
        $bind[] = $t;
        for ($i = 0; $i < count($orderIds); $i++) {
            $bind[] = &$orderIds[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $oid = (int)$row['order_id'];
            $itemsByOrder[$oid][] = ['item' => $row['product_name'], 'qty' => (int)$row['quantity']];
        }
        $stmt->close();
    }
} catch (mysqli_sql_exception $e) {
    $itemsByOrder = [];
}

// Preload orders for dropdown (best-effort)
$orders = [];
try {
    $res = $conn->query("SELECT id, full_name, COALESCE(NULLIF(client_email,''), email) AS client_email FROM orders ORDER BY created_at DESC LIMIT 500");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $orders[] = $row;
        }
    }
} catch (mysqli_sql_exception $e) {
    $orders = [];
}

function formatItemsForDisplay(?string $itemsJsonOrText, array $fallbackItems = []): array {
    $items = [];

    if ($itemsJsonOrText !== null && trim($itemsJsonOrText) !== '') {
        $decoded = json_decode($itemsJsonOrText, true);
        if (is_array($decoded)) {
            // Expected formats:
            // 1) [{"item":"Pizza","qty":2}, ...]
            // 2) [{"product_name":"Pizza","quantity":2}, ...]
            foreach ($decoded as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $name = $row['item'] ?? ($row['product_name'] ?? ($row['name'] ?? ''));
                $qty = $row['qty'] ?? ($row['quantity'] ?? 1);
                $name = is_string($name) ? trim($name) : '';
                $qty = (int)$qty;
                if ($name !== '') {
                    $items[] = ['name' => $name, 'qty' => max(1, $qty)];
                }
            }
        }
    }

    if (empty($items) && !empty($fallbackItems)) {
        foreach ($fallbackItems as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = $row['item'] ?? ($row['product_name'] ?? '');
            $qty = $row['qty'] ?? ($row['quantity'] ?? 1);
            $name = is_string($name) ? trim($name) : '';
            $qty = (int)$qty;
            if ($name !== '') {
                $items[] = ['name' => $name, 'qty' => max(1, $qty)];
            }
        }
    }

    $lines = [];
    foreach ($items as $it) {
        $lines[] = sprintf('%d× %s', (int)$it['qty'], $it['name']);
    }
    return $lines;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <style>
        /* Keep wide tables inside the card and allow horizontal scrolling */
        .table-scroll-x {
            overflow-x: auto;
            max-width: 100%;
            -webkit-overflow-scrolling: touch;
        }

        .table-scroll-x table {
            min-width: 1200px;
        }

        /* Make the Items column readable */
        .items-cell {
            white-space: normal;
            word-break: normal;
            overflow-wrap: anywhere;
            line-height: 1.25;
        }
    </style>
</head>
<body>
<div class="bg-blur"></div>
<div class="container mt-4">
    <a href="dashboard.php" class="btn btn-secondary mb-3">Back</a>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="m-0">Client Notifications</h2>
    </div>

    <?php if ($alert): ?>
        <div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $alert['text']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-12">
            <div class="card card-admin shadow p-3">
                <h5 class="mb-3">Send Notification</h5>
                <form method="post" class="d-flex flex-column gap-2">
                    <input type="hidden" name="action" value="send_notification">

                    <label class="form-label m-0">Order (optional)</label>
                    <select class="form-select" name="order_id">
                        <option value="0">Select an order</option>
                        <?php foreach ($orders as $o): ?>
                            <option value="<?php echo (int)$o['id']; ?>">
                                #<?php echo (int)$o['id']; ?> — <?php echo htmlspecialchars($o['full_name']); ?> (<?php echo htmlspecialchars($o['client_email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label m-0">Status</label>
                    <select class="form-select" name="status" required>
                        <?php foreach ($statusOptions as $opt): ?>
                            <option value="<?php echo $opt; ?>"><?php echo ucfirst($opt); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label m-0">Message (optional)</label>
                    <textarea class="form-control" name="message" rows="3" placeholder="Write a note for the customer..." required></textarea>

                    <button type="submit" class="btn btn-primary">Send</button>
                </form>
            </div>
        </div>

        <div class="col-12">
            <div class="card card-admin shadow p-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="m-0">Notification History</h5>
                    <form method="get" class="d-flex gap-2 flex-wrap">
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($filterEmail); ?>" placeholder="Filter by email" style="width: 220px;">
                        <input type="number" class="form-control" name="order_id" value="<?php echo $filterOrderId > 0 ? $filterOrderId : ''; ?>" placeholder="Order #" style="width: 140px;">
                        <button class="btn btn-outline-secondary" type="submit">Filter</button>
                        <a class="btn btn-outline-secondary" href="notifications.php">Reset</a>
                    </form>
                </div>

                <div class="table-responsive mt-3 table-scroll-x">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Order</th>
                            <th>Client Email</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Message</th>
                            <th>Read</th>
                            <th>Created</th>
                            <th>Updated</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($notifications)): ?>
                            <?php foreach ($notifications as $n): ?>
                                <tr>
                                    <td><?php echo (int)$n['id']; ?></td>
                                    <td>
                                        <?php if (!empty($n['order_id'])): ?>
                                            #<?php echo (int)$n['order_id']; ?>
                                            <?php if (!empty($n['full_name'])): ?>
                                                <div class="small text-muted"><?php echo htmlspecialchars($n['full_name']); ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($n['client_email'] ?? ''); ?></td>
                                    <td style="max-width: 260px;">
                                        <?php
                                            $oid = (int)$n['order_id'];
                                            $fallback = isset($itemsByOrder[$oid]) ? $itemsByOrder[$oid] : [];
                                            $lines = formatItemsForDisplay($n['items'] ?? null, $fallback);
                                        ?>
                                        <div class="small text-muted items-cell">
                                            <?php if (!empty($lines)): ?>
                                                <?php foreach ($lines as $line): ?>
                                                    <div><?php echo htmlspecialchars($line); ?></div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>₱<?php echo number_format((float)($n['total_amount'] ?? 0), 2); ?></td>
                                    <td><span class="badge text-uppercase bg-secondary"><?php echo htmlspecialchars($n['status']); ?></span></td>
                                    <td style="max-width: 260px;">
                                        <div class="small"><?php echo htmlspecialchars($n['message'] ?? ''); ?></div>
                                    </td>
                                    <td><?php echo ((int)$n['is_read'] === 1) ? 'Yes' : 'No'; ?></td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($n['created_at'])); ?></td>
                                    <td><?php echo !empty($n['updated_at']) ? date('M d, Y h:i A', strtotime($n['updated_at'])) : '—'; ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <form method="post" class="d-flex gap-2">
                                                <input type="hidden" name="action" value="update_notification">
                                                <input type="hidden" name="id" value="<?php echo (int)$n['id']; ?>">
                                                <select name="status" class="form-select form-select-sm" style="width:130px;">
                                                    <?php foreach ($statusOptions as $opt): ?>
                                                        <option value="<?php echo $opt; ?>" <?php echo ($n['status'] === $opt) ? 'selected' : ''; ?>><?php echo ucfirst($opt); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="text" name="message" class="form-control form-control-sm" value="<?php echo htmlspecialchars($n['message'] ?? '', ENT_QUOTES); ?>" style="width:220px;" required>
                                                <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                                            </form>
                                            <form method="post" onsubmit="return confirm('Delete this notification?');">
                                                <input type="hidden" name="action" value="delete_notification">
                                                <input type="hidden" name="id" value="<?php echo (int)$n['id']; ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                            </form>
                                            <form method="post">
                                                <input type="hidden" name="action" value="mark_unread">
                                                <input type="hidden" name="id" value="<?php echo (int)$n['id']; ?>">
                                                <button class="btn btn-sm btn-outline-secondary" type="submit">Mark Unread</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="11" class="text-muted">No notifications found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
