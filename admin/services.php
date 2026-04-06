

<?php
session_start();
if (!isset($_SESSION['jt_admin'])) { header('Location: login.php'); exit; }

require_once '../db.php';

$tab = isset($_GET['tab']) && $_GET['tab']==='gallery' ? 'gallery' : 'services';
$message = ''; $msgType = 'success';

// Fetch admin info (same as index.php)
$ar  = $conn->query("SELECT name,email FROM admins WHERE id=1 LIMIT 1");
$adm = $ar ? $ar->fetch_assoc() : ['name'=>'Admin','email'=>'admin@jettransfer.com'];
$admName  = htmlspecialchars($adm['name']);
$admEmail = htmlspecialchars($adm['email']);
$admInit  = strtoupper(substr($adm['name'],0,1));


if ($_SERVER['REQUEST_METHOD']==='POST' && $tab==='services') {
    $action = $_POST['action'] ?? '';

    if ($action==='delete_service') {
        $id = (int)$_POST['id'];
        $message = $conn->query("DELETE FROM services WHERE id=$id") ? '✅ Service deleted!' : '❌ '.$conn->error;
        if (str_starts_with($message,'❌')) $msgType='error';

    } elseif (in_array($action,['add_service','edit_service'])) {
        $id    = (int)($_POST['id']??0);
        $title = $conn->real_escape_string(trim($_POST['title']??''));
        $desc  = $conn->real_escape_string(trim($_POST['description']??''));
        $modal = $conn->real_escape_string(trim($_POST['modal_text']??''));
        $icon  = $conn->real_escape_string(trim($_POST['icon']??'⭐'));
        $sort  = (int)($_POST['sort_order']??0);
        $ia    = isset($_POST['is_active'])?1:0;

        if ($action==='add_service') {
            $sql="INSERT INTO services(title,description,modal_text,icon,sort_order,is_active)VALUES('$title','$desc','$modal','$icon',$sort,$ia)";
            $message = $conn->query($sql) ? '✅ Service added!' : '❌ '.$conn->error;
        } else {
            $sql="UPDATE services SET title='$title',description='$desc',modal_text='$modal',icon='$icon',sort_order=$sort,is_active=$ia WHERE id=$id";
            $message = $conn->query($sql) ? '✅ Service updated!' : '❌ '.$conn->error;
        }
        if (str_starts_with($message,'❌')) $msgType='error';
    }
}





if ($_SERVER['REQUEST_METHOD']==='POST' && $tab==='gallery') {
    $action = $_POST['action'] ?? '';

    if ($action==='delete_gallery') {
        $id = (int)$_POST['id'];
        $message = $conn->query("DELETE FROM gallery WHERE id=$id") ? '✅ Photo deleted!' : '❌ '.$conn->error;
        if (str_starts_with($message,'❌')) $msgType='error';

    } elseif (in_array($action,['add_gallery','edit_gallery'])) {
        $id      = (int)($_POST['id']??0);
        $title   = $conn->real_escape_string(trim($_POST['title']??''));
        $imgPath = $conn->real_escape_string(trim($_POST['image_path']??''));
        $cat     = $conn->real_escape_string(trim($_POST['category']??'all'));
        $caption = $conn->real_escape_string(trim($_POST['caption']??''));
        $cssClass= $conn->real_escape_string(trim($_POST['css_class']??''));
        $sort    = (int)($_POST['sort_order']??0);
        $ia      = isset($_POST['is_active'])?1:0;

        if ($action==='add_gallery') {
            $sql="INSERT INTO gallery(title,image_path,category,caption,css_class,sort_order,is_active)VALUES('$title','$imgPath','$cat','$caption','$cssClass',$sort,$ia)";
            $message = $conn->query($sql) ? '✅ Photo added!' : '❌ '.$conn->error;
        } else {
            $sql="UPDATE gallery SET title='$title',image_path='$imgPath',category='$cat',caption='$caption',css_class='$cssClass',sort_order=$sort,is_active=$ia WHERE id=$id";
            $message = $conn->query($sql) ? '✅ Photo updated!' : '❌ '.$conn->error;
        }
        if (str_starts_with($message,'❌')) $msgType='error';
    }
}

