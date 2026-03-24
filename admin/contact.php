<?php
session_start();
if (!isset($_SESSION['jt_admin'])) { header('Location: login.php'); exit; }

require_once '../db.php';

// Fetch admin info for topbar dropdown
$ar  = $conn->query("SELECT name,email FROM admins WHERE id=1 LIMIT 1");
$adm = $ar ? $ar->fetch_assoc() : ['name' => 'Admin', 'email' => 'admin@jettransfer.com'];
$admName  = htmlspecialchars($adm['name']);
$admEmail = htmlspecialchars($adm['email']);
$admInit  = strtoupper(substr($adm['name'], 0, 1));

// Statistics
$stats = ['total' => 0, 'new' => 0, 'read' => 0, 'in_progress' => 0, 'replied' => 0, 'closed' => 0];
$r = $conn->query("SELECT status, COUNT(*) as cnt FROM contact_messages GROUP BY status");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $key = strtolower(str_replace(' ', '_', $row['status']));
        if (isset($stats[$key])) $stats[$key] = (int)$row['cnt'];
        $stats['total'] += (int)$row['cnt'];
    }
}

// AJAX handlers
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'];

    switch ($action) {

        case 'list':
            $sf = $_POST['status'] ?? 'all';
            $q  = trim($_POST['search'] ?? '');
            $where = [];
            if ($sf && $sf !== 'all') {
                $sd = $conn->real_escape_string(ucwords(str_replace('_', ' ', $sf)));
                $where[] = "status='$sd'";
            }
            if ($q) {
                $s = $conn->real_escape_string($q);
                $where[] = "(name LIKE '%$s%' OR email LIKE '%$s%' OR message LIKE '%$s%' OR phone LIKE '%$s%' OR topic LIKE '%$s%')";
            }
            $sql = 'SELECT * FROM contact_messages';
            if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
            $sql .= ' ORDER BY created_at DESC';
            $res  = $conn->query($sql);
            $msgs = [];
            if ($res) while ($row = $res->fetch_assoc()) $msgs[] = $row;
            echo json_encode(['success' => true, 'messages' => $msgs]);
            break;

        case 'get':
            $id  = (int)$_POST['id'];
            $res = $conn->query("SELECT * FROM contact_messages WHERE id=$id LIMIT 1");
            if ($res && $res->num_rows > 0) {
                $msg = $res->fetch_assoc();
                if ($msg['status'] === 'New') {
                    $conn->query("UPDATE contact_messages SET status='Read' WHERE id=$id");
                    $msg['status'] = 'Read';
                }
                echo json_encode(['success' => true, 'message' => $msg]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Message not found.']);
            }
            break;

        case 'update':
            $id     = (int)$_POST['id'];
            $status = $conn->real_escape_string($_POST['status'] ?? '');
            $reply  = $conn->real_escape_string($_POST['admin_reply'] ?? '');
            $notes  = $conn->real_escape_string($_POST['admin_notes'] ?? '');
            $valid  = ['New', 'Read', 'In Progress', 'Replied', 'Closed'];
            if (!in_array($status, $valid)) {
                echo json_encode(['success' => false, 'message' => 'Invalid status.']);
                break;
            }
            $sql = "UPDATE contact_messages SET status='$status', admin_reply='$reply', admin_notes='$notes', updated_at=NOW() WHERE id=$id";
            echo $conn->query($sql)
                ? json_encode(['success' => true, 'message' => 'Updated successfully.'])
                : json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
            break;

        case 'delete':
            $id = (int)$_POST['id'];
            echo $conn->query("DELETE FROM contact_messages WHERE id=$id")
                ? json_encode(['success' => true, 'message' => 'Deleted successfully.'])
                : json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
    $conn->close();
    exit;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Contact Messages – Jettransfer Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--primary:#0A7EA4;--primary-dark:#065A7A;--primary-light:#E0F2FA;--secondary:#F59E0B;--accent:#10B981;--text-dark:#0F172A;--text-light:#94A3B8;--bg:#F1F5F9;--sidebar-bg:#0F172A;--border:#E2E8F0;--shadow-sm:0 1px 3px rgba(0,0,0,.06);--shadow-md:0 4px 16px rgba(0,0,0,.08);--sidebar-w:260px}
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
        .btn-logout { display:flex; align-items:center; gap:.6rem; padding:.7rem .9rem; border-radius:12px; color:rgba(255,255,255,.5); font-size:.88rem; font-weight:500; cursor:pointer; transition:all .2s; border:none; background:none; width:100%; font-family:'Manrope',sans-serif; }
        .btn-logout:hover { background:rgba(239,68,68,.15); color:#FCA5A5; }

/* ─── Main ─── */
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column}
.topbar{background:#fff;border-bottom:1px solid var(--border);padding:0 2rem;height:70px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;box-shadow:var(--shadow-sm)}
.topbar-left{display:flex;align-items:center;gap:1rem}
.hamburger{display:none;background:none;border:none;cursor:pointer;padding:6px;color:var(--text-dark)}
.page-title h1{font-family:'Sora',sans-serif;font-size:1.2rem;font-weight:700}
.page-title p{font-size:.8rem;color:var(--text-light)}

/* ─── Admin profile dropdown in topbar ─── */
.admin-dropdown{position:relative}
.admin-pill{display:flex;align-items:center;gap:.5rem;padding:.4rem .9rem .4rem .45rem;border-radius:50px;border:2px solid #E2E8F0;background:white;cursor:pointer;font-family:'Manrope',sans-serif;font-weight:600;font-size:.88rem;color:var(--text-dark);transition:all .25s;white-space:nowrap}
.admin-pill:hover{border-color:var(--primary);box-shadow:0 2px 12px rgba(10,126,164,.15)}
.admin-pill-avatar{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--primary-dark),#043D54);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;color:#fff;flex-shrink:0}
.admin-dd-menu{position:absolute;right:0;top:calc(100% + .6rem);background:white;border:1px solid #E2E8F0;border-radius:18px;box-shadow:0 10px 40px rgba(0,0,0,.14);min-width:240px;z-index:200;opacity:0;transform:translateY(-8px) scale(.97);pointer-events:none;transition:all .22s ease}
.admin-dd-menu.open{opacity:1;transform:translateY(0) scale(1);pointer-events:all}
.adm-header{display:flex;align-items:center;gap:.75rem;padding:1rem 1.1rem .8rem}
.adm-avatar{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--primary-dark),#043D54);display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:700;color:#fff;flex-shrink:0}
.adm-name{font-weight:700;font-size:.92rem;color:var(--text-dark);margin-bottom:.1rem}
.adm-role{font-size:.72rem;color:#94A3B8;text-transform:uppercase;letter-spacing:.5px}
.adm-email{font-size:.73rem;color:#94A3B8;margin-top:.15rem;overflow:hidden;text-overflow:ellipsis;max-width:170px}
.adm-divider{height:1px;background:#F1F5F9;margin:.3rem 0}
.adm-item{display:flex;align-items:center;gap:.6rem;padding:.65rem 1rem;color:var(--text-dark);text-decoration:none;font-size:.88rem;font-weight:500;transition:background .15s;border-radius:10px;margin:.1rem .4rem}
.adm-item:hover{background:#F1F5F9;color:var(--primary)}
.adm-item.site{color:var(--text-light)}
.adm-item.logout{color:#EF4444!important}
.adm-item.logout:hover{background:#FEE2E2!important;color:#DC2626!important}

.content{padding:2rem;flex:1}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99}
.sidebar-overlay.show{display:block}

/* ─── Statistics ─── */
.stats-row{display:flex;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap}
.stat-card{background:#fff;border-radius:14px;padding:1.1rem 1.4rem;flex:1;min-width:110px;border:1px solid var(--border);box-shadow:var(--shadow-sm);cursor:pointer;transition:transform .2s,box-shadow .2s}
.stat-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-md)}
.stat-card h4{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-light);margin-bottom:.3rem}
.stat-card .num{font-family:'Sora',sans-serif;font-size:1.6rem;font-weight:800;color:var(--text-dark)}
.stat-card.total{border-left:4px solid var(--primary)}
.stat-card.new{border-left:4px solid #FBBF24}
.stat-card.read{border-left:4px solid #3B82F6}
.stat-card.progress{border-left:4px solid #F97316}
.stat-card.replied{border-left:4px solid #10B981}
.stat-card.closed{border-left:4px solid #6B7280}

/* ─── Filters ─── */
.filters-bar{background:#fff;border-radius:14px;padding:1rem 1.3rem;margin-bottom:1.5rem;border:1px solid var(--border);box-shadow:var(--shadow-sm);display:flex;align-items:center;gap:1rem;flex-wrap:wrap}
.search-box{flex:1;min-width:200px;position:relative}
.search-box input{width:100%;padding:.65rem 1rem .65rem 2.5rem;border:1.5px solid var(--border);border-radius:10px;font-family:'Manrope',sans-serif;font-size:.88rem;outline:none;transition:border-color .25s}
.search-box input:focus{border-color:var(--primary)}
.search-box::before{content:'🔍';position:absolute;left:.9rem;top:50%;transform:translateY(-50%);font-size:.9rem}
.filter-buttons{display:flex;gap:.5rem;flex-wrap:wrap}
.fbtn{padding:.45rem 1rem;border-radius:50px;border:1.5px solid var(--border);background:#fff;color:var(--text-light);font-family:'Manrope',sans-serif;font-size:.8rem;font-weight:600;cursor:pointer;transition:all .2s}
.fbtn:hover{border-color:var(--primary);color:var(--primary)}
.fbtn.active{background:var(--primary);border-color:var(--primary);color:#fff}

/* ─── Table ─── */
.table-card{background:#fff;border-radius:18px;border:1px solid var(--border);box-shadow:var(--shadow-sm);overflow:hidden}
.table-header{padding:1.1rem 1.4rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
.table-header h3{font-family:'Sora',sans-serif;font-size:1rem;font-weight:700}
.table-header span{font-size:.82rem;color:var(--text-light)}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th{text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--text-light);padding:.9rem 1.2rem;background:#FAFBFC;border-bottom:1px solid var(--border);white-space:nowrap}
td{padding:.85rem 1.2rem;border-bottom:1px solid #F1F5F9;vertical-align:middle;font-size:.88rem}
tr:last-child td{border-bottom:none}
tr:hover td{background:#FAFBFD}

/* Status badges */
.status-badge{display:inline-flex;align-items:center;gap:.3rem;padding:.22rem .7rem;border-radius:20px;font-size:.72rem;font-weight:700}
.status-badge.new{background:#FEF3C7;color:#92400E}
.status-badge.read{background:#DBEAFE;color:#1E40AF}
.status-badge.in-progress{background:#FED7AA;color:#C2410C}
.status-badge.replied{background:#D1FAE5;color:#065F46}
.status-badge.closed{background:#E5E7EB;color:#374151}

/* Topic badge */
.topic-badge{display:inline-block;padding:.18rem .6rem;border-radius:6px;font-size:.72rem;font-weight:600;background:var(--primary-light);color:var(--primary-dark)}

/* Actions */
.actions{display:flex;gap:.4rem}
.action-btn{background:none;border:none;cursor:pointer;font-size:.82rem;font-weight:600;padding:.35rem .65rem;border-radius:7px;transition:all .15s;font-family:'Manrope',sans-serif}
.action-btn.view{color:var(--primary)}
.action-btn.view:hover{background:var(--primary-light)}
.action-btn.delete{color:#EF4444}
.action-btn.delete:hover{background:#FEE2E2}

/* Empty state */
.empty-state{text-align:center;padding:3.5rem 2rem;color:var(--text-light)}
.empty-state .icon{font-size:3rem;margin-bottom:1rem}
.empty-state h4{font-family:'Sora',sans-serif;font-size:1.1rem;font-weight:700;color:var(--text-dark);margin-bottom:.5rem}
.empty-state p{font-size:.88rem}

/* ─── Modal ─── */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;display:none;align-items:center;justify-content:center;padding:1rem}
.modal-overlay.show{display:flex}
.modal{background:#fff;border-radius:20px;max-width:620px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 25px 60px rgba(0,0,0,.25);animation:modalIn .3s ease}
@keyframes modalIn{from{opacity:0;transform:scale(.95) translateY(-20px)}to{opacity:1;transform:scale(1) translateY(0)}}
.modal-header{padding:1.3rem 1.6rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;background:#fff;z-index:1}
.modal-header h2{font-family:'Sora',sans-serif;font-size:1.1rem;font-weight:700}
.modal-close{width:32px;height:32px;border-radius:50%;border:none;background:#F1F5F9;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:var(--text-light);transition:all .2s}
.modal-close:hover{background:#E2E8F0;color:var(--text-dark)}
.modal-body{padding:1.6rem}
.modal-footer{padding:1.2rem 1.6rem;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:.8rem;position:sticky;bottom:0;background:#fff}

/* Message detail */
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.2rem}
.detail-group{margin-bottom:.5rem}
.detail-label{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-light);margin-bottom:.3rem}
.detail-value{font-size:.9rem;color:var(--text-dark)}
.detail-value a{color:var(--primary);text-decoration:none}
.detail-value a:hover{text-decoration:underline}
.msg-content{background:#F8FAFC;padding:1rem;border-radius:10px;font-size:.9rem;line-height:1.6;max-height:200px;overflow-y:auto;white-space:pre-wrap;word-break:break-word;border:1px solid var(--border)}
.section-divider{border:none;border-top:1px solid var(--border);margin:1.4rem 0}
.section-title{font-family:'Sora',sans-serif;font-size:.95rem;font-weight:700;margin-bottom:1rem;color:var(--text-dark)}
.form-group{margin-bottom:1rem}
.form-group label{display:block;font-size:.78rem;font-weight:700;color:#475569;margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.5px}
.form-group select,.form-group textarea{width:100%;padding:.65rem .9rem;border:1.5px solid var(--border);border-radius:10px;font-family:'Manrope',sans-serif;font-size:.88rem;color:var(--text-dark);outline:none;transition:border-color .25s;background:#fff}
.form-group select:focus,.form-group textarea:focus{border-color:var(--primary)}
.form-group textarea{resize:vertical;min-height:100px}

/* Buttons */
.btn{padding:.65rem 1.3rem;border-radius:10px;font-family:'Manrope',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;transition:all .2s;border:none}
.btn-primary{background:var(--primary);color:#fff}
.btn-primary:hover{background:var(--primary-dark)}
.btn-primary:disabled{opacity:.6;cursor:not-allowed}
.btn-secondary{background:#F1F5F9;color:var(--text-dark);border:1.5px solid var(--border)}
.btn-secondary:hover{background:#E2E8F0}
.btn-danger{background:#FEE2E2;color:#DC2626;border:1.5px solid #FECACA}
.btn-danger:hover{background:#FECACA}

/* Toast */
.toast{position:fixed;bottom:2rem;right:2rem;padding:1rem 1.5rem;border-radius:12px;font-weight:600;display:none;align-items:center;gap:.6rem;box-shadow:0 10px 30px rgba(0,0,0,.2);z-index:9999}
.toast.show{display:flex;animation:toastIn .3s ease}
.toast.success{background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0}
.toast.error{background:#FEE2E2;color:#991B1B;border:1px solid #FECACA}
@keyframes toastIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}

/* Responsive */
@media(max-width:768px){
    .sidebar{transform:translateX(-100%)}
    .sidebar.open{transform:translateX(0)}
    .main{margin-left:0}
    .hamburger{display:flex}
    .stats-row{gap:.7rem}
    .stat-card{min-width:90px;padding:.9rem 1rem}
    .stat-card .num{font-size:1.3rem}
    .filters-bar{flex-direction:column;align-items:stretch}
    .search-box{width:100%}
    .content{padding:1rem}
    .topbar{padding:0 1rem}
    .detail-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ── SIDEBAR ── -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="sidebar-logo">✈️</div>
    <div class="sidebar-brand-text"><h2>Jettransfer</h2><span>Admin Panel</span></div>
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
      Services &amp; Gallery
    </a>
    <a href="contact.php" class="nav-item active">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Contact Messages 
    </a>
    <div class="nav-label">Other</div>
    <a href="../index.php" class="nav-item" target="_blank">
      <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      View Site
    </a>
  </nav>
  <div class="sidebar-footer">
        <div class="admin-profile">
            <div class="admin-avatar">A</div>
            <div class="admin-info">
                <h4>Admin</h4>
                <p>admin@jettransfer.com</p>
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

  <!-- Topbar -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="hamburger" onclick="toggleSidebar()">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="page-title"><h1>Contact Messages</h1><p>Manage customer inquiries</p></div>
    </div>

  </header>

  <!-- Content -->
  <div class="content">

    <!-- Statistics (clickable to filter) -->
    <div class="stats-row">
      <div class="stat-card total"  onclick="filterStat('all')"><h4>Total</h4><div class="num"><?= $stats['total'] ?></div></div>
      <div class="stat-card new"    onclick="filterStat('new')"><h4>New 🔔</h4><div class="num"><?= $stats['new'] ?></div></div>
      <div class="stat-card read"   onclick="filterStat('read')"><h4>Read</h4><div class="num"><?= $stats['read'] ?></div></div>
      <div class="stat-card progress" onclick="filterStat('in_progress')"><h4>In Progress</h4><div class="num"><?= $stats['in_progress'] ?></div></div>
      <div class="stat-card replied" onclick="filterStat('replied')"><h4>Replied</h4><div class="num"><?= $stats['replied'] ?></div></div>
      <div class="stat-card closed"  onclick="filterStat('closed')"><h4>Closed</h4><div class="num"><?= $stats['closed'] ?></div></div>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <div class="search-box">
        <input type="text" id="searchInput" placeholder="Search by name, email, phone, topic or message…" oninput="loadMessages()">
      </div>
      <div class="filter-buttons">
        <button class="fbtn active" data-status="all"         onclick="setFilter(this)">All</button>
        <button class="fbtn"        data-status="new"         onclick="setFilter(this)">New</button>
        <button class="fbtn"        data-status="read"        onclick="setFilter(this)">Read</button>
        <button class="fbtn"        data-status="in_progress" onclick="setFilter(this)">In Progress</button>
        <button class="fbtn"        data-status="replied"     onclick="setFilter(this)">Replied</button>
        <button class="fbtn"        data-status="closed"      onclick="setFilter(this)">Closed</button>
      </div>
    </div>

    <!-- Messages Table -->
    <div class="table-card">
      <div class="table-header">
        <h3>Messages</h3>
        <span id="msgCount">Loading…</span>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Topic</th>
              <th>Status</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="messagesTable">
            <tr><td colspan="8"><div class="empty-state"><div class="icon">⏳</div><p>Loading messages…</p></div></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- ── MODAL ── -->
<div class="modal-overlay" id="messageModal">
  <div class="modal">
    <div class="modal-header">
      <h2>Message Details</h2>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="modalId">
      <div class="detail-grid">
        <div class="detail-group"><div class="detail-label">Name</div><div class="detail-value" id="modalName">—</div></div>
        <div class="detail-group"><div class="detail-label">Email</div><div class="detail-value" id="modalEmail">—</div></div>
        <div class="detail-group"><div class="detail-label">Phone</div><div class="detail-value" id="modalPhone">—</div></div>
        <div class="detail-group"><div class="detail-label">Topic</div><div class="detail-value"><span class="topic-badge" id="modalTopic">—</span></div></div>
      </div>
      <div class="detail-group" style="margin-bottom:1rem"><div class="detail-label">Received</div><div class="detail-value" id="modalDate">—</div></div>
      <div class="detail-group"><div class="detail-label">Message</div><div class="msg-content" id="modalMessage">—</div></div>

      <hr class="section-divider">
      <div class="section-title">Admin Response</div>

      <div class="form-group">
        <label>Status</label>
        <select id="modalStatus">
          <option value="New">New</option>
          <option value="Read">Read</option>
          <option value="In Progress">In Progress</option>
          <option value="Replied">Replied</option>
          <option value="Closed">Closed</option>
        </select>
      </div>
      <div class="form-group">
        <label>Internal Notes</label>
        <textarea id="modalNotes" placeholder="Private notes visible only to admins…"></textarea>
      </div>
      <div class="form-group">
        <label>Admin Reply (sent / reference)</label>
        <textarea id="modalReply" placeholder="Response sent to customer or follow-up notes…"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-danger"    onclick="deleteMessage()">Delete</button>
      <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
      <button class="btn btn-primary"   id="saveBtn" onclick="saveMessage()">Save Changes</button>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"><span id="toastIcon"></span><span id="toastText"></span></div>

<script>
let currentFilter = 'all';

// Sidebar
function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('show');
}
document.getElementById('sidebarOverlay').addEventListener('click',()=>{
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('show');
});

// Admin dropdown
function toggleAdminDD(){document.getElementById('adminDDMenu').classList.toggle('open')}
document.addEventListener('click',e=>{
  const w=document.getElementById('adminDropdown');
  if(w&&!w.contains(e.target))document.getElementById('adminDDMenu').classList.remove('open');
});

// Filter via stat cards
function filterStat(status){
  currentFilter=status;
  document.querySelectorAll('.fbtn').forEach(b=>{
    b.classList.toggle('active', b.dataset.status===status);
  });
  loadMessages();
}

function setFilter(btn){
  document.querySelectorAll('.fbtn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  currentFilter=btn.dataset.status;
  loadMessages();
}

function loadMessages(){
  const search=document.getElementById('searchInput').value;
  const fd=new FormData();
  fd.append('ajax_action','list');
  fd.append('status',currentFilter);
  fd.append('search',search);
  fetch('contact.php',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(d=>{if(d.success)renderMessages(d.messages)})
    .catch(()=>showToast('Failed to load messages','error'));
}

function renderMessages(msgs){
  const tbody=document.getElementById('messagesTable');
  document.getElementById('msgCount').textContent=msgs.length+' message'+(msgs.length!==1?'s':'');
  if(!msgs.length){
    tbody.innerHTML=`<tr><td colspan="8"><div class="empty-state"><div class="icon">📭</div><h4>No messages found</h4><p>Try adjusting your filters or search.</p></div></td></tr>`;
    return;
  }
  tbody.innerHTML=msgs.map(m=>{
    const sc=m.status.toLowerCase().replace(' ','-');
    const d=new Date(m.created_at);
    const ds=d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});
    const ts=d.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit'});
    return `<tr>
      <td style="color:var(--text-light);font-size:.78rem">#${m.id}</td>
      <td style="font-weight:600">${esc(m.name)}</td>
      <td><a href="mailto:${esc(m.email)}" style="color:var(--primary);text-decoration:none">${esc(m.email)}</a></td>
      <td style="color:var(--text-light)">${esc(m.phone||'—')}</td>
      <td><span class="topic-badge">${esc(m.topic)}</span></td>
      <td><span class="status-badge ${sc}">${esc(m.status)}</span></td>
      <td style="color:var(--text-light);font-size:.8rem">${ds}<br><span style="font-size:.72rem">${ts}</span></td>
      <td><div class="actions">
        <button class="action-btn view" onclick="viewMessage(${m.id})">View</button>
        <button class="action-btn delete" onclick="confirmDel(${m.id},'${esc(m.name)}')">Delete</button>
      </div></td>
    </tr>`;
  }).join('');
}

function esc(t){const d=document.createElement('div');d.textContent=t||'';return d.innerHTML;}

function viewMessage(id){
  const fd=new FormData();
  fd.append('ajax_action','get');fd.append('id',id);
  fetch('contact.php',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(d=>{if(d.success)openModal(d.message);else showToast(d.message,'error')});
}

function openModal(m){
  document.getElementById('modalId').value=m.id;
  document.getElementById('modalName').textContent=m.name;
  document.getElementById('modalEmail').innerHTML=`<a href="mailto:${esc(m.email)}">${esc(m.email)}</a>`;
  document.getElementById('modalPhone').textContent=m.phone||'—';
  document.getElementById('modalTopic').textContent=m.topic;
  const d=new Date(m.created_at);
  document.getElementById('modalDate').textContent=d.toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric',hour:'2-digit',minute:'2-digit'});
  document.getElementById('modalMessage').textContent=m.message;
  document.getElementById('modalStatus').value=m.status;
  document.getElementById('modalNotes').value=m.admin_notes||'';
  document.getElementById('modalReply').value=m.admin_reply||'';
  document.getElementById('messageModal').classList.add('show');
}

function closeModal(){document.getElementById('messageModal').classList.remove('show')}

function saveMessage(){
  const id=document.getElementById('modalId').value;
  const fd=new FormData();
  fd.append('ajax_action','update');
  fd.append('id',id);
  fd.append('status',document.getElementById('modalStatus').value);
  fd.append('admin_notes',document.getElementById('modalNotes').value);
  fd.append('admin_reply',document.getElementById('modalReply').value);
  const btn=document.getElementById('saveBtn');
  btn.disabled=true;btn.textContent='Saving…';
  fetch('contact.php',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(d=>{
      if(d.success){showToast('Message updated!','success');closeModal();loadMessages();}
      else showToast(d.message||'Error','error');
    })
    .finally(()=>{btn.disabled=false;btn.textContent='Save Changes';});
}

function confirmDel(id,name){
  if(confirm('Delete message from '+name+'? This cannot be undone.'))delMsg(id);
}
function deleteMessage(){
  const id=document.getElementById('modalId').value;
  const name=document.getElementById('modalName').textContent;
  if(confirm('Delete message from '+name+'? This cannot be undone.'))delMsg(id);
}
function delMsg(id){
  const fd=new FormData();
  fd.append('ajax_action','delete');fd.append('id',id);
  fetch('contact.php',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(d=>{
      if(d.success){showToast('Message deleted.','success');closeModal();loadMessages();}
      else showToast(d.message||'Error','error');
    });
}

function showToast(msg,type){
  const t=document.getElementById('toast');
  document.getElementById('toastText').textContent=msg;
  document.getElementById('toastIcon').textContent=type==='success'?'✓':'✕';
  t.className='toast '+type+' show';
  setTimeout(()=>t.classList.remove('show'),3500);
}

// Close modal on background click
document.getElementById('messageModal').addEventListener('click',function(e){if(e.target===this)closeModal()});

// Load on start + auto-refresh every 60s
loadMessages();
setInterval(loadMessages,60000);
</script>
</body>
</html>
