<?php
session_start();
if (!isset($_SESSION['jt_admin'])) { header('Location: login.php'); exit; }

$conn = new mysqli('localhost', 'root', '', 'jettransfer');
if ($conn->connect_error) { die('<p style="color:red;padding:1rem">DB Error: '.$conn->connect_error.'</p>'); }
$conn->set_charset('utf8mb4');

$message = ''; $msgType = 'success';

// ── CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {      //delete
        $id = (int)$_POST['id'];
        $message = $conn->query("DELETE FROM destinations WHERE id=$id")
            ? '✅ Destination deleted successfully!'
            : '❌ '.$conn->error;
        if (str_starts_with($message,'❌')) $msgType = 'error';

    } elseif (in_array($action, ['add','edit'])) {
        $id  = (int)($_POST['id'] ?? 0);
        $n   = $conn->real_escape_string(trim($_POST['name']        ?? ''));
        $sl  = $conn->real_escape_string(trim($_POST['slug']        ?? ''));
        $di  = $conn->real_escape_string(trim($_POST['district']    ?? ''));
        $pr  = $conn->real_escape_string(trim($_POST['province']    ?? ''));
        $bl  = $conn->real_escape_string(trim($_POST['badge_label'] ?? ''));
        $sd  = $conn->real_escape_string(trim($_POST['short_desc']  ?? ''));
        $ip  = $conn->real_escape_string(trim($_POST['image_path']  ?? ''));
        $dp  = $conn->real_escape_string(trim($_POST['detail_page'] ?? ''));
        $cat = $conn->real_escape_string($_POST['category']         ?? 'Other');
        $ia  = isset($_POST['is_active']) ? 1 : 0;

        if ($action === 'add') {    //create
            $sql = "INSERT INTO destinations(name,slug,district,province,badge_label,short_desc,image_path,detail_page,category,is_active)
                    VALUES('$n','$sl','$di','$pr','$bl','$sd','$ip','$dp','$cat',$ia)";
            $message = $conn->query($sql) ? '✅ Destination added!' : '❌ '.$conn->error;

        } else {    //update
            $sql = "UPDATE destinations SET name='$n',slug='$sl',district='$di',province='$pr',
                    badge_label='$bl',short_desc='$sd',image_path='$ip',detail_page='$dp',
                    category='$cat',is_active=$ia WHERE id=$id";
            $message = $conn->query($sql) ? '✅ Destination updated!' : '❌ '.$conn->error;
        }
        if (str_starts_with($message,'❌')) $msgType = 'error';
    }
}

// Edit prefill - load old data (read)
$editRow = null;
if (isset($_GET['edit'])) {
    $r = $conn->query("SELECT * FROM destinations WHERE id=".(int)$_GET['edit']." LIMIT 1");
    if ($r) $editRow = $r->fetch_assoc();
}

// Fetch all - get data for table display (read)
$all = [];
$r = $conn->query("SELECT * FROM destinations ORDER BY name ASC");
if ($r) while ($row = $r->fetch_assoc()) $all[] = $row;
$conn->close();

$total  = count($all);
$active = count(array_filter($all, fn($d) => $d['is_active']));
$categories = ['Beach','Heritage','Hills','Wildlife','Culture','City','Other'];
$currentPage = 'destinations.php';
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Destinations – Jettransfer Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--primary:#0A7EA4;--primary-dark:#065A7A;--primary-light:#E0F2FA;--secondary:#F59E0B;--accent:#10B981;--text-dark:#0F172A;--text-light:#94A3B8;--bg:#F1F5F9;--sidebar-bg:#0F172A;--border:#E2E8F0;--shadow-sm:0 1px 3px rgba(0,0,0,.06);--shadow-md:0 4px 16px rgba(0,0,0,.08);--sidebar-w:260px}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Manrope',sans-serif;background:var(--bg);color:var(--text-dark);min-height:100vh;display:flex}

