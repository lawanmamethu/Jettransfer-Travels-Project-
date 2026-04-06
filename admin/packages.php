<?php
session_start();
if (!isset($_SESSION['jt_admin'])) { header('Location: login.php'); exit; }

require_once '../db.php';   // MySQLi $conn — same as all other admin pages

// Fetch admin info for topbar
$ar  = $conn->query("SELECT name,email FROM admins WHERE id=1 LIMIT 1");
$adm = $ar ? $ar->fetch_assoc() : ['name'=>'Admin','email'=>'admin@jettransfer.com'];
$admName  = htmlspecialchars($adm['name']);
$admEmail = htmlspecialchars($adm['email']);
$admInit  = strtoupper(substr($adm['name'],0,1));

function clean($v){ return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8'); }

$flash = '';

// Check if is_active column exists, if not add it
$check_column = $conn->query("SHOW COLUMNS FROM packages LIKE 'is_active'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE packages ADD COLUMN is_active TINYINT(1) DEFAULT 1");
}

// ── HANDLE POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    if ($act === 'add') {
        $name   = $conn->real_escape_string(clean($_POST['name']        ?? ''));
        $desc   = $conn->real_escape_string(clean($_POST['description'] ?? ''));
        $dur    = (int)($_POST['duration']     ?? 0);
        $price  = (float)($_POST['price']      ?? 0);
        $tier   = $conn->real_escape_string($_POST['price_tier']   ?? 'mid');
        $locs   = $conn->real_escape_string(clean($_POST['locations']   ?? ''));
        $veh    = $conn->real_escape_string(clean($_POST['vehicle']     ?? ''));
        $hotel  = $conn->real_escape_string(clean($_POST['hotel_rating']?? '★★★'));
        $grp    = $conn->real_escape_string(clean($_POST['group_size']  ?? ''));
        $img    = $conn->real_escape_string(clean($_POST['image']       ?? ''));
        if (!in_array($tier,['budget','mid','luxury'])) $tier='mid';
        if ($name && $dur>0 && $price>0) {
            $conn->query("INSERT INTO packages(name,description,duration,price,price_tier,locations,vehicle,hotel_rating,group_size,image,is_active)
                          VALUES('$name','$desc',$dur,$price,'$tier','$locs','$veh','$hotel','$grp','$img',1)");
            $conn->close(); header('Location: packages.php?ok=added'); exit;
        }
        $flash = '❌ Please fill all required fields correctly.';
    }

    if ($act === 'edit') {
        $id    = (int)($_POST['id'] ?? 0);
        $name  = $conn->real_escape_string(clean($_POST['name']        ?? ''));
        $desc  = $conn->real_escape_string(clean($_POST['description'] ?? ''));
        $dur   = (int)($_POST['duration']     ?? 0);
        $price = (float)($_POST['price']      ?? 0);
        $tier  = $conn->real_escape_string($_POST['price_tier']   ?? 'mid');
        $locs  = $conn->real_escape_string(clean($_POST['locations']   ?? ''));
        $veh   = $conn->real_escape_string(clean($_POST['vehicle']     ?? ''));
        $hotel = $conn->real_escape_string(clean($_POST['hotel_rating']?? '★★★'));
        $grp   = $conn->real_escape_string(clean($_POST['group_size']  ?? ''));
        $img   = $conn->real_escape_string(clean($_POST['image']       ?? ''));
        if (!in_array($tier,['budget','mid','luxury'])) $tier='mid';
        if ($id>0 && $name && $dur>0 && $price>0) {
            $conn->query("UPDATE packages SET name='$name',description='$desc',duration=$dur,price=$price,price_tier='$tier',locations='$locs',vehicle='$veh',hotel_rating='$hotel',group_size='$grp',image='$img' WHERE id=$id");
            $conn->close(); header('Location: packages.php?ok=updated'); exit;
        }
        $flash = '❌ Please fill all required fields correctly.';
    }

    if ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) $conn->query("DELETE FROM packages WHERE id=$id");
        $conn->close(); header('Location: packages.php?ok=deleted'); exit;
    }

    if ($act === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $r  = $conn->query("SELECT is_active FROM packages WHERE id=$id LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) {
            $new = $row['is_active'] ? 0 : 1;
            $conn->query("UPDATE packages SET is_active=$new WHERE id=$id");
        }
        $conn->close(); header('Location: packages.php'); exit;
    }
}

