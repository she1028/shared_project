<?php

// PHPMailer (no Composer)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer (repo can store it in different folders)
$phpMailerSrc = null;
if (file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')) {
  $phpMailerSrc = __DIR__ . '/PHPMailer/src';
} elseif (file_exists(__DIR__ . '/PHPMailer/PHPMailer/src/PHPMailer.php')) {
  $phpMailerSrc = __DIR__ . '/PHPMailer/PHPMailer/src';
}

if (!$phpMailerSrc) {
  http_response_code(500);
  die('PHPMailer is missing.');
}

require_once $phpMailerSrc . '/Exception.php';
require_once $phpMailerSrc . '/PHPMailer.php';
require_once $phpMailerSrc . '/SMTP.php';

// DB connection
require_once __DIR__ . '/connect.php';

$email = trim((string)($_POST['email'] ?? ''));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: forgot_password.php');
    exit;
}

$debugMode = isset($_GET['debug']) && $_GET['debug'] === '1';

// Ensure reset columns exist on users table (safe no-op if already present)
$needsHash = true;
$needsExp = true;
if ($res = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_token_hash'")) {
    $needsHash = ($res->num_rows === 0);
    $res->free();
}
if ($res = $conn->query("SHOW COLUMNS FROM users LIKE 'reset_token_expires_at'")) {
    $needsExp = ($res->num_rows === 0);
    $res->free();
}
if ($needsHash) {
    $conn->query("ALTER TABLE users ADD COLUMN reset_token_hash VARCHAR(64) NULL");
}
if ($needsExp) {
    $conn->query("ALTER TABLE users ADD COLUMN reset_token_expires_at DATETIME NULL");
}

// Token
$token = bin2hex(random_bytes(16));
$token_hash = hash('sha256', $token);
$expiry = date('Y-m-d H:i:s', time() + 60 * 30);

// Check if user exists (don’t reveal result to the requester)
$userId = null;
$lookup = $conn->prepare("SELECT userID FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
$lookup->bind_param('s', $email);
$lookup->execute();
$lookupRes = $lookup->get_result();
if ($lookupRes) {
  $row = $lookupRes->fetch_assoc();
  if ($row && isset($row['userID'])) {
    $userId = (int)$row['userID'];
  }
}
$lookup->close();

// Save token only if the account exists (UI response stays generic)
$userExists = $userId !== null;
if ($userExists) {
  $sql = "UPDATE users 
      SET reset_token_hash = ?, 
        reset_token_expires_at = ? 
      WHERE userID = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ssi", $token_hash, $expiry, $userId);
  $stmt->execute();
  $stmt->close();
}

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

$mailError = null;
$smtpDebug = '';

try {
  if ($debugMode) {
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function ($str, $level) use (&$smtpDebug) {
      $smtpDebug .= '[' . $level . '] ' . $str . "\n";
    };
  }

    // Server settings and authentication
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'kristineannmaglinao@gmail.com';
    $mail->Password = 'lrny garh dydo qhvx';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->Timeout = 15;
    $mail->CharSet = 'UTF-8';

    // Gmail commonly requires the From address to match the authenticated account
    $mail->setFrom($mail->Username, 'YMZM Support');
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

    if ($userExists) {
      $mail->send();
    }

} catch (Exception $e) {
  // Log error (do not show to user in production)
  $mailError = $mail->ErrorInfo ?: $e->getMessage();
  error_log($mailError);
}

$hostHeaderUi = $_SERVER['HTTP_HOST'] ?? '';
$isLocalUi = (stripos($hostHeaderUi, 'localhost') !== false) || (stripos($hostHeaderUi, '127.0.0.1') !== false);

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
          <?php if ($isLocalUi): ?>
            <div class="alert alert-info mt-3 mb-0">
              <div class="fw-semibold">Local test info</div>
              <div class="small">If Gmail SMTP is blocked on this machine, you can still test using this link:</div>
              <div class="small"><a href="<?= htmlspecialchars($link, ENT_QUOTES) ?>"><?= htmlspecialchars($link) ?></a></div>
              <?php if ($mailError): ?>
                <div class="small text-muted mt-2">Mail error: <?= htmlspecialchars($mailError) ?></div>
              <?php endif; ?>
              <?php if ($debugMode && $smtpDebug): ?>
                <details class="mt-2">
                  <summary class="small">SMTP debug</summary>
                  <pre class="small mb-0" style="white-space:pre-wrap;"><?= htmlspecialchars($smtpDebug) ?></pre>
                </details>
              <?php endif; ?>
            </div>
          <?php endif; ?>
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
    });
  </script>
</body>
</html>
