<?php
// ============================================================
//  packages.php — Tour Packages with Pure PHP Booking
//  Location: C:\xampp\htdocs\jettransfer\packages.php
// ============================================================
require_once __DIR__ . '/admin/db_packages.php';

$bookingSuccess = false;
$bookingError   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_submit'])) {
    $pdo = getDB();

    $name       = htmlspecialchars(strip_tags(trim($_POST['customer_name'] ?? '')));
    $email      = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone      = htmlspecialchars(strip_tags(trim($_POST['phone'] ?? '')));
    $travelDate = trim($_POST['travel_date'] ?? '');
    $guests     = (int)($_POST['guests'] ?? 0);
    $pkgName    = htmlspecialchars(strip_tags(trim($_POST['package_name'] ?? '')));
    $price      = htmlspecialchars(strip_tags(trim($_POST['price'] ?? '')));

    if (!$name || !$email || !$phone || !$travelDate || !$guests || !$pkgName) {
        $bookingError = 'Please fill in all fields correctly.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO bookings (package_name, price, customer_name, email, phone, travel_date, guests)
             VALUES (:pkg, :price, :name, :email, :phone, :date, :guests)'
        );
        $stmt->execute([
            ':pkg'    => $pkgName,
            ':price'  => $price,
            ':name'   => $name,
            ':email'  => $email,
            ':phone'  => $phone,
            ':date'   => $travelDate,
            ':guests' => $guests,
        ]);
        $bookingSuccess = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Packages – Jettransfer Travels</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0A7EA4;
            --primary-dark: #065A7A;
            --secondary: #F59E0B;
            --accent: #10B981;
            --text-dark: #0F172A;
            --text-light: #64748B;
            --bg-light: #F8FAFC;
            --white: #FFFFFF;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 40px rgba(0,0,0,0.15);
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Manrope',sans-serif; color:var(--text-dark); line-height:1.6; overflow-x:hidden; background:var(--bg-light); }

        /* ── HEADER ── */
        .header { position:fixed; top:0; left:0; right:0; background:rgba(255,255,255,0.95); backdrop-filter:blur(10px); box-shadow:var(--shadow-sm); z-index:1000; transition:all 0.3s ease; }
        .header.scrolled { box-shadow:var(--shadow-md); }
        .nav-container { max-width:1400px; margin:0 auto; padding:1rem 2rem; display:flex; justify-content:space-between; align-items:center; }
        .logo { font-family:'Sora',sans-serif; font-size:1.3rem; font-weight:800; color:var(--primary); text-decoration:none; display:flex; align-items:center; gap:.6rem; white-space:nowrap; }
        .logo-img { width:42px; height:42px; border-radius:12px; object-fit:cover; border:1px solid rgba(2,6,23,.10); box-shadow:0 10px 22px rgba(10,126,164,.15); background:#fff; }
        .nav-menu { display:flex; list-style:none; gap:1.6rem; align-items:center; }
        .nav-menu a { color:var(--text-dark); text-decoration:none; font-weight:500; font-size:0.88rem; transition:color 0.3s ease; position:relative; }
        .nav-menu a::after { content:''; position:absolute; bottom:-5px; left:0; width:0; height:2px; background:var(--primary); transition:width 0.3s ease; }
        .nav-menu a:hover::after, .nav-menu a.active::after { width:100%; }
        .nav-menu a:hover, .nav-menu a.active { color:var(--primary); }
        .nav-actions { display:flex; align-items:center; gap:0.6rem; flex-shrink:0; }
        .nav-search { position:relative; display:flex; align-items:center; }
        .search-toggle { width:38px; height:38px; border-radius:50%; border:2px solid #E2E8F0; background:transparent; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--text-dark); transition:all 0.3s ease; }
        .search-toggle:hover { border-color:var(--primary); color:var(--primary); background:var(--bg-light); }
        .search-box { position:absolute; right:0; top:50%; transform:translateY(-50%); display:flex; align-items:center; background:white; border:2px solid var(--primary); border-radius:50px; overflow:hidden; width:0; opacity:0; pointer-events:none; transition:width 0.4s ease, opacity 0.3s ease; box-shadow:var(--shadow-md); z-index:10; }
        .search-box.open { width:280px; opacity:1; pointer-events:all; }
        .search-box input { border:none; outline:none; padding:0.55rem 1rem; font-family:'Manrope',sans-serif; font-size:0.88rem; color:var(--text-dark); width:100%; background:transparent; }
        .search-submit { background:var(--primary); border:none; padding:0.55rem 1rem; color:white; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:background 0.3s; flex-shrink:0; }
        .search-submit:hover { background:var(--primary-dark); }
        .nav-auth { display:flex; align-items:center; gap:0.4rem; }
        .btn-login { padding:0.5rem 1.2rem; border-radius:50px; text-decoration:none; font-weight:600; font-size:0.88rem; color:var(--primary); border:2px solid var(--primary); transition:all 0.3s ease; white-space:nowrap; }
        .btn-login:hover { background:var(--primary); color:white; transform:translateY(-2px); }
        .btn-register { padding:0.5rem 1.2rem; border-radius:50px; text-decoration:none; font-weight:600; font-size:0.88rem; background:var(--primary); color:white; border:2px solid var(--primary); transition:all 0.3s ease; box-shadow:0 4px 12px rgba(10,126,164,0.3); white-space:nowrap; }
        .btn-register:hover { background:var(--primary-dark); border-color:var(--primary-dark); transform:translateY(-2px); }
        .mobile-auth { display:none; }
        .mobile-toggle { display:none; flex-direction:column; gap:5px; cursor:pointer; padding:8px; }
        .mobile-toggle span { width:25px; height:3px; background:var(--text-dark); transition:all 0.3s ease; border-radius:2px; }

        /* ── HERO ── */
        .hero { margin-top:80px; height:50vh; min-height:340px; background:linear-gradient(135deg,rgba(10,126,164,.92),rgba(16,185,129,.82)); display:flex; align-items:center; position:relative; overflow:hidden; }
        .hero::before { content:''; position:absolute; inset:0; background:radial-gradient(circle at 15% 50%,rgba(255,255,255,.1) 0%,transparent 55%),radial-gradient(circle at 85% 30%,rgba(245,158,11,.18) 0%,transparent 50%); animation:bgpulse 8s ease-in-out infinite; }
        @keyframes bgpulse { 0%,100%{opacity:.5} 50%{opacity:1} }
        .hero-circle { position:absolute; border-radius:50%; opacity:.12; background:white; }
        .hero-circle.c1 { width:280px; height:280px; top:-60px; right:80px; }
        .hero-circle.c2 { width:160px; height:160px; bottom:-40px; right:280px; }
        .hero-circle.c3 { width:100px; height:100px; top:50px; left:40%; }
        .hero-content { max-width:1400px; margin:0 auto; padding:0 2rem; color:white; z-index:1; animation:fadeInUp .9s ease-out both; }
        .hero h1 { font-family:'Sora',sans-serif; font-size:3.2rem; font-weight:800; line-height:1.1; margin-bottom:1rem; }
        .hero-subtitle { font-size:1.2rem; opacity:.92; max-width:560px; font-weight:400; }
        @keyframes fadeInUp { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }

        /* ── SECTION ── */
        .section { padding:5rem 2rem; }
        .container { max-width:1400px; margin:0 auto; }
        .section-header { text-align:center; margin-bottom:3.5rem; }
        .section-tag { display:inline-block; color:var(--primary); font-weight:600; text-transform:uppercase; font-size:.85rem; letter-spacing:1.5px; margin-bottom:.8rem; }
        .section-title { font-family:'Sora',sans-serif; font-size:2.6rem; font-weight:700; color:var(--text-dark); margin-bottom:.8rem; }
        .section-description { color:var(--text-light); font-size:1.1rem; max-width:680px; margin:0 auto; }

        /* ── FILTER BAR ── */
        .filter-bar { background:white; border-radius:16px; box-shadow:var(--shadow-md); padding:1.6rem 2rem; margin-bottom:2.5rem; display:flex; flex-wrap:wrap; gap:1.2rem; align-items:center; }
        .filter-search { flex:1; min-width:200px; position:relative; }
        .filter-search input { width:100%; padding:.75rem 1.2rem .75rem 3rem; border:2px solid #E2E8F0; border-radius:50px; font-family:inherit; font-size:.95rem; color:var(--text-dark); outline:none; transition:border-color .3s; }
        .filter-search input:focus { border-color:var(--primary); }
        .filter-search .search-icon { position:absolute; left:1rem; top:50%; transform:translateY(-50%); color:var(--text-light); font-size:1rem; pointer-events:none; }
        .filter-group { display:flex; align-items:center; gap:.7rem; flex-wrap:wrap; }
        .filter-label { font-weight:600; font-size:.88rem; color:var(--text-light); white-space:nowrap; }
        .filter-btn { padding:.5rem 1.2rem; border-radius:50px; border:2px solid #E2E8F0; background:transparent; font-family:inherit; font-size:.85rem; font-weight:600; color:var(--text-light); cursor:pointer; transition:all .25s; white-space:nowrap; }
        .filter-btn:hover, .filter-btn.active { background:var(--primary); border-color:var(--primary); color:white; }
        .results-info { font-size:.93rem; color:var(--text-light); margin-bottom:1.8rem; }
        .results-info strong { color:var(--primary); }

        /* ── PACKAGES GRID ── */
        .packages-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:2rem; }
        .pkg-card { background:white; border-radius:16px; overflow:hidden; box-shadow:var(--shadow-md); transition:transform .4s ease,box-shadow .4s ease; display:flex; flex-direction:column; width:100%; }
        .pkg-card:hover { transform:translateY(-10px); box-shadow:var(--shadow-lg); }
        .pkg-img { position:relative; height:230px; overflow:hidden; }
        .pkg-img img { width:100%; height:100%; object-fit:cover; transition:transform .6s ease; }
        .pkg-card:hover .pkg-img img { transform:scale(1.08); }
        .pkg-img::after { content:''; position:absolute; inset:0; background:linear-gradient(to bottom,transparent 45%,rgba(10,26,60,.5) 100%); }
        .pkg-duration-pill { position:absolute; bottom:1rem; right:1rem; background:rgba(255,255,255,.92); color:var(--primary); font-weight:700; font-size:.8rem; padding:.32rem .85rem; border-radius:50px; z-index:2; }
        .pkg-body { padding:1.6rem; display:flex; flex-direction:column; flex:1; }
        .pkg-name { font-family:'Sora',sans-serif; font-size:1.25rem; font-weight:700; color:var(--text-dark); margin-bottom:.5rem; }
        .pkg-desc { color:var(--text-light); font-size:.9rem; line-height:1.65; margin-bottom:1.1rem; }
        .pkg-meta { display:grid; grid-template-columns:1fr 1fr; gap:.55rem .8rem; margin-bottom:1.1rem; }
        .pkg-meta-item { display:flex; align-items:center; gap:.4rem; font-size:.83rem; color:var(--text-light); }
        .mi-icon { width:26px; height:26px; background:rgba(10,126,164,.1); border-radius:7px; display:flex; align-items:center; justify-content:center; font-size:.8rem; flex-shrink:0; }
        .stars { color:var(--secondary); font-size:.88rem; }
        .pkg-divider { height:1px; background:#E2E8F0; margin:.9rem 0; }
        .pkg-footer { display:flex; justify-content:space-between; align-items:center; margin-top:auto; }
        .pkg-price-label { font-size:.75rem; color:var(--text-light); margin-bottom:.12rem; }
        .pkg-price { font-family:'Sora',sans-serif; font-size:1.35rem; font-weight:800; color:var(--primary); }
        .pkg-price span { font-size:.77rem; font-weight:500; color:var(--text-light); }
        .btn-book { background:var(--primary); color:white; padding:.65rem 1.5rem; border-radius:50px; font-weight:700; font-size:.88rem; border:none; cursor:pointer; transition:all .3s; box-shadow:0 4px 15px rgba(10,126,164,.3); white-space:nowrap; }
        .btn-book:hover { background:var(--primary-dark); transform:translateY(-2px); }
        .no-results { display:none; flex-direction:column; align-items:center; text-align:center; padding:5rem 2rem; color:var(--text-light); }
        .no-results .nr-icon { font-size:3rem; margin-bottom:1rem; }
        .no-results h3 { font-family:'Sora',sans-serif; font-size:1.4rem; margin-bottom:.5rem; color:var(--text-dark); }

        /* ── WHY STRIP ── */
        .why-strip { background:linear-gradient(135deg,var(--primary),var(--primary-dark)); padding:3.5rem 2rem; color:white; }
        .why-strip .container { display:flex; justify-content:space-around; flex-wrap:wrap; gap:2rem; text-align:center; }
        .why-item { flex:1; min-width:130px; }
        .why-item .wi-icon { font-size:2rem; margin-bottom:.5rem; }
        .why-item h4 { font-family:'Sora',sans-serif; font-size:1rem; font-weight:700; margin-bottom:.3rem; }
        .why-item p { font-size:.85rem; opacity:.82; }

        /* ── FOOTER ── */
        .footer { background:var(--text-dark); color:white; padding:4rem 2rem 2rem; }
        .footer-content { max-width:1400px; margin:0 auto; display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:3rem; margin-bottom:3rem; }
        .footer-section h3 { font-family:'Sora',sans-serif; font-size:1.2rem; margin-bottom:1.4rem; }
        .footer-section p,.footer-section a { color:rgba(255,255,255,.68); text-decoration:none; display:block; margin-bottom:.7rem; font-size:.92rem; transition:color .3s; }
        .footer-section a:hover { color:var(--secondary); }
        .social-links { display:flex; gap:.8rem; margin-top:1rem; }
        .social-link { width:38px; height:38px; background:rgba(255,255,255,.1); border-radius:50%; display:flex; align-items:center; justify-content:center; transition:all .3s; object-fit:cover; }
        .social-link:hover { background:var(--primary); transform:translateY(-3px); }
        .footer-bottom { max-width:1400px; margin:0 auto; padding-top:2rem; border-top:1px solid rgba(255,255,255,.1); text-align:center; color:rgba(255,255,255,.45); font-size:.87rem; }

        /* ── FADE IN ── */
        .fade-in { opacity:0; transform:translateY(28px); transition:opacity .75s ease,transform .75s ease; }
        .fade-in.visible { opacity:1; transform:translateY(0); }

        /* ── RESPONSIVE ── */
        @media(max-width:768px) {
            .mobile-toggle { display:flex; }
            .nav-actions { display:none; }
            .nav-menu { position:fixed; top:80px; left:0; right:0; background:white; flex-direction:column; padding:2rem; gap:1.5rem; box-shadow:var(--shadow-lg); transform:translateX(-100%); transition:transform .3s; }
            .nav-menu.active { transform:translateX(0); }
            .mobile-auth { display:flex !important; flex-direction:column; align-items:center; gap:.8rem; padding-top:1.2rem; border-top:1px solid #E2E8F0; width:100%; }
            .mobile-auth-btns { display:flex; gap:.8rem; width:100%; }
            .mobile-auth-btns a { flex:1; text-align:center; }
            .hero { height:auto; padding:4.5rem 0; min-height:unset; }
            .hero h1 { font-size:2rem; }
            .hero-subtitle { font-size:1rem; }
            .section-title { font-size:1.9rem; }
            .packages-grid { grid-template-columns:1fr; }
            .filter-bar { flex-direction:column; align-items:stretch; }
        }
    </style>
</head>
<body>

<?php if ($bookingSuccess): ?>
<script>
window.addEventListener('load', function() {
    const t = document.createElement('div');
    t.textContent = '✅ Booking confirmed! Our team will contact you shortly.';
    Object.assign(t.style, {
        position:'fixed', bottom:'2rem', left:'50%', transform:'translateX(-50%)',
        background:'#065A7A', color:'white', padding:'.9rem 2rem',
        borderRadius:'50px', fontFamily:'Manrope,sans-serif', fontWeight:'600',
        fontSize:'.95rem', boxShadow:'0 8px 30px rgba(0,0,0,.25)',
        zIndex:'99999', whiteSpace:'nowrap', opacity:'1', transition:'opacity .5s'
    });
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity='0'; }, 3500);
    setTimeout(() => t.remove(), 4000);
});
</script>
<?php endif; ?>

<?php if ($bookingError): ?>
<script>
window.addEventListener('load', function() {
    alert('❌ <?= htmlspecialchars($bookingError) ?>');
});
</script>
<?php endif; ?>

    <!-- HEADER -->
    <header class="header" id="header">
        <nav class="nav-container">
            <a href="index.php" class="logo">
                <img src="images/logo.jpeg" alt="Jettransfer Logo" class="logo-img">
                Jettransfer
            </a>
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php">Home</a></li>
                <li><a href="destination.php">Destinations</a></li>
                <li><a href="packages.php" class="active">Packages</a></li>
                <li><a href="vehicles.html">Vehicles</a></li>
                <li><a href="service.php">Services &amp; Gallery</a></li>
                <li><a href="contact.html">Contact Us</a></li>
                <li><a href="aboutus.php">About Us</a></li>

                <li class="mobile-auth">
                    <div class="mobile-auth-btns">
                        <a href="login.php" class="btn-login" style="display:block;padding:.6rem 1rem;border-radius:50px;border:2px solid var(--primary);color:var(--primary);font-weight:600;font-size:.9rem;text-decoration:none;text-align:center;">Login</a>
                        <a href="register.php" class="btn-register" style="display:block;padding:.6rem 1rem;border-radius:50px;background:var(--primary);color:white;font-weight:600;font-size:.9rem;text-decoration:none;text-align:center;">Register</a>
                    </div>
                </li>
            </ul>
            <div class="nav-actions">
                
                <div class="nav-auth">
                    <a href="login.php" class="btn-login">Login</a>
                    <a href="register.php" class="btn-register">Register</a>
                </div>
            </div>
            <div class="mobile-toggle" id="mobileToggle">
                <span></span><span></span><span></span>
            </div>
        </nav>
    </header>

    <!-- HERO -->
    <section class="hero" style="background:none;">
        <video autoplay loop muted playsinline poster="images/colombocitytour.jpeg"
            style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;z-index:0;pointer-events:none;">
            <source src="video.mp4" type="video/mp4">
        </video>
        <div style="position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);z-index:1;"></div>
        <div class="hero-circle c1" style="z-index:2;"></div>
        <div class="hero-circle c2" style="z-index:2;"></div>
        <div class="hero-circle c3" style="z-index:2;"></div>
        <div class="hero-content" style="position:relative;z-index:3;">
            <h1>Tour Packages</h1>
            <p class="hero-subtitle">Handcrafted itineraries across Sri Lanka — from one-day escapes to full island adventures.</p>
        </div>
    </section>

    <!-- PACKAGES SECTION -->
    <section class="section" id="packages">
        <div class="container">
            <div class="section-header fade-in">
                <span class="section-tag">Explore &amp; Book</span>
                <h2 class="section-title">Our Tour Packages</h2>
                <p class="section-description">Find the perfect package for every traveller — from city highlights to the full island experience</p>
            </div>

            <div class="filter-bar fade-in">
                <div class="filter-search">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchInput" placeholder="Search packages…" autocomplete="off">
                </div>
                <div class="filter-group">
                    <span class="filter-label">Duration:</span>
                    <button class="filter-btn active" data-filter="duration" data-value="all">All</button>
                    <button class="filter-btn" data-filter="duration" data-value="1">1 Day</button>
                    <button class="filter-btn" data-filter="duration" data-value="4">4 Days</button>
                    <button class="filter-btn" data-filter="duration" data-value="5">5 Days</button>
                    <button class="filter-btn" data-filter="duration" data-value="7">7 Days</button>
                    <button class="filter-btn" data-filter="duration" data-value="14">14 Days</button>
                </div>
                <div class="filter-group">
                    <span class="filter-label">Price:</span>
                    <button class="filter-btn active" data-filter="price" data-value="all">All</button>
                    <button class="filter-btn" data-filter="price" data-value="budget">Under LKR 50K</button>
                    <button class="filter-btn" data-filter="price" data-value="mid">50K – 100K</button>
                    <button class="filter-btn" data-filter="price" data-value="luxury">100K+</button>
                </div>
            </div>

            <p class="results-info fade-in">Showing <strong id="resultCount">10</strong> packages</p>

            <div class="packages-grid" id="packagesGrid">

                <div class="pkg-card fade-in" data-duration="1" data-price-tier="budget" data-name="colombo city tour">
                    <div class="pkg-img"><img src="images/colombocitytour.jpeg" alt="Colombo City" loading="lazy"><span class="pkg-duration-pill">1 Day</span></div>
                    <div class="pkg-body">
                        <h3 class="pkg-name">Colombo City Tour</h3>
                        <p class="pkg-desc">Discover Sri Lanka's vibrant capital — colonial landmarks, Pettah Market, the serene Gangaramaya Temple, and the breezy Galle Face promenade.</p>
                        <div class="pkg-meta">
                            <div class="pkg-meta-item"><span class="mi-icon">⏱</span><span>1 Day / 0 Nights</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">📍</span><span>Colombo City</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🚗</span><span>Toyota Prius, Honda Shuttle, Honda Vezel</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🏨</span><span class="stars">★★★ Hotel</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">👥</span><span>1–3 People</span></div>
                        </div>
                        <div class="pkg-divider"></div>
                        <div class="pkg-footer">
                            <div><p class="pkg-price-label">Price per person</p><p class="pkg-price">LKR 10,000 <span>/ person</span></p></div>
                            <button class="btn-book" onclick="openBooking('Colombo City Tour','LKR 10,000')">Book Now</button>
                        </div>
                    </div>
                </div>

                <div class="pkg-card fade-in" data-duration="4" data-price-tier="budget" data-name="cultural triangle tour">
                    <div class="pkg-img"><img src="images/culturaltriangelshorttour.jpeg" alt="Cultural Triangle Tour" loading="lazy"><span class="pkg-duration-pill">4 Days</span></div>
                    <div class="pkg-body">
                        <h3 class="pkg-name">Cultural Triangle Tour</h3>
                        <p class="pkg-desc">Journey through UNESCO World Heritage sites — the Sigiriya rock fortress, Dambulla Cave Temple, ancient Kandy.</p>
                        <div class="pkg-meta">
                            <div class="pkg-meta-item"><span class="mi-icon">⏱</span><span>4 Days / 3 Nights</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">📍</span><span>Sigiriya, Dambulla, Kandy</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🚗</span><span>Toyota Hiace KDH</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🏨</span><span class="stars">★★★★ Hotel</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">👥</span><span>2–6 People</span></div>
                        </div>
                        <div class="pkg-divider"></div>
                        <div class="pkg-footer">
                            <div><p class="pkg-price-label">Price per person</p><p class="pkg-price">LKR 45,000 <span>/ person</span></p></div>
                            <button class="btn-book" onclick="openBooking('Cultural Triangle Tour','LKR 45,000')">Book Now</button>
                        </div>
                    </div>
                </div>

                <div class="pkg-card fade-in" data-duration="5" data-price-tier="mid" data-name="southern beach escape">
                    <div class="pkg-img"><img src="images/southernbeachescape.jpeg" alt="Southern Beach Escape" loading="lazy"><span class="pkg-duration-pill">5 Days</span></div>
                    <div class="pkg-body">
                        <h3 class="pkg-name">Southern Beach Escape</h3>
                        <p class="pkg-desc">Golden beaches, the historic Galle Fort, whale watching in Mirissa, and the tranquil shores of Bentota.</p>
                        <div class="pkg-meta">
                            <div class="pkg-meta-item"><span class="mi-icon">⏱</span><span>5 Days / 4 Nights</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">📍</span><span>Galle, Mirissa, Bentota</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🚗</span><span>Toyota Hiace Mini Bus</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🏨</span><span class="stars">★★★★ Hotel</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">👥</span><span>5–10 People</span></div>
                        </div>
                        <div class="pkg-divider"></div>
                        <div class="pkg-footer">
                            <div><p class="pkg-price-label">Price per person</p><p class="pkg-price">LKR 75,000 <span>/ person</span></p></div>
                            <button class="btn-book" onclick="openBooking('Southern Beach Escape','LKR 75,000')">Book Now</button>
                        </div>
                    </div>
                </div>

                <div class="pkg-card fade-in" data-duration="7" data-price-tier="mid" data-name="hill country tea country tour">
                    <div class="pkg-img"><img src="images/hillcountryandteacountry.jpeg" alt="Hill Country Tour" loading="lazy"><span class="pkg-duration-pill">7 Days</span></div>
                    <div class="pkg-body">
                        <h3 class="pkg-name">Hill Country &amp; Tea Country Tour</h3>
                        <p class="pkg-desc">Wind through emerald tea plantations, ride the iconic Kandy–Ella scenic train, visit misty waterfalls.</p>
                        <div class="pkg-meta">
                            <div class="pkg-meta-item"><span class="mi-icon">⏱</span><span>7 Days / 6 Nights</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">📍</span><span>Kandy, Nuwara Eliya, Ella</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🚗</span><span>Toyota Hiace KDH</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🏨</span><span class="stars">★★★★ Hotel</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">👥</span><span>2–6 People</span></div>
                        </div>
                        <div class="pkg-divider"></div>
                        <div class="pkg-footer">
                            <div><p class="pkg-price-label">Price per person</p><p class="pkg-price">LKR 60,000 <span>/ person</span></p></div>
                            <button class="btn-book" onclick="openBooking('Hill Country Tour','LKR 60,000')">Book Now</button>
                        </div>
                    </div>
                </div>

                <div class="pkg-card fade-in" data-duration="7" data-price-tier="luxury" data-name="northern heritage jaffna tour">
                    <div class="pkg-img"><img src="images/nothernheritageandjaffna.jpeg" alt="Northern Heritage Tour" loading="lazy"><span class="pkg-duration-pill">7 Days</span></div>
                    <div class="pkg-body">
                        <h3 class="pkg-name">Northern Heritage Tour</h3>
                        <p class="pkg-desc">Uncover Sri Lanka's rich Tamil culture — the grand Nallur Kovil, Jaffna Fort, pristine Casuarina Beach.</p>
                        <div class="pkg-meta">
                            <div class="pkg-meta-item"><span class="mi-icon">⏱</span><span>7 Days / 6 Nights</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">📍</span><span>Jaffna, Mannar, Nallur Kovil</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🚗</span><span>Toyota Hiace Mini Bus</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🏨</span><span class="stars">★★★★★ Hotel</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">👥</span><span>5–10 People</span></div>
                        </div>
                        <div class="pkg-divider"></div>
                        <div class="pkg-footer">
                            <div><p class="pkg-price-label">Price per person</p><p class="pkg-price">LKR 105,000 <span>/ person</span></p></div>
                            <button class="btn-book" onclick="openBooking('Northern Heritage Tour','LKR 105,000')">Book Now</button>
                        </div>
                    </div>
                </div>

                <div class="pkg-card fade-in" data-duration="14" data-price-tier="luxury" data-name="wild life adventure tour">
                    <div class="pkg-img"><img src="images/wildlifeadventure.jpeg" alt="Wild Life Adventure Tour" loading="lazy"><span class="pkg-duration-pill">10 Days</span></div>
                    <div class="pkg-body">
                        <h3 class="pkg-name">Wild Life Adventure Tour</h3>
                        <p class="pkg-desc">The ultimate Sri Lanka experience — covering Yala, Udawalawe, Sinharaja, Horton Plains, Trincomalee.</p>
                        <div class="pkg-meta">
                            <div class="pkg-meta-item"><span class="mi-icon">⏱</span><span>10 Days / 9 Nights</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">📍</span><span>Yala, Udawalawe, Sinharaja, Horton Plains</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🚗</span><span>Toyota Hiace KDH</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🏨</span><span class="stars">★★★★★ Hotel</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">👥</span><span>6–10 People</span></div>
                        </div>
                        <div class="pkg-divider"></div>
                        <div class="pkg-footer">
                            <div><p class="pkg-price-label">Price per person</p><p class="pkg-price">LKR 90,000 <span>/ person</span></p></div>
                            <button class="btn-book" onclick="openBooking('Wild Life Adventure Tour','LKR 90,000')">Book Now</button>
                        </div>
                    </div>
                </div>

                <div class="pkg-card fade-in" data-duration="12" data-price-tier="luxury" data-name="luxury honeymoon tour">
                    <div class="pkg-img"><img src="images/luxuryhoneymoontour.jpeg" alt="Luxury Honeymoon Tour" loading="lazy"><span class="pkg-duration-pill">12 Days</span></div>
                    <div class="pkg-body">
                        <h3 class="pkg-name">Luxury Honeymoon Tour</h3>
                        <p class="pkg-desc">A romantic getaway featuring 5-star resorts, private beach dinners, scenic hill country views and luxury transport.</p>
                        <div class="pkg-meta">
                            <div class="pkg-meta-item"><span class="mi-icon">⏱</span><span>12 Days / 11 Nights</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">📍</span><span>Kandy, Nuwara Eliya, Ella, Bentota, Galle</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🚗</span><span>Toyota Alphard</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🏨</span><span class="stars">★★★★★ Hotel</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">👥</span><span>2–4 People</span></div>
                        </div>
                        <div class="pkg-divider"></div>
                        <div class="pkg-footer">
                            <div><p class="pkg-price-label">Price per person</p><p class="pkg-price">LKR 160,000 <span>/ person</span></p></div>
                            <button class="btn-book" onclick="openBooking('Luxury Honeymoon Tour','LKR 160,000')">Book Now</button>
                        </div>
                    </div>
                </div>

                <div class="pkg-card fade-in" data-duration="12" data-price-tier="luxury" data-name="northern explore culture combo">
                    <div class="pkg-img"><img src="images/nothernexplorecombo.jpeg" alt="Northern Explore Culture Combo" loading="lazy"><span class="pkg-duration-pill">12 Days</span></div>
                    <div class="pkg-body">
                        <h3 class="pkg-name">Northern Explore Culture Combo</h3>
                        <p class="pkg-desc">Discover Jaffna, Mannar, and Trincomalee while exploring historic temples and coastal beauty.</p>
                        <div class="pkg-meta">
                            <div class="pkg-meta-item"><span class="mi-icon">⏱</span><span>12 Days / 11 Nights</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">📍</span><span>Jaffna, Mannar, Kilinochchi, Sigiriya, Kandy</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🚗</span><span>Toyota Hiace Minibus</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🏨</span><span class="stars">★★★★ Hotel</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">👥</span><span>8–10 People</span></div>
                        </div>
                        <div class="pkg-divider"></div>
                        <div class="pkg-footer">
                            <div><p class="pkg-price-label">Price per person</p><p class="pkg-price">LKR 150,000 <span>/ person</span></p></div>
                            <button class="btn-book" onclick="openBooking('Northern Explore Culture Combo','LKR 150,000')">Book Now</button>
                        </div>
                    </div>
                </div>

                <div class="pkg-card fade-in" data-duration="14" data-price-tier="mid" data-name="full island highlights tour">
                    <div class="pkg-img"><img src="images/fullislandtour.jpeg" alt="Full Island Highlights Tour" loading="lazy"><span class="pkg-duration-pill">14 Days</span></div>
                    <div class="pkg-body">
                        <h3 class="pkg-name">Full Island Highlights Tour</h3>
                        <p class="pkg-desc">Explore the best of Sri Lanka with a complete island journey covering Colombo, the Cultural Triangle, hill country and southern beaches.</p>
                        <div class="pkg-meta">
                            <div class="pkg-meta-item"><span class="mi-icon">⏱</span><span>14 Days / 13 Nights</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">📍</span><span>Colombo, Cultural Triangle, Hill Country, Southern Beaches</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🚗</span><span>Toyota Hiace Minibus</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🏨</span><span class="stars">★★★★ Hotel</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">👥</span><span>Up to 10 People</span></div>
                        </div>
                        <div class="pkg-divider"></div>
                        <div class="pkg-footer">
                            <div><p class="pkg-price-label">Price per person</p><p class="pkg-price">LKR 180,000 <span>/ person</span></p></div>
                            <button class="btn-book" onclick="openBooking('Full Island Highlights Tour','LKR 180,000')">Book Now</button>
                        </div>
                    </div>
                </div>

                <div class="pkg-card fade-in" data-duration="14" data-price-tier="luxury" data-name="ultimate sri lanka explorer">
                    <div class="pkg-img"><img src="images/ultimatesrilankatour.jpeg" alt="Ultimate Sri Lanka Explorer" loading="lazy"><span class="pkg-duration-pill">14 Days</span></div>
                    <div class="pkg-body">
                        <h3 class="pkg-name">Ultimate Sri Lanka Explorer</h3>
                        <p class="pkg-desc">Experience the complete beauty of Sri Lanka with this 14-day luxury journey covering cultural heritage, golden beaches and coastal adventures.</p>
                        <div class="pkg-meta">
                            <div class="pkg-meta-item"><span class="mi-icon">⏱</span><span>14 Days / 13 Nights</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">📍</span><span>Colombo, Sigiriya, Kandy, Nuwara Eliya, Ella, Galle, Mirissa, Jaffna</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🚗</span><span>Toyota Hiace Minibus</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">🏨</span><span class="stars">★★★★★ Hotel</span></div>
                            <div class="pkg-meta-item"><span class="mi-icon">👥</span><span>8–10 People</span></div>
                        </div>
                        <div class="pkg-divider"></div>
                        <div class="pkg-footer">
                            <div><p class="pkg-price-label">Price per person</p><p class="pkg-price">LKR 250,000 <span>/ person</span></p></div>
                            <button class="btn-book" onclick="openBooking('Ultimate Sri Lanka Explorer','LKR 250,000')">Book Now</button>
                        </div>
                    </div>
                </div>

            </div>

            <div class="no-results" id="noResults">
                <div class="nr-icon">🔍</div>
                <h3>No packages found</h3>
                <p>Try adjusting your search or filter criteria.</p>
            </div>
        </div>
    </section>

    <!-- WHY STRIP -->
    <div class="why-strip">
        <div class="container">
            <div class="why-item"><div class="wi-icon">🎯</div><h4>Expert Local Guides</h4><p>Seasoned guides with insider knowledge</p></div>
            <div class="why-item"><div class="wi-icon">🚗</div><h4>Premium Fleet</h4><p>AC vehicles maintained to top standards</p></div>
            <div class="why-item"><div class="wi-icon">⏰</div><h4>24/7 Support</h4><p>Round-the-clock assistance for you</p></div>
            <div class="why-item"><div class="wi-icon">✨</div><h4>Custom Itineraries</h4><p>Plans tailored to your needs &amp; budget</p></div>
            <div class="why-item"><div class="wi-icon">🛡</div><h4>Safe &amp; Reliable</h4><p>Fully licensed and insured services</p></div>
        </div>
    </div>

    <!-- BOOKING MODAL — Pure PHP form, no fetch, no API -->
    <div id="bookingModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:9999;align-items:center;justify-content:center;">
        <div style="background:white;border-radius:20px;padding:2.5rem;max-width:460px;width:92%;box-shadow:0 20px 60px rgba(0,0,0,.3);position:relative;">
            <button onclick="closeBooking()" style="position:absolute;top:1rem;right:1.2rem;background:none;border:none;font-size:1.4rem;cursor:pointer;color:#64748B;">✕</button>
            <h2 style="font-family:'Sora',sans-serif;color:#0A7EA4;margin-bottom:.3rem;" id="modalTitle">Book Package</h2>
            <p style="color:#64748B;margin-bottom:1.8rem;font-size:.9rem;" id="modalPrice"></p>

            <form method="POST" action="packages.php" style="display:flex;flex-direction:column;gap:1rem;">
                <input type="hidden" name="book_submit"  value="1">
                <input type="hidden" name="package_name" id="hiddenPkgName">
                <input type="hidden" name="price"        id="hiddenPrice">

                <input type="text"  name="customer_name" placeholder="Your Full Name" required
                    style="padding:.75rem 1rem;border:2px solid #E2E8F0;border-radius:10px;font-family:inherit;font-size:.95rem;outline:none;transition:border-color .3s;"
                    onfocus="this.style.borderColor='#0A7EA4'" onblur="this.style.borderColor='#E2E8F0'">

                <input type="email" name="email" placeholder="Email Address" required
                    style="padding:.75rem 1rem;border:2px solid #E2E8F0;border-radius:10px;font-family:inherit;font-size:.95rem;outline:none;transition:border-color .3s;"
                    onfocus="this.style.borderColor='#0A7EA4'" onblur="this.style.borderColor='#E2E8F0'">

                <input type="tel"   name="phone" placeholder="Phone Number" required
                    style="padding:.75rem 1rem;border:2px solid #E2E8F0;border-radius:10px;font-family:inherit;font-size:.95rem;outline:none;transition:border-color .3s;"
                    onfocus="this.style.borderColor='#0A7EA4'" onblur="this.style.borderColor='#E2E8F0'">

                <input type="date"  name="travel_date" required
                    style="padding:.75rem 1rem;border:2px solid #E2E8F0;border-radius:10px;font-family:inherit;font-size:.95rem;outline:none;color:#0F172A;transition:border-color .3s;"
                    onfocus="this.style.borderColor='#0A7EA4'" onblur="this.style.borderColor='#E2E8F0'">

                <select name="guests" required
                    style="padding:.75rem 1rem;border:2px solid #E2E8F0;border-radius:10px;font-family:inherit;font-size:.95rem;outline:none;color:#64748B;background:white;cursor:pointer;"
                    onfocus="this.style.borderColor='#0A7EA4'" onblur="this.style.borderColor='#E2E8F0'">
                    <option value="">Number of Guests</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="6">6+</option>
                </select>

                <button type="submit"
                    style="background:#0A7EA4;color:white;padding:.9rem;border:none;border-radius:50px;font-family:inherit;font-size:1rem;font-weight:700;cursor:pointer;transition:background .3s;box-shadow:0 4px 15px rgba(10,126,164,.3);"
                    onmouseover="this.style.background='#065A7A'" onmouseout="this.style.background='#0A7EA4'">
                    Confirm Booking ✓
                </button>
            </form>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="footer" id="contact">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Jettransfer Travels</h3>
                <p>Your trusted partner for exploring the beauty of Sri Lanka with comfort, safety, and professionalism.</p>
                <div class="social-links">
                    <img src="images/insta1.webp" alt="Instagram" class="social-link">
                    <img src="images/fb.avif"     alt="Facebook"  class="social-link">
                    <img src="images/tiktok.webp" alt="TikTok"    class="social-link">
                </div>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <a href="destination.php">Destinations</a>
                <a href="packages.php">Tour Packages</a>
                <a href="vehicles.php">Our Vehicles</a>
                <a href="aboutus.php">About Us</a>
                <a href="service.php">Gallery</a>
            </div>
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p>📞 +94 72 403 0499</p>
                <p>📧 jettransfer@outlook.com</p>
                <p>📍 Ruwani Uyana, Matara, Sri Lanka</p>
            </div>
            <div class="footer-section">
                <h3>Business Hours</h3>
                <p>Monday - Friday: 8:00 AM - 6:00 PM</p>
                <p>Saturday: 10:00 AM - 6:00 PM</p>
                <p>Sunday: 10:00 AM - 6:00 PM</p>
                <p style="margin-top:1rem;color:var(--secondary);">24/7 Emergency Support Available</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Jettransfer Travels. All rights reserved. | Designed with care for your journey</p>
        </div>
    </footer>

    <script>
        /* ── Mobile Menu ── */
        document.getElementById('mobileToggle').addEventListener('click', () => {
            document.getElementById('navMenu').classList.toggle('active');
        });
        document.querySelectorAll('.nav-menu a').forEach(a => {
            a.addEventListener('click', () => document.getElementById('navMenu').classList.remove('active'));
        });

        /* ── Header scroll ── */
        window.addEventListener('scroll', () => {
            document.getElementById('header').classList.toggle('scrolled', window.scrollY > 80);
        });

        /* ── Fade-in ── */
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, i) => {
                if (entry.isIntersecting)
                    setTimeout(() => entry.target.classList.add('visible'), i * 80);
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
        document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));

        /* ── Filter & Search ── */
        let activeDuration = 'all';
        let activePrice    = 'all';
        const cards        = document.querySelectorAll('.pkg-card');
        const resultCount  = document.getElementById('resultCount');
        const noResults    = document.getElementById('noResults');
        const searchInput  = document.getElementById('searchInput');

        function applyFilters() {
            const query = searchInput.value.toLowerCase().trim();
            let visible = 0;
            cards.forEach(card => {
                const durationMatch = activeDuration === 'all' || card.dataset.duration === activeDuration;
                const priceMatch    = activePrice    === 'all' || card.dataset.priceTier === activePrice;
                const searchMatch   = !query || card.dataset.name.includes(query);
                const show = durationMatch && priceMatch && searchMatch;
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            resultCount.textContent = visible;
            noResults.style.display = visible === 0 ? 'flex' : 'none';
        }

        document.querySelectorAll('[data-filter="duration"]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('[data-filter="duration"]').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                activeDuration = btn.dataset.value;
                applyFilters();
            });
        });

        document.querySelectorAll('[data-filter="price"]').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('[data-filter="price"]').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                activePrice = btn.dataset.value;
                applyFilters();
            });
        });

        searchInput.addEventListener('input', applyFilters);

        /* ── Nav Search Toggle ── */
        document.getElementById('searchToggle').addEventListener('click', (e) => {
            e.stopPropagation();
            document.getElementById('searchBox').classList.toggle('open');
        });
        document.addEventListener('click', (e) => {
            if (!document.getElementById('navSearch').contains(e.target))
                document.getElementById('searchBox').classList.remove('open');
        });

        /* ── Booking Modal ── */
        function openBooking(name, price) {
            document.getElementById('modalTitle').textContent     = '📅 Book: ' + name;
            document.getElementById('modalPrice').textContent     = 'Starting from ' + price + ' per person';
            document.getElementById('hiddenPkgName').value        = name;
            document.getElementById('hiddenPrice').value          = price;
            document.getElementById('bookingModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeBooking() {
            document.getElementById('bookingModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        document.getElementById('bookingModal').addEventListener('click', function(e) {
            if (e.target === this) closeBooking();
        });

        /* ── Smooth scroll ── */
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', function(e) {
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    window.scrollTo({ top: target.getBoundingClientRect().top + window.pageYOffset - 80, behavior:'smooth' });
                }
            });
        });
    </script>
</body>
</html>