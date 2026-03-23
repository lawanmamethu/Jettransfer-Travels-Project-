<?php
// ── register.php ──────────────────────────────────────────────
session_start();
if (isset($_SESSION['user_id'])) { header('Location: profile.php'); exit; }

require_once 'db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fn   = trim($_POST['firstname']       ?? '');
    $ln   = trim($_POST['lastname']        ?? '');
    $em   = trim($_POST['email']           ?? '');
    $ph   = trim($_POST['phone']           ?? '');
    $pass = $_POST['password']             ?? '';
    $conf = $_POST['confirm_password']     ?? '';

    if (!$fn || !$ln || !$em || !$pass) {
        $error = 'All required fields must be filled.';
    } elseif (!filter_var($em, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $conf) {
        $error = 'Passwords do not match.';
    } else {
        $e = $conn->real_escape_string($em);
        $chk = $conn->query("SELECT id FROM users WHERE email='$e' LIMIT 1");
        if ($chk && $chk->num_rows > 0) {
            $error = 'This email address is already registered.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $f  = $conn->real_escape_string($fn);
            $l  = $conn->real_escape_string($ln);
            $p  = $conn->real_escape_string($ph);
            $h  = $conn->real_escape_string($hash);
            $sql = "INSERT INTO users (first_name,last_name,email,phone,password_hash,is_active)
                    VALUES ('$f','$l','$e','$p','$h',1)";
            if ($conn->query($sql)) {
                $conn->close();
                header('Location: login.php?registered=1');
                exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
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
<title>Register – Jettransfer Travels</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--primary:#0A7EA4;--primary-dark:#065A7A;--secondary:#F59E0B;--accent:#10B981;--text-dark:#0F172A;--text-light:#64748B;--bg-light:#F8FAFC;--white:#FFFFFF;--shadow-sm:0 1px 3px rgba(0,0,0,.08);--shadow-md:0 4px 12px rgba(0,0,0,.1);--shadow-lg:0 10px 40px rgba(0,0,0,.15)}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Manrope',sans-serif;color:var(--text-dark);background:var(--bg-light);position:relative}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(circle at 10% 20%,rgba(10,126,164,.03) 0,transparent 30%),radial-gradient(circle at 90% 70%,rgba(16,185,129,.03) 0,transparent 30%);pointer-events:none;z-index:0}
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
.nav-actions{display:flex;align-items:center;gap:.6rem;flex-shrink:0}
.nav-search{position:relative;display:flex;align-items:center}
.search-toggle{width:38px;height:38px;border-radius:50%;border:2px solid #E2E8F0;background:transparent;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text-dark);transition:all .3s}
.search-toggle:hover{border-color:var(--primary);color:var(--primary)}
.search-box{position:absolute;right:0;top:50%;transform:translateY(-50%);display:flex;align-items:center;background:white;border:2px solid var(--primary);border-radius:50px;overflow:hidden;width:0;opacity:0;pointer-events:none;transition:width .4s,opacity .3s;box-shadow:var(--shadow-md);z-index:10}
.search-box.open{width:280px;opacity:1;pointer-events:all}
.search-box input{border:none;outline:none;padding:.55rem 1rem;font-family:'Manrope',sans-serif;font-size:.88rem;width:100%;background:transparent}
.search-submit{background:var(--primary);border:none;padding:.55rem 1rem;color:white;cursor:pointer;display:flex;align-items:center;flex-shrink:0}
.btn-nav-login{padding:.5rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:.88rem;color:var(--primary);border:2px solid var(--primary);transition:all .3s;white-space:nowrap}
.btn-nav-login:hover{background:var(--primary);color:white;transform:translateY(-2px)}
.btn-nav-register{padding:.5rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:.88rem;background:var(--primary);color:white;border:2px solid var(--primary);transition:all .3s;white-space:nowrap}
.btn-nav-register:hover{background:var(--primary-dark);transform:translateY(-2px)}
.mobile-toggle{display:none;flex-direction:column;gap:5px;cursor:pointer;padding:8px}
.mobile-toggle span{width:25px;height:3px;background:var(--text-dark);border-radius:2px}
.mobile-auth{display:none}
.page-header{margin-top:80px;padding:3rem 2rem 2rem;background:linear-gradient(135deg,rgba(10,126,164,.05),rgba(16,185,129,.05));text-align:center;position:relative;overflow:hidden}
.page-header::before{content:'✈️';position:absolute;top:20px;left:10%;font-size:3rem;opacity:.1}
.page-header::after{content:'🌴';position:absolute;bottom:10px;right:10%;font-size:3.5rem;opacity:.1}
.page-title{font-family:'Sora',sans-serif;font-size:2.8rem;font-weight:800;margin-bottom:.5rem;animation:fadeInUp .8s ease;position:relative;display:inline-block}
.page-title::after{content:'';position:absolute;bottom:-10px;left:50%;transform:translateX(-50%);width:80px;height:4px;background:linear-gradient(90deg,var(--primary),var(--accent));border-radius:2px}
.page-description{font-size:1.1rem;color:var(--text-light);max-width:700px;margin:1.5rem auto 0;animation:fadeInUp .8s ease .2s both}
@keyframes fadeInUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
.register-section{max-width:620px;margin:3rem auto 5rem;padding:0 2rem;position:relative;z-index:2}
.register-card{background:white;border-radius:30px;box-shadow:var(--shadow-lg);padding:2.5rem;position:relative;overflow:hidden}
.register-card::before{content:'';position:absolute;top:0;left:0;right:0;height:5px;background:linear-gradient(90deg,var(--primary),var(--accent),var(--secondary))}
.register-header{text-align:center;margin-bottom:2rem}
.register-header h2{font-family:'Sora',sans-serif;font-size:2rem;font-weight:700;color:var(--primary);margin-bottom:.5rem}
.register-header p{color:var(--text-light)}
.alert-error{background:#FEE2E2;color:#991B1B;border:1px solid #FECACA;padding:.8rem 1.1rem;border-radius:12px;margin-bottom:1.4rem;font-size:.88rem;font-weight:600}
.form-row-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.form-group{margin-bottom:1.3rem}
.form-label{display:block;font-weight:600;font-size:.9rem;margin-bottom:.5rem}
.form-input{width:100%;padding:.85rem 1.1rem;border:2px solid #E2E8F0;border-radius:12px;font-family:'Manrope',sans-serif;font-size:.95rem;outline:none;transition:all .3s;background:white}
.form-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(10,126,164,.1)}
.form-input:not(:placeholder-shown){border-color:rgba(10,126,164,.3)}
.terms-check{display:flex;align-items:center;gap:.6rem;font-size:.88rem;color:var(--text-light);margin-bottom:1.5rem;cursor:pointer;padding:.4rem;border-radius:8px}
.terms-check:hover{background:var(--bg-light)}
.terms-check a{color:var(--primary);text-decoration:none;font-weight:500}
.terms-check a:hover{text-decoration:underline}
.btn-submit{width:100%;padding:1rem;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:white;border:none;border-radius:50px;font-weight:700;font-size:1rem;cursor:pointer;transition:all .3s;margin-bottom:1.5rem;font-family:'Manrope',sans-serif;position:relative;overflow:hidden}
.btn-submit:hover{transform:translateY(-3px);box-shadow:0 8px 25px rgba(10,126,164,.5)}
.login-prompt{text-align:center;font-size:.95rem;color:var(--text-light);padding:1rem 0 0;border-top:2px dashed var(--bg-light)}
.login-prompt a{color:var(--primary);text-decoration:none;font-weight:600}
.footer{background:var(--text-dark);color:white;padding:4rem 2rem 2rem}
.footer-content{max-width:1400px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:3rem;margin-bottom:3rem}
.footer-section h3{font-family:'Sora',sans-serif;font-size:1.3rem;margin-bottom:1.5rem}
.footer-section p,.footer-section a{color:rgba(255,255,255,.7);text-decoration:none;display:block;margin-bottom:.8rem;transition:color .3s}
.footer-section a:hover{color:var(--secondary)}
.social-links{display:flex;gap:1rem;margin-top:1rem}
.social-link{width:40px;height:40px;background:rgba(255,255,255,.1);border-radius:50%;overflow:hidden;object-fit:cover;display:block}
.footer-bottom{max-width:1400px;margin:0 auto;padding-top:2rem;border-top:1px solid rgba(255,255,255,.1);text-align:center;color:rgba(255,255,255,.5)}
@media(max-width:768px){.mobile-toggle{display:flex}.nav-actions{display:none}.nav-menu{position:fixed;top:80px;left:0;right:0;background:white;flex-direction:column;padding:2rem;gap:1.5rem;box-shadow:var(--shadow-lg);transform:translateX(-100%);transition:transform .3s}.nav-menu.active{transform:translateX(0)}.mobile-auth{display:flex !important;flex-direction:column;gap:.8rem;padding-top:1.2rem;border-top:1px solid #E2E8F0;width:100%}.form-row-2{grid-template-columns:1fr}.page-title{font-size:2.2rem}.register-card{padding:2rem}}
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
  <h1 class="page-title">Create an Account</h1>
  <p class="page-description">Join Jettransfer Travels to manage bookings and get exclusive offers</p>
</section>

<section class="register-section">
  <div class="register-card">
    <div class="register-header"><h2>Register</h2><p>Fill in your details to get started</p></div>
    <?php if ($error): ?>
      <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-row-2">
        <div class="form-group">
          <label class="form-label">First Name *</label>
          <input type="text" name="firstname" class="form-input" placeholder="John" required value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Last Name *</label>
          <input type="text" name="lastname" class="form-input" placeholder="Doe" required value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Email Address *</label>
        <input type="email" name="email" class="form-input" placeholder="your@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Phone Number</label>
        <input type="tel" name="phone" class="form-input" placeholder="+94 77 123 4567" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
      </div>
      <div class="form-row-2">
        <div class="form-group">
          <label class="form-label">Password * (min 6)</label>
          <input type="password" name="password" class="form-input" placeholder="••••••••" required>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password *</label>
          <input type="password" name="confirm_password" class="form-input" placeholder="••••••••" required>
        </div>
      </div>
      <label class="terms-check">
        <input type="checkbox" required>
        I agree to the <a href="terms.html">Terms of Service</a> and <a href="terms.html">Privacy Policy</a>
      </label>
      <button type="submit" class="btn-submit">Create Account</button>
      <div class="login-prompt">Already have an account? <a href="login.php">Login here</a></div>
    </form>
  </div>
</section>

<footer class="footer">
  <div class="footer-content">
    <div class="footer-section"><h3>Jettransfer Travels</h3><p>Your trusted partner for exploring the beauty of Sri Lanka.</p><div class="social-links"><img src="images/insta1.webp" alt="Instagram" class="social-link"><img src="images/fb.avif" alt="Facebook" class="social-link"><img src="images/tiktok.webp" alt="TikTok" class="social-link"></div></div>
    <div class="footer-section"><h3>Quick Links</h3><a href="destination.php">Destinations</a><a href="package.php">Tour Packages</a><a href="service.php">Services &amp; Gallery</a><a href="contact.php">Contact Us</a></div>
    <div class="footer-section"><h3>Contact Us</h3><p>📞 +94 72 403 0499</p><p>📧 jettransfer@outlook.com</p><p>📍 Ruwani Uyana, Matara, Sri Lanka</p></div>
    <div class="footer-section"><h3>Business Hours</h3><p>Mon–Fri: 8:00 AM – 6:00 PM</p><p>Sat–Sun: 10:00 AM – 6:00 PM</p><p style="margin-top:1rem;color:var(--secondary)">24/7 Emergency Support</p></div>
  </div>
  <div class="footer-bottom"><p>&copy; 2026 Jettransfer Travels. All rights reserved.</p></div>
</footer>
<script>
document.getElementById('mobileToggle').addEventListener('click',()=>document.getElementById('navMenu').classList.toggle('active'));
document.querySelectorAll('.nav-menu a').forEach(a=>a.addEventListener('click',()=>document.getElementById('navMenu').classList.remove('active')));
window.addEventListener('scroll',()=>document.getElementById('header').classList.toggle('scrolled',window.scrollY>100));
const st=document.getElementById('searchToggle'),sb=document.getElementById('searchBox');
st.addEventListener('click',e=>{e.stopPropagation();sb.classList.toggle('open');if(sb.classList.contains('open'))setTimeout(()=>document.getElementById('searchInput').focus(),300)});
document.addEventListener('click',e=>{if(!document.getElementById('navSearch').contains(e.target))sb.classList.remove('open')});
</script>
</body>
</html>
