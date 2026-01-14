<?php
if (session_status() === PHP_SESSION_NONE) {
  session_name('client_session');
  session_start();
}
$checkoutError = $_GET['error'] ?? '';
// Avoid using stale selection if user returns to cart
unset($_SESSION['checkout_cart']);

$cart = $_SESSION['cart'] ?? [];
$shipping = 120;
$subtotal = 0;

// Calculate subtotal (default: all items selected)
foreach ($cart as $item) {
  $qty = isset($item['qty']) ? (int)$item['qty'] : 1;
  $price = isset($item['price']) ? (float)$item['price'] : 0.0;
  $subtotal += $price * $qty;
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
      <span class="d-inline-flex align-items-center back-action g-2" onclick="history.back()">
        <i class="material-icons">&#xe5c4;</i>
        <span>back</span>
      </span>
    </div>

    <h3 class="text-center fw-bold my-3">SHOPPING CART</h3>

    <?php if ($checkoutError === 'select'): ?>
      <div class="alert alert-warning" role="alert">
        Please select at least one item to checkout.
      </div>
    <?php endif; ?>

    <div id="cartList" class="cart-card shadow-sm p-3">
      <h6 class="fw-bold mb-3">CART ITEMS</h6>

      <?php if (!empty($cart)): ?>
        <?php foreach ($cart as $index => $item):
            $qty = isset($item['qty']) ? (int)$item['qty'] : 1;
            $price = isset($item['price']) ? (float)$item['price'] : 0.0;
            $itemTotal = $price * $qty;
            $img = $item['image'] ?? '';
        ?>
          <div class="d-flex mb-3 align-items-center cart-item" data-index="<?= (int)$index ?>" data-price="<?= htmlspecialchars((string)$price) ?>">
            <div class="me-2 d-flex align-items-center" style="min-width: 18px;">
              <input class="form-check-input cart-select m-0" type="checkbox" name="selected[]" value="<?= (int)$index ?>" form="checkoutCartForm" checked>
            </div>

            <?php if (!empty($img)): ?>
              <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width:54px; height:54px; object-fit:cover; border-radius:8px;" class="me-2">
            <?php else: ?>
              <div class="me-2" style="width:54px; height:54px; border-radius:8px; background:#f1f1f1;"></div>
            <?php endif; ?>

            <div class="flex-grow-1">
              <div class="fw-semibold"><?= htmlspecialchars($item['name']) ?></div>
              <?php if (!empty($item['color_name'])): ?>
                <div class="text-muted small">Color: <?= htmlspecialchars($item['color_name']) ?></div>
              <?php endif; ?>
              <div class="text-muted small">₱<?= number_format($price, 2) ?> each</div>
            </div>

            <div class="d-flex align-items-center gap-2 cart-actions">
              <div class="input-group input-group-sm" style="width: 120px;">
                <button class="btn btn-outline-secondary qty-minus" type="button">-</button>
                <input type="number" class="form-control text-center cart-qty" name="qty[<?= (int)$index ?>]" form="checkoutCartForm" value="<?= (int)$qty ?>" min="1" step="1">
                <button class="btn btn-outline-secondary qty-plus" type="button">+</button>
              </div>
              <div class="text-nowrap">₱<span class="item-total"><?= number_format($itemTotal, 2) ?></span></div>

              <!-- REMOVE BUTTON (keep separate form) -->
              <form method="post" class="ms-1" action="removefromcart.php">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string)($item['id'] ?? '')) ?>">
                <input type="hidden" name="color_id" value="<?= isset($item['color_id']) ? htmlspecialchars((string)$item['color_id']) : '' ?>">
                <input type="hidden" name="color_name" value="<?= isset($item['color_name']) ? htmlspecialchars((string)$item['color_name']) : '' ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-center text-muted">Your cart is empty.</p>
      <?php endif; ?>

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

      <div class="d-flex justify-content-between fw-bold">
        <span>TOTAL</span>
        <span id="totalValue">₱<?= number_format($total, 2) ?></span>
      </div>
    </div>

    <div class="card p-3 mt-3 shadow-sm" style="max-width: 350px; margin-left: auto;">
      <div class="mb-2">
        <label class="form-label">Delivery Method</label>
        <select class="form-select">
          <option>Delivery</option>
          <option>Pickup</option>
        </select>
      </div>

      <div class="mb-2">
        <label class="form-label">Select Date & Time</label>
        <input type="datetime-local" class="form-control">
      </div>

      <div class="d-flex justify-content-between mt-3" style="font-size: 2rem; font-weight: bold;">
        <span>Total:</span>
        <span id="summaryTotalValue">₱<?= number_format($total, 2) ?></span>
      </div>

      <p class="text-muted small mb-2">Taxes and shipping calculated at checkout</p>
      <form id="checkoutCartForm" action="smsbooking.php" method="post">
        <button type="submit" class="btn btn-dark w-100" id="checkoutBtn" <?= empty($cart) ? 'disabled' : '' ?>>Check Out</button>
      </form>
    </div>
  </div>

  <script>
    (function () {
      const SHIPPING = <?= (int)$shipping ?>;
      const items = Array.from(document.querySelectorAll('.cart-item'));
      const subtotalEl = document.getElementById('subtotalValue');
      const shippingEl = document.getElementById('shippingValue');
      const totalEl = document.getElementById('totalValue');
      const summaryTotalEl = document.getElementById('summaryTotalValue');
      const checkoutBtn = document.getElementById('checkoutBtn');

      function clampQty(val) {
        const n = parseInt(val, 10);
        return Number.isFinite(n) && n > 0 ? n : 1;
      }

      function recalc() {
        let subtotal = 0;
        let anyChecked = false;

        items.forEach(row => {
          const price = parseFloat(row.dataset.price || '0') || 0;
          const checkbox = row.querySelector('.cart-select');
          const qtyInput = row.querySelector('.cart-qty');
          const totalSpan = row.querySelector('.item-total');

          const qty = clampQty(qtyInput.value);
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
        if (totalEl) totalEl.textContent = '₱' + total.toFixed(2);
        if (summaryTotalEl) summaryTotalEl.textContent = '₱' + total.toFixed(2);

        if (checkoutBtn) checkoutBtn.disabled = !anyChecked;
      }

      document.addEventListener('click', (e) => {
        const minus = e.target.closest('.qty-minus');
        const plus = e.target.closest('.qty-plus');
        if (!minus && !plus) return;

        const row = e.target.closest('.cart-item');
        if (!row) return;
        const qtyInput = row.querySelector('.cart-qty');
        if (!qtyInput || qtyInput.disabled) return;

        const current = clampQty(qtyInput.value);
        qtyInput.value = minus ? Math.max(1, current - 1) : (current + 1);
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

      // Initialize
      recalc();
    })();
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
