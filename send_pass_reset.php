<?php

// PHPMailer (no Composer)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// PHPMailer is vendored in this repo; load it from whichever layout exists.
$phpMailerCandidates = [
  __DIR__ . '/PHPMailer/src',
  __DIR__ . '/PHPMailer/PHPMailer/src',
];

$phpMailerLoaded = false;
foreach ($phpMailerCandidates as $srcDir) {
  if (
    is_file($srcDir . '/Exception.php') &&
    is_file($srcDir . '/PHPMailer.php') &&
    is_file($srcDir . '/SMTP.php')
  ) {
    require_once $srcDir . '/Exception.php';
    require_once $srcDir . '/PHPMailer.php';
    require_once $srcDir . '/SMTP.php';
    $phpMailerLoaded = true;
    break;
  }
}

if (!$phpMailerLoaded) {
  die('Mailer library is missing. Please ensure PHPMailer exists in /PHPMailer/PHPMailer/src.');
}

// DB connection
require_once __DIR__ . '/connect.php';

$email = $_POST['email'] ?? '';

// Enforce that the email matches the one typed on this device's Sign In form
$cookieEmail = $_COOKIE['last_auth_email'] ?? null;
if (!$cookieEmail) {
    // No cookie set — prompt user to go back to sign in (render modal page)
    ?>
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Action required</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center" style="height:100vh;">
      <div class="modal fade show" id="infoModal" tabindex="-1" style="display:block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Please use Sign In first</h5></div>
            <div class="modal-body">
              <p>Please enter your email on the Sign In form first (on this device). Then use the same email here to request a password reset.</p>
            </div>
            <div class="modal-footer">
              <a href="auth.php" class="btn btn-primary">Back to Sign In</a>
            </div>
          </div>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}

if (urldecode($cookieEmail) !== $email) {
    // Cookie exists but does not match — show helpful modal pointing back to auth.php
    ?>
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Email mismatch</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center" style="height:100vh;">
      <div class="modal fade show" id="mismatchModal" tabindex="-1" style="display:block; background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Email does not match</h5></div>
            <div class="modal-body">
              <p>The email you entered here does not match the email you typed on the Sign In form on this device. Please use the same email to request a reset, or go back to Sign In.</p>
            </div>
            <div class="modal-footer">
              <a href="auth.php" class="btn btn-primary">Back to Sign In</a>
            </div>
          </div>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// Token
$token = bin2hex(random_bytes(16));
$token_hash = hash('sha256', $token);
$expiry = date('Y-m-d H:i:s', time() + 60 * 30);

// Save token (email matched cookie)
$sql = "UPDATE users 
        SET reset_token_hash = ?, 
            reset_token_expires_at = ? 
        WHERE email = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $token_hash, $expiry, $email);
$stmt->execute();

// Build a reset link that is reachable from other devices on the LAN
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$hostHeader = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? gethostbyname(gethostname());
$host = $hostHeader;
// If host is localhost or loopback, try to replace with LAN IP
if (preg_match('/^(localhost|127\.0\.0\.1|::1)$/i', preg_replace('/(:\d+)$/', '', $hostHeader))) {
    $lanIp = gethostbyname(gethostname());
    if ($lanIp === '127.0.0.1' || $lanIp === false) {
        $socket = @stream_socket_client('udp://8.8.8.8:53', $errno, $errstr, 1);
        if ($socket) {
            $local = stream_socket_get_name($socket, false);
            if ($local) {
                $parts = explode(':', $local);
                $lanIp = $parts[0];
            }
            fclose($socket);
        }
    }
    if ($lanIp && $lanIp !== '127.0.0.1') {
        $port = $_SERVER['SERVER_PORT'] ?? null;
        $host = $lanIp . ($port && !in_array((int)$port, [80, 443]) ? ':' . $port : '');
    }
}
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$link = $protocol . '://' . $host . $basePath . '/reset.php?token=' . urlencode($token);

// Send email (do not reveal account existence)
$mail = new PHPMailer(true);

try {
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER;

    // Server settings and authentication
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'kristineannmaglinao@gmail.com';
    $mail->Password = 'lrny garh dydo qhvx';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Use a From address that matches the authenticated account (more reliable with Gmail SMTP)
    $mail->setFrom('kristineannmaglinao@gmail.com', 'YMZM Support');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Password Reset';
    $mail->Body = "
        Click the link below to reset your password:<br><br>
        <a href='" . htmlspecialchars($link, ENT_QUOTES) . "'>
            Reset Password
        </a>
    ";
    $mail->AltBody = "Reset your password: " . $link;

    $mail->send();

} catch (Exception $e) {
    // Log error (do not show to user)
    error_log($mail->ErrorInfo);
}

// Always show generic success message — show as modal with a Back button to auth.php
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Password Reset Sent</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="height:100vh;">

  <!-- Modal markup -->
  <div class="modal fade" id="resetSentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Password Reset</h5>
        </div>
        <div class="modal-body">
          <p>We have sent a password reset link. Please check your email.</p>
        </div>
        <div class="modal-footer">
          <a href="auth.php" class="btn btn-secondary">Back to Sign In</a>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var modalEl = document.getElementById('resetSentModal');
      var modal = new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
      modal.show();

      // Give the user time to read, then return to Sign In.
      setTimeout(function () {
        window.location.href = 'auth.php';
      }, 6000);
    });
  </script>
</body>
</html>
