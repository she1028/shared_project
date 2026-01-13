<?php
include __DIR__ . '/../connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'method_not_allowed';
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$idsRaw = isset($_POST['ids']) ? trim($_POST['ids']) : '';
if ($email === '') {
    http_response_code(400);
    echo 'missing_email';
    exit;
}

if ($idsRaw === '') {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE recipient_email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->close();
    echo 'ok';
    exit;
}

$idParts = array_filter(array_map('intval', explode(',', $idsRaw)), function ($n) { return $n > 0; });
if (empty($idParts)) {
    echo 'ok';
    exit;
}

$placeholders = implode(',', array_fill(0, count($idParts), '?'));
$types = str_repeat('i', count($idParts));
$sql = "UPDATE notifications SET is_read = 1 WHERE recipient_email = ? AND id IN ($placeholders)";
$stmt = $conn->prepare($sql);
$params = array_merge([$email], $idParts);
$stmt->bind_param('s' . $types, ...$params);
$stmt->execute();
$stmt->close();

echo 'ok';