/* ── SIDEBAR (exact same as index.php) ── */
.sidebar{width:var(--sidebar-w);background:var(--sidebar-bg);min-height:100vh;position:fixed;left:0;top:0;bottom:0;display:flex;flex-direction:column;z-index:100;transition:transform .3s ease}
.sidebar-brand{padding:1.8rem 1.5rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.07);display:flex;align-items:center;gap:.8rem}
.sidebar-logo{width:42px;height:42px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0}
.sidebar-brand-text h2{font-family:'Sora',sans-serif;font-size:1rem;font-weight:800;color:#fff}
.sidebar-brand-text span{font-size:.72rem;color:rgba(255,255,255,.4);letter-spacing:1.5px;text-transform:uppercase}
.sidebar-nav{flex:1;padding:1.2rem .8rem;overflow-y:auto}
.nav-label{font-size:.68rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.25);padding:.5rem .8rem;margin-top:.8rem;margin-bottom:.3rem}
.nav-item{display:flex;align-items:center;gap:.75rem;padding:.75rem .9rem;border-radius:12px;color:rgba(255,255,255,.55);text-decoration:none;font-size:.9rem;font-weight:500;transition:all .2s ease;margin-bottom:.2rem}
.nav-item:hover{background:rgba(255,255,255,.07);color:rgba(255,255,255,.9)}
.nav-item.active{background:linear-gradient(135deg,rgba(10,126,164,.35),rgba(16,185,129,.2));color:#fff;box-shadow:inset 0 0 0 1px rgba(10,126,164,.4)}
.nav-icon{width:20px;height:20px;flex-shrink:0}
.sidebar-footer{padding:1rem .8rem 1.5rem;border-top:1px solid rgba(255,255,255,.07)}
.admin-profile{display:flex;align-items:center;gap:.75rem;padding:.75rem .9rem;border-radius:12px;background:rgba(255,255,255,.05);margin-bottom:.5rem}
.admin-avatar{width:36px;height:36px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:700;color:#fff;flex-shrink:0}
.admin-info h4{font-size:.85rem;font-weight:600;color:#fff}
.admin-info p{font-size:.72rem;color:rgba(255,255,255,.4)}
.btn-logout{display:flex;align-items:center;gap:.6rem;padding:.7rem .9rem;border-radius:12px;color:rgba(255,255,255,.5);font-size:.88rem;font-weight:500;cursor:pointer;transition:all .2s;border:none;background:none;width:100%;font-family:'Manrope',sans-serif;text-decoration:none}
.btn-logout:hover{background:rgba(239,68,68,.15);color:#FCA5A5}

/* ── MAIN ── */
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column}
.topbar{background:#fff;border-bottom:1px solid var(--border);padding:0 2rem;height:70px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;box-shadow:var(--shadow-sm)}
.topbar-left{display:flex;align-items:center;gap:1rem}
.hamburger{display:none;background:none;border:none;cursor:pointer;padding:6px;color:var(--text-dark)}
.page-title h1{font-family:'Sora',sans-serif;font-size:1.2rem;font-weight:700}
.page-title p{font-size:.8rem;color:var(--text-light)}
.topbar-avatar{width:40px;height:40px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.9rem}
.content{padding:2rem;flex:1}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99}
.sidebar-overlay.show{display:block}

/* ── PAGE CONTENT ── */
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.page-header h2{font-family:'Sora',sans-serif;font-size:1.2rem;font-weight:700;color:var(--text-dark)}
.stat-chips{display:flex;gap:.6rem;flex-wrap:wrap}
.chip{background:#fff;border:1px solid var(--border);border-radius:50px;padding:.35rem .9rem;font-size:.78rem;font-weight:600;color:var(--text-dark)}
.chip .val{font-weight:800;margin-right:.2rem}
.chip .val.blue{color:var(--primary)}
.chip .val.green{color:var(--accent)}
.chip .val.red{color:#EF4444}

/* ── SUCCESS/ERROR MSG ── */
.alert{padding:.75rem 1rem;border-radius:10px;margin-bottom:1.2rem;font-size:.86rem;font-weight:600;display:flex;align-items:center;gap:.5rem}
.alert.success{background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0}
.alert.error{background:#FEE2E2;color:#991B1B;border:1px solid #FECACA}

/* ── LAYOUT ── */
.dest-layout{display:grid;grid-template-columns:360px 1fr;gap:1.5rem;align-items:start}
@media(max-width:1100px){.dest-layout{grid-template-columns:1fr}}

/* ── FORM CARD ── */
.card{background:#fff;border-radius:18px;padding:1.6rem;box-shadow:var(--shadow-sm);border:1px solid var(--border)}
.card-title{font-family:'Sora',sans-serif;font-size:1rem;font-weight:700;color:var(--text-dark);margin-bottom:1.2rem;display:flex;align-items:center;gap:.4rem}
.fg{margin-bottom:.9rem}
.fg label{display:block;font-size:.75rem;font-weight:700;color:#475569;margin-bottom:.35rem;text-transform:uppercase;letter-spacing:.5px}
.fg input[type=text],.fg select,.fg textarea{width:100%;padding:.6rem .9rem;border:1.5px solid var(--border);border-radius:9px;font-family:'Manrope',sans-serif;font-size:.88rem;color:var(--text-dark);outline:none;transition:border-color .25s;background:#fff}
.fg input[type=text]:focus,.fg select:focus,.fg textarea:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(10,126,164,.08)}
.fg textarea{resize:vertical;min-height:62px}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:.8rem}
.chk-label{display:flex;align-items:center;gap:.45rem;font-size:.87rem;font-weight:600;cursor:pointer;color:var(--text-dark)}
.chk-label input{width:auto;cursor:pointer;accent-color:var(--primary)}
.btn-save{width:100%;padding:.7rem;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border:none;border-radius:10px;font-family:'Manrope',sans-serif;font-size:.9rem;font-weight:700;cursor:pointer;margin-top:.4rem;transition:all .25s}
.btn-save:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 4px 14px rgba(10,126,164,.3)}
.btn-cancel-edit{display:block;text-align:center;margin-top:.6rem;color:var(--text-light);font-size:.8rem;text-decoration:none;padding:.35rem;border-radius:8px;transition:background .2s}
.btn-cancel-edit:hover{background:var(--bg);color:var(--text-dark)}

/* ── TABLE CARD ── */
.tbl-card{background:#fff;border-radius:18px;box-shadow:var(--shadow-sm);border:1px solid var(--border);overflow:hidden}
.tbl-top{display:flex;align-items:center;justify-content:space-between;padding:1.1rem 1.4rem;border-bottom:1px solid var(--border)}
.tbl-top h3{font-family:'Sora',sans-serif;font-size:.95rem;font-weight:700}
.tbl-search{padding:.7rem 1.4rem;border-bottom:1px solid var(--border)}
.tbl-search input{width:100%;padding:.52rem .9rem;border:1.5px solid var(--border);border-radius:50px;font-family:'Manrope',sans-serif;font-size:.86rem;outline:none;transition:border-color .25s}
.tbl-search input:focus{border-color:var(--primary)}
.tbl-filters{display:flex;gap:.45rem;padding:.65rem 1.4rem;border-bottom:1px solid var(--border);flex-wrap:wrap}
.fb{padding:.28rem .78rem;border-radius:50px;border:1.5px solid var(--border);background:#fff;color:#64748B;font-family:'Manrope',sans-serif;font-size:.75rem;font-weight:600;cursor:pointer;transition:all .2s}
.fb:hover,.fb.on{border-color:var(--primary);background:var(--primary);color:#fff}
.tbl-wrap{overflow-x:auto}
.tbl{width:100%;border-collapse:collapse;font-size:.83rem}
.tbl th{text-align:left;font-size:.69rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.7px;padding:.78rem 1.2rem;background:#F8FAFC;border-bottom:1px solid var(--border);white-space:nowrap}
.tbl td{padding:.85rem 1.2rem;border-bottom:1px solid #F1F5F9;vertical-align:middle}
.tbl tbody tr:last-child td{border-bottom:none}
.tbl tbody tr:hover td{background:#FAFBFD}
.thumb{width:52px;height:38px;object-fit:cover;border-radius:7px;border:1px solid var(--border)}
.badge{display:inline-block;padding:.18rem .6rem;border-radius:20px;font-size:.69rem;font-weight:700}
.badge.act{background:#D1FAE5;color:#065F46}
.badge.hid{background:#FEE2E2;color:#991B1B}
.badge.cat{background:var(--primary-light);color:var(--primary-dark)}
.al{color:var(--primary);font-weight:600;font-size:.8rem;text-decoration:none;cursor:pointer;background:none;border:none;font-family:'Manrope',sans-serif;padding:.24rem .55rem;border-radius:6px;transition:background .15s;display:inline-flex;align-items:center;gap:.2rem}
.al:hover{background:var(--primary-light)}
.al.del{color:#EF4444}
.al.del:hover{background:#FEE2E2}
.empty-row td{text-align:center;padding:2.5rem;color:var(--text-light);font-size:.88rem}

@media(max-width:768px){.sidebar{transform:translateX(-100%)}.sidebar.open{transform:translateX(0)}.main{margin-left:0}.hamburger{display:flex}}
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
        <a href="destinations.php" class="nav-item active">
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
        <div class="nav-label">Other</div>
        <a href="../index.php" class="nav-item" target="_blank">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            View Site
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-profile">
            <div class="admin-avatar">A</div>
            <div class="admin-info"><h4>Admin</h4><p>admin@jettransfer.com</p></div>
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
            <div class="page-title">
                <h1>Destinations</h1>
                <p>Jettransfer Admin Panel</p>
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-avatar">A</div>
        </div>
    </header>

    <div class="content">

        <!-- Page header -->
        <div class="page-header">
            <h2>📍 Destinations Manager</h2>
            <div class="stat-chips">
                <div class="chip"><span class="val blue"><?= $total ?></span>Total</div>
                <div class="chip"><span class="val green"><?= $active ?></span>Active</div>
                <div class="chip"><span class="val red"><?= $total - $active ?></span>Hidden</div>
            </div>
        </div>

        <!-- Alert message -->
        <?php if ($message): ?>
        <div class="alert <?= $msgType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- Layout: Form + Table -->
        <div class="dest-layout">

            <!-- ── ADD / EDIT FORM ── -->
            <div class="card">
                <div class="card-title">
                    <?= $editRow ? '✏️ Edit Destination' : '➕ Add New Destination' ?>
                </div>
                <form method="POST" action="destinations.php<?= $editRow ? '?edit='.$editRow['id'] : '' ?>">
                    <input type="hidden" name="action" value="<?= $editRow ? 'edit' : 'add' ?>">
                    <?php if ($editRow): ?>
                        <input type="hidden" name="id" value="<?= $editRow['id'] ?>">
                    <?php endif; ?>

                    <div class="fg">
                        <label>Name *</label>
                        <input type="text" name="name" id="dName" required
                               value="<?= htmlspecialchars($editRow['name'] ?? '') ?>"
                               placeholder="e.g. Ella">
                    </div>
                    <div class="fg">
                        <label>Slug *</label>
                        <input type="text" name="slug" id="dSlug" required
                               value="<?= htmlspecialchars($editRow['slug'] ?? '') ?>"
                               placeholder="e.g. ella">
                    </div>
                    <div class="row2">
                        <div class="fg">
                            <label>District *</label>
                            <input type="text" name="district" required
                                   value="<?= htmlspecialchars($editRow['district'] ?? '') ?>"
                                   placeholder="Badulla District">
                        </div>
                        <div class="fg">
                            <label>Province *</label>
                            <input type="text" name="province" required
                                   value="<?= htmlspecialchars($editRow['province'] ?? '') ?>"
                                   placeholder="Uva Province">
                        </div>
                    </div>
                    <div class="row2">
                        <div class="fg">
                            <label>Badge Label</label>
                            <input type="text" name="badge_label"
                                   value="<?= htmlspecialchars($editRow['badge_label'] ?? '') ?>"
                                   placeholder="e.g. Scenic">
                        </div>
                        <div class="fg">
                            <label>Category *</label>
                            <select name="category" required>
                                <?php foreach ($categories as $c): ?>
                                <option value="<?= $c ?>" <?= (isset($editRow['category']) && $editRow['category'] === $c) ? 'selected' : '' ?>><?= $c ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="fg">
                        <label>Short Description *</label>
                        <textarea name="short_desc" required
                                  placeholder="One-line description shown on card…"><?= htmlspecialchars($editRow['short_desc'] ?? '') ?></textarea>
                    </div>
                    <div class="row2">
                        <div class="fg">
                            <label>Image Path</label>
                            <input type="text" name="image_path"
                                   value="<?= htmlspecialchars($editRow['image_path'] ?? '') ?>"
                                   placeholder="images/ella.webp">
                        </div>
                        <div class="fg">
                            <label>Detail Page</label>
                            <input type="text" name="detail_page"
                                   value="<?= htmlspecialchars($editRow['detail_page'] ?? '') ?>"
                                   placeholder="ella.html">
                        </div>
                    </div>
                    <div class="fg">
                        <label class="chk-label">
                            <input type="checkbox" name="is_active" value="1"
                                   <?= (!isset($editRow) || $editRow['is_active']) ? 'checked' : '' ?>>
                            Active (visible on website)
                        </label>
                    </div>
                    <button type="submit" class="btn-save">
                        <?= $editRow ? '💾 Save Changes' : '➕ Add Destination' ?>
                    </button>
                    <?php if ($editRow): ?>
                    <a href="destinations.php" class="btn-cancel-edit">✕ Cancel editing</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- ── TABLE ── -->
            <div class="tbl-card">
                <div class="tbl-top">
                    <h3>All Destinations (<?= $total ?>)</h3>
                </div>
                <div class="tbl-search">
                    <input type="text" id="srch"
                           placeholder="🔍  Search by name, district, category…"
                           oninput="doFilter()">
                </div>
                <div class="tbl-filters">
                    <button class="fb on" data-cat="all" onclick="setCat(this)">All</button>
                    <?php foreach ($categories as $c): ?>
                    <button class="fb" data-cat="<?= $c ?>" onclick="setCat(this)"><?= $c ?></button>
                    <?php endforeach; ?>
                </div>
                <div class="tbl-wrap">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>District</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tblBody">
                        <?php if (empty($all)): ?>
                        <tr class="empty-row"><td colspan="7">No destinations yet. Add one using the form!</td></tr>
                        <?php else: ?>
                        <?php foreach ($all as $d): ?>
                        <tr data-cat="<?= htmlspecialchars($d['category']) ?>"
                            data-srch="<?= htmlspecialchars(strtolower($d['name'].' '.$d['district'].' '.$d['category'].' '.($d['badge_label'] ?? ''))) ?>">
                            <td style="color:var(--text-light);font-size:.76rem"><?= $d['id'] ?></td>
                            <td>
                                <?php if ($d['image_path']): ?>
                                <img src="../<?= htmlspecialchars($d['image_path']) ?>"
                                     class="thumb"
                                     onerror="this.style.display='none'">
                                <?php else: ?>
                                <span style="color:var(--text-light)">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:600"><?= htmlspecialchars($d['name']) ?></td>
                            <td><span class="badge cat"><?= htmlspecialchars($d['category']) ?></span></td>
                            <td style="color:#64748B;font-size:.8rem"><?= htmlspecialchars($d['district']) ?></td>
                            <td>
                                <span class="badge <?= $d['is_active'] ? 'act' : 'hid' ?>">
                                    <?= $d['is_active'] ? 'Active' : 'Hidden' ?>
                                </span>
                            </td>
                            <td>
                                <a href="destinations.php?edit=<?= $d['id'] ?>" class="al">✏️ Edit</a>
                                <form method="POST" action="destinations.php"
                                      style="display:inline"
                                      onsubmit="return confirm('Delete \'<?= htmlspecialchars(addslashes($d['name'])) ?>\'? This cannot be undone.')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                    <button type="submit" class="al del">🗑 Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// ── Sidebar toggle
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
document.getElementById('sidebarOverlay').addEventListener('click', () => {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
});

// ── Auto-generate slug from name
(function(){
    const n = document.getElementById('dName');
    const s = document.getElementById('dSlug');
    if (!n || !s) return;
    n.addEventListener('input', function() {
        if (!s.dataset.manual) {
            s.value = this.value.toLowerCase().trim()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-');
        }
    });
    s.addEventListener('input', function() { this.dataset.manual = '1'; });
})();

// ── Table filter
let activeCat = 'all';
function doFilter() {
    const q = (document.getElementById('srch').value || '').toLowerCase().trim();
    let visible = 0;
    document.querySelectorAll('#tblBody tr[data-cat]').forEach(row => {
        const matchCat = activeCat === 'all' || row.dataset.cat === activeCat;
        const matchQ   = !q || (row.dataset.srch || '').includes(q);
        row.style.display = (matchCat && matchQ) ? '' : 'none';
        if (matchCat && matchQ) visible++;
    });
    // Show/hide empty message
    let emptyRow = document.getElementById('noResultsRow');
    if (visible === 0 && !document.querySelector('#tblBody tr.empty-row')) {
        if (!emptyRow) {
            emptyRow = document.createElement('tr');
            emptyRow.id = 'noResultsRow';
            emptyRow.innerHTML = '<td colspan="7" style="text-align:center;padding:2rem;color:#94A3B8">No destinations match your search.</td>';
            document.getElementById('tblBody').appendChild(emptyRow);
        }
        emptyRow.style.display = '';
    } else if (emptyRow) {
        emptyRow.style.display = 'none';
    }
}

function setCat(btn) {
    document.querySelectorAll('.fb').forEach(b => b.classList.remove('on'));
    btn.classList.add('on');
    activeCat = btn.dataset.cat;
    doFilter();
}
</script>
</body>
</html>