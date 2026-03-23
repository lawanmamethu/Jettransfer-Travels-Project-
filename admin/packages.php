<?php
// ============================================================
//  packages.php  —  Admin Packages Page with Full UI
//  Jettransfer Travels
// ============================================================
session_start();
// Uncomment when login is ready:
// if (!isset($_SESSION['jt_admin'])) { header('Location: login.php'); exit; }

require_once __DIR__ . '/db_packages.php';
$pdo = getDB();

function clean(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

$errors  = [];

// ============================================================
//  HANDLE POST — Add / Edit / Delete
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $action = $_POST['action'];

    // ── ADD ──────────────────────────────────────────────────
    if ($action === 'add') {
        $required = ['name','description','duration','price','price_tier','locations','vehicle','hotel_rating','group_size'];
        foreach ($required as $f) {
            if (empty($_POST[$f])) $errors[] = "Field '$f' is required.";
        }
        if (!in_array($_POST['price_tier'] ?? '', ['budget','mid','luxury'])) $errors[] = "Invalid price tier.";
        $duration = (int)($_POST['duration'] ?? 0);
        $price    = (float)($_POST['price']   ?? 0);
        if ($duration <= 0) $errors[] = "Duration must be a positive number.";
        if ($price    <= 0) $errors[] = "Price must be a positive number.";

        if (empty($errors)) {
            $stmt = $pdo->prepare(
                'INSERT INTO packages (name,description,duration,price,price_tier,locations,vehicle,hotel_rating,group_size,image)
                 VALUES (:name,:description,:duration,:price,:price_tier,:locations,:vehicle,:hotel_rating,:group_size,:image)'
            );
            $stmt->execute([
                ':name'         => clean($_POST['name']),
                ':description'  => clean($_POST['description']),
                ':duration'     => $duration,
                ':price'        => $price,
                ':price_tier'   => $_POST['price_tier'],
                ':locations'    => clean($_POST['locations']),
                ':vehicle'      => clean($_POST['vehicle']),
                ':hotel_rating' => clean($_POST['hotel_rating']),
                ':group_size'   => clean($_POST['group_size']),
                ':image'        => clean($_POST['image'] ?? ''),
            ]);
            header('Location: packages.php?success=added'); exit;
        }
    }

    // ── EDIT ─────────────────────────────────────────────────
    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { $errors[] = "Invalid package ID."; }
        $allowedFields = ['name','description','duration','price','price_tier','locations','vehicle','hotel_rating','group_size','image'];
        $sets = []; $params = [':id' => $id];
        foreach ($allowedFields as $field) {
            if (isset($_POST[$field])) {
                $sets[]            = "$field = :$field";
                $params[":$field"] = ($field === 'duration') ? (int)$_POST[$field]
                                   : (($field === 'price')   ? (float)$_POST[$field]
                                   : clean((string)$_POST[$field]));
            }
        }
        if (empty($errors) && !empty($sets)) {
            $pdo->prepare('UPDATE packages SET ' . implode(', ', $sets) . ' WHERE id = :id')->execute($params);
            header('Location: packages.php?success=updated'); exit;
        }
    }

    // ── DELETE ───────────────────────────────────────────────
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM packages WHERE id = :id')->execute([':id' => $id]);
        }
        header('Location: packages.php?success=deleted'); exit;
    }
}

// ============================================================
//  FETCH PACKAGES
// ============================================================
$search = clean($_GET['search']     ?? '');
$tierF  = clean($_GET['price_tier'] ?? '');
$durF   = (int)($_GET['duration']   ?? 0);
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 12;
$offset = ($page - 1) * $limit;

$where = []; $params = [];
if ($search) {
    $where[]            = '(name LIKE :s OR locations LIKE :s2)';
    $params[':s']       = "%$search%";
    $params[':s2']      = "%$search%";
}
if (in_array($tierF, ['budget','mid','luxury'])) {
    $where[]            = 'price_tier = :tier';
    $params[':tier']    = $tierF;
}
if ($durF > 0) {
    $where[]            = 'duration = :dur';
    $params[':dur']     = $durF;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ✅ FIXED COUNT QUERY (use prepare, not query)
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM packages $whereSql");

foreach ($params as $k => $v) {
    $countStmt->bindValue($k, $v);
}

$countStmt->execute();
$total = (int)$countStmt->fetchColumn();

// ✅ FETCH DATA
$stmt = $pdo->prepare("SELECT * FROM packages $whereSql ORDER BY duration ASC, price ASC LIMIT :lim OFFSET :off");

foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}

