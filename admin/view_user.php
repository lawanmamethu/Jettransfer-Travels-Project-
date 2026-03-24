<?php
session_start();
if (!isset($_SESSION['jt_admin'])) { header('Location: login.php'); exit; }

require_once '../db.php';

$uid = (int)($_GET['id'] ?? 0);
if (!$uid) { header('Location: profiles.php'); exit; }

// Fetch admin info
$ar  = $conn->query("SELECT name,email FROM admins WHERE id=1 LIMIT 1");
$adm = $ar ? $ar->fetch_assoc() : ['name'=>'Admin','email'=>'admin@jettransfer.com'];
$admName  = htmlspecialchars($adm['name']);
$admEmail = htmlspecialchars($adm['email']);
$admInit  = strtoupper(substr($adm['name'],0,1));

// Fetch user
$r    = $conn->query("SELECT * FROM users WHERE id=$uid LIMIT 1");
if (!$r || $r->num_rows === 0) { header('Location: profiles.php?err=notfound'); exit; }
$u = $r->fetch_assoc();

// Fetch user's bookings
$bRes     = $conn->query("SELECT * FROM bookings WHERE email='".$conn->real_escape_string($u['email'])."' ORDER BY created_at DESC");
$bookings = [];
if ($bRes) while ($b = $bRes->fetch_assoc()) $bookings[] = $b;

// Fetch user's contact messages
$cRes  = $conn->query("SELECT * FROM contact_messages WHERE email='".$conn->real_escape_string($u['email'])."' ORDER BY created_at DESC LIMIT 5");
$msgs  = [];
if ($cRes) while ($c = $cRes->fetch_assoc()) $msgs[] = $c;

// Handle quick status toggle
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='toggle') {
    $new = $u['is_active'] ? 0 : 1;
    $conn->query("UPDATE users SET is_active=$new WHERE id=$uid");
    $conn->close(); header("Location: view_user.php?id=$uid&ok=toggled"); exit;
}

$conn->close();

