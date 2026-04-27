<?php
// ── Returns whether admin is logged in ──
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['jt_admin']) && $_SESSION['jt_admin'] === true) {
    echo json_encode([
        'loggedIn' => true,
        'name'     => $_SESSION['jt_admin_name']  ?? 'Admin',
        'email'    => $_SESSION['jt_admin_email'] ?? 'admin@jettransfer.com'
    ]);
} else {
    echo json_encode(['loggedIn' => false]);
}
?>