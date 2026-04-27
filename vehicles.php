<?php
// ── vehicles.php — handles ALL vehicle operations ──
header('Content-Type: application/json');
require_once 'db_vehicle.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'get';


//  GET — fetch all vehicles (no auth needed, public)
if ($action === 'get') {
    $result   = $conn->query("SELECT * FROM vehicles ORDER BY id ASC");
    $vehicles = [];
    while ($row = $result->fetch_assoc()) {
        $vehicles[] = $row;
    }
    echo json_encode(['success' => true, 'count' => count($vehicles), 'vehicles' => $vehicles]);
    $conn->close();
    exit;
}



//  ADD — insert new vehicle
if ($action === 'add') {
    $name             = trim($_POST['name']             ?? '');
    $plate            = trim($_POST['plate']            ?? '');
    $type             = trim($_POST['type']             ?? '');
    $seats            = intval($_POST['seats']          ?? 0);
    $fuel             = trim($_POST['fuel']             ?? '');
    $luggage          = trim($_POST['luggage']          ?? '');
    $image            = trim($_POST['image']            ?? '');
    $availability     = trim($_POST['availability']     ?? 'Available');
    $condition_status = trim($_POST['condition_status'] ?? 'Good Condition');

    if (!$name || !$plate || !$type || !$seats || !$fuel || !$luggage) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }

    // Sri Lanka plate: 2 letters + space + 2-3 letters + space + 4 digits (e.g. WP CAB 1234)
    if (!preg_match('/^[A-Z]{2}\s[A-Z]{2,3}\s\d{4}$/i', $plate)) {
        echo json_encode(['success' => false, 'message' => 'Invalid plate number. Use format: WP CAB 1234']);
        exit;
    }

    $plate = strtoupper($plate); // store consistently in uppercase

    $stmt = $conn->prepare("INSERT INTO vehicles (name, plate, type, seats, fuel, luggage, image, availability, condition_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssisssss', $name, $plate, $type, $seats, $fuel, $luggage, $image, $availability, $condition_status);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Vehicle added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed: ' . $conn->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}


//  UPDATE — update availability and condition
if ($action === 'update') {
    $id               = intval($_POST['id']               ?? 0);
    $availability     = trim($_POST['availability']       ?? '');
    $condition_status = trim($_POST['condition_status']   ?? '');

    if (!$id || !$availability || !$condition_status) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $allowed_availability = ['Available', 'Booked'];
    $allowed_condition    = ['Good Condition', 'Under Maintenance'];

    if (!in_array($availability, $allowed_availability) || !in_array($condition_status, $allowed_condition)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status values']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE vehicles SET availability = ?, condition_status = ? WHERE id = ?");
    $stmt->bind_param('ssi', $availability, $condition_status, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Vehicle updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed: ' . $conn->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}


//  DELETE — remove a vehicle
if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid vehicle ID']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM vehicles WHERE id = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Vehicle not found']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Vehicle deleted successfully']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed: ' . $conn->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}


//  ENQUIRE — submit enquiry (public, no auth needed)
if ($action === 'enquire') {
    // This action doesn't need admin session — it's public
    $name         = trim($_POST['name']         ?? '');
    $email        = trim($_POST['email']        ?? '');
    $phone        = trim($_POST['phone']        ?? '');
    $vehicle_type = trim($_POST['vehicle_type'] ?? '');
    $travel_date  = trim($_POST['travel_date']  ?? '');
    $passengers   = intval($_POST['passengers'] ?? 0);

    if (!$name || !$email || !$vehicle_type || !$travel_date) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        exit;
    }

    // Travel date must be today or in the future
    $today = new DateTime('today');
    $chosen = DateTime::createFromFormat('Y-m-d', $travel_date);
    if (!$chosen || $chosen < $today) {
        echo json_encode(['success' => false, 'message' => 'Travel date cannot be in the past. Please select today or a future date.']);
        exit;
    }

    // Phone: optional, but if provided must be +94 followed by exactly 9 digits
    if ($phone !== '') {
        $phone_clean = preg_replace('/\s+/', '', $phone);
        if (!preg_match('/^\+94\d{9}$/', $phone_clean)) {
            echo json_encode(['success' => false, 'message' => 'Phone must be +94 followed by exactly 9 digits (e.g. +94771234567).']);
            exit;
        }
        $phone = $phone_clean;
    }

    $stmt = $conn->prepare("INSERT INTO enquiries (name, email, phone, vehicle_type, travel_date, passengers) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssssi', $name, $email, $phone, $vehicle_type, $travel_date, $passengers);

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        echo json_encode(['success' => true, 'message' => 'Availability check submitted successfully! We will contact you soon.', 'id' => $new_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed: ' . $conn->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}


//  GET ENQUIRIES — fetch all enquiries (admin only)
if ($action === 'get_enquiries') {
    $result     = $conn->query("SELECT * FROM enquiries ORDER BY created_at DESC");
    $enquiries  = [];
    while ($row = $result->fetch_assoc()) {
        $enquiries[] = $row;
    }
    echo json_encode(['success' => true, 'count' => count($enquiries), 'enquiries' => $enquiries]);
    $conn->close();
    exit;
}


//  UPDATE ENQUIRY STATUS — mark as Available or Not Available (admin only)
if ($action === 'update_enquiry') {
    $id           = intval($_POST['id']           ?? 0);
    $status       = trim($_POST['status']         ?? '');

    if (!$id || !in_array($status, ['Pending', 'Available', 'Not Available'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE enquiries SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Enquiry updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed: ' . $conn->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}


//  DELETE ENQUIRY — remove an enquiry (admin only)
if ($action === 'delete_enquiry') {
    $id = intval($_POST['id'] ?? 0);

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid enquiry ID']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM enquiries WHERE id = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Enquiry deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed: ' . $conn->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}


//  CHECK STATUS — user looks up their own requests by email
if ($action === 'check_status') {
    $email = trim($_POST['email'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, vehicle_type, travel_date, passengers, status, created_at FROM enquiries WHERE email = ? ORDER BY created_at DESC");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $enquiries = [];
    while ($row = $result->fetch_assoc()) {
        $enquiries[] = $row;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'enquiries' => $enquiries]);
    $conn->close();
    exit;
}

// ── Unknown action ──
echo json_encode(['success' => false, 'message' => 'Unknown action']);
$conn->close();
?>