<?php
require_once __DIR__ . '/connect.php';

$token = $_POST['token'] ?? '';
$newPassword = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($token) || empty($newPassword) || empty($confirmPassword)) {
    die("Invalid request.");
}

// Confirm match
if ($newPassword !== $confirmPassword) {
    die("Passwords do not match.");
}

// Complexity check (at least one upper, one lower, one special, min 8)
$passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}$/';
if (!preg_match($passwordRegex, $newPassword)) {
    die("Password does not meet complexity requirements.");
}

// Hash token
$token_hash = hash('sha256', $token);

// Find user by token
$sql = "SELECT userID, reset_token_expires_at 
        FROM users 
        WHERE reset_token_hash = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();

$user = $result->fetch_assoc();

if (!$user) {
    die("Invalid or used token.");
}

// Check expiry
if (strtotime($user['reset_token_expires_at']) < time()) {
    die("Token expired.");
}

// 🔐 SAME PASSWORD PROCESS AS SIGN UP
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Update password & clear token
$update = "UPDATE users 
           SET password = ?, 
               reset_token_hash = NULL,
               reset_token_expires_at = NULL
           WHERE userID = ?";

$stmt = $conn->prepare($update);
$stmt->bind_param("si", $hashedPassword, $user['userID']);
$stmt->execute();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Password Reset</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height:100vh;">

  <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Password Reset</h5>
        </div>
        <div class="modal-body">
          <p>Password successfully reset. You may now log in.</p>
        </div>
        <div class="modal-footer">
          <a href="auth.php" class="btn btn-primary">Back to Sign In</a>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var modalEl = document.getElementById('successModal');
      var modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
      modal.show();

      setTimeout(function () {
        window.location.href = 'auth.php';
      }, 4000);
    });
  </script>
</body>
</html>
