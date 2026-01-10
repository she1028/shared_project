<?php
include("connect.php"); // $conn and executeQuery()
header('Content-Type: application/json');

if (!isset($_GET['email'])) {
    echo json_encode(['exists' => false]);
    exit();
}

$email = trim($_GET['email']);
$res = executeQuery("SELECT * FROM users WHERE email='$email'");
echo json_encode(['exists' => mysqli_num_rows($res) > 0]);
