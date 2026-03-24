<?php
session_start();
if (!isset($_SESSION['jt_admin'])) {
    header('Location: login.php'); exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – Jettransfer Travels</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0A7EA4;
            --primary-dark: #065A7A;
            --primary-light: #E0F2FA;
            --secondary: #F59E0B;
            --accent: #10B981;
            --text-dark: #0F172A;
            --text-light: #94A3B8;
            --bg: #F1F5F9;
            --sidebar-bg: #0F172A;
            --border: #E2E8F0;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
            --sidebar-w: 260px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Manrope',sans-serif; background:var(--bg); color:var(--text-dark); min-height:100vh; display:flex; }

        /* SIDEBAR */
        .sidebar { width:var(--sidebar-w); background:var(--sidebar-bg); min-height:100vh; position:fixed; left:0; top:0; bottom:0; display:flex; flex-direction:column; z-index:100; transition:transform .3s ease; }
        .sidebar-brand { padding:1.8rem 1.5rem 1.5rem; border-bottom:1px solid rgba(255,255,255,.07); display:flex; align-items:center; gap:.8rem; }
        .sidebar-logo { width:42px; height:42px; background:linear-gradient(135deg,var(--primary),var(--accent)); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
        .sidebar-brand-text h2 { font-family:'Sora',sans-serif; font-size:1rem; font-weight:800; color:#fff; }
        .sidebar-brand-text span { font-size:.72rem; color:rgba(255,255,255,.4); letter-spacing:1.5px; text-transform:uppercase; }
        .sidebar-nav { flex:1; padding:1.2rem .8rem; overflow-y:auto; }
        .nav-label { font-size:.68rem; font-weight:700; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,.25); padding:.5rem .8rem; margin-top:.8rem; margin-bottom:.3rem; }
        .nav-item { display:flex; align-items:center; gap:.75rem; padding:.75rem .9rem; border-radius:12px; color:rgba(255,255,255,.55); text-decoration:none; font-size:.9rem; font-weight:500; transition:all .2s ease; margin-bottom:.2rem; }
        .nav-item:hover { background:rgba(255,255,255,.07); color:rgba(255,255,255,.9); }
        .nav-item.active { background:linear-gradient(135deg,rgba(10,126,164,.35),rgba(16,185,129,.2)); color:#fff; box-shadow:inset 0 0 0 1px rgba(10,126,164,.4); }
        .nav-icon { width:20px; height:20px; flex-shrink:0; }
        .sidebar-footer { padding:1rem .8rem 1.5rem; border-top:1px solid rgba(255,255,255,.07); }
        .admin-profile { display:flex; align-items:center; gap:.75rem; padding:.75rem .9rem; border-radius:12px; background:rgba(255,255,255,.05); margin-bottom:.5rem; }
        .admin-avatar { width:36px; height:36px; background:linear-gradient(135deg,var(--primary),var(--accent)); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:.9rem; font-weight:700; color:#fff; flex-shrink:0; }
        .admin-info h4 { font-size:.85rem; font-weight:600; color:#fff; }
        .admin-info p { font-size:.72rem; color:rgba(255,255,255,.4); }
        .btn-logout { display:flex; align-items:center; gap:.6rem; padding:.7rem .9rem; border-radius:12px; color:rgba(255,255,255,.5); font-size:.88rem; font-weight:500; cursor:pointer; transition:all .2s; border:none; background:none; width:100%; font-family:'Manrope',sans-serif; }
        .btn-logout:hover { background:rgba(239,68,68,.15); color:#FCA5A5; }

        /* MAIN */
        .main { margin-left:var(--sidebar-w); flex:1; display:flex; flex-direction:column; }
        .topbar { background:#fff; border-bottom:1px solid var(--border); padding:0 2rem; height:70px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:50; box-shadow:var(--shadow-sm); }
        .topbar-left { display:flex; align-items:center; gap:1rem; }
        .hamburger { display:none; background:none; border:none; cursor:pointer; padding:6px; color:var(--text-dark); }
        .page-title h1 { font-family:'Sora',sans-serif; font-size:1.2rem; font-weight:700; }
        .page-title p { font-size:.8rem; color:var(--text-light); }
        .topbar-avatar { width:40px; height:40px; background:linear-gradient(135deg,var(--primary),var(--accent)); border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:.9rem; }

        /* CONTENT */
        .content { padding:2rem; flex:1; }
        .welcome-banner { background:linear-gradient(135deg,var(--primary-dark) 0%,var(--primary) 50%,#0EA5C9 100%); border-radius:20px; padding:2rem 2.5rem; margin-bottom:2rem; display:flex; align-items:center; justify-content:space-between; position:relative; overflow:hidden; box-shadow:var(--shadow-md); }
        .welcome-banner::before { content:''; position:absolute; top:-40%; right:-5%; width:300px; height:300px; background:radial-gradient(circle,rgba(255,255,255,.1) 0%,transparent 70%); pointer-events:none; }
        .welcome-text h2 { font-family:'Sora',sans-serif; font-size:1.6rem; font-weight:800; color:#fff; margin-bottom:.3rem; }
        .welcome-text p { color:rgba(255,255,255,.75); font-size:.95rem; }
        .welcome-date { text-align:right; color:rgba(255,255,255,.7); font-size:.85rem; z-index:1; }
        .welcome-date strong { display:block; font-family:'Sora',sans-serif; font-size:1.1rem; color:#fff; font-weight:700; }
        .section-heading { font-family:'Sora',sans-serif; font-size:1.1rem; font-weight:700; color:var(--text-dark); margin-bottom:1.2rem; }
        .modules-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:1.5rem; }
        .module-card { background:#fff; border-radius:20px; padding:1.8rem; box-shadow:var(--shadow-sm); border:1px solid var(--border); transition:all .3s ease; text-decoration:none; color:inherit; display:flex; flex-direction:column; gap:1rem; animation:fadeUp .5s ease both; }
        .module-card:hover { transform:translateY(-6px); box-shadow:var(--shadow-md); border-color:var(--primary); }
        .module-card-top { display:flex; align-items:center; justify-content:space-between; }
        .module-icon { width:56px; height:56px; border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:1.6rem; }
        .module-arrow { width:36px; height:36px; border-radius:50%; background:var(--bg); display:flex; align-items:center; justify-content:center; color:var(--text-light); transition:all .3s; }
        .module-card:hover .module-arrow { background:var(--primary); color:#fff; }
        .module-card h3 { font-family:'Sora',sans-serif; font-size:1.1rem; font-weight:700; color:var(--text-dark); }
        .module-card p { font-size:.85rem; color:var(--text-light); line-height:1.5; }
        @keyframes fadeUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:99; }
        .sidebar-overlay.show { display:block; }

        @media(max-width:768px) {
            .sidebar { transform:translateX(-100%); }
            .sidebar.open { transform:translateX(0); }
            .main { margin-left:0; }
            .hamburger { display:flex; }
            .welcome-banner { flex-direction:column; gap:1rem; }
            .welcome-date { text-align:left; }
            .modules-grid { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo">✈️</div>
        <div class="sidebar-brand-text">
            <h2>Jettransfer</h2><span>Admin Panel</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-label">Main</div>
        <a href="index.php" class="nav-item active">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>
        <div class="nav-label">Modules</div>
        <a href="destinations.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Destinations
        </a>
        <a href="packages.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            Packages
        </a>
        <a href="vehicles.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            Vehicles
        </a>
        <a href="profiles.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profiles
        </a>
        <a href="bookings.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Bookings
        </a>
        <a href="services.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            Services & Gallery
        </a>
        <a href="contact.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Contact Us / Reviews
        </a>
        <div class="nav-label">Other</div>
        <a href="../index.php" class="nav-item" target="_blank">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            View Site
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-profile">
            <div class="admin-avatar">A</div>
            <div class="admin-info">
                <h4>Admin</h4>
                <p>admin@jettransfer.com</p>
            </div>
        </div>
        <a href="logout.php" class="btn-logout">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Logout
        </a>
    </div>
</aside>

<div class="main">
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger" onclick="toggleSidebar()">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="page-title">
                <h1>Dashboard</h1>
                <p>Jettransfer Admin Panel</p>
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-avatar">A</div>
        </div>
    </header>

    <div class="content">
        <div class="welcome-banner">
            <div class="welcome-text">
                <h2>Welcome back, Admin! 👋</h2>
                <p>Select a section below to manage your content.</p>
            </div>
            <div class="welcome-date">
                <strong id="currentDate">—</strong>
                <span id="currentTime">—</span>
            </div>
        </div>

        <p class="section-heading">Manage Sections</p>
        <div class="modules-grid">

            <a href="destinations.php" class="module-card" style="animation-delay:.1s">
                <div class="module-card-top">
                    <div class="module-icon" style="background:#D1FAE5">📍</div>
                    <div class="module-arrow"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>
                </div>
                <h3>Destinations</h3>
                <p>Add and manage tour destinations displayed on the website.</p>
            </a>

            <a href="packages.php" class="module-card" style="animation-delay:.2s">
                <div class="module-card-top">
                    <div class="module-icon" style="background:#FEF3C7">📦</div>
                    <div class="module-arrow"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>
                </div>
                <h3>Packages</h3>
                <p>Create and manage tour packages and pricing.</p>
            </a>

            <a href="vehicles.php" class="module-card" style="animation-delay:.3s">
                <div class="module-card-top">
                    <div class="module-icon" style="background:#E0F2FA">🚗</div>
                    <div class="module-arrow"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>
                </div>
                <h3>Vehicles</h3>
                <p>Manage fleet — update availability and condition status of all vehicles.</p>
            </a>

            <a href="profiles.php" class="module-card" style="animation-delay:.4s">
                <div class="module-card-top">
                    <div class="module-icon" style="background:#EDE9FE">👤</div>
                    <div class="module-arrow"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>
                </div>
                <h3>Profiles</h3>
                <p>Manage user profiles and account details.</p>
            </a>

            <a href="services.php" class="module-card" style="animation-delay:.5s">
                <div class="module-card-top">
                    <div class="module-icon" style="background:#FCE7F3">🖼️</div>
                    <div class="module-arrow"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>
                </div>
                <h3>Services & Gallery</h3>
                <p>Manage services offered and photos in the gallery section.</p>
            </a>

            <a href="contact.php" class="module-card" style="animation-delay:.6s">
                <div class="module-card-top">
                    <div class="module-icon" style="background:#CCFBF1">💬</div>
                    <div class="module-arrow"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></div>
                </div>
                <h3>Contact Us / Reviews</h3>
                <p>View contact messages and manage customer reviews.</p>
            </a>

        </div>
    </div>
</div>

<script>
function updateClock() {
    const now = new Date();
    document.getElementById('currentDate').textContent = now.toLocaleDateString('en-US', {weekday:'long',year:'numeric',month:'long',day:'numeric'});
    document.getElementById('currentTime').textContent = now.toLocaleTimeString('en-US', {hour:'2-digit',minute:'2-digit'});
}
updateClock();
setInterval(updateClock, 1000);

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
document.getElementById('sidebarOverlay').addEventListener('click', () => {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('show');
});
</script>
</body>
</html>