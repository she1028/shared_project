<?php
if (session_status() === PHP_SESSION_NONE) {
  session_name('client_session');
  session_start();
}
require_once __DIR__ . '/connect.php';

$getUserId = function () {
  return $_SESSION['userID'] ?? $_SESSION['userId'] ?? $_SESSION['user_id'] ?? null;
};

$ensureCartTable = function () use ($conn) {
  static $ready = false;
  if ($ready) return;
  $sql = "CREATE TABLE IF NOT EXISTS user_carts (
    user_id INT NOT NULL PRIMARY KEY,
    cart_json LONGTEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
  $conn->query($sql);
  $ready = true;
};

$loadCartFromDb = function ($userId) use ($conn, $ensureCartTable) {
  $ensureCartTable();
  $stmt = $conn->prepare('SELECT cart_json FROM user_carts WHERE user_id = ? LIMIT 1');
  $stmt->bind_param('i', $userId);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  if (!$row) return [];
  $decoded = json_decode($row['cart_json'], true);
  return is_array($decoded) ? $decoded : [];
};
$checkoutError = $_GET['error'] ?? '';
// Avoid using stale selection if user returns to cart
unset($_SESSION['checkout_cart']);

$userId = $getUserId();
if ($userId) {
  // Refresh session cart from DB for this user to keep carts user-scoped
  $_SESSION['cart'] = $loadCartFromDb((int)$userId);
}

$cartLimit = 99;
$cart = $_SESSION['cart'] ?? [];
$cartEntryCount = is_array($cart) ? min($cartLimit, count($cart)) : 0;
$shipping = 120;
$subtotal = 0;
$totalItemCount = 0;
$minEventDate = date('Y-m-d', strtotime('+3 days'));
$savedDeliveryTime = (string)($_SESSION['checkout_delivery_time'] ?? '');

// Persist a single clean back target across cart interactions to avoid multi-click history hops.
$host = $_SERVER['HTTP_HOST'] ?? '';
$storedBack = $_SESSION['cart_return_url'] ?? '';
$backTarget = '';

// Prefer previously stored target unless a new valid referrer arrives from another page (not cart).
$referrer = $_SERVER['HTTP_REFERER'] ?? '';
if (!empty($referrer) && stripos($referrer, 'cart.php') === false) {
  $parsed = parse_url($referrer);
  if ($parsed && !empty($parsed['scheme']) && !empty($parsed['host']) && (empty($host) || strcasecmp($parsed['host'], $host) === 0)) {
    $path = $parsed['path'] ?? '';
    $query = isset($parsed['query']) ? ('?' . $parsed['query']) : '';
    $backTarget = $parsed['scheme'] . '://' . $parsed['host'] . (isset($parsed['port']) ? (':' . $parsed['port']) : '') . $path . $query;
    $_SESSION['cart_return_url'] = $backTarget;
  }
}

// Use stored target if no fresh referrer was accepted
if (!$backTarget && !empty($storedBack)) {
  $backTarget = $storedBack;
}

// Calculate subtotal (default: all items selected)
foreach ($cart as $item) {
  $qty = isset($item['qty']) ? (int)$item['qty'] : 1;
  $price = isset($item['price']) ? (float)$item['price'] : 0.0;
  $subtotal += $price * $qty;
  $totalItemCount += $qty;
}

$total = !empty($cart) ? ($subtotal + $shipping) : 0;

?>


