<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}

require_once __DIR__ . '/connect.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$error = null;
$order = null;
$items = [];
$latestNotif = null;

// Resolve the current user's email (primary gate for viewing orders)
$email = trim($_SESSION['email'] ?? '');
if ($email === '') {
    $userId = $_SESSION['userID'] ?? ($_SESSION['userId'] ?? ($_SESSION['user_id'] ?? null));
    if ($userId) {
        $uid = (int)$userId;
        try {
            $stmtEmail = $conn->prepare('SELECT email FROM users WHERE userId = ? LIMIT 1');
            $stmtEmail->bind_param('i', $uid);
            $stmtEmail->execute();
            $resEmail = $stmtEmail->get_result();
            $rowEmail = $resEmail ? $resEmail->fetch_assoc() : null;
            $stmtEmail->close();
            if ($rowEmail && !empty($rowEmail['email'])) {
                $email = trim($rowEmail['email']);
            }
        } catch (mysqli_sql_exception $e) {
            try {
                $stmtEmail = $conn->prepare('SELECT email FROM users WHERE userID = ? LIMIT 1');
                $stmtEmail->bind_param('i', $uid);
                $stmtEmail->execute();
                $resEmail = $stmtEmail->get_result();
                $rowEmail = $resEmail ? $resEmail->fetch_assoc() : null;
                $stmtEmail->close();
                if ($rowEmail && !empty($rowEmail['email'])) {
                    $email = trim($rowEmail['email']);
                }
            } catch (mysqli_sql_exception $e2) {
                // ignore
            }
        }
    }
}

if ($orderId < 1) {
    $error = 'Invalid order id.';
} elseif ($email === '') {
    $error = 'Please sign in to view your order details.';
} else {
    // Fetch order belonging to this user
    $stmt = $conn->prepare('SELECT id, full_name, contact, email, client_email, payment_method, delivery_method, status, event_date, delivery_time, street, barangay, city, province, postal_code, notes, subtotal, shipping, total, created_at FROM orders WHERE id = ? AND (email = ? OR client_email = ?) LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('iss', $orderId, $email, $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $order = $res ? $res->fetch_assoc() : null;
        $stmt->close();
    }

    if ($order) {
        // Items
        $stmtItems = $conn->prepare('SELECT product_name, quantity, price, line_total, color_name FROM order_items WHERE order_id = ? ORDER BY id ASC');
        if ($stmtItems) {
            $stmtItems->bind_param('i', $orderId);
            $stmtItems->execute();
            $resItems = $stmtItems->get_result();
            while ($row = $resItems->fetch_assoc()) {
                $items[] = $row;
            }
            $stmtItems->close();
        }

        // Latest notification
        $stmtNotif = $conn->prepare('SELECT message, status, created_at, updated_at FROM notifications WHERE order_id = ? ORDER BY updated_at DESC, created_at DESC LIMIT 1');
        if ($stmtNotif) {
            $stmtNotif->bind_param('i', $orderId);
            $stmtNotif->execute();
            $resNotif = $stmtNotif->get_result();
            $latestNotif = $resNotif ? $resNotif->fetch_assoc() : null;
            $stmtNotif->close();
        }
    } else {
        $error = 'Order not found for your account.';
    }
}

function h(?string $v): string {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}

function money($n): string {
    return number_format((float)$n, 2);
}

