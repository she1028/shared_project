<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}
require 'connect.php';

// Load PayPal credentials from environment or optional config file
$paypalClientId = getenv('PAYPAL_CLIENT_ID') ?: getenv('PAYPAL_CLIENT_ID_SANDBOX') ?: '';
$paypalSecret = getenv('PAYPAL_SECRET') ?: getenv('PAYPAL_CLIENT_SECRET') ?: getenv('PAYPAL_CLIENT_SECRET_SANDBOX') ?: '';

$configPath = __DIR__ . '/paypal_config.php';
if (is_readable($configPath)) {
    require_once $configPath;
    if ($paypalClientId === '' && defined('PAYPAL_CLIENT_ID')) {
        $paypalClientId = PAYPAL_CLIENT_ID;
    }
    if ($paypalSecret === '' && defined('PAYPAL_CLIENT_SECRET')) {
        $paypalSecret = PAYPAL_CLIENT_SECRET;
    }
}

$paypalError = '';
if ($paypalClientId === '') {
    $paypalError = 'PayPal client ID is not configured. Set PAYPAL_CLIENT_ID/PAYPAL_CLIENT_SECRET in your environment or fill paypal_config.php.';
}

// Guests should not access payment page
$isLoggedIn = !empty($_SESSION['userID']) || !empty($_SESSION['userId']) || !empty($_SESSION['user_id']);
if (!$isLoggedIn) {
    header('Location: auth.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? 'payment.php'));
    exit;
}

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($orderId <= 0) {
    header('Location: cart.php');
    exit;
}

// Resolve client email to prevent paying other users' orders
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
            // ignore
        }
    }
}

$stmt = $conn->prepare('SELECT id, client_email, email, status, payment_method, total_amount, total FROM orders WHERE id = ? LIMIT 1');
if (!$stmt) {
    header('Location: cart.php');
    exit;
}
$stmt->bind_param('i', $orderId);
$stmt->execute();
$res = $stmt->get_result();
$order = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$order) {
    header('Location: cart.php');
    exit;
}

$orderEmail = trim((string)($order['client_email'] ?? $order['email'] ?? ''));
if ($clientEmail !== '' && $orderEmail !== '' && strcasecmp($clientEmail, $orderEmail) !== 0) {
    header('Location: cart.php');
    exit;
}

$status = (string)($order['status'] ?? 'pending');
$paymentMethod = strtolower((string)($order['payment_method'] ?? ''));
if ($paymentMethod !== '' && $paymentMethod !== 'paypal') {
    header('Location: checkout.php');
    exit;
}
$dbTotal = $order['total_amount'] ?? $order['total'] ?? 0;
$total = (float)$dbTotal;
$paypalTotal = number_format($total, 2, '.', '');

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

        <!-- PayPal SDK -->
        <?php if ($paypalError === ''): ?>
            <script src="https://www.sandbox.paypal.com/sdk/js?client-id=<?= urlencode($paypalClientId) ?>&currency=PHP&enable-funding=card&intent=capture"></script>
        <?php endif; ?>
</head>

