<?php
// ── admin/monthly_report.php ──────────────────────────────────
session_start();
if (!isset($_SESSION['jt_admin'])) { header('Location: login.php'); exit; }

require_once '../db.php';

// Fetch admin info
$ar  = $conn->query("SELECT name,email FROM admins WHERE id=1 LIMIT 1");
$adm = $ar ? $ar->fetch_assoc() : ['name'=>'Admin','email'=>'admin@jettransfer.com'];
$admName  = htmlspecialchars($adm['name']);
$admEmail = htmlspecialchars($adm['email']);
$admInit  = strtoupper(substr($adm['name'],0,1));

// ── Selected month/year (defaults to current)
$selYear  = (int)($_GET['year']  ?? date('Y'));
$selMonth = (int)($_GET['month'] ?? date('n'));
if ($selYear  < 2020 || $selYear  > 2030) $selYear  = (int)date('Y');
if ($selMonth < 1    || $selMonth > 12)   $selMonth = (int)date('n');

$monthStart = sprintf('%04d-%02d-01', $selYear, $selMonth);
$monthEnd   = date('Y-m-t', strtotime($monthStart)); // last day of month
$monthLabel = date('F Y', strtotime($monthStart));

// ── CSV export mode
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = 'JT_MonthlyReport_' . date('Y_m', strtotime($monthStart)) . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

    // Summary block
    fputcsv($out, ['JETTRANSFER TRAVELS — MONTHLY REPORT']);
    fputcsv($out, ['Period:', $monthLabel]);
    fputcsv($out, ['Generated:', date('d M Y, g:i A')]);
    fputcsv($out, ['']);

    // Stats
    $bAll  = $conn->query("SELECT COUNT(*) FROM bookings WHERE travel_date BETWEEN '$monthStart' AND '$monthEnd'")->fetch_row()[0] ?? 0;
    $bConf = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='Confirmed' AND travel_date BETWEEN '$monthStart' AND '$monthEnd'")->fetch_row()[0] ?? 0;
    $bPend = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='Pending'   AND travel_date BETWEEN '$monthStart' AND '$monthEnd'")->fetch_row()[0] ?? 0;
    $bComp = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='Completed' AND travel_date BETWEEN '$monthStart' AND '$monthEnd'")->fetch_row()[0] ?? 0;
    $bCanc = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='Cancelled' AND travel_date BETWEEN '$monthStart' AND '$monthEnd'")->fetch_row()[0] ?? 0;
    $newU  = $conn->query("SELECT COUNT(*) FROM users    WHERE DATE(created_at) BETWEEN '$monthStart' AND '$monthEnd'")->fetch_row()[0] ?? 0;
    $cMsgs = $conn->query("SELECT COUNT(*) FROM contact_messages WHERE DATE(created_at) BETWEEN '$monthStart' AND '$monthEnd'")->fetch_row()[0] ?? 0;

    fputcsv($out, ['SUMMARY']);
    fputcsv($out, ['Total Bookings', $bAll]);
    fputcsv($out, ['Confirmed',      $bConf]);
    fputcsv($out, ['Pending',        $bPend]);
    fputcsv($out, ['Completed',      $bComp]);
    fputcsv($out, ['Cancelled',      $bCanc]);
    fputcsv($out, ['New Users',      $newU]);
    fputcsv($out, ['Contact Messages', $cMsgs]);
    fputcsv($out, ['']);

    // Bookings detail
    fputcsv($out, ['BOOKINGS DETAIL']);
    fputcsv($out, ['#','Package','Customer','Email','Phone','Travel Date','Guests','Price','Status','Booked On']);
    $bRes = $conn->query("SELECT * FROM bookings WHERE travel_date BETWEEN '$monthStart' AND '$monthEnd' ORDER BY travel_date ASC");
    if ($bRes) while ($b = $bRes->fetch_assoc()) {
        fputcsv($out, [
            '#'.$b['id'], $b['package_name'], $b['customer_name'], $b['email'],
            $b['phone'], date('d M Y',strtotime($b['travel_date'])),
            $b['guests'], $b['price'], $b['status'],
            date('d M Y',strtotime($b['created_at']))
        ]);
    }
    fputcsv($out, ['']);

    // New users
    fputcsv($out, ['NEW USERS']);
    fputcsv($out, ['#','Name','Email','Phone','Nationality','Registered On']);
    $uRes = $conn->query("SELECT * FROM users WHERE DATE(created_at) BETWEEN '$monthStart' AND '$monthEnd' ORDER BY created_at ASC");
    if ($uRes) while ($u = $uRes->fetch_assoc()) {
        fputcsv($out, [
            '#'.$u['id'],
            $u['first_name'].' '.$u['last_name'],
            $u['email'], $u['phone']??'—', $u['nationality']??'—',
            date('d M Y',strtotime($u['created_at']))
        ]);
    }
    fputcsv($out, ['']);

    // Contact messages
    fputcsv($out, ['CONTACT MESSAGES']);
    fputcsv($out, ['#','Name','Email','Phone','Topic','Status','Date']);
    $cRes = $conn->query("SELECT * FROM contact_messages WHERE DATE(created_at) BETWEEN '$monthStart' AND '$monthEnd' ORDER BY created_at ASC");
    if ($cRes) while ($c = $cRes->fetch_assoc()) {
        fputcsv($out, [
            '#'.$c['id'], $c['name'], $c['email'],
            $c['phone']??'—', $c['topic']??'—', $c['status'],
            date('d M Y',strtotime($c['created_at']))
        ]);
    }

    fclose($out);
    $conn->close();
    exit;
}

