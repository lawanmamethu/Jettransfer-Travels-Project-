<?php
// ── profile.php ───────────────────────────────────────────────
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

require_once 'db.php';
$uid     = (int)$_SESSION['user_id'];
$msg     = '';
$msgType = 'success';

// ── Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_pic') {
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['profile_pic'];
        $allowed  = ['image/jpeg','image/jpg','image/png','image/webp','image/gif'];
        $maxSize  = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowed)) {
            $msg = '❌ Only JPG, PNG, WEBP or GIF images are allowed.'; $msgType = 'error';
        } elseif ($file['size'] > $maxSize) {
            $msg = '❌ Image must be under 2MB.'; $msgType = 'error';
        } else {
            $uploadDir = 'uploads/profiles/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            // Delete old pic if exists
            $oldR = $conn->query("SELECT profile_pic FROM users WHERE id=$uid LIMIT 1");
            $oldRow = $oldR ? $oldR->fetch_assoc() : [];
            if (!empty($oldRow['profile_pic']) && file_exists($oldRow['profile_pic'])) {
                unlink($oldRow['profile_pic']);
            }

            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $uid . '_' . time() . '.' . $ext;
            $dest     = $uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $escaped = $conn->real_escape_string($dest);
                $conn->query("UPDATE users SET profile_pic='$escaped' WHERE id=$uid");
                $msg = '✅ Profile picture updated!';
            } else {
                $msg = '❌ Upload failed. Please try again.'; $msgType = 'error';
            }
        }
    } else {
        $msg = '❌ No file selected.'; $msgType = 'error';
    }
}

// ── Handle remove profile picture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_pic') {
    $oldR = $conn->query("SELECT profile_pic FROM users WHERE id=$uid LIMIT 1");
    $oldRow = $oldR ? $oldR->fetch_assoc() : [];
    if (!empty($oldRow['profile_pic']) && file_exists($oldRow['profile_pic'])) {
        unlink($oldRow['profile_pic']);
    }
    $conn->query("UPDATE users SET profile_pic=NULL WHERE id=$uid");
    $msg = '✅ Profile picture removed.';
}

// Handle password change POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'chgpass') {
    $cur  = $_POST['current_password']  ?? '';
    $new  = $_POST['new_password']      ?? '';
    $conf = $_POST['confirm_password']  ?? '';
    $r    = $conn->query("SELECT password_hash FROM users WHERE id=$uid LIMIT 1");
    $row  = $r ? $r->fetch_assoc() : [];
    if (!password_verify($cur, $row['password_hash'] ?? '')) {
        $msg = '❌ Current password is incorrect.';  $msgType = 'error';
    } elseif (strlen($new) < 6) {
        $msg = '❌ New password must be at least 6 characters.'; $msgType = 'error';
    } elseif ($new !== $conf) {
        $msg = '❌ Passwords do not match.'; $msgType = 'error';
    } else {
        $h = $conn->real_escape_string(password_hash($new, PASSWORD_DEFAULT));
        $conn->query("UPDATE users SET password_hash='$h' WHERE id=$uid");
        $msg = '✅ Password changed successfully!';
    }
}

$r    = $conn->query("SELECT * FROM users WHERE id=$uid LIMIT 1");
$user = $r ? $r->fetch_assoc() : [];

// Derive display values
$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$parts    = explode(' ', $fullName);
$initials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
$_SESSION['user_name'] = $fullName;

$profilePic = !empty($user['profile_pic']) && file_exists($user['profile_pic'])
    ? htmlspecialchars($user['profile_pic'])
    : null;

// Active tab
$tab = $_GET['tab'] ?? 'personal';
if (!in_array($tab, ['personal','edit','security','bookings','preferences'])) $tab = 'personal';