// ── Edit prefill
$editService=null; $editGallery=null;
if (isset($_GET['edit_s'])) { $r=$conn->query("SELECT * FROM services WHERE id=".(int)$_GET['edit_s']." LIMIT 1"); if($r) $editService=$r->fetch_assoc(); }
if (isset($_GET['edit_g'])) { $r=$conn->query("SELECT * FROM gallery WHERE id=".(int)$_GET['edit_g']." LIMIT 1"); if($r) $editGallery=$r->fetch_assoc(); }

// ── Fetch all
$services=[]; $r=$conn->query("SELECT * FROM services ORDER BY sort_order,id"); if($r) while($row=$r->fetch_assoc()) $services[]=$row;
$gallery=[];  $r=$conn->query("SELECT * FROM gallery ORDER BY sort_order,id");  if($r) while($row=$r->fetch_assoc()) $gallery[]=$row;

$sTotal=count($services); $sActive=count(array_filter($services,fn($s)=>$s['is_active']));
$gTotal=count($gallery);  $gActive=count(array_filter($gallery,fn($g)=>$g['is_active']));

$gCategories=['all','beaches','hillcountry','cultural','wildlife','welcome'];
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Services &amp; Gallery – Jettransfer Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
    --primary:#0A7EA4;
    --primary-dark:#065A7A;
    --primary-light:#E0F2FA;
    --secondary:#F59E0B;
    --accent:#10B981;
    --text-dark:#0F172A;
    --text-light:#94A3B8;
    --bg:#F1F5F9;
    --sidebar-bg:#0F172A;
    --border:#E2E8F0;
    --shadow-sm:0 1px 3px rgba(0,0,0,.06);
    --shadow-md:0 4px 16px rgba(0,0,0,.08);
    --sidebar-w:260px
}
*{
    margin:0;
    padding:0;
    box-sizing:border-box
}
body{
    font-family:'Manrope',sans-serif;
    background:var(--bg);
    color:var(--text-dark);
    min-height:100vh;
    display:flex
}
.sidebar{
    width:var(--sidebar-w);
    background:var(--sidebar-bg);
    min-height:100vh;
    position:fixed;
    left:0;top:0;
    bottom:0;
    display:flex;
    flex-direction:column;
    z-index:100;
    transition:transform .3s ease
}
.sidebar-brand{
    padding:1.8rem 1.5rem 1.5rem;
    border-bottom:1px solid rgba(255,255,255,.07);
    display:flex;
    align-items:center;
    gap:.8rem
}
.sidebar-logo{
    width:42px;
    height:42px;
    background:linear-gradient(135deg,var(--primary),var(--accent));
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:1.3rem;
    flex-shrink:0
}
.sidebar-brand-text h2{
    font-family:'Sora',sans-serif;
    font-size:1rem;
    font-weight:800;
    color:#fff
}
.sidebar-brand-text span{
    font-size:.72rem;
    color:rgba(255,255,255,.4);
    letter-spacing:1.5px;
    text-transform:uppercase
}
.sidebar-nav{
    flex:1;padding:1.2rem .8rem;
    overflow-y:auto
}
.nav-label{
    font-size:.68rem;
    font-weight:700;
    letter-spacing:2px;
    text-transform:uppercase;
    color:rgba(255,255,255,.25);
    padding:.5rem .8rem;
    margin-top:.8rem;
    margin-bottom:.3rem
}
.nav-item{
    display:flex;
    align-items:center;
    gap:.75rem;
    padding:.75rem .9rem;
    border-radius:12px;
    color:rgba(255,255,255,.55);
    text-decoration:none;
    font-size:.9rem;
    font-weight:500;
    transition:all .2s ease;
    margin-bottom:.2rem
}
.nav-item:hover{
    background:rgba(255,255,255,.07);
    color:rgba(255,255,255,.9)
}
.nav-item.active{
    background:linear-gradient(135deg,rgba(10,126,164,.35),rgba(16,185,129,.2));
    color:#fff;
    box-shadow:inset 0 0 0 1px rgba(10,126,164,.4)
}
.nav-icon{
    width:20px;
    height:20px;
    flex-shrink:0
}
.sidebar-footer{
    padding:1rem .8rem 1.5rem;
    border-top:1px solid rgba(255,255,255,.07)
}
.admin-profile{
    display:flex;
    align-items:center;
    gap:.75rem;
    padding:.75rem .9rem;
    border-radius:12px;
    background:rgba(255,255,255,.05);
    margin-bottom:.5rem
}
.admin-avatar{
    width:36px;
    height:36px;
    background:linear-gradient(135deg,var(--primary),var(--accent));
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:.9rem;
    font-weight:700;
    color:#fff;
    flex-shrink:0
}
.admin-info h4{
    font-size:.85rem;
    font-weight:600;
    color:#fff
}
.admin-info p{
    font-size:.72rem;
    color:rgba(255,255,255,.4)
}
.btn-logout{
    display:flex;
    align-items:center;
    gap:.6rem;
    padding:.7rem .9rem;
    border-radius:12px;
    color:rgba(255,255,255,.5);
    font-size:.88rem;
    font-weight:500;
    cursor:pointer;
    transition:all .2s;
    border:none;
    background:none;
    width:100%;
    font-family:'Manrope',sans-serif;
    text-decoration:none
}
.btn-logout:hover{
    background:rgba(239,68,68,.15);
    color:#FCA5A5
}
.sidebar-overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.5);
    z-index:99
}
.sidebar-overlay.show{
    display:block
}
.main{
    margin-left:var(--sidebar-w);
    flex:1;
    display:flex;
    flex-direction:column
}
.topbar{
    background:#fff;
    border-bottom:1px solid var(--border);
    padding:0 2rem;
    height:70px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    position:sticky;
    top:0;
    z-index:50;
    box-shadow:var(--shadow-sm)
}
.topbar-left{
    display:flex;
    align-items:center;
    gap:1rem
}
.hamburger{
    display:none;
    background:none;
    border:none;
    cursor:pointer;
    padding:6px;
    color:var(--text-dark)
}
.page-title h1{
    font-family:'Sora',sans-serif;
    font-size:1.2rem;
    font-weight:700
}
.page-title p{
    font-size:.8rem;
    color:var(--text-light)
}
.topbar-avatar{
    width:40px;
    height:40px;
    background:linear-gradient(135deg,var(--primary),var(--accent));
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    font-weight:700;
    font-size:.9rem
}
.content{
    padding:2rem;
    flex:1
}
/* TABS */
.tab-bar{
    display:flex;
    gap:.5rem;
    margin-bottom:1.8rem;
    background:#fff;
    border-radius:14px;
    padding:.5rem;
    border:1px solid var(--border);
    box-shadow:var(--shadow-sm);
    width:fit-content
}
.tab-btn{
    padding:.6rem 1.5rem;
    border-radius:10px;
    border:none;
    background:transparent;
    font-family:'Manrope',sans-serif;
    font-size:.88rem;
    font-weight:600;
    color:var(--text-light);
    cursor:pointer;
    transition:all .2s;
    text-decoration:none;
    display:flex;
    align-items:center;
    gap:.4rem
}
.tab-btn:hover{
    color:var(--text-dark);
    background:var(--bg)
}
.tab-btn.on{
    background:var(--primary);
    color:#fff;
    box-shadow:0 2px 8px rgba(10,126,164,.3)
}
/* STATS */
.stat-row{
    display:flex;
    gap:.7rem;
    margin-bottom:1.5rem;
    flex-wrap:wrap
}
.chip{
    background:#fff;
    border:1px solid var(--border);
    border-radius:50px;
    padding:.38rem .95rem;
    font-size:.78rem;
    font-weight:600;
    color:var(--text-dark)
}
.chip .v{
    font-weight:800;
    margin-right:.2rem
}
.chip .v.bl{
    color:var(--primary)
}
.chip .v.gn{
    color:var(--accent)
}
.chip .v.rd{
    color:#EF4444
}
/* ALERT */
.alert{
    padding:.75rem 1rem;
    border-radius:10px;
    margin-bottom:1.3rem;
    font-size:.86rem;
    font-weight:600
}
.alert.success{
    background:#D1FAE5;
    color:#065F46;
    border:1px solid #A7F3D0
}
.alert.error{
    background:#FEE2E2;
    color:#991B1B;
    border:1px solid #FECACA
}
/* LAYOUT */
.pg-layout{
    display:grid;
    grid-template-columns:370px 1fr;
    gap:1.5rem;
    align-items:start
}
@media(max-width:1100px){
    .pg-layout{
        grid-template-columns:1fr
    }
}
/* FORM CARD */
.card{
    background:#fff;
    border-radius:18px;
    padding:1.6rem;
    box-shadow:var(--shadow-sm);
    border:1px solid var(--border)
}
.card-title{
    font-family:'Sora',sans-serif;
    font-size:1rem;
    font-weight:700;
    color:var(--text-dark);
    margin-bottom:1.2rem;
    display:flex;
    align-items:center;
    gap:.4rem
}
.fg{
    margin-bottom:.9rem
}
.fg label{
    display:block;
    font-size:.74rem;
    font-weight:700;
    color:#475569;
    margin-bottom:.35rem;
    text-transform:uppercase;
    letter-spacing:.5px
}
.fg input[type=text],.fg select,.fg textarea{
    width:100%;
    padding:.58rem .88rem;
    border:1.5px solid var(--border);
    border-radius:9px;
    font-family:'Manrope',sans-serif;
    font-size:.87rem;
    color:var(--text-dark);
    outline:none;
    transition:border-color .25s;
    background:#fff
}
.fg input[type=text]:focus,.fg select:focus,.fg textarea:focus{
    border-color:var(--primary);
    box-shadow:0 0 0 3px rgba(10,126,164,.07)
}
.fg textarea{
    resize:vertical;
    min-height:70px
}
.row2{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:.8rem
}
.icon-row{
    display:flex;
    gap:.5rem;
    align-items:center
}
.icon-row input{
    flex:1
}
.icon-preview{
    width:42px;
    height:42px;
    border-radius:10px;
    background:var(--bg);
    border:1.5px solid var(--border);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:1.4rem;
    flex-shrink:0
}
.chk-label{
    display:flex;
    align-items:center;
    gap:.45rem;
    font-size:.87rem;
    font-weight:600;
    cursor:pointer
}
.chk-label input{
    width:auto;
    accent-color:var(--primary)
}
.btn-save{
    width:100%;
    padding:.68rem;
    background:linear-gradient(135deg,var(--primary),var(--primary-dark));
    color:#fff;
    border:none;
    border-radius:10px;
    font-family:'Manrope',sans-serif;
    font-size:.9rem;
    font-weight:700;
    cursor:pointer;
    margin-top:.4rem;
    transition:all .25s
}
.btn-save:hover{
    opacity:.9;
    transform:translateY(-1px)
}
.btn-cancel-edit{
    display:block;
    text-align:center;
    margin-top:.55rem;
    color:var(--text-light);
    font-size:.79rem;
    text-decoration:none;
    padding:.32rem;
    border-radius:7px;
    transition:background .2s
}
.btn-cancel-edit:hover{
    background:var(--bg)
}
/* TABLE */
.tcard{
    background:#fff;
    border-radius:18px;
    box-shadow:var(--shadow-sm);
    border:1px solid var(--border);
    overflow:hidden
}
.tcard-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:1.1rem 1.4rem;
    border-bottom:1px solid var(--border)
}
.tcard-top h3{
    font-family:'Sora',sans-serif;
    font-size:.95rem;
    font-weight:700
}
.tbl-search{
    padding:.65rem 1.4rem;
    border-bottom:1px solid var(--border)
}
.tbl-search input{
    width:100%;
    padding:.5rem .88rem;
    border:1.5px solid var(--border);
    border-radius:50px;
    font-family:'Manrope',sans-serif;
    font-size:.85rem;
    outline:none;
    transition:border-color .25s
}
.tbl-search input:focus{
    border-color:var(--primary)
}
.gal-filters{
    display:flex;
    gap:.45rem;
    padding:.65rem 1.4rem;
    border-bottom:1px solid var(--border);
    flex-wrap:wrap
}
.gfb{
    padding:.27rem .75rem;
    border-radius:50px;
    border:1.5px solid var(--border);
    background:#fff;
    color:#64748B;
    font-family:'Manrope',sans-serif;
    font-size:.74rem;
    font-weight:600;
    cursor:pointer;
    transition:all .2s
}
.gfb:hover,.gfb.on{
    border-color:var(--primary);
    background:var(--primary);
    color:#fff
}
.tbl-wrap{
    overflow-x:auto
}
.tbl{
    width:100%;
    border-collapse:collapse;
    font-size:.83rem
}
.tbl th{
    text-align:left;
    font-size:.69rem;
    font-weight:700;
    color:var(--text-light);
    text-transform:uppercase;
    letter-spacing:.7px;
    padding:.75rem 1.1rem;
    background:#F8FAFC;
    border-bottom:1px solid var(--border);
    white-space:nowrap
}
.tbl td{
    padding:.82rem 1.1rem;
    border-bottom:1px solid #F1F5F9;
    vertical-align:middle
}
.tbl tbody tr:last-child td{
    border-bottom:none
}
.tbl tbody tr:hover td{
    background:#FAFBFD
}
.svc-ico{
    width:40px;
    height:40px;
    border-radius:10px;
    background:var(--primary-light);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:1.2rem
}
.gal-thumb{
    width:58px;
    height:42px;
    object-fit:cover;
    border-radius:7px;
    border:1px solid var(--border)
}
.badge{
    display:inline-block;
    padding:.17rem .58rem;
    border-radius:20px;
    font-size:.69rem;
    font-weight:700
}
.badge.act{
    background:#D1FAE5;
    color:#065F46
}
.badge.hid{
    background:#FEE2E2;
    color:#991B1B
}
.badge.cat{
    background:var(--primary-light);
    color:var(--primary-dark)
}
.al{
    color:var(--primary);
    font-weight:600;
    font-size:.79rem;
    text-decoration:none;
    cursor:pointer;
    background:none;
    border:none;
    font-family:'Manrope',sans-serif;
    padding:.22rem .52rem;
    border-radius:6px;
    transition:background .15s
}
.al:hover{
    background:var(--primary-light)
}
.al.del{
    color:#EF4444
}
.al.del:hover{
    background:#FEE2E2
}
.empty-msg td{
    text-align:center;
    padding:2.5rem;
    color:var(--text-light);
    font-size:.88rem
}
.thumb-wrap{
    margin-bottom:.9rem;
    display:none
}
.thumb-wrap img{
    width:100%;
    max-height:130px;
    object-fit:cover;
    border-radius:10px;
    border:1.5px solid var(--border)
}
@media(max-width:768px){
    .sidebar{
        transform:translateX(-100%)
        }
        .sidebar.open{
            transform:translateX(0)
            }
            .main{
                margin-left:0
                }
                .hamburger{
                    display:flex
                    }
}
</style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
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
        <a href="services.php" class="nav-item active">
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
                        

