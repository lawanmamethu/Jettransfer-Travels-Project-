<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['jt_admin'])) {
    header('Location: login.php');
    exit;
}

// DB connection
$conn = new mysqli('localhost', 'root', '', 'jettransfer');

if ($conn->connect_error) {
    die('
    <div style="font-family:sans-serif;padding:3rem;text-align:center">
        <h2 style="color:#EF4444">&#9888;&#65039; Database Error</h2>
        <p style="color:#64748B;margin-top:1rem">' . $conn->connect_error . '</p>
        <p style="margin-top:1rem;font-size:.9rem;color:#94A3B8">
            Make sure MySQL is running in XAMPP and database name is <strong>jettransfer</strong>
        </p>
    </div>');
}

$conn->set_charset('utf8mb4');