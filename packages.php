<?php
// ============================================================
//  packages.php — Tour Packages with Dynamic Price Customizer
//  DB-driven version (replaces hardcoded cards)
// ============================================================
require_once __DIR__ . '/admin/db_packages.php';

$bookingSuccess = false;
$bookingError   = '';

// ── Price Calculation Logic (mirrored from JS) ─────────────
function calculatePrice(float $base, string $hotel, string $vehicle, int $days, int $groupSize): float {
    $hotelAdd   = match($hotel)   { '★★★★★' => 10000, '★★★★' => 5000, default => 0 };
    $vehicleAdd = match($vehicle) { 'Luxury Van' => 7000, 'Van' => 3000, default => 0 };
    $total      = ($base + $hotelAdd + $vehicleAdd) * $days;
    if ($groupSize > 5) $total *= 0.90;
    return round($total);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_submit'])) {
    $pdo = getDB();

    $name        = htmlspecialchars(strip_tags(trim($_POST['customer_name'] ?? '')));
    $email       = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone       = htmlspecialchars(strip_tags(trim($_POST['phone'] ?? '')));
    $travelDate  = trim($_POST['travel_date'] ?? '');
    $guests      = (int)($_POST['guests'] ?? 0);
    $pkgName     = htmlspecialchars(strip_tags(trim($_POST['package_name'] ?? '')));
    $basePrice   = (float)($_POST['base_price'] ?? 0);
    $bookingType = (($_POST['booking_type'] ?? '') === 'custom') ? 'custom' : 'standard';

    $customHotel    = htmlspecialchars(strip_tags(trim($_POST['custom_hotel']    ?? '')));
    $customVehicle  = htmlspecialchars(strip_tags(trim($_POST['custom_vehicle']  ?? '')));
    $customDuration = (int)($_POST['custom_duration'] ?? 0);
    $customGroup    = (int)($_POST['custom_group_size'] ?? 0);

    if ($bookingType === 'standard') {
        $price           = htmlspecialchars(strip_tags(trim($_POST['price'] ?? '')));
        $customDuration  = null;
        $customHotel     = null;
        $customVehicle   = null;
        $customGroupSize = null;
    } else {
        $customGroupSize = $customGroup;
        $totalPrice      = calculatePrice($basePrice, $customHotel, $customVehicle, $customDuration, $customGroup);
        $price           = 'LKR ' . number_format($totalPrice);
    }

    if (!$name || !$email || !$phone || !$travelDate || !$guests || !$pkgName) {
        $bookingError = 'Please fill in all required fields correctly.';
    } elseif ($bookingType === 'custom' && (!$customDuration || !$customHotel || !$customVehicle || !$customGroupSize)) {
        $bookingError = 'Please fill in all customization fields.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO bookings
                (package_name, price, customer_name, email, phone, travel_date, guests,
                 custom_duration, custom_hotel, custom_vehicle, custom_group_size, booking_type)
             VALUES
                (:pkg, :price, :name, :email, :phone, :date, :guests,
                 :cdur, :chotel, :cvehicle, :cgroup, :btype)'
        );
        $stmt->execute([
            ':pkg'     => $pkgName,
            ':price'   => $price,
            ':name'    => $name,
            ':email'   => $email,
            ':phone'   => $phone,
            ':date'    => $travelDate,
            ':guests'  => $guests,
            ':cdur'    => $customDuration ?? null,
            ':chotel'  => $customHotel   ?? null,
            ':cvehicle'=> $customVehicle ?? null,
            ':cgroup'  => $customGroupSize ?? null,
            ':btype'   => $bookingType,
        ]);
        $bookingSuccess = true;
    }
}

// ── Fetch all active packages from DB ──────────────────────
$pdo      = getDB();
$packages = $pdo->query("SELECT * FROM `packages` WHERE `is_active` = 1 ORDER BY `price` ASC")->fetchAll(PDO::FETCH_ASSOC);

// ── Helper: map price (decimal) → price_tier label ─────────
function priceTier(float $price): string {
    if ($price < 50000)  return 'budget';
    if ($price <= 100000) return 'mid';
    return 'luxury';
}

