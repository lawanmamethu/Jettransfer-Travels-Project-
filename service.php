<?php
// Handle form submission FIRST before any connection is closed
$enquiry_success = false;
$enquiry_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_enquiry'])) {
    // Create a NEW connection for the form submission
    $conn_submit = new mysqli('localhost', 'root', '', 'jettransfer');
    
    if (!$conn_submit->connect_error) {
        $conn_submit->set_charset('utf8mb4');
        
        $service_name = $conn_submit->real_escape_string($_POST['service_name']);
        $customer_name = $conn_submit->real_escape_string($_POST['customer_name']);
        $customer_email = $conn_submit->real_escape_string($_POST['customer_email']);
        $customer_phone = $conn_submit->real_escape_string($_POST['customer_phone']);
        $travel_date = $conn_submit->real_escape_string($_POST['travel_date']);
        $travelers = (int)$_POST['travelers'];
        $message = $conn_submit->real_escape_string($_POST['message']);
        
        $sql = "INSERT INTO service_enquiries (service_name, customer_name, customer_email, customer_phone, travel_date, travelers, message, status, created_at) 
                VALUES ('$service_name', '$customer_name', '$customer_email', '$customer_phone', '$travel_date', $travelers, '$message', 'new', NOW())";
        
        if ($conn_submit->query($sql)) {
            $enquiry_success = true;
        } else {
            $enquiry_error = "Database error: " . $conn_submit->error;
        }
        $conn_submit->close();
    } else {
        $enquiry_error = "Connection error. Please try again.";
    }
}

