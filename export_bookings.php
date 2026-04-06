<?php
// ── export_bookings.php ───────────────────────────────────────
// Place in htdocs/jettransfer/
// Logged-in users download their own bookings as CSV
// ─────────────────────────────────────────────────────────────
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'db.php';

$uid   = (int)$_SESSION['user_id'];
$email = $conn->real_escape_string($_SESSION['user_email'] ?? '');
$name  = $_SESSION['user_name'] ?? 'User';

// Fetch this user's bookings (matched by email — same as profile bookings tab)
$r = $conn->query("
    SELECT
        id,
        package_name,
        price,
        travel_date,
        guests,
        status,
        created_at
    FROM bookings
    WHERE email = '$email'
    ORDER BY created_at DESC
");

$rows = [];
if ($r) while ($row = $r->fetch_assoc()) $rows[] = $row;
$conn->close();

// Build filename
$safeName = preg_replace('/[^a-z0-9_]/i', '_', $name);
$filename = 'JettransferBookings_' . $safeName . '_' . date('Ymd') . '.csv';

// Send CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// BOM for Excel UTF-8 compatibility
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

// ── Header row
fputcsv($out, [
    'Booking #',
    'Package Name',
    'Price',
    'Travel Date',
    'Guests',
    'Status',
    'Booked On'
]);

// ── Data rows
if (empty($rows)) {
    fputcsv($out, ['No bookings found.', '', '', '', '', '', '']);
} else {
    foreach ($rows as $row) {
        fputcsv($out, [
            '#' . $row['id'],
            $row['package_name'],
            $row['price'],
            date('d M Y', strtotime($row['travel_date'])),
            $row['guests'] . ' guest' . ($row['guests'] > 1 ? 's' : ''),
            $row['status'],
            date('d M Y, g:i A', strtotime($row['created_at']))
        ]);
    }
}

fclose($out);
exit;