<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Shopping Cart</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="cart.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-4">
    <div class="m-2">
      <a id="cartBackBtn" class="d-inline-flex align-items-center back-action g-2" href="javascript:void(0)">
        <i class="material-icons">&#xe5c4;</i>
        <span>back</span>
      </a>
    </div>

    <h3 class="text-center fw-bold my-3">SHOPPING CART</h3> 

    <?php if ($checkoutError === 'select'): ?>
      <div class="alert alert-warning" role="alert">
        Please select at least one item to checkout.
      </div>
    <?php elseif ($checkoutError === 'date'): ?>
      <div class="alert alert-warning" role="alert">  
        Please select a date at least 3 days in advance.
      </div>
    <?php elseif ($checkoutError === 'time'): ?>
      <div class="alert alert-warning" role="alert">
        Please select a valid delivery time.
      </div>
    <?php endif; ?>

    <div class="row g-3 align-items-start">
      <div class="col-lg-8">
        <div id="cartList" class="cart-card shadow-sm p-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold m-0 d-flex align-items-center gap-2">
              Cart
              <span class="cart-items-count badge bg-light text-muted fw-normal border">Items: <?= (int)$cartEntryCount ?></span>
            </h6>
          </div>

          <?php if (!empty($cart)): ?>
            <?php foreach ($cart as $index => $item):
                $qty = isset($item['qty']) ? (int)$item['qty'] : 1;
                $price = isset($item['price']) ? (float)$item['price'] : 0.0;
                $itemTotal = $price * $qty;
                $img = $item['image'] ?? '';
                $maxQtyAttr = '';
                $availableStock = null;
                if (($item['type'] ?? '') === 'rental' && isset($item['color_stock']) && $item['color_stock'] !== null && (int)$item['color_stock'] > 0) {
                  $maxQtyAttr = ' max="' . (int)$item['color_stock'] . '"';
                  $availableStock = (int)$item['color_stock'];
                } elseif (isset($item['stock']) && $item['stock'] !== null && (int)$item['stock'] >= 0) {
                  $availableStock = (int)$item['stock'];
                }
            ?>
              <div class="d-flex mb-3 align-items-center cart-item" data-index="<?= (int)$index ?>" data-price="<?= htmlspecialchars((string)$price) ?>">
                <div class="me-2 d-flex align-items-center" style="min-width: 18px;">
                  <input class="form-check-input cart-select m-0" type="checkbox" name="selected[]" value="<?= (int)$index ?>" form="checkoutCartForm" checked>
                </div>

                <?php if (!empty($img)): ?>
                  <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width:80px; height:80px; object-fit:cover; border-radius:12px;" class="me-3">
                <?php else: ?>
                  <div class="me-3" style="width:80px; height:80px; border-radius:12px; background:#f1f1f1;"></div>
                <?php endif; ?>

                <div class="flex-grow-1 cart-meta">
                  <div class="fw-semibold cart-meta-title"><?= htmlspecialchars($item['name']) ?></div>
                  <div class="cart-meta-lines">
                    <?php if (!empty($item['color_name'])): ?>
                      <div class="cart-meta-line"><span class="cart-meta-label">Color:</span> <span class="cart-meta-value"><?= htmlspecialchars($item['color_name']) ?></span></div>
                    <?php endif; ?>
                    <div class="cart-meta-line"><span class="cart-meta-label">Price:</span> <span class="cart-meta-value">₱<?= number_format($price, 2) ?> each</span></div>
                    <?php if ($availableStock !== null): ?>
                      <div class="cart-meta-line"><span class="cart-meta-label">Available:</span> <span class="cart-meta-value"><?= (int)$availableStock ?></span></div>
                    <?php endif; ?>
                  </div>
                  <form method="post" class="cart-remove-left" action="removefromcart.php">
                    <input type="hidden" name="id" value="<?= htmlspecialchars((string)($item['id'] ?? '')) ?>">
                    <input type="hidden" name="color_id" value="<?= isset($item['color_id']) ? htmlspecialchars((string)$item['color_id']) : '' ?>">
                    <input type="hidden" name="color_name" value="<?= isset($item['color_name']) ? htmlspecialchars((string)$item['color_name']) : '' ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                  </form>
                </div>

                <div class="cart-actions">
                  <div class="cart-qty-wrap">
                    <button class="btn btn-outline-secondary qty-minus" type="button">-</button>
                    <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control text-center cart-qty" name="qty[<?= (int)$index ?>]" form="checkoutCartForm" value="<?= (int)$qty ?>"<?= $maxQtyAttr ?>>
                    <button class="btn btn-outline-secondary qty-plus" type="button">+</button>
                  </div>
                  <div class="cart-price text-end">₱<span class="item-total"><?= number_format($itemTotal, 2) ?></span></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-center text-muted mb-0">Your cart is empty.</p>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card p-3 shadow-sm cart-summary sticky-top" style="top: 90px;">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <h6 class="fw-bold m-0">Checkout Details</h6>
            <div class="text-muted small">Select date & time</div>
          </div>

          <div class="mb-2">
            <label class="form-label">Select Date</label>
            <input type="date" class="form-control" name="event_date" form="checkoutCartForm" min="<?= htmlspecialchars($minEventDate) ?>" required>
            <div class="form-text">Must be at least 3 days from today.</div>
          </div>

          <div class="mb-2">
            <label class="form-label">Delivery Time</label>
            <input type="time" class="form-control" id="deliveryTime" name="delivery_time" form="checkoutCartForm" value="<?= htmlspecialchars($savedDeliveryTime) ?>" min="08:00" max="18:00" step="900" required>
            <div class="form-text" id="deliveryTimeHelp">Available (08:00 AM – 06:00 PM)</div>
          </div>

          <hr>

          <div class="d-flex justify-content-between">
            <span>Subtotal</span>
            <span id="subtotalValue">₱<?= number_format($subtotal, 2) ?></span>
          </div>
          <div class="d-flex justify-content-between">
            <span>Shipping</span>
            <span id="shippingValue">₱<?= number_format(!empty($cart) ? $shipping : 0, 2) ?></span>
          </div>

          <hr>

          <div class="d-flex justify-content-between fw-bold" style="font-size: 1.25rem;">
            <span>Total</span>
            <span id="summaryTotalValue">₱<?= number_format($total, 2) ?></span>
          </div>

          <form id="checkoutCartForm" action="smsbooking.php" method="post" class="mt-3">
            <button type="submit" class="btn btn-dark w-100" id="checkoutBtn" <?= empty($cart) ? 'disabled' : '' ?>>Proceed to Checkout</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function () {
      const SHIPPING = <?= (int)$shipping ?>;
      const items = Array.from(document.querySelectorAll('.cart-item'));
      const subtotalEl = document.getElementById('subtotalValue');
      const shippingEl = document.getElementById('shippingValue');
      const summaryTotalEl = document.getElementById('summaryTotalValue');
      const checkoutBtn = document.getElementById('checkoutBtn');
      const deliveryTimeInput = document.getElementById('deliveryTime');
      const deliveryTimeHelp = document.getElementById('deliveryTimeHelp');

      function normalizeTimeValue(v) {
        const s = String(v || '').trim();
        if (!/^\d{2}:\d{2}$/.test(s)) return '';
        return s;
      }

      function isTimeInWindow(t, start, end) {
        // HH:MM string compare works for 00-23 range
        return t >= start && t <= end;
      }

      function clampQty(val, maxVal) {
        const n = parseInt(val, 10);
        const base = Number.isFinite(n) && n > 0 ? n : 1;
        if (typeof maxVal === 'number' && Number.isFinite(maxVal) && maxVal > 0) {
          return Math.min(base, Math.floor(maxVal));
        }
        return base;
      }

      function recalc() {
        let subtotal = 0;
        let anyChecked = false;

        items.forEach(row => {
          const price = parseFloat(row.dataset.price || '0') || 0;
          const checkbox = row.querySelector('.cart-select');
          const qtyInput = row.querySelector('.cart-qty');
          const totalSpan = row.querySelector('.item-total');

          const maxAttr = qtyInput && qtyInput.max ? parseInt(qtyInput.max, 10) : NaN;
          const maxVal = Number.isFinite(maxAttr) ? maxAttr : undefined;
          const qty = clampQty(qtyInput.value, maxVal);
          qtyInput.value = qty;

          const line = price * qty;
          if (totalSpan) totalSpan.textContent = line.toFixed(2);

          const checked = checkbox && checkbox.checked;
          if (checked) {
            anyChecked = true;
            subtotal += line;
          }

          // Optional: prevent editing qty when unchecked
          if (qtyInput) qtyInput.disabled = !checked;
        });

        const shipping = anyChecked ? SHIPPING : 0;
        const total = subtotal + shipping;

        if (subtotalEl) subtotalEl.textContent = '₱' + subtotal.toFixed(2);
        if (shippingEl) shippingEl.textContent = '₱' + shipping.toFixed(2);
        if (summaryTotalEl) summaryTotalEl.textContent = '₱' + total.toFixed(2);

        if (checkoutBtn) checkoutBtn.disabled = !anyChecked;

        if (deliveryTimeInput && deliveryTimeHelp) {
          const t = normalizeTimeValue(deliveryTimeInput.value);
          if (!t) {
            deliveryTimeHelp.textContent = 'Please select a delivery time.';
            deliveryTimeHelp.classList.remove('text-success');
            deliveryTimeHelp.classList.add('text-danger');
            checkoutBtn && (checkoutBtn.disabled = true);
          } else if (!isTimeInWindow(t, '08:00', '18:00')) {
            deliveryTimeHelp.textContent = 'Not available. Choose 08:00 AM – 06:00 PM.';
            deliveryTimeHelp.classList.remove('text-success');
            deliveryTimeHelp.classList.add('text-danger');
            checkoutBtn && (checkoutBtn.disabled = true);
          } else {
            deliveryTimeHelp.textContent = 'Available (08:00 AM – 06:00 PM)';
            deliveryTimeHelp.classList.remove('text-danger');
            deliveryTimeHelp.classList.add('text-success');
          }
        }
      }

      document.addEventListener('click', (e) => {
        const minus = e.target.closest('.qty-minus');
        const plus = e.target.closest('.qty-plus');
        if (!minus && !plus) return;

        const row = e.target.closest('.cart-item');
        if (!row) return;
        const qtyInput = row.querySelector('.cart-qty');
        if (!qtyInput || qtyInput.disabled) return;

        const maxAttr = qtyInput && qtyInput.max ? parseInt(qtyInput.max, 10) : NaN;
        const maxVal = Number.isFinite(maxAttr) ? maxAttr : undefined;
        const current = clampQty(qtyInput.value, maxVal);
        const next = minus ? Math.max(1, current - 1) : (current + 1);
        qtyInput.value = clampQty(next, maxVal);
        recalc();
      });

      document.addEventListener('input', (e) => {
        if (e.target && e.target.classList.contains('cart-qty')) {
          recalc();
        }
      });

      document.addEventListener('change', (e) => {
        if (e.target && e.target.classList.contains('cart-select')) {
          recalc();
        }
      });

      document.addEventListener('input', (e) => {
        if (e.target && e.target.id === 'deliveryTime') {
          recalc();
        }
      });

      // Initialize
      recalc();
    })();

    (function () {
      const btn = document.getElementById('cartBackBtn');
      if (!btn) return;

      const backTarget = "<?= htmlspecialchars($backTarget, ENT_QUOTES) ?>";

      btn.addEventListener('click', (e) => {
        e.preventDefault();

        if (backTarget) {
          window.location.replace(backTarget);
          return;
        }

        // Fallback: go home if we somehow lack a stored target
        window.location.replace('index.php');
      });
    })();
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
