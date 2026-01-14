<?php
$dbhost = "localhost";
$dbuser = "root";
$dbpass = "";      // set to your MySQL root password if it has one
$dbname = "ymzm";


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
  $conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
  $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
  die("Database connection failed: " . $e->getMessage());
}

function executeQuery($query) {
  return mysqli_query($GLOBALS['conn'], $query);
}
?>