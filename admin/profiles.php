<?php
session_start();
if (!isset($_SESSION['jt_admin'])) { header('Location: login.php'); exit; }

require_once '../db.php';
$msg = '';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$tid    = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

if ($action === 'toggle' && $tid) {
    $r   = $conn->query("SELECT is_active FROM users WHERE id=$tid LIMIT 1");
    $row = $r ? $r->fetch_assoc() : null;
    if ($row) {
        $new = $row['is_active'] ? 0 : 1;  
        $conn->query("UPDATE users SET is_active=$new WHERE id=$tid");
        $msg = $new ? '✅ User activated.' : '⚠️ User deactivated.';
    }
}
if ($action === 'delete' && $tid) {  
    $conn->query("DELETE FROM users WHERE id=$tid");
    $msg = '🗑️ User deleted.';
}

// ── Fetch admin info (same as index.php)
$ar   = $conn->query("SELECT name,email FROM admins WHERE id=1 LIMIT 1");
$adm  = $ar ? $ar->fetch_assoc() : ['name'=>'Admin','email'=>'admin@jettransfer.com'];
$admName  = htmlspecialchars($adm['name']);
$admEmail = htmlspecialchars($adm['email']);
$admInit  = strtoupper(substr($adm['name'],0,1));

// ── Stats 
$total    = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0] ?? 0;
$active   = $conn->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetch_row()[0] ?? 0;
$inactive = $total - $active;
$today    = $conn->query("SELECT COUNT(*) FROM users WHERE DATE(created_at)=CURDATE()")->fetch_row()[0] ?? 0;

// ── Search + Filter
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'all';
$where  = '1';
if ($search !== '') {
    $s = $conn->real_escape_string($search);
    $where .= " AND (first_name LIKE '%$s%' OR last_name LIKE '%$s%' OR email LIKE '%$s%' OR phone LIKE '%$s%')";
}
if ($status === 'active')   $where .= ' AND is_active=1';
if ($status === 'inactive') $where .= ' AND is_active=0';