<body class="bg-light">

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <h3 class="mb-3">Complete Your Payment</h3>
                        <p class="text-muted mb-1">Order #<?= (int)$orderId ?></p>
                        <p class="mb-3"><strong>Total:</strong> PHP <?= htmlspecialchars($paypalTotal) ?></p>

                        <?php if ($paypalError !== ''): ?>
                            <div class="alert alert-danger">PayPal is not configured. <?= htmlspecialchars($paypalError) ?></div>
                        <?php elseif (strtolower($status) === 'paid'): ?>
                            <div class="alert alert-success">This order is already marked as paid.</div>
                        <?php else: ?>
                            <!-- PayPal Button -->
                            <div id="paypal-button-container"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Confirmation Modal -->
    <div class="modal fade" id="paypalModal" tabindex="-1" aria-labelledby="paypalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="paypalModalLabel">Payment Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="paypalModalClose"></button>
                </div>

                <div class="modal-body" id="paypalModalBody">
                    <!-- Payment info will appear here dynamically -->
                </div>

                <div class="modal-footer flex-column gap-2">
                    <button type="button" class="btn btn-primary w-100" id="paypalViewNotif">View Notifications</button>
                    <button type="button" class="btn btn-secondary w-100" id="paypalStayHere" data-bs-dismiss="modal">Stay on this page</button>
                </div>

            </div>
        </div>
    </div>

    <script>
        // Internal order + DB-validated total (do not change client-side)
        const internalOrderId = <?= (int)$orderId ?>;
        const totalAmount = '<?= $paypalTotal ?>';
        const goToNotifications = () => {
            try {
                sessionStorage.setItem('openNotifications', '1');
            } catch (err) {}
            window.location.href = 'index.php?show=notifications';
        };
        const viewNotifBtn = document.getElementById('paypalViewNotif');
        const stayHereBtn = document.getElementById('paypalStayHere');
        const modalCloseBtn = document.getElementById('paypalModalClose');
        if (viewNotifBtn) viewNotifBtn.addEventListener('click', goToNotifications);
        if (modalCloseBtn) modalCloseBtn.addEventListener('click', () => {
            window.location.href = 'index.php';
        });
        if (stayHereBtn) stayHereBtn.addEventListener('click', () => {
            /* just dismiss */ });

        // Render PayPal button
        <?php if ($paypalError === '' && strtolower($status) !== 'paid'): ?>
            paypal.Buttons({
                style: {
                    shape: 'rect',
                    color: 'blue',
                    layout: 'vertical',
                    label: 'paypal'
                },

                // Create the order
                createOrder: function(data, actions) {
                    return actions.order.create({
                        purchase_units: [{
                            reference_id: String(internalOrderId),
                            amount: {
                                currency_code: 'PHP',
                                value: totalAmount
                            }
                        }]
                    });
                },

                // On approval
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {

                        // Send data to PHP
                        fetch("save_payment.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({
                                    order_id: internalOrderId,
                                    paypal_order_id: details.id
                                })
                            })
                            .then(async res => {
                                let text = '';
                                let payload = null;
                                try {
                                    text = await res.text();
                                } catch (e) {
                                    // ignore
                                }
                                try {
                                    if (text) payload = JSON.parse(text);
                                } catch (e) {
                                    // non-JSON response
                                }
                                if (!res.ok) {
                                    const message = (payload && payload.message) ? payload.message : (text || 'Unable to process your payment details. Please try again.');
                                    throw new Error(message);
                                }
                                if (!payload) {
                                    const message = text || 'Unexpected empty response from server.';
                                    throw new Error(message);
                                }
                                return payload;
                            })
                            .then(res => {
                                const modalBody = document.getElementById('paypalModalBody');
                                if (viewNotifBtn) viewNotifBtn.disabled = res.status !== "success";

                                if (res.status === "success") {
                                    try {
                                        sessionStorage.setItem('openNotifications', '1');
                                    } catch (err) {}
                                    modalBody.innerHTML = `
                        <div class="alert alert-success">
                            <div class="fw-semibold">Thank you for your support!</div>
                            <div class="small text-muted mb-2">Your payment was successful. Notifications now include your receipt.</div>
                            <div class="d-flex justify-content-between"><span>Order #</span><strong>${res.order_id}</strong></div>
                            <div class="d-flex justify-content-between"><span>Paid Amount</span><strong>PHP ${res.amount}</strong></div>
                            <div class="d-flex justify-content-between"><span>Status</span><strong>${res.payment_status}</strong></div>
                            <div class="small text-muted mt-2">PayPal Ref: ${res.paypal_order_id}</div>
                            <div class="small text-muted">Processed via PayPal Sandbox.</div>
                        </div>
                    `;
                                } else {
                                    modalBody.innerHTML = `
                        <div class="alert alert-danger text-center">
                            <h4>Oops! Something went wrong.</h4>
                            <p>${res.message || 'We could not validate/save your payment. Please try again.'}</p>
                        </div>
                    `;
                                }

                                // Show modal
                                const paypalModal = new bootstrap.Modal(document.getElementById('paypalModal'));
                                paypalModal.show();

                            })
                            .catch(err => {
                                const modalBody = document.getElementById('paypalModalBody');
                                modalBody.innerHTML = `
                    <div class="alert alert-danger text-center">
                        <h4>Network or server error!</h4>
                        <p>${err && err.message ? err.message : 'Unable to process your payment details. Please try again.'}</p>
                    </div>
                `;
                                const paypalModal = new bootstrap.Modal(document.getElementById('paypalModal'));
                                paypalModal.show();
                            });

                    });
                }

            }).render('#paypal-button-container');
        <?php endif; ?>
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>