<?php
session_start();
require_once 'db.php';

// Dynamic nav
$isLoggedIn   = isset($_SESSION['user_id']);
$userName     = $isLoggedIn ? htmlspecialchars($_SESSION['user_name'] ?? 'User') : '';
$userEmail    = $isLoggedIn ? htmlspecialchars($_SESSION['user_email'] ?? '') : '';
$parts        = explode(' ', trim($userName));
$userInitials = $isLoggedIn ? strtoupper(substr($parts[0]??'U',0,1).(isset($parts[1])?substr($parts[1],0,1):'')) : '';

// Handle booking form
$bookingSuccess = false;
$bookingError   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_submit'])) {
    $name    = trim($_POST['customer_name'] ?? '');
    $email   = trim($_POST['email']         ?? '');
    $phone   = trim($_POST['phone']         ?? '');
    $date    = trim($_POST['travel_date']   ?? '');
    $guests  = (int)($_POST['guests']       ?? 0);
    $pkgName = trim($_POST['package_name']  ?? '');
    $price   = trim($_POST['price']         ?? '');

    if (!$name || !filter_var($email,FILTER_VALIDATE_EMAIL) || !$phone || !$date || !$guests || !$pkgName) {
        $bookingError = 'Please fill in all fields correctly.';
    } else {
        $n  = $conn->real_escape_string($name);
        $e  = $conn->real_escape_string($email);
        $p  = $conn->real_escape_string($phone);
        $d  = $conn->real_escape_string($date);
        $pn = $conn->real_escape_string($pkgName);
        $pr = $conn->real_escape_string($price);
        $sql = "INSERT INTO bookings(package_name,price,customer_name,email,phone,travel_date,guests,status)
                VALUES('$pn','$pr','$n','$e','$p','$d',$guests,'Pending')";
        if ($conn->query($sql)) {
            $bookingSuccess = true;
        } else {
            $bookingError = 'Could not save booking. Please try again.';
        }
    }
}

