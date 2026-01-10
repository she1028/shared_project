<?php
session_start();
$cart = $_SESSION['cart'] ?? [];
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

    <div id="cartList" class="cart-card shadow-sm p-3">
      <h6 class="fw-bold mb-3">CART ITEMS</h6>

      <?php
      $subtotal = 0;
      foreach ($cart as $index => $item):
        $subtotal += $item['price'];
      ?>
        <div class="d-flex mb-3 align-items-center">
          <div class="item-number"><?= $index + 1 ?></div>
          <div class="flex-grow-1 ms-2">
            <div class="fw-semibold"><?= $item['name'] ?></div>
            <small class="text-muted"><?= $item['qty'] ?></small>
          </div>
          <div>₱<?= number_format($item['price'], 2) ?></div>
        </div>
      <?php endforeach; ?>

      <hr>

      <?php
      $shipping = 120; // example shipping
      $total = $subtotal + $shipping;
      ?>

      <div class="d-flex justify-content-between">
        <span>Subtotal</span>
        <span>₱<?= number_format($subtotal, 2) ?></span>
      </div>
      <div class="d-flex justify-content-between">
        <span>Shipping</span>
        <span>₱<?= number_format($shipping, 2) ?></span>
      </div>

      <hr>

      <div class="d-flex justify-content-between fw-bold">
        <span>TOTAL</span>
        <span>₱<?= number_format($total, 2) ?></span>
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

      <div class="d-flex justify-content-between mt-3" style="font-size: 3.0rem; font-weight: bold;">
        <span>Total:</span>
        <span id="totalPrice">$0.00</span>
      </div>

      <p class="text-muted small mb-2">Taxes and shipping calculated at checkout</p>

      <form action="checkout.php" method="get">
        <button type="submit" class="btn btn-dark w-100">Check Out</button>
      </form>

    </div>
  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>