// Now proceed with main page data fetching
$conn = new mysqli('localhost', 'root', '', 'jettransfer');
if (!$conn->connect_error) {
    $conn->set_charset('utf8mb4');

    // Fetch active services
    $services = [];
    $r = $conn->query("SELECT * FROM services WHERE is_active=1 ORDER BY sort_order ASC");
    if ($r) while ($row = $r->fetch_assoc()) $services[] = $row;

    // Fetch active gallery
    $gallery = [];
    $r = $conn->query("SELECT * FROM gallery WHERE is_active=1 ORDER BY sort_order ASC");
    if ($r) while ($row = $r->fetch_assoc()) $gallery[] = $row;

    $conn->close();
} else {
    // Fallback: empty arrays (page still loads without DB)
    $services = [];
    $gallery  = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services &amp; Gallery – Jettransfer Travels</title>
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
            --success: #10B981;
            --error: #EF4444;
        }
        * { 
            margin:0; 
            padding:0;
            box-sizing:border-box; 
        }
        body { 
            font-family:'Manrope',sans-serif; 
            color:var(--text-dark); 
            line-height:1.6; 
            overflow-x:hidden; 
        }
        body.modal-open {
            overflow: hidden;
        }

        /* HEADER */
        .header { 
            position:fixed; 
            top:0; 
            left:0; 
            right:0; 
            background:rgba(255,255,255,0.95); 
            backdrop-filter:blur(10px); 
            box-shadow:var(--shadow-sm); 
            z-index:1000; 
            transition:all 0.3s ease; 
        }
        .header.scrolled { 
            box-shadow:var(--shadow-md); 
        }
        .nav-container { 
            max-width:1400px; 
            margin:0 auto; 
            padding:1rem 2rem; 
            display:flex; 
            justify-content:space-between; 
            align-items:center; 
        }
        .logo { 
            font-family:'Sora',sans-serif; 
            font-size:1.3rem; 
            font-weight:800; 
            color:var(--primary); 
            text-decoration:none; 
            display:flex; 
            align-items:center; 
            gap:.6rem; 
            white-space:nowrap; 
        }
        .logo-img { 
            width:42px; 
            height:42px; 
            border-radius:12px; 
            object-fit:cover; 
            border:1px solid rgba(2,6,23,.10); 
            box-shadow:0 10px 22px rgba(10,126,164,.15); 
            background:#fff; 
        }
        .nav-menu { 
            display:flex; 
            list-style:none; 
            gap:1.6rem; 
            align-items:center; 
        }
        .nav-menu a { 
            color:var(--text-dark); 
            text-decoration:none; 
            font-weight:500; 
            font-size:0.88rem; 
            transition:color 0.3s ease; 
            position:relative; 
        }
        .nav-menu a::after { 
            content:''; 
            position:absolute; 
            bottom:-5px; left:0; 
            width:0; 
            height:2px; 
            background:var(--primary); 
            transition:width 0.3s ease; 
        }
        .nav-menu a:hover::after, .nav-menu a.active::after { 
            width:100%; 
        }
        .nav-menu a:hover, .nav-menu a.active { 
            color:var(--primary); 
        }
        .nav-actions { 
            display:flex; 
            align-items:center; 
            gap:0.6rem; 
            flex-shrink:0; 
        }
        .nav-search { 
            position:relative; 
            display:flex; 
            align-items:center; 
        }
        .search-toggle { 
            width:38px; 
            height:38px; 
            border-radius:50%; 
            border:2px solid #E2E8F0; 
            background:transparent; 
            display:flex; 
            align-items:center; 
            justify-content:center; 
            cursor:pointer; 
            color:var(--text-dark); 
            transition:all 0.3s ease; 
        }
        .search-toggle:hover { 
            border-color:var(--primary); 
            color:var(--primary); 
            background:var(--bg-light); 
        }
        .search-box { 
            position:absolute; 
            right:0; top:50%; 
            transform:translateY(-50%); 
            display:flex; 
            align-items:center; 
            background:white; 
            border:2px solid var(--primary); 
            border-radius:50px; 
            overflow:hidden; 
            width:0; opacity:0; 
            pointer-events:none; 
            transition:width 0.4s ease, opacity 0.3s ease; 
            box-shadow:var(--shadow-md); 
            z-index:10; 
        }
        .search-box.open { 
            width:280px; 
            opacity:1; 
            pointer-events:all; 
        }
        .search-box input { 
            border:none; 
            outline:none; 
            padding:0.55rem 1rem; 
            font-family:'Manrope',sans-serif; 
            font-size:0.88rem; 
            color:var(--text-dark); 
            width:100%; 
            background:transparent; 
        }
        .search-submit { 
            background:var(--primary); 
            border:none; 
            padding:0.55rem 1rem; 
            color:white; 
            cursor:pointer; 
            display:flex; 
            align-items:center; 
            justify-content:center; 
            transition:background 0.3s; 
            flex-shrink:0; 
        }
        .search-submit:hover { 
            background:var(--primary-dark); 
        }
        .nav-auth { 
            display:flex; 
            align-items:center; 
            gap:0.4rem; 
        }
        .btn-login { 
            padding:0.5rem 1.2rem; 
            border-radius:50px; 
            text-decoration:none; 
            font-weight:600; 
            font-size:0.88rem; 
            color:var(--primary); 
            border:2px solid var(--primary); 
            transition:all 0.3s ease; 
            white-space:nowrap; 
        }
        .btn-login:hover { 
            background:var(--primary); 
            color:white; 
            transform:translateY(-2px); 
        }
        .btn-register { 
            padding:0.5rem 1.2rem; 
            border-radius:50px; 
            text-decoration:none; 
            font-weight:600; 
            font-size:0.88rem; 
            background:var(--primary); 
            color:white; 
            border:2px solid var(--primary); 
            transition:all 0.3s ease; 
            box-shadow:0 4px 12px rgba(10,126,164,0.3); 
            white-space:nowrap; 
        }
        .btn-register:hover { 
            background:var(--primary-dark); 
            border-color:var(--primary-dark); 
            transform:translateY(-2px); 
        }
        .mobile-toggle { 
            display:none; 
            flex-direction:column; 
            gap:5px; 
            cursor:pointer; 
            padding:8px; 
        }
        .mobile-toggle span { 
            width:25px; 
            height:3px; 
            background:var(--text-dark); 
            transition:all 0.3s ease; 
            border-radius:2px; 
        }
        .mobile-auth { 
            display:none; 
        }

        /* HERO */
        .hero { 
            margin-top:80px; 
            height:50vh; 
            min-height:340px; 
            background:linear-gradient(135deg,rgba(10,126,164,.93),rgba(16,185,129,.82)); 
            display:flex; 
            align-items:center; 
            position:relative; 
            overflow:hidden; 
        }
        .hero::before { 
            content:''; 
            position:absolute; 
            inset:0; 
            background:radial-gradient(circle at 15% 50%,rgba(255,255,255,.1) 0%,transparent 55%),radial-gradient(circle at 85% 30%,rgba(245,158,11,.18) 0%,transparent 50%); 
            animation:bgpulse 8s ease-in-out infinite; 
        }
        @keyframes bgpulse { 0%,100%{opacity:.5}50%{opacity:1} }
        .hero-circle { 
            position:absolute; 
            border-radius:50%; 
            opacity:.11; 
            background:white; 
        }
        .hero-circle.c1 { 
            width:300px; 
            height:300px; 
            top:-70px; 
            right:60px; 
        }
        .hero-circle.c2 { 
            width:170px; 
            height:170px; 
            bottom:-50px; 
            right:300px; 
        }
        .hero-circle.c3 { 
            width:110px; 
            height:110px; 
            top:40px; 
            left:38%; 
        }
        .hero-content { 
            max-width:1400px; 
            margin:0 auto; 
            padding:0 2rem; 
            color:white; 
            z-index:1; 
            animation:fadeInUp .9s ease-out both; 
        }
        .hero h1 { 
            font-family:'Sora',sans-serif; 
            font-size:3.2rem; 
            font-weight:800; 
            line-height:1.1; 
            margin-bottom:1rem; 
        }
        .hero-subtitle { 
            font-size:1.15rem; 
            opacity:.92; 
            max-width:560px; 
            font-weight:400; 
        }
        @keyframes fadeInUp { from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)} }

        /* SECTIONS */
        .section { 
            padding:5.5rem 2rem; 
        }
        .section-alt { 
            background:var(--bg-light); 
        }
        .container { 
            max-width:1400px; 
            margin:0 auto; 
        }
        .section-header { 
            text-align:center; 
            margin-bottom:3.5rem; 
        }
        .section-title { 
            font-family:'Sora',sans-serif; 
            font-size:2.6rem; 
            font-weight:700; 
            color:var(--text-dark); 
            margin-bottom:.8rem; 
        }
        .section-description { 
            color:var(--text-light); 
            font-size:1.05rem; 
            max-width:680px; 
            margin:0 auto; 
        }

        /* SERVICES GRID */
        .services-grid { 
            display:grid; 
            grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); 
            gap:2rem; 
        }
        .srv-card { 
            background:white; 
            border-radius:18px; 
            padding:2.2rem 2rem; 
            box-shadow:var(--shadow-md); 
            transition:transform .38s ease,box-shadow .38s ease,border-color .3s; 
            border:2px solid transparent; 
            display:flex; 
            flex-direction:column; 
            gap:1rem; 
            cursor:pointer; 
            position:relative;
        }
        .srv-card:hover { 
            transform:translateY(-10px); 
            box-shadow:var(--shadow-lg); 
            border-color:rgba(10,126,164,.15); 
        }
        .popular-badge {
            position:absolute;
            top:1rem;
            right:1rem;
            background:var(--secondary);
            color:white;
            padding:0.25rem 0.8rem;
            border-radius:50px;
            font-size:0.7rem;
            font-weight:700;
            z-index:1;
        }
        .srv-icon { 
            width:70px; 
            height:70px; 
            background:#E0F2FA; 
            border-radius:18px; 
            display:flex; 
            align-items:center; 
            justify-content:center; 
            font-size:1.9rem; 
            transition:background .3s,transform .3s; 
            flex-shrink:0; 
        }
        .srv-card:hover .srv-icon { 
            background:var(--primary); 
            transform:rotate(-5deg) scale(1.08); 
        }
        .srv-title { 
            font-family:'Sora',sans-serif; 
            font-size:1.2rem;
            font-weight:700; 
            color:var(--text-dark); 
        }
        .srv-desc { 
            color:var(--text-light); 
            font-size:.9rem; 
            line-height:1.7; 
            flex:1; 
        }
        .btn-learn { 
            display:inline-flex; 
            align-items:center; 
            gap:.4rem; 
            color:var(--primary); 
            font-weight:700; 
            font-size:.9rem; 
            text-decoration:none; 
            border:2px solid var(--primary); 
            padding:.55rem 1.4rem; 
            border-radius:50px; 
            transition:all .28s; 
            align-self:flex-start; 
            cursor:pointer; 
            background:transparent; 
            font-family:inherit; 
        }
        .btn-learn:hover { 
            background:var(--primary); 
            color:white; 
            transform:translateX(3px); 
        }
        .btn-book {
            background:var(--secondary);
            color:white;
            border:none;
            padding:0.6rem 1rem;
            border-radius:50px;
            margin-top:0.5rem;
            cursor:pointer;
            font-weight:600;
            font-size:0.85rem;
            transition:all .28s;
            width:100%;
        }
        .btn-book:hover {
            background:#e68a00;
            transform:translateY(-2px);
        }

        /* PROCESS STRIP */
        .process-strip { 
            background:linear-gradient(135deg,var(--primary),var(--primary-dark)); 
            padding:3.5rem 2rem; 
            color:white; 
        }
        .process-strip .container { 
            display:flex; 
            justify-content:space-around; 
            flex-wrap:wrap; gap:2rem; 
            text-align:center; 
        }
        .proc-item { 
            flex:1; 
            min-width:140px; 
        }
        .proc-num { 
            font-family:'Sora',sans-serif; 
            font-size:2.4rem; 
            font-weight:800; 
            opacity:.35; 
            line-height:1; 
            margin-bottom:.3rem; 
        }
        .proc-item h4 { 
            font-family:'Sora',sans-serif; 
            font-size:1rem; 
            font-weight:700; 
            margin-bottom:.3rem; 
        }
        .proc-item p { 
            font-size:.85rem; 
            opacity:.82; 
        }

        /* GALLERY FILTERS */
        .gallery-filters { 
            display:flex; 
            flex-wrap:wrap; 
            gap:.7rem; 
            justify-content:center; 
            margin-bottom:2.5rem; 
        }
        .gal-btn { 
            padding:.55rem 1.4rem; 
            border-radius:50px; 
            border:2px solid #E2E8F0; 
            background:transparent; 
            font-family:inherit; 
            font-size:.88rem; 
            font-weight:600; 
            color:var(--text-light); 
            cursor:pointer; 
            transition:all .25s; 
        }
        .gal-btn:hover, .gal-btn.active { 
            background:var(--primary); 
            border-color:var(--primary); 
            color:white; 
        }

        /* GALLERY GRID */
        .gallery-grid { 
            display:grid; 
            grid-template-columns:repeat(4,1fr); 
            grid-auto-rows:200px; 
            gap:1rem; 
        }
        .gal-item { 
            position:relative;
            border-radius:14px; 
            overflow:hidden; 
            cursor:pointer; 
            box-shadow:var(--shadow-sm); 
            transition:opacity .4s,transform .4s; 
        }
        .gal-item.tall { 
            grid-row:span 2; 
        }
        .gal-item.wide { 
            grid-column:span 2; 
        }
        .gal-item img { 
            width:100%; 
            height:100%; 
            object-fit:cover; 
            transition:transform .5s ease; 
            display:block; 
        }
        .gal-item:hover img { 
            transform:scale(1.08); 
        }
        .gal-overlay { 
            position:absolute; 
            inset:0; 
            background:linear-gradient(to top,rgba(6,90,122,.75) 0%,transparent 55%); 
            opacity:0; 
            transition:opacity .35s; 
            display:flex; 
            flex-direction:column; 
            justify-content:flex-end; 
            padding:1.2rem; 
        }
        .gal-item:hover .gal-overlay { 
            opacity:1; 
        }
        .gal-overlay h4 { 
            font-family:'Sora',sans-serif; 
            font-size:1rem; 
            font-weight:700; 
            color:white; 
            margin-bottom:.2rem; 
        }
        .gal-overlay span { 
            font-size:.78rem; 
            color:rgba(255,255,255,.8); 
            background:rgba(10,126,164,.5); 
            padding:.18rem .65rem; 
            border-radius:50px; 
            display:inline-block; 
        }
        .gal-zoom-icon { 
            position:absolute; 
            top:50%; left:50%; 
            transform:translate(-50%,-50%) scale(0); 
            width:48px; height:48px; 
            background:rgba(255,255,255,.92); 
            border-radius:50%; 
            display:flex; 
            align-items:center; 
            justify-content:center; 
            font-size:1.4rem; 
            transition:transform .3s ease; 
            color:var(--primary); 
        }
        .gal-item:hover .gal-zoom-icon { 
            transform:translate(-50%,-50%) scale(1); 
        }
        .gal-item.hidden { 
            opacity:0; 
            pointer-events:none; 
            transform:scale(.95); 
        }

        /* LIGHTBOX */
        .lightbox { 
            display:none; 
            position:fixed; 
            inset:0; 
            background:rgba(10,23,40,.92); 
            z-index:9999; 
            align-items:center; 
            justify-content:center; 
        }
        .lightbox.open { 
            display:flex; 
        }
        .lb-inner { 
            position:relative; 
            max-width:900px; 
            width:92%; 
            animation:fadeInUp .35s ease both; 
        }
        .lb-img { 
            width:100%; 
            max-height:80vh; 
            object-fit:contain; 
            border-radius:14px; 
            box-shadow:0 20px 60px rgba(0,0,0,.5); 
        }
        .lb-caption { 
            color:white; 
            text-align:center; 
            margin-top:1rem; 
            font-size:1rem; 
            font-weight:600;
        }
        .lb-close { 
            position:absolute; 
            top:-2.5rem; 
            right:0; 
            background:none; 
            border:none; 
            color:white; 
            font-size:1.8rem; 
            cursor:pointer; 
            line-height:1; 
            opacity:.85; 
            transition:opacity .2s; 
        }
        .lb-close:hover { 
            opacity:1; 
        }
        .lb-nav { 
            position:absolute; 
            top:50%; 
            transform:translateY(-50%); 
            background:rgba(255,255,255,.15); 
            border:none; 
            color:white; 
            width:44px; 
            height:44px; 
            border-radius:50%; 
            font-size:1.3rem; 
            cursor:pointer; 
            transition:background .2s; 
            display:flex; 
            align-items:center; 
            justify-content:center; 
        }
        .lb-nav:hover { 
            background:rgba(255,255,255,.3); 
        }
        .lb-prev { 
            left:-3.5rem; 
        }
        .lb-next { 
            right:-3.5rem; 
        }

        /* SERVICE MODAL WITH ENQUIRY FORM */
        .svc-modal { 
            display:none; 
            position:fixed; 
            inset:0; 
            background:rgba(15,23,42,.85); 
            z-index:9998; 
            align-items:center; 
            justify-content:center; 
            backdrop-filter:blur(4px);
        }
        .svc-modal.open { 
            display:flex; 
        }
        .svc-modal-inner { 
            background:white; 
            border-radius:20px; 
            padding:2.5rem; 
            max-width:550px; 
            width:92%; 
            max-height:90vh;
            overflow-y:auto;
            box-shadow:0 20px 60px rgba(0,0,0,.3); 
            position:relative; 
            animation:fadeInUp .35s ease both; 
        }
        .svc-modal-close { 
            position:absolute; 
            top:1rem; 
            right:1.2rem; 
            background:none; 
            border:none; 
            font-size:1.5rem; 
            cursor:pointer; 
            color:#64748B; 
            line-height:1; 
            transition:color .3s;
        }
        .svc-modal-close:hover {
            color:var(--error);
        }
        .svc-modal-icon { 
            width:56px; 
            height:56px; 
            background:#E0F4FB; 
            border-radius:14px; 
            display:flex; 
            align-items:center; 
            justify-content:center; 
            font-size:1.6rem; 
            margin-bottom:1rem; 
        }
        .svc-modal-title { 
            font-family:'Sora',sans-serif; 
            color:var(--primary); 
            margin-bottom:0.5rem; 
            font-size:1.5rem; 
        }
        .svc-modal-desc { 
            color:#64748B; 
            line-height:1.75; 
            font-size:.95rem; 
            margin-bottom:1.5rem;
            padding-bottom:1rem;
            border-bottom:1px solid #E2E8F0;
        }
        
        /* Enquiry Form Styles */
        .enquiry-toggle {
            background:var(--primary);
            color:white;
            border:none;
            padding:0.75rem 1.5rem;
            border-radius:50px;
            font-weight:700;
            cursor:pointer;
            width:100%;
            margin-bottom:1rem;
            transition:all .3s;
        }
        .enquiry-toggle:hover {
            background:var(--primary-dark);
            transform:translateY(-2px);
        }
        .enquiry-form {
            display:none;
            margin-top:1rem;
            animation:fadeInUp .4s ease;
        }
        .enquiry-form.show {
            display:block;
        }
        .form-group {
            margin-bottom:1rem;
        }
        .form-group label {
            display:block;
            margin-bottom:0.4rem;
            font-weight:600;
            font-size:0.85rem;
            color:var(--text-dark);
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width:100%;
            padding:0.75rem;
            border:2px solid #E2E8F0;
            border-radius:12px;
            font-family:'Manrope',sans-serif;
            font-size:0.9rem;
            transition:border-color .3s;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline:none;
            border-color:var(--primary);
        }
        .form-row {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:1rem;
        }
        .btn-submit-enquiry {
            background:var(--success);
            color:white;
            border:none;
            padding:0.85rem;
            border-radius:50px;
            font-weight:700;
            font-size:1rem;
            cursor:pointer;
            width:100%;
            transition:all .3s;
        }
        .btn-submit-enquiry:hover {
            background:#0d9668;
            transform:translateY(-2px);
        }
        .success-message {
            background:#D1FAE5;
            color:#065F46;
            padding:1rem;
            border-radius:12px;
            text-align:center;
            margin-top:1rem;
        }
        .error-message {
            background:#FEE2E2;
            color:#991B1B;
            padding:0.75rem;
            border-radius:12px;
            text-align:center;
            margin-bottom:1rem;
            font-size:0.85rem;
        }
        .svc-modal-actions {
            display:flex;
            gap:1rem;
            margin-top:1rem;
        }
        .btn-enquire {
            background:var(--primary);
            color:white;
            padding:.75rem 1.8rem;
            border-radius:50px;
            text-decoration:none;
            font-weight:700;
            font-size:.9rem;
            transition:background .3s;
            box-shadow:0 4px 15px rgba(10,126,164,.3);
            border:none;
            cursor:pointer;
            flex:1;
            text-align:center;
        }
        .btn-enquire:hover {
            background:var(--primary-dark);
        }
        .btn-close-modal {
            background:none;
            border:2px solid #E2E8F0;
            color:#64748B;
            padding:.75rem 1.8rem;
            border-radius:50px;
            font-family:inherit;
            font-weight:600;
            font-size:.9rem;
            cursor:pointer;
            transition:all .3s;
            flex:1;
        }
        .btn-close-modal:hover {
            border-color:var(--primary);
            color:var(--primary);
        }

        /* CTA STRIP */
        .cta-strip { 
            background:var(--bg-light); 
            padding:4rem 2rem; 
            text-align:center; 
        }
        .btn-cta { 
            background:var(--primary); 
            color:white; 
            padding:.9rem 2.5rem; 
            border-radius:50px; 
            text-decoration:none; 
            font-weight:700; 
            font-size:1rem; 
            transition:all .3s; 
            box-shadow:0 4px 20px rgba(10,126,164,.35); 
            display:inline-block; 
        }
        .btn-cta:hover { 
            background:var(--primary-dark); 
            transform:translateY(-3px); 
        }

        /* FOOTER */
        .footer { 
            background:var(--text-dark); 
            color:white; 
            padding:4rem 2rem 2rem; 
        }
        .footer-content { 
            max-width:1400px; 
            margin:0 auto; 
            display:grid; 
            grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); 
            gap:3rem; 
            margin-bottom:3rem; 
        }
        .footer-section h3 { 
            font-family:'Sora',sans-serif; 
            font-size:1.15rem; 
            margin-bottom:1.3rem; 
        }
        .footer-section p, .footer-section a { 
            color:rgba(255,255,255,.68); 
            text-decoration:none; 
            display:block; 
            margin-bottom:.65rem; 
            font-size:.91rem; 
            transition:color .3s; 
        }
        .footer-section a:hover { 
            color:var(--secondary); 
        }
        .social-links { 
            display:flex; 
            gap:.8rem; 
            margin-top:1rem; 
        }
        .social-link { 
            width:38px; 
            height:38px; 
            background:rgba(255,255,255,.1); 
            border-radius:50%; 
            display:flex; 
            align-items:center; 
            justify-content:center; 
            transition:all .3s; 
        }
        .social-link:hover { 
            background:var(--primary); 
            transform:translateY(-3px); 
        }
        .footer-bottom { 
            max-width:1400px; 
            margin:0 auto; 
            padding-top:2rem; 
            border-top:1px solid rgba(255,255,255,.1); 
            text-align:center; 
            color:rgba(255,255,255,.42); 
            font-size:.86rem; 
        }

        /* FADE IN */
        .fade-in { 
            opacity:0; 
            transform:translateY(26px); 
            transition:opacity .75s ease,transform .75s ease; 
        }
        .fade-in.visible { 
            opacity:1; 
            transform:translateY(0); 
        }

        /* RESPONSIVE */
        @media(max-width:1100px){ 
            .gallery-grid{ 
                grid-template-columns:repeat(3,1fr); 
            } 
        }
        @media(max-width:768px){
            .mobile-toggle{ 
                display:flex; 
            }
            .nav-actions{ 
                display:none; 
            }
            .nav-menu{ 
                position:fixed; 
                top:80px; 
                left:0; 
                right:0; 
                background:white; 
                flex-direction:column; 
                padding:2rem; 
                gap:1.5rem; 
                box-shadow:var(--shadow-lg); 
                transform:translateX(-100%); 
                transition:transform .3s; 
                z-index:999; 
            }
            .nav-menu.active{ 
                transform:translateX(0); 
            }
            .mobile-auth{ 
                display:flex !important; 
                flex-direction:column; 
                align-items:center; 
                gap:.8rem; 
                padding-top:1.2rem; 
                border-top:1px solid #E2E8F0; 
                width:100%; 
            }
            .hero{ 
                height:auto; 
                padding:4.5rem 0; 
                min-height:unset; 
            }
            .hero h1{ 
                font-size:2rem; 
            }
            .section-title{ 
                font-size:1.9rem; 
            }
            .gallery-grid{ 
                grid-template-columns:repeat(2,1fr); 
                grid-auto-rows:160px; 
            }
            .gal-item.wide{ 
                grid-column:span 2; 
            }
            .services-grid{ 
                grid-template-columns:1fr; 
            }
            .lb-prev{ 
                left:-2.5rem; 
            }
            .lb-next{ 
                right:-2.5rem; 
            }
            .form-row {
                grid-template-columns:1fr;
                gap:0;
            }
        }
        @media(max-width:480px){ 
            .gallery-grid{ 
                grid-template-columns:1fr 1fr; 
                grid-auto-rows:130px; 
            } 
            .gal-item.wide{ 
                grid-column:span 2; 
            } 
            .lb-prev,.lb-next{ 
                display:none; 
            } 
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="header" id="header">
    <nav class="nav-container">
        <a href="#" class="logo">
            <img src="images/logo.jpeg" alt="Jettransfer Logo" class="logo-img">
            Jettransfer
        </a>
        <ul class="nav-menu" id="navMenu">
            <li><a href="index.php">Home</a></li>
            <li><a href="destination.php">Destinations</a></li>
            <li><a href="packages.php">Packages</a></li>
            <li><a href="vehicles.html">Vehicles</a></li>
            <li><a href="service.php" class="active">Services &amp; Gallery</a></li>
            <li><a href="contact.php">Contact Us</a></li>
            <li><a href="aboutus.php">About Us</a></li>
            <li class="mobile-auth">
                <div style="display:flex;gap:.8rem;width:100%">
                    <a href="login.php" class="btn-login" style="flex:1;text-align:center;display:block;padding:.6rem 1rem">Login</a>
                    <a href="register.php" class="btn-register" style="flex:1;text-align:center;display:block;padding:.6rem 1rem">Register</a>
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
<section class="hero">
    <div class="hero-circle c1"></div>
    <div class="hero-circle c2"></div>
    <div class="hero-circle c3"></div>
    <div class="hero-content">
        <h1>Services &amp; Gallery</h1>
        <p class="hero-subtitle">World-class travel services and a visual journey through the Pearl of the Indian Ocean.</p>
    </div>
</section>

<!-- SERVICES SECTION -->
<section class="section" id="services">
    <div class="container">
        <div class="section-header fade-in">
            <h2 class="section-title">Our Story Of Tourism Industry</h2>
            <p class="section-description">Welcome to our journey in the tourism industry, a story driven by passion, innovation, and a deep respect for cultural and natural heritage. Founded by a group of enthusiastic young professionals, our company is dedicated to redefining travel experiences with fresh perspectives and sustainable practices.

Our story began with a shared dream: to create travel experiences that are not only enjoyable but also meaningful. We believe that travel should be more than just sightseeing; it should be an opportunity to connect with different cultures, explore natural wonders, and contribute positively to the world.

Sustainability is a core principle of our operations. We prioritize eco-friendly practices, from minimizing waste and carbon footprints to supporting conservation efforts.</p>
        </div>
        <div class="section-header fade-in">
            <h2 class="section-title">Our Services</h2>
        </div>

        <div class="services-grid">
        <?php if (empty($services)): ?>
            <p style="text-align:center;color:var(--text-light);grid-column:1/-1">No services available yet.</p>
        <?php else: ?>
        <?php foreach ($services as $i => $s): ?>
            <div class="srv-card fade-in" style="animation-delay:<?= ($i * 0.1) ?>s">
                <?php if(isset($s['is_popular']) && $s['is_popular']): ?>
                    <span class="popular-badge">🔥 Popular</span>
                <?php endif; ?>
                <div class="srv-icon"><?= htmlspecialchars($s['icon']) ?></div>
                <h3 class="srv-title"><?= htmlspecialchars($s['title']) ?></h3>
                <p class="srv-desc"><?= htmlspecialchars($s['description']) ?></p>
                <button class="btn-learn"
                        onclick="openServiceModal(
                            '<?= addslashes(htmlspecialchars($s['title'])) ?>',
                            '<?= addslashes(htmlspecialchars($s['modal_text'] ?: $s['description'])) ?>',
                            '<?= addslashes(htmlspecialchars($s['icon'])) ?>'
                        )">Learn More →</button>
                <button class="btn-book" onclick="openServiceModalWithForm('<?= addslashes(htmlspecialchars($s['title'])) ?>', '<?= addslashes(htmlspecialchars($s['modal_text'] ?: $s['description'])) ?>', '<?= addslashes(htmlspecialchars($s['icon'])) ?>')">
                    📅 Quick Enquiry
                </button>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </div>
