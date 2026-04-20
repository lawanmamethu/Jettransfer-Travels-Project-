<?php

session_start();


require_once __DIR__ . '/db_packages.php';
$pdo = getDB();

// Fetch admin info for topbar dropdown
$admName = 'Admin';
$admEmail = 'admin@jettransfer.com';
$admInit = 'A';

// Try to get admin info from admins table if exists
try {
    $stmt = $pdo->query("SELECT name, email FROM admins WHERE id = 1 LIMIT 1");
    if ($stmt && $row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $admName = $row['name'];
        $admEmail = $row['email'];
        $admInit = strtoupper(substr($row['name'], 0, 1));
    }
} catch (Exception $e) {
    // Table might not exist, use defaults
}

// ── Helper: sanitize ─────────────────────────────────────────
function clean(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

// ============================================================
//  HANDLE POST — Save new booking (from package.html form)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    $required = ['package_name','price','customer_name','email','phone','travel_date','guests'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['status'=>'error','message'=>"Field '{$field}' is required."]);
            exit;
        }
    }

    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    if (!$email) { echo json_encode(['status'=>'error','message'=>'Invalid email.']); exit; }

    $guests = (int)$_POST['guests'];
    if ($guests <= 0) { echo json_encode(['status'=>'error','message'=>'Guests must be at least 1.']); exit; }

    $travelDate = clean($_POST['travel_date']);
    $dateObj    = DateTime::createFromFormat('Y-m-d', $travelDate);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $travelDate) {
        echo json_encode(['status'=>'error','message'=>'Invalid date format.']); exit;
    }

    $bookingType = $_POST['booking_type'] ?? 'standard';
    $customDuration = ($bookingType === 'custom' && !empty($_POST['custom_duration'])) ? (int)$_POST['custom_duration'] : null;
    $customHotel = ($bookingType === 'custom' && !empty($_POST['custom_hotel'])) ? clean($_POST['custom_hotel']) : null;
    $customVehicle = ($bookingType === 'custom' && !empty($_POST['custom_vehicle'])) ? clean($_POST['custom_vehicle']) : null;
    $customGroupSize = ($bookingType === 'custom' && !empty($_POST['custom_group_size'])) ? clean($_POST['custom_group_size']) : null;

    $stmt = $pdo->prepare(
        'INSERT INTO bookings (package_name, price, customer_name, email, phone, travel_date, guests, 
         booking_type, custom_duration, custom_hotel, custom_vehicle, custom_group_size)
         VALUES (:package_name, :price, :customer_name, :email, :phone, :travel_date, :guests,
         :booking_type, :custom_duration, :custom_hotel, :custom_vehicle, :custom_group_size)'
    );
    $stmt->execute([
        ':package_name'  => clean($_POST['package_name']),
        ':price'         => clean($_POST['price']),
        ':customer_name' => clean($_POST['customer_name']),
        ':email'         => $email,
        ':phone'         => clean($_POST['phone']),
        ':travel_date'   => $travelDate,
        ':guests'        => $guests,
        ':booking_type'  => $bookingType,
        ':custom_duration' => $customDuration,
        ':custom_hotel'    => $customHotel,
        ':custom_vehicle'  => $customVehicle,
        ':custom_group_size' => $customGroupSize,
    ]);

    echo json_encode(['status'=>'success','message'=>'Booking confirmed!']);
    exit;
}

// ============================================================
//  HANDLE DELETE — Cancel booking
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    if ($id > 0) {
        $pdo->prepare('DELETE FROM bookings WHERE id = :id')->execute([':id' => $id]);
    }
    header('Location: bookings.php?deleted=1');
    exit;
}

// ============================================================
//  FETCH BOOKINGS
// ============================================================
$search  = clean($_GET['search']  ?? '');
$type    = clean($_GET['type']    ?? '');  // filter by booking type
$page    = max(1, (int)($_GET['page'] ?? 1));
$limit   = 15;
$offset  = ($page - 1) * $limit;

$where  = [];
$params = [];

if ($search) {
    $where[]            = '(customer_name LIKE :search OR package_name LIKE :search2 OR email LIKE :search3)';
    $params[':search']  = "%$search%";
    $params[':search2'] = "%$search%";
    $params[':search3'] = "%$search%";
}

