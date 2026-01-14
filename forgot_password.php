<?php
// Pre-fill email from query param or cookie
$prefill = '';
if (!empty($_GET['email'])) {
    $prefill = $_GET['email'];
} elseif (!empty($_COOKIE['last_auth_email'])) {
    $prefill = urldecode($_COOKIE['last_auth_email']);
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  </head>
  <body class="bg-light">

<div class="container vh-100 d-flex align-items-center justify-content-center">
    <div class="w-100" style="max-width: 420px;">
        <div class="mb-3">
            <a href="auth.php" class="text-decoration-none text-muted small"><i class="bi bi-arrow-left"></i> Back to Sign In</a>
        </div>

        <div class="card shadow-sm" style="width: 100%;">
            <div class="card-body p-4">
                <h3 class="text-center mb-3">Forgot Password</h3>
            <p class="text-muted text-center mb-4">
                Enter your account email address. If it exists, we’ll send a reset link to Gmail.
            </p>

            <form id="forgotForm" method="post" action="send_pass_reset.php">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control"
                        placeholder="you@example.com"
                        required
                        value="<?= htmlspecialchars($prefill) ?>"
                    >
                </div>

                <div class="d-grid">
                    <button id="submitBtn" type="submit" class="btn btn-primary">
                        Send Reset Link
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

    <script>
      const form = document.getElementById('forgotForm');
      const submitBtn = document.getElementById('submitBtn');

            // If PHP didn't prefill (cookie timing), prefill from browser cookie.
            (function () {
                const emailInput = document.getElementById('email');
                if (!emailInput || emailInput.value) return;
                const match = document.cookie.match(/(?:^|;\s*)last_auth_email=([^;]+)/);
                if (match && match[1]) {
                    try {
                        emailInput.value = decodeURIComponent(match[1]);
                    } catch (e) {
                        // ignore
                    }
                }
            })();

      // prevent double submit
      form.addEventListener('submit', function() {
        submitBtn.disabled = true;
      });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  </body>
</html>