</section>

<!-- PROCESS STRIP -->
<div class="process-strip">
    <div class="container">
        <div class="proc-item"><div class="proc-num">01</div><h4>Choose Your Service</h4><p>Browse packages or tell us what you need</p></div>
        <div class="proc-item"><div class="proc-num">02</div><h4>Get a Custom Quote</h4><p>We tailor the price to your exact needs</p></div>
        <div class="proc-item"><div class="proc-num">03</div><h4>Confirm &amp; Book</h4><p>Secure your booking with a simple deposit</p></div>
        <div class="proc-item"><div class="proc-num">04</div><h4>Travel &amp; Enjoy</h4><p>We handle everything — you just explore</p></div>
    </div>
</div>

<!-- GALLERY SECTION -->
<section class="section section-alt" id="gallery">
    <div class="container">
        <div class="section-header fade-in">
            <h2 class="section-title">The Unforgettable Tour Gallery</h2>
            <p class="section-description">Explore our Tour Gallery to get a glimpse of the breathtaking destinations and unforgettable experiences we offer. From stunning landscapes to vibrant cityscapes, our gallery showcases the highlights of our tours.</p>
        </div>

        <!-- Category Filters -->
        <div class="gallery-filters fade-in">
            <button class="gal-btn active" data-cat="all">All</button>
            <button class="gal-btn" data-cat="beaches">🌊 Beaches</button>
            <button class="gal-btn" data-cat="hillcountry">🌿 Hill Country</button>
            <button class="gal-btn" data-cat="cultural">🏛 Cultural Sites</button>
            <button class="gal-btn" data-cat="wildlife">🐘 Wildlife</button>
            <button class="gal-btn" data-cat="welcome">🙏 Welcome</button>
        </div>

        <!-- Gallery Grid -->
        <div class="gallery-grid fade-in" id="galleryGrid">
        <?php if (empty($gallery)): ?>
            <p style="grid-column:1/-1;text-align:center;color:var(--text-light)">No gallery photos yet.</p>
        <?php else: ?>
        <?php foreach ($gallery as $g): ?>
            <div class="gal-item <?= htmlspecialchars($g['css_class'] ?? '') ?>"
                 data-cat="<?= htmlspecialchars($g['category']) ?>"
                 data-title="<?= htmlspecialchars($g['title']) ?>"
                 data-url="<?= htmlspecialchars($g['image_path']) ?>">
                <img src="<?= htmlspecialchars($g['image_path']) ?>"
                     alt="<?= htmlspecialchars($g['title']) ?>"
                     loading="lazy">
                <div class="gal-overlay">
                    <h4><?= htmlspecialchars($g['title']) ?></h4>
                    <span><?= htmlspecialchars($g['caption'] ?? '') ?></span>
                </div>
                <div class="gal-zoom-icon">🔍</div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </div>