<div class="main">
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger" onclick="toggleSidebar()"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
            <div class="page-title"><h1>Services &amp; Gallery</h1><p>Jettransfer Admin Panel</p></div>
        </div>
        <div class="topbar-right"><div class="topbar-avatar">A</div></div>
    </header>

    <div class="content">

        <!-- TABS -->
        <div class="tab-bar">
            <a href="services.php?tab=services" class="tab-btn <?= $tab==='services'?'on':'' ?>">🛎️ Services (<?= $sTotal ?>)</a>
            <a href="services.php?tab=gallery"  class="tab-btn <?= $tab==='gallery'?'on':'' ?>">🖼️ Gallery (<?= $gTotal ?>)</a>
        </div>

        <?php if($message): ?>
        <div class="alert <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- ════════ SERVICES TAB ════════ -->
        <?php if($tab==='services'): ?>
        <div class="stat-row">
            <div class="chip"><span class="v bl"><?= $sTotal ?></span>Total</div>
            <div class="chip"><span class="v gn"><?= $sActive ?></span>Active</div>
            <div class="chip"><span class="v rd"><?= $sTotal-$sActive ?></span>Hidden</div>
        </div>
        <div class="pg-layout">
            <!-- Form -->
            <div class="card">
                <div class="card-title"><?= $editService?'✏️ Edit Service':'➕ Add Service' ?></div>
                <form method="POST" action="services.php?tab=services<?= $editService?'&edit_s='.$editService['id']:'' ?>">
                    <input type="hidden" name="action" value="<?= $editService?'edit_service':'add_service' ?>">
                    <?php if($editService): ?><input type="hidden" name="id" value="<?= $editService['id'] ?>"><?php endif; ?>
                    <div class="fg"><label>Title *</label><input type="text" name="title" required value="<?= htmlspecialchars($editService['title']??'') ?>" placeholder="e.g. Airport Transfers"></div>
                    <div class="fg"><label>Description * (shown on card)</label><textarea name="description" required placeholder="Short description shown on service card…"><?= htmlspecialchars($editService['description']??'') ?></textarea></div>
                    <div class="fg"><label>Modal Text (shown in popup)</label><textarea name="modal_text" placeholder="Detailed info shown when user clicks Learn More…"><?= htmlspecialchars($editService['modal_text']??'') ?></textarea></div>
                    <div class="row2">
                        <div class="fg">
                            <label>Icon (Emoji)</label>
                            <div class="icon-row">
                                <input type="text" name="icon" id="iconInp" value="<?= htmlspecialchars($editService['icon']??'⭐') ?>" placeholder="✈️" oninput="document.getElementById('iconPrev').textContent=this.value||'⭐'">
                                <div class="icon-preview" id="iconPrev"><?= htmlspecialchars($editService['icon']??'⭐') ?></div>
                            </div>
                        </div>
                        <div class="fg"><label>Sort Order</label><input type="text" name="sort_order" value="<?= htmlspecialchars((string)($editService['sort_order']??0)) ?>" placeholder="0"></div>
                    </div>
                    <div class="fg"><label class="chk-label"><input type="checkbox" name="is_active" value="1" <?= (!isset($editService)||$editService['is_active'])?'checked':'' ?>> Active (visible on website)</label></div>
                    <button type="submit" class="btn-save"><?= $editService?'💾 Save Changes':'➕ Add Service' ?></button>
                    <?php if($editService): ?><a href="services.php?tab=services" class="btn-cancel-edit">✕ Cancel editing</a><?php endif; ?>
                </form>
            </div>
            <!-- Table -->
            <div class="tcard">
                <div class="tcard-top"><h3>All Services (<?= $sTotal ?>)</h3></div>
                <div class="tbl-search"><input type="text" id="svcSrch" placeholder="🔍 Search services…" oninput="filterTbl('svcTbody',this.value)"></div>
                <div class="tbl-wrap">
                    <table class="tbl"><thead><tr><th>#</th><th>Icon</th><th>Title</th><th>Description</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody id="svcTbody">
                    <?php if(empty($services)): ?><tr class="empty-msg"><td colspan="7">No services yet.</td></tr>
                    <?php else: foreach($services as $s): ?>
                    <tr data-srch="<?= htmlspecialchars(strtolower($s['title'].' '.$s['description'])) ?>">
                        <td style="color:var(--text-light);font-size:.76rem"><?= $s['id'] ?></td>
                        <td><div class="svc-ico"><?= htmlspecialchars($s['icon']) ?></div></td>
                        <td style="font-weight:600"><?= htmlspecialchars($s['title']) ?></td>
                        <td style="color:#64748B;font-size:.8rem;max-width:200px"><span style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= htmlspecialchars($s['description']) ?></span></td>
                        <td style="text-align:center;color:var(--text-light)"><?= $s['sort_order'] ?></td>
                        <td><span class="badge <?= $s['is_active']?'act':'hid' ?>"><?= $s['is_active']?'Active':'Hidden' ?></span></td>
                        <td>
                            <a href="services.php?tab=services&edit_s=<?= $s['id'] ?>" class="al">✏️ Edit</a>
                            <form method="POST" action="services.php?tab=services" style="display:inline" onsubmit="return confirm('Delete \'<?= htmlspecialchars(addslashes($s['title'])) ?>\'?')">
                                <input type="hidden" name="action" value="delete_service"><input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button type="submit" class="al del">🗑 Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody></table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ════════ GALLERY TAB ════════ -->
        <?php if($tab==='gallery'): ?>
        <div class="stat-row">
            <div class="chip"><span class="v bl"><?= $gTotal ?></span>Total</div>
            <div class="chip"><span class="v gn"><?= $gActive ?></span>Active</div>
            <div class="chip"><span class="v rd"><?= $gTotal-$gActive ?></span>Hidden</div>
        </div>
        <div class="pg-layout">
            <!-- Form -->
            <div class="card">
                <div class="card-title"><?= $editGallery?'✏️ Edit Photo':'➕ Add Photo' ?></div>
                <form method="POST" action="services.php?tab=gallery<?= $editGallery?'&edit_g='.$editGallery['id']:'' ?>">
                    <input type="hidden" name="action" value="<?= $editGallery?'edit_gallery':'add_gallery' ?>">
                    <?php if($editGallery): ?><input type="hidden" name="id" value="<?= $editGallery['id'] ?>"><?php endif; ?>
                    <div class="fg"><label>Photo Title *</label><input type="text" name="title" required value="<?= htmlspecialchars($editGallery['title']??'') ?>" placeholder="e.g. Mirissa Beach"></div>
                    <div class="fg">
                        <label>Image Path * (relative to project root)</label>
                        <input type="text" name="image_path" required id="imgPathInp"
                               value="<?= htmlspecialchars($editGallery['image_path']??'') ?>"
                               placeholder="img/mirissa1.jpeg"
                               oninput="updatePreview(this.value)">
                    </div>
                    <!-- Live preview -->
                    <div class="thumb-wrap" id="thumbWrap" style="<?= ($editGallery&&$editGallery['image_path'])?'display:block':'' ?>">
                        <img id="thumbPrev" src="<?= $editGallery?'../'.$editGallery['image_path']:'' ?>" onerror="document.getElementById('thumbWrap').style.display='none'">
                    </div>
                    <div class="row2">
                        <div class="fg">
                            <label>Category *</label>
                            <select name="category" required>
                                <?php foreach($gCategories as $gc): ?>
                                <option value="<?= $gc ?>" <?= (isset($editGallery['category'])&&$editGallery['category']===$gc)?'selected':'' ?>><?= ucfirst($gc) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="fg">
                            <label>CSS Class</label>
                            <select name="css_class">
                                <option value="" <?= (isset($editGallery['css_class'])&&$editGallery['css_class']==='')?'selected':'' ?>>Normal</option>
                                <option value="tall" <?= (isset($editGallery['css_class'])&&$editGallery['css_class']==='tall')?'selected':'' ?>>Tall (2 rows)</option>
                                <option value="wide" <?= (isset($editGallery['css_class'])&&$editGallery['css_class']==='wide')?'selected':'' ?>>Wide (2 cols)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row2">
                        <div class="fg"><label>Caption</label><input type="text" name="caption" value="<?= htmlspecialchars($editGallery['caption']??'') ?>" placeholder="e.g. Beaches"></div>
                        <div class="fg"><label>Sort Order</label><input type="text" name="sort_order" value="<?= htmlspecialchars((string)($editGallery['sort_order']??0)) ?>" placeholder="0"></div>
                    </div>
                    <div class="fg"><label class="chk-label"><input type="checkbox" name="is_active" value="1" <?= (!isset($editGallery)||$editGallery['is_active'])?'checked':'' ?>> Active (visible on website)</label></div>
                    <button type="submit" class="btn-save"><?= $editGallery?'💾 Save Changes':'➕ Add Photo' ?></button>
                    <?php if($editGallery): ?><a href="services.php?tab=gallery" class="btn-cancel-edit">✕ Cancel editing</a><?php endif; ?>
                </form>
            </div>
            <!-- Table -->
            <div class="tcard">
                <div class="tcard-top"><h3>All Photos (<?= $gTotal ?>)</h3></div>
                <div class="tbl-search"><input type="text" id="galSrch" placeholder="🔍 Search gallery…" oninput="filterGal(this.value)"></div>
                <div class="gal-filters">
                    <button class="gfb on" data-cat="all" onclick="setGalCat(this)">All</button>
                    <?php foreach($gCategories as $gc): if($gc==='all') continue; ?>
                    <button class="gfb" data-cat="<?= $gc ?>" onclick="setGalCat(this)"><?= ucfirst($gc) ?></button>
                    <?php endforeach; ?>
                </div>
                <div class="tbl-wrap">
                    <table class="tbl"><thead><tr><th>#</th><th>Img</th><th>Title</th><th>Category</th><th>Class</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody id="galTbody">
                    <?php if(empty($gallery)): ?><tr class="empty-msg"><td colspan="7">No photos yet.</td></tr>
                    <?php else: foreach($gallery as $g): ?>
                    <tr data-cat="<?= htmlspecialchars($g['category']) ?>"
                        data-srch="<?= htmlspecialchars(strtolower($g['title'].' '.$g['category'].' '.($g['caption']??''))) ?>">
                        <td style="color:var(--text-light);font-size:.76rem"><?= $g['id'] ?></td>
                        <td><?php if($g['image_path']): ?><img src="../<?= htmlspecialchars($g['image_path']) ?>" class="gal-thumb" onerror="this.style.display='none'"><?php else: ?>—<?php endif; ?></td>
                        <td style="font-weight:600;max-width:140px"><span style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= htmlspecialchars($g['title']) ?></span></td>
                        <td><span class="badge cat"><?= htmlspecialchars($g['category']) ?></span></td>
                        <td style="color:var(--text-light);font-size:.8rem"><?= $g['css_class']?htmlspecialchars($g['css_class']):'normal' ?></td>
                        <td><span class="badge <?= $g['is_active']?'act':'hid' ?>"><?= $g['is_active']?'Active':'Hidden' ?></span></td>
                        <td>
                            <a href="services.php?tab=gallery&edit_g=<?= $g['id'] ?>" class="al">✏️ Edit</a>
                            <form method="POST" action="services.php?tab=gallery" style="display:inline" onsubmit="return confirm('Delete \'<?= htmlspecialchars(addslashes($g['title'])) ?>\'?')">
                                <input type="hidden" name="action" value="delete_gallery"><input type="hidden" name="id" value="<?= $g['id'] ?>">
                                <button type="submit" class="al del">🗑 Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody></table>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show')}