// Success message from edit redirect
if (isset($_GET['updated'])) $msg = '✅ Profile updated successfully!';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile – Jettransfer Travels</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--primary:#0A7EA4;--primary-dark:#065A7A;--secondary:#F59E0B;--accent:#10B981;--text-dark:#0F172A;--text-light:#64748B;--bg-light:#F8FAFC;--white:#FFFFFF;--shadow-sm:0 1px 3px rgba(0,0,0,.08);--shadow-md:0 4px 12px rgba(0,0,0,.1);--shadow-lg:0 10px 40px rgba(0,0,0,.15)}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Manrope',sans-serif;color:var(--text-dark);background:var(--bg-light)}
/* ─── Header ─── */
.header{position:fixed;top:0;left:0;right:0;background:rgba(255,255,255,.95);backdrop-filter:blur(10px);box-shadow:var(--shadow-sm);z-index:1000;transition:all .3s}
.header.scrolled{box-shadow:var(--shadow-md)}
.nav-container{max-width:1400px;margin:0 auto;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center}
.logo{font-family:'Sora',sans-serif;font-size:1.3rem;font-weight:800;color:var(--primary);text-decoration:none;display:flex;align-items:center;gap:.6rem;white-space:nowrap}
.logo-img{width:42px;height:42px;border-radius:12px;object-fit:cover;background:#fff}
.nav-menu{display:flex;list-style:none;gap:1.6rem;align-items:center}
.nav-menu a{color:var(--text-dark);text-decoration:none;font-weight:500;font-size:.88rem;transition:color .3s;position:relative}
.nav-menu a::after{content:'';position:absolute;bottom:-5px;left:0;width:0;height:2px;background:var(--primary);transition:width .3s}
.nav-menu a:hover::after,.nav-menu a.active::after{width:100%}
.nav-menu a:hover,.nav-menu a.active{color:var(--primary)}
.nav-actions{display:flex;align-items:center;gap:.6rem;flex-shrink:0}
/* ─── Search ─── */
.nav-search{position:relative;display:flex;align-items:center}
.search-toggle{width:38px;height:38px;border-radius:50%;border:2px solid #E2E8F0;background:transparent;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text-dark);transition:all .3s}
.search-toggle:hover{border-color:var(--primary);color:var(--primary)}
.search-box{position:absolute;right:0;top:50%;transform:translateY(-50%);display:flex;align-items:center;background:white;border:2px solid var(--primary);border-radius:50px;overflow:hidden;width:0;opacity:0;pointer-events:none;transition:width .4s,opacity .3s;box-shadow:var(--shadow-md);z-index:10}
.search-box.open{width:280px;opacity:1;pointer-events:all}
.search-box input{border:none;outline:none;padding:.55rem 1rem;font-family:'Manrope',sans-serif;font-size:.88rem;width:100%;background:transparent}
.search-submit{background:var(--primary);border:none;padding:.55rem 1rem;color:white;cursor:pointer;display:flex;align-items:center;flex-shrink:0}
/* ─── Profile dropdown ─── */
.profile-dropdown{position:relative}
.profile-pill{display:flex;align-items:center;gap:.5rem;padding:.4rem .9rem .4rem .45rem;border-radius:50px;border:2px solid #E2E8F0;background:white;cursor:pointer;font-family:'Manrope',sans-serif;font-weight:600;font-size:.88rem;color:var(--text-dark);transition:all .25s;white-space:nowrap}
.profile-pill:hover{border-color:var(--primary);box-shadow:0 2px 12px rgba(10,126,164,.15)}
.pill-avatar{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#fff;flex-shrink:0;overflow:hidden}
.pill-avatar img{width:100%;height:100%;object-fit:cover;border-radius:50%}
.pill-name{max-width:110px;overflow:hidden;text-overflow:ellipsis}
.dropdown-menu{position:absolute;right:0;top:calc(100% + .6rem);background:white;border:1px solid #E2E8F0;border-radius:18px;box-shadow:0 10px 40px rgba(0,0,0,.12);min-width:230px;z-index:1001;opacity:0;transform:translateY(-8px) scale(.97);pointer-events:none;transition:all .22s ease}
.dropdown-menu.open{opacity:1;transform:translateY(0) scale(1);pointer-events:all}
.dd-header{display:flex;align-items:center;gap:.75rem;padding:1rem 1.1rem .8rem}
.dd-avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:700;color:#fff;flex-shrink:0;overflow:hidden}
.dd-avatar img{width:100%;height:100%;object-fit:cover;border-radius:50%}
.dd-name{font-weight:700;font-size:.92rem;color:var(--text-dark);margin-bottom:.15rem}
.dd-email{font-size:.73rem;color:#94A3B8;overflow:hidden;text-overflow:ellipsis;max-width:155px}
.dd-divider{height:1px;background:#F1F5F9;margin:.3rem 0}
.dd-item{display:flex;align-items:center;gap:.6rem;padding:.65rem 1rem;color:var(--text-dark);text-decoration:none;font-size:.88rem;font-weight:500;transition:background .15s;border-radius:10px;margin:.1rem .4rem}
.dd-item:hover{background:#F1F5F9;color:var(--primary)}
.dd-logout{color:#EF4444 !important}
.dd-logout:hover{background:#FEE2E2 !important;color:#DC2626 !important}
/* Mobile */
.mobile-toggle{display:none;flex-direction:column;gap:5px;cursor:pointer;padding:8px}
.mobile-toggle span{width:25px;height:3px;background:var(--text-dark);border-radius:2px}
.mobile-auth{display:none}
/* ─── Page header ─── */
.page-header{margin-top:80px;padding:3rem 2rem 2rem;background:linear-gradient(135deg,rgba(10,126,164,.05),rgba(16,185,129,.05));text-align:center}
.page-title{font-family:'Sora',sans-serif;font-size:2.8rem;font-weight:800;margin-bottom:.5rem;animation:fadeInUp .8s ease}
.page-description{font-size:1.1rem;color:var(--text-light);max-width:700px;margin:0 auto;animation:fadeInUp .8s ease .2s both}
@keyframes fadeInUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
/* ─── Profile layout ─── */
.profile-section{max-width:1200px;margin:3rem auto 5rem;padding:0 2rem}
.profile-grid{display:grid;grid-template-columns:300px 1fr;gap:2rem}
/* Sidebar */
.sidebar{background:white;border-radius:28px;box-shadow:var(--shadow-md);padding:2rem;height:fit-content;animation:fadeInUp .8s ease .2s both}
.sb-avatar{text-align:center;margin-bottom:1.5rem}

/* ── Profile picture area ── */
.sb-pic-wrap{position:relative;width:110px;height:110px;margin:0 auto 1rem}
.sb-circle{width:110px;height:110px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:white;font-weight:700;box-shadow:0 8px 25px rgba(10,126,164,.3);overflow:hidden}
.sb-circle img{width:100%;height:100%;object-fit:cover;border-radius:50%}
.sb-pic-overlay{position:absolute;inset:0;border-radius:50%;background:rgba(0,0,0,0);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .25s}
.sb-pic-wrap:hover .sb-pic-overlay{background:rgba(0,0,0,.45)}
.sb-pic-overlay span{color:white;font-size:.72rem;font-weight:700;opacity:0;transition:opacity .25s;text-align:center;line-height:1.4;padding:.3rem}
.sb-pic-wrap:hover .sb-pic-overlay span{opacity:1}
.sb-pic-input{display:none}

/* Upload buttons below avatar */
.sb-pic-btns{display:flex;justify-content:center;gap:.5rem;margin-bottom:.8rem;flex-wrap:wrap}
.btn-pic-upload{display:inline-flex;align-items:center;gap:.3rem;padding:.3rem .75rem;border-radius:50px;border:1.5px solid var(--primary);background:white;color:var(--primary);font-family:'Manrope',sans-serif;font-size:.74rem;font-weight:700;cursor:pointer;transition:all .2s}
.btn-pic-upload:hover{background:var(--primary);color:white}
.btn-pic-remove{display:inline-flex;align-items:center;gap:.3rem;padding:.3rem .75rem;border-radius:50px;border:1.5px solid #EF4444;background:white;color:#EF4444;font-family:'Manrope',sans-serif;font-size:.74rem;font-weight:700;cursor:pointer;transition:all .2s}
.btn-pic-remove:hover{background:#EF4444;color:white}

.sb-name{font-family:'Sora',sans-serif;font-size:1.3rem;font-weight:700;margin-bottom:.25rem}
.sb-email{color:var(--text-light);font-size:.88rem;margin-bottom:1rem}
.sb-stats{display:flex;justify-content:center;gap:1.5rem;padding:1rem 0;border-top:2px solid var(--bg-light);border-bottom:2px solid var(--bg-light);margin-bottom:1.5rem}
.sb-stat-val{font-family:'Sora',sans-serif;font-size:1.4rem;font-weight:700;color:var(--primary);text-align:center;line-height:1.2}
.sb-stat-lbl{font-size:.75rem;color:var(--text-light);text-align:center}
.sb-menu{list-style:none}
.sb-menu li{margin-bottom:.35rem}
.sb-menu a{display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;border-radius:12px;color:var(--text-dark);text-decoration:none;font-weight:500;font-size:.9rem;transition:all .25s}
.sb-menu a:hover{background:var(--bg-light);color:var(--primary)}
.sb-menu a.active{background:var(--primary);color:white}
.sb-menu a.danger{color:#EF4444}
.sb-menu a.danger:hover{background:#FEE2E2;color:#DC2626}
/* Content panel */
.panel{background:white;border-radius:28px;box-shadow:var(--shadow-md);padding:2.5rem;animation:fadeInUp .8s ease .3s both}
.panel-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;padding-bottom:1rem;border-bottom:2px solid var(--bg-light)}
.panel-title{font-family:'Sora',sans-serif;font-size:1.5rem;font-weight:700}
.btn-act{padding:.55rem 1.4rem;border-radius:50px;font-weight:600;font-size:.88rem;cursor:pointer;text-decoration:none;display:inline-block;transition:all .3s;font-family:'Manrope',sans-serif}
.btn-outline{color:var(--primary);border:2px solid var(--primary);background:transparent}
.btn-outline:hover{background:var(--primary);color:white}
.btn-solid{background:var(--primary);color:white;border:2px solid var(--primary)}
.btn-solid:hover{background:var(--primary-dark)}
/* Info grid */
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;margin-bottom:1.5rem}
.info-item{background:var(--bg-light);padding:1.1rem;border-radius:14px}
.info-label{font-size:.73rem;color:var(--text-light);margin-bottom:.25rem;text-transform:uppercase;letter-spacing:.5px}
.info-value{font-size:.95rem;font-weight:600;word-break:break-all}
/* Form inside panel */
.fg{margin-bottom:1.2rem}
.fl{display:block;font-weight:600;font-size:.85rem;margin-bottom:.4rem}
.fi{width:100%;padding:.7rem 1rem;border:1.5px solid #E2E8F0;border-radius:10px;font-family:'Manrope',sans-serif;font-size:.9rem;outline:none;transition:all .25s;background:white}
.fi:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(10,126,164,.08)}
.fi:disabled{background:#F1F5F9;color:#94A3B8}
.fg-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
/* Alert */
.alert{padding:.75rem 1rem;border-radius:10px;margin-bottom:1.3rem;font-size:.87rem;font-weight:600}
.alert.success{background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0}
.alert.error{background:#FEE2E2;color:#991B1B;border:1px solid #FECACA}
/* Preferences toggles */
.pref-list{display:flex;flex-direction:column}
.pref-item{display:flex;justify-content:space-between;align-items:center;padding:1rem 0;border-bottom:1px solid var(--bg-light)}
.pref-item:last-child{border-bottom:none}
.pref-label{font-weight:600;font-size:.92rem}
.pref-val{color:var(--text-light);font-size:.88rem}
.switch{position:relative;display:inline-block;width:50px;height:26px}
.switch input{opacity:0;width:0;height:0}
.slider{position:absolute;cursor:pointer;inset:0;background:#CBD5E1;transition:.3s;border-radius:34px}
.slider:before{position:absolute;content:"";height:20px;width:20px;left:3px;bottom:3px;background:white;transition:.3s;border-radius:50%}
input:checked+.slider{background:var(--primary)}
input:checked+.slider:before{transform:translateX(24px)}
/* Tab content */
.tab-panel{display:none}
.tab-panel.active{display:block}
/* Footer */
.footer{background:var(--text-dark);color:white;padding:4rem 2rem 2rem}
.footer-content{max-width:1400px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:3rem;margin-bottom:3rem}
.footer-section h3{font-family:'Sora',sans-serif;font-size:1.3rem;margin-bottom:1.5rem}
.footer-section p,.footer-section a{color:rgba(255,255,255,.7);text-decoration:none;display:block;margin-bottom:.8rem;transition:color .3s}
.footer-section a:hover{color:var(--secondary)}
.social-links{display:flex;gap:1rem;margin-top:1rem}
.social-link{width:40px;height:40px;background:rgba(255,255,255,.1);border-radius:50%;overflow:hidden;object-fit:cover;display:block}
.footer-bottom{max-width:1400px;margin:0 auto;padding-top:2rem;border-top:1px solid rgba(255,255,255,.1);text-align:center;color:rgba(255,255,255,.5)}
/* Responsive */
@media(max-width:968px){.profile-grid{grid-template-columns:1fr}}
@media(max-width:768px){
  .mobile-toggle{display:flex}
  .nav-actions{display:none}
  .nav-menu{position:fixed;top:80px;left:0;right:0;background:white;flex-direction:column;padding:2rem;gap:1.5rem;box-shadow:var(--shadow-lg);transform:translateX(-100%);transition:transform .3s}
  .nav-menu.active{transform:translateX(0)}
  .mobile-auth{display:flex !important;flex-direction:column;gap:.8rem;padding-top:1.2rem;border-top:1px solid #E2E8F0;width:100%}
  .info-grid{grid-template-columns:1fr}
  .fg-row{grid-template-columns:1fr}
  .page-title{font-size:2.2rem}
  .panel{padding:1.5rem}
}
</style>
</head>
<body>

<header class="header" id="header">
  <nav class="nav-container">
    <a href="index.php" class="logo"><img src="images/logo.jpeg" alt="Jettransfer" class="logo-img"> Jettransfer</a>
    <ul class="nav-menu" id="navMenu">
      <li><a href="index.php">Home</a></li>
      <li><a href="destination.php">Destinations</a></li>
      <li><a href="packages.php">Packages</a></li>
      <li><a href="vehicles.html">Vehicles</a></li>
      <li><a href="service.php">Services &amp; Gallery</a></li>
      <li><a href="contact.php">Contact Us</a></li>
      <li><a href="aboutus.php">About Us</a></li>
      <li><a href="profile.php" class="active">Profile</a></li>
      <li class="mobile-auth">
        <a href="logout.php" style="display:block;padding:.6rem 1rem;border-radius:50px;border:2px solid #EF4444;color:#EF4444;font-weight:600;font-size:.9rem;text-decoration:none;text-align:center">🚪 Logout</a>
      </li>
    </ul>
    <div class="nav-actions">
      <div class="profile-dropdown" id="profileDropdown">
        <button class="profile-pill" onclick="toggleDD()">
          <div class="pill-avatar">
            <?php if ($profilePic): ?>
              <img src="<?= $profilePic ?>" alt="Profile">
            <?php else: ?>
              <?= $initials ?>
            <?php endif; ?>
          </div>
          <span class="pill-name"><?= htmlspecialchars(explode(' ', $fullName)[0]) ?></span>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="dropdown-menu" id="dropdownMenu">
          <div class="dd-header">
            <div class="dd-avatar">
              <?php if ($profilePic): ?>
                <img src="<?= $profilePic ?>" alt="Profile">
              <?php else: ?>
                <?= $initials ?>
              <?php endif; ?>
            </div>
            <div>
              <div class="dd-name"><?= htmlspecialchars($fullName) ?></div>
              <div class="dd-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
            </div>
          </div>
          <div class="dd-divider"></div>
          <a href="profile.php?tab=personal" class="dd-item">👤 My Profile</a>
          <a href="editprofile.php" class="dd-item">✏️ Edit Profile</a>
          <a href="profile.php?tab=bookings" class="dd-item">📅 My Bookings</a>
          <a href="profile.php?tab=security" class="dd-item">🔒 Change Password</a>
          <div class="dd-divider"></div>
          <a href="logout.php" class="dd-item dd-logout">🚪 Logout</a>
        </div>
      </div>
    </div>
    <div class="mobile-toggle" id="mobileToggle"><span></span><span></span><span></span></div>
  </nav>
</header>

<section class="page-header">
  <h1 class="page-title">My Profile</h1>
  <p class="page-description">Manage your personal information, bookings, and preferences</p>
</section>

<section class="profile-section">
  <div class="profile-grid">

    <!-- ── SIDEBAR ── -->
    <aside class="sidebar">
      <div class="sb-avatar">

        <!-- Profile picture circle with hover overlay -->
        <div class="sb-pic-wrap" onclick="document.getElementById('picInput').click()" title="Click to change photo">
          <div class="sb-circle">
            <?php if ($profilePic): ?>
              <img src="<?= $profilePic ?>" alt="Profile Photo">
            <?php else: ?>
              <?= $initials ?>
            <?php endif; ?>
          </div>
          <div class="sb-pic-overlay">
            <span>📷<br>Change<br>Photo</span>
          </div>
        </div>

        <!-- Hidden file input — triggers on circle click -->
        <form method="POST" enctype="multipart/form-data" id="picForm">
          <input type="hidden" name="action" value="upload_pic">
          <input type="file" name="profile_pic" id="picInput" class="sb-pic-input"
                 accept="image/jpeg,image/jpg,image/png,image/webp,image/gif"
                 onchange="document.getElementById('picForm').submit()">
        </form>

        <!-- Upload / Remove buttons -->
        <div class="sb-pic-btns">
          <label for="picInput" class="btn-pic-upload">
            📷 Upload Photo
          </label>
          <?php if ($profilePic): ?>
          <form method="POST" style="display:inline" onsubmit="return confirm('Remove profile picture?')">
            <input type="hidden" name="action" value="remove_pic">
            <button type="submit" class="btn-pic-remove">🗑 Remove</button>
          </form>
          <?php endif; ?>
        </div>

        <p style="font-size:.7rem;color:var(--text-light);margin-bottom:.8rem">JPG, PNG, WEBP · Max 2MB</p>

        <h2 class="sb-name"><?= htmlspecialchars($fullName) ?></h2>
        <p class="sb-email"><?= htmlspecialchars($user['email'] ?? '') ?></p>
      </div>

      <ul class="sb-menu">
        <li><a href="profile.php?tab=personal"     class="<?= $tab==='personal'    ?'active':'' ?>">👤 Personal Info</a></li>
        <li><a href="editprofile.php"               class="<?= $tab==='edit'        ?'active':'' ?>">✏️ Edit Profile</a></li>
        <li><a href="profile.php?tab=security"      class="<?= $tab==='security'    ?'active':'' ?>">🔒 Change Password</a></li>
        <li><a href="profile.php?tab=bookings"      class="<?= $tab==='bookings'    ?'active':'' ?>">📅 My Bookings</a></li>
        <li><a href="profile.php?tab=preferences"   class="<?= $tab==='preferences' ?'active':'' ?>">⚙️ Preferences</a></li>
        <li><a href="logout.php" class="danger">🚪 Logout</a></li>
      </ul>
    </aside>

    <!-- ── CONTENT ── -->
    <div class="panel">
      <?php if ($msg): ?>
        <div class="alert <?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <!-- PERSONAL INFO -->
      <div id="tab-personal" class="tab-panel <?= $tab==='personal'?'active':'' ?>">
        <div class="panel-header">
          <h2 class="panel-title">Personal Information</h2>
          <a href="editprofile.php" class="btn-act btn-outline">Edit Profile</a>
        </div>
        <div class="info-grid">
          <div class="info-item"><div class="info-label">First Name</div><div class="info-value"><?= htmlspecialchars($user['first_name'] ?? '—') ?></div></div>
          <div class="info-item"><div class="info-label">Last Name</div><div class="info-value"><?= htmlspecialchars($user['last_name'] ?? '—') ?></div></div>
          <div class="info-item"><div class="info-label">Email Address</div><div class="info-value"><?= htmlspecialchars($user['email'] ?? '—') ?></div></div>
          <div class="info-item"><div class="info-label">Phone Number</div><div class="info-value"><?= htmlspecialchars($user['phone'] ?? '—') ?></div></div>
          <div class="info-item"><div class="info-label">Date of Birth</div><div class="info-value"><?= $user['dob'] ? date('d M Y', strtotime($user['dob'])) : '—' ?></div></div>
          <div class="info-item"><div class="info-label">Nationality</div><div class="info-value"><?= htmlspecialchars($user['nationality'] ?? '—') ?></div></div>
          <div class="info-item"><div class="info-label">City</div><div class="info-value"><?= htmlspecialchars($user['city'] ?? '—') ?></div></div>
          <div class="info-item"><div class="info-label">Country</div><div class="info-value"><?= htmlspecialchars($user['country'] ?? '—') ?></div></div>
          <div class="info-item" style="grid-column:1/-1"><div class="info-label">Address</div><div class="info-value"><?= htmlspecialchars($user['address'] ?? '—') ?></div></div>
          <div class="info-item"><div class="info-label">Member Since</div><div class="info-value"><?= date('F Y', strtotime($user['created_at'] ?? 'now')) ?></div></div>
          <div class="info-item"><div class="info-label">Last Login</div><div class="info-value"><?= $user['last_login'] ? date('d M Y, g:i A', strtotime($user['last_login'])) : '—' ?></div></div>
        </div>
      </div>

      <!-- CHANGE PASSWORD -->
      <div id="tab-security" class="tab-panel <?= $tab==='security'?'active':'' ?>">
        <div class="panel-header"><h2 class="panel-title">Change Password</h2></div>
        <form method="POST" style="max-width:440px">
          <input type="hidden" name="action" value="chgpass">
          <div class="fg"><label class="fl">Current Password *</label><input type="password" name="current_password" class="fi" required placeholder="Enter current password"></div>
          <div class="fg"><label class="fl">New Password * (min 6)</label><input type="password" name="new_password" class="fi" required placeholder="Enter new password"></div>
          <div class="fg"><label class="fl">Confirm New Password *</label><input type="password" name="confirm_password" class="fi" required placeholder="Repeat new password"></div>
          <button type="submit" class="btn-act btn-solid">🔒 Update Password</button>
        </form>
      </div>

      <!-- BOOKINGS -->
      <div id="tab-bookings" class="tab-panel <?= $tab==='bookings'?'active':'' ?>">
        <div class="panel-header">
          <h2 class="panel-title">My Bookings</h2>
          <div style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap">
            <a href="export_bookings.php"
               style="display:inline-flex;align-items:center;gap:.5rem;padding:.55rem 1.3rem;
                      background:#065F46;color:white;border-radius:50px;
                      font-family:'Manrope',sans-serif;font-weight:600;font-size:.85rem;
                      text-decoration:none;transition:all .25s;box-shadow:0 3px 10px rgba(6,95,70,.3)"
               onmouseover="this.style.background='#047857';this.style.transform='translateY(-1px)'"
               onmouseout="this.style.background='#065F46';this.style.transform='translateY(0)'">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
              Export as CSV
            </a>
            <a href="packages.php" class="btn-act btn-outline">Browse Packages</a>
          </div>
        </div>
        <?php
        $bookEmail    = $conn->real_escape_string($user['email'] ?? '');
        $bRes2        = $conn->query("SELECT * FROM bookings WHERE email='$bookEmail' ORDER BY created_at DESC");
        $userBookings = [];
        if ($bRes2) while ($row = $bRes2->fetch_assoc()) $userBookings[] = $row;
        ?>
        <?php if (empty($userBookings)): ?>
          <div style="text-align:center;padding:3rem 1rem;color:var(--text-light)">
            <div style="font-size:3.5rem;margin-bottom:1rem">📅</div>
            <p style="font-weight:600;font-size:1rem;margin-bottom:.5rem">No bookings yet</p>
            <p style="font-size:.88rem">Browse our packages and book your first Sri Lanka adventure!</p>
            <a href="packages.php" style="display:inline-block;margin-top:1rem;padding:.6rem 1.5rem;background:var(--primary);color:white;border-radius:50px;text-decoration:none;font-weight:600;font-size:.88rem">Browse Packages →</a>
          </div>
        <?php else: ?>
          <p style="font-size:.82rem;color:var(--text-light);margin-bottom:1rem">
            You have <strong style="color:var(--primary)"><?= count($userBookings) ?></strong> booking<?= count($userBookings) > 1 ? 's' : '' ?>.
          </p>
          <div style="display:flex;flex-direction:column;gap:1rem">
          <?php foreach ($userBookings as $b):
            $statusColors = ['Pending'=>['bg'=>'#FEF3C7','txt'=>'#92400E'],'Confirmed'=>['bg'=>'#ECFDF5','txt'=>'#065F46'],'Completed'=>['bg'=>'#DBEAFE','txt'=>'#1E40AF'],'Cancelled'=>['bg'=>'#FEE2E2','txt'=>'#991B1B']];
            $sc = $statusColors[$b['status']] ?? ['bg'=>'#F1F5F9','txt'=>'#475569'];
          ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:1.1rem 1.2rem;background:var(--bg-light);border-radius:14px;transition:transform .2s"
               onmouseover="this.style.transform='translateX(4px)'" onmouseout="this.style.transform='translateX(0)'">
            <div>
              <div style="font-weight:700;font-size:.95rem;margin-bottom:.3rem"><?= htmlspecialchars($b['package_name']) ?></div>
              <div style="display:flex;gap:1.2rem;flex-wrap:wrap;font-size:.82rem;color:var(--text-light)">
                <span>📅 <?= date('d M Y', strtotime($b['travel_date'])) ?></span>
                <span>👥 <?= $b['guests'] ?> guest<?= $b['guests'] > 1 ? 's' : '' ?></span>
                <span>🕐 Booked <?= date('d M Y', strtotime($b['created_at'])) ?></span>
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:1rem;flex-shrink:0;margin-left:1rem">
              <span style="padding:.28rem .85rem;border-radius:50px;font-size:.73rem;font-weight:700;background:<?= $sc['bg'] ?>;color:<?= $sc['txt'] ?>"><?= htmlspecialchars($b['status']) ?></span>
              <span style="font-family:'Sora',sans-serif;font-weight:800;color:var(--primary);font-size:1rem;white-space:nowrap"><?= htmlspecialchars($b['price']) ?></span>
            </div>
          </div>
          <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- PREFERENCES -->
      <div id="tab-preferences" class="tab-panel <?= $tab==='preferences'?'active':'' ?>">
        <div class="panel-header"><h2 class="panel-title">Preferences</h2></div>
        <div class="pref-list">
          <div class="pref-item"><span class="pref-label">Email Notifications</span><label class="switch"><input type="checkbox" checked><span class="slider"></span></label></div>
          <div class="pref-item"><span class="pref-label">SMS Notifications</span><label class="switch"><input type="checkbox"><span class="slider"></span></label></div>
          <div class="pref-item"><span class="pref-label">Promotional Offers</span><label class="switch"><input type="checkbox" checked><span class="slider"></span></label></div>
          <div class="pref-item"><span class="pref-label">Preferred Currency</span><span class="pref-val">Sri Lankan Rupee (LKR)</span></div>
          <div class="pref-item"><span class="pref-label">Preferred Language</span><span class="pref-val">English</span></div>
        </div>
      </div>

    </div><!-- /.panel -->
  </div>
</section>

<footer class="footer">
  <div class="footer-content">
    <div class="footer-section"><h3>Jettransfer Travels</h3><p>Your trusted partner for exploring the beauty of Sri Lanka.</p><div class="social-links"><img src="images/insta1.webp" alt="Instagram" class="social-link"><img src="images/fb.avif" alt="Facebook" class="social-link"><img src="images/tiktok.webp" alt="TikTok" class="social-link"></div></div>
    <div class="footer-section"><h3>Quick Links</h3><a href="destination.php">Destinations</a><a href="package.php">Tour Packages</a><a href="service.php">Services &amp; Gallery</a><a href="contact.php">Contact Us</a></div>
    <div class="footer-section"><h3>Contact Us</h3><p>📞 +94 72 403 0499</p><p>📧 jettransfer@outlook.com</p><p>📍 Ruwani Uyana, Matara, Sri Lanka</p></div>
    <div class="footer-section"><h3>Business Hours</h3><p>Mon–Fri: 8:00 AM – 6:00 PM</p><p>Sat–Sun: 10:00 AM – 6:00 PM</p><p style="margin-top:1rem;color:var(--secondary)">24/7 Emergency Support</p></div>
  </div>
  <div class="footer-bottom"><p>&copy; 2026 Jettransfer Travels. All rights reserved. | Designed with care for your journey</p></div>
</footer>

<script>
document.getElementById('mobileToggle').addEventListener('click',()=>document.getElementById('navMenu').classList.toggle('active'));
document.querySelectorAll('.nav-menu a').forEach(a=>a.addEventListener('click',()=>document.getElementById('navMenu').classList.remove('active')));
window.addEventListener('scroll',()=>document.getElementById('header').classList.toggle('scrolled',window.scrollY>100));
function toggleDD(){document.getElementById('dropdownMenu').classList.toggle('open')}
document.addEventListener('click',e=>{
  const dd=document.getElementById('profileDropdown');
  if(dd&&!dd.contains(e.target))document.getElementById('dropdownMenu').classList.remove('open');
});
</script>
</body>
</html>