<?php

require_once __DIR__ . '/db_packages.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit;
}

$id = (int)$_GET['id'];

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    
    // Format dates for display
    $booking['travel_date'] = date('d M Y', strtotime($booking['travel_date']));
    $booking['created_at'] = date('d M Y, h:i A', strtotime($booking['created_at']));
    
    echo json_encode([
        'success' => true,
        'customer_name' => $booking['customer_name'],
        'email' => $booking['email'],
        'phone' => $booking['phone'],
        'package_name' => $booking['package_name'],
        'price' => $booking['price'],
        'travel_date' => $booking['travel_date'],
        'guests' => $booking['guests'],
        'created_at' => $booking['created_at'],
        'custom_duration' => $booking['custom_duration'] ?? null,
        'custom_hotel' => $booking['custom_hotel'] ?? null,
        'custom_vehicle' => $booking['custom_vehicle'] ?? null,
        'custom_group_size' => $booking['custom_group_size'] ?? null,
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>