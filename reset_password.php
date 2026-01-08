<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$newPassword = $data['password'] ?? '';

if (!$email || !$newPassword) {
    echo json_encode(['success' => false, 'message' => 'Email or password missing']);
    exit;
}
$conn = new mysqli('localhost', 'root', '', 'your_database');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

$passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password=? WHERE email=?");
$stmt->bind_param("ss", $passwordHash, $email);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Password reset successful']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update password']);
}

$stmt->close();
$conn->close();
