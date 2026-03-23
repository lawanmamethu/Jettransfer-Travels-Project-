<?php
// ── forgot-password.php ───────────────────────────────────────
session_start();
if (isset($_SESSION['user_id'])) { header('Location: profile.php'); exit; }
$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    // In production: generate token, save to DB, send real email
    // For now we just show success (prevents email enumeration)
    $sent = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password – Jettransfer Travels</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--primary:#0A7EA4;--primary-dark:#065A7A;--secondary:#F59E0B;--accent:#10B981;--text-dark:#0F172A;--text-light:#64748B;--bg-light:#F8FAFC;--white:#FFFFFF;--shadow-sm:0 1px 3px rgba(0,0,0,.08);--shadow-md:0 4px 12px rgba(0,0,0,.1);--shadow-lg:0 10px 40px rgba(0,0,0,.15)}
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
.nav-menu a:hover::after{width:100%}
.nav-menu a:hover{color:var(--primary)}
.nav-actions{display:flex;align-items:center;gap:.6rem}
.btn-nav-login{padding:.5rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:.88rem;color:var(--primary);border:2px solid var(--primary);transition:all .3s;white-space:nowrap}
.btn-nav-login:hover{background:var(--primary);color:white;transform:translateY(-2px)}
.btn-nav-register{padding:.5rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:.88rem;background:var(--primary);color:white;border:2px solid var(--primary);transition:all .3s;white-space:nowrap}
.btn-nav-register:hover{background:var(--primary-dark);transform:translateY(-2px)}
.mobile-toggle{display:none;flex-direction:column;gap:5px;cursor:pointer;padding:8px}
.mobile-toggle span{width:25px;height:3px;background:var(--text-dark);border-radius:2px}
.mobile-auth{display:none}
.page-header{margin-top:80px;padding:3rem 2rem 2rem;background:linear-gradient(135deg,rgba(10,126,164,.05),rgba(16,185,129,.05));text-align:center}
.page-title{font-family:'Sora',sans-serif;font-size:2.8rem;font-weight:800;margin-bottom:.5rem;animation:fadeInUp .8s ease}
.page-description{font-size:1.1rem;color:var(--text-light);max-width:700px;margin:0 auto;animation:fadeInUp .8s ease .2s both}
@keyframes fadeInUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
.forgot-section{max-width:520px;margin:3rem auto 5rem;padding:0 2rem}
.forgot-card{background:white;border-radius:30px;box-shadow:var(--shadow-lg);padding:2.5rem;animation:fadeInUp .8s ease .3s both}
.forgot-header{text-align:center;margin-bottom:2rem}
.forgot-header h2{font-family:'Sora',sans-serif;font-size:2rem;font-weight:700;color:var(--primary);margin-bottom:.5rem}
.forgot-header p{color:var(--text-light)}
.info-box{background:#EFF6FF;border-left:4px solid var(--primary);padding:1rem 1.2rem;border-radius:12px;margin-bottom:1.8rem;font-size:.9rem;display:flex;align-items:flex-start;gap:.8rem;color:var(--text-dark)}
.info-icon{font-size:1.4rem;flex-shrink:0;margin-top:.1rem}
.alert-success{background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0;padding:1rem 1.2rem;border-radius:12px;margin-bottom:1.5rem;font-weight:600;display:flex;align-items:center;gap:.6rem}
.form-group{margin-bottom:1.5rem}
.form-label{display:block;font-weight:600;font-size:.9rem;margin-bottom:.5rem}
.form-input{width:100%;padding:.9rem 1.2rem;border:2px solid #E2E8F0;border-radius:12px;font-family:'Manrope',sans-serif;font-size:.95rem;outline:none;transition:all .3s}
.form-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(10,126,164,.1)}
.btn-submit{width:100%;padding:1rem;background:var(--primary);color:white;border:none;border-radius:50px;font-weight:700;font-size:1rem;cursor:pointer;transition:all .3s;margin-bottom:1.5rem;font-family:'Manrope',sans-serif;box-shadow:0 4px 12px rgba(10,126,164,.3)}
.btn-submit:hover{background:var(--primary-dark);transform:translateY(-2px)}
.back-link{text-align:center}
.back-link a{color:var(--primary);text-decoration:none;font-weight:600;display:inline-flex;align-items:center;gap:.4rem;font-size:.95rem}
.back-link a:hover{text-decoration:underline}
.footer{background:var(--text-dark);color:white;padding:4rem 2rem 2rem;margin-top:2rem}
.footer-content{max-width:1400px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:3rem;margin-bottom:3rem}
.footer-section h3{font-family:'Sora',sans-serif;font-size:1.3rem;margin-bottom:1.5rem}
.footer-section p,.footer-section a{color:rgba(255,255,255,.7);text-decoration:none;display:block;margin-bottom:.8rem}
.footer-section a:hover{color:var(--secondary)}
.social-links{display:flex;gap:1rem;margin-top:1rem}
.social-link{width:40px;height:40px;background:rgba(255,255,255,.1);border-radius:50%;overflow:hidden;object-fit:cover;display:block}
.footer-bottom{max-width:1400px;margin:0 auto;padding-top:2rem;border-top:1px solid rgba(255,255,255,.1);text-align:center;color:rgba(255,255,255,.5)}
@media(max-width:768px){.mobile-toggle{display:flex}.nav-actions{display:none}.nav-menu{position:fixed;top:80px;left:0;right:0;background:white;flex-direction:column;padding:2rem;gap:1.5rem;box-shadow:var(--shadow-lg);transform:translateX(-100%);transition:transform .3s}.nav-menu.active{transform:translateX(0)}.mobile-auth{display:flex !important;flex-direction:column;gap:.8rem;padding-top:1.2rem;border-top:1px solid #E2E8F0;width:100%}.page-title{font-size:2.2rem}}
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
      
      <li class="mobile-auth">
        <a href="login.php" style="display:block;padding:.6rem 1rem;border-radius:50px;border:2px solid var(--primary);color:var(--primary);font-weight:600;font-size:.9rem;text-decoration:none;text-align:center">Login</a>
        <a href="register.php" style="display:block;padding:.6rem 1rem;border-radius:50px;background:var(--primary);color:white;font-weight:600;font-size:.9rem;text-decoration:none;text-align:center">Register</a>
      </li>
    </ul>
    <div class="nav-actions">
      <a href="login.php" class="btn-nav-login">Login</a>
      <a href="register.php" class="btn-nav-register">Register</a>
    </div>
    <div class="mobile-toggle" id="mobileToggle"><span></span><span></span><span></span></div>
  </nav>
</header>

<section class="page-header">
  <h1 class="page-title">Reset Password</h1>
  <p class="page-description">We'll help you get back into your account</p>
</section>

<section class="forgot-section">
  <div class="forgot-card">
    <div class="forgot-header">
      <h2>Forgot Password?</h2>
      <p>No worries, we'll help you reset it.</p>
    </div>
    <?php if ($sent): ?>
      <div class="alert-success">✅ If an account exists with that email, a reset link has been sent. Check your inbox.</div>
      <div class="back-link"><a href="login.php">← Back to Login</a></div>
    <?php else: ?>
      <div class="info-box">
        <span class="info-icon">🔐</span>
        <span>Enter your registered email address and we'll send you a secure link to reset your password.</span>
      </div>
      <form method="POST">
        <div class="form-group">
          <label class="form-label" for="email">Email Address</label>
          <input type="email" id="email" name="email" class="form-input" placeholder="your@email.com" required>
        </div>
        <button type="submit" class="btn-submit">Send Reset Link</button>
      </form>
      <div class="back-link"><a href="login.php">← Back to Login</a></div>
    <?php endif; ?>
  </div>
</section>

<footer class="footer">
  <div class="footer-content">
    <div class="footer-section"><h3>Jettransfer Travels</h3><p>Your trusted partner for exploring Sri Lanka.</p><div class="social-links"><img src="images/insta1.webp" alt="Instagram" class="social-link"><img src="images/fb.avif" alt="Facebook" class="social-link"><img src="images/tiktok.webp" alt="TikTok" class="social-link"></div></div>
    <div class="footer-section"><h3>Quick Links</h3><a href="destination.php">Destinations</a><a href="package.php">Tour Packages</a><a href="service.php">Services &amp; Gallery</a></div>
    <div class="footer-section"><h3>Contact Us</h3><p>📞 +94 72 403 0499</p><p>📧 jettransfer@outlook.com</p><p>📍 Ruwani Uyana, Matara, Sri Lanka</p></div>
    <div class="footer-section"><h3>Business Hours</h3><p>Mon–Fri: 8:00 AM – 6:00 PM</p><p>Sat–Sun: 10:00 AM – 6:00 PM</p></div>
  </div>
  <div class="footer-bottom"><p>&copy; 2026 Jettransfer Travels. All rights reserved.</p></div>
</footer>
<script>
document.getElementById('mobileToggle').addEventListener('click',()=>document.getElementById('navMenu').classList.toggle('active'));
document.querySelectorAll('.nav-menu a').forEach(a=>a.addEventListener('click',()=>document.getElementById('navMenu').classList.remove('active')));
window.addEventListener('scroll',()=>document.getElementById('header').classList.toggle('scrolled',window.scrollY>100));
</script>
</body>
</html>
