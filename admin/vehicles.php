<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vehicles – Jettransfer Admin</title>
    <link rel="icon" type="image/jpeg" href="../logo.jpeg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=Manrope:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
        /* ── Root Variables ── */
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

        /* ── Reset ── */
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

        /* ── Sidebar ── */
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
        }

        .btn-logout:hover {
            background: rgba(239, 68, 68, .15);
            color: #FCA5A5;
        }

        /* ── Main Layout ── */
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

        /* ── Pills ── */
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

        .pill-pending {
            background: #FEF3C7;
            color: #D97706;
        }

        .pill-pending .pill-dot {
            background: #D97706;
        }

        .pill-responded {
            background: #D1FAE5;
            color: #059669;
        }

        .pill-responded .pill-dot {
            background: #059669;
        }

        .pill-available-check {
            background: #D1FAE5;
            color: #059669;
        }

        .pill-available-check .pill-dot {
            background: #059669;
        }

        .pill-not-available {
            background: #FEE2E2;
            color: #DC2626;
        }

        .pill-not-available .pill-dot {
            background: #DC2626;
        }

        /* ── Buttons ── */
        .btn-report {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .45rem .9rem;
            border-radius: 8px;
            border: 1.5px solid var(--border);
            background: #fff;
            font-family: 'Manrope', sans-serif;
            font-size: .8rem;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            transition: all .2s;
            text-decoration: none;
        }

        .btn-report:hover {
            background: var(--bg);
            border-color: var(--accent);
            color: var(--accent);
        }

        .btn-report-pdf:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--bg);
        }

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

        .btn-respond {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .35rem .8rem;
            border-radius: 50px;
            background: #D1FAE5;
            color: #059669;
            border: none;
            font-family: 'Manrope', sans-serif;
            font-size: .78rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }

        .btn-respond:hover {
            background: #059669;
            color: white;
        }

        /* ── Tab Bar ── */
        .tab-bar {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .tab-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.4rem;
            border-radius: 12px;
            border: 1.5px solid var(--border);
            background: white;
            font-family: 'Manrope', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-mid);
            cursor: pointer;
            transition: all 0.2s;
        }

        .tab-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .tab-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .tab-badge {
            background: #EF4444;
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.15rem 0.5rem;
            border-radius: 50px;
            min-width: 20px;
            text-align: center;
        }

        /* ── Misc table helpers ── */
        .msg-cell {
            max-width: 160px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--text-light);
            font-size: 0.82rem;
        }

        /* ── Loading / empty states ── */
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

        .form-row input[type=text],
        .form-row input[type=number],
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

        .form-row input[type=text]:focus,
        .form-row input[type=number]:focus,
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

        /* ── Sidebar overlay (mobile) ── */
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

        /* ── Responsive ── */
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
                <h2>Jettransfer</h2>
                <span>Admin Panel</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-label">Main</div>
            <a href="index.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" />
                    <rect x="14" y="3" width="7" height="7" />
                    <rect x="3" y="14" width="7" height="7" />
                    <rect x="14" y="14" width="7" height="7" />
                </svg>
                Dashboard
            </a>
            <div class="nav-label">Modules</div>
            <a href="destinations.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                    <circle cx="12" cy="10" r="3" />
                </svg>
                Destinations
            </a>
            <a href="packages.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                </svg>
                Packages
            </a>
            <a href="vehicles.php" class="nav-item active">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="3" width="15" height="13" />
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8" />
                    <circle cx="5.5" cy="18.5" r="2.5" />
                    <circle cx="18.5" cy="18.5" r="2.5" />
                </svg>
                Vehicles
            </a>
            <a href="profiles.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
                Profiles
            </a>
            <a href="bookings.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/></svg>
            Bookings
            </a>
            <a href="services.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" />
                    <circle cx="8.5" cy="8.5" r="1.5" />
                    <polyline points="21 15 16 10 5 21" />
                </svg>
                Services & Gallery
            </a>
            <a href="contact.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                </svg>
                Contact Us / Reviews
            </a>
            <div class="nav-label">Reports</div>
        <a href="admin_monthly_report.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/></svg>
            Monthly Report
        </a>
            <div class="nav-label">Other</div>
            <a href="../index.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9 22 9 12 15 12 15 22" />
                </svg>
                View Site
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="admin-profile">
                <div class="admin-avatar" id="sidebarAvatar">A</div>
                <div class="admin-info">
                    <h4 id="adminName">Admin</h4>
                    <p id="adminEmail"></p>
                </div>
            </div>
            <button class="btn-logout" onclick="logout()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
                Logout
            </button>
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
            <div class="topbar-avatar" id="topbarAvatar">A</div>
        </header>

        <div class="content">

            <!-- Tab Buttons -->
            <div class="tab-bar">
                <button class="tab-btn active" id="tabVehicles" onclick="switchTab('vehicles')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="3" width="15" height="13" />
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8" />
                        <circle cx="5.5" cy="18.5" r="2.5" />
                        <circle cx="18.5" cy="18.5" r="2.5" />
                    </svg>
                    Vehicles
                </button>
                <button class="tab-btn" id="tabEnquiries" onclick="switchTab('enquiries')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                    </svg>
                    Availability Checks
                    <span class="tab-badge" id="enquiryBadge" style="display:none">0</span>
                </button>
            </div>

            <!-- Vehicles Panel -->
            <div class="panel" id="panelVehicles">
                <div class="panel-header">
                    <h2>All Vehicles</h2>
                    <div style="display:flex;gap:0.8rem;align-items:center;flex-wrap:wrap;">
                        <button class="btn-report" onclick="exportCSV()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" y1="15" x2="12" y2="3" />
                            </svg>
                            Export CSV
                        </button>
                        <button class="btn-report btn-report-pdf" onclick="printReport()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 6 2 18 2 18 9" />
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                                <rect x="6" y="14" width="12" height="8" />
                            </svg>
                            Print / PDF
                        </button>
                        <button class="btn-add" onclick="openAddModal()">+ Add Vehicle</button>
                    </div>
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

            <!-- Enquiries Panel -->
            <div class="panel" id="panelEnquiries" style="display:none">
                <div class="panel-header">
                    <h2>Vehicle Availability Checks</h2>
                    <span style="font-size:0.85rem;color:var(--text-light);" id="enquiryCount">Loading...</span>
                </div>
                <div class="table-wrap">
                    <table id="enquiryTable" style="display:none">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Vehicle Type</th>
                                <th>Travel Date</th>
                                <th>Passengers</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="enquiryTbody"></tbody>
                    </table>
                </div>
                <div id="enquiryLoadingState" class="state-box">
                    <div class="spinner"></div>
                    <p>Loading availability checks...</p>
                </div>
                <div id="enquiryEmptyState" class="state-box" style="display:none">
                    <div class="icon">🔍</div>
                    <p>No availability check requests yet.</p>
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
            <div class="form-row"><label>Vehicle Name *</label><input type="text" id="addName"
                    placeholder="e.g. Toyota Prius" maxlength="100"></div>
            <div class="form-row"><label>Plate Number *</label><input type="text" id="addPlate"
                    placeholder="e.g. WP CAB 1234" maxlength="20" style="text-transform:uppercase">
                <small style="color:var(--text-light);font-size:0.75rem;margin-top:0.3rem;display:block;">Format: 2-letter province + 2-3 letters + 4 digits &nbsp;(e.g. WP CAB 1234 · NC AB 5678)</small>
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
            <div class="form-row"><label>Seats</label><input type="number" id="addSeats" placeholder="e.g. 4" min="1">
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
            <div class="form-row"><label>Image Filename</label><input type="text" id="addImage"
                    placeholder="e.g. prius.jpeg"></div>
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

    <!-- Respond Modal -->
    <div class="modal-overlay" id="respondModal">
        <div class="modal">
            <div class="modal-title">Respond to Availability Check</div>
            <div class="modal-subtitle" id="respondModalName">Is this vehicle type available for the requested date?</div>
            <input type="hidden" id="respondEnquiryId">
            <div style="display:flex;gap:1rem;margin-top:1rem;">
                <button onclick="submitRespond('Available')" style="flex:1;padding:.9rem;border-radius:12px;border:none;background:#D1FAE5;color:#059669;font-family:'Manrope',sans-serif;font-size:.95rem;font-weight:700;cursor:pointer;transition:all .2s;" onmouseover="this.style.background='#059669';this.style.color='white'" onmouseout="this.style.background='#D1FAE5';this.style.color='#059669'">
                    ✅ Available
                </button>
                <button onclick="submitRespond('Not Available')" style="flex:1;padding:.9rem;border-radius:12px;border:none;background:#FEE2E2;color:#DC2626;font-family:'Manrope',sans-serif;font-size:.95rem;font-weight:700;cursor:pointer;transition:all .2s;" onmouseover="this.style.background='#DC2626';this.style.color='white'" onmouseout="this.style.background='#FEE2E2';this.style.color='#DC2626'">
                    ❌ Not Available
                </button>
            </div>
            <div class="modal-actions" style="margin-top:1rem;">
                <button class="btn-cancel" onclick="closeModal('respondModal')">Cancel</button>
            </div>
        </div>
    </div>

    <script>

        // ── Pill helpers ──
        function availPill(val) {
            return val === 'Available'
                ? `<span class="pill pill-available"><span class="pill-dot"></span>Available</span>`
                : `<span class="pill pill-booked"><span class="pill-dot"></span>Booked</span>`;
        }
        function condPill(val) {
            return val === 'Good Condition'
                ? `<span class="pill pill-good"><span class="pill-dot"></span>Good Condition</span>`
                : `<span class="pill pill-maintenance"><span class="pill-dot"></span>Under Maintenance</span>`;
        }

        // ── Render vehicles table ──
        function renderTable(vehicles) {
            const tbody = document.getElementById('vehicleTbody');
            tbody.innerHTML = '';
            vehicles.forEach((v, i) => {
                const imgCell = v.image
                    ? `<img src="${v.image}" alt="${v.name}" class="vehicle-thumb" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
                    : '';
                tbody.innerHTML += `
            <tr>
                <td>${i + 1}</td>
                <td>
                    <div class="vehicle-name-cell">
                        ${imgCell}
                        <div class="vehicle-thumb-placeholder" style="${v.image ? 'display:none' : ''}">🚗</div>
                        <div>
                            <div class="vehicle-name">${v.name}</div>
                            <div class="vehicle-plate">${v.plate}</div>
                        </div>
                    </div>
                </td>
                <td>${v.type}</td>
                <td>${v.seats}</td>
                <td>${v.fuel}</td>
                <td>${v.luggage}</td>
                <td>${availPill(v.availability)}</td>
                <td>${condPill(v.condition_status)}</td>
                <td>
                    <button class="btn-edit" onclick="openModal(${v.id},'${v.name}','${v.plate}','${v.availability}','${v.condition_status}')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Edit
                    </button>
                </td>
                <td>
                    <button class="btn-delete" onclick="deleteVehicle(${v.id},'${v.name}')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                        Delete
                    </button>
                </td>
            </tr>`;
            });
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('vehicleTable').style.display = 'table';
        }

        // ── Load vehicles ──
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

        // ── Modal helpers ──
        function openModal(id, name, plate, availability, condition) {
            document.getElementById('editVehicleId').value = id;
            document.getElementById('modalVehicleName').textContent = name;
            document.getElementById('modalVehiclePlate').textContent = plate;
            document.getElementById('editAvailability').value = availability;
            document.getElementById('editCondition').value = condition;
            document.getElementById('editModal').classList.add('show');
        }
        function closeModal(id) { document.getElementById(id).classList.remove('show'); }
        document.getElementById('editModal').addEventListener('click', function (e) { if (e.target === this) closeModal('editModal'); });
        document.getElementById('addModal').addEventListener('click', function (e) { if (e.target === this) closeModal('addModal'); });
        document.getElementById('respondModal').addEventListener('click', function (e) { if (e.target === this) closeModal('respondModal'); });

        // ── Save edit ──
        function saveChanges() {
            const id = document.getElementById('editVehicleId').value;
            const availability = document.getElementById('editAvailability').value;
            const condition = document.getElementById('editCondition').value;
            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = true; saveBtn.textContent = 'Saving...';

            const fd = new FormData();
            fd.append('action', 'update'); fd.append('id', id);
            fd.append('availability', availability); fd.append('condition_status', condition);

            fetch('../vehicles.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) { closeModal('editModal'); loadVehicles(); showToast('✅ Vehicle updated successfully!', 'success'); }
                    else showToast('❌ ' + data.message, 'error');
                })
                .catch(() => showToast('❌ Network error. Try again.', 'error'))
                .finally(() => { saveBtn.disabled = false; saveBtn.textContent = 'Save Changes'; });
        }

        // ── Open Add Modal ──
        function openAddModal() {
            ['addName', 'addPlate', 'addSeats', 'addImage'].forEach(id => document.getElementById(id).value = '');
            ['addType', 'addFuel', 'addLuggage'].forEach(id => document.getElementById(id).value = '');
            document.getElementById('addAvailability').value = 'Available';
            document.getElementById('addCondition').value = 'Good Condition';
            document.getElementById('addModal').classList.add('show');
        }

        // ── Add Vehicle ──
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
                showToast('❌ Please fill in all required fields.', 'error'); return;
            }
            if (isNaN(seats) || parseInt(seats) < 1 || parseInt(seats) > 100) {
                showToast('❌ Seats must be a number between 1 and 100.', 'error'); return;
            }
            // Sri Lanka plate: 2 letters + space + 2-3 letters + space + 4 digits  e.g. WP CAB 1234
            const plateRegex = /^[A-Z]{2}\s[A-Z]{2,3}\s\d{4}$/i;
            if (!plateRegex.test(plate.trim())) {
                showToast('❌ Invalid plate number. Use format: WP CAB 1234 (province + 2-3 letters + 4 digits).', 'error');
                document.getElementById('addPlate').focus();
                return;
            }
            addBtn.disabled = true; addBtn.textContent = 'Adding...';

            const fd = new FormData();
            fd.append('action', 'add'); fd.append('name', name); fd.append('plate', plate);
            fd.append('type', type); fd.append('seats', seats); fd.append('fuel', fuel);
            fd.append('luggage', luggage); fd.append('image', image);
            fd.append('availability', availability); fd.append('condition_status', condition);

            fetch('../vehicles.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) { closeModal('addModal'); loadVehicles(); showToast('✅ Vehicle added successfully!', 'success'); }
                    else showToast('❌ ' + data.message, 'error');
                })
                .catch(() => showToast('❌ Network error. Try again.', 'error'))
                .finally(() => { addBtn.disabled = false; addBtn.textContent = 'Add Vehicle'; });
        }

        // ── Delete Vehicle ──
        function deleteVehicle(id, name) {
            if (!confirm(`Are you sure you want to delete "${name}"?`)) return;
            const fd = new FormData();
            fd.append('action', 'delete'); fd.append('id', id);
            fetch('../vehicles.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) { loadVehicles(); showToast('✅ Vehicle deleted successfully!', 'success'); }
                    else showToast('❌ ' + data.message, 'error');
                })
                .catch(() => showToast('❌ Network error. Try again.', 'error'));
        }

        // ── Toast ──
        function showToast(msg, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = msg;
            toast.className = `toast ${type} show`;
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        // ── Export CSV (Vehicles + Availability Checks) ──
        function exportCSV() {
            // Section 1: Vehicles from DOM
            const vehicleHeaders = ['#', 'Name', 'Plate', 'Type', 'Seats', 'Fuel', 'Luggage', 'Availability', 'Condition'];
            const vehicleRows = [vehicleHeaders];
            document.querySelectorAll('#vehicleTbody tr').forEach((row, i) => {
                const cells = row.querySelectorAll('td');
                if (cells.length < 9) return;
                const esc = v => '"' + (v || '').replace(/"/g, '""').trim() + '"';
                vehicleRows.push([
                    esc(String(i + 1)),
                    esc(cells[1].querySelector('.vehicle-name')?.textContent || ''),
                    esc(cells[1].querySelector('.vehicle-plate')?.textContent || ''),
                    esc(cells[2].textContent), esc(cells[3].textContent),
                    esc(cells[4].textContent), esc(cells[5].textContent),
                    esc(cells[6].textContent), esc(cells[7].textContent),
                ]);
            });

            // Section 2: Availability checks fetched from backend
            const fd = new FormData();
            fd.append('action', 'get_enquiries');
            fetch('../vehicles.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    const esc = v => '"' + (String(v || '')).replace(/"/g, '""').trim() + '"';
                    const enquiryHeaders = ['#', 'Name', 'Email', 'Phone', 'Vehicle Type', 'Travel Date', 'Passengers', 'Status', 'Submitted'];
                    const enquiryRows = [enquiryHeaders];
                    if (data.success && data.enquiries.length > 0) {
                        data.enquiries.forEach((e, i) => {
                            const submitted = new Date(e.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                            // Use ="value" to force Excel to treat phone/dates as plain text
                            const phoneCell  = e.phone ? `="${e.phone}"` : '""';
                            const travelCell = `="${e.travel_date}"`;
                            const submitCell = `="${submitted}"`;
                            enquiryRows.push([
                                esc(i + 1), esc(e.name), esc(e.email), phoneCell,
                                esc(e.vehicle_type), travelCell, esc(e.passengers),
                                esc(e.status), submitCell
                            ]);
                        });
                    }

                    // Combine both sections with a blank line separator
                    const csv = [
                        '"FLEET REPORT"',
                        vehicleRows.map(r => r.join(',')).join('\n'),
                        '',
                        '"VEHICLE AVAILABILITY CHECKS"',
                        enquiryRows.map(r => r.join(',')).join('\n')
                    ].join('\n');

                    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
                    const a = Object.assign(document.createElement('a'), {
                        href: URL.createObjectURL(blob),
                        download: 'vehicles_report_' + new Date().toISOString().slice(0, 10) + '.csv'
                    });
                    document.body.appendChild(a); a.click(); document.body.removeChild(a);
                })
                .catch(() => showToast('❌ Could not fetch availability checks for CSV', 'error'));
        }

        // ── Print / PDF Report (Vehicles + Enquiries) ──
        function printReport() {
            // Build vehicle rows from DOM
            const tableRows = [...document.querySelectorAll('#vehicleTbody tr')].map((row, i) => {
                const cells = row.querySelectorAll('td');
                if (cells.length < 9) return '';
                const name = cells[1].querySelector('.vehicle-name')?.textContent.trim() || '';
                const plate = cells[1].querySelector('.vehicle-plate')?.textContent.trim() || '';
                const type = cells[2].textContent.trim();
                const seats = cells[3].textContent.trim();
                const fuel = cells[4].textContent.trim();
                const lugg = cells[5].textContent.trim();
                const avail = cells[6].textContent.trim();
                const cond = cells[7].textContent.trim();
                const availColor = avail === 'Available' ? '#059669' : '#D97706';
                const condColor = cond === 'Good Condition' ? '#059669' : '#DC2626';
                return `<tr>
                <td>${i + 1}</td>
                <td><strong>${name}</strong><br><span style="color:#94A3B8;font-size:.78rem">${plate}</span></td>
                <td>${type}</td><td>${seats}</td><td>${fuel}</td><td>${lugg}</td>
                <td style="color:${availColor};font-weight:700">${avail}</td>
                <td style="color:${condColor};font-weight:700">${cond}</td>
            </tr>`;
            }).join('');

            const total = document.querySelectorAll('#vehicleTbody tr').length;
            const available = [...document.querySelectorAll('#vehicleTbody tr')].filter(r => r.querySelector('td:nth-child(7)')?.textContent.trim() === 'Available').length;
            const booked = [...document.querySelectorAll('#vehicleTbody tr')].filter(r => r.querySelector('td:nth-child(7)')?.textContent.trim() === 'Booked').length;
            const maintenance = [...document.querySelectorAll('#vehicleTbody tr')].filter(r => r.querySelector('td:nth-child(8)')?.textContent.trim() === 'Under Maintenance').length;

            // Fetch enquiries, then open print window
            const fd = new FormData();
            fd.append('action', 'get_enquiries');

            fetch('../vehicles.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    const enquiries = (data.success && data.enquiries) ? data.enquiries : [];
                    const pending = enquiries.filter(e => e.status === 'Pending').length;
                    const availableCheck = enquiries.filter(e => e.status === 'Available').length;
                    const notAvailable = enquiries.filter(e => e.status === 'Not Available').length;

                    const enquiryRows = enquiries.map((e, i) => {
                        const statusColor = e.status === 'Pending' ? '#D97706' : e.status === 'Available' ? '#059669' : '#DC2626';
                        const date = new Date(e.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                        return `<tr>
                        <td>${i + 1}</td>
                        <td><strong>${e.name}</strong></td>
                        <td style="font-size:.78rem">${e.email}</td>
                        <td style="font-size:.78rem">${e.phone || '—'}</td>
                        <td>${e.vehicle_type}</td>
                        <td>${e.travel_date}</td>
                        <td style="text-align:center">${e.passengers || '—'}</td>
                        <td style="color:${statusColor};font-weight:700">${e.status}</td>
                        <td style="font-size:.78rem;color:#94A3B8">${date}</td>
                    </tr>`;
                    }).join('');

                    // Open print window
                    const w = window.open('', '_blank');
                    w.document.write(`<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Fleet & Enquiries Report – Jettransfer Travels</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:Arial,sans-serif; padding:2rem; color:#0F172A; font-size:13px; }

    /* Header */
    .rpt-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; padding-bottom:1rem; border-bottom:2px solid #0A7EA4; }
    .rpt-header-left { display:flex; align-items:center; gap:1rem; }
    .rpt-logo { width:55px; height:55px; border-radius:10px; object-fit:contain; border:1px solid #E2E8F0; }
    .rpt-header-left h1 { font-size:1.3rem; color:#0A7EA4; margin-bottom:.2rem; }
    .rpt-header-left p  { font-size:.78rem; color:#64748B; }
    .rpt-header-right { text-align:right; font-size:.75rem; color:#64748B; }
    .rpt-header-right strong { display:block; font-size:.95rem; color:#0F172A; margin-bottom:.3rem; }
    .rpt-badge { background:#E0F2FA; color:#0A7EA4; padding:.2rem .6rem; border-radius:50px; font-weight:700; }

    /* Stats grid */
    .stats { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.5rem; }
    .stat { background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:.8rem; text-align:center; }
    .stat-n { font-size:1.6rem; font-weight:800; line-height:1; }
    .stat-l { font-size:.7rem; color:#64748B; text-transform:uppercase; letter-spacing:.5px; margin-top:.2rem; }

    /* Section titles */
    .section-title { font-size:.95rem; font-weight:700; color:#0F172A; margin-bottom:.8rem; padding-bottom:.5rem; border-bottom:2px solid #E2E8F0; margin-top:2rem; }
    .section-title:first-of-type { margin-top:0; }

    /* Tables */
    table { width:100%; border-collapse:collapse; font-size:.82rem; margin-bottom:1rem; }
    th { text-align:left; padding:.6rem .8rem; background:#0F172A; color:#fff; font-size:.7rem; text-transform:uppercase; letter-spacing:.5px; }
    td { padding:.6rem .8rem; border-bottom:1px solid #E2E8F0; vertical-align:middle; }
    tr:nth-child(even) td { background:#F8FAFC; }

    /* Footer */
    .rpt-footer { margin-top:1.5rem; font-size:.72rem; color:#94A3B8; text-align:center; border-top:1px solid #E2E8F0; padding-top:.75rem; }

    @media print { body { padding:.5rem; } @page { size:A4 landscape; margin:1cm; } }
</style>
</head>
<body>

<!-- Report Header -->
<div class="rpt-header">
    <div class="rpt-header-left">
        <img src="../logo.jpeg" alt="Logo" class="rpt-logo" onerror="this.style.display='none'">
        <div>
            <h1>Jettransfer Travels</h1>
            <p>Ruwani Uyana, Matara, Sri Lanka</p>
            <p>+94 72 403 0499 &nbsp;|&nbsp; jettransfer@outlook.com</p>
        </div>
    </div>
    <div class="rpt-header-right">
        <strong>🚗 Fleet &amp; Enquiries Report</strong>
        Generated: ${new Date().toLocaleString()}<br><br>
        <span class="rpt-badge">OFFICIAL REPORT</span>
    </div>
</div>

<!-- Vehicle Stats -->
<div class="stats">
    <div class="stat"><div class="stat-n" style="color:#0A7EA4">${total}</div><div class="stat-l">Total Vehicles</div></div>
    <div class="stat"><div class="stat-n" style="color:#059669">${available}</div><div class="stat-l">Available</div></div>
    <div class="stat"><div class="stat-n" style="color:#D97706">${booked}</div><div class="stat-l">Booked</div></div>
    <div class="stat"><div class="stat-n" style="color:#DC2626">${maintenance}</div><div class="stat-l">Under Maintenance</div></div>
</div>

<!-- Vehicles Table -->
<div class="section-title">🚗 Fleet Details — All Vehicles</div>
<table>
    <thead>
        <tr><th>#</th><th>Vehicle</th><th>Type</th><th>Seats</th><th>Fuel</th><th>Luggage</th><th>Availability</th><th>Condition</th></tr>
    </thead>
    <tbody>
        ${tableRows || '<tr><td colspan="8" style="text-align:center;padding:1.5rem;color:#94A3B8">No records found.</td></tr>'}
    </tbody>
</table>

<!-- Enquiry Stats -->
<div class="stats" style="margin-top:1.5rem">
    <div class="stat"><div class="stat-n" style="color:#0A7EA4">${enquiries.length}</div><div class="stat-l">Total Requests</div></div>
    <div class="stat"><div class="stat-n" style="color:#D97706">${pending}</div><div class="stat-l">Pending</div></div>
    <div class="stat"><div class="stat-n" style="color:#059669">${availableCheck}</div><div class="stat-l">Available</div></div>
    <div class="stat"><div class="stat-n" style="color:#DC2626">${notAvailable}</div><div class="stat-l">Not Available</div></div>
</div>

<!-- Availability Checks Table -->
<div class="section-title">📋 Vehicle Availability Checks</div>
<table>
    <thead>
        <tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Vehicle Type</th><th>Travel Date</th><th>Passengers</th><th>Status</th><th>Submitted</th></tr>
    </thead>
    <tbody>
        ${enquiryRows || '<tr><td colspan="9" style="text-align:center;padding:1.5rem;color:#94A3B8">No requests found.</td></tr>'}
    </tbody>
</table>

<div class="rpt-footer">
    Jettransfer Travels &nbsp;·&nbsp; Fleet &amp; Enquiries Report &nbsp;·&nbsp;
    ${new Date().toLocaleDateString()} &nbsp;·&nbsp;
    Vehicles: ${total} &nbsp;·&nbsp; Enquiries: ${enquiries.length}
</div>

<script>window.onload = () => { window.print(); window.onafterprint = () => window.close(); }<\/script>
</body>
</html>`);
                    w.document.close();
                })
                .catch(() => showToast('❌ Could not load enquiries for report', 'error'));
        }

        // ── Sidebar toggle ──
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('show');
        }
        document.getElementById('sidebarOverlay').addEventListener('click', () => {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebarOverlay').classList.remove('show');
        });

        function logout() {
            if (!confirm('Are you sure you want to logout?')) return;
            window.location.href = 'logout.php';
        }

        // ── Tab switching ──
        function switchTab(tab) {
            const isVehicles = tab === 'vehicles';
            document.getElementById('panelVehicles').style.display = isVehicles ? 'block' : 'none';
            document.getElementById('panelEnquiries').style.display = isVehicles ? 'none' : 'block';
            document.getElementById('tabVehicles').classList.toggle('active', isVehicles);
            document.getElementById('tabEnquiries').classList.toggle('active', !isVehicles);
            if (!isVehicles) loadEnquiries();
        }

        // ── Load enquiries ──
        function loadEnquiries() {
            const fd = new FormData();
            fd.append('action', 'get_enquiries');
            fetch('../vehicles.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) throw new Error(data.message);
                    renderEnquiries(data.enquiries);
                })
                .catch(() => {
                    document.getElementById('enquiryLoadingState').style.display = 'none';
                    showToast('❌ Could not load enquiries', 'error');
                });
        }

        // ── Render enquiries ──
        function renderEnquiries(enquiries) {
            const tbody = document.getElementById('enquiryTbody');
            const loadingState = document.getElementById('enquiryLoadingState');
            const emptyState = document.getElementById('enquiryEmptyState');
            const table = document.getElementById('enquiryTable');
            const countEl = document.getElementById('enquiryCount');
            const badge = document.getElementById('enquiryBadge');

            loadingState.style.display = 'none';
            tbody.innerHTML = '';

            const pending = enquiries.filter(e => e.status === 'Pending').length;
            badge.textContent = pending;
            badge.style.display = pending > 0 ? 'inline-block' : 'none';
            countEl.textContent = `${enquiries.length} total · ${pending} pending`;

            if (enquiries.length === 0) {
                table.style.display = 'none'; emptyState.style.display = 'block'; return;
            }
            table.style.display = 'table'; emptyState.style.display = 'none';

            enquiries.forEach((e, i) => {
                const isPending = e.status === 'Pending';
                const isAvailable = e.status === 'Available';

                let statusPill;
                if (isPending) {
                    statusPill = `<span class="pill pill-pending"><span class="pill-dot"></span>Pending</span>`;
                } else if (isAvailable) {
                    statusPill = `<span class="pill pill-available-check"><span class="pill-dot"></span>Available</span>`;
                } else {
                    statusPill = `<span class="pill pill-not-available"><span class="pill-dot"></span>Not Available</span>`;
                }

                const respondBtn = isPending
                    ? `<button class="btn-respond" onclick="openRespondModal(${e.id},'${e.name.replace(/'/g,"\\'")}','${e.vehicle_type}','${e.travel_date}')">✅ Respond</button>`
                    : `<button class="btn-respond" onclick="updateEnquiry(${e.id},'Pending')" style="background:#FEF3C7;color:#D97706;">↩ Reset</button>`;

                const date = new Date(e.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });

                tbody.innerHTML += `
            <tr>
                <td style="color:var(--text-light);font-size:.8rem">${i + 1}</td>
                <td style="font-weight:700">${e.name}</td>
                <td style="font-size:.82rem;color:var(--text-mid)">${e.email}</td>
                <td style="font-size:.82rem">${e.phone || '—'}</td>
                <td><span style="background:var(--primary-light);color:var(--primary);padding:.2rem .7rem;border-radius:50px;font-size:.78rem;font-weight:600">${e.vehicle_type}</span></td>
                <td style="font-size:.85rem">${e.travel_date}</td>
                <td style="text-align:center">${e.passengers || '—'}</td>
                <td>${statusPill}</td>
                <td style="font-size:.8rem;color:var(--text-light)">${date}</td>
                <td style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap">
                    ${respondBtn}
                    <button class="btn-delete" onclick="deleteEnquiry(${e.id})">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                    </button>
                </td>
            </tr>`;
            });
        }

        // ── Open respond modal ──
        function openRespondModal(id, name, vehicleType, travelDate) {
            document.getElementById('respondEnquiryId').value = id;
            document.getElementById('respondModalName').textContent = `${name} · ${vehicleType} · ${travelDate}`;
            document.getElementById('respondModal').classList.add('show');
        }

        // ── Submit availability response ──
        function submitRespond(status) {
            const id = document.getElementById('respondEnquiryId').value;
            updateEnquiry(id, status);
            closeModal('respondModal');
        }

        // ── Update enquiry status ──
        function updateEnquiry(id, status) {
            const fd = new FormData();
            fd.append('action', 'update_enquiry');
            fd.append('id', id);
            fd.append('status', status);
            fetch('../vehicles.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) { loadEnquiries(); showToast(`✅ Status updated!`, 'success'); }
                    else showToast('❌ ' + data.message, 'error');
                });
        }

        // ── Delete enquiry ──
        function deleteEnquiry(id) {
            if (!confirm('Delete this availability check request?')) return;
            const fd = new FormData();
            fd.append('action', 'delete_enquiry'); fd.append('id', id);
            fetch('../vehicles.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) { loadEnquiries(); showToast('✅ Request deleted!', 'success'); }
                    else showToast('❌ ' + data.message, 'error');
                });
        }

        // ── Load pending badge on page load ──
        (function checkPendingBadge() {
            const fd = new FormData();
            fd.append('action', 'get_enquiries');
            fetch('../vehicles.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    const pending = data.enquiries.filter(e => e.status === 'Pending').length;
                    const badge = document.getElementById('enquiryBadge');
                    if (pending > 0) { badge.textContent = pending; badge.style.display = 'inline-block'; }
                });
        })();

    </script>
</body>

</html>