// Load packages from DB (only active ones, ordered by sort_order then id)
$pkgRes   = $conn->query("SELECT * FROM packages WHERE is_active=1 ORDER BY sort_order ASC, id ASC");
$packages = [];
if ($pkgRes) while ($r = $pkgRes->fetch_assoc()) $packages[] = $r;
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tour Packages – Jettransfer Travels</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--primary:#0A7EA4;--primary-dark:#065A7A;--secondary:#F59E0B;--accent:#10B981;--text-dark:#0F172A;--text-light:#64748B;--bg-light:#F8FAFC;--white:#FFFFFF;--shadow-sm:0 1px 3px rgba(0,0,0,.08);--shadow-md:0 4px 12px rgba(0,0,0,.1);--shadow-lg:0 10px 40px rgba(0,0,0,.15)}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Manrope',sans-serif;color:var(--text-dark);background:var(--bg-light);overflow-x:hidden}
/* ─── Header ─── */
.header{position:fixed;top:0;left:0;right:0;background:rgba(255,255,255,.95);backdrop-filter:blur(10px);box-shadow:var(--shadow-sm);z-index:1000;transition:all .3s}
.header.scrolled{box-shadow:var(--shadow-md)}
.nav-container{max-width:1400px;margin:0 auto;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center}
.logo{font-family:'Sora',sans-serif;font-size:1.3rem;font-weight:800;color:var(--primary);text-decoration:none;display:flex;align-items:center;gap:.6rem;white-space:nowrap}
.logo-img{width:42px;height:42px;border-radius:12px;object-fit:cover;border:1px solid rgba(2,6,23,.10);background:#fff}
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
/* Auth buttons */
.btn-nav-login{padding:.5rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:.88rem;color:var(--primary);border:2px solid var(--primary);transition:all .3s;white-space:nowrap}
.btn-nav-login:hover{background:var(--primary);color:white;transform:translateY(-2px)}
.btn-nav-reg{padding:.5rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:.88rem;background:var(--primary);color:white;border:2px solid var(--primary);transition:all .3s;white-space:nowrap}
.btn-nav-reg:hover{background:var(--primary-dark);transform:translateY(-2px)}
/* Profile dropdown */
.profile-dropdown{position:relative}
.profile-pill{display:flex;align-items:center;gap:.5rem;padding:.4rem .9rem .4rem .45rem;border-radius:50px;border:2px solid #E2E8F0;background:white;cursor:pointer;font-family:'Manrope',sans-serif;font-weight:600;font-size:.88rem;color:var(--text-dark);transition:all .25s}
.profile-pill:hover{border-color:var(--primary);box-shadow:0 2px 12px rgba(10,126,164,.15)}
.pill-av{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#fff;flex-shrink:0}
.dd-menu{position:absolute;right:0;top:calc(100% + .6rem);background:white;border:1px solid #E2E8F0;border-radius:18px;box-shadow:0 10px 40px rgba(0,0,0,.12);min-width:220px;z-index:1001;opacity:0;transform:translateY(-8px) scale(.97);pointer-events:none;transition:all .22s}
.dd-menu.open{opacity:1;transform:translateY(0) scale(1);pointer-events:all}
.dd-hdr{display:flex;align-items:center;gap:.7rem;padding:1rem 1.1rem .8rem}
.dd-av{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:700;color:#fff}
.dd-name{font-weight:700;font-size:.9rem;color:var(--text-dark);margin-bottom:.1rem}
.dd-email{font-size:.73rem;color:#94A3B8}
.dd-div{height:1px;background:#F1F5F9;margin:.3rem 0}
.dd-item{display:flex;align-items:center;gap:.6rem;padding:.65rem 1rem;color:var(--text-dark);text-decoration:none;font-size:.88rem;font-weight:500;transition:background .15s;border-radius:10px;margin:.1rem .4rem}
.dd-item:hover{background:#F1F5F9;color:var(--primary)}
.dd-out{color:#EF4444!important}
.dd-out:hover{background:#FEE2E2!important}
.mobile-toggle{display:none;flex-direction:column;gap:5px;cursor:pointer;padding:8px}
.mobile-toggle span{width:25px;height:3px;background:var(--text-dark);border-radius:2px}
.mobile-auth{display:none}
/* ─── Hero ─── */
.hero{margin-top:80px;height:50vh;min-height:340px;position:relative;display:flex;align-items:center;overflow:hidden}
.hero-video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.hero-overlay{position:absolute;inset:0;background:rgba(0,0,0,.42)}
.hero-content{position:relative;z-index:2;max-width:1400px;margin:0 auto;padding:0 2rem;color:white;animation:fadeInUp .9s ease-out both}
.hero h1{font-family:'Sora',sans-serif;font-size:3.2rem;font-weight:800;line-height:1.1;margin-bottom:1rem}
.hero-sub{font-size:1.2rem;opacity:.92;max-width:560px}
@keyframes fadeInUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
/* ─── Packages section ─── */
.section{padding:5rem 2rem}
.container{max-width:1400px;margin:0 auto}
.section-header{text-align:center;margin-bottom:3.5rem}
.section-tag{display:inline-block;color:var(--primary);font-weight:600;text-transform:uppercase;font-size:.85rem;letter-spacing:1.5px;margin-bottom:.8rem}
.section-title{font-family:'Sora',sans-serif;font-size:2.6rem;font-weight:700;margin-bottom:.8rem}
.section-desc{color:var(--text-light);font-size:1.1rem;max-width:680px;margin:0 auto}
/* Filter bar */
.filter-bar{background:white;border-radius:16px;box-shadow:var(--shadow-md);padding:1.6rem 2rem;margin-bottom:2.5rem;display:flex;flex-wrap:wrap;gap:1.2rem;align-items:center}
.filter-search{flex:1;min-width:200px;position:relative}
.filter-search input{width:100%;padding:.75rem 1.2rem .75rem 3rem;border:2px solid #E2E8F0;border-radius:50px;font-family:inherit;font-size:.95rem;outline:none;transition:border-color .3s}
.filter-search input:focus{border-color:var(--primary)}
.filter-search .si{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--text-light);pointer-events:none}
.filter-group{display:flex;align-items:center;gap:.7rem;flex-wrap:wrap}
.filter-label{font-weight:600;font-size:.88rem;color:var(--text-light);white-space:nowrap}
.fbtn{padding:.5rem 1.2rem;border-radius:50px;border:2px solid #E2E8F0;background:transparent;font-family:inherit;font-size:.85rem;font-weight:600;color:var(--text-light);cursor:pointer;transition:all .25s;white-space:nowrap}
.fbtn:hover,.fbtn.active{background:var(--primary);border-color:var(--primary);color:white}
.results-info{font-size:.93rem;color:var(--text-light);margin-bottom:1.8rem}
.results-info strong{color:var(--primary)}
/* Package cards */
.packages-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:2rem}
.pkg-card{background:white;border-radius:16px;overflow:hidden;box-shadow:var(--shadow-md);transition:transform .4s,box-shadow .4s;display:flex;flex-direction:column}
.pkg-card:hover{transform:translateY(-10px);box-shadow:var(--shadow-lg)}
.pkg-img{position:relative;height:230px;overflow:hidden}
.pkg-img img{width:100%;height:100%;object-fit:cover;transition:transform .6s}
.pkg-card:hover .pkg-img img{transform:scale(1.08)}
.pkg-img::after{content:'';position:absolute;inset:0;background:linear-gradient(to bottom,transparent 45%,rgba(10,26,60,.5) 100%)}
.pkg-dur{position:absolute;bottom:1rem;right:1rem;background:rgba(255,255,255,.92);color:var(--primary);font-weight:700;font-size:.8rem;padding:.32rem .85rem;border-radius:50px;z-index:2}
.pkg-body{padding:1.6rem;display:flex;flex-direction:column;flex:1}
.pkg-name{font-family:'Sora',sans-serif;font-size:1.25rem;font-weight:700;margin-bottom:.5rem}
.pkg-desc{color:var(--text-light);font-size:.9rem;line-height:1.65;margin-bottom:1.1rem}
.pkg-meta{display:grid;grid-template-columns:1fr 1fr;gap:.55rem .8rem;margin-bottom:1.1rem}
.pm-item{display:flex;align-items:center;gap:.4rem;font-size:.83rem;color:var(--text-light)}
.pm-icon{width:26px;height:26px;background:rgba(10,126,164,.1);border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0}
.stars{color:var(--secondary);font-size:.88rem}
.pkg-divider{height:1px;background:#E2E8F0;margin:.9rem 0}
.pkg-footer{display:flex;justify-content:space-between;align-items:center;margin-top:auto}
.pkg-price-lbl{font-size:.75rem;color:var(--text-light);margin-bottom:.12rem}
.pkg-price{font-family:'Sora',sans-serif;font-size:1.35rem;font-weight:800;color:var(--primary)}
.pkg-price span{font-size:.77rem;font-weight:500;color:var(--text-light)}
.btn-book{background:var(--primary);color:white;padding:.65rem 1.5rem;border-radius:50px;font-weight:700;font-size:.88rem;border:none;cursor:pointer;transition:all .3s;box-shadow:0 4px 15px rgba(10,126,164,.3)}
.btn-book:hover{background:var(--primary-dark);transform:translateY(-2px)}
.no-results{display:none;flex-direction:column;align-items:center;text-align:center;padding:5rem 2rem;color:var(--text-light)}
.no-results .nr-icon{font-size:3rem;margin-bottom:1rem}
.no-results h3{font-family:'Sora',sans-serif;font-size:1.4rem;margin-bottom:.5rem;color:var(--text-dark)}
/* Why strip */
.why-strip{background:linear-gradient(135deg,var(--primary),var(--primary-dark));padding:3.5rem 2rem;color:white}
.why-strip .container{display:flex;justify-content:space-around;flex-wrap:wrap;gap:2rem;text-align:center}
.why-item{flex:1;min-width:130px}
.wi-icon{font-size:2rem;margin-bottom:.5rem}
.why-item h4{font-family:'Sora',sans-serif;font-size:1rem;font-weight:700;margin-bottom:.3rem}
.why-item p{font-size:.85rem;opacity:.82}
/* Footer */
.footer{background:var(--text-dark);color:white;padding:4rem 2rem 2rem}
.footer-content{max-width:1400px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:3rem;margin-bottom:3rem}
.footer-section h3{font-family:'Sora',sans-serif;font-size:1.2rem;margin-bottom:1.4rem}
.footer-section p,.footer-section a{color:rgba(255,255,255,.68);text-decoration:none;display:block;margin-bottom:.7rem;font-size:.92rem;transition:color .3s}
.footer-section a:hover{color:var(--secondary)}
.social-links{display:flex;gap:.8rem;margin-top:1rem}
.social-link{width:38px;height:38px;background:rgba(255,255,255,.1);border-radius:50%;overflow:hidden;object-fit:cover;display:block}
.footer-bottom{max-width:1400px;margin:0 auto;padding-top:2rem;border-top:1px solid rgba(255,255,255,.1);text-align:center;color:rgba(255,255,255,.45);font-size:.87rem}
/* Fade in */
.fade-in{opacity:0;transform:translateY(28px);transition:opacity .75s,transform .75s}
.fade-in.visible{opacity:1;transform:translateY(0)}
/* Booking modal */
#bookingModal{display:none;position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:9999;align-items:center;justify-content:center}
.bm-box{background:white;border-radius:20px;padding:2.5rem;max-width:460px;width:92%;box-shadow:0 20px 60px rgba(0,0,0,.3);position:relative}
.bm-close{position:absolute;top:1rem;right:1.2rem;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#64748B}
.bm-title{font-family:'Sora',sans-serif;color:var(--primary);margin-bottom:.3rem;font-size:1.4rem;font-weight:700}
.bm-sub{color:#64748B;margin-bottom:1.8rem;font-size:.9rem}
.bm-form{display:flex;flex-direction:column;gap:1rem}
.bm-input{padding:.75rem 1rem;border:2px solid #E2E8F0;border-radius:10px;font-family:inherit;font-size:.95rem;outline:none;transition:border-color .3s;width:100%}
.bm-input:focus{border-color:var(--primary)}
.bm-btn{background:var(--primary);color:white;padding:.9rem;border:none;border-radius:50px;font-family:inherit;font-size:1rem;font-weight:700;cursor:pointer;transition:background .3s;box-shadow:0 4px 15px rgba(10,126,164,.3)}
.bm-btn:hover{background:var(--primary-dark)}
@media(max-width:768px){
  .mobile-toggle{display:flex}
  .nav-actions{display:none}
  .nav-menu{position:fixed;top:80px;left:0;right:0;background:white;flex-direction:column;padding:2rem;gap:1.5rem;box-shadow:var(--shadow-lg);transform:translateX(-100%);transition:transform .3s}
  .nav-menu.active{transform:translateX(0)}
  .mobile-auth{display:flex !important;flex-direction:column;gap:.8rem;padding-top:1.2rem;border-top:1px solid #E2E8F0;width:100%}
  .hero h1{font-size:2rem}
  .hero-sub{font-size:1rem}
  .section-title{font-size:1.9rem}
  .packages-grid{grid-template-columns:1fr}
  .filter-bar{flex-direction:column;align-items:stretch}
}
</style>
</head>
<body>

<?php if ($bookingSuccess): ?>
<script>
window.addEventListener('load',function(){
  const t=document.createElement('div');
  t.textContent='✅ Booking confirmed! Our team will contact you shortly.';
  Object.assign(t.style,{position:'fixed',bottom:'2rem',left:'50%',transform:'translateX(-50%)',background:'#065A7A',color:'white',padding:'.9rem 2rem',borderRadius:'50px',fontFamily:'Manrope,sans-serif',fontWeight:'600',fontSize:'.95rem',boxShadow:'0 8px 30px rgba(0,0,0,.25)',zIndex:'99999',whiteSpace:'nowrap',opacity:'1',transition:'opacity .5s'});
  document.body.appendChild(t);
  setTimeout(()=>{t.style.opacity='0'},3500);
  setTimeout(()=>t.remove(),4000);
});
</script>
<?php endif; ?>
<?php if ($bookingError): ?>
<script>window.addEventListener('load',function(){alert('❌ <?= htmlspecialchars($bookingError) ?>');})</script>
<?php endif; ?>

<header class="header" id="header">
  <nav class="nav-container">
    <a href="index.php" class="logo"><img src="images/logo.jpeg" alt="Jettransfer" class="logo-img">Jettransfer</a>
    <ul class="nav-menu" id="navMenu">
      <li><a href="index.php">Home</a></li>
      <li><a href="destination.php">Destinations</a></li>
      <li><a href="packages.php" class="active">Packages</a></li>
      <li><a href="vehicles.html">Vehicles</a></li>
      <li><a href="service.php">Services &amp; Gallery</a></li>
      <li><a href="contact.php">Contact Us</a></li>
      <li><a href="aboutus.php">About Us</a></li>
      
      <?php if ($isLoggedIn): ?><li><a href="profile.php">Profile</a></li><?php endif; ?>
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
        <button class="search-toggle" id="searchToggle"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></button>
        <div class="search-box" id="searchBox"><input type="text" placeholder="Search packages…" id="searchInputNav"><button class="search-submit"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></button></div>
      </div>
      <?php if ($isLoggedIn): ?>
        <div class="profile-dropdown" id="profileDD">
          <button class="profile-pill" onclick="toggleDD()">
            <div class="pill-av"><?= $userInitials ?></div>
            <span><?= htmlspecialchars(explode(' ',$userName)[0]) ?></span>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="dd-menu" id="ddMenu">
            <div class="dd-hdr"><div class="dd-av"><?= $userInitials ?></div><div><div class="dd-name"><?= $userName ?></div><div class="dd-email"><?= $userEmail ?></div></div></div>
            <div class="dd-div"></div>
            <a href="profile.php" class="dd-item">👤 My Profile</a>
            <a href="editprofile.php" class="dd-item">✏️ Edit Profile</a>
            <a href="profile.php?tab=bookings" class="dd-item">📅 My Bookings</a>
            <div class="dd-div"></div>
            <a href="logout.php" class="dd-item dd-out">🚪 Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a href="login.php" class="btn-nav-login">Login</a>
        <a href="register.php" class="btn-nav-reg">Register</a>
      <?php endif; ?>
    </div>
    <div class="mobile-toggle" id="mobileToggle"><span></span><span></span><span></span></div>
  </nav>
</header>

<!-- Hero -->
<section class="hero">
  <video autoplay loop muted playsinline poster="images/colombocitytour.jpeg" class="hero-video">
    <source src="video.mp4" type="video/mp4">
  </video>
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <h1>Tour Packages</h1>
    <p class="hero-sub">Handcrafted itineraries across Sri Lanka — from one-day escapes to full island adventures.</p>
  </div>
</section>

<!-- Packages -->
<section class="section" id="packages">
  <div class="container">
    <div class="section-header fade-in">
      <span class="section-tag">Explore &amp; Book</span>
      <h2 class="section-title">Our Tour Packages</h2>
      <p class="section-desc">Find the perfect package for every traveller — from city highlights to the full island experience</p>
    </div>

    <div class="filter-bar fade-in">
      <div class="filter-search">
        <span class="si">🔍</span>
        <input type="text" id="searchInput" placeholder="Search packages…" autocomplete="off">
      </div>
      <div class="filter-group">
        <span class="filter-label">Duration:</span>
        <button class="fbtn active" data-filter="duration" data-value="all">All</button>
        <button class="fbtn" data-filter="duration" data-value="1">1 Day</button>
        <button class="fbtn" data-filter="duration" data-value="4">4 Days</button>
        <button class="fbtn" data-filter="duration" data-value="5">5 Days</button>
        <button class="fbtn" data-filter="duration" data-value="7">7 Days</button>
        <button class="fbtn" data-filter="duration" data-value="10">10 Days</button>
        <button class="fbtn" data-filter="duration" data-value="12">12 Days</button>
        <button class="fbtn" data-filter="duration" data-value="14">14 Days</button>
      </div>
      <div class="filter-group">
        <span class="filter-label">Price:</span>
        <button class="fbtn active" data-filter="price" data-value="all">All</button>
        <button class="fbtn" data-filter="price" data-value="budget">Under LKR 50K</button>
        <button class="fbtn" data-filter="price" data-value="mid">50K – 100K</button>
        <button class="fbtn" data-filter="price" data-value="luxury">100K+</button>
      </div>
    </div>

    <p class="results-info fade-in">Showing <strong id="resultCount"><?= count($packages) ?></strong> packages</p>

    <div class="packages-grid" id="packagesGrid">
      <?php foreach ($packages as $p):
        $imgSrc = $p['image'] ?: 'images/colombocitytour.jpeg';
        $priceFmt = 'LKR ' . number_format($p['price'], 0);
      ?>
      <div class="pkg-card fade-in"
           data-duration="<?= $p['duration'] ?>"
           data-price-tier="<?= htmlspecialchars($p['price_tier']) ?>"
           data-name="<?= strtolower(htmlspecialchars($p['name'])) ?>">
        <div class="pkg-img">
          <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy" onerror="this.src='images/colombocitytour.jpeg'">
          <span class="pkg-dur"><?= $p['duration'] ?> Day<?= $p['duration']>1?'s':'' ?></span>
        </div>
        <div class="pkg-body">
          <h3 class="pkg-name"><?= htmlspecialchars($p['name']) ?></h3>
          <p class="pkg-desc"><?= htmlspecialchars($p['description']) ?></p>
          <div class="pkg-meta">
            <div class="pm-item"><span class="pm-icon">⏱</span><span><?= $p['duration'] ?> Day<?= $p['duration']>1?'s':'' ?> / <?= max(0,$p['duration']-1) ?> Night<?= $p['duration']>1?'s':'' ?></span></div>
            <div class="pm-item"><span class="pm-icon">📍</span><span><?= htmlspecialchars($p['locations']) ?></span></div>
            <div class="pm-item"><span class="pm-icon">🚗</span><span><?= htmlspecialchars($p['vehicle']) ?></span></div>
            <div class="pm-item"><span class="pm-icon">🏨</span><span class="stars"><?= htmlspecialchars($p['hotel_rating']) ?> Hotel</span></div>
            <div class="pm-item"><span class="pm-icon">👥</span><span><?= htmlspecialchars($p['group_size']) ?></span></div>
          </div>
          <div class="pkg-divider"></div>
          <div class="pkg-footer">
            <div>
              <p class="pkg-price-lbl">Price per person</p>
              <p class="pkg-price"><?= $priceFmt ?> <span>/ person</span></p>
            </div>
            <button class="btn-book" onclick="openBooking('<?= htmlspecialchars(addslashes($p['name'])) ?>','<?= $priceFmt ?>')">Book Now</button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($packages)): ?>
        <div style="grid-column:1/-1;text-align:center;padding:4rem;color:var(--text-light)">
          <p style="font-size:3rem;margin-bottom:1rem">📦</p>
          <p style="font-weight:600;font-size:1.1rem">No packages available yet</p>
          <p style="font-size:.9rem;margin-top:.5rem">Check back soon or <a href="contact.php" style="color:var(--primary)">contact us</a> for a custom itinerary.</p>
        </div>
      <?php endif; ?>
    </div>

    <div class="no-results" id="noResults">
      <div class="nr-icon">🔍</div>
      <h3>No packages found</h3>
      <p>Try adjusting your search or filter criteria.</p>
    </div>
  </div>
</section>

<!-- Why strip -->
<div class="why-strip">
  <div class="container">
    <div class="why-item"><div class="wi-icon">🎯</div><h4>Expert Local Guides</h4><p>Seasoned guides with insider knowledge</p></div>
    <div class="why-item"><div class="wi-icon">🚗</div><h4>Premium Fleet</h4><p>AC vehicles maintained to top standards</p></div>
    <div class="why-item"><div class="wi-icon">⏰</div><h4>24/7 Support</h4><p>Round-the-clock assistance for you</p></div>
    <div class="why-item"><div class="wi-icon">✨</div><h4>Custom Itineraries</h4><p>Plans tailored to your needs &amp; budget</p></div>
    <div class="why-item"><div class="wi-icon">🛡</div><h4>Safe &amp; Reliable</h4><p>Fully licensed and insured services</p></div>
  </div>
</div>

<!-- Booking modal -->
<div id="bookingModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:9999;align-items:center;justify-content:center">
  <div class="bm-box">
    <button class="bm-close" onclick="closeBooking()">✕</button>
    <h2 class="bm-title" id="modalTitle">Book Package</h2>
    <p class="bm-sub" id="modalPrice"></p>
    <form method="POST" action="packages.php" class="bm-form">
      <input type="hidden" name="book_submit" value="1">
      <input type="hidden" name="package_name" id="hiddenPkg">
      <input type="hidden" name="price" id="hiddenPrice">
      <?php if ($isLoggedIn): ?>
        <input type="text"  name="customer_name" class="bm-input" placeholder="Your Full Name" required value="<?= $userName ?>">
        <input type="email" name="email"          class="bm-input" placeholder="Email Address"  required value="<?= $userEmail ?>">
      <?php else: ?>
        <input type="text"  name="customer_name" class="bm-input" placeholder="Your Full Name" required>
        <input type="email" name="email"          class="bm-input" placeholder="Email Address"  required>
      <?php endif; ?>
      <input type="tel"  name="phone"        class="bm-input" placeholder="Phone Number"  required>
      <input type="date" name="travel_date"  class="bm-input" required style="color:var(--text-dark)">
      <select name="guests" class="bm-input" required style="cursor:pointer">
        <option value="">Number of Guests</option>
        <?php for($i=1;$i<=10;$i++): ?><option value="<?= $i ?>"><?= $i ?><?= $i===10?'+':'' ?></option><?php endfor; ?>
      </select>
      <button type="submit" class="bm-btn">Confirm Booking ✓</button>
    </form>
  </div>
</div>

<!-- Footer -->
<footer class="footer">
  <div class="footer-content">
    <div class="footer-section"><h3>Jettransfer Travels</h3><p>Your trusted partner for exploring the beauty of Sri Lanka with comfort, safety, and professionalism.</p><div class="social-links"><img src="images/insta1.webp" alt="Instagram" class="social-link"><img src="images/fb.avif" alt="Facebook" class="social-link"><img src="images/tiktok.webp" alt="TikTok" class="social-link"></div></div>
    <div class="footer-section"><h3>Quick Links</h3><a href="destination.php">Destinations</a><a href="packages.php">Tour Packages</a><a href="vehicles.html">Our Vehicles</a><a href="aboutus.php">About Us</a></div>
    <div class="footer-section"><h3>Contact Us</h3><p>📞 +94 72 403 0499</p><p>📧 jettransfer@outlook.com</p><p>📍 Ruwani Uyana, Matara, Sri Lanka</p></div>
    <div class="footer-section"><h3>Business Hours</h3><p>Mon–Fri: 8:00 AM – 6:00 PM</p><p>Sat–Sun: 10:00 AM – 6:00 PM</p><p style="margin-top:1rem;color:var(--secondary)">24/7 Emergency Support</p></div>
  </div>
  <div class="footer-bottom"><p>&copy; 2026 Jettransfer Travels. All rights reserved. | Designed with care for your journey</p></div>
</footer>

<script>
// Nav
document.getElementById('mobileToggle').addEventListener('click',()=>document.getElementById('navMenu').classList.toggle('active'));
document.querySelectorAll('.nav-menu a').forEach(a=>a.addEventListener('click',()=>document.getElementById('navMenu').classList.remove('active')));
window.addEventListener('scroll',()=>document.getElementById('header').classList.toggle('scrolled',window.scrollY>80));
// Search toggle
const st=document.getElementById('searchToggle'),sb=document.getElementById('searchBox');
st.addEventListener('click',e=>{e.stopPropagation();sb.classList.toggle('open');if(sb.classList.contains('open'))setTimeout(()=>document.getElementById('searchInputNav').focus(),300)});
document.addEventListener('click',e=>{if(!document.getElementById('navSearch').contains(e.target))sb.classList.remove('open')});
// Profile dropdown
function toggleDD(){document.getElementById('ddMenu')?.classList.toggle('open')}
document.addEventListener('click',e=>{const d=document.getElementById('profileDD');if(d&&!d.contains(e.target))document.getElementById('ddMenu')?.classList.remove('open')});
// Fade in
const obs=new IntersectionObserver((entries)=>{entries.forEach((e,i)=>{if(e.isIntersecting)setTimeout(()=>e.target.classList.add('visible'),i*80)})},{threshold:.08,rootMargin:'0px 0px -40px 0px'});
document.querySelectorAll('.fade-in').forEach(el=>obs.observe(el));
// Filters
let activeDur='all', activePrc='all';
const cards=document.querySelectorAll('.pkg-card');
const rc=document.getElementById('resultCount');
const nr=document.getElementById('noResults');
function applyFilters(){
  const q=document.getElementById('searchInput').value.toLowerCase().trim();
  let vis=0;
  cards.forEach(c=>{
    const dm=activeDur==='all'||c.dataset.duration===activeDur;
    const pm=activePrc==='all'||c.dataset.priceTier===activePrc;
    const sm=!q||c.dataset.name.includes(q);
    const show=dm&&pm&&sm;
    c.style.display=show?'':'none';
    if(show)vis++;
  });
  rc.textContent=vis;
  nr.style.display=vis===0?'flex':'none';
}
document.querySelectorAll('[data-filter="duration"]').forEach(b=>b.addEventListener('click',()=>{document.querySelectorAll('[data-filter="duration"]').forEach(x=>x.classList.remove('active'));b.classList.add('active');activeDur=b.dataset.value;applyFilters()}));
document.querySelectorAll('[data-filter="price"]').forEach(b=>b.addEventListener('click',()=>{document.querySelectorAll('[data-filter="price"]').forEach(x=>x.classList.remove('active'));b.classList.add('active');activePrc=b.dataset.value;applyFilters()}));
document.getElementById('searchInput').addEventListener('input',applyFilters);
// Booking modal
function openBooking(name,price){
  document.getElementById('modalTitle').textContent='📅 Book: '+name;
  document.getElementById('modalPrice').textContent='Starting from '+price+' per person';
  document.getElementById('hiddenPkg').value=name;
  document.getElementById('hiddenPrice').value=price;
  document.getElementById('bookingModal').style.display='flex';
  document.body.style.overflow='hidden';
}
function closeBooking(){document.getElementById('bookingModal').style.display='none';document.body.style.overflow=''}
document.getElementById('bookingModal').addEventListener('click',function(e){if(e.target===this)closeBooking()});
</script>
</body>
</html>
