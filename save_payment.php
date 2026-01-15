<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('client_session');
    session_start();
}
require 'connect.php';

// Helper to load PayPal credentials from environment or optional paypal_config.php
function loadPayPalCredentials(): array {
    $clientId = getenv('PAYPAL_CLIENT_ID') ?: getenv('PAYPAL_CLIENT_ID_SANDBOX') ?: '';
    $clientSecret = getenv('PAYPAL_SECRET') ?: getenv('PAYPAL_CLIENT_SECRET') ?: getenv('PAYPAL_CLIENT_SECRET_SANDBOX') ?: '';

    $configPath = __DIR__ . '/paypal_config.php';
    if (is_readable($configPath)) {
        require_once $configPath;
        if ($clientId === '' && defined('PAYPAL_CLIENT_ID')) {
            $clientId = PAYPAL_CLIENT_ID;
        }
        if ($clientSecret === '' && defined('PAYPAL_CLIENT_SECRET')) {
            $clientSecret = PAYPAL_CLIENT_SECRET;
        }
    }

    return [$clientId, $clientSecret];
}

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request body']);
    exit;
}

$orderId = isset($data['order_id']) ? (int)$data['order_id'] : 0;
$paypalOrderId = trim((string)($data['paypal_order_id'] ?? ''));
if ($orderId <= 0 || $paypalOrderId === '') {
    echo json_encode(['status' => 'error', 'message' => 'Missing order details']);
    exit;
}

// Guests cannot mark payments
$isLoggedIn = !empty($_SESSION['userID']) || !empty($_SESSION['userId']) || !empty($_SESSION['user_id']);
if (!$isLoggedIn) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// Resolve client email (same approach as checkout)
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

// Load order total from DB for validation
$stmt = $conn->prepare('SELECT id, client_email, email, status, total_amount, total FROM orders WHERE id = ? LIMIT 1');
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Unable to load order']);
    exit;
}
$stmt->bind_param('i', $orderId);
$stmt->execute();
$res = $stmt->get_result();
$order = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$order) {
    echo json_encode(['status' => 'error', 'message' => 'Order not found']);
    exit;
}

$orderEmail = trim((string)($order['client_email'] ?? $order['email'] ?? ''));
if ($clientEmail !== '' && $orderEmail !== '' && strcasecmp($clientEmail, $orderEmail) !== 0) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// If already paid, return success idempotently
if (strtolower((string)($order['status'] ?? '')) === 'paid') {
    $existingTotal = (float)($order['total_amount'] ?? $order['total'] ?? 0);
    echo json_encode([
        'status' => 'success',
        'order_id' => $orderId,
        'paypal_order_id' => $paypalOrderId,
        'amount' => number_format($existingTotal, 2, '.', ''),
        'payment_status' => 'COMPLETED'
    ]);
    exit;
}

$expectedAmount = (float)($order['total_amount'] ?? $order['total'] ?? 0);
$expectedAmountStr = number_format($expectedAmount, 2, '.', '');
$expectedCurrency = 'PHP';

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

// PayPal credentials: load from env or paypal_config.php
[$clientId, $clientSecret] = loadPayPalCredentials();
if ($clientId === '' || $clientSecret === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'PayPal server credentials are not set. Provide PAYPAL_CLIENT_ID/PAYPAL_CLIENT_SECRET env vars or fill paypal_config.php.'
    ]);
    exit;
}

// --- Verify payment with PayPal (Sandbox) ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api-m.sandbox.paypal.com/v1/oauth2/token');
curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $clientSecret);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Accept-Language: en_US']);
$tokenRes = curl_exec($ch);
curl_close($ch);

$tokenData = json_decode((string)$tokenRes, true);
$accessToken = (string)($tokenData['access_token'] ?? '');
if ($accessToken === '') {
    echo json_encode(['status' => 'error', 'message' => 'PayPal token error']);
    exit;
}

$ch = curl_init('https://api-m.sandbox.paypal.com/v2/checkout/orders/' . rawurlencode($paypalOrderId));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $accessToken,
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$orderRes = curl_exec($ch);
curl_close($ch);

$ppOrder = json_decode((string)$orderRes, true);
if (!is_array($ppOrder)) {
    echo json_encode(['status' => 'error', 'message' => 'Unable to verify PayPal order']);
    exit;
}

$ppStatus = strtoupper((string)($ppOrder['status'] ?? ''));
if ($ppStatus !== 'COMPLETED') {
    echo json_encode(['status' => 'error', 'message' => 'Payment not completed']);
    exit;
}

// Try to extract capture amount (preferred) or fallback to purchase unit amount
$ppAmount = '';
$ppCurrency = '';
$payerName = '';
$payerEmail = '';

try {
    $payer = $ppOrder['payer'] ?? [];
    $payerEmail = (string)($payer['email_address'] ?? '');
    $given = (string)($payer['name']['given_name'] ?? '');
    $surname = (string)($payer['name']['surname'] ?? '');
    $payerName = trim($given . ' ' . $surname);
} catch (Throwable $e) {
    // ignore
}

$purchaseUnits = $ppOrder['purchase_units'] ?? [];
if (is_array($purchaseUnits) && !empty($purchaseUnits[0])) {
    $pu0 = $purchaseUnits[0];

    $captures = $pu0['payments']['captures'] ?? null;
    if (is_array($captures) && !empty($captures[0])) {
        $cap0 = $captures[0];
        $ppAmount = (string)($cap0['amount']['value'] ?? '');
        $ppCurrency = (string)($cap0['amount']['currency_code'] ?? '');
    }

    if ($ppAmount === '' || $ppCurrency === '') {
        $ppAmount = (string)($pu0['amount']['value'] ?? '');
        $ppCurrency = (string)($pu0['amount']['currency_code'] ?? '');
    }
}