// ── STATS ─────────────────────────────────────────────────────
$totalPkgs  = $conn->query("SELECT COUNT(*) FROM packages")->fetch_row()[0] ?? 0;
$budgetPkgs = $conn->query("SELECT COUNT(*) FROM packages WHERE price_tier='budget'")->fetch_row()[0] ?? 0;
$midPkgs    = $conn->query("SELECT COUNT(*) FROM packages WHERE price_tier='mid'")->fetch_row()[0] ?? 0;
$luxPkgs    = $conn->query("SELECT COUNT(*) FROM packages WHERE price_tier='luxury'")->fetch_row()[0] ?? 0;

// ── SEARCH & FILTER ───────────────────────────────────────────
$search = clean($_GET['search']     ?? '');
$tierF  = clean($_GET['price_tier'] ?? '');
$durF   = (int)($_GET['duration']   ?? 0);
$page   = max(1,(int)($_GET['page'] ?? 1));
$limit  = 15; $offset = ($page-1)*$limit;

$where = '1';
if ($search !== '') {
    $s = $conn->real_escape_string($search);
    $where .= " AND (name LIKE '%$s%' OR locations LIKE '%$s%')";
}
if (in_array($tierF,['budget','mid','luxury'])) {
    $where .= " AND price_tier='$tierF'";
}
if ($durF > 0) $where .= " AND duration=$durF";

$total  = $conn->query("SELECT COUNT(*) FROM packages WHERE $where")->fetch_row()[0] ?? 0;
$pkgRes = $conn->query("SELECT * FROM packages WHERE $where ORDER BY id ASC LIMIT $limit OFFSET $offset");
$packages = [];
if ($pkgRes) while ($r = $pkgRes->fetch_assoc()) $packages[] = $r;
$totalPages = (int)ceil($total / $limit);

