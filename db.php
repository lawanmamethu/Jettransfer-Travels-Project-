<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jettransfer');  

$conn         = null;
$db_connected = false;

$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME); 

if ($conn->connect_error) {
    $db_connected = false;
    $conn         = null;
} else {
    $conn->set_charset('utf8mb4');
    $db_connected = true;
}


if (!isset($conn)) {
    $conn = new mysqli('localhost', 'root', '', 'jettransfer');
    if ($conn->connect_error) die('DB Error: ' . $conn->connect_error);
    $conn->set_charset('utf8mb4');
}