if ($type && in_array($type, ['standard', 'custom'])) {
    $where[] = 'booking_type = :type';
    $params[':type'] = $type;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM bookings $whereSql");

foreach ($params as $k => $v) {
    $stmtTotal->bindValue($k, $v);
}

$stmtTotal->execute();
$total = (int)$stmtTotal->fetchColumn();

$stmt     = $pdo->prepare("SELECT * FROM bookings $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$bookings   = $stmt->fetchAll();
$totalPages = (int)ceil($total / $limit);

// Stats
$totalBookings = (int)$pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
$todayBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$totalGuests   = (int)$pdo->query('SELECT SUM(guests) FROM bookings')->fetchColumn();
$customBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_type = 'custom'")->fetchColumn();
$standardBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_type = 'standard' OR booking_type IS NULL")->fetchColumn();

// Store all bookings for export (without pagination limit for full report)
$stmtAll = $pdo->prepare("SELECT * FROM bookings ORDER BY created_at DESC");
$stmtAll->execute();
$allBookings = $stmtAll->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – Bookings | Jettransfer Travels</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0A7EA4;
            --primary-dark: #065A7A;
            --primary-light: #E0F2FA;
            --secondary: #F59E0B;
            --accent: #10B981;
            --danger: #EF4444;
            --text-dark: #0F172A;
            --text-light: #94A3B8;
            --bg: #F1F5F9;
            --sidebar-bg: #0F172A;
            --border: #E2E8F0;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 40px rgba(0,0,0,0.15);
            --sidebar-w: 260px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Manrope',sans-serif; background:var(--bg); color:var(--text-dark); min-height:100vh; display:flex; }

        /* ── SIDEBAR ── */
        .sidebar { width:var(--sidebar-w); background:var(--sidebar-bg); min-height:100vh; position:fixed; left:0; top:0; bottom:0; display:flex; flex-direction:column; z-index:100; transition:transform .3s ease; }
        .sidebar-brand { padding:1.8rem 1.5rem 1.5rem; border-bottom:1px solid rgba(255,255,255,.07); display:flex; align-items:center; gap:.8rem; }
        .sidebar-logo { width:42px; height:42px; background:linear-gradient(135deg,var(--primary),var(--accent)); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
        .sidebar-brand-text h2 { font-family:'Sora',sans-serif; font-size:1rem; font-weight:800; color:#fff; }
        .sidebar-brand-text span { font-size:.72rem; color:rgba(255,255,255,.4); letter-spacing:1.5px; text-transform:uppercase; }
        .sidebar-nav { flex:1; padding:1.2rem .8rem; overflow-y:auto; }
        .nav-label { font-size:.68rem; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,.25); padding:.5rem .8rem; margin-top:.8rem; margin-bottom:.3rem; }
        .nav-item { display:flex; align-items:center; gap:.75rem; padding:.75rem .9rem; border-radius:12px; color:rgba(255,255,255,.55); text-decoration:none; font-size:.9rem; font-weight:500; transition:all .2s ease; margin-bottom:.2rem; }
        .nav-item:hover { background:rgba(255,255,255,.07); color:rgba(255,255,255,.9); }
        .nav-item.active { background:linear-gradient(135deg,rgba(10,126,164,.35),rgba(16,185,129,.2)); color:#fff; box-shadow:inset 0 0 0 1px rgba(10,126,164,.4); }
        .nav-icon { width:20px; height:20px; flex-shrink:0; }
        .sidebar-footer { padding:1rem .8rem 1.5rem; border-top:1px solid rgba(255,255,255,.07); }
        .admin-profile { display:flex; align-items:center; gap:.75rem; padding:.75rem .9rem; border-radius:12px; background:rgba(255,255,255,.05); margin-bottom:.5rem; }
        .admin-avatar { width:36px; height:36px; background:linear-gradient(135deg,var(--primary),var(--accent)); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:.9rem; font-weight:700; color:#fff; flex-shrink:0; }
        .admin-info h4 { font-size:.85rem; font-weight:600; color:#fff; }
        .admin-info p { font-size:.72rem; color:rgba(255,255,255,.4); }
        .btn-logout { display:flex; align-items:center; gap:.6rem; padding:.7rem .9rem; border-radius:12px; color:rgba(255,255,255,.5); font-size:.88rem; font-weight:500; cursor:pointer; transition:all .2s; border:none; background:none; width:100%; font-family:'Manrope',sans-serif; text-decoration:none; }
        .btn-logout:hover { background:rgba(239,68,68,.15); color:#FCA5A5; }
        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:99; }
        .sidebar-overlay.show { display:block; }

        /* ── MAIN ── */
        .main { margin-left:var(--sidebar-w); flex:1; display:flex; flex-direction:column; }
        .topbar { background:#fff; border-bottom:1px solid var(--border); padding:0 2rem; height:70px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:50; box-shadow:var(--shadow-sm); }
        .topbar-left { display:flex; align-items:center; gap:1rem; }
        .hamburger { display:none; background:none; border:none; cursor:pointer; padding:6px; color:var(--text-dark); }
        .page-title h1 { font-family:'Sora',sans-serif; font-size:1.2rem; font-weight:700; }
        .page-title p { font-size:.8rem; color:var(--text-light); }

        /* Admin dropdown in topbar */
        .admin-dropdown { position:relative; }
        .admin-pill { display:flex; align-items:center; gap:.5rem; padding:.4rem .9rem .4rem .45rem; border-radius:50px; border:2px solid #E2E8F0; background:white; cursor:pointer; font-family:'Manrope',sans-serif; font-weight:600; font-size:.88rem; color:var(--text-dark); transition:all .25s; white-space:nowrap; }
        .admin-pill:hover { border-color:var(--primary); box-shadow:0 2px 12px rgba(10,126,164,.15); }
        .admin-pill-avatar { width:30px; height:30px; border-radius:50%; background:linear-gradient(135deg,var(--primary-dark),#043D54); display:flex; align-items:center; justify-content:center; font-size:.8rem; font-weight:700; color:#fff; flex-shrink:0; }
        .admin-dd-menu { position:absolute; right:0; top:calc(100% + .6rem); background:white; border:1px solid #E2E8F0; border-radius:18px; box-shadow:0 10px 40px rgba(0,0,0,.14); min-width:240px; z-index:200; opacity:0; transform:translateY(-8px) scale(.97); pointer-events:none; transition:all .22s ease; }
        .admin-dd-menu.open { opacity:1; transform:translateY(0) scale(1); pointer-events:all; }
        .adm-header { display:flex; align-items:center; gap:.75rem; padding:1rem 1.1rem .8rem; }
        .adm-avatar { width:44px; height:44px; border-radius:50%; background:linear-gradient(135deg,var(--primary-dark),#043D54); display:flex; align-items:center; justify-content:center; font-size:1.2rem; font-weight:700; color:#fff; flex-shrink:0; }
        .adm-name { font-weight:700; font-size:.92rem; color:var(--text-dark); margin-bottom:.1rem; }
        .adm-role { font-size:.72rem; color:#94A3B8; text-transform:uppercase; letter-spacing:.5px; }
        .adm-email { font-size:.73rem; color:#94A3B8; margin-top:.15rem; overflow:hidden; text-overflow:ellipsis; max-width:170px; }
        .adm-divider { height:1px; background:#F1F5F9; margin:.3rem 0; }
        .adm-item { display:flex; align-items:center; gap:.6rem; padding:.65rem 1rem; color:var(--text-dark); text-decoration:none; font-size:.88rem; font-weight:500; transition:background .15s; border-radius:10px; margin:.1rem .4rem; }
        .adm-item:hover { background:#F1F5F9; color:var(--primary); }
        .adm-item.site { color:var(--text-light); }
        .adm-item.logout { color:#EF4444!important; }
        .adm-item.logout:hover { background:#FEE2E2!important; color:#DC2626!important; }

        /* ── PAGE BODY ── */
        .page-body { padding:2rem; flex:1; }

        /* ── STATS ── */
        .stats-strip { display:grid; grid-template-columns:repeat(6,1fr); gap:1.2rem; margin-bottom:2rem; }
        .stat-card { background:white; border-radius:16px; padding:1.4rem; box-shadow:var(--shadow-sm); display:flex; align-items:center; gap:1rem; border:1px solid var(--border); }
        .stat-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0; }
        .stat-icon.blue   { background:rgba(10,126,164,0.12); }
        .stat-icon.green  { background:rgba(16,185,129,0.12); }
        .stat-icon.yellow { background:rgba(245,158,11,0.12); }
        .stat-icon.purple { background:rgba(124,58,237,0.12); }
        .stat-icon.teal   { background:rgba(20,184,166,0.12); }
        .stat-icon.orange { background:rgba(249,115,22,0.12); }
        .stat-info h3 { font-family:'Sora',sans-serif; font-size:1.6rem; font-weight:800; color:var(--text-dark); line-height:1; }
        .stat-info p  { font-size:0.82rem; color:var(--text-light); margin-top:0.2rem; }

        /* ── TOOLBAR ── */
        .toolbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; gap:1rem; flex-wrap:wrap; }
        .toolbar-search { position:relative; flex:1; max-width:360px; }
        .toolbar-search input { width:100%; padding:0.7rem 1rem 0.7rem 2.8rem; border:2px solid var(--border); border-radius:50px; font-family:inherit; font-size:0.9rem; outline:none; background:white; color:var(--text-dark); transition:border-color .3s; }
        .toolbar-search input:focus { border-color:var(--primary); }
        .toolbar-search .s-icon { position:absolute; left:1rem; top:50%; transform:translateY(-50%); color:var(--text-light); }
        .filter-group { display:flex; align-items:center; gap:.8rem; }
        .filter-btn { padding:.55rem 1.2rem; border-radius:50px; border:2px solid var(--border); background:white; font-family:inherit; font-size:.85rem; font-weight:600; color:var(--text-light); cursor:pointer; transition:all .25s; text-decoration:none; display:inline-block; }
        .filter-btn:hover, .filter-btn.active { background:var(--primary); border-color:var(--primary); color:white; }
        .toolbar-right { display:flex; gap:.8rem; align-items:center; }

        /* Report buttons */
        .report-buttons { display:flex; gap:0.5rem; }
        .btn-report { display:inline-flex; align-items:center; gap:0.4rem; padding:0.4rem 0.9rem; border-radius:50px; font-size:0.75rem; font-weight:600; font-family:'Manrope',sans-serif; cursor:pointer; transition:all 0.2s ease; border:1px solid var(--border); background:#fff; color:var(--text-dark); }
        .btn-report:hover { background:var(--bg); border-color:var(--primary); color:var(--primary); }

        /* ── TABLE ── */
        .table-card { background:white; border-radius:20px; box-shadow:var(--shadow-sm); overflow:hidden; border:1px solid var(--border); }
        .table-header { padding:1.2rem 1.5rem; border-bottom:1px solid #F1F5F9; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; }
        .table-header h3 { font-family:'Sora',sans-serif; font-size:1rem; font-weight:700; }
        .table-header span { font-size:0.85rem; color:var(--text-light); }
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; }
        thead th { background:#F8FAFC; padding:0.9rem 1.2rem; text-align:left; font-size:0.78rem; font-weight:700; color:var(--text-light); text-transform:uppercase; letter-spacing:0.8px; border-bottom:1px solid var(--border); white-space:nowrap; }
        tbody tr { border-bottom:1px solid #F1F5F9; transition:background .2s; }
        tbody tr:last-child { border-bottom:none; }
        tbody tr:hover { background:#F8FAFC; }
        tbody td { padding:1rem 1.2rem; font-size:0.88rem; vertical-align:middle; }

        .customer-cell { font-weight:700; color:var(--text-dark); }
        .customer-cell small { display:block; color:var(--text-light); font-weight:400; font-size:0.8rem; margin-top:.2rem; }
        .price-cell { font-family:'Sora',sans-serif; font-weight:700; color:var(--primary); }
        
        /* Custom badge */
        .custom-badge { display:inline-block; padding:.2rem .7rem; border-radius:50px; font-size:.7rem; font-weight:700; background:linear-gradient(135deg,#10B981,#059669); color:white; white-space:nowrap; }
        .standard-badge { display:inline-block; padding:.2rem .7rem; border-radius:50px; font-size:.7rem; font-weight:700; background:#E2E8F0; color:#64748B; white-space:nowrap; }
        
        /* View details button */
        .btn-view { padding:0.38rem 0.9rem; background:rgba(10,126,164,0.1); color:var(--primary); border:none; border-radius:8px; font-family:inherit; font-size:0.78rem; font-weight:600; cursor:pointer; transition:all .2s; margin-right:.5rem; }
        .btn-view:hover { background:var(--primary); color:white; }
        .btn-delete { padding:0.38rem 0.9rem; background:rgba(239,68,68,0.1); color:var(--danger); border:none; border-radius:8px; font-family:inherit; font-size:0.78rem; font-weight:600; cursor:pointer; transition:all .2s; }
        .btn-delete:hover { background:var(--danger); color:white; }

        /* ── PAGINATION ── */
        .pagination { display:flex; justify-content:center; align-items:center; gap:.5rem; padding:1.5rem; }
        .page-btn { padding:.5rem .9rem; border:2px solid var(--border); border-radius:8px; background:white; font-family:inherit; font-size:.85rem; font-weight:600; cursor:pointer; text-decoration:none; color:var(--text-dark); transition:all .2s; }
        .page-btn:hover { border-color:var(--primary); color:var(--primary); }
        .page-btn.active { background:var(--primary); border-color:var(--primary); color:white; }
        .page-btn.disabled { opacity:.4; pointer-events:none; }

        /* ── EMPTY ── */
        .table-empty { text-align:center; padding:4rem 2rem; color:var(--text-light); }
        .table-empty .e-icon { font-size:3rem; margin-bottom:1rem; }
        .table-empty h3 { font-family:'Sora',sans-serif; font-size:1.2rem; color:var(--text-dark); margin-bottom:.5rem; }

        /* ── TOAST ── */
        .toast { position:fixed; bottom:2rem; left:50%; transform:translateX(-50%); background:#065A7A; color:white; padding:.9rem 2rem; border-radius:50px; font-weight:600; font-size:.95rem; box-shadow:0 8px 30px rgba(0,0,0,.25); z-index:9999; white-space:nowrap; animation:fadeInUp .4s ease; }
        @keyframes fadeInUp { from{opacity:0;transform:translate(-50%,20px)} to{opacity:1;transform:translate(-50%,0)} }

        /* ── DELETE CONFIRM ── */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.6); z-index:999; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .confirm-modal { background:white; border-radius:20px; padding:2rem; max-width:400px; width:92%; text-align:center; box-shadow:var(--shadow-lg); }
        .confirm-icon { font-size:3rem; margin-bottom:1rem; }
        .confirm-modal h3 { font-family:'Sora',sans-serif; font-size:1.3rem; font-weight:700; margin-bottom:.5rem; }
        .confirm-modal p  { color:var(--text-light); font-size:.9rem; margin-bottom:1.5rem; }
        .confirm-btns { display:flex; gap:1rem; justify-content:center; }
        .btn-confirm-yes { padding:.75rem 2rem; background:var(--danger); color:white; border:none; border-radius:50px; font-family:inherit; font-size:.95rem; font-weight:700; cursor:pointer; transition:all .3s; }
        .btn-confirm-yes:hover { background:#DC2626; }
        .btn-confirm-no { padding:.75rem 2rem; background:transparent; color:var(--text-light); border:2px solid var(--border); border-radius:50px; font-family:inherit; font-size:.95rem; font-weight:600; cursor:pointer; }

        /* ── DETAILS MODAL ── */
        .details-modal { max-width: 550px; }
        .details-grid { display: grid; gap: 0.8rem; margin-top: 1rem; }
        .details-row { display: flex; border-bottom: 1px solid #F1F5F9; padding: 0.6rem 0; }
        .details-label { font-weight: 700; width: 130px; flex-shrink: 0; color: var(--text-dark); font-size: 0.85rem; }
        .details-value { color: var(--text-light); font-size: 0.85rem; flex: 1; }
        .custom-section { background: linear-gradient(135deg,#F0FDF4,#ECFDF5); border-radius: 12px; padding: 1rem; margin-top: 1rem; }
        .custom-title { font-weight: 700; color: #065f46; margin-bottom: 0.8rem; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }

        /* ── RESPONSIVE ── */
        @media(max-width:1200px) { .stats-strip { grid-template-columns:repeat(3,1fr); } }
        @media(max-width:1024px) { .stats-strip { grid-template-columns:repeat(2,1fr); } }
        @media(max-width:768px) {
            .sidebar { transform:translateX(-100%); }
            .sidebar.open { transform:translateX(0); }
            .main { margin-left:0; }
            .hamburger { display:flex; }
            .stats-strip { grid-template-columns:1fr 1fr; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ══════════════════════════════════
     SIDEBAR
══════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo">✈️</div>
        <div class="sidebar-brand-text">
            <h2>Jettransfer</h2>
            <span>Admin Panel</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-label">Main</div>
        <a href="index.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>
        <div class="nav-label">Modules</div>
        <a href="destinations.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Destinations
        </a>
        <a href="packages.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            Packages
        </a>
        <a href="vehicles.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            Vehicles
        </a>
        <a href="profiles.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profiles
        </a>
        <a href="bookings.php" class="nav-item active">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Bookings
        </a>
        <a href="services.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            Services &amp; Gallery
        </a>
        <a href="contact.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Contact Us / Reviews
        </a>
        <div class="nav-label">Reports</div>
        <a href="admin_monthly_report.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Monthly Report
        </a>
        <div class="nav-label">Other</div>
        <a href="../index.php" class="nav-item" target="_blank">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            View Site
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-profile">
            <div class="admin-avatar"><?= $admInit ?></div>
            <div class="admin-info">
                <h4><?= htmlspecialchars($admName) ?></h4>
                <p><?= htmlspecialchars($admEmail) ?></p>
            </div>
        </div>
        <a href="logout.php" class="btn-logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
        </a>
    </div>
</aside>

<!-- ══════════════════════════════════
     MAIN
══════════════════════════════════ -->
<div class="main">
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger" onclick="toggleSidebar()">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="page-title">
                <h1>Bookings Management</h1>
                <p>View and manage all customer bookings (including custom packages)</p>
            </div>
        </div>
        
    </header>

    <div class="page-body">

        <?php if (isset($_GET['deleted'])): ?>
        <div class="toast" id="deletedToast">✅ Booking deleted successfully!</div>
        <script>setTimeout(() => document.getElementById('deletedToast')?.remove(), 3500);</script>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-strip">
            <div class="stat-card">
                <div class="stat-icon blue">📅</div>
                <div class="stat-info">
                    <h3><?= $totalBookings ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">🌟</div>
                <div class="stat-info">
                    <h3><?= $todayBookings ?></h3>
                    <p>Today's Bookings</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">👥</div>
                <div class="stat-info">
                    <h3><?= $totalGuests ?: 0 ?></h3>
                    <p>Total Guests</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon teal">✏️</div>
                <div class="stat-info">
                    <h3><?= $customBookings ?></h3>
                    <p>Custom Bookings</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">📋</div>
                <div class="stat-info">
                    <h3><?= $standardBookings ?></h3>
                    <p>Standard Bookings</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">📄</div>
                <div class="stat-info">
                    <h3><?= $totalPages ?></h3>
                    <p>Total Pages</p>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <form method="GET" action="bookings.php">
            <div class="toolbar">
                <div class="toolbar-search">
                    <span class="s-icon">🔍</span>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, package, email…">
                </div>
                <div class="filter-group">
                    <span class="filter-label" style="font-weight:600;font-size:.88rem;color:var(--text-light);">Filter:</span>
                    <a href="bookings.php" class="filter-btn <?= $type === '' ? 'active' : '' ?>">All</a>
                    <a href="bookings.php?type=standard" class="filter-btn <?= $type === 'standard' ? 'active' : '' ?>">Standard</a>
                    <a href="bookings.php?type=custom" class="filter-btn <?= $type === 'custom' ? 'active' : '' ?>">Custom</a>
                </div>
                <div class="toolbar-right">
                    <button type="submit" style="padding:.7rem 1.5rem;background:var(--primary);color:white;border:none;border-radius:50px;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;">
                        Search
                    </button>
                    <?php if ($search || $type): ?>
                    <a href="bookings.php" style="padding:.7rem 1.2rem;border:2px solid var(--border);border-radius:50px;font-size:.88rem;font-weight:600;color:var(--text-light);text-decoration:none;">
                        Clear
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- Table -->
        <div class="table-card">
            <div class="table-header">
                <h3>All Bookings</h3>
                <div class="report-buttons">
                    <button type="button" class="btn-report" onclick="exportBookingsCSV()">📎 Export CSV</button>
                    <button type="button" class="btn-report" onclick="printBookings()">🖨️ Print/PDF</button>
                </div>
            </div>

            <?php if (empty($bookings)): ?>
            <div class="table-empty">
                <div class="e-icon">📅</div>
                <h3>No bookings found</h3>
                <p><?= $search ? 'Try a different search term.' : 'No bookings have been made yet.' ?></p>
            </div>
            <?php else: ?>
            <div class="table-wrap">
                <table id="bookingsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Package</th>
                            <th>Type</th>
                            <th>Travel Date</th>
                            <th>Guests</th>
                            <th>Price</th>
                            <th>Phone</th>
                            <th>Booked On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td style="color:var(--text-light);font-weight:600;">#<?= $b['id'] ?></td>
                            <td>
                                <div class="customer-cell">
                                    <?= htmlspecialchars($b['customer_name']) ?>
                                    <small>📧 <?= htmlspecialchars($b['email']) ?></small>
                                </div>
                            </td>
                            <td style="font-weight:600;"><?= htmlspecialchars($b['package_name']) ?></td>
                            <td>
                                <?php if (($b['booking_type'] ?? 'standard') === 'custom'): ?>
                                    <span class="custom-badge">✏️ Custom</span>
                                <?php else: ?>
                                    <span class="standard-badge">📦 Standard</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d M Y', strtotime($b['travel_date'])) ?></td>
                            <td style="text-align:center;"><?= $b['guests'] ?></td>
                            <td class="price-cell"><?= htmlspecialchars($b['price']) ?></td>
                            <td><?= htmlspecialchars($b['phone']) ?></td>
                            <td style="color:var(--text-light);"><?= date('d M Y', strtotime($b['created_at'])) ?></td>
                            <td>
                                <?php if (($b['booking_type'] ?? 'standard') === 'custom'): ?>
                                    <button class="btn-view" onclick="viewDetails(<?= $b['id'] ?>)">📋 View Details</button>
                                <?php endif; ?>
                                <button class="btn-delete" onclick="openDeleteModal(<?= $b['id'] ?>, '<?= htmlspecialchars(addslashes($b['customer_name'])) ?>')">
                                    🗑️ Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type) ?>" class="page-btn">← Prev</a>
                <?php else: ?>
                    <span class="page-btn disabled">← Prev</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type) ?>"
                       class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type) ?>" class="page-btn">Next →</a>
                <?php else: ?>
                    <span class="page-btn disabled">Next →</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="confirm-modal">
        <div class="confirm-icon">🗑️</div>
        <h3>Delete Booking?</h3>
        <p id="deleteMsg">This action cannot be undone.</p>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="delete_id" id="deleteId">
            <div class="confirm-btns">
                <button type="submit" class="btn-confirm-yes">Yes, Delete</button>
                <button type="button" class="btn-confirm-no" onclick="closeDeleteModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- CUSTOM DETAILS MODAL -->
<div class="modal-overlay" id="detailsModal">
    <div class="modal-box details-modal">
        <button class="modal-close-btn" onclick="closeDetailsModal()">✕</button>
        <h2 class="modal-title" style="color:var(--primary);">📋 Custom Booking Details</h2>
        <div id="detailsContent">
            <div style="text-align:center;padding:2rem;">Loading...</div>
        </div>
    </div>
</div>

<style>
.modal-box {
    background: white;
    border-radius: 24px;
    padding: 2rem;
    width: 100%;
    max-width: 550px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    animation: slideUp 0.3s ease;
}
.modal-close-btn {
    position: absolute;
    top: 1rem;
    right: 1.2rem;
    background: none;
    border: none;
    font-size: 1.4rem;
    cursor: pointer;
    color: #64748B;
    line-height: 1;
}
.modal-title {
    font-family: 'Sora', sans-serif;
    margin-bottom: 0.3rem;
    font-size: 1.3rem;
    font-weight: 700;
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
    // Store all bookings data for export (full dataset)
    const allBookings = <?php echo json_encode($allBookings); ?>;

    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('show');
    }
    document.getElementById('sidebarOverlay').addEventListener('click', () => {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('show');
    });

    function toggleAdminDD() {
        document.getElementById('adminDDMenu').classList.toggle('open');
    }
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('adminDropdown');
        if (dropdown && !dropdown.contains(e.target)) {
            document.getElementById('adminDDMenu').classList.remove('open');
        }
    });

    function openDeleteModal(id, name) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteMsg').textContent = 'Booking by "' + name + '" will be permanently removed.';
        document.getElementById('deleteModal').classList.add('open');
    }
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('open');
    }
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
    });

    function viewDetails(id) {
        const modal = document.getElementById('detailsModal');
        const content = document.getElementById('detailsContent');
        content.innerHTML = '<div style="text-align:center;padding:2rem;">Loading details...</div>';
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';

        // Fetch booking details via AJAX
        fetch(`get_booking_details.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = '<div class="details-grid">';
                    html += '<div class="details-row"><div class="details-label">Customer:</div><div class="details-value">' + escapeHtml(data.customer_name) + '</div></div>';
                    html += '<div class="details-row"><div class="details-label">Email:</div><div class="details-value">' + escapeHtml(data.email) + '</div></div>';
                    html += '<div class="details-row"><div class="details-label">Phone:</div><div class="details-value">' + escapeHtml(data.phone) + '</div></div>';
                    html += '<div class="details-row"><div class="details-label">Package:</div><div class="details-value">' + escapeHtml(data.package_name) + '</div></div>';
                    html += '<div class="details-row"><div class="details-label">Base Price:</div><div class="details-value">' + escapeHtml(data.price) + '</div></div>';
                    html += '<div class="details-row"><div class="details-label">Travel Date:</div><div class="details-value">' + escapeHtml(data.travel_date) + '</div></div>';
                    html += '<div class="details-row"><div class="details-label">Guests:</div><div class="details-value">' + data.guests + '</div></div>';
                    html += '<div class="details-row"><div class="details-label">Booked On:</div><div class="details-value">' + escapeHtml(data.created_at) + '</div></div>';
                    
                    if (data.custom_duration || data.custom_hotel || data.custom_vehicle || data.custom_group_size) {
                        html += '<div class="custom-section"><div class="custom-title">✏️ Customization Details</div>';
                        if (data.custom_duration) html += '<div class="details-row"><div class="details-label">Duration:</div><div class="details-value">' + data.custom_duration + ' Days</div></div>';
                        if (data.custom_hotel) html += '<div class="details-row"><div class="details-label">Hotel Rating:</div><div class="details-value">' + escapeHtml(data.custom_hotel) + '</div></div>';
                        if (data.custom_vehicle) html += '<div class="details-row"><div class="details-label">Vehicle:</div><div class="details-value">' + escapeHtml(data.custom_vehicle) + '</div></div>';
                        if (data.custom_group_size) html += '<div class="details-row"><div class="details-label">Group Size:</div><div class="details-value">' + escapeHtml(data.custom_group_size) + '</div></div>';
                        html += '</div>';
                    }
                    html += '</div>';
                    content.innerHTML = html;
                } else {
                    content.innerHTML = '<div style="text-align:center;padding:2rem;color:#EF4444;">❌ ' + escapeHtml(data.message) + '</div>';
                }
            })
            .catch(error => {
                content.innerHTML = '<div style="text-align:center;padding:2rem;color:#EF4444;">❌ Error loading details</div>';
            });
    }

    function closeDetailsModal() {
        document.getElementById('detailsModal').classList.remove('open');
        document.body.style.overflow = '';
    }
    document.getElementById('detailsModal').addEventListener('click', function(e) {
        if (e.target === this) closeDetailsModal();
    });

    // Export to CSV
    function exportBookingsCSV() {
        if (!allBookings.length) {
            alert('No bookings to export.');
            return;
        }
        let rows = [['ID', 'Customer Name', 'Email', 'Phone', 'Package Name', 'Booking Type', 'Travel Date', 'Guests', 'Price', 'Created Date', 'Custom Duration', 'Custom Hotel', 'Custom Vehicle', 'Custom Group Size']];
        allBookings.forEach(b => {
            rows.push([
                b.id,
                b.customer_name,
                b.email,
                b.phone || '',
                b.package_name,
                (b.booking_type || 'standard'),
                b.travel_date,
                b.guests,
                b.price,
                b.created_at,
                b.custom_duration || '',
                b.custom_hotel || '',
                b.custom_vehicle || '',
                b.custom_group_size || ''
            ]);
        });
        let csvContent = rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n');
        const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.href = url;
        link.setAttribute('download', 'jettransfer_bookings.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    // Print/PDF
    function printBookings() {
        if (!allBookings.length) {
            alert('No bookings to print.');
            return;
        }
        const printWindow = window.open('', '_blank');
        let html = `
            <html>
            <head><title>Jettransfer - Bookings Report</title>
            <style>
                body { font-family: 'Manrope', sans-serif; margin: 2rem; }
                h1 { color: #0A7EA4; }
                table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
                th, td { border: 1px solid #ccc; padding: 0.5rem; text-align: left; vertical-align: top; }
                th { background: #f2f2f2; }
            </style>
            </head>
            <body>
            <h1>Jettransfer - Bookings Report</h1>
            <p>Generated on: ${new Date().toLocaleString()}</p>
            <table><thead><tr>
                <th>ID</th><th>Customer</th><th>Email</th><th>Phone</th><th>Package</th><th>Type</th><th>Travel Date</th><th>Guests</th><th>Price</th><th>Booked On</th>
            </tr></thead><tbody>
        `;
        allBookings.forEach(b => {
            html += `<tr>
                <td>${escapeHtml(b.id)}</td>
                <td>${escapeHtml(b.customer_name)}</td>
                <td>${escapeHtml(b.email)}</td>
                <td>${escapeHtml(b.phone || '—')}</td>
                <td>${escapeHtml(b.package_name)}</td>
                <td>${(b.booking_type || 'standard') === 'custom' ? 'Custom' : 'Standard'}</td>
                <td>${escapeHtml(b.travel_date)}</td>
                <td>${b.guests}</td>
                <td>${escapeHtml(b.price)}</td>
                <td>${escapeHtml(b.created_at)}</td>
            </tr>`;
        });
        html += `</tbody></table></body></html>`;
        printWindow.document.write(html);
        printWindow.document.close();
        printWindow.print();
        printWindow.onafterprint = () => printWindow.close();
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
</script>
</body>
</html>