$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);

$stmt->execute();
$packages = $stmt->fetchAll();

$totalPages = (int)ceil($total / $limit);

// Stats
$totalPkgs  = (int)$pdo->query('SELECT COUNT(*) FROM packages')->fetchColumn();
$budgetPkgs = (int)$pdo->query("SELECT COUNT(*) FROM packages WHERE price_tier='budget'")->fetchColumn();
$midPkgs    = (int)$pdo->query("SELECT COUNT(*) FROM packages WHERE price_tier='mid'")->fetchColumn();
$luxPkgs    = (int)$pdo->query("SELECT COUNT(*) FROM packages WHERE price_tier='luxury'")->fetchColumn();

// Edit package fetch
$editPkg = null;
if (!empty($_GET['edit'])) {
    $es = $pdo->prepare('SELECT * FROM packages WHERE id = :id');
    $es->execute([':id' => (int)$_GET['edit']]);
    $editPkg = $es->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin – Packages | Jettransfer Travels</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary:#0A7EA4; --primary-dark:#065A7A; --primary-light:#E0F2FA;
            --secondary:#F59E0B; --accent:#10B981; --danger:#EF4444;
            --text-dark:#0F172A; --text-light:#94A3B8; --bg:#F1F5F9;
            --sidebar-bg:#0F172A; --border:#E2E8F0;
            --shadow-sm:0 1px 3px rgba(0,0,0,.06);
            --shadow-md:0 4px 16px rgba(0,0,0,.08);
            --shadow-lg:0 10px 40px rgba(0,0,0,.15);
            --sidebar-w:260px;
        }
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Manrope',sans-serif;background:var(--bg);color:var(--text-dark);min-height:100vh;display:flex;}

        /* SIDEBAR */
        .sidebar{width:var(--sidebar-w);background:var(--sidebar-bg);min-height:100vh;position:fixed;left:0;top:0;bottom:0;display:flex;flex-direction:column;z-index:100;transition:transform .3s ease;}
        .sidebar-brand{padding:1.8rem 1.5rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.07);display:flex;align-items:center;gap:.8rem;}
        .sidebar-logo{width:42px;height:42px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;}
        .sidebar-brand-text h2{font-family:'Sora',sans-serif;font-size:1rem;font-weight:800;color:#fff;}
        .sidebar-brand-text span{font-size:.72rem;color:rgba(255,255,255,.4);letter-spacing:1.5px;text-transform:uppercase;}
        .sidebar-nav{flex:1;padding:1.2rem .8rem;overflow-y:auto;}
        .nav-label{font-size:.68rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.25);padding:.5rem .8rem;margin-top:.8rem;margin-bottom:.3rem;}
        .nav-item{display:flex;align-items:center;gap:.75rem;padding:.75rem .9rem;border-radius:12px;color:rgba(255,255,255,.55);text-decoration:none;font-size:.9rem;font-weight:500;transition:all .2s ease;margin-bottom:.2rem;}
        .nav-item:hover{background:rgba(255,255,255,.07);color:rgba(255,255,255,.9);}
        .nav-item.active{background:linear-gradient(135deg,rgba(10,126,164,.35),rgba(16,185,129,.2));color:#fff;box-shadow:inset 0 0 0 1px rgba(10,126,164,.4);}
        .nav-icon{width:20px;height:20px;flex-shrink:0;}
        .sidebar-footer{padding:1rem .8rem 1.5rem;border-top:1px solid rgba(255,255,255,.07);}
        .admin-profile{display:flex;align-items:center;gap:.75rem;padding:.75rem .9rem;border-radius:12px;background:rgba(255,255,255,.05);margin-bottom:.5rem;}
        .admin-avatar{width:36px;height:36px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.9rem;font-weight:700;color:#fff;flex-shrink:0;}
        .admin-info h4{font-size:.85rem;font-weight:600;color:#fff;}
        .admin-info p{font-size:.72rem;color:rgba(255,255,255,.4);}
        .btn-logout{display:flex;align-items:center;gap:.6rem;padding:.7rem .9rem;border-radius:12px;color:rgba(255,255,255,.5);font-size:.88rem;font-weight:500;cursor:pointer;transition:all .2s;border:none;background:none;width:100%;font-family:'Manrope',sans-serif;text-decoration:none;}
        .btn-logout:hover{background:rgba(239,68,68,.15);color:#FCA5A5;}
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99;}
        .sidebar-overlay.show{display:block;}

        /* MAIN */
        .main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;}
        .topbar{background:#fff;border-bottom:1px solid var(--border);padding:0 2rem;height:70px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;box-shadow:var(--shadow-sm);}
        .topbar-left{display:flex;align-items:center;gap:1rem;}
        .hamburger{display:none;background:none;border:none;cursor:pointer;padding:6px;color:var(--text-dark);}
        .page-title h1{font-family:'Sora',sans-serif;font-size:1.2rem;font-weight:700;}
        .page-title p{font-size:.8rem;color:var(--text-light);}
        .topbar-avatar{width:40px;height:40px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.9rem;}
        .page-body{padding:2rem;flex:1;}

        /* STATS */
        .stats-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:1.2rem;margin-bottom:2rem;}
        .stat-card{background:white;border-radius:16px;padding:1.4rem;box-shadow:var(--shadow-sm);display:flex;align-items:center;gap:1rem;border:1px solid var(--border);}
        .stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;}
        .stat-icon.blue{background:rgba(10,126,164,.12);}
        .stat-icon.green{background:rgba(16,185,129,.12);}
        .stat-icon.yellow{background:rgba(245,158,11,.12);}
        .stat-icon.purple{background:rgba(124,58,237,.12);}
        .stat-info h3{font-family:'Sora',sans-serif;font-size:1.6rem;font-weight:800;color:var(--text-dark);line-height:1;}
        .stat-info p{font-size:.82rem;color:var(--text-light);margin-top:.2rem;}

        /* TOOLBAR */
        .toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;gap:1rem;flex-wrap:wrap;}
        .toolbar-search{position:relative;flex:1;max-width:280px;}
        .toolbar-search input{width:100%;padding:.7rem 1rem .7rem 2.8rem;border:2px solid var(--border);border-radius:50px;font-family:inherit;font-size:.9rem;outline:none;background:white;color:var(--text-dark);transition:border-color .3s;}
        .toolbar-search input:focus{border-color:var(--primary);}
        .toolbar-search .s-icon{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--text-light);}
        .toolbar-right{display:flex;gap:.8rem;align-items:center;flex-wrap:wrap;}
        .filter-select{padding:.7rem 1rem;border:2px solid var(--border);border-radius:50px;font-family:inherit;font-size:.88rem;color:var(--text-dark);outline:none;cursor:pointer;background:white;transition:border-color .3s;}
        .filter-select:focus{border-color:var(--primary);}
        .btn-primary{background:var(--primary);color:white;padding:.7rem 1.5rem;border:none;border-radius:50px;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;transition:all .3s;box-shadow:0 4px 12px rgba(10,126,164,.3);text-decoration:none;display:inline-flex;align-items:center;gap:.4rem;}
        .btn-primary:hover{background:var(--primary-dark);transform:translateY(-2px);}
        .btn-clear{background:var(--text-light);color:white;padding:.7rem 1.2rem;border:none;border-radius:50px;font-family:inherit;font-size:.88rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;}
        .btn-clear:hover{background:#64748B;}

        /* TABLE */
        .table-card{background:white;border-radius:20px;box-shadow:var(--shadow-sm);overflow:hidden;border:1px solid var(--border);}
        .table-header{padding:1.2rem 1.5rem;border-bottom:1px solid #F1F5F9;display:flex;justify-content:space-between;align-items:center;}
        .table-header h3{font-family:'Sora',sans-serif;font-size:1rem;font-weight:700;}
        .table-header span{font-size:.85rem;color:var(--text-light);}
        .table-wrap{overflow-x:auto;}
        table{width:100%;border-collapse:collapse;}
        thead th{background:#F8FAFC;padding:.9rem 1.2rem;text-align:left;font-size:.78rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.8px;border-bottom:1px solid var(--border);white-space:nowrap;}
        tbody tr{border-bottom:1px solid #F1F5F9;transition:background .2s;}
        tbody tr:last-child{border-bottom:none;}
        tbody tr:hover{background:#F8FAFC;}
        tbody td{padding:1rem 1.2rem;font-size:.88rem;vertical-align:middle;}
        .pkg-thumb{width:56px;height:40px;border-radius:8px;object-fit:cover;background:var(--bg);}
        .pkg-name-cell{font-weight:700;color:var(--text-dark);}
        .pkg-name-cell small{display:block;color:var(--text-light);font-weight:400;font-size:.8rem;margin-top:.2rem;}
        .tier-badge{padding:.28rem .85rem;border-radius:50px;font-size:.75rem;font-weight:700;text-transform:uppercase;}
        .tier-budget{background:#d1fae5;color:#065f46;}
        .tier-mid{background:#dbeafe;color:#1e40af;}
        .tier-luxury{background:#ede9fe;color:#5b21b6;}
        .price-cell{font-family:'Sora',sans-serif;font-weight:700;color:var(--primary);}
        .action-btns{display:flex;gap:.5rem;align-items:center;}
        .btn-edit-row{padding:.38rem .9rem;background:rgba(10,126,164,.1);color:var(--primary);border:none;border-radius:8px;font-family:inherit;font-size:.8rem;font-weight:600;cursor:pointer;transition:all .2s;text-decoration:none;display:inline-block;}
        .btn-edit-row:hover{background:var(--primary);color:white;}
        .btn-delete-row{padding:.38rem .9rem;background:rgba(239,68,68,.1);color:var(--danger);border:none;border-radius:8px;font-family:inherit;font-size:.8rem;font-weight:600;cursor:pointer;transition:all .2s;}
        .btn-delete-row:hover{background:var(--danger);color:white;}

        /* PAGINATION */
        .pagination{display:flex;justify-content:center;align-items:center;gap:.5rem;padding:1.5rem;}
        .page-btn{padding:.5rem .9rem;border:2px solid var(--border);border-radius:8px;background:white;font-family:inherit;font-size:.85rem;font-weight:600;cursor:pointer;text-decoration:none;color:var(--text-dark);transition:all .2s;}
        .page-btn:hover{border-color:var(--primary);color:var(--primary);}
        .page-btn.active{background:var(--primary);border-color:var(--primary);color:white;}
        .page-btn.disabled{opacity:.4;pointer-events:none;}

        /* MODAL */
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:999;align-items:center;justify-content:center;padding:1rem;}
        .modal-overlay.open{display:flex;}
        .modal{background:white;border-radius:24px;padding:2rem;width:100%;max-width:640px;max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-lg);animation:slideUp .3s ease;}
        @keyframes slideUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
        .modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.8rem;padding-bottom:1rem;border-bottom:2px solid var(--bg);}
        .modal-header h2{font-family:'Sora',sans-serif;font-size:1.4rem;font-weight:700;color:var(--primary);}
        .modal-close{background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--text-light);line-height:1;text-decoration:none;}
        .modal-close:hover{color:var(--danger);}
        .modal-form{display:flex;flex-direction:column;gap:1.1rem;}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
        .form-group{display:flex;flex-direction:column;gap:.4rem;}
        .form-group label{font-size:.8rem;font-weight:700;color:var(--text-light);text-transform:uppercase;letter-spacing:.5px;}
        .form-group input,.form-group select,.form-group textarea{padding:.75rem 1rem;border:2px solid var(--border);border-radius:12px;font-family:inherit;font-size:.92rem;color:var(--text-dark);outline:none;transition:border-color .3s;background:white;}
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--primary);}
        .form-group textarea{resize:vertical;min-height:80px;}
        .modal-footer{display:flex;gap:1rem;margin-top:1.5rem;padding-top:1.2rem;border-top:2px solid var(--bg);}
        .btn-modal-save{flex:1;background:var(--primary);color:white;padding:.85rem;border:none;border-radius:50px;font-family:inherit;font-size:1rem;font-weight:700;cursor:pointer;transition:all .3s;box-shadow:0 4px 15px rgba(10,126,164,.3);}
        .btn-modal-save:hover{background:var(--primary-dark);}
        .btn-modal-cancel{padding:.85rem 2rem;background:transparent;color:var(--text-light);border:2px solid var(--border);border-radius:50px;font-family:inherit;font-size:.95rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;text-align:center;}

        /* DELETE CONFIRM */
        .confirm-modal{background:white;border-radius:20px;padding:2rem;max-width:400px;width:92%;text-align:center;box-shadow:var(--shadow-lg);animation:slideUp .3s ease;}
        .confirm-icon{font-size:3rem;margin-bottom:1rem;}
        .confirm-modal h3{font-family:'Sora',sans-serif;font-size:1.3rem;font-weight:700;margin-bottom:.5rem;}
        .confirm-modal p{color:var(--text-light);font-size:.9rem;margin-bottom:1.5rem;}
        .confirm-btns{display:flex;gap:1rem;justify-content:center;}
        .btn-confirm-yes{padding:.75rem 2rem;background:var(--danger);color:white;border:none;border-radius:50px;font-family:inherit;font-size:.95rem;font-weight:700;cursor:pointer;}
        .btn-confirm-yes:hover{background:#DC2626;}
        .btn-confirm-no{padding:.75rem 2rem;background:transparent;color:var(--text-light);border:2px solid var(--border);border-radius:50px;font-family:inherit;font-size:.95rem;font-weight:600;cursor:pointer;}

        /* ALERTS */
        .alert{padding:1rem 1.5rem;border-radius:12px;margin-bottom:1.5rem;font-weight:600;font-size:.92rem;}
        .alert-success{background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;}
        .alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}

        /* EMPTY */
        .table-empty{text-align:center;padding:4rem 2rem;color:var(--text-light);}
        .table-empty .e-icon{font-size:3rem;margin-bottom:1rem;}
        .table-empty h3{font-family:'Sora',sans-serif;font-size:1.2rem;color:var(--text-dark);margin-bottom:.5rem;}

        /* RESPONSIVE */
        @media(max-width:1024px){.stats-strip{grid-template-columns:repeat(2,1fr);}}
        @media(max-width:768px){
            .sidebar{transform:translateX(-100%);}
            .sidebar.open{transform:translateX(0);}
            .main{margin-left:0;}
            .hamburger{display:flex;}
            .stats-strip{grid-template-columns:1fr 1fr;}
            .form-row{grid-template-columns:1fr;}
            table{display:block;overflow-x:auto;}
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
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
            Services &amp; Gallery
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

<!-- MAIN -->
<div class="main">
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger" onclick="toggleSidebar()">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="page-title">
                <h1>Packages Management</h1>
                <p>Add, edit and delete tour packages</p>
            </div>
        </div>
        <div class="topbar-right"><div class="topbar-avatar">A</div></div>
    </header>

    <div class="page-body">

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            ✅ Package <?= $_GET['success']==='added'?'added':($_GET['success']==='updated'?'updated':'deleted') ?> successfully!
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">❌ <?= implode('<br>', $errors) ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-strip">
            <div class="stat-card"><div class="stat-icon blue">📦</div><div class="stat-info"><h3><?= $totalPkgs ?></h3><p>Total Packages</p></div></div>
            <div class="stat-card"><div class="stat-icon green">💰</div><div class="stat-info"><h3><?= $budgetPkgs ?></h3><p>Budget Packages</p></div></div>
            <div class="stat-card"><div class="stat-icon yellow">⭐</div><div class="stat-info"><h3><?= $midPkgs ?></h3><p>Mid Range</p></div></div>
            <div class="stat-card"><div class="stat-icon purple">💎</div><div class="stat-info"><h3><?= $luxPkgs ?></h3><p>Luxury Packages</p></div></div>
        </div>

        <!-- Toolbar -->
        <form method="GET" action="packages.php">
            <div class="toolbar">
                <div class="toolbar-search">
                    <span class="s-icon">🔍</span>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search packages…">
                </div>
                <div class="toolbar-right">
                    <select name="price_tier" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Tiers</option>
                        <option value="budget" <?= $tierF==='budget'?'selected':'' ?>>Budget</option>
                        <option value="mid"    <?= $tierF==='mid'   ?'selected':'' ?>>Mid Range</option>
                        <option value="luxury" <?= $tierF==='luxury'?'selected':'' ?>>Luxury</option>
                    </select>
                    <select name="duration" class="filter-select" onchange="this.form.submit()">
                        <option value="">All Durations</option>
                        <?php foreach([1,4,5,7,12,14] as $d): ?>
                        <option value="<?= $d ?>" <?= $durF===$d?'selected':'' ?>><?= $d ?> Day<?= $d>1?'s':'' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-primary">Search</button>
                    <?php if($search||$tierF||$durF): ?>
                    <a href="packages.php" class="btn-clear">Clear</a>
                    <?php endif; ?>
                    <button type="button" class="btn-primary" onclick="openAddModal()">＋ Add Package</button>
                </div>
            </div>
        </form>

        <!-- Table -->
        <div class="table-card">
            <div class="table-header">
                <h3>All Packages</h3>
                <span><?= $total ?> package<?= $total!==1?'s':'' ?></span>
            </div>

            <?php if (empty($packages)): ?>
            <div class="table-empty">
                <div class="e-icon">📦</div>
                <h3>No packages found</h3>
                <p>Try a different search or add a new package.</p>
            </div>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th><th>Package Name</th><th>Duration</th>
                            <th>Tier</th><th>Price</th><th>Vehicle</th>
                            <th>Hotel</th><th>Group</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($packages as $p): ?>
                        <tr>
                            <td><img src="../<?= htmlspecialchars($p['image']?:'images/colombocitytour.jpeg') ?>" alt="" class="pkg-thumb" onerror="this.src='../images/colombocitytour.jpeg'"></td>
                            <td><div class="pkg-name-cell"><?= htmlspecialchars($p['name']) ?><small>📍 <?= htmlspecialchars($p['locations']) ?></small></div></td>
                            <td><?= $p['duration'] ?> Day<?= $p['duration']>1?'s':'' ?></td>
                            <td><span class="tier-badge tier-<?= $p['price_tier'] ?>"><?= $p['price_tier'] ?></span></td>
                            <td class="price-cell">LKR <?= number_format($p['price'],0) ?></td>
                            <td><?= htmlspecialchars($p['vehicle']) ?></td>
                            <td><?= htmlspecialchars($p['hotel_rating']) ?></td>
                            <td><?= htmlspecialchars($p['group_size']) ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="packages.php?edit=<?= $p['id'] ?>" class="btn-edit-row">✏️ Edit</a>
                                    <button class="btn-delete-row" onclick="openDeleteModal(<?= $p['id'] ?>,'<?= htmlspecialchars(addslashes($p['name'])) ?>')">🗑️ Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if($totalPages>1): ?>
            <div class="pagination">
                <?php if($page>1): ?>
                    <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&price_tier=<?= urlencode($tierF) ?>&duration=<?= $durF ?>" class="page-btn">← Prev</a>
                <?php else: ?><span class="page-btn disabled">← Prev</span><?php endif; ?>
                <?php for($i=1;$i<=$totalPages;$i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&price_tier=<?= urlencode($tierF) ?>&duration=<?= $durF ?>" class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <?php if($page<$totalPages): ?>
                    <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&price_tier=<?= urlencode($tierF) ?>&duration=<?= $durF ?>" class="page-btn">Next →</a>
                <?php else: ?><span class="page-btn disabled">Next →</span><?php endif; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <div class="modal-header">
            <h2>➕ Add New Package</h2>
            <button class="modal-close" onclick="closeAddModal()">✕</button>
        </div>
        <form method="POST" action="packages.php" class="modal-form">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <div class="form-group" style="grid-column:1/-1;"><label>Package Name *</label><input type="text" name="name" placeholder="e.g. Colombo City Tour" required></div>
            </div>
            <div class="form-group"><label>Description *</label><textarea name="description" placeholder="Describe the package…" required></textarea></div>
            <div class="form-row">
                <div class="form-group"><label>Duration (Days) *</label><input type="number" name="duration" placeholder="e.g. 7" min="1" required></div>
                <div class="form-group"><label>Price (LKR) *</label><input type="number" name="price" placeholder="e.g. 75000" min="1" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Price Tier *</label>
                    <select name="price_tier" required>
                        <option value="">Select tier</option>
                        <option value="budget">Budget (Under 50K)</option>
                        <option value="mid">Mid Range (50K–100K)</option>
                        <option value="luxury">Luxury (100K+)</option>
                    </select>
                </div>
                <div class="form-group"><label>Hotel Rating *</label>
                    <select name="hotel_rating" required>
                        <option value="">Select rating</option>
                        <option value="★★★">★★★ (3 Star)</option>
                        <option value="★★★★">★★★★ (4 Star)</option>
                        <option value="★★★★★">★★★★★ (5 Star)</option>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Locations *</label><input type="text" name="locations" placeholder="e.g. Colombo, Kandy, Ella" required></div>
            <div class="form-row">
                <div class="form-group"><label>Vehicle *</label><input type="text" name="vehicle" placeholder="e.g. Toyota Hiace KDH" required></div>
                <div class="form-group"><label>Group Size *</label><input type="text" name="group_size" placeholder="e.g. 2–6 People" required></div>
            </div>
            <div class="form-group"><label>Image Path</label><input type="text" name="image" placeholder="e.g. images/colombocitytour.jpeg"></div>
            <div class="modal-footer">
                <button type="submit" class="btn-modal-save">Add Package</button>
                <button type="button" class="btn-modal-cancel" onclick="closeAddModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<?php if($editPkg): ?>
<div class="modal-overlay open" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h2>✏️ Edit Package</h2>
            <a href="packages.php" class="modal-close">✕</a>
        </div>
        <form method="POST" action="packages.php" class="modal-form">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= $editPkg['id'] ?>">
            <div class="form-row">
                <div class="form-group" style="grid-column:1/-1;"><label>Package Name *</label><input type="text" name="name" value="<?= htmlspecialchars($editPkg['name']) ?>" required></div>
            </div>
            <div class="form-group"><label>Description *</label><textarea name="description" required><?= htmlspecialchars($editPkg['description']) ?></textarea></div>
            <div class="form-row">
                <div class="form-group"><label>Duration (Days) *</label><input type="number" name="duration" value="<?= $editPkg['duration'] ?>" min="1" required></div>
                <div class="form-group"><label>Price (LKR) *</label><input type="number" name="price" value="<?= $editPkg['price'] ?>" min="1" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Price Tier *</label>
                    <select name="price_tier" required>
                        <option value="budget"  <?= $editPkg['price_tier']==='budget' ?'selected':'' ?>>Budget (Under 50K)</option>
                        <option value="mid"     <?= $editPkg['price_tier']==='mid'    ?'selected':'' ?>>Mid Range (50K–100K)</option>
                        <option value="luxury"  <?= $editPkg['price_tier']==='luxury' ?'selected':'' ?>>Luxury (100K+)</option>
                    </select>
                </div>
                <div class="form-group"><label>Hotel Rating *</label>
                    <select name="hotel_rating" required>
                        <option value="★★★"   <?= $editPkg['hotel_rating']==='★★★'   ?'selected':'' ?>>★★★ (3 Star)</option>
                        <option value="★★★★"  <?= $editPkg['hotel_rating']==='★★★★'  ?'selected':'' ?>>★★★★ (4 Star)</option>
                        <option value="★★★★★" <?= $editPkg['hotel_rating']==='★★★★★' ?'selected':'' ?>>★★★★★ (5 Star)</option>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Locations *</label><input type="text" name="locations" value="<?= htmlspecialchars($editPkg['locations']) ?>" required></div>
            <div class="form-row">
                <div class="form-group"><label>Vehicle *</label><input type="text" name="vehicle" value="<?= htmlspecialchars($editPkg['vehicle']) ?>" required></div>
                <div class="form-group"><label>Group Size *</label><input type="text" name="group_size" value="<?= htmlspecialchars($editPkg['group_size']) ?>" required></div>
            </div>
            <div class="form-group"><label>Image Path</label><input type="text" name="image" value="<?= htmlspecialchars($editPkg['image']) ?>"></div>
            <div class="modal-footer">
                <button type="submit" class="btn-modal-save">Save Changes</button>
                <a href="packages.php" class="btn-modal-cancel">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- DELETE CONFIRM MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="confirm-modal">
        <div class="confirm-icon">🗑️</div>
        <h3>Delete Package?</h3>
        <p id="deleteMsg">This action cannot be undone.</p>
        <form method="POST" action="packages.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteId">
            <div class="confirm-btns">
                <button type="submit" class="btn-confirm-yes">Yes, Delete</button>
                <button type="button" class="btn-confirm-no" onclick="closeDeleteModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleSidebar(){
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('show');
    }
    document.getElementById('sidebarOverlay').addEventListener('click',()=>{
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('show');
    });
    function openAddModal()  { document.getElementById('addModal').classList.add('open'); }
    function closeAddModal() { document.getElementById('addModal').classList.remove('open'); }
    function openDeleteModal(id, name){
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteMsg').textContent = '"'+name+'" will be permanently removed.';
        document.getElementById('deleteModal').classList.add('open');
    }
    function closeDeleteModal(){ document.getElementById('deleteModal').classList.remove('open'); }
    document.getElementById('addModal').addEventListener('click',    function(e){ if(e.target===this) closeAddModal(); });
    document.getElementById('deleteModal').addEventListener('click', function(e){ if(e.target===this) closeDeleteModal(); });
</script>
</body>
</html>