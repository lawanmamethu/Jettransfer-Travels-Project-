<?php
// ── editprofile.php ───────────────────────────────────────────
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

require_once 'db.php';
$uid     = (int)$_SESSION['user_id'];
$msg     = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fn   = trim($_POST['first_name']   ?? '');
    $ln   = trim($_POST['last_name']    ?? '');
    $ph   = trim($_POST['phone']        ?? '');
    $dob  = trim($_POST['dob']          ?? '');
    $nat  = trim($_POST['nationality']  ?? '');
    $addr = trim($_POST['address']      ?? '');
    $city = trim($_POST['city']         ?? '');
    $ctry = trim($_POST['country']      ?? '');

    if (!$fn || !$ln) {
        $msg = '❌ First name and last name are required.';
        $msgType = 'error';
    } else {
        $f    = $conn->real_escape_string($fn);
        $l    = $conn->real_escape_string($ln);
        $p    = $conn->real_escape_string($ph);
        $d    = $conn->real_escape_string($dob);
        $n    = $conn->real_escape_string($nat);
        $a    = $conn->real_escape_string($addr);
        $ci   = $conn->real_escape_string($city);
        $co   = $conn->real_escape_string($ctry);
        $dobV = $d ? "'$d'" : 'NULL';

        $sql = "UPDATE users SET
                  first_name='$f', last_name='$l', phone='$p',
                  dob=$dobV, nationality='$n', address='$a',
                  city='$ci', country='$co'
                WHERE id=$uid";

        if ($conn->query($sql)) {
            $_SESSION['user_name'] = "$fn $ln";
            $conn->close();
            header('Location: profile.php?tab=personal&updated=1');
            exit;
        } else {
            $msg = '❌ Update failed: ' . $conn->error;
            $msgType = 'error';
        }
    }
}