document.getElementById('sidebarOverlay').addEventListener('click',()=>{document.getElementById('sidebar').classList.remove('open');document.getElementById('sidebarOverlay').classList.remove('show')});

// Image live preview
function updatePreview(val){
    const wrap=document.getElementById('thumbWrap');
    const img=document.getElementById('thumbPrev');
    if(!wrap||!img) return;
    if(val.trim()){img.src='../'+val.trim();wrap.style.display='block';}
    else wrap.style.display='none';
}

// Search filter
function filterTbl(tbodyId,q){
    q=(q||'').toLowerCase().trim();
    let n=0;
    document.querySelectorAll('#'+tbodyId+' tr[data-srch]').forEach(r=>{
        const show=!q||(r.dataset.srch||'').includes(q);
        r.style.display=show?'':'none';
        if(show)n++;
    });
    const eid=tbodyId+'Empty';let em=document.getElementById(eid);
    if(n===0){if(!em){em=document.createElement('tr');em.id=eid;em.innerHTML='<td colspan="7" style="text-align:center;padding:2rem;color:#94A3B8">No results found.</td>';document.getElementById(tbodyId).appendChild(em);}em.style.display='';}
    else if(em) em.style.display='none';
}

// Gallery cat filter
let galCat='all';
function setGalCat(btn){
    document.querySelectorAll('.gfb').forEach(b=>b.classList.remove('on'));
    btn.classList.add('on'); galCat=btn.dataset.cat; filterGal(document.getElementById('galSrch').value);
}
function filterGal(q){
    q=(q||'').toLowerCase().trim();
    let n=0;
    document.querySelectorAll('#galTbody tr[data-cat]').forEach(r=>{
        const mc=galCat==='all'||r.dataset.cat===galCat;
        const mq=!q||(r.dataset.srch||'').includes(q);
        r.style.display=(mc&&mq)?'':'none';
        if(mc&&mq)n++;
    });
    let em=document.getElementById('galTbodyEmpty');
    if(n===0){if(!em){em=document.createElement('tr');em.id='galTbodyEmpty';em.innerHTML='<td colspan="7" style="text-align:center;padding:2rem;color:#94A3B8">No results.</td>';document.getElementById('galTbody').appendChild(em);}em.style.display='';}
    else if(em) em.style.display='none';
}
</script>
</body>
</html> 