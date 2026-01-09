<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$code = $data['code'] ?? '';

if (!$code) {
    echo json_encode(['success' => false, 'message' => 'Code is required']);
    exit;
}

if (isset($_SESSION['verification_code']) && $_SESSION['verification_code'] == $code) {
    echo json_encode(['success' => true, 'message' => 'Code correct']);
} else {
    echo json_encode(['success' => false, 'message' => 'Incorrect code']);
}
?>