// Also handle password change from this page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'chgpass') {
    $cur  = $_POST['currentPassword']  ?? '';
    $new  = $_POST['newPassword']      ?? '';
    $conf = $_POST['confirmPassword']  ?? '';
    $r    = $conn->query("SELECT password_hash FROM users WHERE id=$uid LIMIT 1");
    $row  = $r ? $r->fetch_assoc() : [];
    if (!password_verify($cur, $row['password_hash'] ?? '')) {
        $msg = '❌ Current password is incorrect.'; $msgType = 'error';
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
$conn->close();

$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$parts    = explode(' ', $fullName);
$initials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Profile – Jettransfer Travels</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--primary:#0A7EA4;--primary-dark:#065A7A;--secondary:#F59E0B;--accent:#10B981;--text-dark:#0F172A;--text-light:#64748B;--bg-light:#F8FAFC;--white:#FFFFFF;--shadow-sm:0 1px 3px rgba(0,0,0,.08);--shadow-md:0 4px 12px rgba(0,0,0,.1);--shadow-lg:0 10px 40px rgba(0,0,0,.15);--error:#EF4444}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Manrope',sans-serif;color:var(--text-dark);background:var(--bg-light)}
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
.nav-search{position:relative;display:flex;align-items:center}
.search-toggle{width:38px;height:38px;border-radius:50%;border:2px solid #E2E8F0;background:transparent;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text-dark);transition:all .3s}
.search-toggle:hover{border-color:var(--primary);color:var(--primary)}
.search-box{position:absolute;right:0;top:50%;transform:translateY(-50%);display:flex;align-items:center;background:white;border:2px solid var(--primary);border-radius:50px;overflow:hidden;width:0;opacity:0;pointer-events:none;transition:width .4s,opacity .3s;box-shadow:var(--shadow-md);z-index:10}
.search-box.open{width:280px;opacity:1;pointer-events:all}
.search-box input{border:none;outline:none;padding:.55rem 1rem;font-family:'Manrope',sans-serif;font-size:.88rem;width:100%;background:transparent}
.search-submit{background:var(--primary);border:none;padding:.55rem 1rem;color:white;cursor:pointer;display:flex;align-items:center;flex-shrink:0}
/* Profile pill dropdown */
.profile-dropdown{position:relative}
.profile-pill{display:flex;align-items:center;gap:.5rem;padding:.4rem .9rem .4rem .45rem;border-radius:50px;border:2px solid #E2E8F0;background:white;cursor:pointer;font-family:'Manrope',sans-serif;font-weight:600;font-size:.88rem;color:var(--text-dark);transition:all .25s;white-space:nowrap}
.profile-pill:hover{border-color:var(--primary);box-shadow:0 2px 12px rgba(10,126,164,.15)}
.pill-avatar{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#fff;flex-shrink:0}
.dropdown-menu{position:absolute;right:0;top:calc(100% + .6rem);background:white;border:1px solid #E2E8F0;border-radius:18px;box-shadow:0 10px 40px rgba(0,0,0,.12);min-width:230px;z-index:1001;opacity:0;transform:translateY(-8px) scale(.97);pointer-events:none;transition:all .22s ease}
.dropdown-menu.open{opacity:1;transform:translateY(0) scale(1);pointer-events:all}
.dd-header{display:flex;align-items:center;gap:.75rem;padding:1rem 1.1rem .8rem}
.dd-avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:700;color:#fff}
.dd-name{font-weight:700;font-size:.92rem;color:var(--text-dark);margin-bottom:.15rem}
.dd-email{font-size:.73rem;color:#94A3B8}
.dd-divider{height:1px;background:#F1F5F9;margin:.3rem 0}
.dd-item{display:flex;align-items:center;gap:.6rem;padding:.65rem 1rem;color:var(--text-dark);text-decoration:none;font-size:.88rem;font-weight:500;transition:background .15s;border-radius:10px;margin:.1rem .4rem}
.dd-item:hover{background:#F1F5F9;color:var(--primary)}
.dd-logout{color:#EF4444!important}
.dd-logout:hover{background:#FEE2E2!important;color:#DC2626!important}
.mobile-toggle{display:none;flex-direction:column;gap:5px;cursor:pointer;padding:8px}
.mobile-toggle span{width:25px;height:3px;background:var(--text-dark);border-radius:2px}
.mobile-auth{display:none}
/* Page */
.page-header{margin-top:80px;padding:3rem 2rem 2rem;background:linear-gradient(135deg,rgba(10,126,164,.05),rgba(16,185,129,.05));text-align:center}
.page-title{font-family:'Sora',sans-serif;font-size:2.8rem;font-weight:800;margin-bottom:.5rem;animation:fadeInUp .8s ease}
.page-description{font-size:1.1rem;color:var(--text-light);max-width:700px;margin:0 auto;animation:fadeInUp .8s ease .2s both}
@keyframes fadeInUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
.edit-section{max-width:1200px;margin:3rem auto 5rem;padding:0 2rem}
.edit-grid{display:grid;grid-template-columns:300px 1fr;gap:2rem}
/* Sidebar */
.sidebar{background:white;border-radius:28px;box-shadow:var(--shadow-md);padding:2rem;height:fit-content;animation:fadeInUp .8s ease .2s both}
.sb-avatar{text-align:center;margin-bottom:1.5rem}
.sb-circle{width:110px;height:110px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:white;font-weight:700}
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
/* Edit card */
.edit-card{background:white;border-radius:28px;box-shadow:var(--shadow-md);padding:2.5rem;animation:fadeInUp .8s ease .3s both}
.card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;padding-bottom:1rem;border-bottom:2px solid var(--bg-light)}
.card-title{font-family:'Sora',sans-serif;font-size:1.5rem;font-weight:700}
.btn-back{padding:.55rem 1.4rem;background:transparent;color:var(--text-light);border:2px solid #E2E8F0;border-radius:50px;font-weight:600;font-size:.88rem;text-decoration:none;transition:all .3s}
.btn-back:hover{border-color:var(--primary);color:var(--primary)}
/* Form */
.form-section{margin-bottom:2rem}
.section-title{font-family:'Sora',sans-serif;font-size:1.05rem;font-weight:700;margin-bottom:1.2rem;padding-bottom:.6rem;border-bottom:1px solid var(--bg-light)}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.2rem}
.form-group{display:flex;flex-direction:column;gap:.4rem}
.form-group.span-2{grid-column:span 2}
label{font-size:.78rem;font-weight:600;color:var(--text-light);text-transform:uppercase;letter-spacing:.5px}
input[type=text],input[type=email],input[type=tel],input[type=date],input[type=password],select,textarea{width:100%;padding:.75rem 1rem;border:2px solid #E2E8F0;border-radius:12px;font-family:'Manrope',sans-serif;font-size:.95rem;color:var(--text-dark);background:white;outline:none;transition:all .3s}
input:focus,select:focus,textarea:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(10,126,164,.08)}
input:disabled{background:#F1F5F9;color:#94A3B8;cursor:not-allowed}
input.error-field{border-color:var(--error)}
select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 1rem center;padding-right:2.5rem}
/* Password wrapper */
.pw-wrap{position:relative}
.pw-wrap input{padding-right:3rem}
.pw-toggle{position:absolute;right:.9rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-light);display:flex;align-items:center;transition:color .2s}
.pw-toggle:hover{color:var(--primary)}
/* Strength */
.strength-wrap{margin-top:.4rem}
.strength-bar{height:4px;border-radius:4px;background:#E2E8F0;overflow:hidden;margin-bottom:.25rem}
.strength-fill{height:100%;border-radius:4px;width:0;transition:width .4s,background .4s}
.strength-txt{font-size:.75rem;color:var(--text-light)}
.divider{border:none;border-top:2px solid var(--bg-light);margin:2rem 0}
.form-actions{display:flex;justify-content:flex-end;gap:1rem;padding-top:1rem}
.btn-cancel{padding:.7rem 1.8rem;border-radius:50px;border:2px solid #E2E8F0;background:transparent;color:var(--text-light);font-family:'Manrope',sans-serif;font-weight:600;font-size:.95rem;cursor:pointer;text-decoration:none;display:inline-block;transition:all .3s}
.btn-cancel:hover{border-color:var(--text-light);color:var(--text-dark)}
.btn-save{padding:.7rem 2rem;border-radius:50px;border:none;background:var(--primary);color:white;font-family:'Manrope',sans-serif;font-weight:600;font-size:.95rem;cursor:pointer;transition:all .3s;box-shadow:0 4px 12px rgba(10,126,164,.3);display:inline-flex;align-items:center;gap:.5rem}
.btn-save:hover{background:var(--primary-dark);transform:translateY(-2px)}
.btn-save:disabled{opacity:.7;cursor:not-allowed;transform:none}
.spinner{width:16px;height:16px;border:2px solid rgba(255,255,255,.4);border-top-color:white;border-radius:50%;animation:spin .7s linear infinite;display:none}
.btn-save.loading .spinner{display:block}
.btn-save.loading .btn-txt{display:none}
@keyframes spin{to{transform:rotate(360deg)}}
.alert{padding:.75rem 1rem;border-radius:10px;margin-bottom:1.3rem;font-size:.88rem;font-weight:600}
.alert.error{background:#FEE2E2;color:#991B1B;border:1px solid #FECACA}
/* Toast */
.toast{position:fixed;bottom:2rem;right:2rem;background:var(--text-dark);color:white;padding:1rem 1.5rem;border-radius:16px;box-shadow:var(--shadow-lg);display:flex;align-items:center;gap:.8rem;font-weight:600;transform:translateY(120px);opacity:0;transition:all .4s cubic-bezier(.34,1.56,.64,1);z-index:9999}
.toast.show{transform:translateY(0);opacity:1}
/* Footer */
.footer{background:var(--text-dark);color:white;padding:4rem 2rem 2rem}
.footer-content{max-width:1400px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:3rem;margin-bottom:3rem}
.footer-section h3{font-family:'Sora',sans-serif;font-size:1.3rem;margin-bottom:1.5rem}
.footer-section p,.footer-section a{color:rgba(255,255,255,.7);text-decoration:none;display:block;margin-bottom:.8rem;transition:color .3s}
.footer-section a:hover{color:var(--secondary)}
.social-links{display:flex;gap:1rem;margin-top:1rem}
.social-link{width:40px;height:40px;background:rgba(255,255,255,.1);border-radius:50%;overflow:hidden;object-fit:cover;display:block}
.footer-bottom{max-width:1400px;margin:0 auto;padding-top:2rem;border-top:1px solid rgba(255,255,255,.1);text-align:center;color:rgba(255,255,255,.5)}
@media(max-width:968px){.edit-grid{grid-template-columns:1fr}}
@media(max-width:768px){.mobile-toggle{display:flex}.nav-actions{display:none}.nav-menu{position:fixed;top:80px;left:0;right:0;background:white;flex-direction:column;padding:2rem;gap:1.5rem;box-shadow:var(--shadow-lg);transform:translateX(-100%);transition:transform .3s}.nav-menu.active{transform:translateX(0)}.mobile-auth{display:flex !important;flex-direction:column;gap:.8rem;padding-top:1.2rem;border-top:1px solid #E2E8F0;width:100%}.form-grid{grid-template-columns:1fr}.form-group.span-2{grid-column:span 1}.form-actions{flex-direction:column-reverse}.btn-cancel,.btn-save{width:100%;justify-content:center}.toast{left:1rem;right:1rem}.page-title{font-size:2.2rem}}
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
      <li><a href="profile.php">Profile</a></li>

      <li class="mobile-auth">
        <a href="logout.php" style="display:block;padding:.6rem 1rem;border-radius:50px;border:2px solid #EF4444;color:#EF4444;font-weight:600;font-size:.9rem;text-decoration:none;text-align:center">🚪 Logout</a>
      </li>
    </ul>
    
      <!-- ✅ Profile dropdown replacing Login/Register -->
      <div class="profile-dropdown" id="profileDropdown">
        <button class="profile-pill" onclick="toggleDD()">
          <div class="pill-avatar"><?= $initials ?></div>
          <span><?= htmlspecialchars(explode(' ', $fullName)[0]) ?></span>
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="dropdown-menu" id="dropdownMenu">
          <div class="dd-header">
            <div class="dd-avatar"><?= $initials ?></div>
            <div><div class="dd-name"><?= htmlspecialchars($fullName) ?></div><div class="dd-email"><?= htmlspecialchars($user['email'] ?? '') ?></div></div>
          </div>
          <div class="dd-divider"></div>
          <a href="profile.php" class="dd-item">👤 My Profile</a>
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
  <h1 class="page-title">Edit Profile</h1>
  <p class="page-description">Update your personal information and keep your account details current</p>
</section>

<section class="edit-section">
  <div class="edit-grid">

    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="sb-avatar">
        <div class="sb-circle" id="sbInitials"><?= $initials ?></div>
        <h2 class="sb-name" id="sbName"><?= htmlspecialchars($fullName) ?></h2>
        <p class="sb-email"><?= htmlspecialchars($user['email'] ?? '') ?></p>
      </div>
      <div class="sb-stats">
        <div><div class="sb-stat-val">0</div><div class="sb-stat-lbl">Bookings</div></div>
        <div><div class="sb-stat-val">0</div><div class="sb-stat-lbl">Reviews</div></div>
      </div>
      <ul class="sb-menu">
        <li><a href="profile.php">👤 Personal Info</a></li>
        <li><a href="editprofile.php" class="active">✏️ Edit Profile</a></li>
        <li><a href="profile.php?tab=security">🔒 Change Password</a></li>
        <li><a href="profile.php?tab=bookings">📅 My Bookings</a></li>
        <li><a href="profile.php?tab=preferences">⚙️ Preferences</a></li>
        <li><a href="logout.php" class="danger">🚪 Logout</a></li>
      </ul>
    </aside>

    <!-- EDIT FORM -->
    <div class="edit-card">
      <div class="card-header">
        <h2 class="card-title">Edit Information</h2>
        <a href="profile.php" class="btn-back">← Back to Profile</a>
      </div>

      <?php if ($msg && $msgType === 'error'): ?>
        <div class="alert error"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <!-- PERSONAL DETAILS FORM -->
      <form id="editForm" method="POST">
        <!-- Personal Details -->
        <div class="form-section">
          <h3 class="section-title">Personal Details</h3>
          <div class="form-grid">
            <div class="form-group">
              <label for="first_name">First Name *</label>
              <input type="text" id="first_name" name="first_name" required placeholder="Enter first name"
                     value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="last_name">Last Name *</label>
              <input type="text" id="last_name" name="last_name" required placeholder="Enter last name"
                     value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Email Address (cannot be changed)</label>
              <input type="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
            </div>
            <div class="form-group">
              <label for="phone">Phone Number</label>
              <input type="tel" id="phone" name="phone" placeholder="+94 77 123 4567"
                     value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="dob">Date of Birth</label>
              <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($user['dob'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="nationality">Nationality</label>
              <select id="nationality" name="nationality">
                <option value="">Select nationality</option>
                <?php foreach (['Sri Lankan','Indian','British','Australian','American','German','French','Chinese','Japanese','Other'] as $n): ?>
                  <option value="<?= $n ?>" <?= ($user['nationality'] ?? '') === $n ? 'selected' : '' ?>><?= $n ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <hr class="divider">

        <!-- Address -->
        <div class="form-section">
          <h3 class="section-title">Address</h3>
          <div class="form-grid">
            <div class="form-group span-2">
              <label for="address">Street Address</label>
              <input type="text" id="address" name="address" placeholder="Enter street address"
                     value="<?= htmlspecialchars($user['address'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="city">City</label>
              <input type="text" id="city" name="city" placeholder="Enter city"
                     value="<?= htmlspecialchars($user['city'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="country">Country</label>
              <select id="country" name="country">
                <option value="">Select country</option>
                <?php foreach (['Sri Lanka','India','United Kingdom','Australia','United States','Germany','France','China','Japan','Other'] as $c): ?>
                  <option value="<?= $c ?>" <?= ($user['country'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <hr class="divider">

        <div class="form-actions">
          <a href="profile.php" class="btn-cancel">Cancel</a>
          <button type="submit" class="btn-save" id="saveBtn">
            <span class="spinner"></span>
            <span class="btn-txt">💾 Save Changes</span>
          </button>
        </div>
      </form>

      <hr class="divider">

      <!-- CHANGE PASSWORD (separate form) -->
      <div class="form-section">
        <h3 class="section-title">Change Password <span style="font-size:.8rem;font-weight:400;color:var(--text-light)">(leave blank to keep current)</span></h3>
        <form method="POST" style="max-width:520px">
          <input type="hidden" name="action" value="chgpass">
          <div class="form-grid">
            <div class="form-group">
              <label>Current Password</label>
              <div class="pw-wrap">
                <input type="password" id="curPw" name="currentPassword" placeholder="Enter current password">
                <button type="button" class="pw-toggle" onclick="togglePw('curPw',this)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
              </div>
            </div>
            <div class="form-group">
              <label>New Password</label>
              <div class="pw-wrap">
                <input type="password" id="newPw" name="newPassword" placeholder="Min 6 characters" oninput="checkStrength(this.value)">
                <button type="button" class="pw-toggle" onclick="togglePw('newPw',this)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
              </div>
              <div class="strength-wrap" id="strengthWrap" style="display:none">
                <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                <span class="strength-txt" id="strengthTxt"></span>
              </div>
            </div>
            <div class="form-group">
              <label>Confirm New Password</label>
              <div class="pw-wrap">
                <input type="password" id="confPw" name="confirmPassword" placeholder="Repeat new password">
                <button type="button" class="pw-toggle" onclick="togglePw('confPw',this)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
              </div>
            </div>
          </div>
          <button type="submit" class="btn-save" style="margin-top:.5rem">🔒 Update Password</button>
        </form>
      </div>

    </div><!-- /.edit-card -->
  </div>
</section>

<div class="toast" id="toast"><span style="font-size:1.3rem">✅</span><span>Profile updated successfully!</span></div>

<footer class="footer">
  <div class="footer-content">
    <div class="footer-section"><h3>Jettransfer Travels</h3><p>Your trusted partner for exploring Sri Lanka.</p><div class="social-links"><img src="images/insta1.webp" alt="Instagram" class="social-link"><img src="images/fb.avif" alt="Facebook" class="social-link"><img src="images/tiktok.webp" alt="TikTok" class="social-link"></div></div>
    <div class="footer-section"><h3>Quick Links</h3><a href="destination.php">Destinations</a><a href="package.php">Tour Packages</a><a href="service.php">Services &amp; Gallery</a><a href="contact.php">Contact Us</a></div>
    <div class="footer-section"><h3>Contact Us</h3><p>📞 +94 72 403 0499</p><p>📧 jettransfer@outlook.com</p><p>📍 Ruwani Uyana, Matara, Sri Lanka</p></div>
    <div class="footer-section"><h3>Business Hours</h3><p>Mon–Fri: 8:00 AM – 6:00 PM</p><p>Sat–Sun: 10:00 AM – 6:00 PM</p></div>
  </div>
  <div class="footer-bottom"><p>&copy; 2026 Jettransfer Travels. All rights reserved. | Designed with care for your journey</p></div>
</footer>

<script>
// Nav
document.getElementById('mobileToggle').addEventListener('click',()=>document.getElementById('navMenu').classList.toggle('active'));
document.querySelectorAll('.nav-menu a').forEach(a=>a.addEventListener('click',()=>document.getElementById('navMenu').classList.remove('active')));
window.addEventListener('scroll',()=>document.getElementById('header').classList.toggle('scrolled',window.scrollY>100));
// Search
const st=document.getElementById('searchToggle'),sb=document.getElementById('searchBox');
st.addEventListener('click',e=>{e.stopPropagation();sb.classList.toggle('open');if(sb.classList.contains('open'))setTimeout(()=>document.getElementById('searchInput').focus(),300)});
document.addEventListener('click',e=>{if(!document.getElementById('navSearch').contains(e.target))sb.classList.remove('open')});
// Profile dropdown
function toggleDD(){document.getElementById('dropdownMenu').classList.toggle('open')}
document.addEventListener('click',e=>{const dd=document.getElementById('profileDropdown');if(dd&&!dd.contains(e.target))document.getElementById('dropdownMenu').classList.remove('open')});
// Password toggle
function togglePw(id,btn){const i=document.getElementById(id);i.type=i.type==='password'?'text':'password';btn.style.opacity=i.type==='text'?'.4':'1'}
// Password strength
function checkStrength(v){
  const wrap=document.getElementById('strengthWrap'),fill=document.getElementById('strengthFill'),txt=document.getElementById('strengthTxt');
  if(!v){wrap.style.display='none';return}
  wrap.style.display='block';
  let s=0;
  if(v.length>=8)s++;if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;
  const lv=[{p:25,c:'#EF4444',l:'Weak'},{p:50,c:'#F59E0B',l:'Fair'},{p:75,c:'#0A7EA4',l:'Good'},{p:100,c:'#10B981',l:'Strong'}];
  const r=lv[s-1]||lv[0];
  fill.style.width=r.p+'%';fill.style.background=r.c;txt.textContent=r.l;txt.style.color=r.c;
}
// Live sidebar name update
['first_name','last_name'].forEach(id=>{
  const el=document.getElementById(id);
  if(el) el.addEventListener('input',()=>{
    const fn=document.getElementById('first_name').value.trim();
    const ln=document.getElementById('last_name').value.trim();
    document.getElementById('sbName').textContent=[fn,ln].filter(Boolean).join(' ')||'Your Name';
  });
});
// Save button loading state
document.getElementById('editForm').addEventListener('submit',()=>{
  const btn=document.getElementById('saveBtn');
  btn.classList.add('loading');btn.disabled=true;
});
</script>
</body>
</html>