$fullName = trim(($u['first_name']??'').' '.($u['last_name']??''));
$initials = strtoupper(substr($u['first_name']??'U',0,1).substr($u['last_name']??'',0,1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($fullName) ?> – User Detail</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--primary:#0A7EA4;--primary-dark:#065A7A;--accent:#10B981;--secondary:#F59E0B;--text-dark:#0F172A;--text-light:#94A3B8;--bg:#F1F5F9;--sidebar-bg:#0F172A;--border:#E2E8F0;--shadow-sm:0 1px 3px rgba(0,0,0,.06);--shadow-md:0 4px 16px rgba(0,0,0,.08);--sidebar-w:260px;--danger:#EF4444}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Manrope',sans-serif;background:var(--bg);color:var(--text-dark);min-height:100vh;display:flex}
/* Sidebar */
.sidebar{width:var(--sidebar-w);background:var(--sidebar-bg);min-height:100vh;position:fixed;left:0;top:0;bottom:0;display:flex;flex-direction:column;z-index:100;overflow-y:auto}
.sidebar-brand{padding:1.8rem 1.5rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.07);display:flex;align-items:center;gap:.8rem}
.sidebar-logo{width:42px;height:42px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0}
.sidebar-brand-text h2{font-family:'Sora',sans-serif;font-size:1rem;font-weight:800;color:#fff}
.sidebar-brand-text span{font-size:.72rem;color:rgba(255,255,255,.4);letter-spacing:1.5px;text-transform:uppercase}
.sidebar-nav{flex:1;padding:1.2rem .8rem}
.nav-label{font-size:.68rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.25);padding:.5rem .8rem;margin-top:.8rem;margin-bottom:.3rem}
.nav-item{display:flex;align-items:center;gap:.75rem;padding:.75rem .9rem;border-radius:12px;color:rgba(255,255,255,.55);text-decoration:none;font-size:.9rem;font-weight:500;transition:all .2s;margin-bottom:.2rem}
.nav-item:hover{background:rgba(255,255,255,.07);color:rgba(255,255,255,.9)}
.nav-item.active{background:linear-gradient(135deg,rgba(10,126,164,.35),rgba(16,185,129,.2));color:#fff;box-shadow:inset 0 0 0 1px rgba(10,126,164,.4)}
.nav-icon{width:20px;height:20px;flex-shrink:0}
.sidebar-footer{padding:1rem .8rem 1.5rem;border-top:1px solid rgba(255,255,255,.07)}
.sb-admin{display:flex;align-items:center;gap:.75rem;padding:.75rem .9rem;border-radius:12px;background:rgba(255,255,255,.05);margin-bottom:.5rem}
.sb-av{width:36px;height:36px;background:linear-gradient(135deg,var(--primary-dark),#043D54);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:700;color:#fff}
.sb-info h4{font-size:.85rem;font-weight:600;color:#fff}
.sb-info p{font-size:.72rem;color:rgba(255,255,255,.4)}
.btn-logout{display:flex;align-items:center;gap:.6rem;padding:.7rem .9rem;border-radius:12px;color:rgba(255,255,255,.5);font-size:.88rem;font-weight:500;cursor:pointer;transition:all .2s;border:none;background:none;width:100%;font-family:'Manrope',sans-serif;text-decoration:none}
.btn-logout:hover{background:rgba(239,68,68,.15);color:#FCA5A5}
/* Main */
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column}
.topbar{background:#fff;border-bottom:1px solid var(--border);padding:0 2rem;height:70px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;box-shadow:var(--shadow-sm)}
.topbar-left{display:flex;align-items:center;gap:1rem}
.back-btn{display:flex;align-items:center;gap:.4rem;padding:.45rem 1rem;border-radius:8px;border:1.5px solid var(--border);background:white;font-family:inherit;font-size:.85rem;font-weight:600;color:var(--text-light);cursor:pointer;text-decoration:none;transition:all .2s}
.back-btn:hover{border-color:var(--primary);color:var(--primary)}
.page-title h1{font-family:'Sora',sans-serif;font-size:1.2rem;font-weight:700}
.page-title p{font-size:.8rem;color:var(--text-light)}
.admin-dropdown{position:relative}
.admin-pill{display:flex;align-items:center;gap:.5rem;padding:.4rem .9rem .4rem .45rem;border-radius:50px;border:2px solid #E2E8F0;background:white;cursor:pointer;font-family:'Manrope',sans-serif;font-weight:600;font-size:.88rem;color:var(--text-dark);transition:all .25s;white-space:nowrap}
.admin-pill:hover{border-color:var(--primary)}
.apill-av{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--primary-dark),#043D54);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;color:#fff}
.add-menu{position:absolute;right:0;top:calc(100% + .5rem);background:white;border:1px solid #E2E8F0;border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,.12);min-width:210px;z-index:200;opacity:0;transform:translateY(-8px) scale(.97);pointer-events:none;transition:all .2s}
.add-menu.open{opacity:1;transform:translateY(0) scale(1);pointer-events:all}
.adm-hdr2{display:flex;align-items:center;gap:.7rem;padding:.9rem 1rem .7rem}
.adm-av2{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--primary-dark),#043D54);display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:700;color:#fff}
.adm-n2{font-weight:700;font-size:.9rem;color:var(--text-dark)}
.adm-r2{font-size:.72rem;color:#94A3B8;text-transform:uppercase;letter-spacing:.5px}
.add-div{height:1px;background:#F1F5F9;margin:.25rem 0}
.add-item{display:flex;align-items:center;gap:.6rem;padding:.6rem 1rem;color:var(--text-dark);text-decoration:none;font-size:.86rem;font-weight:500;transition:background .15s;border-radius:8px;margin:.1rem .35rem}
.add-item:hover{background:#F1F5F9;color:var(--primary)}
.add-out{color:#EF4444!important}
.add-out:hover{background:#FEE2E2!important}
/* Content */
.content{padding:2rem;flex:1}
.user-hero{background:white;border-radius:20px;box-shadow:var(--shadow-sm);padding:2rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:2rem;border:1px solid var(--border)}
.user-big-avatar{width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:2.2rem;font-weight:700;color:#fff;flex-shrink:0;box-shadow:0 6px 20px rgba(10,126,164,.3)}
.user-hero-info h2{font-family:'Sora',sans-serif;font-size:1.6rem;font-weight:700;margin-bottom:.3rem}
.user-hero-info p{color:var(--text-light);font-size:.9rem;margin-bottom:.6rem}
.user-hero-badges{display:flex;gap:.6rem;flex-wrap:wrap}
.badge{display:inline-flex;align-items:center;gap:.3rem;padding:.3rem .85rem;border-radius:50px;font-size:.73rem;font-weight:700}
.badge-active{background:#ECFDF5;color:#065F46}
.badge-inactive{background:#FEF2F2;color:#991B1B}
.badge-date{background:#EFF6FF;color:#1D4ED8}
.user-hero-actions{margin-left:auto;display:flex;gap:.8rem;align-items:center}
.btn-act{padding:.55rem 1.2rem;border-radius:8px;font-family:inherit;font-size:.85rem;font-weight:600;cursor:pointer;border:none;transition:all .2s;text-decoration:none;display:inline-block;white-space:nowrap}
.btn-toggle-on{background:#FEF3C7;color:#92400E}
.btn-toggle-on:hover{background:#FDE68A}
.btn-toggle-off{background:#ECFDF5;color:#065F46}
.btn-toggle-off:hover{background:#A7F3D0}
.btn-back-profiles{background:#EFF6FF;color:#1D4ED8}
.btn-back-profiles:hover{background:#BFDBFE}
/* Tabs */
.tabs{display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap}
.tab-btn{padding:.55rem 1.2rem;border-radius:8px;border:2px solid var(--border);background:white;font-family:inherit;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .2s;color:var(--text-light)}
.tab-btn:hover{border-color:var(--primary);color:var(--primary)}
.tab-btn.active{background:var(--primary);border-color:var(--primary);color:white}
.tab-panel{display:none}
.tab-panel.active{display:block}
/* Cards */
.info-card{background:white;border-radius:16px;box-shadow:var(--shadow-sm);padding:1.8rem;border:1px solid var(--border);margin-bottom:1.5rem}
.card-title{font-family:'Sora',sans-serif;font-size:1rem;font-weight:700;margin-bottom:1.4rem;padding-bottom:.8rem;border-bottom:1px solid var(--border)}
.info-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem}
.info-item{background:#F8FAFC;padding:1rem;border-radius:12px}
.il{font-size:.72rem;color:var(--text-light);margin-bottom:.25rem;text-transform:uppercase;letter-spacing:.5px}
.iv{font-size:.9rem;font-weight:600;word-break:break-word}
/* Bookings table */
.mini-table{width:100%;border-collapse:collapse}
.mini-table th{text-align:left;font-size:.72rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.5px;padding:.7rem 1rem;background:#F8FAFC;border-bottom:1px solid var(--border)}
.mini-table td{padding:.8rem 1rem;font-size:.86rem;border-bottom:1px solid #F1F5F9;vertical-align:middle}
.mini-table tr:last-child td{border-bottom:none}
.mini-table tr:hover td{background:#FAFBFD}
.status-pill{display:inline-flex;align-items:center;padding:.25rem .7rem;border-radius:50px;font-size:.72rem;font-weight:700}
.sp-pending{background:#FEF3C7;color:#92400E}
.sp-confirmed{background:#ECFDF5;color:#065F46}
.sp-cancelled{background:#FEE2E2;color:#991B1B}
.sp-completed{background:#DBEAFE;color:#1E40AF}
.empty-mini{text-align:center;padding:2.5rem;color:var(--text-light);font-size:.88rem}
/* Flash */
.flash{padding:.75rem 1rem;border-radius:10px;margin-bottom:1.2rem;font-size:.88rem;font-weight:600;background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0}
@media(max-width:768px){
  .sidebar{display:none}
  .main{margin-left:0}
  .info-grid{grid-template-columns:1fr}
  .user-hero{flex-direction:column;text-align:center}
  .user-hero-actions{margin-left:0}
  .content{padding:1rem}
  .topbar{padding:0 1rem}
}
</style>
</head>
<body>

<!-- ── SIDEBAR ── -->
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="sidebar-logo">✈️</div>
    <div class="sidebar-brand-text"><h2>Jettransfer</h2><span>Admin Panel</span></div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-label">Main</div>
    <a href="index.php" class="nav-item"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>Dashboard</a>
    <div class="nav-label">Modules</div>
    <a href="destinations.php" class="nav-item"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Destinations</a>
    <a href="packages.php" class="nav-item"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>Packages</a>
    <a href="vehicles.php" class="nav-item"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>Vehicles</a>
    <a href="profiles.php" class="nav-item active"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Profiles</a>
    <a href="services.php" class="nav-item"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>Services &amp; Gallery</a>
    <a href="contact.php" class="nav-item"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Contact Messages</a>
    <div class="nav-label">Other</div>
    <a href="../index.php" class="nav-item" target="_blank"><svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>View Site</a>
  </nav>
  <div class="sidebar-footer">
    <div class="sb-admin"><div class="sb-av"><?= $admInit ?></div><div class="sb-info"><h4><?= $admName ?></h4><p><?= $admEmail ?></p></div></div>
    <a href="logout.php" class="btn-logout"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Logout</a>
  </div>
</aside>

<!-- ── MAIN ── -->
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <a href="profiles.php" class="back-btn">← Back to Profiles</a>
      <div class="page-title">
        <h1>User Detail</h1>
        <p><?= htmlspecialchars($fullName) ?> — #<?= $uid ?></p>
      </div>
    </div>
    <div class="admin-dropdown" id="adminDD">
      <button class="admin-pill" onclick="toggleAdminDD()">
        <div class="apill-av"><?= $admInit ?></div>
        <span><?= $admName ?></span>
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
      </button>
      <div class="add-menu" id="addMenu">
        <div class="adm-hdr2"><div class="adm-av2"><?= $admInit ?></div><div><div class="adm-n2"><?= $admName ?></div><div class="adm-r2">Administrator</div></div></div>
        <div class="add-div"></div>
        <a href="../index.php" target="_blank" class="add-item">🌐 View Website</a>
        <a href="index.php" class="add-item">📊 Dashboard</a>
        <a href="profiles.php" class="add-item">👥 User Management</a>
        <div class="add-div"></div>
        <a href="logout.php" class="add-item add-out">🚪 Logout</a>
      </div>
    </div>
  </header>

  <div class="content">
    <?php if (isset($_GET['ok'])): ?><div class="flash">✅ User status updated successfully.</div><?php endif; ?>

    <!-- User Hero Card -->
    <div class="user-hero">
      <div class="user-big-avatar"><?= $initials ?></div>
      <div class="user-hero-info">
        <h2><?= htmlspecialchars($fullName) ?></h2>
        <p><?= htmlspecialchars($u['email']) ?></p>
        <div class="user-hero-badges">
          <span class="badge <?= $u['is_active']?'badge-active':'badge-inactive' ?>"><?= $u['is_active']?'✅ Active':'🚫 Inactive' ?></span>
          <span class="badge badge-date">📅 Joined <?= date('d M Y',strtotime($u['created_at'])) ?></span>
          <?php if($u['last_login']): ?><span class="badge badge-date">🕐 Last login <?= date('d M Y',strtotime($u['last_login'])) ?></span><?php endif; ?>
          <span class="badge badge-date">📦 <?= count($bookings) ?> Booking<?= count($bookings)!==1?'s':'' ?></span>
        </div>
      </div>
      <div class="user-hero-actions">
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="toggle">
          <button type="submit" class="btn-act <?= $u['is_active']?'btn-toggle-on':'btn-toggle-off' ?>">
            <?= $u['is_active']?'Deactivate User':'Activate User' ?>
          </button>
        </form>
        <a href="profiles.php" class="btn-act btn-back-profiles">← All Users</a>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <button class="tab-btn active" onclick="showTab('personal',this)">👤 Personal Info</button>
      <button class="tab-btn" onclick="showTab('bookings',this)">📅 Bookings (<?= count($bookings) ?>)</button>
      <button class="tab-btn" onclick="showTab('messages',this)">📩 Messages (<?= count($msgs) ?>)</button>
    </div>

    <!-- PERSONAL INFO TAB -->
    <div id="tab-personal" class="tab-panel active">
      <div class="info-card">
        <div class="card-title">Personal Details</div>
        <div class="info-grid">
          <div class="info-item"><div class="il">First Name</div><div class="iv"><?= htmlspecialchars($u['first_name']??'—') ?></div></div>
          <div class="info-item"><div class="il">Last Name</div><div class="iv"><?= htmlspecialchars($u['last_name']??'—') ?></div></div>
          <div class="info-item"><div class="il">Email</div><div class="iv"><a href="mailto:<?= htmlspecialchars($u['email']) ?>" style="color:var(--primary);text-decoration:none"><?= htmlspecialchars($u['email']) ?></a></div></div>
          <div class="info-item"><div class="il">Phone</div><div class="iv"><?= htmlspecialchars($u['phone']??'—') ?></div></div>
          <div class="info-item"><div class="il">Date of Birth</div><div class="iv"><?= $u['dob']?date('d M Y',strtotime($u['dob'])):'—' ?></div></div>
          <div class="info-item"><div class="il">Nationality</div><div class="iv"><?= htmlspecialchars($u['nationality']??'—') ?></div></div>
        </div>
      </div>
      <div class="info-card">
        <div class="card-title">Address</div>
        <div class="info-grid">
          <div class="info-item" style="grid-column:1/-1"><div class="il">Street Address</div><div class="iv"><?= htmlspecialchars($u['address']??'—') ?></div></div>
          <div class="info-item"><div class="il">City</div><div class="iv"><?= htmlspecialchars($u['city']??'—') ?></div></div>
          <div class="info-item"><div class="il">Country</div><div class="iv"><?= htmlspecialchars($u['country']??'—') ?></div></div>
        </div>
      </div>
      <div class="info-card">
        <div class="card-title">Account Info</div>
        <div class="info-grid">
          <div class="info-item"><div class="il">User ID</div><div class="iv">#<?= $u['id'] ?></div></div>
          <div class="info-item"><div class="il">Status</div><div class="iv"><span class="badge <?= $u['is_active']?'badge-active':'badge-inactive' ?>"><?= $u['is_active']?'Active':'Inactive' ?></span></div></div>
          <div class="info-item"><div class="il">Registered</div><div class="iv"><?= date('d M Y, g:i A',strtotime($u['created_at'])) ?></div></div>
          <div class="info-item"><div class="il">Last Login</div><div class="iv"><?= $u['last_login']?date('d M Y, g:i A',strtotime($u['last_login'])):'Never' ?></div></div>
        </div>
      </div>
    </div>

    <!-- BOOKINGS TAB -->
    <div id="tab-bookings" class="tab-panel">
      <div class="info-card">
        <div class="card-title">Booking History (<?= count($bookings) ?>)</div>
        <?php if (empty($bookings)): ?>
          <div class="empty-mini">📅 No bookings found for this user.</div>
        <?php else: ?>
        <div style="overflow-x:auto">
          <table class="mini-table">
            <thead><tr><th>#</th><th>Package</th><th>Travel Date</th><th>Guests</th><th>Price</th><th>Status</th><th>Booked On</th></tr></thead>
            <tbody>
              <?php foreach ($bookings as $b): ?>
              <tr>
                <td style="color:var(--text-light);font-size:.78rem">#<?= $b['id'] ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($b['package_name']) ?></td>
                <td><?= date('d M Y',strtotime($b['travel_date'])) ?></td>
                <td><?= $b['guests'] ?> guest<?= $b['guests']>1?'s':'' ?></td>
                <td style="font-weight:700;color:var(--primary)"><?= htmlspecialchars($b['price']) ?></td>
                <td><span class="status-pill sp-<?= strtolower($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
                <td style="color:var(--text-light);font-size:.8rem"><?= date('d M Y',strtotime($b['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- CONTACT MESSAGES TAB -->
    <div id="tab-messages" class="tab-panel">
      <div class="info-card">
        <div class="card-title">Contact Messages (<?= count($msgs) ?>)</div>
        <?php if (empty($msgs)): ?>
          <div class="empty-mini">📩 No contact messages from this user.</div>
        <?php else: ?>
        <div style="overflow-x:auto">
          <table class="mini-table">
            <thead><tr><th>#</th><th>Topic</th><th>Message</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
              <?php foreach ($msgs as $m): ?>
              <tr>
                <td style="color:var(--text-light);font-size:.78rem">#<?= $m['id'] ?></td>
                <td><span style="background:var(--primary-light,#E0F2FA);color:var(--primary);padding:.2rem .6rem;border-radius:6px;font-size:.73rem;font-weight:600"><?= htmlspecialchars($m['topic']??'General') ?></span></td>
                <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.83rem;color:var(--text-light)"><?= htmlspecialchars($m['message']) ?></td>
                <td><span class="status-pill sp-<?= strtolower(str_replace(' ','-',$m['status'])) ?>"><?= htmlspecialchars($m['status']) ?></span></td>
                <td style="color:var(--text-light);font-size:.8rem"><?= date('d M Y',strtotime($m['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<script>
function showTab(name, btn) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-'+name).classList.add('active');
  btn.classList.add('active');
}
function toggleAdminDD() { document.getElementById('addMenu').classList.toggle('open'); }
document.addEventListener('click', e => {
  const w = document.getElementById('adminDD');
  if (w && !w.contains(e.target)) document.getElementById('addMenu').classList.remove('open');
});
</script>
</body>
</html>