</section>

<!-- CTA STRIP -->
<div class="cta-strip">
    <div class="container">
        <h2 class="section-title" style="margin-bottom:.8rem">Ready to Start Your Journey?</h2>
        <p class="section-description" style="margin-bottom:2rem">Let our travel experts craft your perfect Sri Lanka experience — completely personalised, hassle-free.</p>
        <a href="packages.php" class="btn-cta">Book Your Trip Now</a>
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
                <img src="images/fb.avif" alt="Facebook" class="social-link">
                <img src="images/tiktok.webp" alt="TikTok" class="social-link">
            </div>
        </div>
        <div class="footer-section">
            <h3>Quick Links</h3>
            <a href="destination.php">Destinations</a>
            <a href="packages.php">Tour Packages</a>
            <a href="vehicles.html">Our Vehicles</a>
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

<!-- SERVICE MODAL WITH ENQUIRY FORM -->
<div class="svc-modal" id="serviceModal">
    <div class="svc-modal-inner">
        <button class="svc-modal-close" onclick="closeServiceModal()">✕</button>
        <div class="svc-modal-icon" id="modalIcon">🗺️</div>
        <h2 class="svc-modal-title" id="svcModalTitle">Service</h2>
        <p class="svc-modal-desc" id="svcModalDesc"></p>
        
        <button class="enquiry-toggle" id="enquiryToggleBtn" onclick="toggleEnquiryForm()">
            📝 Send Enquiry
        </button>
        
        <div class="enquiry-form" id="enquiryForm">
            <div id="formMessage"></div>
            <form method="POST" action="" id="enquiryFormElement">
                <input type="hidden" name="service_name" id="hiddenServiceName">
                <input type="hidden" name="submit_enquiry" value="1">
                
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="customer_name" id="customerName" required placeholder="Enter your full name">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="customer_email" id="customerEmail" required placeholder="your@email.com">
                    </div>
                    <div class="form-group">
                        <label>Phone *</label>
                        <input type="tel" name="customer_phone" id="customerPhone" required placeholder="+94 XX XXX XXXX">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Travel Date</label>
                        <input type="date" name="travel_date" id="travelDate">
                    </div>
                    <div class="form-group">
                        <label>Number of Travelers</label>
                        <select name="travelers" id="travelers">
                            <option value="1">1 Traveler</option>
                            <option value="2">2 Travelers</option>
                            <option value="3">3 Travelers</option>
                            <option value="4">4 Travelers</option>
                            <option value="5">5 Travelers</option>
                            <option value="6">6+ Travelers</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Special Requests / Message</label>
                    <textarea name="message" id="enquiryMessage" rows="3" placeholder="Any special requirements or questions?"></textarea>
                </div>
                
                <button type="submit" class="btn-submit-enquiry">✈️ Submit Enquiry</button>
            </form>
        </div>
        
        <div class="svc-modal-actions">
            <a href="contact.php" class="btn-enquire" onclick="closeServiceModal()">Contact Us Directly</a>
            <button class="btn-close-modal" onclick="closeServiceModal()">Close</button>
        </div>
    </div>
