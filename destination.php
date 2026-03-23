<?php

$conn = new mysqli('localhost', 'root', '', 'jettransfer');
if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:3rem;text-align:center;color:red">
        <h2>⚠️ Database Error</h2>
        <p>'.$conn->connect_error.'</p>
        <p>Make sure MySQL is running in XAMPP.</p>
    </div>');
}
$conn->set_charset('utf8mb4');

// Get ALL active destinations from DB
$destinations = [];
$r = $conn->query("SELECT * FROM destinations WHERE is_active=1 ORDER BY name ASC");
if ($r) while ($row = $r->fetch_assoc()) $destinations[] = $row;

// Fetch categories from DB
$categories = [];
$r2 = $conn->query("SELECT DISTINCT category FROM destinations WHERE is_active=1 ORDER BY category ASC");
if ($r2) while ($row = $r2->fetch_assoc()) $categories[] = $row['category'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Jettransfer - Destinations</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=Manrope:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary:#0A7EA4; --primary-dark:#065A7A; --secondary:#F59E0B;
            --accent:#10B981; --text-dark:#0F172A; --text-light:#64748B;
            --bg-light:#F8FAFC; --white:#FFFFFF;
            --shadow-sm:0 1px 3px rgba(0,0,0,0.08);
            --shadow-md:0 4px 12px rgba(0,0,0,0.1);
            --shadow-lg:0 10px 40px rgba(0,0,0,0.15);
        }
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Manrope',sans-serif;color:var(--text-dark);line-height:1.6;overflow-x:hidden}
        .header{position:fixed;top:0;left:0;right:0;background:rgba(255,255,255,0.95);backdrop-filter:blur(10px);box-shadow:var(--shadow-sm);z-index:1000;transition:all 0.3s}
        .header.scrolled{box-shadow:var(--shadow-md)}
        .nav-container{max-width:1400px;margin:0 auto;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center}
        .logo{font-family:'Sora',sans-serif;font-size:1.3rem;font-weight:800;color:var(--primary);text-decoration:none;display:flex;align-items:center;gap:.6rem;white-space:nowrap}
        .logo-img{width:42px;height:42px;border-radius:12px;object-fit:cover;border:1px solid rgba(2,6,23,.10);box-shadow:0 10px 22px rgba(10,126,164,.15);background:#fff}
        .nav-menu{display:flex;list-style:none;gap:1.6rem;align-items:center}
        .nav-menu a{color:var(--text-dark);text-decoration:none;font-weight:500;font-size:0.88rem;transition:color 0.3s;position:relative}
        .nav-menu a::after{content:'';position:absolute;bottom:-5px;left:0;width:0;height:2px;background:var(--primary);transition:width 0.3s}
        .nav-menu a:hover::after,.nav-menu a.active::after{width:100%}
        .nav-menu a:hover,.nav-menu a.active{color:var(--primary)}
        .nav-actions{display:flex;align-items:center;gap:0.6rem;flex-shrink:0}
        .nav-search{position:relative;display:flex;align-items:center}
        .search-toggle{width:38px;height:38px;border-radius:50%;border:2px solid #E2E8F0;background:transparent;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text-dark);transition:all 0.3s}
        .search-toggle:hover{border-color:var(--primary);color:var(--primary);background:var(--bg-light)}
        .search-box{position:absolute;right:0;top:50%;transform:translateY(-50%);display:flex;align-items:center;background:white;border:2px solid var(--primary);border-radius:50px;overflow:hidden;width:0;opacity:0;pointer-events:none;transition:width 0.4s,opacity 0.3s;box-shadow:var(--shadow-md);z-index:10}
        .search-box.open{width:280px;opacity:1;pointer-events:all}
        .search-box input{border:none;outline:none;padding:0.55rem 1rem;font-family:'Manrope',sans-serif;font-size:0.88rem;color:var(--text-dark);width:100%;background:transparent}
        .search-submit{background:var(--primary);border:none;padding:0.55rem 1rem;color:white;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background 0.3s;flex-shrink:0}
        .search-submit:hover{background:var(--primary-dark)}
        .nav-auth{display:flex;align-items:center;gap:0.4rem}
        .btn-login{padding:0.5rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:0.88rem;color:var(--primary);border:2px solid var(--primary);transition:all 0.3s;white-space:nowrap}
        .btn-login:hover{background:var(--primary);color:white;transform:translateY(-2px)}
        .btn-register{padding:0.5rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:0.88rem;background:var(--primary);color:white;border:2px solid var(--primary);transition:all 0.3s;box-shadow:0 4px 12px rgba(10,126,164,0.3);white-space:nowrap}
        .btn-register:hover{background:var(--primary-dark);border-color:var(--primary-dark);transform:translateY(-2px)}
        .mobile-toggle{display:none;flex-direction:column;gap:5px;cursor:pointer;padding:8px}
        .mobile-toggle span{width:25px;height:3px;background:var(--text-dark);transition:all 0.3s;border-radius:2px}
        .mobile-auth{display:none}
        .hero{margin-top:80px;height:90vh;background:url('images/hero.jpeg') no-repeat center center/cover;display:flex;justify-content:center;align-items:center;text-align:center;position:relative;overflow:hidden}
        .hero::before{content:'';position:absolute;inset:0;background:linear-gradient(160deg,rgba(6,90,122,0.72) 0%,rgba(10,126,164,0.45) 40%,rgba(0,0,0,0.55) 100%);z-index:1}
        .hero-particles{position:absolute;inset:0;z-index:2;pointer-events:none;overflow:hidden}
        .particle{position:absolute;border-radius:50%;background:rgba(255,255,255,0.15);animation:floatUp linear infinite}
        @keyframes floatUp{0%{transform:translateY(110vh) scale(0.5);opacity:0}10%{opacity:1}90%{opacity:0.6}100%{transform:translateY(-10vh) scale(1);opacity:0}}
        .hero-content{position:relative;z-index:3;animation:heroReveal 1.1s cubic-bezier(0.22,1,0.36,1) both}
        @keyframes heroReveal{from{opacity:0;transform:translateY(40px) scale(0.97)}to{opacity:1;transform:translateY(0) scale(1)}}
        .hero-badge{display:inline-flex;align-items:center;gap:0.5rem;background:rgba(245,158,11,0.2);border:1px solid rgba(245,158,11,0.5);color:#FDE68A;padding:0.35rem 1rem;border-radius:50px;font-size:0.8rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:1.2rem;backdrop-filter:blur(6px)}
        .hero-content h1{font-family:'Sora',sans-serif;font-size:clamp(2.4rem,5vw,4rem);font-weight:700;color:#fff;line-height:1.1;margin-bottom:1rem;text-shadow:0 4px 30px rgba(0,0,0,0.3)}
        .hero-content h1 span{color:var(--secondary)}
        .hero-content p{font-size:1.15rem;color:rgba(255,255,255,0.88);margin-bottom:2rem}
        .hero-scroll{position:absolute;bottom:2.5rem;left:50%;transform:translateX(-50%);z-index:3;display:flex;flex-direction:column;align-items:center;gap:0.4rem;color:rgba(255,255,255,0.6);font-size:0.72rem;letter-spacing:0.1em;text-transform:uppercase}
        .hero-scroll-line{width:2px;height:40px;background:linear-gradient(to bottom,rgba(255,255,255,0.6),transparent);animation:scrollPulse 2s ease-in-out infinite}
        @keyframes scrollPulse{0%,100%{transform:scaleY(1);opacity:0.6}50%{transform:scaleY(0.6);opacity:0.2}}
        .emoji-bg{position:absolute;inset:0;pointer-events:none;overflow:hidden;z-index:0}
        .emoji-float{position:absolute;font-size:2rem;opacity:0;animation:emojiDrift linear infinite;will-change:transform,opacity;user-select:none}
        @keyframes emojiDrift{0%{transform:translateY(60px) rotate(-15deg) scale(0.6);opacity:0}8%{opacity:0.55}40%{transform:translateY(-30vh) rotate(10deg) scale(1.1) translateX(30px);opacity:0.45}75%{transform:translateY(-65vh) rotate(-8deg) scale(0.95) translateX(-20px);opacity:0.3}95%{opacity:0}100%{transform:translateY(-90vh) rotate(20deg) scale(0.7) translateX(10px);opacity:0}}
        .section{padding:80px 20px;background:var(--bg-light);position:relative}
        .section-header{text-align:center;margin-bottom:40px;position:relative;z-index:1}
        .section-tag{display:inline-block;background:linear-gradient(135deg,rgba(10,126,164,0.1),rgba(16,185,129,0.1));border:1px solid rgba(10,126,164,0.2);color:var(--primary);font-size:0.75rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;padding:0.3rem 1rem;border-radius:50px;margin-bottom:0.8rem}
        .section h2{font-family:'Sora',sans-serif;font-size:clamp(1.8rem,3vw,2.6rem);font-weight:700;color:var(--text-dark);margin-bottom:1rem;line-height:1.2}
        .section-divider{width:60px;height:4px;background:linear-gradient(90deg,var(--primary),var(--secondary));border-radius:2px;margin:0 auto}
        .dest-search-wrap{display:flex;justify-content:center;margin-bottom:30px;position:relative;z-index:1}
        .dest-search-inner{display:flex;align-items:center;background:white;border:2px solid #E2E8F0;border-radius:50px;overflow:hidden;box-shadow:var(--shadow-sm);width:100%;max-width:480px;transition:border-color 0.3s,box-shadow 0.3s}
        .dest-search-inner:focus-within{border-color:var(--primary);box-shadow:0 0 0 4px rgba(10,126,164,0.1)}
        .dest-search-inner input{flex:1;border:none;outline:none;padding:0.7rem 1.2rem;font-family:'Manrope',sans-serif;font-size:0.9rem;color:var(--text-dark);background:transparent}
        .dest-search-inner button{background:var(--primary);border:none;padding:0.7rem 1.2rem;color:white;cursor:pointer;display:flex;align-items:center;gap:0.4rem;font-family:'Manrope',sans-serif;font-weight:600;font-size:0.85rem;transition:background 0.3s;flex-shrink:0}
        .dest-search-inner button:hover{background:var(--primary-dark)}
        .filter-bar{display:flex;justify-content:center;gap:0.6rem;flex-wrap:wrap;margin-bottom:30px;position:relative;z-index:1}
        .filter-btn{padding:0.5rem 1.3rem;border-radius:50px;border:2px solid #E2E8F0;background:white;color:var(--text-light);font-family:'Manrope',sans-serif;font-weight:600;font-size:0.82rem;cursor:pointer;transition:all 0.3s}
        .filter-btn:hover,.filter-btn.active{border-color:var(--primary);background:var(--primary);color:white;transform:translateY(-2px);box-shadow:0 6px 16px rgba(10,126,164,0.3)}
        .results-count{text-align:center;margin-bottom:20px;color:var(--text-light);font-size:0.85rem;position:relative;z-index:1}
        .results-count span{color:var(--primary);font-weight:700;font-size:1rem}
        .card-grid{max-width:1200px;margin:auto;display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:30px;position:relative;z-index:1}
        .card{background:white;border-radius:18px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.07);transition:transform 0.4s cubic-bezier(0.34,1.56,0.64,1),box-shadow 0.4s;position:relative;cursor:pointer}
        .card:hover{transform:translateY(-12px);box-shadow:0 24px 50px rgba(10,126,164,0.18)}
        .card-img-wrap{position:relative;overflow:hidden;height:220px}
        .card img{width:100%;height:100%;object-fit:cover;transition:transform 0.6s cubic-bezier(0.25,0.46,0.45,0.94)}
        .card:hover img{transform:scale(1.12)}
        .card-img-wrap::after{content:'';position:absolute;inset:0;background:linear-gradient(to top,rgba(6,90,122,0.55) 0%,transparent 60%);opacity:0;transition:opacity 0.4s}
        .card:hover .card-img-wrap::after{opacity:1}
        .card-badge{position:absolute;top:14px;left:14px;z-index:2;background:var(--secondary);color:#fff;font-size:0.68rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;padding:0.25rem 0.75rem;border-radius:50px;box-shadow:0 2px 8px rgba(0,0,0,0.15)}
        .card-view-icon{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%) scale(0.7);z-index:3;width:54px;height:54px;border-radius:50%;background:rgba(255,255,255,0.92);display:flex;align-items:center;justify-content:center;color:var(--primary);opacity:0;transition:all 0.35s cubic-bezier(0.34,1.56,0.64,1)}
        .card:hover .card-view-icon{opacity:1;transform:translate(-50%,-50%) scale(1)}
        .card-content{padding:20px 22px 24px}
        .card-meta{display:flex;align-items:center;gap:0.4rem;color:var(--text-light);font-size:0.75rem;margin-bottom:0.4rem}
        .card-meta svg{color:var(--primary)}
        .card h3{margin-bottom:8px;font-family:'Sora',sans-serif;font-size:1.15rem;color:var(--text-dark);font-weight:700}
        .card p{color:var(--text-light);margin-bottom:16px;font-size:0.87rem;line-height:1.55}
        .btn{display:inline-flex;align-items:center;gap:0.4rem;padding:0.55rem 1.4rem;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:white;text-decoration:none;border-radius:50px;font-weight:600;font-size:0.84rem;transition:all 0.3s;box-shadow:0 4px 12px rgba(10,126,164,0.3)}
        .btn svg{transition:transform 0.3s}
        .btn:hover{background:linear-gradient(135deg,var(--primary-dark),#043D54);transform:translateY(-2px);box-shadow:0 8px 20px rgba(10,126,164,0.4)}
        .btn:hover svg{transform:translateX(4px)}
        .no-results{text-align:center;padding:60px 20px;grid-column:1/-1;display:none}
        .no-results .emoji{font-size:3.5rem;margin-bottom:1rem}
        .no-results h3{font-family:'Sora',sans-serif;font-size:1.4rem;color:var(--text-dark);margin-bottom:0.5rem}
        .no-results p{color:var(--text-light);font-size:0.9rem}
        .why{background:linear-gradient(135deg,#f0f8ff,#e6f2ff);text-align:center;padding:90px 20px;position:relative;overflow:hidden}
        .why::before,.why::after{content:'';position:absolute;border-radius:50%;filter:blur(80px);opacity:0.35;pointer-events:none}
        .why::before{width:500px;height:500px;background:radial-gradient(circle,rgba(10,126,164,0.4),transparent);top:-150px;left:-100px;animation:blobFloat 8s ease-in-out infinite}
        .why::after{width:400px;height:400px;background:radial-gradient(circle,rgba(16,185,129,0.3),transparent);bottom:-120px;right:-80px;animation:blobFloat 10s ease-in-out infinite reverse}
        @keyframes blobFloat{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(20px,-30px) scale(1.05)}66%{transform:translate(-20px,20px) scale(0.95)}}
        .why-inner{position:relative;z-index:1;font-family:'Sora',sans-serif;font-size:clamp(1.8rem,3vw,2.4rem);font-weight:700;margin-bottom:0.5rem;color:var(--text-dark)}
        .why>.why-inner>p{color:var(--text-light);margin-bottom:50px;font-size:1rem}
        .why ul{list-style:none;display:flex;justify-content:center;gap:30px;flex-wrap:wrap;padding:0}
        .why li{background:#ffffff;padding:35px 25px;width:270px;border-radius:20px;box-shadow:0 10px 30px rgba(0,0,0,0.08);font-weight:600;font-size:0.95rem;transition:all 0.4s cubic-bezier(0.34,1.56,0.64,1);position:relative;border:1px solid rgba(10,126,164,0.08);color:var(--text-dark)}
        .why li::before{content:"";display:block;width:56px;height:56px;line-height:56px;margin:0 auto 18px;background:linear-gradient(135deg,var(--primary),var(--accent));color:white;border-radius:16px;font-size:22px;box-shadow:0 6px 16px rgba(10,126,164,0.35)}
        .why li:nth-child(1)::before{content:"🧭"}.why li:nth-child(2)::before{content:"🚐"}.why li:nth-child(3)::before{content:"💎"}
        .why li:hover{transform:translateY(-12px) scale(1.03);box-shadow:0 20px 45px rgba(10,126,164,0.18)}
        .why li::after{content:'';position:absolute;top:0;left:20%;right:20%;height:3px;background:linear-gradient(90deg,var(--primary),var(--secondary));border-radius:0 0 4px 4px;opacity:0;transition:opacity 0.3s}
        .why li:hover::after{opacity:1}
        .footer{background:var(--text-dark);color:white;padding:4rem 2rem 2rem}
        .footer-content{max-width:1400px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:3rem;margin-bottom:3rem}
        .footer-section h3{font-family:'Sora',sans-serif;font-size:1.3rem;margin-bottom:1.5rem}
        .footer-section p,.footer-section a{color:rgba(255,255,255,0.7);text-decoration:none;display:block;margin-bottom:0.8rem;transition:color 0.3s}
        .footer-section a:hover{color:var(--secondary)}
        .social-links{display:flex;gap:1rem;margin-top:1rem}
        .social-link{width:40px;height:40px;background:rgba(255,255,255,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;transition:all 0.3s}
        .social-link:hover{background:var(--primary);transform:translateY(-3px)}
        .footer-bottom{max-width:1400px;margin:0 auto;padding-top:2rem;border-top:1px solid rgba(255,255,255,0.1);text-align:center;color:rgba(255,255,255,0.5)}
        .fade-in{opacity:0;transform:translateY(35px);transition:opacity 0.75s ease,transform 0.75s ease}
        .fade-in.visible{opacity:1;transform:translateY(0)}
        #readingProgress{position:fixed;top:0;left:0;height:3px;background:linear-gradient(90deg,var(--primary),var(--secondary),var(--accent));z-index:9999;width:0%;transition:width 0.1s linear}
        .back-to-top{position:fixed;bottom:2rem;right:2rem;width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:white;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 20px rgba(10,126,164,0.4);opacity:0;transform:translateY(20px);transition:all 0.4s;z-index:999}
        .back-to-top.show{opacity:1;transform:translateY(0)}
        .back-to-top:hover{transform:translateY(-4px)}
        @media(max-width:768px){.mobile-toggle{display:flex}.nav-actions{display:none}.nav-menu{position:fixed;top:80px;left:0;right:0;background:white;flex-direction:column;padding:2rem;gap:1.5rem;box-shadow:var(--shadow-lg);transform:translateX(-100%);transition:transform 0.3s}.nav-menu.active{transform:translateX(0)}.mobile-auth{display:flex !important;flex-direction:column;align-items:center;gap:0.8rem;padding-top:1.2rem;border-top:1px solid #E2E8F0;width:100%}.card-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>

<div id="readingProgress"></div>

<header class="header" id="header">
    <nav class="nav-container">
        <a href="#" class="logo">
            <img src="images/logo.jpeg" alt="Jettransfer Logo" class="logo-img">
            Jettransfer
        </a>
        <ul class="nav-menu" id="navMenu">
            <li><a href="index.php">Home</a></li>
            <li><a href="destination.php" class="active">Destinations</a></li>
            <li><a href="packages.php">Packages</a></li>
            <li><a href="vehicles.html">Vehicles</a></li>
            <li><a href="service.php">Services &amp; Gallery</a></li>
            <li><a href="contact.php">Contact Us</a></li>
            <li><a href="aboutus.php">About Us</a></li>
            
            <li class="mobile-auth">
                <div style="display:flex;gap:0.8rem;width:100%">
                    <a href="login.php" style="flex:1;display:block;padding:0.6rem 1rem;border-radius:50px;border:2px solid var(--primary);color:var(--primary);font-weight:600;font-size:0.9rem;text-decoration:none;text-align:center">Login</a>
                    <a href="register.php" style="flex:1;display:block;padding:0.6rem 1rem;border-radius:50px;background:var(--primary);color:white;font-weight:600;font-size:0.9rem;text-decoration:none;text-align:center">Register</a>
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

<section class="hero">
    <div class="hero-particles" id="particles"></div>
    <div class="hero-content">
        <div class="hero-badge">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            Sri Lanka's Finest
        </div>
        <h1>Explore <span>Amazing</span><br>Destinations</h1>
        <p>Discover the breathtaking beauty of Sri Lanka</p>
    </div>
    <div class="hero-scroll">Scroll<div class="hero-scroll-line"></div></div>
</section>

<section class="section" id="destinations">
    <div class="emoji-bg" id="emojiBg"></div>
    <div class="section-header fade-in">
        <div class="section-tag">✈ Our Destinations</div>
        <h2>Explore Beautiful Destinations in Sri Lanka</h2>
        <div class="section-divider"></div>
    </div>

    <div class="dest-search-wrap fade-in">
        <div class="dest-search-inner">
            <input type="text" id="destSearch" placeholder="Search destinations, provinces…" oninput="applyFilters()">
            <button onclick="applyFilters()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Search
            </button>
        </div>
    </div>

    <div class="filter-bar fade-in">
        <button class="filter-btn active" data-cat="All" onclick="setFilter(this)">All</button>
        <?php foreach($categories as $cat): ?>
        <button class="filter-btn" data-cat="<?= htmlspecialchars($cat) ?>" onclick="setFilter(this)">
            <?= htmlspecialchars($cat) ?>
        </button>
        <?php endforeach; ?>
    </div>

    <p class="results-count">
        Showing <span id="countNum"><?= count($destinations) ?></span> destinations
    </p>

    <div class="card-grid" id="cardGrid">
        <div class="no-results" id="noResults">
            <div class="emoji">🔍</div>
            <h3>No destinations found</h3>
            <p>Try a different search or category.</p>
        </div>

        <?php if(empty($destinations)): ?>
        <div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-light)">
            <p style="font-size:1.1rem">No destinations added yet.</p>
            <p style="font-size:.9rem;margin-top:.5rem">Add destinations from the <a href="admin/destinations.php" style="color:var(--primary);font-weight:600">Admin Panel</a>.</p>
        </div>
        <?php else: ?>
        <?php foreach($destinations as $i => $d): ?>
        <div class="card dest-card fade-in"
             style="--i:<?= $i ?>"
             data-cat="<?= htmlspecialchars($d['category']) ?>"
             data-name="<?= htmlspecialchars(strtolower($d['name'])) ?>"
             data-district="<?= htmlspecialchars(strtolower($d['district'])) ?>"
             data-desc="<?= htmlspecialchars(strtolower($d['short_desc'])) ?>">
            <div class="card-img-wrap">
                <span class="card-badge"><?= htmlspecialchars($d['badge_label'] ?? '') ?></span>
                <img src="<?= htmlspecialchars($d['image_path']) ?>" alt="<?= htmlspecialchars($d['name']) ?>" loading="lazy">
                <div class="card-view-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                </div>
            </div>
            <div class="card-content">
                <div class="card-meta">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <?= htmlspecialchars($d['district']) ?>
                </div>
                <h3><?= htmlspecialchars($d['name']) ?></h3>
                <p><?= htmlspecialchars($d['short_desc']) ?></p>
                <a href="<?= htmlspecialchars($d['detail_page']) ?>" class="btn">
                    View More
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<section class="why">
    <div class="emoji-bg" id="emojiBgWhy"></div>
    <div class="why-inner">
        <div class="section-tag fade-in" style="display:inline-block;background:rgba(10,126,164,0.1);border:1px solid rgba(10,126,164,0.2);color:var(--primary);font-size:0.75rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;padding:0.3rem 1rem;border-radius:50px;margin-bottom:0.8rem">Our Advantages</div>
        <h2 class="fade-in">Why Travel with Us?</h2>
        <p class="fade-in">We make every journey extraordinary</p>
        <ul>
            <li class="fade-in" style="--i:0">Expert Local Guides</li>
            <li class="fade-in" style="--i:1">Comfortable Transport</li>
            <li class="fade-in" style="--i:2">Best Price Guarantee</li>
        </ul>
    </div>
</section>

<footer class="footer" id="contact">
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
            <a href="terms.html">Terms &amp; Conditions</a>
            <a href="service.php">Services &amp; Gallery</a>
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
            <p style="margin-top:1rem;color:var(--secondary)">24/7 Emergency Support Available</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2026 Jettransfer Travels. All rights reserved. | Designed with care for your journey</p>
    </div>
</footer>

<button class="back-to-top" id="backToTop">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
</button>

<script>
const mobileToggle = document.getElementById('mobileToggle');
const navMenu = document.getElementById('navMenu');
mobileToggle.addEventListener('click', () => navMenu.classList.toggle('active'));
document.querySelectorAll('.nav-menu a').forEach(l => l.addEventListener('click', () => navMenu.classList.remove('active')));
window.addEventListener('scroll', () => {
    document.getElementById('header').classList.toggle('scrolled', window.scrollY > 100);
    document.getElementById('backToTop').classList.toggle('show', window.scrollY > 400);
    const pct = window.scrollY / (document.documentElement.scrollHeight - window.innerHeight) * 100;
    document.getElementById('readingProgress').style.width = pct + '%';
});
document.getElementById('backToTop').addEventListener('click', () => window.scrollTo({top:0,behavior:'smooth'}));
const pc = document.getElementById('particles');
for(let i=0;i<20;i++){
    const p=document.createElement('div'); p.className='particle';
    const s=Math.random()*18+5;
    p.style.cssText=`width:${s}px;height:${s}px;left:${Math.random()*100}%;animation-duration:${Math.random()*12+8}s;animation-delay:${Math.random()*10}s`;
    pc.appendChild(p);
}
const obs=new IntersectionObserver(entries=>{
    entries.forEach(e=>{if(e.isIntersecting){const d=e.target.style.getPropertyValue('--i')||0;setTimeout(()=>e.target.classList.add('visible'),d*80);}});
},{threshold:0.1,rootMargin:'0px 0px -40px 0px'});
document.querySelectorAll('.fade-in').forEach(el=>obs.observe(el));
const searchToggle=document.getElementById('searchToggle');
const searchBox=document.getElementById('searchBox');
const searchInput=document.getElementById('searchInput');
searchToggle.addEventListener('click',e=>{e.stopPropagation();searchBox.classList.toggle('open');if(searchBox.classList.contains('open')) setTimeout(()=>searchInput.focus(),300);});
document.addEventListener('click',e=>{if(!document.getElementById('navSearch').contains(e.target)) searchBox.classList.remove('open');});
searchInput.addEventListener('input',()=>{document.getElementById('destSearch').value=searchInput.value;applyFilters();});
function setFilter(btn){document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));btn.classList.add('active');applyFilters();}
function applyFilters(){
    const search=document.getElementById('destSearch').value.trim().toLowerCase();
    const activeBtn=document.querySelector('.filter-btn.active');
    const category=activeBtn?activeBtn.dataset.cat:'All';
    const cards=document.querySelectorAll('.dest-card');
    let visible=0;
    cards.forEach(card=>{
        const matchCat=category==='All'||card.dataset.cat===category;
        const matchSearch=search===''||card.dataset.name.includes(search)||card.dataset.district.includes(search)||card.dataset.desc.includes(search);
        card.style.display=(matchCat&&matchSearch)?'':'none';
        if(matchCat&&matchSearch) visible++;
    });
    document.getElementById('countNum').textContent=visible;
    document.getElementById('noResults').style.display=visible===0?'block':'none';
}
const emojis=['✈️','🌴','🏖️','🗺️','🧳','🌊','🏔️','🌅','🦁','🐘','🦋','🌺','🍃','🏝️','⛵','🌍','🎒','📸','🦜','🌸','🐬','🦚','🌿','🏄','🌈','🎑','🪸','🦩','🐚','🌻'];
function spawnEmoji(c){const el=document.createElement('span');el.className='emoji-float';el.textContent=emojis[Math.floor(Math.random()*emojis.length)];el.style.fontSize=(Math.random()*1.6+1.4).toFixed(2)+'rem';el.style.left=(Math.random()*96)+'%';el.style.bottom='-60px';const dur=(Math.random()*14+12).toFixed(1);const delay=(Math.random()*8).toFixed(1);el.style.animationDuration=dur+'s';el.style.animationDelay=delay+'s';c.appendChild(el);setTimeout(()=>el.remove(),(parseFloat(dur)+parseFloat(delay))*1000+500);}
function startEmojiRain(id){const c=document.getElementById(id);if(!c)return;for(let i=0;i<18;i++) setTimeout(()=>spawnEmoji(c),i*400);setInterval(()=>spawnEmoji(c),900);}
startEmojiRain('emojiBg');
startEmojiRain('emojiBgWhy');
</script>
</body>
</html>