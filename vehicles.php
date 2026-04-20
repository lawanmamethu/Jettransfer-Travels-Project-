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
    exit;
}


//  All other actions require admin session

session_start();
if (!isset($_SESSION['jt_admin']) || $_SESSION['jt_admin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
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

    $stmt = $conn->prepare("INSERT INTO vehicles (name, plate, type, seats, fuel, luggage, image, availability, condition_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssisssss', $name, $plate, $type, $seats, $fuel, $luggage, $image, $availability, $condition_status);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Vehicle added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed: ' . $conn->error]);
    }
    $stmt->close();
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
    exit;
}


//  ENQUIRE — submit enquiry (public, no auth needed)

if ($action === 'enquire') {
    // This doesn't need admin session 
    $name         = trim($_POST['name']         ?? '');
    $email        = trim($_POST['email']        ?? '');
    $phone        = trim($_POST['phone']        ?? '');
    $vehicle_type = trim($_POST['vehicle_type'] ?? '');
    $travel_date  = trim($_POST['travel_date']  ?? '');
    $passengers   = intval($_POST['passengers'] ?? 0);
    $message      = trim($_POST['message']      ?? '');

    if (!$name || !$email || !$vehicle_type || !$travel_date) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO enquiries (name, email, phone, vehicle_type, travel_date, passengers, message) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssssds', $name, $email, $phone, $vehicle_type, $travel_date, $passengers, $message);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Enquiry submitted successfully! We will contact you soon.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed: ' . $conn->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}


//  GET ENQUIRIES — fetch all enquiries 

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


//  UPDATE ENQUIRY STATUS — mark as Responded 

if ($action === 'update_enquiry') {
    $id     = intval($_POST['id']     ?? 0);
    $status = trim($_POST['status']   ?? '');

    if (!$id || !in_array($status, ['Pending', 'Responded'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE enquiries SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Enquiry status updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed: ' . $conn->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}


//  DELETE ENQUIRY — remove an enquiry 

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

// ── Unknown action ──
echo json_encode(['success' => false, 'message' => 'Unknown action']);
$conn->close();
?>