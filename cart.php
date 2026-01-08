<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Shopping Cart</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="cart.css">
</head>

<body class="bg-light">

  <div class="container py-4">

    <div class="container my-4">
      <a href="index.php" class="btn btn-outline-dark text-decoration-none">
        &larr; Back
      </a>
    </div>

    <h3 class="text-center fw-bold my-3">SHOPPING CART</h3>

    <div id="cartList" class="cart-card shadow-sm"></div>

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

      <button class="btn btn-dark w-100">Check Out</button>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>