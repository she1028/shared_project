<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

header('Content-Type: application/json');

// Get data from frontend
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

// Generate 6-digit verification code
$code = rand(100000, 999999);

// Save code and email in session
$_SESSION['verification_code'] = $code;
$_SESSION['verification_email'] = $email;
$_SESSION['code_time'] = time();

// Send email via admin Gmail
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'kristineannmaglinao@gmail.com'; // <-- Your Gmail here
    $mail->Password = 'uufsxxjmqcqmqeph';   // <-- Gmail App Password here
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('kristineannmaglinao@gmail.com', 'Website Admin');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Your Verification Code';
    $mail->Body = "Hello,<br><br>Your verification code is: <b>$code</b><br><br>Use this to reset your password.";

    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Code sent to email']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
}
?>