// ── Fetch stats for display ───────────────────────────────────
$bAll  = $conn->query("SELECT COUNT(*) FROM bookings WHERE travel_date BETWEEN '$monthStart' AND '$monthEnd'")->fetch_row()[0] ?? 0;
$bConf = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='Confirmed' AND travel_date BETWEEN '$monthStart' AND '$monthEnd'")->fetch_row()[0] ?? 0;
$bPend = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='Pending'   AND travel_date BETWEEN '$monthStart' AND '$monthEnd'")->fetch_row()[0] ?? 0;
$bComp = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='Completed' AND travel_date BETWEEN '$monthStart' AND '$monthEnd'")->fetch_row()[0] ?? 0;
$bCanc = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='Cancelled' AND travel_date BETWEEN '$monthStart' AND '$monthEnd'")->fetch_row()[0] ?? 0;
$newUsers = $conn->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) BETWEEN '$monthStart' AND '$monthEnd'")->fetch_row()[0] ?? 0;
$cMsgs    = $conn->query("SELECT COUNT(*) FROM contact_messages WHERE DATE(created_at) BETWEEN '$monthStart' AND '$monthEnd'")->fetch_row()[0] ?? 0;

// Top packages this month
$topPkgs = [];
$tpRes = $conn->query("SELECT package_name, COUNT(*) as cnt FROM bookings WHERE travel_date BETWEEN '$monthStart' AND '$monthEnd' GROUP BY package_name ORDER BY cnt DESC LIMIT 5");
if ($tpRes) while ($tp = $tpRes->fetch_assoc()) $topPkgs[] = $tp;

// Bookings list
$bookings = [];
$bRes = $conn->query("SELECT * FROM bookings WHERE travel_date BETWEEN '$monthStart' AND '$monthEnd' ORDER BY travel_date ASC");
if ($bRes) while ($b = $bRes->fetch_assoc()) $bookings[] = $b;

// New users list
$newUsersList = [];
$uRes = $conn->query("SELECT * FROM users WHERE DATE(created_at) BETWEEN '$monthStart' AND '$monthEnd' ORDER BY created_at ASC");
if ($uRes) while ($u = $uRes->fetch_assoc()) $newUsersList[] = $u;

// Contact messages list
$contactMsgs = [];
$cRes = $conn->query("SELECT * FROM contact_messages WHERE DATE(created_at) BETWEEN '$monthStart' AND '$monthEnd' ORDER BY created_at DESC LIMIT 20");
if ($cRes) while ($c = $cRes->fetch_assoc()) $contactMsgs[] = $c;

// Available years for selector
$years = [];
for ($y = (int)date('Y'); $y >= 2024; $y--) $years[] = $y;

$conn->close();