function formatDate(?string $val, string $fmt = 'M d, Y h:i A'): string {
    if (!$val) return '';
    $ts = strtotime($val);
    return $ts ? date($fmt, $ts) : '';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f7f7f9; font-size: 13px; line-height: 1.3; }
        .page-shell { max-width: 760px; margin: 18px auto 14px; padding: 0 10px; min-height: auto; }
        .card { border: none; box-shadow: 0 4px 14px rgba(0,0,0,0.06); padding: 10px !important; }
        .pill { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.78rem; }
        .pill.pending { background: #fff3cd; color: #856404; }
        .pill.paid { background: #d1e7dd; color: #0f5132; }
        .pill.shipped { background: #cff4fc; color: #055160; }
        h3, h5, h6 { margin-bottom: 4px; }
        .card .row.g-2 { row-gap: 4px; }
        .table { margin-bottom: 6px; font-size: 13px; }
        .table th, .table td { padding: 6px 8px; }
        .card + .card { margin-top: 10px; }
    </style>
</head>
<body>
<div class="page-shell">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">Order Details</h5>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-secondary" href="index.php?show=notifications">Back to notifications</a>
            <a class="btn btn-sm btn-outline-secondary" href="index.php">Home</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php if ($email === ''): ?>
            <a class="btn btn-primary" href="auth.php?next=<?php echo urlencode('order_details.php?order_id=' . $orderId); ?>">Sign in</a>
        <?php endif; ?>
    <?php elseif (!$order): ?>
        <div class="alert alert-warning">Order could not be loaded.</div>
    <?php else: ?>
        <div class="card mb-2 p-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <div class="text-muted">Order #<?php echo (int)$order['id']; ?></div>
                    <h5 class="mb-1"><?php echo h($order['full_name']); ?></h5>
                    <div class="text-muted">Placed: <?php echo h(formatDate($order['created_at'])); ?></div>
                </div>
                <div class="text-end">
                    <span class="pill <?php echo h($order['status']); ?> text-uppercase"><?php echo h($order['status']); ?></span>
                    <?php if ($order['event_date'] || $order['delivery_time']): ?>
                        <div class="text-muted mt-1">Schedule: <?php echo h(trim(($order['event_date'] ?? '') . ' ' . ($order['delivery_time'] ?? ''))); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($latestNotif): ?>
            <div class="card mb-2 p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted">Latest message</div>
                        <div class="fw-semibold"><?php echo h($latestNotif['message'] ?? ''); ?></div>
                        <div class="text-muted small">Status: <?php echo h(strtoupper($latestNotif['status'] ?? '')); ?></div>
                    </div>
                    <div class="text-end text-muted small">
                        <div>Created: <?php echo h(formatDate($latestNotif['created_at'] ?? '')); ?></div>
                        <div>Updated: <?php echo h(formatDate($latestNotif['updated_at'] ?? '')); ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card mb-2 p-3">
            <h6>Contact & Delivery</h6>
            <div class="row g-2">
                <div class="col-md-6"><div class="text-muted">Email</div><div><?php echo h($order['email'] ?: $order['client_email']); ?></div></div>
                <div class="col-md-6"><div class="text-muted">Contact</div><div><?php echo h($order['contact']); ?></div></div>
                <div class="col-md-6"><div class="text-muted">Payment</div><div><?php echo h($order['payment_method']); ?></div></div>
                <div class="col-md-6"><div class="text-muted">Delivery</div><div><?php echo h($order['delivery_method']); ?></div></div>
                <?php
                    $addressBits = array_filter([
                        $order['street'] ?? '',
                        $order['barangay'] ?? '',
                        $order['city'] ?? '',
                        $order['province'] ?? '',
                        $order['postal_code'] ?? '',
                    ], fn($v) => trim($v) !== '');
                ?>
                <?php if (!empty($addressBits)): ?>
                    <div class="col-12"><div class="text-muted">Address</div><div><?php echo h(implode(', ', $addressBits)); ?></div></div>
                <?php endif; ?>
                <?php if (!empty($order['notes'])): ?>
                    <div class="col-12"><div class="text-muted">Notes</div><div><?php echo h($order['notes']); ?></div></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-2 p-3">
            <h6 class="mb-2">Items</h6>
            <?php if (!empty($items)): ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): ?>
                                <tr>
                                    <td><?php echo h($it['product_name'] . ($it['color_name'] ? (' (' . $it['color_name'] . ')') : '')); ?></td>
                                    <td class="text-center"><?php echo (int)$it['quantity']; ?></td>
                                    <td class="text-end">₱<?php echo money($it['price']); ?></td>
                                    <td class="text-end">₱<?php echo money($it['line_total']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-muted">No items found.</div>
            <?php endif; ?>
        </div>

        <div class="card p-3">
            <div class="d-flex justify-content-between"><span>Subtotal</span><span>₱<?php echo money($order['subtotal']); ?></span></div>
            <div class="d-flex justify-content-between"><span>Shipping</span><span>₱<?php echo money($order['shipping']); ?></span></div>
            <hr>
            <div class="d-flex justify-content-between fw-semibold"><span>Total</span><span>₱<?php echo money($order['total']); ?></span></div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
