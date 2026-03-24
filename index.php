<?php
$conn = new mysqli('localhost', 'root', '', 'jettransfer');
$featuredDestinations = [];
if (!$conn->connect_error) {
    $conn->set_charset('utf8mb4');
    // Get 3 featured destinations for homepage
    $r = $conn->query("SELECT * FROM destinations WHERE is_active=1 ORDER BY id ASC LIMIT 3");
    if ($r) while ($row = $r->fetch_assoc()) $featuredDestinations[] = $row;
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jettransfer Travels - Explore Sri Lanka with Comfort</title>
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
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Manrope',sans-serif;color:var(--text-dark);line-height:1.6;overflow-x:hidden}

        /* HEADER */
        .header{position:fixed;top:0;left:0;right:0;background:rgba(255,255,255,0.95);backdrop-filter:blur(10px);box-shadow:var(--shadow-sm);z-index:1000;transition:all 0.3s ease}
        .header.scrolled{box-shadow:var(--shadow-md)}
        .nav-container{max-width:1400px;margin:0 auto;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center}
        .logo{font-family:'Sora',sans-serif;font-size:1.5rem;font-weight:800;color:var(--primary);text-decoration:none;display:flex;align-items:center;gap:0.5rem}
        .logo-img{width:42px;height:42px;border-radius:12px;object-fit:cover;border:1px solid rgba(2,6,23,.10);box-shadow:0 10px 22px rgba(10,126,164,.15);background:#fff}
        .nav-menu{display:flex;list-style:none;gap:1.6rem;align-items:center}
        .nav-menu a{color:var(--text-dark);text-decoration:none;font-weight:500;font-size:0.88rem;transition:color 0.3s ease;position:relative}
        .nav-menu a::after{content:'';position:absolute;bottom:-5px;left:0;width:0;height:2px;background:var(--primary);transition:width 0.3s ease}
        .nav-menu a:hover::after,.nav-menu a.active::after{width:100%}
        .nav-menu a:hover,.nav-menu a.active{color:var(--primary)}
        .nav-actions{display:flex;align-items:center;gap:0.6rem;flex-shrink:0}
        .nav-search{position:relative;display:flex;align-items:center}
        .search-toggle{width:38px;height:38px;border-radius:50%;border:2px solid #E2E8F0;background:transparent;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text-dark);transition:all 0.3s ease}
        .search-toggle:hover{border-color:var(--primary);color:var(--primary);background:var(--bg-light)}
        .search-box{position:absolute;right:0;top:50%;transform:translateY(-50%);display:flex;align-items:center;background:white;border:2px solid var(--primary);border-radius:50px;overflow:hidden;width:0;opacity:0;pointer-events:none;transition:width 0.4s ease,opacity 0.3s ease;box-shadow:var(--shadow-md);z-index:10}
        .search-box.open{width:280px;opacity:1;pointer-events:all}
        .search-box input{border:none;outline:none;padding:0.55rem 1rem;font-family:'Manrope',sans-serif;font-size:0.88rem;color:var(--text-dark);width:100%;background:transparent}
        .search-submit{background:var(--primary);border:none;padding:0.55rem 1rem;color:white;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background 0.3s;flex-shrink:0}
        .search-submit:hover{background:var(--primary-dark)}
        .nav-auth{display:flex;align-items:center;gap:0.4rem}
        .btn-login{padding:0.5rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:0.88rem;color:var(--primary);border:2px solid var(--primary);transition:all 0.3s ease;white-space:nowrap}
        .btn-login:hover{background:var(--primary);color:white;transform:translateY(-2px)}
        .btn-register{padding:0.5rem 1.2rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:0.88rem;background:var(--primary);color:white;border:2px solid var(--primary);transition:all 0.3s ease;box-shadow:0 4px 12px rgba(10,126,164,0.3);white-space:nowrap}
        .btn-register:hover{background:var(--primary-dark);border-color:var(--primary-dark);transform:translateY(-2px)}
        .mobile-toggle{display:none;flex-direction:column;gap:5px;cursor:pointer;padding:8px}
        .mobile-toggle span{width:25px;height:3px;background:var(--text-dark);transition:all 0.3s ease;border-radius:2px}
        .mobile-auth{display:none}

        /* HERO */
        .hero{margin-top:80px;height:90vh;position:relative;overflow:hidden;display:flex;align-items:center}
        .hero-video{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;object-position:center center;z-index:0}
        .hero-overlay{position:absolute;top:0;left:0;width:100%;height:100%;background:linear-gradient(135deg,rgba(10,126,164,0.7),rgba(16,185,129,0.6));z-index:1}
        .hero::before{content:'';position:absolute;top:0;left:0;right:0;bottom:0;background:radial-gradient(circle at 20% 50%,rgba(16,185,129,0.2) 0%,transparent 50%),radial-gradient(circle at 80% 80%,rgba(245,158,11,0.2) 0%,transparent 50%);animation:pulse 8s ease-in-out infinite}
        @keyframes pulse{0%,100%{opacity:0.5}50%{opacity:1}}
        
        /* NEW BEAUTIFUL BANNER TEXT - FALLS INTO PLACE */
        .hero-text-banner {
            position: absolute;
            z-index: 15;
            left: 0;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            text-align: center;
            pointer-events: none;
            animation: fallGlow 1.2s cubic-bezier(0.21, 1.11, 0.38, 1) forwards;
        }
        .brand-title {
            font-family: 'Sora', sans-serif;
            font-size: 5.2rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #FFFFFF 0%, #FFF9E6 30%, #FFE6B3 70%, #FFD966 100%);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            text-shadow: 0 10px 30px rgba(0,0,0,0.25);
            display: inline-block;
            padding: 0.2em 0.8em;
            backdrop-filter: blur(6px);
            border-radius: 60px;
            white-space: nowrap;
            animation: textGlow 1.5s ease-out;
        }
        .brand-sub {
            display: block;
            font-size: 1.3rem;
            font-weight: 500;
            letter-spacing: 4px;
            color: rgba(255,255,240,0.95);
            text-transform: uppercase;
            margin-top: 0.8rem;
            word-spacing: 6px;
            opacity: 0;
            animation: slideUpFade 0.8s ease-out 0.6s forwards;
            text-shadow: 0 2px 12px rgba(0,0,0,0.2);
        }
        @keyframes fallGlow {
            0% {
                opacity: 0;
                transform: translateY(-80%) scale(0.92);
                filter: blur(8px);
            }
            40% {
                opacity: 0.7;
                transform: translateY(8%) scale(1.02);
                filter: blur(0);
            }
            70% {
                transform: translateY(-2%) scale(0.99);
            }
            100% {
                opacity: 1;
                transform: translateY(-50%) scale(1);
                filter: blur(0);
            }
        }
        @keyframes textGlow {
            0% {
                text-shadow: 0 0 0 rgba(255,215,0,0);
                letter-spacing: 4px;
            }
            40% {
                text-shadow: 0 0 18px rgba(255,220,80,0.8);
            }
            100% {
                text-shadow: 0 10px 30px rgba(0,0,0,0.25);
            }
        }
        @keyframes slideUpFade {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* FLYING EMOJIS ANIMATION ON WHITE BACKGROUND SECTIONS */
        .section {
            position: relative;
            overflow: hidden;
        }
        .flying-emojis-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }
        .flying-emojis-bg .emoji {
            position: absolute;
            font-size: 1.8rem;
            opacity: 0;
            animation: flyAcross 20s linear infinite;
            filter: drop-shadow(0 2px 6px rgba(0,0,0,0.1));
            pointer-events: none;
            will-change: transform;
        }
        @keyframes flyAcross {
            0% {
                opacity: 0;
                transform: translateX(-100px) translateY(10vh) rotate(0deg) scale(0.4);
            }
            15% {
                opacity: 0.7;
            }
            30% {
                opacity: 1;
                transform: translateX(20vw) translateY(5vh) rotate(90deg) scale(1);
            }
            60% {
                opacity: 0.9;
                transform: translateX(60vw) translateY(15vh) rotate(180deg) scale(1.1);
            }
            85% {
                opacity: 0.5;
                transform: translateX(90vw) translateY(8vh) rotate(270deg) scale(0.8);
            }
            100% {
                opacity: 0;
                transform: translateX(120vw) translateY(0vh) rotate(360deg) scale(0.3);
            }
        }
        /* Different directions for variety */
        .flying-emojis-bg .emoji:nth-child(even) {
            animation-direction: reverse;
            animation-duration: 24s;
        }
        .flying-emojis-bg .emoji:nth-child(3n) {
            animation-duration: 18s;
        }
        .flying-emojis-bg .emoji:nth-child(5n+2) {
            animation-duration: 28s;
            animation-delay: -5s;
        }
        .flying-emojis-bg .emoji:nth-child(7n+4) {
            animation-duration: 22s;
            animation-delay: -10s;
        }
        /* Make sure content stays above emojis */
        .section .container,
        .section .section-header,
        .section .card-grid,
        .section .view-all-wrap,
        .section .features-grid {
            position: relative;
            z-index: 2;
        }
        
        /* Responsive banner */
        @media (max-width: 768px) {
            .brand-title {
                font-size: 2.5rem;
                white-space: normal;
                padding: 0.2em 0.5em;
                background: linear-gradient(135deg, #FFF, #FFF2CC);
                background-clip: text;
                -webkit-background-clip: text;
            }
            .brand-sub {
                font-size: 0.9rem;
                letter-spacing: 2px;
            }
            .hero-text-banner {
                width: 90%;
                left: 5%;
                right: 5%;
            }
            .flying-emojis-bg .emoji {
                font-size: 1.2rem;
            }
        }
        @media (max-width: 480px) {
            .brand-title {
                font-size: 1.8rem;
            }
            .brand-sub {
                font-size: 0.7rem;
                letter-spacing: 1.5px;
            }
            .flying-emojis-bg .emoji {
                font-size: 1rem;
            }
        }
        
        .hero-content{max-width:1400px;margin:0 auto;padding:0 2rem;color:white;z-index:2;animation:fadeInUp 1s ease-out}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(40px)}to{opacity:1;transform:translateY(0)}}
        .hero h1{font-family:'Sora',sans-serif;font-size:4rem;font-weight:800;line-height:1.1;margin-bottom:1.5rem;animation:fadeInUp 1s ease-out 0.2s both}
        .hero-subtitle{font-size:1.4rem;margin-bottom:3rem;opacity:0.95;max-width:600px;font-weight:400;animation:fadeInUp 1s ease-out 0.4s both}
        .hero-buttons{display:flex;gap:1.5rem;animation:fadeInUp 1s ease-out 0.6s both}
        .btn{padding:1rem 2.5rem;border-radius:50px;text-decoration:none;font-weight:600;font-size:1.05rem;transition:all 0.3s ease;display:inline-block}
        .btn-primary{background:var(--secondary);color:var(--text-dark);box-shadow:0 6px 25px rgba(245,158,11,0.4)}
        .btn-primary:hover{transform:translateY(-3px);box-shadow:0 8px 30px rgba(245,158,11,0.5)}
        .btn-secondary{background:rgba(255,255,255,0.2);color:white;backdrop-filter:blur(10px);border:2px solid rgba(255,255,255,0.3)}
        .btn-secondary:hover{background:rgba(255,255,255,0.3);border-color:rgba(255,255,255,0.5)}

        /* SECTIONS */
        .section{padding:6rem 2rem}
        .section-alt{background:var(--bg-light)}
        .container{max-width:1400px;margin:0 auto}
        .section-header{text-align:center;margin-bottom:4rem}
        .section-tag{display:inline-block;color:var(--primary);font-weight:600;text-transform:uppercase;font-size:0.9rem;letter-spacing:1.5px;margin-bottom:1rem}
        .section-title{font-family:'Sora',sans-serif;font-size:3rem;font-weight:700;color:var(--text-dark);margin-bottom:1rem}
        .section-description{color:var(--text-light);font-size:1.2rem;max-width:700px;margin:0 auto}

        /* CARDS */
        .card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:2rem}
        .card{background:white;border-radius:20px;overflow:hidden;box-shadow:var(--shadow-md);transition:all 0.4s ease;cursor:pointer}
        .card:hover{transform:translateY(-10px);box-shadow:var(--shadow-lg)}
        .card-image{width:100%;height:250px;background:linear-gradient(135deg,var(--primary),var(--accent));position:relative;overflow:hidden}
        .card-image::after{content:'';position:absolute;top:0;left:0;right:0;bottom:0;background:linear-gradient(to bottom,transparent 0%,rgba(0,0,0,0.3) 100%)}
        .card-content{padding:2rem}
        .card-title{font-family:'Sora',sans-serif;font-size:1.5rem;font-weight:700;margin-bottom:1rem;color:var(--text-dark)}
        .card-text{color:var(--text-light);margin-bottom:1.5rem;line-height:1.7}
        .card-footer{display:flex;justify-content:space-between;align-items:center}
        .card-link{color:var(--primary);text-decoration:none;font-weight:600;display:flex;align-items:center;gap:0.5rem;transition:gap 0.3s ease}
        .card-link:hover{gap:0.8rem}
        .destination-badge{position:absolute;top:1rem;right:1rem;background:var(--secondary);color:white;padding:0.5rem 1rem;border-radius:50px;font-weight:600;font-size:0.85rem;z-index:2}
        .package-price{font-family:'Sora',sans-serif;font-size:1.8rem;font-weight:700;color:var(--primary)}
        .package-price span{font-size:0.9rem;color:var(--text-light);font-weight:400}
        .package-info{display:flex;gap:2rem;margin-bottom:1.5rem;padding-top:1rem;border-top:2px solid var(--bg-light)}
        .package-info-item{display:flex;align-items:center;gap:0.5rem;color:var(--text-light);font-size:0.95rem}

        /* FEATURES */
        .features-grid{display:grid;grid-template-columns:repeat(3, 1fr);gap:3rem}
        .feature-item{text-align:center;padding:2rem}
        .feature-icon{width:80px;height:80px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:20px;margin:0 auto 1.5rem;display:flex;align-items:center;justify-content:center;font-size:2rem;color:white;transform:rotate(-5deg);transition:transform 0.3s ease}
        .feature-item:hover .feature-icon{transform:rotate(0deg) scale(1.1)}
        .feature-title{font-family:'Sora',sans-serif;font-size:1.3rem;font-weight:700;margin-bottom:0.8rem}
        .feature-text{color:var(--text-light);line-height:1.7}

        /* REVIEWS */
        .review-card{background:white;padding:2.5rem;border-radius:20px;box-shadow:var(--shadow-md)}
        .review-header{display:flex;align-items:center;gap:1.5rem;margin-bottom:1.5rem}
        .review-avatar{width:60px;height:60px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:1.5rem;font-weight:700}
        .review-info h4{font-size:1.1rem;font-weight:600;margin-bottom:0.3rem}
        .stars{color:var(--secondary);font-size:1.1rem}
        .review-text{color:var(--text-light);line-height:1.8;font-style:italic}

        /* VIEW ALL BTN */
        .view-all-wrap{text-align:center;margin-top:2.5rem}
        .btn-view-all{display:inline-flex;align-items:center;gap:.5rem;padding:.8rem 2rem;border-radius:50px;background:var(--primary);color:white;text-decoration:none;font-weight:600;font-size:.95rem;transition:all .3s;box-shadow:0 4px 14px rgba(10,126,164,.3)}
        .btn-view-all:hover{background:var(--primary-dark);transform:translateY(-2px);box-shadow:0 6px 20px rgba(10,126,164,.4)}

        /* FOOTER */
        .footer{background:var(--text-dark);color:white;padding:4rem 2rem 2rem}
        .footer-content{max-width:1400px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:3rem;margin-bottom:3rem}
        .footer-section h3{font-family:'Sora',sans-serif;font-size:1.3rem;margin-bottom:1.5rem}
        .footer-section p,.footer-section a{color:rgba(255,255,255,0.7);text-decoration:none;display:block;margin-bottom:0.8rem;transition:color 0.3s}
        .footer-section a:hover{color:var(--secondary)}
        .social-links{display:flex;gap:1rem;margin-top:1rem}
        .social-link{width:40px;height:40px;background:rgba(255,255,255,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;transition:all 0.3s}
        .social-link:hover{background:var(--primary);transform:translateY(-3px)}
        .footer-bottom{max-width:1400px;margin:0 auto;padding-top:2rem;border-top:1px solid rgba(255,255,255,0.1);text-align:center;color:rgba(255,255,255,0.5)}

        /* ANIMATIONS */
        .fade-in{opacity:0;transform:translateY(30px);transition:opacity 0.8s ease,transform 0.8s ease}
        .fade-in.visible{opacity:1;transform:translateY(0)}

        /* RESPONSIVE */
        @media(max-width:1024px){.hero h1{font-size:3rem}.section-title{font-size:2.5rem}}
        @media(max-width:768px){
            .mobile-toggle{display:flex}
            .nav-actions{display:none}
            .nav-menu{position:fixed;top:80px;left:0;right:0;background:white;flex-direction:column;padding:2rem;gap:1.5rem;box-shadow:var(--shadow-lg);transform:translateX(-100%);transition:transform 0.3s ease}
            .nav-menu.active{transform:translateX(0)}
            .mobile-auth{display:flex !important;flex-direction:column;align-items:center;gap:0.8rem;padding-top:1.2rem;border-top:1px solid #E2E8F0;width:100%}
            .hero{height:100vh}.hero h1{font-size:2.5rem}.hero-subtitle{font-size:1.1rem}.hero-buttons{flex-direction:column}
            .section-title{font-size:2rem}.card-grid{grid-template-columns:1fr}.features-grid{grid-template-columns:1fr}
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="header" id="header">
    <nav class="nav-container">
        <a href="index.php" class="logo">
            <img src="images/logo.jpeg" alt="Jettransfer Logo" class="logo-img">
            Jettransfer
        </a>
        <ul class="nav-menu" id="navMenu">
            <li><a href="index.php" class="active">Home</a></li>
            <li><a href="destination.php">Destinations</a></li>
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

<!-- HERO SECTION -->
<section class="hero" id="home">
    <video class="hero-video" autoplay muted loop playsinline>
        <source src="video.mp4" type="video/mp4">
    </video>
    <div class="hero-overlay"></div>
    
    <!-- BEAUTIFUL BANNER TEXT -->
    <div class="hero-text-banner">
        <div class="brand-title">Jettransfer Travels</div>
        <div class="brand-sub">Explore Sri Lanka · Ride in Style</div>
    </div>
</section>

<!-- ── POPULAR DESTINATIONS (Dynamic from DB) WITH FLYING EMOJIS ── -->
<section class="section" id="destinations">
    <!-- Flying Travel Emojis Background -->
    <div class="flying-emojis-bg">
        <div class="emoji" style="top: 5%; left: -5%;">✈️</div>
        <div class="emoji" style="top: 15%; left: -8%;">🌴</div>
        <div class="emoji" style="top: 25%; left: -3%;">🏖️</div>
        <div class="emoji" style="top: 35%; left: -10%;">🚗</div>
        <div class="emoji" style="top: 45%; left: -6%;">🏝️</div>
        <div class="emoji" style="top: 55%; left: -12%;">🌊</div>
        <div class="emoji" style="top: 65%; left: -4%;">⛰️</div>
        <div class="emoji" style="top: 75%; left: -9%;">🐘</div>
        <div class="emoji" style="top: 85%; left: -7%;">🚐</div>
        <div class="emoji" style="top: 12%; left: -2%;">🏯</div>
        <div class="emoji" style="top: 22%; left: -11%;">🍃</div>
        <div class="emoji" style="top: 32%; left: -5%;">🌅</div>
        <div class="emoji" style="top: 42%; left: -13%;">🚤</div>
        <div class="emoji" style="top: 52%; left: -8%;">🏄‍♂️</div>
        <div class="emoji" style="top: 62%; left: -15%;">🏕️</div>
        <div class="emoji" style="top: 72%; left: -6%;">🌋</div>
        <div class="emoji" style="top: 82%; left: -10%;">⛲</div>
        <div class="emoji" style="top: 8%; left: -12%;">🛺</div>
        <div class="emoji" style="top: 18%; left: -4%;">🐒</div>
        <div class="emoji" style="top: 28%; left: -14%;">🏞️</div>
        <div class="emoji" style="top: 38%; left: -7%;">🚁</div>
        <div class="emoji" style="top: 48%; left: -9%;">🍛</div>
        <div class="emoji" style="top: 58%; left: -11%;">🧭</div>
        <div class="emoji" style="top: 68%; left: -5%;">🌺</div>
        <div class="emoji" style="top: 78%; left: -13%;">🎒</div>
        <div class="emoji" style="top: 88%; left: -8%;">📸</div>
    </div>
    
    <div class="container">
        <div class="section-header fade-in">
            <span class="section-tag">Discover</span>
            <h2 class="section-title">Popular Destinations</h2>
            <p class="section-description">Explore the most breathtaking locations across Sri Lanka with our expertly curated tours</p>
        </div>
        <div class="card-grid">
        <?php if (empty($featuredDestinations)): ?>
            <!-- Fallback if DB is empty — show static cards -->
            <div class="card destination-card fade-in">
                <div class="card-image" style="background:url('images/sigiriya.jpg') center/cover"></div>
                <div class="card-content">
                    <h3 class="card-title">Sigiriya Rock Fortress</h3>
                    <p class="card-text">Ancient rock fortress with stunning views, remarkable frescoes, and a fascinating history dating back to the 5th century.</p>
                    <div class="card-footer"><a href="sigiriya.html" class="card-link">View More →</a></div>
                </div>
            </div>
            <div class="card destination-card fade-in">
                <div class="card-image" style="background:url('images/ella.jpg') center/cover"></div>
                <div class="card-content">
                    <h3 class="card-title">Ella Nine Arch Bridge</h3>
                    <p class="card-text">Scenic hill country town featuring tea plantations, waterfalls, and the iconic Nine Arch Bridge railway.</p>
                    <div class="card-footer"><a href="ella.html" class="card-link">View More →</a></div>
                </div>
            </div>
            <div class="card destination-card fade-in">
                <div class="card-image" style="background:url('images/gallefort.jpg') center/cover"></div>
                <div class="card-content">
                    <h3 class="card-title">Galle Fort</h3>
                    <p class="card-text">UNESCO World Heritage colonial fortress with charming streets, boutique shops, and stunning ocean views.</p>
                    <div class="card-footer"><a href="galle.html" class="card-link">View More →</a></div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($featuredDestinations as $d): ?>
            <div class="card destination-card fade-in">
                <div class="card-image" style="background:url('<?= htmlspecialchars($d['image_path']) ?>') center/cover">
                    <span class="destination-badge"><?= htmlspecialchars($d['badge_label'] ?? $d['category']) ?></span>
                </div>
                <div class="card-content">
                    <h3 class="card-title"><?= htmlspecialchars($d['name']) ?></h3>
                    <p class="card-text"><?= htmlspecialchars($d['short_desc']) ?></p>
                    <div class="card-footer">
                        <a href="<?= htmlspecialchars($d['detail_page'] ?: 'destination.php') ?>" class="card-link">View More →</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
        <!-- View All Destinations button -->
        <div class="view-all-wrap fade-in">
            <a href="destination.php" class="btn-view-all">
                View All Destinations
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div>
</section>

<!-- TOUR PACKAGES WITH FLYING EMOJIS -->
<section class="section section-alt" id="packages">
    <!-- Flying Travel Emojis Background -->
    <div class="flying-emojis-bg">
        <div class="emoji" style="top: 8%; left: -5%;">✈️</div>
        <div class="emoji" style="top: 18%; left: -7%;">🚐</div>
        <div class="emoji" style="top: 28%; left: -4%;">🏝️</div>
        <div class="emoji" style="top: 38%; left: -9%;">🌊</div>
        <div class="emoji" style="top: 48%; left: -6%;">⛰️</div>
        <div class="emoji" style="top: 58%; left: -11%;">🐘</div>
        <div class="emoji" style="top: 68%; left: -8%;">🚗</div>
        <div class="emoji" style="top: 78%; left: -5%;">🏯</div>
        <div class="emoji" style="top: 88%; left: -10%;">🌴</div>
        <div class="emoji" style="top: 12%; left: -12%;">🏄‍♂️</div>
        <div class="emoji" style="top: 22%; left: -3%;">🏕️</div>
        <div class="emoji" style="top: 32%; left: -14%;">🚤</div>
        <div class="emoji" style="top: 42%; left: -6%;">🌅</div>
        <div class="emoji" style="top: 52%; left: -9%;">🍃</div>
        <div class="emoji" style="top: 62%; left: -13%;">🎒</div>
        <div class="emoji" style="top: 72%; left: -7%;">📸</div>
        <div class="emoji" style="top: 82%; left: -11%;">🧭</div>
    </div>
    
    <div class="container">
        <div class="section-header fade-in">
            <span class="section-tag">Featured</span>
            <h2 class="section-title">Tour Packages</h2>
            <p class="section-description">Carefully crafted itineraries for an unforgettable Sri Lankan adventure</p>
        </div>
        <div class="card-grid">
            <div class="card fade-in">
                <div class="card-image" style="background:url('images/southern.jpg') center/cover"></div>
                <div class="card-content">
                    <h3 class="card-title">Southern Beach Escape</h3>
                    <p class="card-text">Sun-kissed beaches, Galle Fort, whale watching in Mirissa, and the peaceful shores of Tangalle — a perfect southern coastal escape.</p>
                    <div class="package-info">
                        <div class="package-info-item"><span>⏱</span> 5 Days / 4 Nights</div>
                        <div class="package-info-item"><span>👥</span> 2-6 People</div>
                    </div>
                    <div class="card-footer">
                        <div class="package-price">LKR 75,000<span> / person</span></div>
                        <a href="packages.php" class="btn btn-primary" style="padding:0.7rem 1.8rem;font-size:0.95rem">View</a>
                    </div>
                </div>
            </div>
            <div class="card fade-in">
                <div class="card-image" style="background:url('images/full.jpg') center/cover"></div>
                <div class="card-content">
                    <h3 class="card-title">Full Island Tour</h3>
                    <p class="card-text">The ultimate Sri Lanka journey — from the Cultural Triangle and misty Hill Country to the wild South, serene East Coast, and vibrant Northern Peninsula.</p>
                    <div class="package-info">
                        <div class="package-info-item"><span>⏱</span> 14 Days / 13 Nights</div>
                        <div class="package-info-item"><span>👥</span> 2-12 People</div>
                    </div>
                    <div class="card-footer">
                        <div class="package-price">LKR 180,000<span> / person</span></div>
                        <a href="packages.php" class="btn btn-primary" style="padding:0.7rem 1.8rem;font-size:0.95rem">View</a>
                    </div>
                </div>
            </div>
            <div class="card fade-in">
                <div class="card-image" style="background:url('images/hill.jpg') center/cover"></div>
                <div class="card-content">
                    <h3 class="card-title">Hill Country Tour</h3>
                    <p class="card-text">Tea plantations, the Kandy–Ella scenic train, and misty waterfalls in Sri Lanka's cool highlands.</p>
                    <div class="package-info">
                        <div class="package-info-item"><span>⏱</span> 7 Days / 6 Nights</div>
                        <div class="package-info-item"><span>👥</span> 2-8 People</div>
                    </div><br>
                    <div class="card-footer">
                        <div class="package-price">LKR 60,000<span> / person</span></div>
                        <a href="packages.php" class="btn btn-primary" style="padding:0.7rem 1.8rem;font-size:0.95rem">View</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- WHY CHOOSE US WITH FLYING EMOJIS -->
<section class="section section-alt" id="services">
    <!-- Flying Travel Emojis Background -->
    <div class="flying-emojis-bg">
        <div class="emoji" style="top: 5%; left: -4%;">⭐</div>
        <div class="emoji" style="top: 15%; left: -8%;">🚗</div>
        <div class="emoji" style="top: 25%; left: -6%;">🎯</div>
        <div class="emoji" style="top: 35%; left: -10%;">✨</div>
        <div class="emoji" style="top: 45%; left: -5%;">💰</div>
        <div class="emoji" style="top: 55%; left: -12%;">🌍</div>
        <div class="emoji" style="top: 65%; left: -7%;">⏰</div>
        <div class="emoji" style="top: 75%; left: -9%;">🏆</div>
        <div class="emoji" style="top: 85%; left: -11%;">💎</div>
        <div class="emoji" style="top: 10%; left: -13%;">👍</div>
        <div class="emoji" style="top: 20%; left: -3%;">🚐</div>
        <div class="emoji" style="top: 30%; left: -14%;">🎉</div>
        <div class="emoji" style="top: 40%; left: -6%;">💪</div>
        <div class="emoji" style="top: 50%; left: -8%;">🌟</div>
        <div class="emoji" style="top: 60%; left: -11%;">🚀</div>
    </div>
    
    <div class="container">
        <div class="section-header fade-in">
            <span class="section-tag">Excellence</span>
            <h2 class="section-title">Why Choose Us</h2>
            <p class="section-description">Experience the difference with our commitment to quality service</p>
        </div>
        <div class="features-grid">
            <div class="feature-item fade-in"><div class="feature-icon">🎯</div><h3 class="feature-title">Professional Drivers</h3><p class="feature-text">Experienced, licensed drivers who know Sri Lanka's roads like the back of their hand, ensuring safe and smooth journeys.</p></div>
            <div class="feature-item fade-in"><div class="feature-icon">🚗</div><h3 class="feature-title">Comfortable Vehicles</h3><p class="feature-text">Modern, air-conditioned vehicles maintained to the highest standards for your comfort and safety throughout your trip.</p></div>
            <div class="feature-item fade-in"><div class="feature-icon">⏰</div><h3 class="feature-title">24/7 Support</h3><p class="feature-text">Round-the-clock customer service ready to assist you anytime, ensuring peace of mind during your travels.</p></div>
            <div class="feature-item fade-in"><div class="feature-icon">✨</div><h3 class="feature-title">Custom Tour Plans</h3><p class="feature-text">Flexible itineraries tailored to your interests, schedule, and budget for a truly personalized experience.</p></div>
            <div class="feature-item fade-in"><div class="feature-icon">💰</div><h3 class="feature-title">Affordable Pricing</h3><p class="feature-text">We offer competitive and transparent pricing with no hidden costs, giving you the best value for your travel experience.</p></div>
            <div class="feature-item fade-in"><div class="feature-icon">🌍</div><h3 class="feature-title">Wide Range of Destinations</h3><p class="feature-text">Explore a variety of popular and hidden destinations across Sri Lanka with carefully planned tour packages.</p></div>
        </div>
    </div>
</section>

<!-- CUSTOMER REVIEWS WITH FLYING EMOJIS -->
<section class="section" id="reviews">
    <!-- Flying Travel Emojis Background -->
    <div class="flying-emojis-bg">
        <div class="emoji" style="top: 8%; left: -6%;">⭐</div>
        <div class="emoji" style="top: 18%; left: -4%;">😊</div>
        <div class="emoji" style="top: 28%; left: -9%;">👍</div>
        <div class="emoji" style="top: 38%; left: -7%;">🌟</div>
        <div class="emoji" style="top: 48%; left: -11%;">💬</div>
        <div class="emoji" style="top: 58%; left: -5%;">❤️</div>
        <div class="emoji" style="top: 68%; left: -12%;">🎉</div>
        <div class="emoji" style="top: 78%; left: -8%;">🏆</div>
        <div class="emoji" style="top: 88%; left: -10%;">✨</div>
        <div class="emoji" style="top: 12%; left: -13%;">😍</div>
        <div class="emoji" style="top: 22%; left: -3%;">💯</div>
        <div class="emoji" style="top: 32%; left: -14%;">🎯</div>
        <div class="emoji" style="top: 42%; left: -5%;">📝</div>
        <div class="emoji" style="top: 52%; left: -9%;">👏</div>
    </div>
    
    <div class="container">
        <div class="section-header fade-in">
            <span class="section-tag">Testimonials</span>
            <h2 class="section-title">What Our Clients Say</h2>
            <p class="section-description">Read about experiences from our happy travelers</p>
        </div>
        <div class="card-grid">
            <div class="review-card fade-in">
                <div class="review-header"><div class="review-avatar">SJ</div><div class="review-info"><h4>Sarah Johnson</h4><div class="stars">★★★★★</div></div></div>
                <p class="review-text">"Absolutely wonderful experience! Our driver was knowledgeable and friendly, the vehicle was spotless, and every detail was perfectly organized. Jettransfer made our Sri Lanka vacation unforgettable!"</p>
            </div>
            <div class="review-card fade-in">
                <div class="review-header"><div class="review-avatar">MP</div><div class="review-info"><h4>Rohit Patel</h4><div class="stars">★★★★★</div></div></div>
                <p class="review-text">"Professional service from start to finish. The custom tour plan was exactly what we wanted, and the team went above and beyond to ensure we had the best experience. Highly recommended!"</p>
            </div>
            <div class="review-card fade-in">
                <div class="review-header"><div class="review-avatar">EW</div><div class="review-info"><h4>Emma Williams</h4><div class="stars">★★★★★</div></div></div>
                <p class="review-text">"Best travel decision we made! The vehicles were luxurious, drivers were punctual and courteous, and the entire journey was smooth. Will definitely book with Jettransfer again on our next visit!"</p>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
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
            <a href="package.php">Tour Packages</a>
            <a href="vehicles.php">Our Vehicles</a>
            <a href="service.php">Services</a>
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
            <p style="margin-top:1rem;color:var(--secondary)">24/7 Emergency Support Available</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2026 Jettransfer Travels. All rights reserved. | Designed with care for your journey</p>
    </div>
</footer>

<script>
const mobileToggle = document.getElementById('mobileToggle');
const navMenu = document.getElementById('navMenu');
mobileToggle.addEventListener('click', () => navMenu.classList.toggle('active'));
document.querySelectorAll('.nav-menu a').forEach(link => link.addEventListener('click', () => navMenu.classList.remove('active')));

window.addEventListener('scroll', () => {
    document.getElementById('header').classList.toggle('scrolled', window.scrollY > 100);
});

const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
        if (entry.isIntersecting) setTimeout(() => entry.target.classList.add('visible'), index * 100);
    });
}, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            window.scrollTo({ top: target.getBoundingClientRect().top + window.pageYOffset - 80, behavior: 'smooth' });
        }
    });
});

const searchToggle = document.getElementById('searchToggle');
const searchBox    = document.getElementById('searchBox');
const searchInput  = document.getElementById('searchInput');
if(searchToggle){
searchToggle.addEventListener('click', (e) => {
    e.stopPropagation();
    searchBox.classList.toggle('open');
    if (searchBox.classList.contains('open')) setTimeout(() => searchInput.focus(), 300);
});
document.addEventListener('click', (e) => {
    if (!document.getElementById('navSearch')?.contains(e.target)) searchBox?.classList.remove('open');
});
}
const sections = document.querySelectorAll('section[id]');
window.addEventListener('scroll', () => {
    let current = '';
    sections.forEach(sec => { if (window.scrollY >= sec.offsetTop - 120) current = sec.getAttribute('id'); });
    document.querySelectorAll('.nav-menu a').forEach(a => {
        a.classList.remove('active');
        if (a.getAttribute('href') === '#' + current) a.classList.add('active');
    });
});
</script>
</body>
</html>