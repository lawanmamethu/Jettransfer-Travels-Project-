<?php
// ── Session check ──
session_start();
date_default_timezone_set('Asia/Colombo');
if (!isset($_SESSION['jt_admin']) || $_SESSION['jt_admin'] !== true) {
    header('Location: login.php');
    exit;
}

// ── Fetch all vehicles ──
require_once '../db_vehicle.php';
$result   = $conn->query("SELECT * FROM vehicles ORDER BY id ASC");
$vehicles = [];
while ($row = $result->fetch_assoc()) {
    $vehicles[] = $row;
}

// ── Fetch all enquiries ──
$eResult   = $conn->query("SELECT * FROM enquiries ORDER BY created_at DESC");
$enquiries = [];
while ($row = $eResult->fetch_assoc()) {
    $enquiries[] = $row;
}
$totalEnquiries  = count($enquiries);
$pendingCount    = count(array_filter($enquiries, fn($e) => $e['status'] === 'Pending'));
$respondedCount  = count(array_filter($enquiries, fn($e) => $e['status'] === 'Responded'));

// ── Stats ──
$total       = count($vehicles);
$available   = count(array_filter($vehicles, fn($v) => $v['availability'] === 'Available'));
$booked      = count(array_filter($vehicles, fn($v) => $v['availability'] === 'Booked'));
$maintenance = count(array_filter($vehicles, fn($v) => $v['condition_status'] === 'Under Maintenance'));
$generated   = date('F d, Y  h:i A');

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Fleet Report – Jettransfer Travels</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f8fafc;
            color: #0F172A;
        }

        /* ── Print button (hidden when printing) ── */
        .print-bar {
            background: #0A7EA4;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .print-bar span {
            color: white;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .print-actions {
            display: flex;
            gap: 0.8rem;
        }

        .btn-print {
            padding: 0.6rem 1.5rem;
            background: white;
            color: #0A7EA4;
            border: none;
            border-radius: 50px;
            font-family: Arial, sans-serif;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-print:hover {
            background: #E0F2FA;
        }

        .btn-back {
            padding: 0.6rem 1.5rem;
            background: transparent;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: 50px;
            font-family: Arial, sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .btn-back:hover {
            border-color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        /* ── Report container ── */
        .report {
            max-width: 1000px;
            margin: 2rem auto;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }

        /* ── Header ── */
        .report-header {
            background: linear-gradient(135deg, #0F172A 0%, #0A7EA4 100%);
            padding: 2.5rem 3rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }

        .header-logo {
            width: 65px;
            height: 65px;
            border-radius: 12px;
            object-fit: contain;
            background: white;
            padding: 4px;
        }

        .header-text h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: white;
            letter-spacing: -0.5px;
        }

        .header-text p {
            font-size: 0.88rem;
            color: rgba(255, 255, 255, 0.65);
            margin-top: 0.2rem;
        }

        .header-right {
            text-align: right;
        }

        .report-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
        }

        .report-meta {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 0.3rem;
        }

        .report-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.8rem;
            border-radius: 50px;
            margin-top: 0.5rem;
            letter-spacing: 0.5px;
        }

        /* ── Stats bar ── */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            border-bottom: 2px solid #E2E8F0;
        }

        .stat-box {
            padding: 1.5rem;
            text-align: center;
            border-right: 1px solid #E2E8F0;
        }

        .stat-box:last-child {
            border-right: none;
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 800;
            font-family: Arial, sans-serif;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.78rem;
            color: #64748B;
            font-weight: 600;
            margin-top: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 4px;
            vertical-align: middle;
        }

        /* ── Table section ── */
        .table-section {
            padding: 2rem 2.5rem 2.5rem;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 1.2rem;
            padding-bottom: 0.6rem;
            border-bottom: 2px solid #E2E8F0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }

        thead th {
            background: #0F172A;
            color: white;
            padding: 0.85rem 1rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        thead th:first-child {
            border-radius: 8px 0 0 0;
        }

        thead th:last-child {
            border-radius: 0 8px 0 0;
        }

        tbody tr {
            border-bottom: 1px solid #E2E8F0;
        }

        tbody tr:last-child {
            border-bottom: none;
        }

        tbody tr:nth-child(even) {
            background: #F8FAFC;
        }

        tbody tr:hover {
            background: #E0F2FA;
        }

        tbody td {
            padding: 0.85rem 1rem;
            vertical-align: middle;
            color: #0F172A;
        }

        .td-num {
            color: #94A3B8;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .td-name {
            font-weight: 700;
        }

        .td-plate {
            font-size: 0.8rem;
            color: #64748B;
            margin-top: 2px;
        }

        /* Status pills */
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .pill-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .pill-available {
            background: #D1FAE5;
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
            background: #D1FAE5;
            color: #059669;
        }

        .pill-good .pill-dot {
            background: #059669;
        }

        .pill-maintenance {
            background: #FEE2E2;
            color: #DC2626;
        }

        .pill-maintenance .pill-dot {
            background: #DC2626;
        }

        /* ── Footer ── */
        .report-footer {
            background: #F8FAFC;
            border-top: 2px solid #E2E8F0;
            padding: 1.2rem 2.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #94A3B8;
        }

        /* ── Print styles ── */
        @media print {
            body {
                background: white;
            }

            .print-bar {
                display: none !important;
            }

            .report {
                margin: 0;
                border-radius: 0;
                box-shadow: none;
                max-width: 100%;
            }

            tbody tr:hover {
                background: inherit;
            }

            @page {
                size: A4 landscape;
                margin: 1cm;
            }
        }
    </style>
</head>

<body>

    <!-- Print bar (hidden when printing) -->
    <div class="print-bar">
        <span>📄 Vehicle Fleet Report — Jettransfer Travels</span>
        <div class="print-actions">
            <a href="vehicles.php" class="btn-back">← Back to Admin</a>
            <button class="btn-print" onclick="window.print()">🖨️ Save as PDF / Print</button>
        </div>
    </div>

    <!-- Report -->
    <div class="report">

        <!-- Header -->
        <div class="report-header">
            <div class="header-left">
                <img src="../logo.jpeg" alt="Jettransfer Logo" class="header-logo">
                <div class="header-text">
                    <h1>Jettransfer Travels</h1>
                    <p>Ruwani Uyana, Matara, Sri Lanka</p>
                    <p>+94 72 403 0499 | jettransfer@outlook.com</p>
                </div>
            </div>
            <div class="header-right">
                <div class="report-title">🚗 Vehicle Fleet Report</div>
                <div class="report-meta">Generated:
                    <?= $generated ?>
                </div>
                <div class="report-badge">OFFICIAL REPORT</div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-bar">
            <div class="stat-box">
                <div class="stat-number" style="color:#0A7EA4">
                    <?= $total ?>
                </div>
                <div class="stat-label">Total Vehicles</div>
            </div>
            <div class="stat-box">
                <div class="stat-number" style="color:#059669">
                    <?= $available ?>
                </div>
                <div class="stat-label">
                    <span class="stat-dot" style="background:#059669"></span>Available
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-number" style="color:#D97706">
                    <?= $booked ?>
                </div>
                <div class="stat-label">
                    <span class="stat-dot" style="background:#D97706"></span>Booked
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-number" style="color:#DC2626">
                    <?= $maintenance ?>
                </div>
                <div class="stat-label">
                    <span class="stat-dot" style="background:#DC2626"></span>Under Maintenance
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-number" style="color:#7C3AED">
                    <?= $totalEnquiries ?>
                </div>
                <div class="stat-label">
                    <span class="stat-dot" style="background:#7C3AED"></span>Total Enquiries
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-number" style="color:#D97706">
                    <?= $pendingCount ?>
                </div>
                <div class="stat-label">
                    <span class="stat-dot" style="background:#D97706"></span>Pending
                </div>
            </div>
        </div>

        <!-- Vehicles Table -->
        <div class="table-section">
            <div class="section-title">🚗 Fleet Details — All Vehicles</div>
            <table>
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
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $i => $v): ?>
                    <tr>
                        <td class="td-num">
                            <?= $i + 1 ?>
                        </td>
                        <td>
                            <div class="td-name">
                                <?= htmlspecialchars($v['name']) ?>
                            </div>
                            <div class="td-plate">
                                <?= htmlspecialchars($v['plate']) ?>
                            </div>
                        </td>
                        <td>
                            <?= htmlspecialchars($v['type']) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($v['seats']) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($v['fuel']) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($v['luggage']) ?>
                        </td>
                        <td>
                            <?php if ($v['availability'] === 'Available'): ?>
                            <span class="pill pill-available"><span class="pill-dot"></span>Available</span>
                            <?php else: ?>
                            <span class="pill pill-booked"><span class="pill-dot"></span>Booked</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($v['condition_status'] === 'Good Condition'): ?>
                            <span class="pill pill-good"><span class="pill-dot"></span>Good Condition</span>
                            <?php else: ?>
                            <span class="pill pill-maintenance"><span class="pill-dot"></span>Under Maintenance</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Enquiries Table -->
        <div class="table-section" style="border-top: 2px solid #E2E8F0;">
            <div class="section-title">📋 Vehicle Enquiries — All Submissions</div>
            <?php if (empty($enquiries)): ?>
            <p style="color:#94A3B8; font-size:0.9rem; padding:1rem 0;">No enquiries submitted yet.</p>
            <?php else: ?>
            <table>
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
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($enquiries as $i => $e): ?>
                    <tr>
                        <td class="td-num">
                            <?= $i + 1 ?>
                        </td>
                        <td class="td-name">
                            <?= htmlspecialchars($e['name']) ?>
                        </td>
                        <td style="font-size:0.82rem;color:#64748B">
                            <?= htmlspecialchars($e['email']) ?>
                        </td>
                        <td style="font-size:0.82rem">
                            <?= htmlspecialchars($e['phone'] ?: '—') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($e['vehicle_type']) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($e['travel_date']) ?>
                        </td>
                        <td style="text-align:center">
                            <?= htmlspecialchars($e['passengers'] ?: '—') ?>
                        </td>
                        <td>
                            <?php if ($e['status'] === 'Pending'): ?>
                            <span class="pill pill-booked"><span class="pill-dot"></span>Pending</span>
                            <?php else: ?>
                            <span class="pill pill-available"><span class="pill-dot"></span>Responded</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:0.8rem;color:#94A3B8">
                            <?= date('M d, Y', strtotime($e['created_at'])) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="report-footer">
            <span>Jettransfer Travels — Vehicle Fleet & Enquiries Report</span>
            <span>Generated on
                <?= $generated ?> | Vehicles:
                <?= $total ?> | Enquiries:
                <?= $totalEnquiries ?>
            </span>
        </div>

    </div>

    <script>
        // Auto trigger print dialog when page loads
        window.onload = function () {
            setTimeout(() => window.print(), 800);
        };
    </script>

</body>

</html>