if ($ppAmount === '' || $ppCurrency === '') {
    echo json_encode(['status' => 'error', 'message' => 'Unable to read PayPal amount']);
    exit;
}

// Validate currency and amount
if (strtoupper($ppCurrency) !== $expectedCurrency) {
    echo json_encode(['status' => 'error', 'message' => 'Currency mismatch']);
    exit;
}

$ppAmountFloat = (float)$ppAmount;
if (abs($ppAmountFloat - $expectedAmount) > 0.01) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Amount mismatch. Expected PHP ' . $expectedAmountStr . ' but got PHP ' . number_format($ppAmountFloat, 2, '.', '')
    ]);
    exit;
}

// Ensure payments table exists (support both "new" and your existing schema)
$conn->query("CREATE TABLE IF NOT EXISTS payments (
    order_id VARCHAR(100) NOT NULL,
    payer_name VARCHAR(100) NULL,
    payer_email VARCHAR(100) NULL,
    total_amount DECIMAL(10,2) NULL,
    payment_status VARCHAR(50) NULL,
    payment_method VARCHAR(50) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Optional columns (keeps compatibility with existing table)
// Some older installs may not have order_id yet; add it if missing to avoid insert errors.
if (!$tableHasColumn($conn, 'payments', 'order_id')) {
    try { $conn->query("ALTER TABLE payments ADD COLUMN order_id VARCHAR(120) NULL"); } catch (mysqli_sql_exception $e) {}
}
try { $conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS internal_order_id INT NULL"); } catch (mysqli_sql_exception $e) {}
try { $conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS paypal_order_id VARCHAR(120) NULL"); } catch (mysqli_sql_exception $e) {}
try { $conn->query("ALTER TABLE payments ADD COLUMN IF NOT EXISTS currency VARCHAR(10) NOT NULL DEFAULT 'PHP'"); } catch (mysqli_sql_exception $e) {}
// Add a uniqueness guard for PayPal order ids when possible (ignore if already exists or if duplicates exist)
try { $conn->query("ALTER TABLE payments ADD UNIQUE KEY uniq_paypal_order (order_id)"); } catch (mysqli_sql_exception $e) {}

$conn->begin_transaction();

// Save payment record
$statusText = 'COMPLETED';
$amountToStore = (float)$ppAmount;

if ($tableHasColumn($conn, 'payments', 'provider') && $tableHasColumn($conn, 'payments', 'provider_order_id')) {
    // New schema (if you migrated)
    $provider = 'paypal';
    $insert = $conn->prepare(
        "INSERT INTO payments (order_id, provider, provider_order_id, payer_name, payer_email, amount, currency, status)
         VALUES (?,?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE
            order_id = VALUES(order_id),
            payer_name = VALUES(payer_name),
            payer_email = VALUES(payer_email),
            amount = VALUES(amount),
            currency = VALUES(currency),
            status = VALUES(status)"
    );
    if (!$insert) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Unable to save payment']);
        exit;
    }
    $insert->bind_param('issssdss', $orderId, $provider, $paypalOrderId, $payerName, $payerEmail, $amountToStore, $ppCurrency, $statusText);
    $ok = $insert->execute();
    $insert->close();
    if (!$ok) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Unable to save payment']);
        exit;
    }
} else {
    // Your existing schema: store PayPal order id in payments.order_id
    $paymentMethod = 'PayPal';
    $insert = $conn->prepare(
        "INSERT INTO payments (order_id, payer_name, payer_email, total_amount, payment_status, payment_method, internal_order_id, paypal_order_id, currency)
         VALUES (?,?,?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE
            payer_name = VALUES(payer_name),
            payer_email = VALUES(payer_email),
            total_amount = VALUES(total_amount),
            payment_status = VALUES(payment_status),
            payment_method = VALUES(payment_method),
            internal_order_id = VALUES(internal_order_id),
            paypal_order_id = VALUES(paypal_order_id),
            currency = VALUES(currency)"
    );
    if (!$insert) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Unable to save payment']);
        exit;
    }
    $insert->bind_param('sssdssiss', $paypalOrderId, $payerName, $payerEmail, $amountToStore, $statusText, $paymentMethod, $orderId, $paypalOrderId, $ppCurrency);
    $ok = $insert->execute();
    $insert->close();
    if (!$ok) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Unable to save payment']);
        exit;
    }
}

// Mark order as paid
$upd = $conn->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
if ($upd) {
    $upd->bind_param('i', $orderId);
    $upd->execute();
    $upd->close();
}

// Update notification status/message (best-effort; support both schemas)
$msg = 'Order paid via PayPal.';
if ($tableHasColumn($conn, 'notifications', 'order_id')) {
    $updNotif = $conn->prepare("UPDATE notifications SET status = 'paid', message = ? WHERE order_id = ?");
    if ($updNotif) {
        $updNotif->bind_param('si', $msg, $orderId);
        $updNotif->execute();
        $updNotif->close();
    }
} elseif ($tableHasColumn($conn, 'notifications', 'or_derid') && $tableHasColumn($conn, 'notifications', 'recipient_email')) {
    $updNotif = $conn->prepare("UPDATE notifications SET status = 'paid', message = ? WHERE or_derid = ? AND recipient_email = ?");
    if ($updNotif) {
        $updNotif->bind_param('sis', $msg, $orderId, $clientEmail);
        $updNotif->execute();
        $updNotif->close();
    }
}

$conn->commit();

echo json_encode([
    'status' => 'success',
    'order_id' => $orderId,
    'paypal_order_id' => $paypalOrderId,
    'amount' => number_format($amountToStore, 2, '.', ''),
    'payment_status' => $statusText
]);