// ── Helper: star string → integer count ────────────────────
function starCount(string $stars): int {
    return mb_strlen(trim($stars));
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
            --primary: #0A7EA4; --primary-dark: #065A7A;
            --secondary: #F59E0B; --accent: #10B981;
            --text-dark: #0F172A; --text-light: #64748B;
            --bg-light: #F8FAFC;
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
        .logo-img { width:42px; height:42px; border-radius:12px; object-fit:cover; border:1px solid rgba(2,6,23,.10); background:#fff; }
        .nav-menu { display:flex; list-style:none; gap:1.6rem; align-items:center; }
        .nav-menu a { color:var(--text-dark); text-decoration:none; font-weight:500; font-size:0.88rem; transition:color 0.3s ease; position:relative; }
        .nav-menu a::after { content:''; position:absolute; bottom:-5px; left:0; width:0; height:2px; background:var(--primary); transition:width 0.3s ease; }
        .nav-menu a:hover::after, .nav-menu a.active::after { width:100%; }
        .nav-menu a:hover, .nav-menu a.active { color:var(--primary); }
        .nav-actions { display:flex; align-items:center; gap:0.6rem; flex-shrink:0; }
        .nav-auth { display:flex; align-items:center; gap:0.4rem; }
        .btn-login { padding:0.5rem 1.2rem; border-radius:50px; text-decoration:none; font-weight:600; font-size:0.88rem; color:var(--primary); border:2px solid var(--primary); transition:all 0.3s ease; white-space:nowrap; }
        .btn-login:hover { background:var(--primary); color:white; }
        .btn-register { padding:0.5rem 1.2rem; border-radius:50px; text-decoration:none; font-weight:600; font-size:0.88rem; background:var(--primary); color:white; border:2px solid var(--primary); transition:all 0.3s ease; white-space:nowrap; }
        .btn-register:hover { background:var(--primary-dark); }
        .mobile-auth { display:none; }
        .mobile-toggle { display:none; flex-direction:column; gap:5px; cursor:pointer; padding:8px; }
        .mobile-toggle span { width:25px; height:3px; background:var(--text-dark); border-radius:2px; }

        /* ── HERO ── */
        .hero { margin-top:80px; height:50vh; min-height:340px; display:flex; align-items:center; position:relative; overflow:hidden; }
        .hero-content { max-width:1400px; margin:0 auto; padding:0 2rem; color:white; z-index:3; animation:fadeInUp .9s ease-out both; }
        .hero h1 { font-family:'Sora',sans-serif; font-size:3.2rem; font-weight:800; line-height:1.1; margin-bottom:1rem; }
        .hero-subtitle { font-size:1.2rem; opacity:.92; max-width:560px; }
        @keyframes fadeInUp { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }

        /* ── SECTION ── */
        .section { padding:5rem 2rem; }
        .container { max-width:1400px; margin:0 auto; }
        .section-header { text-align:center; margin-bottom:3.5rem; }
        .section-tag { display:inline-block; color:var(--primary); font-weight:600; text-transform:uppercase; font-size:.85rem; letter-spacing:1.5px; margin-bottom:.8rem; }
        .section-title { font-family:'Sora',sans-serif; font-size:2.6rem; font-weight:700; margin-bottom:.8rem; }
        .section-description { color:var(--text-light); font-size:1.1rem; max-width:680px; margin:0 auto; }

        /* ── FILTER BAR ── */
        .filter-bar { background:white; border-radius:16px; box-shadow:var(--shadow-md); padding:1.6rem 2rem; margin-bottom:2.5rem; display:flex; flex-wrap:wrap; gap:1.2rem; align-items:center; }
        .filter-search { flex:1; min-width:200px; position:relative; }
        .filter-search input { width:100%; padding:.75rem 1.2rem .75rem 3rem; border:2px solid #E2E8F0; border-radius:50px; font-family:inherit; font-size:.95rem; outline:none; transition:border-color .3s; }
        .filter-search input:focus { border-color:var(--primary); }
        .filter-search .search-icon { position:absolute; left:1rem; top:50%; transform:translateY(-50%); color:var(--text-light); pointer-events:none; }
        .filter-group { display:flex; align-items:center; gap:.7rem; flex-wrap:wrap; }
        .filter-label { font-weight:600; font-size:.88rem; color:var(--text-light); white-space:nowrap; }
        .filter-btn { padding:.5rem 1.2rem; border-radius:50px; border:2px solid #E2E8F0; background:transparent; font-family:inherit; font-size:.85rem; font-weight:600; color:var(--text-light); cursor:pointer; transition:all .25s; white-space:nowrap; }
        .filter-btn:hover, .filter-btn.active { background:var(--primary); border-color:var(--primary); color:white; }
        .results-info { font-size:.93rem; color:var(--text-light); margin-bottom:1.8rem; }
        .results-info strong { color:var(--primary); }

        /* ── PACKAGES GRID ── */
        .packages-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:2rem; }
        .pkg-card { background:white; border-radius:16px; overflow:hidden; box-shadow:var(--shadow-md); transition:transform .4s ease,box-shadow .4s ease; display:flex; flex-direction:column; }
        .pkg-card:hover { transform:translateY(-10px); box-shadow:var(--shadow-lg); }
        .pkg-img { position:relative; height:230px; overflow:hidden; }
        .pkg-img img { width:100%; height:100%; object-fit:cover; transition:transform .6s ease; }
        .pkg-card:hover .pkg-img img { transform:scale(1.08); }
        .pkg-img::after { content:''; position:absolute; inset:0; background:linear-gradient(to bottom,transparent 45%,rgba(10,26,60,.5) 100%); }
        .pkg-duration-pill { position:absolute; bottom:1rem; right:1rem; background:rgba(255,255,255,.92); color:var(--primary); font-weight:700; font-size:.8rem; padding:.32rem .85rem; border-radius:50px; z-index:2; }
        .pkg-body { padding:1.6rem; display:flex; flex-direction:column; flex:1; }
        .pkg-name { font-family:'Sora',sans-serif; font-size:1.25rem; font-weight:700; margin-bottom:.5rem; }
        .pkg-desc { color:var(--text-light); font-size:.9rem; line-height:1.65; margin-bottom:1.1rem; }
        .pkg-meta { display:grid; grid-template-columns:1fr 1fr; gap:.55rem .8rem; margin-bottom:1.1rem; }
        .pkg-meta-item { display:flex; align-items:center; gap:.4rem; font-size:.83rem; color:var(--text-light); }
        .mi-icon { width:26px; height:26px; background:rgba(10,126,164,.1); border-radius:7px; display:flex; align-items:center; justify-content:center; font-size:.8rem; flex-shrink:0; }
        .stars { color:var(--secondary); font-size:.88rem; }
        .pkg-divider { height:1px; background:#E2E8F0; margin:.9rem 0; }
        .pkg-footer { display:flex; justify-content:space-between; align-items:center; margin-top:auto; gap:.6rem; flex-wrap:wrap; }
        .pkg-price-label { font-size:.75rem; color:var(--text-light); margin-bottom:.12rem; }
        .pkg-price { font-family:'Sora',sans-serif; font-size:1.35rem; font-weight:800; color:var(--primary); }
        .pkg-price span { font-size:.77rem; font-weight:500; color:var(--text-light); }
        .btn-book { background:var(--primary); color:white; padding:.6rem 1.2rem; border-radius:50px; font-weight:700; font-size:.85rem; border:none; cursor:pointer; transition:all .3s; box-shadow:0 4px 15px rgba(10,126,164,.3); white-space:nowrap; }
        .btn-book:hover { background:var(--primary-dark); transform:translateY(-2px); }
        .btn-customize { background:white; color:var(--accent); padding:.6rem 1.2rem; border-radius:50px; font-weight:700; font-size:.85rem; border:2px solid var(--accent); cursor:pointer; transition:all .3s; white-space:nowrap; }
        .btn-customize:hover { background:var(--accent); color:white; transform:translateY(-2px); }
        .no-results { display:none; flex-direction:column; align-items:center; text-align:center; padding:5rem 2rem; color:var(--text-light); }
        .no-results .nr-icon { font-size:3rem; margin-bottom:1rem; }
        .no-results h3 { font-family:'Sora',sans-serif; font-size:1.4rem; margin-bottom:.5rem; }

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
        .social-link { width:38px; height:38px; background:rgba(255,255,255,.1); border-radius:50%; object-fit:cover; }
        .footer-bottom { max-width:1400px; margin:0 auto; padding-top:2rem; border-top:1px solid rgba(255,255,255,.1); text-align:center; color:rgba(255,255,255,.45); font-size:.87rem; }

        /* ── FADE IN ── */
        .fade-in { opacity:0; transform:translateY(28px); transition:opacity .75s ease,transform .75s ease; }
        .fade-in.visible { opacity:1; transform:translateY(0); }

        /* ── MODAL SHARED ── */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.65); z-index:9999; align-items:center; justify-content:center; padding:1rem; backdrop-filter:blur(4px); }
        .modal-overlay.open { display:flex; }
        .modal-box { background:white; border-radius:20px; padding:2.5rem; max-width:540px; width:100%; max-height:92vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.3); position:relative; animation:slideUp .35s ease both; }
        @keyframes slideUp { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }
        .modal-close-btn { position:absolute; top:1rem; right:1.2rem; background:none; border:none; font-size:1.4rem; cursor:pointer; color:#64748B; line-height:1; }
        .modal-title { font-family:'Sora',sans-serif; color:var(--primary); margin-bottom:.3rem; font-size:1.3rem; font-weight:700; }
        .modal-subtitle { color:#64748B; margin-bottom:1.5rem; font-size:.9rem; }

        /* ── FORM FIELDS ── */
        .field-group { display:flex; flex-direction:column; gap:.4rem; margin-bottom:.9rem; }
        .field-group label { font-size:.78rem; font-weight:700; color:#64748B; text-transform:uppercase; letter-spacing:.5px; }
        .field-group input,
        .field-group select { padding:.75rem 1rem; border:2px solid #E2E8F0; border-radius:10px; font-family:'Manrope',sans-serif; font-size:.95rem; color:var(--text-dark); outline:none; transition:border-color .3s; background:white; width:100%; }
        .field-group input:focus, .field-group select:focus { border-color:var(--primary); }
        .field-row { display:grid; grid-template-columns:1fr 1fr; gap:.8rem; }

        /* ── CUSTOMIZE SECTION ── */
        .customize-section { background:linear-gradient(135deg,#F0FDF4,#ECFDF5); border:2px solid #86EFAC; border-radius:14px; padding:1.4rem; margin-bottom:1rem; }
        .customize-section-title { font-size:.8rem; font-weight:700; color:#065f46; text-transform:uppercase; letter-spacing:.5px; margin-bottom:1rem; display:flex; align-items:center; gap:.4rem; }

        /* ── DYNAMIC PRICE BOX ── */
        .price-preview-box {
            background: linear-gradient(135deg, #0A7EA4, #065A7A);
            border-radius: 14px;
            padding: 1.4rem 1.6rem;
            margin-bottom: 1.2rem;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .price-preview-box::before {
            content: '';
            position: absolute;
            top: -20px; right: -20px;
            width: 100px; height: 100px;
            background: rgba(255,255,255,.07);
            border-radius: 50%;
        }
        .price-preview-box::after {
            content: '';
            position: absolute;
            bottom: -30px; left: 30%;
            width: 140px; height: 140px;
            background: rgba(255,255,255,.05);
            border-radius: 50%;
        }
        .price-preview-label { font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; opacity:.75; margin-bottom:.4rem; }
        .price-preview-amount { font-family:'Sora',sans-serif; font-size:2rem; font-weight:800; letter-spacing:-1px; line-height:1; margin-bottom:.6rem; transition: all .3s ease; }
        .price-preview-amount.updating { opacity:.5; transform:scale(.97); }
        .price-breakdown { display:flex; flex-wrap:wrap; gap:.5rem; position:relative; z-index:1; }
        .breakdown-pill {
            background: rgba(255,255,255,.15);
            border-radius: 50px;
            padding: .2rem .75rem;
            font-size: .75rem;
            font-weight: 600;
            display: flex; align-items:center; gap:.3rem;
            transition: background .3s;
        }
        .breakdown-pill.active { background: rgba(255,255,255,.28); }
        .breakdown-pill.discount { background: rgba(16,185,129,.35); }
        .price-preview-note { font-size:.78rem; opacity:.6; margin-top:.6rem; }

        /* ── SUBMIT BUTTONS ── */
        .btn-submit { width:100%; background:var(--primary); color:white; padding:.9rem; border:none; border-radius:50px; font-family:inherit; font-size:1rem; font-weight:700; cursor:pointer; transition:background .3s; box-shadow:0 4px 15px rgba(10,126,164,.3); margin-top:.5rem; }
        .btn-submit:hover { background:var(--primary-dark); }
        .btn-submit.green { background:#059669; box-shadow:0 4px 15px rgba(5,150,105,.3); }
        .btn-submit.green:hover { background:#047857; }

        /* ── RESPONSIVE ── */
        @media(max-width:768px) {
            .mobile-toggle { display:flex; }
            .nav-actions { display:none; }
            .nav-menu { position:fixed; top:80px; left:0; right:0; background:white; flex-direction:column; padding:2rem; gap:1.5rem; box-shadow:var(--shadow-lg); transform:translateX(-100%); transition:transform .3s; }
            .nav-menu.active { transform:translateX(0); }
            .mobile-auth { display:flex !important; flex-direction:column; align-items:center; gap:.8rem; padding-top:1.2rem; border-top:1px solid #E2E8F0; width:100%; }
            .mobile-auth-btns { display:flex; gap:.8rem; width:100%; }
            .mobile-auth-btns a { flex:1; text-align:center; }
            .hero h1 { font-size:2rem; }
            .section-title { font-size:1.9rem; }
            .packages-grid { grid-template-columns:1fr; }
            .filter-bar { flex-direction:column; align-items:stretch; }
            .field-row { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>

<?php if ($bookingSuccess): ?>
<script>
window.addEventListener('load', function () {
    const t = document.createElement('div');
    t.textContent = '✅ Booking confirmed! Our team will contact you shortly.';
    Object.assign(t.style, {
        position:'fixed', bottom:'2rem', left:'50%', transform:'translateX(-50%)',
        background:'#065A7A', color:'white', padding:'.9rem 2rem', borderRadius:'50px',
        fontFamily:'Manrope,sans-serif', fontWeight:'600', fontSize:'.95rem',
        boxShadow:'0 8px 30px rgba(0,0,0,.25)', zIndex:'99999', whiteSpace:'nowrap',
        opacity:'1', transition:'opacity .5s'
    });
    document.body.appendChild(t);
    setTimeout(() => t.style.opacity = '0', 3500);
    setTimeout(() => t.remove(), 4000);
});
</script>
<?php endif; ?>

<?php if ($bookingError): ?>
<script>
window.addEventListener('load', function () {
    alert('❌ <?= htmlspecialchars($bookingError, ENT_QUOTES) ?>');
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
        <div class="mobile-toggle" id="mobileToggle"><span></span><span></span><span></span></div>
    </nav>
</header>

<!-- HERO -->
<section class="hero" style="background:none;">
    <video autoplay loop muted playsinline poster="images/colombocitytour.jpeg"
        style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;z-index:0;pointer-events:none;">
        <source src="video.mp4" type="video/mp4">
    </video>
    <div style="position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);z-index:1;"></div>
    <div class="hero-content">
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
            <p class="section-description">Find the perfect package — or customize one to match your dream trip</p>
        </div>

        <div class="filter-bar fade-in">
            <div class="filter-search">
                <span class="search-icon">🔍</span>
                <input type="text" id="searchInput" placeholder="Search packages…" autocomplete="off">
            </div>
            <div class="filter-group">
                <span class="filter-label">Duration:</span>
                <button class="filter-btn active" data-filter="duration" data-value="all">All</button>
                <?php
                // Build unique duration buttons from DB data
                $durations = array_unique(array_column($packages, 'duration'));
                sort($durations);
                foreach ($durations as $d):
                    $label = $d == 1 ? '1 Day' : $d . ' Days';
                ?>
                <button class="filter-btn" data-filter="duration" data-value="<?= $d ?>"><?= $label ?></button>
                <?php endforeach; ?>
            </div>
            <div class="filter-group">
                <span class="filter-label">Price:</span>
                <button class="filter-btn active" data-filter="price" data-value="all">All</button>
                <button class="filter-btn" data-filter="price" data-value="budget">Under LKR 50K</button>
                <button class="filter-btn" data-filter="price" data-value="mid">50K – 100K</button>
                <button class="filter-btn" data-filter="price" data-value="luxury">100K+</button>
            </div>
        </div>

        <p class="results-info fade-in">Showing <strong id="resultCount"><?= count($packages) ?></strong> packages</p>

        <div class="packages-grid" id="packagesGrid">

            <?php foreach ($packages as $pkg):
                $tier      = $pkg['price_tier'] ?? priceTier((float)$pkg['price']);
                $nights    = $pkg['duration'] - 1;
                $priceFormatted = 'LKR ' . number_format((float)$pkg['price']);
                $nameLower = strtolower($pkg['name']);
                // escape for JS single-quoted attribute
                $nameJS    = addslashes($pkg['name']);
                $basePrice = (float)$pkg['price'];
                $duration  = (int)$pkg['duration'];
            ?>
            <div class="pkg-card fade-in"
                 data-duration="<?= $duration ?>"
                 data-price-tier="<?= htmlspecialchars($tier) ?>"
                 data-name="<?= htmlspecialchars($nameLower) ?>">

                <div class="pkg-img">
                    <img src="<?= htmlspecialchars($pkg['image']) ?>"
                         alt="<?= htmlspecialchars($pkg['name']) ?>" loading="lazy">
                    <span class="pkg-duration-pill"><?= $duration ?> Day<?= $duration > 1 ? 's' : '' ?></span>
                </div>

                <div class="pkg-body">
                    <h3 class="pkg-name"><?= htmlspecialchars($pkg['name']) ?></h3>
                    <p class="pkg-desc"><?= htmlspecialchars($pkg['description']) ?></p>

                    <div class="pkg-meta">
                        <div class="pkg-meta-item">
                            <span class="mi-icon">⏱</span>
                            <span><?= $duration ?> Day<?= $duration > 1 ? 's' : '' ?> / <?= $nights ?> Night<?= $nights != 1 ? 's' : '' ?></span>
                        </div>
                        <div class="pkg-meta-item">
                            <span class="mi-icon">📍</span>
                            <span><?= htmlspecialchars($pkg['locations']) ?></span>
                        </div>
                        <div class="pkg-meta-item">
                            <span class="mi-icon">🚗</span>
                            <span><?= htmlspecialchars($pkg['vehicle']) ?></span>
                        </div>
                        <div class="pkg-meta-item">
                            <span class="mi-icon">🏨</span>
                            <span class="stars"><?= htmlspecialchars($pkg['hotel_rating']) ?> Hotel</span>
                        </div>
                        <div class="pkg-meta-item">
                            <span class="mi-icon">👥</span>
                            <span><?= htmlspecialchars($pkg['group_size']) ?></span>
                        </div>
                    </div>

                    <div class="pkg-divider"></div>

                    <div class="pkg-footer">
                        <div>
                            <p class="pkg-price-label">Price per person</p>
                            <p class="pkg-price"><?= $priceFormatted ?> <span>/ person</span></p>
                        </div>
                        <button class="btn-book"
                                onclick="openBooking('<?= $nameJS ?>', '<?= addslashes($priceFormatted) ?>')">
                            Book Now
                        </button>
                        <button class="btn-customize"
                                onclick="openCustomize('<?= $nameJS ?>', <?= $basePrice ?>, <?= $duration ?>)">
                            ✏️ Customize
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        </div><!-- /.packages-grid -->

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

<!-- ══════════════════════════════════════════════════════════
     MODAL 1: STANDARD BOOKING
══════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="bookingModal">
    <div class="modal-box">
        <button class="modal-close-btn" onclick="closeBooking()">✕</button>
        <h2 class="modal-title" id="modalTitle">Book Package</h2>
        <p class="modal-subtitle" id="modalPrice"></p>
        <form method="POST" action="packages.php">
            <input type="hidden" name="book_submit"  value="1">
            <input type="hidden" name="booking_type" value="standard">
            <input type="hidden" name="package_name" id="hiddenPkgName">
            <input type="hidden" name="price"        id="hiddenPrice">
            <div class="field-group">
                <label>Full Name *</label>
                <input type="text" name="customer_name" placeholder="Your Full Name" required>
            </div>
            <div class="field-row">
                <div class="field-group">
                    <label>Email *</label>
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
                <div class="field-group">
                    <label>Phone *</label>
                    <input type="tel" name="phone" placeholder="Phone Number" required>
                </div>
            </div>
            <div class="field-row">
                <div class="field-group">
                    <label>Travel Date *</label>
                    <input type="date" name="travel_date" required>
                </div>
                <div class="field-group">
                    <label>Guests *</label>
                    <select name="guests" required>
                        <option value="">Select</option>
                        <option value="1">1</option><option value="2">2</option>
                        <option value="3">3</option><option value="4">4</option>
                        <option value="5">5</option><option value="6">6+</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn-submit">Confirm Booking ✓</button>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     MODAL 2: CUSTOMIZE & BOOK — with Dynamic Price Preview
══════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="customizeModal">
    <div class="modal-box">
        <button class="modal-close-btn" onclick="closeCustomize()">✕</button>
        <h2 class="modal-title" id="customModalTitle">✏️ Customize Package</h2>
        <p class="modal-subtitle" id="customModalPrice"></p>

        <!-- Live Price Preview Box -->
        <div class="price-preview-box">
            <div class="price-preview-label">Total Estimated Price (per person)</div>
            <div class="price-preview-amount" id="previewAmount">LKR 0</div>
            <div class="price-breakdown" id="priceBreakdown">
                <span class="breakdown-pill" id="pill-base">Base: —</span>
                <span class="breakdown-pill" id="pill-hotel">Hotel: —</span>
                <span class="breakdown-pill" id="pill-vehicle">Vehicle: —</span>
                <span class="breakdown-pill" id="pill-days">Days: —</span>
                <span class="breakdown-pill" id="pill-discount" style="display:none;">🎉 10% Group Discount</span>
            </div>
            <div class="price-preview-note" id="previewNote">Fill in the options below to see your price</div>
        </div>

        <form method="POST" action="packages.php">
            <input type="hidden" name="book_submit"   value="1">
            <input type="hidden" name="booking_type"  value="custom">
            <input type="hidden" name="package_name"  id="customHiddenName">
            <input type="hidden" name="base_price"    id="customHiddenBase">
            <input type="hidden" name="price"         id="customHiddenPriceDisplay">

            <!-- Customer Details -->
            <div class="field-group">
                <label>Full Name *</label>
                <input type="text" name="customer_name" placeholder="Your Full Name" required>
            </div>
            <div class="field-row">
                <div class="field-group">
                    <label>Email *</label>
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
                <div class="field-group">
                    <label>Phone *</label>
                    <input type="tel" name="phone" placeholder="Phone Number" required>
                </div>
            </div>
            <div class="field-row">
                <div class="field-group">
                    <label>Travel Date *</label>
                    <input type="date" name="travel_date" required>
                </div>
                <div class="field-group">
                    <label>Guests *</label>
                    <select name="guests" required>
                        <option value="">Select</option>
                        <option value="1">1</option><option value="2">2</option>
                        <option value="3">3</option><option value="4">4</option>
                        <option value="5">5</option><option value="6">6+</option>
                    </select>
                </div>
            </div>

            <!-- Customization Options -->
            <div class="customize-section">
                <div class="customize-section-title">✏️ Customize Your Package</div>

                <div class="field-row">
                    <div class="field-group">
                        <label>Duration (Days) *</label>
                        <input type="number" name="custom_duration" id="c_duration"
                               placeholder="e.g. 5" min="1" max="30" required oninput="recalcPrice()">
                    </div>
                    <div class="field-group">
                        <label>Hotel Rating *</label>
                        <select name="custom_hotel" id="c_hotel" required onchange="recalcPrice()">
                            <option value="">Select rating</option>
                            <option value="★★★">★★★ (3 Star) — No extra charge</option>
                            <option value="★★★★">★★★★ (4 Star) — +LKR 5,000</option>
                            <option value="★★★★★">★★★★★ (5 Star) — +LKR 10,000</option>
                        </select>
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-group">
                        <label>Vehicle *</label>
                        <select name="custom_vehicle" id="c_vehicle" required onchange="recalcPrice()">
                            <option value="">Select vehicle</option>
                            <option value="Car">Car (Toyota Prius / Vezel) — No extra</option>
                            <option value="Van">Van (Toyota Hiace KDH) — +LKR 3,000</option>
                            <option value="Luxury Van">Luxury Van (Alphard / Minibus) — +LKR 7,000</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label>Group Size *</label>
                        <input type="number" name="custom_group_size" id="c_group"
                               placeholder="e.g. 4" min="1" max="30" required oninput="recalcPrice()">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit green" id="submitCustomBtn" onclick="setFinalPrice()">✓ Confirm Custom Booking</button>
        </form>
    </div>
</div>

<!-- FOOTER -->
<footer class="footer" id="contact">
    <div class="footer-content">
        <div class="footer-section">
            <h3>Jettransfer Travels</h3>
            <p>Your trusted partner for exploring the beauty of Sri Lanka.</p>
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
            <p>Monday – Friday: 8:00 AM – 6:00 PM</p>
            <p>Saturday: 10:00 AM – 6:00 PM</p>
            <p>Sunday: 10:00 AM – 6:00 PM</p>
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
    window.addEventListener('scroll', () => {
        document.getElementById('header').classList.toggle('scrolled', window.scrollY > 80);
    });

    /* ── Fade-in observer ── */
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, i) => {
            if (entry.isIntersecting)
                setTimeout(() => entry.target.classList.add('visible'), i * 80);
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
    document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));

    /* ── Filter & Search ── */
    let activeDuration = 'all', activePrice = 'all';
    const cards       = document.querySelectorAll('.pkg-card');
    const resultCount = document.getElementById('resultCount');
    const noResults   = document.getElementById('noResults');
    const searchInput = document.getElementById('searchInput');

    function applyFilters() {
        const query = searchInput.value.toLowerCase().trim();
        let visible = 0;
        cards.forEach(card => {
            const show = (activeDuration === 'all' || card.dataset.duration === activeDuration)
                      && (activePrice    === 'all' || card.dataset.priceTier === activePrice)
                      && (!query || card.dataset.name.includes(query));
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

    /* ══════════════════════════════════════════════════════
       MODAL 1 — Standard Booking
    ══════════════════════════════════════════════════════ */
    function openBooking(name, price) {
        document.getElementById('modalTitle').textContent  = '📅 Book: ' + name;
        document.getElementById('modalPrice').textContent  = 'Starting from ' + price + ' per person';
        document.getElementById('hiddenPkgName').value     = name;
        document.getElementById('hiddenPrice').value       = price;
        document.getElementById('bookingModal').classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeBooking() {
        document.getElementById('bookingModal').classList.remove('open');
        document.body.style.overflow = '';
    }
    document.getElementById('bookingModal').addEventListener('click', function(e) {
        if (e.target === this) closeBooking();
    });

    /* ══════════════════════════════════════════════════════
       MODAL 2 — Customize & Book + Dynamic Price Calculator
    ══════════════════════════════════════════════════════ */
    let _basePrice = 0;
    let _baseDays  = 1;

    function openCustomize(name, basePrice, defaultDays) {
        _basePrice = basePrice;
        _baseDays  = defaultDays;

        document.getElementById('customModalTitle').textContent = '✏️ Customize: ' + name;
        document.getElementById('customModalPrice').textContent = 'Base price: LKR ' + basePrice.toLocaleString() + ' per person · ' + defaultDays + ' day(s)';
        document.getElementById('customHiddenName').value       = name;
        document.getElementById('customHiddenBase').value       = basePrice;

        // Pre-fill defaults
        document.getElementById('c_duration').value = defaultDays;
        document.getElementById('c_hotel').value    = '';
        document.getElementById('c_vehicle').value  = '';
        document.getElementById('c_group').value    = '';

        recalcPrice();
        document.getElementById('customizeModal').classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeCustomize() {
        document.getElementById('customizeModal').classList.remove('open');
        document.body.style.overflow = '';
    }
    document.getElementById('customizeModal').addEventListener('click', function(e) {
        if (e.target === this) closeCustomize();
    });

    /* ── Price Calculation (mirrors PHP logic exactly) ── */
    function recalcPrice() {
        const hotel   = document.getElementById('c_hotel').value;
        const vehicle = document.getElementById('c_vehicle').value;
        const days    = parseInt(document.getElementById('c_duration').value) || 0;
        const group   = parseInt(document.getElementById('c_group').value) || 0;

        const hotelAdd   = hotel === '★★★★★' ? 10000 : hotel === '★★★★' ? 5000 : 0;
        const vehicleAdd = vehicle === 'Luxury Van' ? 7000 : vehicle === 'Van' ? 3000 : 0;

        let total = (_basePrice + hotelAdd + vehicleAdd) * days;
        const hasDiscount = group > 5;
        if (hasDiscount) total *= 0.90;
        total = Math.round(total);

        // Animate the amount
        const amountEl = document.getElementById('previewAmount');
        amountEl.classList.add('updating');
        setTimeout(() => {
            amountEl.textContent = 'LKR ' + total.toLocaleString();
            amountEl.classList.remove('updating');
        }, 150);

        // Update breakdown pills
        document.getElementById('pill-base').textContent    = 'Base: LKR ' + _basePrice.toLocaleString();
        document.getElementById('pill-base').classList.add('active');

        const hotelPill = document.getElementById('pill-hotel');
        hotelPill.textContent = hotelAdd > 0 ? '🏨 +LKR ' + hotelAdd.toLocaleString() : '🏨 Included';
        hotelPill.classList.toggle('active', hotelAdd > 0);

        const vehiclePill = document.getElementById('pill-vehicle');
        vehiclePill.textContent = vehicleAdd > 0 ? '🚗 +LKR ' + vehicleAdd.toLocaleString() : '🚗 Included';
        vehiclePill.classList.toggle('active', vehicleAdd > 0);

        const dayPill = document.getElementById('pill-days');
        dayPill.textContent = days > 0 ? '📅 ×' + days + ' days' : '📅 Days: —';
        dayPill.classList.toggle('active', days > 0);

        const discountPill = document.getElementById('pill-discount');
        discountPill.style.display = hasDiscount ? 'flex' : 'none';
        discountPill.classList.toggle('discount', hasDiscount);

        // Note
        const noteEl = document.getElementById('previewNote');
        if (!hotel || !vehicle || !days) {
            noteEl.textContent = 'Fill all options above to finalize your price';
        } else if (hasDiscount) {
            noteEl.textContent = '🎉 Group discount of 10% applied for ' + group + '+ people!';
        } else if (group > 0 && group <= 5) {
            noteEl.textContent = 'Tip: Groups of 6+ people get a 10% discount!';
        } else {
            noteEl.textContent = 'Price updates live as you change options';
        }
    }

    /* Set the final computed price into the hidden field before submitting */
    function setFinalPrice() {
        const hotel   = document.getElementById('c_hotel').value;
        const vehicle = document.getElementById('c_vehicle').value;
        const days    = parseInt(document.getElementById('c_duration').value) || 0;
        const group   = parseInt(document.getElementById('c_group').value) || 0;
        const hotelAdd   = hotel === '★★★★★' ? 10000 : hotel === '★★★★' ? 5000 : 0;
        const vehicleAdd = vehicle === 'Luxury Van' ? 7000 : vehicle === 'Van' ? 3000 : 0;
        let total = (_basePrice + hotelAdd + vehicleAdd) * days;
        if (group > 5) total *= 0.90;
        document.getElementById('customHiddenPriceDisplay').value = 'LKR ' + Math.round(total).toLocaleString();
    }
</script>
</body>
</html>