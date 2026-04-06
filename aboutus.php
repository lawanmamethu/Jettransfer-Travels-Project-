<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Jettransfer Travels</title>
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Manrope', sans-serif; color: var(--text-dark); line-height: 1.6; overflow-x: hidden; }
        
        /* ========== CONSISTENT NAVBAR STYLES (matches destination page) ========== */
        .logo-img { width: 42px; height: 42px; border-radius: 12px; object-fit: cover; border: 1px solid rgba(2,6,23,.10); box-shadow: 0 10px 22px rgba(10,126,164,.15); background: #fff; }
        .header { position: fixed; top: 0; left: 0; right: 0; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); box-shadow: var(--shadow-sm); z-index: 1000; transition: all 0.3s ease; }
        .header.scrolled { box-shadow: var(--shadow-md); }
        .nav-container { max-width: 1400px; margin: 0 auto; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-family: 'Sora', sans-serif; font-size: 1.3rem; font-weight: 800; color: var(--primary); text-decoration: none; display: flex; align-items: center; gap: .6rem; white-space: nowrap; }
        .nav-menu { display: flex; list-style: none; gap: 1.6rem; align-items: center; }
        .nav-menu a { color: var(--text-dark); text-decoration: none; font-weight: 500; font-size: 0.88rem; transition: color 0.3s ease; position: relative; }
        .nav-menu a::after { content: ''; position: absolute; bottom: -5px; left: 0; width: 0; height: 2px; background: var(--primary); transition: width 0.3s ease; }
        .nav-menu a:hover::after, .nav-menu a.active::after { width: 100%; }
        .nav-menu a:hover, .nav-menu a.active { color: var(--primary); }
        .nav-actions { display: flex; align-items: center; gap: 0.6rem; flex-shrink: 0; }
        .nav-auth { display: flex; align-items: center; gap: 0.4rem; }
        .btn-login { padding: 0.5rem 1.2rem; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 0.88rem; color: var(--primary); border: 2px solid var(--primary); transition: all 0.3s ease; white-space: nowrap; }
        .btn-login:hover { background: var(--primary); color: white; transform: translateY(-2px); }
        .btn-register { padding: 0.5rem 1.2rem; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 0.88rem; background: var(--primary); color: white; border: 2px solid var(--primary); transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(10,126,164,0.3); white-space: nowrap; }
        .btn-register:hover { background: var(--primary-dark); border-color: var(--primary-dark); transform: translateY(-2px); }
        .mobile-toggle { display: none; flex-direction: column; gap: 5px; cursor: pointer; padding: 8px; }
        .mobile-toggle span { width: 25px; height: 3px; background: var(--text-dark); transition: all 0.3s ease; border-radius: 2px; }
        .mobile-auth { display: none; }
        
        @media (max-width: 768px) {
            .mobile-toggle { display: flex; }
            .nav-actions { display: none; }
            .nav-menu { position: fixed; top: 80px; left: 0; right: 0; background: white; flex-direction: column; padding: 2rem; gap: 1.5rem; box-shadow: var(--shadow-lg); transform: translateX(-100%); transition: transform 0.3s ease; z-index: 999; }
            .nav-menu.active { transform: translateX(0); }
            .mobile-auth { display: flex !important; flex-direction: column; align-items: center; gap: 0.8rem; padding-top: 1.2rem; border-top: 1px solid #E2E8F0; width: 100%; }
            .mobile-auth-btns { display: flex; gap: 0.8rem; width: 100%; }
            .mobile-auth-btns a { flex: 1; text-align: center; }
        }
        
        /* ========== REST OF YOUR EXISTING STYLES ========== */
        .about-hero { margin-top: 80px; height: 65vh; position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center; text-align: center; }
        .about-hero-img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; object-position: center; z-index: 0; }
        .about-hero-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(10,126,164,0.75), rgba(16,185,129,0.65)); z-index: 1; }
        .hero-shape { position: absolute; border-radius: 50%; background: rgba(255,255,255,0.06); animation: float 10s ease-in-out infinite; z-index: 2; }
        .hero-shape:nth-child(3) { width: 400px; height: 400px; top: -120px; left: -80px; animation-delay: 0s; }
        .hero-shape:nth-child(4) { width: 250px; height: 250px; bottom: -60px; right: 10%; animation-delay: 3s; }
        .hero-shape:nth-child(5) { width: 180px; height: 180px; top: 30%; right: 5%; animation-delay: 6s; }
        @keyframes float { 0%, 100% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-20px) rotate(5deg); } }
        .about-hero-content { position: relative; z-index: 3; color: white; animation: fadeInUp 1s ease-out; }
        .about-hero h1 { font-family: 'Sora', sans-serif; font-size: 3.8rem; font-weight: 800; line-height: 1.1; margin-bottom: 1.2rem; }
        .about-hero p { font-size: 1.25rem; opacity: 0.92; max-width: 600px; margin: 0 auto; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
        .section { padding: 6rem 2rem; }
        .section-alt { background: var(--bg-light); }
        .container { max-width: 1400px; margin: 0 auto; }
        .section-header { text-align: center; margin-bottom: 4rem; }
        .section-tag { display: inline-block; color: var(--primary); font-weight: 600; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 1.5px; margin-bottom: 1rem; }
        .section-title { font-family: 'Sora', sans-serif; font-size: 3rem; font-weight: 700; color: var(--text-dark); margin-bottom: 1rem; }
        .section-description { color: var(--text-light); font-size: 1.2rem; max-width: 700px; margin: 0 auto; }
        .story-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 5rem; align-items: center; }
        .story-image { position: relative; }
        .story-img-main { width: 100%; height: 440px; border-radius: 24px; overflow: hidden; position: relative; box-shadow: var(--shadow-lg); }
        .story-img-main img { width: 100%; height: 100%; object-fit: cover; object-position: center; }
        .story-img-main::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, rgba(10,126,164,0.4) 0%, transparent 60%); border-radius: 24px; }
        .story-badge { position: absolute; bottom: -30px; right: -30px; width: 160px; height: 160px; background: var(--secondary); border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 8px 30px rgba(245,158,11,0.4); text-align: center; }
        .story-badge .badge-num { font-family: 'Sora', sans-serif; font-size: 3rem; font-weight: 800; color: white; line-height: 1; }
        .story-badge .badge-label { font-size: 0.75rem; font-weight: 600; color: rgba(255,255,255,0.85); text-transform: uppercase; letter-spacing: 1px; }
        .story-text h2 { font-family: 'Sora', sans-serif; font-size: 2.6rem; font-weight: 700; margin-bottom: 1.5rem; line-height: 1.2; }
        .story-text h2 span { color: var(--primary); }
        .story-text p { color: var(--text-light); font-size: 1.05rem; line-height: 1.9; margin-bottom: 1.2rem; }
        .mv-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2.5rem; }
        .mv-card { background: white; border-radius: 24px; padding: 3rem; box-shadow: var(--shadow-md); position: relative; overflow: hidden; transition: transform 0.4s ease, box-shadow 0.4s ease; }
        .mv-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-lg); }
        .mv-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; }
        .mv-card.mission::before { background: linear-gradient(90deg, var(--primary), var(--accent)); }
        .mv-card.vision::before { background: linear-gradient(90deg, var(--secondary), #F97316); }
        .mv-icon { width: 70px; height: 70px; border-radius: 18px; margin-bottom: 1.8rem; display: flex; align-items: center; justify-content: center; font-size: 2rem; }
        .mission .mv-icon { background: linear-gradient(135deg, rgba(10,126,164,0.12), rgba(16,185,129,0.1)); }
        .vision .mv-icon { background: linear-gradient(135deg, rgba(245,158,11,0.12), rgba(249,115,22,0.1)); }
        .mv-card h3 { font-family: 'Sora', sans-serif; font-size: 1.6rem; font-weight: 700; margin-bottom: 1rem; }
        .mv-card p { color: var(--text-light); line-height: 1.8; font-size: 1.05rem; }
        .values-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; }
        .value-card { background: white; border-radius: 20px; padding: 2.5rem 2rem; box-shadow: var(--shadow-sm); text-align: center; transition: all 0.4s ease; border: 2px solid transparent; }
        .value-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-md); border-color: var(--primary); }
        .value-icon { width: 72px; height: 72px; background: linear-gradient(135deg, var(--primary), var(--accent)); border-radius: 20px; margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; font-size: 1.9rem; color: white; transform: rotate(-5deg); transition: transform 0.3s ease; }
        .value-card:hover .value-icon { transform: rotate(0deg) scale(1.1); }
        .value-card h3 { font-family: 'Sora', sans-serif; font-size: 1.2rem; font-weight: 700; margin-bottom: 0.8rem; }
        .value-card p { color: var(--text-light); font-size: 0.95rem; line-height: 1.7; }
        .cta-section { padding: 6rem 2rem; text-align: center; background: var(--bg-light); }
        .cta-box { background: linear-gradient(135deg, var(--primary), var(--accent)); border-radius: 32px; padding: 5rem 3rem; max-width: 900px; margin: 0 auto; position: relative; overflow: hidden; box-shadow: var(--shadow-lg); }
        .cta-box::before { content: ''; position: absolute; inset: 0; background: radial-gradient(circle at 70% 30%, rgba(245,158,11,0.25) 0%, transparent 60%); }
        .cta-box h2 { font-family: 'Sora', sans-serif; font-size: 2.8rem; font-weight: 800; color: white; margin-bottom: 1.2rem; position: relative; z-index: 1; }
        .cta-box p { color: rgba(255,255,255,0.88); font-size: 1.15rem; margin-bottom: 2.5rem; max-width: 600px; margin-left: auto; margin-right: auto; position: relative; z-index: 1; }
        .cta-btns { display: flex; gap: 1.2rem; justify-content: center; position: relative; z-index: 1; }
        .btn { padding: 1rem 2.5rem; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 1.05rem; transition: all 0.3s ease; display: inline-block; }
        .btn-primary { background: var(--secondary); color: var(--text-dark); box-shadow: 0 6px 25px rgba(245,158,11,0.4); }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(245,158,11,0.5); }
        .btn-outline { background: rgba(255,255,255,0.2); color: white; backdrop-filter: blur(10px); border: 2px solid rgba(255,255,255,0.3); }
        .btn-outline:hover { background: rgba(255,255,255,0.3); }
        .footer { background: var(--text-dark); color: white; padding: 4rem 2rem 2rem; }
        .footer-content { max-width: 1400px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 3rem; margin-bottom: 3rem; }
        .footer-section h3 { font-family: 'Sora', sans-serif; font-size: 1.3rem; margin-bottom: 1.5rem; }
        .footer-section p, .footer-section a { color: rgba(255,255,255,0.7); text-decoration: none; display: block; margin-bottom: 0.8rem; transition: color 0.3s; }
        .footer-section a:hover { color: var(--secondary); }
        .social-links { display: flex; gap: 1rem; margin-top: 1rem; }
        .social-link { width: 40px; height: 40px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; object-fit: cover; }
        .social-link:hover { background: var(--primary); transform: translateY(-3px); }
        .footer-bottom { max-width: 1400px; margin: 0 auto; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); text-align: center; color: rgba(255,255,255,0.5); }
        .fade-in { opacity: 0; transform: translateY(30px); transition: opacity 0.8s ease, transform 0.8s ease; }
        .fade-in.visible { opacity: 1; transform: translateY(0); }
        @media (max-width: 1024px) { .story-grid { grid-template-columns: 1fr; gap: 3rem; } .story-badge { right: 20px; bottom: -20px; width: 130px; height: 130px; } .story-badge .badge-num { font-size: 2.4rem; } .mv-grid { grid-template-columns: 1fr; } .values-grid { grid-template-columns: repeat(2, 1fr); } .about-hero h1 { font-size: 2.8rem; } }
        @media (max-width: 768px) { .about-hero h1 { font-size: 2.2rem; } .section-title { font-size: 2rem; } .values-grid { grid-template-columns: 1fr; } .cta-box h2 { font-size: 2rem; } .cta-btns { flex-direction: column; align-items: center; } }
    </style>
