<?php
// ── contact.php ───────────────────────────────────────────────
session_start();
require_once 'db.php';

// ── Dynamic nav vars ──────────────────────────────────────────
$isLoggedIn   = isset($_SESSION['user_id']);
$userName     = $isLoggedIn ? htmlspecialchars($_SESSION['user_name'] ?? 'User') : '';
$userEmail    = $isLoggedIn ? htmlspecialchars($_SESSION['user_email'] ?? '') : '';
$parts        = explode(' ', trim($userName));
$userInitials = $isLoggedIn
    ? strtoupper(substr($parts[0] ?? 'U', 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''))
    : '';

// ── Handle form submission ────────────────────────────────────
$success = false;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $topic   = trim($_POST['topic']   ?? 'General Inquiry');
    $message = trim($_POST['message'] ?? '');

    // Validation
    if (!$name)                                   $errors[] = 'Name is required.';
    if (!$email)                                  $errors[] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (!$message || strlen($message) < 10)       $errors[] = 'Message must be at least 10 characters.';

    if (empty($errors)) {
        $n   = $conn->real_escape_string($name);
        $e   = $conn->real_escape_string($email);
        $p   = $conn->real_escape_string($phone);
        $t   = $conn->real_escape_string($topic);
        $m   = $conn->real_escape_string($message);
        $ip  = $conn->real_escape_string($_SERVER['REMOTE_ADDR'] ?? '');

        $sql = "INSERT INTO contact_messages (name,email,phone,topic,message,status,ip_address)
                VALUES ('$n','$e','$p','$t','$m','New','$ip')";

        if ($conn->query($sql)) {
            $success = true;
        } else {
            $errors[] = 'Could not send message. Please try again.';
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us – Jettransfer Travels</title>
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
.logo-img{width:42px;height:42px;border-radius:12px;object-fit:cover;background:#fff;border:1px solid rgba(2,6,23,.1)}
.nav-menu{display:flex;list-style:none;gap:1.6rem;align-items:center}
.nav-menu a{color:var(--text-dark);text-decoration:none;font-weight:500;font-size:.88rem;transition:color .3s;position:relative}
.nav-menu a::after{content:'';position:absolute;bottom:-5px;left:0;width:0;height:2px;background:var(--primary);transition:width .3s}
.nav-menu a:hover::after,.nav-menu a.active::after{width:100%}
.nav-menu a:hover,.nav-menu a.active{color:var(--primary)}
.nav-actions{display:flex;align-items:center;gap:.6rem;flex-shrink:0}
/* Search */
.nav-search{position:relative;display:flex;align-items:center}
.search-toggle{width:38px;height:38px;border-radius:50%;border:2px solid #E2E8F0;background:transparent;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text-dark);transition:all .3s}
.search-toggle:hover{border-color:var(--primary);color:var(--primary)}
.search-box{position:absolute;right:0;top:50%;transform:translateY(-50%);display:flex;align-items:center;background:white;border:2px solid var(--primary);border-radius:50px;overflow:hidden;width:0;opacity:0;pointer-events:none;transition:width .4s,opacity .3s;box-shadow:var(--shadow-md);z-index:10}
.search-box.open{width:280px;opacity:1;pointer-events:all}
.search-box input{border:none;outline:none;padding:.55rem 1rem;font-family:'Manrope',sans-serif;font-size:.88rem;width:100%;background:transparent}
.search-submit{background:var(--primary);border:none;padding:.55rem 1rem;color:white;cursor:pointer;display:flex;align-items:center;flex-shrink:0}
/* Auth buttons */
.btn-nav-login{padding:.5rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:.88rem;color:var(--primary);border:2px solid var(--primary);transition:all .3s;white-space:nowrap}
.btn-nav-login:hover{background:var(--primary);color:white;transform:translateY(-2px)}
.btn-nav-register{padding:.5rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:.88rem;background:var(--primary);color:white;border:2px solid var(--primary);transition:all .3s;white-space:nowrap}
.btn-nav-register:hover{background:var(--primary-dark);transform:translateY(-2px)}
/* Profile dropdown (when logged in) */
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
/* Mobile */
.mobile-toggle{display:none;flex-direction:column;gap:5px;cursor:pointer;padding:8px}
.mobile-toggle span{width:25px;height:3px;background:var(--text-dark);border-radius:2px}
.mobile-auth{display:none}
/* ─── Page Header ─── */
.page-header{margin-top:80px;padding:3.5rem 2rem 2.5rem;background:linear-gradient(135deg,rgba(10,126,164,.06),rgba(16,185,129,.06));text-align:center;position:relative;overflow:hidden}
.page-header::before{content:'📬';position:absolute;top:20px;left:8%;font-size:3rem;opacity:.08;animation:float 8s ease-in-out infinite}
.page-header::after{content:'✈️';position:absolute;bottom:15px;right:8%;font-size:3rem;opacity:.08;animation:float 10s ease-in-out infinite reverse}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-18px)}}
.page-title{font-family:'Sora',sans-serif;font-size:2.8rem;font-weight:800;margin-bottom:.5rem;animation:fadeInUp .8s ease}
.page-description{font-size:1.1rem;color:var(--text-light);max-width:700px;margin:.5rem auto 0;animation:fadeInUp .8s ease .2s both}
@keyframes fadeInUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
/* ─── Contact section ─── */
.contact-section{max-width:1200px;margin:3rem auto 5rem;padding:0 2rem}
.contact-grid{display:grid;grid-template-columns:1fr 1.6fr;gap:2.5rem;align-items:start}
/* Info cards */
.contact-info{display:flex;flex-direction:column;gap:1.2rem}
.info-card{background:white;border-radius:20px;padding:1.6rem;box-shadow:var(--shadow-md);display:flex;align-items:flex-start;gap:1rem;transition:transform .25s,box-shadow .25s;animation:fadeInUp .8s ease .2s both}
.info-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-lg)}
.info-icon{width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
.info-icon.blue{background:linear-gradient(135deg,rgba(10,126,164,.12),rgba(10,126,164,.06))}
.info-icon.green{background:linear-gradient(135deg,rgba(16,185,129,.12),rgba(16,185,129,.06))}
.info-icon.amber{background:linear-gradient(135deg,rgba(245,158,11,.12),rgba(245,158,11,.06))}
.info-icon.purple{background:linear-gradient(135deg,rgba(139,92,246,.12),rgba(139,92,246,.06))}
.info-content h3{font-family:'Sora',sans-serif;font-size:1rem;font-weight:700;margin-bottom:.3rem}
.info-content p,.info-content a{color:var(--text-light);font-size:.9rem;text-decoration:none;line-height:1.5;display:block}
.info-content a:hover{color:var(--primary)}
/* Map placeholder */
.map-card{background:white;border-radius:20px;overflow:hidden;box-shadow:var(--shadow-md);animation:fadeInUp .8s ease .4s both}
.map-placeholder{height:180px;background:linear-gradient(135deg,#E0F2FA,#ECFDF5);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.5rem;color:var(--text-light);font-size:.88rem}
.map-placeholder .map-icon{font-size:2.5rem}
.map-address{padding:1rem 1.4rem;font-size:.88rem;color:var(--text-light)}
/* Form card */
.form-card{background:white;border-radius:24px;box-shadow:var(--shadow-lg);padding:2.5rem;animation:fadeInUp .8s ease .3s both;position:relative;overflow:hidden}
.form-card::before{content:'';position:absolute;top:0;left:0;right:0;height:5px;background:linear-gradient(90deg,var(--primary),var(--accent),var(--secondary))}
.form-header{margin-bottom:2rem}
.form-header h2{font-family:'Sora',sans-serif;font-size:1.6rem;font-weight:700;margin-bottom:.4rem}
.form-header p{color:var(--text-light);font-size:.92rem}
/* Alert */
.alert{padding:1rem 1.2rem;border-radius:14px;margin-bottom:1.5rem;font-size:.9rem;font-weight:600;display:flex;align-items:flex-start;gap:.7rem}
.alert.success{background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0}
.alert.error{background:#FEE2E2;color:#991B1B;border:1px solid #FECACA}
.alert ul{margin:.3rem 0 0 1rem}
.alert li{margin-bottom:.2rem;font-size:.85rem}
/* Form */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:1.2rem}
.form-group{margin-bottom:1.4rem}
.form-label{display:block;font-weight:600;font-size:.85rem;margin-bottom:.5rem;color:var(--text-dark)}
.form-label .req{color:#EF4444;margin-left:.2rem}
.form-input,.form-select,.form-textarea{width:100%;padding:.85rem 1.1rem;border:2px solid #E2E8F0;border-radius:12px;font-family:'Manrope',sans-serif;font-size:.95rem;color:var(--text-dark);background:white;outline:none;transition:all .3s}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(10,126,164,.08)}
.form-input:not(:placeholder-shown):valid{border-color:rgba(16,185,129,.5)}
.form-select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 1rem center;padding-right:2.5rem;cursor:pointer}
.form-textarea{resize:vertical;min-height:130px;line-height:1.6}
.char-count{font-size:.75rem;color:var(--text-light);text-align:right;margin-top:.3rem}
.btn-submit{width:100%;padding:1rem;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:white;border:none;border-radius:50px;font-weight:700;font-size:1rem;cursor:pointer;transition:all .3s;font-family:'Manrope',sans-serif;box-shadow:0 4px 15px rgba(10,126,164,.35);display:flex;align-items:center;justify-content:center;gap:.6rem;margin-top:.5rem;position:relative;overflow:hidden}
.btn-submit::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.2),transparent);transition:left .5s}
.btn-submit:hover{transform:translateY(-3px);box-shadow:0 8px 25px rgba(10,126,164,.5)}
.btn-submit:hover::before{left:100%}
.btn-submit:active{transform:translateY(0)}
/* Success state */
.success-box{text-align:center;padding:2rem 1rem}
.success-icon{font-size:4rem;margin-bottom:1rem;animation:popIn .5s cubic-bezier(.34,1.56,.64,1)}
@keyframes popIn{from{opacity:0;transform:scale(.5)}to{opacity:1;transform:scale(1)}}
.success-box h3{font-family:'Sora',sans-serif;font-size:1.5rem;font-weight:700;margin-bottom:.6rem;color:var(--primary)}
.success-box p{color:var(--text-light);font-size:.95rem;margin-bottom:1.5rem}
.btn-another{display:inline-block;padding:.7rem 2rem;background:var(--primary);color:white;border-radius:50px;font-weight:600;font-size:.9rem;text-decoration:none;transition:all .3s}
.btn-another:hover{background:var(--primary-dark);transform:translateY(-2px)}
/* ─── Footer ─── */
.footer{background:var(--text-dark);color:white;padding:4rem 2rem 2rem}
.footer-content{max-width:1400px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:3rem;margin-bottom:3rem}
.footer-section h3{font-family:'Sora',sans-serif;font-size:1.3rem;margin-bottom:1.5rem}
.footer-section p,.footer-section a{color:rgba(255,255,255,.7);text-decoration:none;display:block;margin-bottom:.8rem;transition:color .3s}
.footer-section a:hover{color:var(--secondary)}
.social-links{display:flex;gap:1rem;margin-top:1rem}
.social-link{width:40px;height:40px;background:rgba(255,255,255,.1);border-radius:50%;overflow:hidden;object-fit:cover;display:block}
.footer-bottom{max-width:1400px;margin:0 auto;padding-top:2rem;border-top:1px solid rgba(255,255,255,.1);text-align:center;color:rgba(255,255,255,.5)}
/* ─── Responsive ─── */
@media(max-width:968px){.contact-grid{grid-template-columns:1fr}}
@media(max-width:768px){
  .mobile-toggle{display:flex}
  .nav-actions{display:none}
  .nav-menu{position:fixed;top:80px;left:0;right:0;background:white;flex-direction:column;padding:2rem;gap:1.5rem;box-shadow:var(--shadow-lg);transform:translateX(-100%);transition:transform .3s}
  .nav-menu.active{transform:translateX(0)}
  .mobile-auth{display:flex !important;flex-direction:column;gap:.8rem;padding-top:1.2rem;border-top:1px solid #E2E8F0;width:100%}
  .page-title{font-size:2.2rem}
  .form-row{grid-template-columns:1fr}
  .form-card{padding:1.8rem}
}
</style>
</head>
<body>

<header class="header" id="header">
  <nav class="nav-container">
    <a href="index.php" class="logo">
      <img src="images/logo.jpeg" alt="Jettransfer" class="logo-img"> Jettransfer
    </a>
    <ul class="nav-menu" id="navMenu">
      <li><a href="index.php">Home</a></li>
      <li><a href="destination.php">Destinations</a></li>
      <li><a href="packages.php">Packages</a></li>
      <li><a href="vehicles.html">Vehicles</a></li>
      <li><a href="service.php">Services &amp; Gallery</a></li>
      <li><a href="contact.php" class="active">Contact Us</a></li>
      <li><a href="aboutus.php">About Us</a></li>

      <?php if ($isLoggedIn): ?>
        <li><a href="profile.php">Profile</a></li>
      <?php endif; ?>
      <!-- Mobile auth area -->
      <li class="mobile-auth">
        <?php if ($isLoggedIn): ?>
          <a href="profile.php" style="display:block;padding:.6rem 1rem;border-radius:50px;border:2px solid var(--primary);color:var(--primary);font-weight:600;font-size:.9rem;text-decoration:none;text-align:center">👤 <?= $userName ?></a>
          <a href="logout.php" style="display:block;padding:.6rem 1rem;border-radius:50px;border:2px solid #EF4444;color:#EF4444;font-weight:600;font-size:.9rem;text-decoration:none;text-align:center">🚪 Logout</a>
        <?php else: ?>
          <a href="login.php" style="display:block;padding:.6rem 1rem;border-radius:50px;border:2px solid var(--primary);color:var(--primary);font-weight:600;font-size:.9rem;text-decoration:none;text-align:center">Login</a>
          <a href="register.php" style="display:block;padding:.6rem 1rem;border-radius:50px;background:var(--primary);color:white;font-weight:600;font-size:.9rem;text-decoration:none;text-align:center">Register</a>
        <?php endif; ?>
      </li>
    </ul>
    <div class="nav-actions">
      <div class="nav-search" id="navSearch">
        <button class="search-toggle" id="searchToggle">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </button>
        <div class="search-box" id="searchBox">
          <input type="text" placeholder="Search destinations…" id="searchInput">
          <button class="search-submit"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></button>
        </div>
      </div>

      <?php if ($isLoggedIn): ?>
        <!-- ✅ Logged in: show profile dropdown -->
        <div class="profile-dropdown" id="profileDropdown">
          <button class="profile-pill" onclick="toggleDD()">
            <div class="pill-avatar"><?= $userInitials ?></div>
            <span><?= htmlspecialchars(explode(' ', $userName)[0]) ?></span>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="dropdown-menu" id="dropdownMenu">
            <div class="dd-header">
              <div class="dd-avatar"><?= $userInitials ?></div>
              <div><div class="dd-name"><?= $userName ?></div><div class="dd-email"><?= $userEmail ?></div></div>
            </div>
            <div class="dd-divider"></div>
            <a href="profile.php" class="dd-item">👤 My Profile</a>
            <a href="editprofile.php" class="dd-item">✏️ Edit Profile</a>
            <a href="profile.php?tab=bookings" class="dd-item">📅 My Bookings</a>
            <div class="dd-divider"></div>
            <a href="logout.php" class="dd-item dd-logout">🚪 Logout</a>
          </div>
        </div>
      <?php else: ?>
        <!-- ✅ Not logged in: show Login/Register -->
        <a href="login.php" class="btn-nav-login">Login</a>
        <a href="register.php" class="btn-nav-register">Register</a>
      <?php endif; ?>
    </div>
    <div class="mobile-toggle" id="mobileToggle"><span></span><span></span><span></span></div>
  </nav>
</header>

<section class="page-header">
  <h1 class="page-title">Contact Us</h1>
  <p class="page-description">Have a question or ready to plan your Sri Lanka adventure? We'd love to hear from you!</p>
</section>

<section class="contact-section">
  <div class="contact-grid">

    <!-- LEFT: Info + Map -->
    <div class="contact-info">
      <div class="info-card">
        <div class="info-icon blue">📞</div>
        <div class="info-content">
          <h3>Call Us</h3>
          <a href="tel:+94724030499">+94 72 403 0499</a>
          <p style="margin-top:.3rem;font-size:.8rem">Mon – Sun: 8:00 AM – 8:00 PM</p>
        </div>
      </div>
      <div class="info-card">
        <div class="info-icon green">📧</div>
        <div class="info-content">
          <h3>Email Us</h3>
          <a href="mailto:jettransfer@outlook.com">jettransfer@outlook.com</a>
          <p style="margin-top:.3rem;font-size:.8rem">We reply within 24 hours</p>
        </div>
      </div>
      <div class="info-card">
        <div class="info-icon amber">📍</div>
        <div class="info-content">
          <h3>Our Location</h3>
          <p>Ruwani Uyana, Matara</p>
          <p>Southern Province, Sri Lanka</p>
        </div>
      </div>
      <div class="info-card">
        <div class="info-icon purple">🕐</div>
        <div class="info-content">
          <h3>Business Hours</h3>
          <p>Mon – Fri: 8:00 AM – 6:00 PM</p>
          <p>Sat – Sun: 10:00 AM – 6:00 PM</p>
          <p style="color:var(--accent);font-weight:600;margin-top:.3rem;font-size:.82rem">24/7 Emergency Support ✅</p>
        </div>
      </div>  
      
    </div>

    <!-- RIGHT: Contact Form -->
    <div class="form-card">
      <?php if ($success): ?>
        <!-- ✅ SUCCESS STATE -->
        <div class="success-box">
          <div class="success-icon">✅</div>
          <h3>Message Sent!</h3>
          <p>Thank you for reaching out. Our team will get back to you within 24 hours.</p>
          <a href="contact.php" class="btn-another">Send Another Message</a>
        </div>
      <?php else: ?>
        <div class="form-header">
          <h2>Send Us a Message</h2>
          <p>Fill in the form below and we'll get back to you as soon as possible.</p>
        </div>

        <?php if (!empty($errors)): ?>
          <div class="alert error">
            <span style="font-size:1.2rem;flex-shrink:0">⚠️</span>
            <div>Please fix the following:
              <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
            </div>
          </div>
        <?php endif; ?>

        <form method="POST" id="contactForm">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="name">Full Name <span class="req">*</span></label>
              <input type="text" id="name" name="name" class="form-input"
                     placeholder="Your full name" required
                     value="<?= htmlspecialchars($_POST['name'] ?? ($isLoggedIn ? $userName : '')) ?>">
            </div>
            <div class="form-group">
              <label class="form-label" for="email">Email Address <span class="req">*</span></label>
              <input type="email" id="email" name="email" class="form-input"
                     placeholder="your@email.com" required
                     value="<?= htmlspecialchars($_POST['email'] ?? ($isLoggedIn ? $userEmail : '')) ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="phone">Phone Number</label>
              <input type="tel" id="phone" name="phone" class="form-input"
                     placeholder="+94 77 123 4567"
                     value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label" for="topic">Topic <span class="req">*</span></label>
              <select id="topic" name="topic" class="form-select">
                <?php
                $topics = ['General Inquiry','Tour Package','Vehicle Hire','Airport Transfer','Hotel Booking','Custom Tour','Pricing & Quote','Other'];
                $selTopic = $_POST['topic'] ?? 'General Inquiry';
                foreach ($topics as $tp):
                ?>
                  <option value="<?= $tp ?>" <?= $selTopic === $tp ? 'selected' : '' ?>><?= $tp ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="message">Your Message <span class="req">*</span></label>
            <textarea id="message" name="message" class="form-textarea"
                      placeholder="Tell us about your travel plans, dates, number of people, or any questions you have…"
                      required maxlength="2000"
                      oninput="updateCharCount(this)"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            <div class="char-count"><span id="charCount">0</span> / 2000 characters</div>
          </div>

          <button type="submit" class="btn-submit">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            Send Message
          </button>
        </form>
      <?php endif; ?>
    </div>

  </div>
</section>

<footer class="footer">
  <div class="footer-content">
    <div class="footer-section">
      <h3>Jettransfer Travels</h3>
      <p>Your trusted partner for exploring the beauty of Sri Lanka with comfort, safety, and professionalism.</p>
      <div class="social-links">
        <img src="images/insta1.webp" alt="Instagram" class="social-link">
        <img src="images/fb.avif" alt="Facebook" class="social-link">
        <img src="images/tiktok.webp" alt="TikTok" class="social-link">
      </div>
    </div>
    <div class="footer-section">
      <h3>Quick Links</h3>
      <a href="destination.php">Destinations</a>
      <a href="packages.php">Tour Packages</a>
      <a href="vehicles.html">Our Vehicles</a>
      <a href="service.php">Services &amp; Gallery</a>
      <a href="contact.php">Contact Us</a>
    </div>
    <div class="footer-section">
      <h3>Contact Us</h3>
      <p>📞 +94 72 403 0499</p>
      <p>📧 jettransfer@outlook.com</p>
      <p>📍 Ruwani Uyana, Matara, Sri Lanka</p>
    </div>
    <div class="footer-section">
      <h3>Business Hours</h3>
      <p>Monday – Friday: 8:00 AM – 6:00 PM</p>
      <p>Saturday: 10:00 AM – 6:00 PM</p>
      <p>Sunday: 10:00 AM – 6:00 PM</p>
      <p style="margin-top:1rem;color:var(--secondary)">24/7 Emergency Support Available</p>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; 2026 Jettransfer Travels. All rights reserved. | Designed with care for your journey</p>
  </div>
</footer>

<script>
// Nav
const mobileToggle = document.getElementById('mobileToggle');
const navMenu = document.getElementById('navMenu');
mobileToggle.addEventListener('click', () => navMenu.classList.toggle('active'));
document.querySelectorAll('.nav-menu a').forEach(a => a.addEventListener('click', () => navMenu.classList.remove('active')));
window.addEventListener('scroll', () => document.getElementById('header').classList.toggle('scrolled', window.scrollY > 100));

// Search
const st = document.getElementById('searchToggle'), sb = document.getElementById('searchBox');
st.addEventListener('click', e => { e.stopPropagation(); sb.classList.toggle('open'); if (sb.classList.contains('open')) setTimeout(() => document.getElementById('searchInput').focus(), 300); });
document.addEventListener('click', e => { if (!document.getElementById('navSearch').contains(e.target)) sb.classList.remove('open'); });

// Profile dropdown
function toggleDD() { document.getElementById('dropdownMenu')?.classList.toggle('open'); }
document.addEventListener('click', e => {
  const dd = document.getElementById('profileDropdown');
  if (dd && !dd.contains(e.target)) document.getElementById('dropdownMenu')?.classList.remove('open');
});

// Char count
function updateCharCount(el) {
  document.getElementById('charCount').textContent = el.value.length;
}
// Init char count if message has content (e.g. after failed submit)
const msgEl = document.getElementById('message');
if (msgEl) updateCharCount(msgEl);
</script>
</body>
</html>
