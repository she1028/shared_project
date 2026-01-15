<?php

// PHPMailer (no Composer)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/connect.php';

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['send'])) {
  header('Location: index.php');
  exit;
}

if (session_status() === PHP_SESSION_NONE) {
  session_name('client_session');
  session_start();
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$note = trim($_POST['note'] ?? '');

if ($name === '' || strlen($name) < 2 || strlen($name) > 50) {
  header('Location: index.php?contact=invalid');
  exit;
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  header('Location: index.php?contact=invalid');
  exit;
}

if ($subject === '' || strlen($subject) < 3 || strlen($subject) > 100) {
  header('Location: index.php?contact=invalid');
  exit;
}

if ($note === '' || strlen($note) < 10 || strlen($note) > 500) {
  header('Location: index.php?contact=invalid');
  exit;
}

// Prevent header injection / CRLF in subject.
$subject = preg_replace("/\r|\n/", ' ', $subject);

// Resolve user id (needed for FK). Try session first, then email lookup.
$userId = null;
if (!empty($_SESSION['userID'])) {
  $userId = (int)$_SESSION['userID'];
} elseif (!empty($_SESSION['userId'])) {
  $userId = (int)$_SESSION['userId'];
} elseif (!empty($_SESSION['user_id'])) {
  $userId = (int)$_SESSION['user_id'];
}

if ($userId === null) {
  try {
    $stmtLookup = $conn->prepare('SELECT userId FROM users WHERE email = ? LIMIT 1');
    if ($stmtLookup) {
      $stmtLookup->bind_param('s', $email);
      $stmtLookup->execute();
      $resLookup = $stmtLookup->get_result();
      $rowLookup = $resLookup ? $resLookup->fetch_assoc() : null;
      if ($rowLookup && isset($rowLookup['userId'])) {
        $userId = (int)$rowLookup['userId'];
      }
      $stmtLookup->close();
    }
  } catch (mysqli_sql_exception $e) {
    // ignore lookup failures
  }
}

// Require a valid user_id to satisfy your FK (contact_messages.user_id NOT NULL + FK to users.userId)
if ($userId === null) {
  header('Location: index.php?contact=nouser');
  exit;
}

try {
  $stmt = $conn->prepare("INSERT INTO contact_messages (user_id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)");
  if ($stmt) {
    $stmt->bind_param('issss', $userId, $name, $email, $subject, $note);
    $stmt->execute();
    $stmt->close();
  } else {
    header('Location: index.php?contact=dberror');
    exit;
  }
} catch (mysqli_sql_exception $e) {
  error_log('Contact message DB insert failed: ' . $e->getMessage());
  header('Location: index.php?contact=dberror');
  exit;
}

$mail = new PHPMailer(true);

try {
  // $mail->SMTPDebug = SMTP::DEBUG_SERVER;

  // Server settings
  $mail->isSMTP();
  $mail->Host = 'smtp.gmail.com';
  $mail->SMTPAuth = true;
  $mail->Username = 'kristineannmaglinao@gmail.com';
  $mail->Password = 'cxqqvmwrbrblurys';
  $mail->SMTPSecure = 'ssl';
  $mail->Port = 465;

  // Recipients (send ONLY to admin)
  $adminEmail = 'kristineannmaglinao@gmail.com';
  $mail->setFrom('kristineannmaglinao@gmail.com', $name);
  $mail->addAddress($adminEmail);
  $mail->addReplyTo($email, $name);

  // Content
  $mail->isHTML(true);
  $mail->Subject = $subject;
  $mail->Body =
    "<h2>New Contact Form Submission</h2>" .
    "<p><strong>Name:</strong> " . htmlspecialchars($name, ENT_QUOTES) . "</p>" .
    "<p><strong>Email Address:</strong> " . htmlspecialchars($email, ENT_QUOTES) . "</p>" .
    "<p><strong>Subject:</strong> " . htmlspecialchars($subject, ENT_QUOTES) . "</p>" .
    "<p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($note, ENT_QUOTES)) . "</p>";
  $mail->AltBody = "New Contact Form Submission\n\n" .
    "Name: " . $name . "\n" .
    "Email: " . $email . "\n" .
    "Subject: " . $subject . "\n\n" .
    "Message:\n" . $note;

  $mail->send();

  header('Location: index.php?contact=sent');
  exit;
} catch (Exception $e) {
  error_log('Contact form mail failed: ' . $mail->ErrorInfo);
  header('Location: index.php?contact=error');
  exit;
}

?>