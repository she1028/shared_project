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
    $stmt = $conn->prepare("UPDATE notifications n\nJOIN orders o ON n.order_id = o.id\nSET n.is_read = 1\nWHERE (o.client_email = ? OR o.email = ?)");
    $stmt->bind_param('ss', $email, $email);
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
$sql = "UPDATE notifications n\nJOIN orders o ON n.order_id = o.id\nSET n.is_read = 1\nWHERE (o.client_email = ? OR o.email = ?) AND n.id IN ($placeholders)";
$stmt = $conn->prepare($sql);
$params = array_merge([$email, $email], $idParts);

// mysqli_stmt::bind_param requires references
$bind = [];
$bind[] = 'ss' . $types;
for ($i = 0; $i < count($params); $i++) {
    $bind[] = &$params[$i];
}
call_user_func_array([$stmt, 'bind_param'], $bind);
$stmt->execute();
$stmt->close();

echo 'ok';