</div>

<!-- LIGHTBOX -->
<div class="lightbox" id="lightbox">
    <div class="lb-inner">
        <button class="lb-close" onclick="closeLightbox()">✕</button>
        <button class="lb-nav lb-prev" onclick="lbNav(-1)">‹</button>
        <button class="lb-nav lb-next" onclick="lbNav(1)">›</button>
        <img src="" alt="" class="lb-img" id="lbImg">
        <p class="lb-caption" id="lbCaption"></p>
    </div>
</div>

<!-- SUCCESS TOAST NOTIFICATION -->
<?php if ($enquiry_success): ?>
<div id="successToast" style="position:fixed; bottom:20px; right:20px; background:var(--success); color:white; padding:1rem 1.5rem; border-radius:12px; z-index:10000; animation:fadeInUp 0.3s ease; box-shadow:var(--shadow-lg);">
    ✅ Enquiry sent successfully! We'll contact you within 24 hours.
</div>
<script>
    setTimeout(() => {
        const toast = document.getElementById('successToast');
        if(toast) toast.style.display = 'none';
    }, 5000);
</script>
<?php elseif ($enquiry_error): ?>
<div id="errorToast" style="position:fixed; bottom:20px; right:20px; background:var(--error); color:white; padding:1rem 1.5rem; border-radius:12px; z-index:10000; animation:fadeInUp 0.3s ease; box-shadow:var(--shadow-lg);">
    ❌ <?= htmlspecialchars($enquiry_error) ?>