</head>
<body>

<header class="header" id="header">
    <nav class="nav-container">
        <a href="index.php" class="logo">
            <img src="images/logo.jpeg" alt="Jettransfer Logo" class="logo-img">
            Jettransfer
        </a>
        <ul class="nav-menu" id="navMenu">
            <li><a href="index.php">Home</a></li>
            <li><a href="destination.php">Destinations</a></li>
            <li><a href="packages.php">Packages</a></li>
            <li><a href="vehicles.html">Vehicles</a></li>
            <li><a href="service.php">Services &amp; Gallery</a></li>
            <li><a href="contact.php">Contact Us</a></li>
            <li><a href="aboutus.php" class="active">About Us</a></li>
            <li class="mobile-auth">
                <div class="mobile-auth-btns">
                    <a href="login.php" style="display:block;padding:0.6rem 1rem;border-radius:50px;border:2px solid var(--primary);color:var(--primary);font-weight:600;font-size:0.9rem;text-decoration:none;text-align:center">Login</a>
                    <a href="register.php" style="display:block;padding:0.6rem 1rem;border-radius:50px;background:var(--primary);color:white;font-weight:600;font-size:0.9rem;text-decoration:none;text-align:center">Register</a>
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

<section class="about-hero">
    <img src="about.jpg" alt="Sri Lanka" class="about-hero-img">
    <div class="about-hero-overlay"></div>
    <div class="hero-shape"></div>
    <div class="hero-shape"></div>
    <div class="hero-shape"></div>
    <div class="about-hero-content">
        <h1>Our Story &amp;<br>Our Passion</h1>
        <p>Driven by a love for Sri Lanka, dedicated to giving you the journey of a lifetime.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="story-grid">
            <div class="story-image fade-in">
                <div class="story-img-main">
                    <img src="images/full.jpg" alt="Jettransfer journey through Sri Lanka">
                </div>
                <div class="story-badge">
                    <span class="badge-num">6+</span>
                    <span class="badge-label">Years of Service</span>
                </div>
            </div>
            <div class="story-text fade-in">
                <span class="section-tag">Who We Are</span>
                <h2>Born in Sri Lanka, <span>Built for Travellers</span></h2>
                <p>Jettransfer Travels was founded with a simple belief — every traveller deserves to experience Sri Lanka the way locals know it. From the misty highlands of Ella to the golden beaches of the south, we have spent over a decade crafting journeys that are as comfortable as they are memorable.</p>
                <p>Our team of passionate drivers, planners, and guides share one common goal: to show you the real heart of this beautiful island.</p>
                <p>We are not just a transfer company — we are your companion on the road, your local guide, and your safety net, every step of the way.</p>
            </div>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-header fade-in">
            <span class="section-tag">Purpose</span>
            <h2 class="section-title">Mission &amp; Vision</h2>
            <p class="section-description">The principles that guide every journey we take with you</p>
        </div>
        <div class="mv-grid">
            <div class="mv-card mission fade-in">
                <div class="mv-icon">🎯</div>
                <h3>Our Mission</h3>
                <p>To provide safe, comfortable, and affordable travel experiences across Sri Lanka — connecting people with the island's rich culture, breathtaking landscapes, and warm hospitality. We believe that the journey itself should be as rewarding as the destination, and we work tirelessly to make every kilometre count.</p>
            </div>
            <div class="mv-card vision fade-in">
                <div class="mv-icon">🌟</div>
                <h3>Our Vision</h3>
                <p>To become Sri Lanka's most loved and trusted travel partner — known not just for our vehicles and routes, but for the genuine connections we build between travellers and this incredible island. We envision a future where every visitor leaves Sri Lanka with a story worth telling for a lifetime.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header fade-in">
            <span class="section-tag">What We Stand For</span>
            <h2 class="section-title">Our Core Values</h2>
            <p class="section-description">The promises we make — and keep — with every single traveller</p>
        </div>
        <div class="values-grid">
            <div class="value-card fade-in"><div class="value-icon">🛡️</div><h3>Safety First</h3><p>Your safety is our top priority. All our vehicles are regularly maintained, and our drivers are fully licensed and trained to the highest standards.</p></div>
            <div class="value-card fade-in"><div class="value-icon">💎</div><h3>Quality Service</h3><p>From spotless interiors to punctual pickups, we take pride in delivering a premium experience at every touchpoint of your journey.</p></div>
            <div class="value-card fade-in"><div class="value-icon">🤝</div><h3>Honesty &amp; Trust</h3><p>No hidden fees, no surprises. We believe in transparent pricing and honest communication — always.</p></div>
            <div class="value-card fade-in"><div class="value-icon">🌿</div><h3>Responsible Travel</h3><p>We respect Sri Lanka's natural environment and local communities, encouraging sustainable and mindful tourism practices.</p></div>
            <div class="value-card fade-in"><div class="value-icon">❤️</div><h3>Genuine Hospitality</h3><p>Rooted in Sri Lankan warmth, we treat every traveller like a guest in our own home — with care, kindness, and a smile.</p></div>
            <div class="value-card fade-in"><div class="value-icon">🔄</div><h3>Continuous Improvement</h3><p>We listen to your feedback and constantly evolve our services to exceed your expectations on every visit.</p></div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="cta-box fade-in">
        <h2>Ready to Explore Sri Lanka?</h2>
        <p>Let us take you on the journey of a lifetime. Browse our packages or get in touch to plan your perfect trip.</p>
        <div class="cta-btns">
            <a href="packages.php" class="btn btn-primary">View Packages</a>
            <a href="contact.php" class="btn btn-outline">Contact Us</a>
        </div>
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
            <a href="aboutus.php">About Us</a>
            <a href="terms.php">Terms &amp; Conditions</a>
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
    window.addEventListener('scroll', () => document.getElementById('header').classList.toggle('scrolled', window.scrollY > 100));
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, i) => { if (entry.isIntersecting) setTimeout(() => entry.target.classList.add('visible'), i * 100); });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
    document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));
</script>
</body>
</html>