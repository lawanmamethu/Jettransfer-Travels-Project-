<?php
session_start();
if (!isset($_SESSION['jt_admin'])) { header('Location: login.php'); exit; }

require_once '../db.php';

// Fetch admin info (same as index.php)
$ar  = $conn->query("SELECT name,email FROM admins WHERE id=1 LIMIT 1");
$adm = $ar ? $ar->fetch_assoc() : ['name'=>'Admin','email'=>'admin@jettransfer.com'];
$admName  = htmlspecialchars($adm['name']);
$admEmail = htmlspecialchars($adm['email']);
$admInit  = strtoupper(substr($adm['name'],0,1));

// Note: The actual vehicle CRUD operations are handled by ../vehicles.php API endpoint
// This file is just the admin interface
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vehicles – Jettransfer Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --primary: #0A7EA4;
            --primary-dark: #065A7A;
            --primary-light: #E0F2FA;
            --secondary: #F59E0B;
            --accent: #10B981;
            --accent-light: #D1FAE5;
            --danger: #EF4444;
            --danger-light: #FEE2E2;
            --text-dark: #0F172A;
            --text-mid: #475569;
            --text-light: #94A3B8;
            --bg: #F1F5F9;
            --sidebar-bg: #0F172A;
            --border: #E2E8F0;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.08);
            --sidebar-w: 260px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Manrope', sans-serif;
            background: var(--bg);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
        }

        /* ── Sidebar (same as dashboard) ── */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            display: flex;
            flex-direction: column;
            z-index: 100;
            transition: transform .3s ease;
        }

        .sidebar-brand {
            padding: 1.8rem 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, .07);
            display: flex;
            align-items: center;
            gap: .8rem;
        }

        .sidebar-logo {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .sidebar-brand-text h2 {
            font-family: 'Sora', sans-serif;
            font-size: 1rem;
            font-weight: 800;
            color: #fff;
        }

        .sidebar-brand-text span {
            font-size: .72rem;
            color: rgba(255, 255, 255, .4);
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .sidebar-nav {
            flex: 1;
            padding: 1.2rem .8rem;
            overflow-y: auto;
        }

        .nav-label {
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .25);
            padding: .5rem .8rem;
            margin-top: .8rem;
            margin-bottom: .3rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .75rem .9rem;
            border-radius: 12px;
            color: rgba(255, 255, 255, .55);
            text-decoration: none;
            font-size: .9rem;
            font-weight: 500;
            transition: all .2s ease;
            margin-bottom: .2rem;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, .07);
            color: rgba(255, 255, 255, .9);
        }

        .nav-item.active {
            background: linear-gradient(135deg, rgba(10, 126, 164, .35), rgba(16, 185, 129, .2));
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(10, 126, 164, .4);
        }

        .nav-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .sidebar-footer {
            padding: 1rem .8rem 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, .07);
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .75rem .9rem;
            border-radius: 12px;
            background: rgba(255, 255, 255, .05);
            margin-bottom: .5rem;
        }

        .admin-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .9rem;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .admin-info h4 {
            font-size: .85rem;
            font-weight: 600;
            color: #fff;
        }

        .admin-info p {
            font-size: .72rem;
            color: rgba(255, 255, 255, .4);
        }

        .btn-logout {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .7rem .9rem;
            border-radius: 12px;
            color: rgba(255, 255, 255, .5);
            font-size: .88rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .2s;
            border: none;
            background: none;
            width: 100%;
            font-family: 'Manrope', sans-serif;
            text-decoration: none;
        }

        .btn-logout:hover {
            background: rgba(239, 68, 68, .15);
            color: #FCA5A5;
        }

        /* ── Main ── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: var(--shadow-sm);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .hamburger {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 6px;
            color: var(--text-dark);
        }

        .page-title h1 {
            font-family: 'Sora', sans-serif;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .page-title p {
            font-size: .8rem;
            color: var(--text-light);
        }

        .topbar-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: .9rem;
        }

        /* ── Content ── */
        .content {
            padding: 2rem;
            flex: 1;
        }

        /* ── Panel ── */
        .panel {
            background: #fff;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .panel-header {
            padding: 1.4rem 1.8rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .panel-header h2 {
            font-family: 'Sora', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
        }

        /* ── Table ── */
        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            text-align: left;
            font-size: .72rem;
            font-weight: 700;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: .8px;
            padding: .9rem 1.5rem;
            background: var(--bg);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        tbody td {
            padding: 1.1rem 1.5rem;
            font-size: .88rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover td {
            background: #F8FAFC;
        }

        .vehicle-name-cell {
            display: flex;
            align-items: center;
            gap: .9rem;
        }

        .vehicle-thumb {
            width: 52px;
            height: 40px;
            border-radius: 10px;
            object-fit: cover;
            background: var(--bg);
            flex-shrink: 0;
        }

        .vehicle-thumb-placeholder {
            width: 52px;
            height: 40px;
            border-radius: 10px;
            background: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .vehicle-name {
            font-weight: 700;
            color: var(--text-dark);
            font-size: .9rem;
        }

        .vehicle-plate {
            font-size: .75rem;
            color: var(--text-light);
            margin-top: .15rem;
        }

        /* ── Status pills ── */
        .pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .3rem .85rem;
            border-radius: 50px;
            font-size: .75rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .pill-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .pill-available {
            background: var(--accent-light);
            color: #059669;
        }

        .pill-available .pill-dot {
            background: #059669;
        }

        .pill-booked {
            background: #FEF3C7;
            color: #D97706;
        }

        .pill-booked .pill-dot {
            background: #D97706;
        }

        .pill-good {
            background: var(--accent-light);
            color: #059669;
        }

        .pill-good .pill-dot {
            background: #059669;
        }

        .pill-maintenance {
            background: #FEE2E2;
            color: var(--danger);
        }

        .pill-maintenance .pill-dot {
            background: var(--danger);
        }

        /* ── Add button ── */
        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .55rem 1.2rem;
            border-radius: 50px;
            background: var(--primary);
            color: #fff;
            border: none;
            font-family: 'Manrope', sans-serif;
            font-size: .85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }

        .btn-add:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        /* ── Delete button ── */
        .btn-delete {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .45rem 1rem;
            border-radius: 50px;
            background: var(--danger-light);
            color: var(--danger);
            border: none;
            font-family: 'Manrope', sans-serif;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }

        .btn-delete:hover {
            background: var(--danger);
            color: #fff;
        }

        /* ── Form input style for add modal ── */
        .form-row input[type=text],
        .form-row input[type=number] {
            width: 100%;
            padding: .75rem 1rem;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-family: 'Manrope', sans-serif;
            font-size: .9rem;
            color: var(--text-dark);
            outline: none;
            transition: border-color .2s;
            background: #fff;
        }

        .form-row input[type=text]:focus,
        .form-row input[type=number]:focus {
            border-color: var(--primary);
        }

        /* ── Edit button ── */
        .btn-edit {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .45rem 1rem;
            border-radius: 50px;
            background: var(--primary-light);
            color: var(--primary);
            border: none;
            font-family: 'Manrope', sans-serif;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }

        .btn-edit:hover {
            background: var(--primary);
            color: #fff;
        }

        /* ── Loading / empty ── */
        .state-box {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
        }

        .state-box .icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .state-box p {
            font-size: .9rem;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin .7s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ── Modal ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .45);
            z-index: 200;
            align-items: flex-start;
            justify-content: center;
            overflow-y: auto;
            padding: 2rem 1rem;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal {
            background: #fff;
            border-radius: 20px;
            padding: 2rem;
            width: 100%;
            max-width: 440px;
            margin: 1rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .2);
            animation: popIn .3s ease;
            max-height: 90vh;
            overflow-y: auto;
        }

        @keyframes popIn {
            from {
                opacity: 0;
                transform: scale(.95) translateY(10px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-title {
            font-family: 'Sora', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: .4rem;
        }

        .modal-subtitle {
            font-size: .85rem;
            color: var(--text-light);
            margin-bottom: 1.8rem;
        }

        .form-row {
            margin-bottom: 1.2rem;
        }

        .form-row label {
            display: block;
            font-size: .78rem;
            font-weight: 700;
            color: var(--text-mid);
            margin-bottom: .45rem;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .form-row select {
            width: 100%;
            padding: .75rem 1rem;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-family: 'Manrope', sans-serif;
            font-size: .9rem;
            color: var(--text-dark);
            outline: none;
            transition: border-color .2s;
            background: #fff;
            cursor: pointer;
        }

        .form-row select:focus {
            border-color: var(--primary);
        }

        .modal-actions {
            display: flex;
            gap: .8rem;
            justify-content: flex-end;
            margin-top: 1.8rem;
        }

        .btn-cancel {
            padding: .65rem 1.4rem;
            border-radius: 50px;
            border: 1.5px solid var(--border);
            background: #fff;
            font-family: 'Manrope', sans-serif;
            font-size: .88rem;
            font-weight: 600;
            cursor: pointer;
            color: var(--text-mid);
            transition: all .2s;
        }

        .btn-cancel:hover {
            border-color: var(--text-mid);
        }

        .btn-save {
            padding: .65rem 1.6rem;
            border-radius: 50px;
            border: none;
            background: var(--primary);
            color: #fff;
            font-family: 'Manrope', sans-serif;
            font-size: .88rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }

        .btn-save:hover {
            background: var(--primary-dark);
        }

        .btn-save:disabled {
            opacity: .6;
            pointer-events: none;
        }

        /* ── Toast ── */
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--text-dark);
            color: #fff;
            padding: .9rem 1.5rem;
            border-radius: 14px;
            font-size: .88rem;
            font-weight: 600;
            box-shadow: 0 8px 30px rgba(0, 0, 0, .2);
            transform: translateY(100px);
            opacity: 0;
            transition: all .4s cubic-bezier(.22, 1, .36, 1);
            z-index: 999;
            display: flex;
            align-items: center;
            gap: .6rem;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        .toast.success {
            background: #059669;
        }

        .toast.error {
            background: var(--danger);
        }

        /* ── Sidebar overlay ── */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .5);
            z-index: 99;
        }

        .sidebar-overlay.show {
            display: block;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main {
                margin-left: 0;
            }

            .hamburger {
                display: flex;
            }
        }
    </style>
</head>

<body>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-logo">✈️</div>
            <div class="sidebar-brand-text">
                <h2>Jettransfer</h2><span>Admin Panel</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Main</div>
            <a href="index.php" class="nav-item">
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
            <a href="vehicles.php" class="nav-item active">
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
            <div class="nav-label">Reports</div>
            <a href="admin_monthly_report.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Monthly Report
            </a>
            <div class="nav-label">Other</div>
            <a href="../index.php" class="nav-item" target="_blank">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                View Site
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="admin-profile">
                <div class="admin-avatar"><?= $admInit ?></div>
                <div class="admin-info">
                    <h4><?= $admName ?></h4>
                    <p><?= $admEmail ?></p>
                </div>
            </div>
            <a href="logout.php" class="btn-logout">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
    </aside>

    <!-- Main -->
    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="hamburger" onclick="toggleSidebar()">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>
                <div class="page-title">
                    <h1>Vehicles</h1>
                    <p>Manage fleet availability and condition</p>
                </div>
            </div>
            <div class="topbar-right">
                <div class="topbar-avatar" id="topbarAvatar"><?= $admInit ?></div>
            </div>
        </header>

        <div class="content">
            <div class="panel">
                <div class="panel-header">
                    <h2>All Vehicles</h2>
                    <button class="btn-add" onclick="openAddModal()">+ Add Vehicle</button>
                </div>
                <div class="table-wrap">
                    <table id="vehicleTable" style="display:none">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Vehicle</th>
                                <th>Type</th>
                                <th>Seats</th>
                                <th>Fuel</th>
                                <th>Luggage</th>
                                <th>Availability</th>
                                <th>Condition</th>
                                <th>Edit</th>
                                <th>Delete</th>
                            </tr>
                        </thead>
                        <tbody id="vehicleTbody"></tbody>
                    </table>
                </div>
                <div id="loadingState" class="state-box">
                    <div class="spinner"></div>
                    <p>Loading vehicles...</p>
                </div>
                <div id="errorState" class="state-box" style="display:none">
                    <div class="icon">⚠️</div>
                    <p id="errorMsg">Could not load vehicles.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal">
            <div class="modal-title" id="modalVehicleName">Edit Vehicle</div>
            <div class="modal-subtitle" id="modalVehiclePlate">Update status below</div>
            <input type="hidden" id="editVehicleId">

            <div class="form-row">
                <label>Availability</label>
                <select id="editAvailability">
                    <option value="Available">✅ Available</option>
                    <option value="Booked">📅 Booked</option>
                </select>
            </div>

            <div class="form-row">
                <label>Condition Status</label>
                <select id="editCondition">
                    <option value="Good Condition">✅ Good Condition</option>
                    <option value="Under Maintenance">🔧 Under Maintenance</option>
                </select>
            </div>

            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeModal('editModal')">Cancel</button>
                <button class="btn-save" id="saveBtn" onclick="saveChanges()">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Add Vehicle Modal -->
    <div class="modal-overlay" id="addModal">
        <div class="modal">
            <div class="modal-title">Add New Vehicle</div>
            <div class="modal-subtitle">Fill in the vehicle details below</div>

            <div class="form-row">
                <label>Vehicle Name</label>
                <input type="text" id="addName" placeholder="e.g. Toyota Prius">
            </div>
            <div class="form-row">
                <label>Plate Number</label>
                <input type="text" id="addPlate" placeholder="e.g. WP CAB 1234">
            </div>
            <div class="form-row">
                <label>Type</label>
                <select id="addType">
                    <option value="">Select type…</option>
                    <option value="Luxury Car">Luxury Car</option>
                    <option value="SUV">SUV</option>
                    <option value="Van">Van</option>
                    <option value="Luxury Van">Luxury Van</option>
                    <option value="Minibus">Minibus</option>
                    <option value="Premium Car">Premium Car</option>
                </select>
            </div>
            <div class="form-row">
                <label>Seats</label>
                <input type="number" id="addSeats" placeholder="e.g. 4" min="1">
            </div>
            <div class="form-row">
                <label>Fuel</label>
                <select id="addFuel">
                    <option value="">Select fuel…</option>
                    <option value="Petrol">Petrol</option>
                    <option value="Diesel">Diesel</option>
                    <option value="Hybrid">Hybrid</option>
                    <option value="Petrol/Diesel">Petrol/Diesel</option>
                </select>
            </div>
            <div class="form-row">
                <label>Luggage</label>
                <select id="addLuggage">
                    <option value="">Select luggage…</option>
                    <option value="Small Luggage">Small Luggage</option>
                    <option value="Medium Luggage">Medium Luggage</option>
                    <option value="Large Luggage">Large Luggage</option>
                </select>
            </div>
            <div class="form-row">
                <label>Image Filename</label>
                <input type="text" id="addImage" placeholder="e.g. prius.jpeg">
            </div>
            <div class="form-row">
                <label>Availability</label>
                <select id="addAvailability">
                    <option value="Available">✅ Available</option>
                    <option value="Booked">📅 Booked</option>
                </select>
            </div>
            <div class="form-row">
                <label>Condition</label>
                <select id="addCondition">
                    <option value="Good Condition">✅ Good Condition</option>
                    <option value="Under Maintenance">🔧 Under Maintenance</option>
                </select>
            </div>

            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeModal('addModal')">Cancel</button>
                <button class="btn-save" id="addBtn" onclick="addVehicle()">Add Vehicle</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <script>
        // Admin info from PHP
        const adminName = '<?= $admName ?>';
        const adminEmail = '<?= $admEmail ?>';
        const adminInit = '<?= $admInit ?>';

        // ── Pill HTML helpers ────────────────────────────────────
        function availPill(val) {
            if (val === 'Available') return `<span class="pill pill-available"><span class="pill-dot"></span>Available</span>`;
            return `<span class="pill pill-booked"><span class="pill-dot"></span>Booked</span>`;
        }

        function condPill(val) {
            if (val === 'Good Condition') return `<span class="pill pill-good"><span class="pill-dot"></span>Good Condition</span>`;
            return `<span class="pill pill-maintenance"><span class="pill-dot"></span>Under Maintenance</span>`;
        }

        // ── Render table ─────────────────────────────────────────
        function renderTable(vehicles) {
            const tbody = document.getElementById('vehicleTbody');
            tbody.innerHTML = '';
            vehicles.forEach((v, i) => {
                const imgCell = v.image
                    ? `<img src="../${v.image}" alt="${v.name}" class="vehicle-thumb" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
                    : '';
                tbody.innerHTML += `
            <tr>
                <td>${i + 1}</td>
                <td>
                    <div class="vehicle-name-cell">
                        ${imgCell}
                        <div class="vehicle-thumb-placeholder" style="${v.image ? 'display:none' : ''}">🚗</div>
                        <div>
                            <div class="vehicle-name">${escapeHtml(v.name)}</div>
                            <div class="vehicle-plate">${escapeHtml(v.plate)}</div>
                        </div>
                    </div>
                 </td>
                <td>${escapeHtml(v.type)}</td>
                <td>${v.seats}</td>
                <td>${escapeHtml(v.fuel)}</td>
                <td>${escapeHtml(v.luggage)}</td>
                <td>${availPill(v.availability)}</td>
                <td>${condPill(v.condition_status)}</td>
                <td>
                    <button class="btn-edit" onclick="openModal(${v.id}, '${escapeHtml(v.name)}', '${escapeHtml(v.plate)}', '${v.availability}', '${v.condition_status}')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Edit
                    </button>
                 </td>
                <td>
                    <button class="btn-delete" onclick="deleteVehicle(${v.id}, '${escapeHtml(v.name)}')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                        Delete
                    </button>
                 </td>
             </tr>`;
            });

            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('vehicleTable').style.display = 'table';
        }

        // Helper to escape HTML
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }

        // ── Load vehicles ────────────────────────────────────────
        function loadVehicles() {
            fetch('../vehicles.php?action=get')
                .then(r => r.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message);
                    renderTable(data.vehicles);
                })
                .catch(err => {
                    document.getElementById('loadingState').style.display = 'none';
                    document.getElementById('errorState').style.display = 'block';
                    document.getElementById('errorMsg').textContent = err.message;
                });
        }

        loadVehicles();

        // ── Modal ────────────────────────────────────────────────
        function openModal(id, name, plate, availability, condition) {
            document.getElementById('editVehicleId').value = id;
            document.getElementById('modalVehicleName').textContent = name;
            document.getElementById('modalVehiclePlate').textContent = plate;
            document.getElementById('editAvailability').value = availability;
            document.getElementById('editCondition').value = condition;
            document.getElementById('editModal').classList.add('show');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('show');
        }

        document.getElementById('editModal').addEventListener('click', function (e) {
            if (e.target === this) closeModal('editModal');
        });
        document.getElementById('addModal').addEventListener('click', function (e) {
            if (e.target === this) closeModal('addModal');
        });

        // ── Save changes → update_vehicle.php ───────────────────
        function saveChanges() {
            const id = document.getElementById('editVehicleId').value;
            const availability = document.getElementById('editAvailability').value;
            const condition = document.getElementById('editCondition').value;
            const saveBtn = document.getElementById('saveBtn');

            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            const formData = new FormData();
            formData.append('id', id);
            formData.append('availability', availability);
            formData.append('condition_status', condition);
            formData.append('action', 'update');

            fetch('../vehicles.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        closeModal('editModal');
                        loadVehicles();
                        showToast('✅ Vehicle updated successfully!', 'success');
                    } else {
                        showToast('❌ ' + data.message, 'error');
                    }
                })
                .catch(() => showToast('❌ Network error. Try again.', 'error'))
                .finally(() => {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save Changes';
                });
        }

        // ── Open Add Modal ───────────────────────────────────────
        function openAddModal() {
            document.getElementById('addName').value = '';
            document.getElementById('addPlate').value = '';
            document.getElementById('addType').value = '';
            document.getElementById('addSeats').value = '';
            document.getElementById('addFuel').value = '';
            document.getElementById('addLuggage').value = '';
            document.getElementById('addImage').value = '';
            document.getElementById('addAvailability').value = 'Available';
            document.getElementById('addCondition').value = 'Good Condition';
            document.getElementById('addModal').classList.add('show');
        }

        // ── Add Vehicle ──────────────────────────────────────────
        function addVehicle() {
            const name = document.getElementById('addName').value.trim();
            const plate = document.getElementById('addPlate').value.trim();
            const type = document.getElementById('addType').value;
            const seats = document.getElementById('addSeats').value.trim();
            const fuel = document.getElementById('addFuel').value;
            const luggage = document.getElementById('addLuggage').value;
            const image = document.getElementById('addImage').value.trim();
            const availability = document.getElementById('addAvailability').value;
            const condition = document.getElementById('addCondition').value;
            const addBtn = document.getElementById('addBtn');

            if (!name || !plate || !type || !seats || !fuel || !luggage) {
                showToast('❌ Please fill in all required fields.', 'error');
                return;
            }

            addBtn.disabled = true;
            addBtn.textContent = 'Adding...';

            const formData = new FormData();
            formData.append('name', name);
            formData.append('plate', plate);
            formData.append('type', type);
            formData.append('seats', seats);
            formData.append('fuel', fuel);
            formData.append('luggage', luggage);
            formData.append('image', image);
            formData.append('availability', availability);
            formData.append('condition_status', condition);
            formData.append('action', 'add');

            fetch('../vehicles.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        closeModal('addModal');
                        loadVehicles();
                        showToast('✅ Vehicle added successfully!', 'success');
                    } else {
                        showToast('❌ ' + data.message, 'error');
                    }
                })
                .catch(() => showToast('❌ Network error. Try again.', 'error'))
                .finally(() => {
                    addBtn.disabled = false;
                    addBtn.textContent = 'Add Vehicle';
                });
        }

        // ── Delete Vehicle ───────────────────────────────────────
        function deleteVehicle(id, name) {
            if (!confirm(`Are you sure you want to delete "${name}"?`)) return;

            const formData = new FormData();
            formData.append('id', id);
            formData.append('action', 'delete');

            fetch('../vehicles.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        loadVehicles();
                        showToast('✅ Vehicle deleted successfully!', 'success');
                    } else {
                        showToast('❌ ' + data.message, 'error');
                    }
                })
                .catch(() => showToast('❌ Network error. Try again.', 'error'));
        }

        // ── Toast notification ───────────────────────────────────
        function showToast(msg, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = msg;
            toast.className = `toast ${type} show`;
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        // ── Sidebar ──────────────────────────────────────────────
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