$users = $conn->query("SELECT * FROM users WHERE $where ORDER BY id DESC");
$allUsers = [];
if ($users) while ($row = $users->fetch_assoc()) $allUsers[] = $row;
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Management – Jettransfer Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    /* ── CSS variables — matches index.php exactly ── */
    :root {
        --primary: #0A7EA4;
        --primary-dark: #065A7A;
        --primary-light: #E0F2FA;
        --secondary: #F59E0B;
        --accent: #10B981;
        --text-dark: #0F172A;
        --text-light: #94A3B8;
        --bg: #F1F5F9;
        --sidebar-bg: #0F172A;
        --border: #E2E8F0;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
        --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
        --sidebar-w: 260px;
    }
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Manrope',sans-serif; background:var(--bg); color:var(--text-dark); min-height:100vh; display:flex; }

    /* ── SIDEBAR — matches index.php exactly ── */
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
    .admin-avatar-sb { width:36px; height:36px; background:linear-gradient(135deg,var(--primary),var(--accent)); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:.9rem; font-weight:700; color:#fff; flex-shrink:0; }
    .admin-info h4 { font-size:.85rem; font-weight:600; color:#fff; }
    .admin-info p { font-size:.72rem; color:rgba(255,255,255,.4); }
    .btn-logout { display:flex; align-items:center; gap:.6rem; padding:.7rem .9rem; border-radius:12px; color:rgba(255,255,255,.5); font-size:.88rem; font-weight:500; cursor:pointer; transition:all .2s; border:none; background:none; width:100%; font-family:'Manrope',sans-serif; text-decoration:none; }
    .btn-logout:hover { background:rgba(239,68,68,.15); color:#FCA5A5; }
    .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:99; }
    .sidebar-overlay.show { display:block; }

    /* ── MAIN ── */
    .main { margin-left:var(--sidebar-w); flex:1; display:flex; flex-direction:column; min-height:100vh; }

    /* ── TOPBAR ── */
    .topbar { background:#fff; border-bottom:1px solid var(--border); padding:0 2rem; height:70px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:50; box-shadow:var(--shadow-sm); }
    .topbar-left { display:flex; align-items:center; gap:1rem; }
    .hamburger { display:none; background:none; border:none; cursor:pointer; padding:6px; color:var(--text-dark); }
    .page-title h1 { font-family:'Sora',sans-serif; font-size:1.2rem; font-weight:700; }
    .page-title p { font-size:.8rem; color:var(--text-light); margin-top:.1rem; }
    .topbar-avatar { width:40px; height:40px; background:linear-gradient(135deg,var(--primary),var(--accent)); border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:.9rem; }

    /* ── CONTENT ── */
    .content { padding:2rem; flex:1; }

    /* ── STATS ── */
    .stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:1.2rem; margin-bottom:2rem; }
    .stat-card { background:white; border-radius:16px; padding:1.4rem; box-shadow:var(--shadow-sm); display:flex; align-items:center; gap:1rem; border:1px solid var(--border); transition:transform .2s,box-shadow .2s; }
    .stat-card:hover { transform:translateY(-2px); box-shadow:var(--shadow-md); }
    .stat-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0; }
    .stat-icon.blue  { background:#EFF6FF; }
    .stat-icon.green { background:#ECFDF5; }
    .stat-icon.red   { background:#FEF2F2; }
    .stat-icon.amber { background:#FFFBEB; }
    .stat-val { font-family:'Sora',sans-serif; font-size:1.8rem; font-weight:800; line-height:1.1; }
    .stat-lbl { font-size:.78rem; color:var(--text-light); margin-top:.2rem; }

    /* ── TOOLBAR ── */
    .toolbar { background:white; border-radius:16px; padding:1.2rem 1.5rem; box-shadow:var(--shadow-sm); margin-bottom:1.5rem; display:flex; align-items:center; gap:1rem; flex-wrap:wrap; border:1px solid var(--border); }
    .search-wrap { flex:1; min-width:200px; display:flex; align-items:center; gap:.6rem; background:#F8FAFC; border:1.5px solid var(--border); border-radius:10px; padding:.55rem 1rem; transition:border-color .25s; }
    .search-wrap:focus-within { border-color:var(--primary); }
    .search-wrap input { border:none; outline:none; background:transparent; font-family:'Manrope',sans-serif; font-size:.9rem; color:var(--text-dark); width:100%; }
    .filter-tabs { display:flex; gap:.4rem; }
    .filter-btn { padding:.45rem 1rem; border-radius:8px; border:1.5px solid var(--border); background:white; font-family:'Manrope',sans-serif; font-size:.82rem; font-weight:600; cursor:pointer; text-decoration:none; color:var(--text-light); transition:all .2s; }
    .filter-btn:hover, .filter-btn.active { background:var(--primary); border-color:var(--primary); color:white; }
    .btn-search { padding:.5rem 1.2rem; background:var(--primary); color:white; border:none; border-radius:8px; font-family:'Manrope',sans-serif; font-weight:600; font-size:.85rem; cursor:pointer; }
    .btn-clear  { padding:.5rem 1rem; background:#F1F5F9; color:var(--text-light); border-radius:8px; text-decoration:none; font-size:.85rem; font-weight:600; }

    /* Report buttons */
    .report-buttons { display:flex; gap:0.5rem; }
    .btn-report { display:inline-flex; align-items:center; gap:0.4rem; padding:0.4rem 0.9rem; border-radius:50px; font-size:0.75rem; font-weight:600; font-family:'Manrope',sans-serif; cursor:pointer; transition:all 0.2s ease; border:1px solid var(--border); background:#fff; color:var(--text-dark); }
    .btn-report:hover { background:var(--bg); border-color:var(--primary); color:var(--primary); }

    /* ── TABLE ── */
    .table-card { background:white; border-radius:16px; box-shadow:var(--shadow-sm); overflow:hidden; border:1px solid var(--border); }
    .table-header { padding:1.1rem 1.5rem; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; }
    .table-header h3 { font-family:'Sora',sans-serif; font-size:1rem; font-weight:700; }
    .table-wrap { overflow-x:auto; }
    table { width:100%; border-collapse:collapse; }
    thead tr { background:#F8FAFC; border-bottom:2px solid var(--border); }
    th { padding:.9rem 1.2rem; text-align:left; font-size:.75rem; font-weight:700; color:var(--text-light); text-transform:uppercase; letter-spacing:.5px; white-space:nowrap; }
    tbody tr { border-bottom:1px solid #F1F5F9; transition:background .15s; }
    tbody tr:hover { background:#F8FAFC; }
    tbody tr:last-child { border-bottom:none; }
    td { padding:.9rem 1.2rem; font-size:.88rem; vertical-align:middle; }

    .user-cell { display:flex; align-items:center; gap:.85rem; }
    .user-avatar { width:38px; height:38px; border-radius:50%; background:linear-gradient(135deg,var(--primary),var(--accent)); display:flex; align-items:center; justify-content:center; font-size:.88rem; font-weight:700; color:#fff; flex-shrink:0; }
    .user-name  { font-weight:600; font-size:.88rem; }
    .user-email { font-size:.75rem; color:var(--text-light); }

    .badge { display:inline-flex; align-items:center; gap:.3rem; padding:.3rem .8rem; border-radius:50px; font-size:.73rem; font-weight:700; text-transform:uppercase; letter-spacing:.3px; }
    .badge-active   { background:#ECFDF5; color:#065F46; }
    .badge-inactive { background:#FEF2F2; color:#991B1B; }

    .actions { display:flex; align-items:center; gap:.5rem; flex-wrap:nowrap; }
    .btn-sm { padding:.35rem .85rem; border-radius:8px; font-size:.78rem; font-weight:600; cursor:pointer; border:none; font-family:'Manrope',sans-serif; transition:all .2s; text-decoration:none; display:inline-block; white-space:nowrap; }
    .btn-toggle-on  { background:#FEF3C7; color:#92400E; }
    .btn-toggle-on:hover  { background:#FDE68A; }
    .btn-toggle-off { background:#ECFDF5; color:#065F46; }
    .btn-toggle-off:hover { background:#A7F3D0; }
    .btn-delete { background:#FEE2E2; color:#991B1B; }
    .btn-delete:hover { background:#FECACA; }
    .btn-view   { background:#EFF6FF; color:#1D4ED8; }
    .btn-view:hover { background:#BFDBFE; }

    .alert-bar { padding:.8rem 1.2rem; border-radius:12px; margin-bottom:1.2rem; font-size:.88rem; font-weight:600; }
    .alert-bar.ok   { background:#D1FAE5; color:#065F46; border:1px solid #A7F3D0; }
    .alert-bar.warn { background:#FEF3C7; color:#92400E; border:1px solid #FDE68A; }

    .empty-state { text-align:center; padding:3rem 1rem; color:var(--text-light); }
    .empty-state .icon { font-size:3rem; margin-bottom:.8rem; }

    /* ── RESPONSIVE ── */
    @media(max-width:768px) {
        .sidebar { transform:translateX(-100%); }
        .sidebar.open { transform:translateX(0); }
        .main { margin-left:0; }
        .hamburger { display:flex; }
        .stats-row { grid-template-columns:1fr 1fr; }
        table { display:block; overflow-x:auto; }
    }
</style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ══════════════════════════════════
     SIDEBAR — matches index.php exactly
══════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo">✈️</div>
        <div class="sidebar-brand-text">
            <h2>Jettransfer</h2><span>Admin Panel</span>
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
        <a href="profiles.php" class="nav-item active">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profiles
        </a>
        <a href="bookings.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Bookings
        </a>
        <a href="services.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            Services & Gallery
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
            <div class="admin-avatar-sb"><?= $admInit ?></div>
            <div class="admin-info">
                <h4><?= $admName ?></h4>
                <p><?= $admEmail ?></p>
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

    <!-- TOPBAR -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger" onclick="toggleSidebar()">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="page-title">
                <h1>User Profiles</h1>
                <p>Manage registered site members</p>
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-avatar"><?= $admInit ?></div>
        </div>
    </header>

    <!-- CONTENT -->
    <div class="content">

        <?php if ($msg): ?>
        <div class="alert-bar <?= str_contains($msg,'✅') ? 'ok' : 'warn' ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card"><div class="stat-icon blue">👥</div><div><div class="stat-val"><?= $total ?></div><div class="stat-lbl">Total Users</div></div></div>
            <div class="stat-card"><div class="stat-icon green">✅</div><div><div class="stat-val"><?= $active ?></div><div class="stat-lbl">Active Users</div></div></div>
            <div class="stat-card"><div class="stat-icon red">🚫</div><div><div class="stat-val"><?= $inactive ?></div><div class="stat-lbl">Inactive Users</div></div></div>
            <div class="stat-card"><div class="stat-icon amber">🆕</div><div><div class="stat-val"><?= $today ?></div><div class="stat-lbl">Joined Today</div></div></div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <form method="GET" style="flex:1;display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                <div class="search-wrap">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="search" placeholder="Search by name, email, phone…" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="filter-tabs">
                    <a href="profiles.php?status=all<?= $search ? '&search='.urlencode($search) : '' ?>"      class="filter-btn <?= $status==='all'      ? 'active':'' ?>">All (<?= $total ?>)</a>
                    <a href="profiles.php?status=active<?= $search ? '&search='.urlencode($search) : '' ?>"   class="filter-btn <?= $status==='active'   ? 'active':'' ?>">Active (<?= $active ?>)</a>
                    <a href="profiles.php?status=inactive<?= $search ? '&search='.urlencode($search) : '' ?>" class="filter-btn <?= $status==='inactive' ? 'active':'' ?>">Inactive (<?= $inactive ?>)</a>
                </div>
                <button type="submit" class="btn-search">Search</button>
                <?php if ($search || $status !== 'all'): ?>
                <a href="profiles.php" class="btn-clear">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Table -->
        <div class="table-card">
            <div class="table-header">
                <h3>All Users</h3>
                <div class="report-buttons">
                    <button type="button" class="btn-report" onclick="exportUsersCSV()">📎 Export CSV</button>
                    <button type="button" class="btn-report" onclick="printUsers()">🖨️ Print/PDF</button>
                </div>
            </div>
            <div class="table-wrap">
                <?php if (!empty($allUsers)): ?>
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Phone</th>
                            <th>Nationality</th>
                            <th>Registered</th>
                            <th>Last Login</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allUsers as $u):
                            $ini = strtoupper(substr($u['first_name'],0,1) . substr($u['last_name'],0,1));
                        ?>
                        <tr>
                            <td style="color:var(--text-light);font-size:.8rem">#<?= $u['id'] ?></td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar"><?= $ini ?></div>
                                    <div>
                                        <div class="user-name"><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></div>
                                        <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($u['phone'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($u['nationality'] ?: '—') ?></td>
                            <td style="font-size:.82rem;color:var(--text-light)"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td style="font-size:.82rem;color:var(--text-light)"><?= $u['last_login'] ? date('d M Y', strtotime($u['last_login'])) : '—' ?></td>
                            <td>
                                <span class="badge <?= $u['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= $u['is_active'] ? '✅ Active' : '🚫 Inactive' ?>
                                </span>
                             </td>
                            <td>
                                <div class="actions">
                                    <a href="view_user.php?id=<?= $u['id'] ?>" class="btn-sm btn-view">View</a>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn-sm <?= $u['is_active'] ? 'btn-toggle-on' : 'btn-toggle-off' ?>">
                                            <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete <?= htmlspecialchars($u['first_name']) ?>? This cannot be undone.')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn-sm btn-delete">Delete</button>
                                    </form>
                                </div>
                             </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <div class="icon">👥</div>
                    <p style="font-weight:600;font-size:1rem;margin-bottom:.4rem">
                        <?= $search ? 'No users match your search' : 'No users registered yet' ?>
                    </p>
                    <p style="font-size:.85rem">Users will appear here after they register on the website.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /.content -->
</div><!-- /.main -->

<script>
    // Store all users data for export/print
    const allUsers = <?php echo json_encode($allUsers); ?>;

    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('show');
    }
    document.getElementById('sidebarOverlay').addEventListener('click', () => {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('show');
    });

    // Export to CSV
    function exportUsersCSV() {
        if (!allUsers.length) {
            alert('No users to export.');
            return;
        }
        let rows = [['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Nationality', 'Status', 'Registered Date', 'Last Login']];
        allUsers.forEach(user => {
            rows.push([
                user.id,
                user.first_name,
                user.last_name,
                user.email,
                user.phone || '',
                user.nationality || '',
                user.is_active ? 'Active' : 'Inactive',
                user.created_at,
                user.last_login || ''
            ]);
        });
        let csvContent = rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n');
        const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.href = url;
        link.setAttribute('download', 'jettransfer_users.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    // Print/PDF
    function printUsers() {
        if (!allUsers.length) {
            alert('No users to print.');
            return;
        }
        const printWindow = window.open('', '_blank');
        let html = `
            <html>
            <head><title>Jettransfer - Users Report</title>
            <style>
                body { font-family: 'Manrope', sans-serif; margin: 2rem; }
                h1 { color: #0A7EA4; }
                table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
                th, td { border: 1px solid #ccc; padding: 0.5rem; text-align: left; vertical-align: top; }
                th { background: #f2f2f2; }
                .status-active { color: green; font-weight: bold; }
                .status-inactive { color: red; }
            </style>
            </head>
            <body>
            <h1>Jettransfer - Registered Users Report</h1>
            <p>Generated on: ${new Date().toLocaleString()}</p>
            <table><thead><tr>
                <th>ID</th><th>First Name</th><th>Last Name</th><th>Email</th><th>Phone</th><th>Nationality</th><th>Status</th><th>Registered</th><th>Last Login</th>
            </tr></thead><tbody>
        `;
        allUsers.forEach(user => {
            html += `<tr>
                <td>${escapeHtml(user.id)}</td>
                <td>${escapeHtml(user.first_name)}</td>
                <td>${escapeHtml(user.last_name)}</td>
                <td>${escapeHtml(user.email)}</td>
                <td>${escapeHtml(user.phone || '—')}</td>
                <td>${escapeHtml(user.nationality || '—')}</td>
                <td class="${user.is_active ? 'status-active' : 'status-inactive'}">${user.is_active ? 'Active' : 'Inactive'}</td>
                <td>${escapeHtml(user.created_at)}</td>
                <td>${escapeHtml(user.last_login || '—')}</td>
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