</div>
<script>
    setTimeout(() => {
        const toast = document.getElementById('errorToast');
        if(toast) toast.style.display = 'none';
    }, 5000);
</script>
<?php endif; ?>

<script>
// Mobile menu
const mobileToggle = document.getElementById('mobileToggle');
const navMenu = document.getElementById('navMenu');
if(mobileToggle) {
    mobileToggle.addEventListener('click', () => navMenu.classList.toggle('active'));
}
document.querySelectorAll('.nav-menu a').forEach(a => a.addEventListener('click', () => navMenu.classList.remove('active')));

// Header scroll
window.addEventListener('scroll', () => document.getElementById('header').classList.toggle('scrolled', window.scrollY > 80));

// Fade-in observer
const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, i) => {
        if (entry.isIntersecting) setTimeout(() => entry.target.classList.add('visible'), i * 80);
    });
}, { threshold:0.07, rootMargin:'0px 0px -40px 0px' });
document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', function(e) {
        const t = document.querySelector(this.getAttribute('href'));
        if (t) { e.preventDefault(); window.scrollTo({ top:t.getBoundingClientRect().top + window.pageYOffset - 80, behavior:'smooth' }); }
    });
});

// Service Modal Functions
let currentServiceTitle = '';

function openServiceModal(title, desc, icon) {
    currentServiceTitle = title;
    document.getElementById('svcModalTitle').textContent = title;
    document.getElementById('svcModalDesc').textContent = desc;
    document.getElementById('modalIcon').textContent = icon || '🌐';
    document.getElementById('hiddenServiceName').value = title;
    
    // Reset and hide enquiry form
    const enquiryForm = document.getElementById('enquiryForm');
    enquiryForm.classList.remove('show');
    document.getElementById('enquiryFormElement').reset();
    document.getElementById('formMessage').innerHTML = '';
    
    // Reset toggle button text
    const toggleBtn = document.getElementById('enquiryToggleBtn');
    toggleBtn.textContent = '📝 Send Enquiry';
    toggleBtn.style.background = 'var(--primary)';
    
    document.getElementById('serviceModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    document.body.classList.add('modal-open');
}

function openServiceModalWithForm(title, desc, icon) {
    openServiceModal(title, desc, icon);
    // Auto-show the enquiry form
    setTimeout(() => {
        toggleEnquiryForm(true);
    }, 100);
}

function closeServiceModal() {
    document.getElementById('serviceModal').classList.remove('open');
    document.body.style.overflow = '';
    document.body.classList.remove('modal-open');
}

function toggleEnquiryForm(forceShow = false) {
    const form = document.getElementById('enquiryForm');
    const toggleBtn = document.getElementById('enquiryToggleBtn');
    
    if (forceShow || !form.classList.contains('show')) {
        form.classList.add('show');
        toggleBtn.textContent = '📝 Hide Enquiry Form';
        toggleBtn.style.background = '#64748B';
    } else {
        form.classList.remove('show');
        toggleBtn.textContent = '📝 Send Enquiry';
        toggleBtn.style.background = 'var(--primary)';
    }
}

// Form validation
const enquiryForm = document.getElementById('enquiryFormElement');
if(enquiryForm) {
    enquiryForm.addEventListener('submit', function(e) {
        const name = document.getElementById('customerName').value.trim();
        const email = document.getElementById('customerEmail').value.trim();
        const phone = document.getElementById('customerPhone').value.trim();
        
        if (!name || !email || !phone) {
            e.preventDefault();
            document.getElementById('formMessage').innerHTML = '<div class="error-message">⚠️ Please fill in all required fields (Name, Email, Phone)</div>';
            return false;
        }
        
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            document.getElementById('formMessage').innerHTML = '<div class="error-message">⚠️ Please enter a valid email address</div>';
            return false;
        }
        
        const phoneRegex = /^[\+\d\s\-\(\)]{10,}$/;
        if (!phoneRegex.test(phone)) {
            e.preventDefault();
            document.getElementById('formMessage').innerHTML = '<div class="error-message">⚠️ Please enter a valid phone number</div>';
            return false;
        }
        
        document.getElementById('formMessage').innerHTML = '<div class="success-message">📧 Sending your enquiry...</div>';
    });
}