// Edit package fetch
$editPkg = null;
if (!empty($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $er  = $conn->query("SELECT * FROM packages WHERE id=$eid LIMIT 1");
    if ($er) $editPkg = $er->fetch_assoc();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Packages – Jettransfer Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--primary:#0A7EA4;--primary-dark:#065A7A;--primary-light:#E0F2FA;--secondary:#F59E0B;--accent:#10B981;--danger:#EF4444;--text-dark:#0F172A;--text-light:#94A3B8;--bg:#F1F5F9;--sidebar-bg:#0F172A;--border:#E2E8F0;--shadow-sm:0 1px 3px rgba(0,0,0,.06);--shadow-md:0 4px 16px rgba(0,0,0,.08);--shadow-lg:0 10px 40px rgba(0,0,0,.15);--sidebar-w:260px}
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
/* Main */
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column}
.topbar{background:#fff;border-bottom:1px solid var(--border);padding:0 2rem;height:70px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;box-shadow:var(--shadow-sm)}
.topbar-left{display:flex;align-items:center;gap:1rem}
.hamburger{display:none;background:none;border:none;cursor:pointer;padding:6px;color:var(--text-dark)}
.page-title h1{font-family:'Sora',sans-serif;font-size:1.2rem;font-weight:700}
.page-title p{font-size:.8rem;color:var(--text-light)}
/* Admin dropdown */
.admin-dropdown{position:relative}
.admin-pill{display:flex;align-items:center;gap:.5rem;padding:.4rem .9rem .4rem .45rem;border-radius:50px;border:2px solid #E2E8F0;background:white;cursor:pointer;font-family:'Manrope',sans-serif;font-weight:600;font-size:.88rem;color:var(--text-dark);transition:all .25s;white-space:nowrap}
.admin-pill:hover{border-color:var(--primary);box-shadow:0 2px 12px rgba(10,126,164,.15)}
.admin-pill-av{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--primary-dark),#043D54);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;color:#fff;flex-shrink:0}
.admin-dd{position:absolute;right:0;top:calc(100% + .6rem);background:white;border:1px solid #E2E8F0;border-radius:18px;box-shadow:0 10px 40px rgba(0,0,0,.14);min-width:230px;z-index:200;opacity:0;transform:translateY(-8px) scale(.97);pointer-events:none;transition:all .22s}
.admin-dd.open{opacity:1;transform:translateY(0) scale(1);pointer-events:all}
.adm-hdr{display:flex;align-items:center;gap:.75rem;padding:1rem 1.1rem .8rem}
.adm-av{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--primary-dark),#043D54);display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:700;color:#fff}
.adm-n{font-weight:700;font-size:.9rem;color:var(--text-dark);margin-bottom:.1rem}
.adm-r{font-size:.72rem;color:#94A3B8;text-transform:uppercase;letter-spacing:.5px}
.adm-div{height:1px;background:#F1F5F9;margin:.3rem 0}
.adm-itm{display:flex;align-items:center;gap:.6rem;padding:.65rem 1rem;color:var(--text-dark);text-decoration:none;font-size:.88rem;font-weight:500;transition:background .15s;border-radius:10px;margin:.1rem .4rem}
.adm-itm:hover{background:#F1F5F9;color:var(--primary)}
.adm-out{color:#EF4444!important}
.adm-out:hover{background:#FEE2E2!important}
/* Content */
.content{padding:2rem;flex:1}
/* Stats */
.stats-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:1.2rem;margin-bottom:2rem}
.stat-card{background:white;border-radius:16px;padding:1.4rem;box-shadow:var(--shadow-sm);display:flex;align-items:center;gap:1rem;border:1px solid var(--border)}
.stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
.ic-blue{background:rgba(10,126,164,.12)}
.ic-green{background:rgba(16,185,129,.12)}
.ic-yellow{background:rgba(245,158,11,.12)}
.ic-purple{background:rgba(124,58,237,.12)}
.stat-info h3{font-family:'Sora',sans-serif;font-size:1.6rem;font-weight:800;line-height:1}
.stat-info p{font-size:.82rem;color:var(--text-light);margin-top:.2rem}
/* Toolbar */
.toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;gap:1rem;flex-wrap:wrap;background:white;border-radius:16px;padding:1.2rem 1.5rem;box-shadow:var(--shadow-sm);border:1px solid var(--border)}
.tb-left{display:flex;align-items:center;gap:.8rem;flex:1;flex-wrap:wrap}
.tb-right{display:flex;gap:.8rem;align-items:center}
.tb-search{position:relative;min-width:200px;flex:1;max-width:280px}
.tb-search input{width:100%;padding:.65rem 1rem .65rem 2.5rem;border:2px solid var(--border);border-radius:50px;font-family:inherit;font-size:.9rem;outline:none;transition:border-color .3s}
.tb-search input:focus{border-color:var(--primary)}
.tb-search .si{position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--text-light);font-size:.95rem;pointer-events:none}
.filter-sel{padding:.65rem 1.1rem;border:2px solid var(--border);border-radius:50px;font-family:inherit;font-size:.88rem;color:var(--text-dark);outline:none;cursor:pointer;background:white;transition:border-color .3s}
.filter-sel:focus{border-color:var(--primary)}
.btn-search{padding:.65rem 1.3rem;background:var(--primary);color:white;border:none;border-radius:50px;font-family:inherit;font-size:.88rem;font-weight:700;cursor:pointer;transition:all .25s}
.btn-search:hover{background:var(--primary-dark)}
.btn-clear{padding:.65rem 1.1rem;background:#F1F5F9;color:var(--text-light);border:none;border-radius:50px;font-family:inherit;font-size:.88rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block}
.btn-add{display:inline-flex;align-items:center;gap:.4rem;padding:.65rem 1.4rem;background:var(--primary);color:white;border:none;border-radius:50px;font-family:inherit;font-size:.88rem;font-weight:700;cursor:pointer;transition:all .25s;box-shadow:0 4px 12px rgba(10,126,164,.3)}
.btn-add:hover{background:var(--primary-dark);transform:translateY(-2px)}
/* Table */
.table-card{background:white;border-radius:18px;box-shadow:var(--shadow-sm);overflow:hidden;border:1px solid var(--border)}
.table-hdr{padding:1.1rem 1.5rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
.table-hdr h3{font-family:'Sora',sans-serif;font-size:1rem;font-weight:700}
.table-hdr span{font-size:.82rem;color:var(--text-light)}
.tw{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead th{background:#F8FAFC;padding:.9rem 1.2rem;text-align:left;font-size:.73rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.8px;border-bottom:1px solid var(--border);white-space:nowrap}
tbody tr{border-bottom:1px solid #F1F5F9;transition:background .15s}
tbody tr:hover{background:#F8FAFC}
tbody tr:last-child{border-bottom:none}
td{padding:.9rem 1.2rem;font-size:.88rem;vertical-align:middle}
.pkg-thumb{width:54px;height:38px;border-radius:8px;object-fit:cover;background:var(--bg)}
.pkg-name-cell{font-weight:700;max-width:220px}
.pkg-name-cell small{display:block;color:var(--text-light);font-weight:400;font-size:.78rem;margin-top:.15rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px}
.tier-badge{padding:.28rem .85rem;border-radius:50px;font-size:.73rem;font-weight:700;text-transform:uppercase;white-space:nowrap}
.tier-budget{background:#d1fae5;color:#065f46}
.tier-mid{background:#dbeafe;color:#1e40af}
.tier-luxury{background:#ede9fe;color:#5b21b6}
.price-cell{font-family:'Sora',sans-serif;font-weight:700;color:var(--primary);white-space:nowrap}
.status-on{background:#ECFDF5;color:#065F46}
.status-off{background:#FEF2F2;color:#991B1B}
.status-pill{display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .75rem;border-radius:50px;font-size:.72rem;font-weight:700}
.action-btns{display:flex;gap:.5rem}
.btn-edit-r{padding:.35rem .85rem;background:rgba(10,126,164,.1);color:var(--primary);border:none;border-radius:8px;font-family:inherit;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .2s;text-decoration:none;display:inline-block;white-space:nowrap}
.btn-edit-r:hover{background:var(--primary);color:white}
.btn-del-r{padding:.35rem .85rem;background:rgba(239,68,68,.1);color:var(--danger);border:none;border-radius:8px;font-family:inherit;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .2s;white-space:nowrap}
.btn-del-r:hover{background:var(--danger);color:white}
.btn-tog-r{padding:.35rem .85rem;border:none;border-radius:8px;font-family:inherit;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .2s;white-space:nowrap}
/* Pagination */
.pagination{display:flex;justify-content:center;align-items:center;gap:.5rem;padding:1.5rem}
.page-btn{padding:.5rem .9rem;border:2px solid var(--border);border-radius:8px;background:white;font-family:inherit;font-size:.85rem;font-weight:600;cursor:pointer;text-decoration:none;color:var(--text-dark);transition:all .2s}
.page-btn:hover{border-color:var(--primary);color:var(--primary)}
.page-btn.active{background:var(--primary);border-color:var(--primary);color:white}
.page-btn.disabled{opacity:.4;pointer-events:none}
/* Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:999;align-items:center;justify-content:center;padding:1rem}
.modal-overlay.open{display:flex}
.modal{background:white;border-radius:24px;padding:2rem;width:100%;max-width:660px;max-height:92vh;overflow-y:auto;box-shadow:var(--shadow-lg);animation:slideUp .3s ease}
@keyframes slideUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
.modal-hdr{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.8rem;padding-bottom:1rem;border-bottom:2px solid var(--bg)}
.modal-hdr h2{font-family:'Sora',sans-serif;font-size:1.3rem;font-weight:700;color:var(--primary)}
.modal-close{background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--text-light);text-decoration:none;line-height:1}
.modal-close:hover{color:var(--danger)}
.modal-form{display:flex;flex-direction:column;gap:1.1rem}
.form-row-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.fg{display:flex;flex-direction:column;gap:.4rem}
.fg label{font-size:.78rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.5px}
.fg input,.fg select,.fg textarea{padding:.75rem 1rem;border:2px solid var(--border);border-radius:12px;font-family:inherit;font-size:.92rem;color:var(--text-dark);outline:none;transition:border-color .3s;background:white}
.fg input:focus,.fg select:focus,.fg textarea:focus{border-color:var(--primary)}
.fg textarea{resize:vertical;min-height:85px}
.modal-foot{display:flex;gap:1rem;margin-top:1.5rem;padding-top:1.2rem;border-top:2px solid var(--bg)}
.btn-save{flex:1;background:var(--primary);color:white;padding:.85rem;border:none;border-radius:50px;font-family:inherit;font-size:1rem;font-weight:700;cursor:pointer;transition:all .3s;box-shadow:0 4px 15px rgba(10,126,164,.3)}
.btn-save:hover{background:var(--primary-dark)}
.btn-cancel-m{padding:.85rem 2rem;background:transparent;color:var(--text-light);border:2px solid var(--border);border-radius:50px;font-family:inherit;font-size:.95rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;text-align:center}
/* Delete confirm modal */
.confirm-modal{background:white;border-radius:20px;padding:2rem;max-width:400px;width:92%;text-align:center;box-shadow:var(--shadow-lg);animation:slideUp .3s ease}
.conf-icon{font-size:3rem;margin-bottom:.8rem}
.confirm-modal h3{font-family:'Sora',sans-serif;font-size:1.3rem;font-weight:700;margin-bottom:.5rem}
.confirm-modal p{color:var(--text-light);font-size:.9rem;margin-bottom:1.5rem}
.conf-btns{display:flex;gap:1rem;justify-content:center}
.btn-yes{padding:.75rem 2rem;background:var(--danger);color:white;border:none;border-radius:50px;font-family:inherit;font-size:.95rem;font-weight:700;cursor:pointer}
.btn-yes:hover{background:#DC2626}
.btn-no{padding:.75rem 2rem;background:transparent;color:var(--text-light);border:2px solid var(--border);border-radius:50px;font-family:inherit;font-size:.95rem;font-weight:600;cursor:pointer}
/* Alert */
.flash{padding:.8rem 1.2rem;border-radius:12px;margin-bottom:1.2rem;font-size:.88rem;font-weight:600}
.flash.ok{background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0}
.flash.err{background:#FEE2E2;color:#991B1B;border:1px solid #FECACA}
/* Empty state */
.empty-state{text-align:center;padding:3.5rem 2rem;color:var(--text-light)}
.empty-state .icon{font-size:3rem;margin-bottom:.8rem}
/* Responsive */
@media(max-width:1024px){.stats-strip{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .main{margin-left:0}
  .hamburger{display:flex}
  .stats-strip{grid-template-columns:1fr 1fr}
  .form-row-2{grid-template-columns:1fr}
  .content{padding:1rem}
  .topbar{padding:0 1rem}
}
</style>
</head>
<body>

<div class="sb-overlay" id="sbOverlay"></div>

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
        <a href="packages.php" class="nav-item active">
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
      <button class="hamburger" onclick="toggleSB()"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <div class="page-title"><h1>Packages Management</h1><p>Add, edit, and delete tour packages — changes show on the website instantly</p></div>
    </div>
    <div class="admin-dropdown" id="adminDD">
      <button class="admin-pill" onclick="toggleAdminDD()">
        <div class="admin-pill-av"><?= $admInit ?></div>
        <span><?= $admName ?></span>
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
      </button>
      <div class="admin-dd" id="adminDDMenu">
        <div class="adm-hdr">
          <div class="adm-av"><?= $admInit ?></div>
          <div><div class="adm-n"><?= $admName ?></div><div class="adm-r">Administrator</div></div>
        </div>
        <div class="adm-div"></div>
        <a href="../index.php" target="_blank" class="adm-itm">🌐 View Website</a>
        <a href="index.php" class="adm-itm">📊 Dashboard</a>
        <a href="profiles.php" class="adm-itm">👥 User Management</a>
        <div class="adm-div"></div>
        <a href="logout.php" class="adm-itm adm-out">🚪 Logout</a>
      </div>
    </div>
  </header>

  <div class="content">
    <?php if (isset($_GET['ok'])): ?>
      <div class="flash ok">✅ Package <?= $_GET['ok']==='added'?'added':($_GET['ok']==='updated'?'updated':'deleted') ?> successfully! Changes are now live on the website.</div>
    <?php endif; ?>
    <?php if ($flash): ?><div class="flash err"><?= $flash ?></div><?php endif; ?>

    <!-- Stats -->
    <div class="stats-strip">
      <div class="stat-card"><div class="stat-icon ic-blue">📦</div><div class="stat-info"><h3><?= $totalPkgs ?></h3><p>Total Packages</p></div></div>
      <div class="stat-card"><div class="stat-icon ic-green">💰</div><div class="stat-info"><h3><?= $budgetPkgs ?></h3><p>Budget</p></div></div>
      <div class="stat-card"><div class="stat-icon ic-yellow">⭐</div><div class="stat-info"><h3><?= $midPkgs ?></h3><p>Mid Range</p></div></div>
      <div class="stat-card"><div class="stat-icon ic-purple">💎</div><div class="stat-info"><h3><?= $luxPkgs ?></h3><p>Luxury</p></div></div>
    </div>

    <!-- Toolbar -->
    <form method="GET" action="packages.php">
      <div class="toolbar">
        <div class="tb-left">
          <div class="tb-search">
            <span class="si">🔍</span>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search packages…">
          </div>
          <select name="price_tier" class="filter-sel" onchange="this.form.submit()">
            <option value="">All Tiers</option>
            <option value="budget" <?= $tierF==='budget'?'selected':'' ?>>Budget</option>
            <option value="mid"    <?= $tierF==='mid'   ?'selected':'' ?>>Mid Range</option>
            <option value="luxury" <?= $tierF==='luxury'?'selected':'' ?>>Luxury</option>
          </select>
          <select name="duration" class="filter-sel" onchange="this.form.submit()">
            <option value="">All Durations</option>
            <?php foreach([1,4,5,7,10,12,14] as $d): ?>
              <option value="<?= $d ?>" <?= $durF===$d?'selected':'' ?>><?= $d ?> Day<?= $d>1?'s':'' ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn-search">Search</button>
          <?php if($search||$tierF||$durF): ?><a href="packages.php" class="btn-clear">Clear</a><?php endif; ?>
        </div>
        <div class="tb-right">
          <button type="button" class="btn-add" onclick="openAddModal()">＋ Add Package</button>
        </div>
      </div>
    </form>

    <!-- Table -->
    <div class="table-card">
      <div class="table-hdr">
        <h3>All Packages</h3>
        <span><?= $total ?> package<?= $total!==1?'s':'' ?></span>
      </div>
      <?php if (empty($packages)): ?>
        <div class="empty-state"><div class="icon">📦</div><p style="font-weight:600;font-size:1rem">No packages found</p><p style="font-size:.85rem;margin-top:.3rem">Try a different search or add a new package.</p></div>
      <?php else: ?>
      <div class="tw">
        <table>
          <thead>
            <tr>
              <th>Image</th>
              <th>Package</th>
              <th>Days</th>
              <th>Tier</th>
              <th>Price (LKR)</th>
              <th>Vehicle</th>
              <th>Hotel</th>
              <th>Group</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($packages as $p):
            $imgSrc = $p['image'] ? '../'.$p['image'] : '../images/colombocitytour.jpeg';
          ?>
            <tr>
              <td><img src="<?= htmlspecialchars($imgSrc) ?>" alt="" class="pkg-thumb" onerror="this.src='../images/colombocitytour.jpeg'"></td>
              <td><div class="pkg-name-cell"><?= htmlspecialchars($p['name']) ?><small>📍 <?= htmlspecialchars($p['locations']) ?></small></div></td>
              <td><?= $p['duration'] ?> Day<?= $p['duration']>1?'s':'' ?></td>
              <td><span class="tier-badge tier-<?= $p['price_tier'] ?>"><?= $p['price_tier'] ?></span></td>
              <td class="price-cell"><?= number_format($p['price'],0) ?></td>
              <td style="font-size:.82rem;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($p['vehicle']) ?></td>
              <td><?= htmlspecialchars($p['hotel_rating']) ?></td>
              <td style="font-size:.82rem"><?= htmlspecialchars($p['group_size']) ?></td>
              <td><span class="status-pill <?= $p['is_active']?'status-on':'status-off' ?>"><?= $p['is_active']?'✅ Active':'🚫 Off' ?></span></td>
              <td>
                <div class="action-btns">
                  <a href="packages.php?edit=<?= $p['id'] ?>" class="btn-edit-r">✏️ Edit</a>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn-tog-r <?= $p['is_active']?'btn-del-r':'btn-edit-r' ?>"><?= $p['is_active']?'Hide':'Show' ?></button>
                  </form>
                  <button class="btn-del-r" onclick="openDelModal(<?= $p['id'] ?>,'<?= htmlspecialchars(addslashes($p['name'])) ?>')">🗑️</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php if($page>1): ?><a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&price_tier=<?= urlencode($tierF) ?>&duration=<?= $durF ?>" class="page-btn">← Prev</a><?php else: ?><span class="page-btn disabled">← Prev</span><?php endif; ?>
        <?php for($i=1;$i<=$totalPages;$i++): ?><a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&price_tier=<?= urlencode($tierF) ?>&duration=<?= $durF ?>" class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a><?php endfor; ?>
        <?php if($page<$totalPages): ?><a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&price_tier=<?= urlencode($tierF) ?>&duration=<?= $durF ?>" class="page-btn">Next →</a><?php else: ?><span class="page-btn disabled">Next →</span><?php endif; ?>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ── ADD MODAL ── -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-hdr">
      <h2>➕ Add New Package</h2>
      <button class="modal-close" onclick="closeModal('addModal')">✕</button>
    </div>
    <form method="POST" action="packages.php" class="modal-form">
      <input type="hidden" name="action" value="add">
      <div class="fg" style="grid-column:1/-1"><label>Package Name *</label><input type="text" name="name" placeholder="e.g. Colombo City Tour" required></div>
      <div class="fg"><label>Description *</label><textarea name="description" placeholder="Describe the package…" required></textarea></div>
      <div class="form-row-2">
        <div class="fg"><label>Duration (Days) *</label><input type="number" name="duration" placeholder="e.g. 7" min="1" required></div>
        <div class="fg"><label>Price (LKR) *</label><input type="number" name="price" placeholder="e.g. 75000" min="1" required></div>
      </div>
      <div class="form-row-2">
        <div class="fg"><label>Price Tier *</label>
          <select name="price_tier" required>
            <option value="">Select tier</option>
            <option value="budget">Budget (Under 50K)</option>
            <option value="mid">Mid Range (50K–100K)</option>
            <option value="luxury">Luxury (100K+)</option>
          </select>
        </div>
        <div class="fg"><label>Hotel Rating *</label>
          <select name="hotel_rating" required>
            <option value="">Select</option>
            <option value="★★★">★★★ (3 Star)</option>
            <option value="★★★★">★★★★ (4 Star)</option>
            <option value="★★★★★">★★★★★ (5 Star)</option>
          </select>
        </div>
      </div>
      <div class="fg"><label>Locations *</label><input type="text" name="locations" placeholder="e.g. Colombo, Kandy, Ella" required></div>
      <div class="form-row-2">
        <div class="fg"><label>Vehicle *</label><input type="text" name="vehicle" placeholder="e.g. Toyota Hiace KDH" required></div>
        <div class="fg"><label>Group Size *</label><input type="text" name="group_size" placeholder="e.g. 2–6 People" required></div>
      </div>
      <div class="fg"><label>Image Path (relative to jettransfer/)</label><input type="text" name="image" placeholder="images/colombocitytour.jpeg"></div>
      <div class="modal-foot">
        <button type="submit" class="btn-save">Add Package</button>
        <button type="button" class="btn-cancel-m" onclick="closeModal('addModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- ── EDIT MODAL ── -->
<?php if ($editPkg): ?>
<div class="modal-overlay open" id="editModal">
  <div class="modal">
    <div class="modal-hdr">
      <h2>✏️ Edit Package</h2>
      <a href="packages.php" class="modal-close">✕</a>
    </div>
    <form method="POST" action="packages.php" class="modal-form">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?= $editPkg['id'] ?>">
      <div class="fg"><label>Package Name *</label><input type="text" name="name" value="<?= htmlspecialchars($editPkg['name']) ?>" required></div>
      <div class="fg"><label>Description *</label><textarea name="description" required><?= htmlspecialchars($editPkg['description']) ?></textarea></div>
      <div class="form-row-2">
        <div class="fg"><label>Duration (Days) *</label><input type="number" name="duration" value="<?= $editPkg['duration'] ?>" min="1" required></div>
        <div class="fg"><label>Price (LKR) *</label><input type="number" name="price" value="<?= $editPkg['price'] ?>" min="1" required></div>
      </div>
      <div class="form-row-2">
        <div class="fg"><label>Price Tier *</label>
          <select name="price_tier" required>
            <option value="budget"  <?= $editPkg['price_tier']==='budget' ?'selected':'' ?>>Budget (Under 50K)</option>
            <option value="mid"     <?= $editPkg['price_tier']==='mid'    ?'selected':'' ?>>Mid Range (50K–100K)</option>
            <option value="luxury"  <?= $editPkg['price_tier']==='luxury' ?'selected':'' ?>>Luxury (100K+)</option>
          </select>
        </div>
        <div class="fg"><label>Hotel Rating *</label>
          <select name="hotel_rating" required>
            <option value="★★★"   <?= $editPkg['hotel_rating']==='★★★'   ?'selected':'' ?>>★★★ (3 Star)</option>
            <option value="★★★★"  <?= $editPkg['hotel_rating']==='★★★★'  ?'selected':'' ?>>★★★★ (4 Star)</option>
            <option value="★★★★★" <?= $editPkg['hotel_rating']==='★★★★★' ?'selected':'' ?>>★★★★★ (5 Star)</option>
          </select>
        </div>
      </div>
      <div class="fg"><label>Locations *</label><input type="text" name="locations" value="<?= htmlspecialchars($editPkg['locations']) ?>" required></div>
      <div class="form-row-2">
        <div class="fg"><label>Vehicle *</label><input type="text" name="vehicle" value="<?= htmlspecialchars($editPkg['vehicle']) ?>" required></div>
        <div class="fg"><label>Group Size *</label><input type="text" name="group_size" value="<?= htmlspecialchars($editPkg['group_size']) ?>" required></div>
      </div>
      <div class="fg"><label>Image Path</label><input type="text" name="image" value="<?= htmlspecialchars($editPkg['image']) ?>"></div>
      <div class="modal-foot">
        <button type="submit" class="btn-save">Save Changes</button>
        <a href="packages.php" class="btn-cancel-m">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- ── DELETE CONFIRM MODAL ── -->
<div class="modal-overlay" id="delModal">
  <div class="confirm-modal">
    <div class="conf-icon">🗑️</div>
    <h3>Delete Package?</h3>
    <p id="delMsg">This action cannot be undone.</p>
    <form method="POST" action="packages.php">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" id="delId">
      <div class="conf-btns">
        <button type="submit" class="btn-yes">Yes, Delete</button>
        <button type="button" class="btn-no" onclick="closeModal('delModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleSB(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sbOverlay').classList.toggle('show')}
document.getElementById('sbOverlay').addEventListener('click',()=>{document.getElementById('sidebar').classList.remove('open');document.getElementById('sbOverlay').classList.remove('show')});
function toggleAdminDD(){document.getElementById('adminDDMenu').classList.toggle('open')}
document.addEventListener('click',e=>{const w=document.getElementById('adminDD');if(w&&!w.contains(e.target))document.getElementById('adminDDMenu').classList.remove('open')});
function openAddModal(){document.getElementById('addModal').classList.add('open')}
function closeModal(id){document.getElementById(id).classList.remove('open')}
function openDelModal(id,name){document.getElementById('delId').value=id;document.getElementById('delMsg').textContent='"'+name+'" will be permanently removed.';document.getElementById('delModal').classList.add('open')}
document.getElementById('addModal').addEventListener('click',function(e){if(e.target===this)closeModal('addModal')});
document.getElementById('delModal').addEventListener('click',function(e){if(e.target===this)closeModal('delModal')});
</script>
</body>
</html>