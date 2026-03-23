<?php

// Handles all AJAX requests

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['jt_admin'])) {
    echo json_encode(['success'=>false,'message'=>'Not authenticated']);
    exit;
}

$conn = new mysqli('localhost','root','','jettransfer');
if ($conn->connect_error) {
    echo json_encode(['success'=>false,'message'=>'DB Error: '.$conn->connect_error]);
    exit;
}
$conn->set_charset('utf8mb4');

$action = $_POST['action'] ?? '';

// ── DELETE
if ($action === 'delete') {
    $id = (int)$_POST['id'];
    if ($conn->query("DELETE FROM destinations WHERE id=$id")) {
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false,'message'=>$conn->error]);
    }
    exit;
}

// ── ADD or EDIT
if (in_array($action, ['add','edit'])) {
    $id   = (int)($_POST['id'] ?? 0);
    $name = $conn->real_escape_string(trim($_POST['name']        ?? ''));
    $slug = $conn->real_escape_string(trim($_POST['slug']        ?? ''));
    $di   = $conn->real_escape_string(trim($_POST['district']    ?? ''));
    $pr   = $conn->real_escape_string(trim($_POST['province']    ?? ''));
    $bl   = $conn->real_escape_string(trim($_POST['badge_label'] ?? ''));
    $sd   = $conn->real_escape_string(trim($_POST['short_desc']  ?? ''));
    $ip   = $conn->real_escape_string(trim($_POST['image_path']  ?? ''));
    $dp   = $conn->real_escape_string(trim($_POST['detail_page'] ?? ''));
    $cat  = $conn->real_escape_string($_POST['category']         ?? 'Other');
    $ia   = isset($_POST['is_active']) ? 1 : 0;

    if ($action === 'add') {
        $sql = "INSERT INTO destinations
                    (name,slug,district,province,badge_label,short_desc,image_path,detail_page,category,is_active)
                VALUES ('$name','$slug','$di','$pr','$bl','$sd','$ip','$dp','$cat',$ia)";
        if ($conn->query($sql)) {
            $newId = $conn->insert_id;
            $row = $conn->query("SELECT * FROM destinations WHERE id=$newId")->fetch_assoc();
            echo json_encode(['success'=>true,'destination'=>$row]);
        } else {
            echo json_encode(['success'=>false,'message'=>$conn->error]);
        }
    } else {
        $sql = "UPDATE destinations SET
                    name='$name',slug='$slug',district='$di',province='$pr',
                    badge_label='$bl',short_desc='$sd',image_path='$ip',
                    detail_page='$dp',category='$cat',is_active=$ia
                WHERE id=$id";
        if ($conn->query($sql)) {
            $row = $conn->query("SELECT * FROM destinations WHERE id=$id")->fetch_assoc();
            echo json_encode(['success'=>true,'destination'=>$row]);
        } else {
            echo json_encode(['success'=>false,'message'=>$conn->error]);
        }
    }
    exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);