// Gallery Filter
const galItems = Array.from(document.querySelectorAll('.gal-item'));
document.querySelectorAll('.gal-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.gal-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const cat = btn.dataset.cat;
        galItems.forEach(item => {
            item.classList.toggle('hidden', cat !== 'all' && item.dataset.cat !== cat);
        });
    });
});

// Lightbox
let lbIndex = 0, lbVisible = [];
function buildVisible() { lbVisible = galItems.filter(i => !i.classList.contains('hidden')); }
galItems.forEach(item => {
    item.addEventListener('click', () => {
        buildVisible();
        lbIndex = lbVisible.indexOf(item);
        openLb();
    });
});
function openLb() {
    const item = lbVisible[lbIndex];
    document.getElementById('lbImg').src = item.dataset.url;
    document.getElementById('lbImg').alt = item.dataset.title;
    document.getElementById('lbCaption').textContent = item.dataset.title;
    document.getElementById('lightbox').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
    document.body.style.overflow = '';
}
function lbNav(dir) { lbIndex = (lbIndex + dir + lbVisible.length) % lbVisible.length; openLb(); }
const lightbox = document.getElementById('lightbox');
if(lightbox) {
    lightbox.addEventListener('click', function(e) { if (e.target === this) closeLightbox(); });
}
document.addEventListener('keydown', e => {
    if (document.getElementById('lightbox') && document.getElementById('lightbox').classList.contains('open')) {
        if (e.key === 'ArrowRight') lbNav(1);
        if (e.key === 'ArrowLeft')  lbNav(-1);
        if (e.key === 'Escape')     closeLightbox();
    }
    if (e.key === 'Escape' && document.getElementById('serviceModal').classList.contains('open')) {
        closeServiceModal();
    }
});

// Close modal when clicking outside
const serviceModal = document.getElementById('serviceModal');
if(serviceModal) {
    serviceModal.addEventListener('click', function(e) {
        if (e.target === this) closeServiceModal();
    });
}
</script>
</body>
</html>