$months = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Monthly Report – <?= $monthLabel ?> – Jettransfer Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--primary:#0A7EA4;--primary-dark:#065A7A;--accent:#10B981;--secondary:#F59E0B;--danger:#EF4444;--text-dark:#0F172A;--text-light:#94A3B8;--bg:#F1F5F9;--sidebar-bg:#0F172A;--border:#E2E8F0;--shadow-sm:0 1px 3px rgba(0,0,0,.06);--shadow-md:0 4px 16px rgba(0,0,0,.08);--shadow-lg:0 10px 40px rgba(0,0,0,.15);--sidebar-w:260px}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Manrope',sans-serif;background:var(--bg);color:var(--text-dark);min-height:100vh;display:flex}
/* SIDEBAR */
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
/* ─── Main ─── */
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column}
.topbar{background:#fff;border-bottom:1px solid var(--border);padding:0 2rem;height:70px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;box-shadow:var(--shadow-sm)}
.topbar-left{display:flex;align-items:center;gap:1rem}
.hamburger{display:none;background:none;border:none;cursor:pointer;padding:6px;color:var(--text-dark)}
.page-title h1{font-family:'Sora',sans-serif;font-size:1.2rem;font-weight:700}
.page-title p{font-size:.8rem;color:var(--text-light)}
.topbar-avatar{width:40px;height:40px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.9rem}
/* ─── Content ─── */
.content{padding:2rem;flex:1}
/* Month selector bar */
.report-toolbar{background:white;border-radius:16px;box-shadow:var(--shadow-sm);border:1px solid var(--border);padding:1.2rem 1.5rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap}
.report-toolbar h2{font-family:'Sora',sans-serif;font-size:1.1rem;font-weight:700;flex:1}
.sel-group{display:flex;align-items:center;gap:.6rem}
.sel-group label{font-size:.82rem;font-weight:600;color:var(--text-light)}
.sel-input{padding:.55rem .9rem;border:2px solid var(--border);border-radius:10px;font-family:inherit;font-size:.88rem;color:var(--text-dark);outline:none;cursor:pointer;background:white;transition:border-color .25s}
.sel-input:focus{border-color:var(--primary)}
.btn-load{padding:.6rem 1.3rem;background:var(--primary);color:white;border:none;border-radius:10px;font-family:inherit;font-size:.88rem;font-weight:700;cursor:pointer;transition:all .25s}
.btn-load:hover{background:var(--primary-dark)}
.export-btns{display:flex;gap:.6rem}
.btn-csv{display:inline-flex;align-items:center;gap:.5rem;padding:.6rem 1.3rem;background:#065F46;color:white;border:none;border-radius:10px;font-family:inherit;font-size:.85rem;font-weight:700;cursor:pointer;text-decoration:none;transition:all .25s}
.btn-csv:hover{background:#047857;transform:translateY(-1px)}
.btn-print{display:inline-flex;align-items:center;gap:.5rem;padding:.6rem 1.3rem;background:#1D4ED8;color:white;border:none;border-radius:10px;font-family:inherit;font-size:.85rem;font-weight:700;cursor:pointer;transition:all .25s}
.btn-print:hover{background:#1E40AF;transform:translateY(-1px)}
/* ─── Stats grid ─── */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1.2rem;margin-bottom:1.5rem}
.stat-card{background:white;border-radius:16px;padding:1.4rem;box-shadow:var(--shadow-sm);border:1px solid var(--border);display:flex;align-items:center;gap:1rem}
.stat-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0}
.si-blue{background:#EFF6FF}.si-green{background:#ECFDF5}.si-amber{background:#FFFBEB}.si-red{background:#FEF2F2}.si-purple{background:#F5F3FF}.si-teal{background:#F0FDFA}.si-pink{background:#FDF2F8}
.stat-val{font-family:'Sora',sans-serif;font-size:1.8rem;font-weight:800;line-height:1.1}
.stat-lbl{font-size:.78rem;color:var(--text-light);margin-top:.2rem}
/* ─── Two-col layout ─── */
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem}
/* ─── Cards ─── */
.report-card{background:white;border-radius:16px;box-shadow:var(--shadow-sm);border:1px solid var(--border);overflow:hidden}
.rc-header{padding:1rem 1.4rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
.rc-header h3{font-family:'Sora',sans-serif;font-size:.95rem;font-weight:700}
.rc-header span{font-size:.8rem;color:var(--text-light)}
/* ─── Booking status breakdown ─── */
.status-bars{padding:1.2rem 1.4rem;display:flex;flex-direction:column;gap:.9rem}
.sb-row{display:flex;flex-direction:column;gap:.3rem}
.sb-top{display:flex;justify-content:space-between;font-size:.82rem}
.sb-label{font-weight:600}
.sb-val{color:var(--text-light)}
.sb-bar{height:8px;border-radius:4px;background:#F1F5F9;overflow:hidden}
.sb-fill{height:100%;border-radius:4px;transition:width .8s ease}
.fill-conf{background:#10B981}.fill-pend{background:#F59E0B}.fill-comp{background:#3B82F6}.fill-canc{background:#EF4444}
/* ─── Top packages ─── */
.pkg-list{padding:.5rem 0}
.pkg-row{display:flex;align-items:center;gap:1rem;padding:.75rem 1.4rem;border-bottom:1px solid #F1F5F9;transition:background .15s}
.pkg-row:last-child{border-bottom:none}
.pkg-row:hover{background:#F8FAFC}
.pkg-rank{width:26px;height:26px;border-radius:50%;background:var(--primary);color:white;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;flex-shrink:0}
.pkg-rank.r1{background:linear-gradient(135deg,#F59E0B,#D97706)}
.pkg-rank.r2{background:linear-gradient(135deg,#6B7280,#4B5563)}
.pkg-rank.r3{background:linear-gradient(135deg,#B45309,#92400E)}
.pkg-name{flex:1;font-size:.88rem;font-weight:600}
.pkg-cnt{font-family:'Sora',sans-serif;font-size:.85rem;font-weight:700;color:var(--primary)}
/* ─── Table ─── */
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead th{text-align:left;font-size:.72rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.5px;padding:.75rem 1.2rem;background:#F8FAFC;border-bottom:1px solid var(--border);white-space:nowrap}
tbody tr{border-bottom:1px solid #F1F5F9;transition:background .15s}
tbody tr:hover{background:#F8FAFC}
tbody tr:last-child{border-bottom:none}
td{padding:.75rem 1.2rem;font-size:.85rem;vertical-align:middle}
.status-pill{display:inline-flex;align-items:center;padding:.22rem .7rem;border-radius:50px;font-size:.72rem;font-weight:700}
.sp-pending{background:#FEF3C7;color:#92400E}
.sp-confirmed{background:#ECFDF5;color:#065F46}
.sp-cancelled{background:#FEE2E2;color:#991B1B}
.sp-completed{background:#DBEAFE;color:#1E40AF}
.empty-state{text-align:center;padding:2.5rem;color:var(--text-light);font-size:.88rem}
/* ─── Full width section ─── */
.full-card{margin-bottom:1.5rem}
/* ─── Alert ─── */
.no-data-banner{background:linear-gradient(135deg,#EFF6FF,#F0FDFA);border:1px solid var(--border);border-radius:14px;padding:2rem;text-align:center;color:var(--text-light);margin-bottom:1.5rem}
.no-data-banner .icon{font-size:2.5rem;margin-bottom:.5rem}
.no-data-banner p{font-size:.9rem}
/* ─── Responsive ─── */
@media(max-width:1200px){.stats-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:968px){.two-col{grid-template-columns:1fr}}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .main{margin-left:0}
  .hamburger{display:flex}
  .stats-grid{grid-template-columns:1fr 1fr}
  .content{padding:1rem}
  .topbar{padding:0 1rem}
  .report-toolbar{flex-direction:column;align-items:stretch}
}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99}
.sidebar-overlay.show{display:block}
/* ─── PRINT STYLES ─── */
@media print {
  .sidebar,.topbar,.report-toolbar .export-btns,.hamburger,.sidebar-overlay{display:none!important}
  .main{margin-left:0!important}
  body{background:white}
  .report-card,.stat-card{box-shadow:none;border:1px solid #ddd}
  .stats-grid{grid-template-columns:repeat(4,1fr)}
  .two-col{grid-template-columns:1fr 1fr}
  .content{padding:0}
  @page{margin:1.5cm}
  .print-header{display:block!important}
}
.print-header{display:none;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:2px solid #E2E8F0}
.print-header h1{font-family:'Sora',sans-serif;font-size:1.4rem;font-weight:800;color:#0A7EA4}
.print-header p{font-size:.85rem;color:#64748B;margin-top:.3rem}
</style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ── SIDEBAR ── -->
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
        <a href="profiles.php" class="nav-item">
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
        <a href="admin_monthly_report.php" class="nav-item active">
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

<!-- ── MAIN ── -->
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button class="hamburger" onclick="toggleSidebar()">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="page-title"><h1>Monthly Report</h1><p><?= $monthLabel ?></p></div>
    </div>
    <div class="topbar-right">
      <div class="topbar-avatar"><?= $admInit ?></div>
    </div>
  </header>

  <div class="content">

    <!-- Print header (only visible when printing) -->
    <div class="print-header">
      <h1>🏝️ Jettransfer Travels — Monthly Report</h1>
      <p>Period: <?= $monthLabel ?> &nbsp;|&nbsp; Generated: <?= date('d M Y, g:i A') ?> &nbsp;|&nbsp; By: <?= $admName ?></p>
    </div>

    <!-- ── Toolbar ── -->
    <div class="report-toolbar">
      <h2>📅 <?= $monthLabel ?></h2>
      <form method="GET" action="admin_monthly_report.php" style="display:flex;align-items:center;gap:.8rem;flex-wrap:wrap">
        <div class="sel-group">
          <label>Month</label>
          <select name="month" class="sel-input">
            <?php for ($m=1;$m<=12;$m++): ?>
              <option value="<?= $m ?>" <?= $m===$selMonth?'selected':'' ?>><?= $months[$m] ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="sel-group">
          <label>Year</label>
          <select name="year" class="sel-input">
            <?php foreach($years as $y): ?>
              <option value="<?= $y ?>" <?= $y===$selYear?'selected':'' ?>><?= $y ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn-load">Load Report</button>
      </form>
      <div class="export-btns">
        <a href="admin_monthly_report.php?month=<?= $selMonth ?>&year=<?= $selYear ?>&export=csv" class="btn-csv">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          Export CSV
        </a>
        <button onclick="window.print()" class="btn-print">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
          Print / PDF
        </button>
      </div>
    </div>

    <!-- ── Stats ── -->
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-icon si-blue">📦</div><div><div class="stat-val"><?= $bAll ?></div><div class="stat-lbl">Total Bookings</div></div></div>
      <div class="stat-card"><div class="stat-icon si-green">✅</div><div><div class="stat-val"><?= $bConf ?></div><div class="stat-lbl">Confirmed</div></div></div>
      <div class="stat-card"><div class="stat-icon si-amber">⏳</div><div><div class="stat-val"><?= $bPend ?></div><div class="stat-lbl">Pending</div></div></div>
      <div class="stat-card"><div class="stat-icon si-teal">🏁</div><div><div class="stat-val"><?= $bComp ?></div><div class="stat-lbl">Completed</div></div></div>
      <div class="stat-card"><div class="stat-icon si-red">❌</div><div><div class="stat-val"><?= $bCanc ?></div><div class="stat-lbl">Cancelled</div></div></div>
      <div class="stat-card"><div class="stat-icon si-purple">👥</div><div><div class="stat-val"><?= $newUsers ?></div><div class="stat-lbl">New Users</div></div></div>
      <div class="stat-card"><div class="stat-icon si-pink">📩</div><div><div class="stat-val"><?= $cMsgs ?></div><div class="stat-lbl">Contact Messages</div></div></div>
    </div>

    <?php if ($bAll === 0 && $newUsers === 0 && $cMsgs === 0): ?>
    <div class="no-data-banner">
      <div class="icon">📭</div>
      <p>No activity recorded for <strong><?= $monthLabel ?></strong>. Try selecting a different month.</p>
    </div>
    <?php endif; ?>

    <!-- ── Two-col: Status breakdown + Top packages ── -->
    <div class="two-col">
      <!-- Booking status breakdown -->
      <div class="report-card">
        <div class="rc-header"><h3>Booking Status Breakdown</h3><span><?= $bAll ?> total</span></div>
        <div class="status-bars">
          <?php
          $statuses = [
            ['Confirmed', $bConf, 'fill-conf'],
            ['Pending',   $bPend, 'fill-pend'],
            ['Completed', $bComp, 'fill-comp'],
            ['Cancelled', $bCanc, 'fill-canc'],
          ];
          foreach ($statuses as [$lbl, $cnt, $cls]):
            $pct = $bAll > 0 ? round($cnt / $bAll * 100) : 0;
          ?>
          <div class="sb-row">
            <div class="sb-top"><span class="sb-label"><?= $lbl ?></span><span class="sb-val"><?= $cnt ?> (<?= $pct ?>%)</span></div>
            <div class="sb-bar"><div class="sb-fill <?= $cls ?>" style="width:<?= $pct ?>%"></div></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Top packages -->
      <div class="report-card">
        <div class="rc-header"><h3>Top Packages This Month</h3><span>By bookings</span></div>
        <?php if (empty($topPkgs)): ?>
          <div class="empty-state">No package bookings this month.</div>
        <?php else: ?>
        <div class="pkg-list">
          <?php foreach ($topPkgs as $i => $tp):
            $rankClass = $i===0?'r1':($i===1?'r2':($i===2?'r3':''));
          ?>
          <div class="pkg-row">
            <div class="pkg-rank <?= $rankClass ?>"><?= $i+1 ?></div>
            <span class="pkg-name"><?= htmlspecialchars($tp['package_name']) ?></span>
            <span class="pkg-cnt"><?= $tp['cnt'] ?> booking<?= $tp['cnt']>1?'s':'' ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ── Bookings table ── -->
    <div class="report-card full-card">
      <div class="rc-header"><h3>📋 Bookings for <?= $monthLabel ?></h3><span><?= count($bookings) ?> bookings</span></div>
      <?php if (empty($bookings)): ?>
        <div class="empty-state">No bookings for this period.</div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Package</th><th>Customer</th><th>Email</th><th>Travel Date</th><th>Guests</th><th>Price</th><th>Status</th><th>Booked On</th></tr></thead>
          <tbody>
          <?php foreach ($bookings as $b): ?>
          <tr>
            <td style="color:var(--text-light);font-size:.78rem">#<?= $b['id'] ?></td>
            <td style="font-weight:600;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($b['package_name']) ?></td>
            <td><?= htmlspecialchars($b['customer_name']) ?></td>
            <td style="color:var(--text-light);font-size:.82rem"><?= htmlspecialchars($b['email']) ?></td>
            <td><?= date('d M Y', strtotime($b['travel_date'])) ?></td>
            <td><?= $b['guests'] ?></td>
            <td style="font-weight:700;color:var(--primary)"><?= htmlspecialchars($b['price']) ?></td>
            <td><span class="status-pill sp-<?= strtolower($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
            <td style="color:var(--text-light);font-size:.8rem"><?= date('d M Y', strtotime($b['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- ── New users ── -->
    <div class="report-card full-card">
      <div class="rc-header"><h3>👤 New Registrations in <?= $monthLabel ?></h3><span><?= count($newUsersList) ?> users</span></div>
      <?php if (empty($newUsersList)): ?>
        <div class="empty-state">No new registrations this month.</div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Nationality</th><th>Registered On</th></tr></thead>
          <tbody>
          <?php foreach ($newUsersList as $u): ?>
          <tr>
            <td style="color:var(--text-light);font-size:.78rem">#<?= $u['id'] ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></td>
            <td style="color:var(--text-light);font-size:.82rem"><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['phone']??'—') ?></td>
            <td><?= htmlspecialchars($u['nationality']??'—') ?></td>
            <td style="color:var(--text-light);font-size:.8rem"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- ── Contact messages ── -->
    <div class="report-card full-card">
      <div class="rc-header"><h3>📩 Contact Messages in <?= $monthLabel ?></h3><span><?= $cMsgs ?> messages</span></div>
      <?php if (empty($contactMsgs)): ?>
        <div class="empty-state">No contact messages this month.</div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Topic</th><th>Status</th><th>Date</th></tr></thead>
          <tbody>
          <?php foreach ($contactMsgs as $c): ?>
          <tr>
            <td style="color:var(--text-light);font-size:.78rem">#<?= $c['id'] ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($c['name']) ?></td>
            <td style="color:var(--text-light);font-size:.82rem"><?= htmlspecialchars($c['email']) ?></td>
            <td><span style="background:#E0F2FA;color:#065A7A;padding:.2rem .6rem;border-radius:6px;font-size:.73rem;font-weight:600"><?= htmlspecialchars($c['topic']??'General') ?></span></td>
            <td><span class="status-pill sp-<?= strtolower(str_replace(' ','-',$c['status'])) ?>"><?= htmlspecialchars($c['status']) ?></span></td>
            <td style="color:var(--text-light);font-size:.8rem"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

  </div><!-- /.content -->
</div><!-- /.main -->

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
document.getElementById('sidebarOverlay').addEventListener('click', () => {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
});

// Animate stat bars on load
document.querySelectorAll('.sb-fill').forEach(el => {
  const w = el.style.width;
  el.style.width = '0';
  setTimeout(() => { el.style.width = w; }, 200);
});
</script>
</body>
</html>