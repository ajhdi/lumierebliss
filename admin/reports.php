<?php
// /admin/reports.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}

// ─── Date Range Filter ────────────────────────────────────────────────────────
$filter    = $_GET['filter']     ?? 'monthly';
$date_from = $_GET['date_from']  ?? date('Y-m-01');
$date_to   = $_GET['date_to']    ?? date('Y-m-d');

switch ($filter) {
    case 'daily':
        $date_from = date('Y-m-d');
        $date_to   = date('Y-m-d');
        break;
    case 'weekly':
        $date_from = date('Y-m-d', strtotime('monday this week'));
        $date_to   = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'monthly':
        $date_from = date('Y-m-01');
        $date_to   = date('Y-m-t');
        break;
    case 'yearly':
        $date_from = date('Y-01-01');
        $date_to   = date('Y-12-31');
        break;
    case 'custom':
        $date_from = $_GET['date_from'] ?? date('Y-m-01');
        $date_to   = $_GET['date_to']   ?? date('Y-m-d');
        break;
}

// ─── 1. Overview Metrics ──────────────────────────────────────────────────────

// Total lifetime revenue (confirmed + completed)
$total_revenue = $pdo->query(
    "SELECT COALESCE(SUM(total_amount),0) FROM appointments WHERE status != 'cancelled'"
)->fetchColumn();

// Revenue within selected range
$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(total_amount),0) FROM appointments
     WHERE status != 'cancelled'
       AND appointment_date BETWEEN ? AND ?"
);
$stmt->execute([$date_from, $date_to]);
$range_revenue = $stmt->fetchColumn();

// Total transactions (all time)
$total_transactions = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();

// Transactions within range
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM appointments
     WHERE appointment_date BETWEEN ? AND ?"
);
$stmt->execute([$date_from, $date_to]);
$range_transactions = $stmt->fetchColumn();

// Completed within range
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM appointments
     WHERE status='completed' AND appointment_date BETWEEN ? AND ?"
);
$stmt->execute([$date_from, $date_to]);
$range_completed = $stmt->fetchColumn();

// Cancelled within range
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM appointments
     WHERE status='cancelled' AND appointment_date BETWEEN ? AND ?"
);
$stmt->execute([$date_from, $date_to]);
$range_cancelled = $stmt->fetchColumn();

// Confirmed (pending) within range
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM appointments
     WHERE status='confirmed' AND appointment_date BETWEEN ? AND ?"
);
$stmt->execute([$date_from, $date_to]);
$range_confirmed = $stmt->fetchColumn();

// Active therapists
$active_therapists = $pdo->query(
    "SELECT COUNT(*) FROM therapists WHERE status='active'"
)->fetchColumn();

// Total registered users
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Active rooms
$active_rooms = $pdo->query(
    "SELECT COUNT(*) FROM rooms WHERE status='active'"
)->fetchColumn();

// ─── 2. Monthly Revenue (last 12 months) for Chart ───────────────────────────
$monthly_revenue_rows = $pdo->query("
    SELECT DATE_FORMAT(appointment_date,'%b %Y') AS month_label,
           DATE_FORMAT(appointment_date,'%Y-%m') AS month_key,
           COALESCE(SUM(total_amount),0)          AS revenue
    FROM appointments
    WHERE status != 'cancelled'
      AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY month_key, month_label
    ORDER BY month_key ASC
")->fetchAll(PDO::FETCH_ASSOC);

$chart_labels  = array_column($monthly_revenue_rows, 'month_label');
$chart_revenue = array_column($monthly_revenue_rows, 'revenue');

// ─── 3. Appointments per Month (last 12 months) ───────────────────────────────
$monthly_appt_rows = $pdo->query("
    SELECT DATE_FORMAT(appointment_date,'%b %Y') AS month_label,
           DATE_FORMAT(appointment_date,'%Y-%m') AS month_key,
           COUNT(*)                               AS total
    FROM appointments
    WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY month_key, month_label
    ORDER BY month_key ASC
")->fetchAll(PDO::FETCH_ASSOC);

$chart_appt_labels = array_column($monthly_appt_rows, 'month_label');
$chart_appt_total  = array_column($monthly_appt_rows, 'total');

// ─── 4. Appointment Status Breakdown ─────────────────────────────────────────
$status_rows = $pdo->query("
    SELECT status, COUNT(*) AS total
    FROM appointments
    GROUP BY status
")->fetchAll(PDO::FETCH_ASSOC);

$status_labels = array_column($status_rows, 'status');
$status_totals = array_column($status_rows, 'total');

// ─── 5. Top 5 Services ───────────────────────────────────────────────────────
$top_services = $pdo->query("
    SELECT name, COUNT(*) AS bookings FROM (
        SELECT t.name FROM appointments a
        JOIN treatments t ON a.treatment_id = t.treatment_id
        WHERE a.treatment_id IS NOT NULL
        UNION ALL
        SELECT p.name FROM appointments a
        JOIN packages p ON a.package_id = p.package_id
        WHERE a.package_id IS NOT NULL
    ) AS services
    GROUP BY name
    ORDER BY bookings DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ─── 6. Top 5 Therapists by Appointments ─────────────────────────────────────
$top_therapists = $pdo->query("
    SELECT CONCAT(t.first_name,' ',t.last_name) AS name,
           COUNT(a.appointment_id) AS total
    FROM therapists t
    LEFT JOIN appointments a ON t.therapist_id = a.therapist_id
    GROUP BY t.therapist_id
    ORDER BY total DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ─── 7. Recent Appointments within range ─────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT a.appointment_id,
           CONCAT(u.first_name,' ',u.last_name) AS client,
           CONCAT(t.first_name,' ',t.last_name) AS therapist,
           a.appointment_date, a.appointment_time,
           a.total_amount, a.status
    FROM appointments a
    JOIN users      u ON a.user_id      = u.user_id
    JOIN therapists t ON a.therapist_id = t.therapist_id
    WHERE a.appointment_date BETWEEN ? AND ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 50
");
$stmt->execute([$date_from, $date_to]);
$recent_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── 8. Peak booking hour ────────────────────────────────────────────────────
$peak = $pdo->query("
    SELECT HOUR(appointment_time) AS hour, COUNT(*) AS cnt
    FROM appointments
    GROUP BY hour ORDER BY cnt DESC LIMIT 1
")->fetch();
$peak_hour = $peak ? date("g:i A", strtotime($peak['hour'].":00")) : "N/A";

// ─── Admin name ───────────────────────────────────────────────────────────────
$admin = $pdo->prepare("SELECT username FROM admins WHERE admin_id = ?");
$admin->execute([$_SESSION['admin_id']]);
$admin_name = $admin->fetchColumn() ?: 'Administrator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports — Lumiére &amp; Bliss</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        /* ─── Tokens ─────────────────────────────────────────────── */
        :root {
            --white:       #ffffff;
            --cream:       #fdfbf7;
            --gold:        #c9a96e;
            --gold-light:  #e8d5b0;
            --gold-dim:    rgba(201,169,110,.13);
            --dark:        #1a1a1a;
            --dark-soft:   #2e2e2e;
            --muted:       #8a8070;
            --border:      rgba(201,169,110,.22);
            --sidebar-w:   270px;
            --radius-lg:   18px;
            --radius-md:   12px;
            --shadow:      0 8px 32px rgba(26,26,26,.07);
            --shadow-deep: 0 16px 48px rgba(26,26,26,.13);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--cream);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--dark);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ─── Scrollbar ──────────────────────────────────────────── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--cream); }
        ::-webkit-scrollbar-thumb { background: var(--gold-light); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--gold); }

        /* ─── Layout ─────────────────────────────────────────────── */
        .main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            padding: 48px 44px 60px;
            transition: margin .35s cubic-bezier(.4,0,.2,1);
        }

        /* ─── Topbar ─────────────────────────────────────────────── */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 10px;
        }
        .topbar-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            font-size: 2.4rem;
            color: var(--dark);
            line-height: 1.1;
        }
        .topbar-title span {
            display: block;
            font-family: 'DM Sans', sans-serif;
            font-size: .75rem;
            font-weight: 500;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 6px;
        }
        .gold-rule {
            width: 48px; height: 2px;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
            border-radius: 2px;
            margin: 16px 0 36px;
        }
        .section-eyebrow {
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 16px;
        }

        /* ─── Filter Bar ─────────────────────────────────────────── */
        .filter-bar {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 22px 28px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(201,169,110,.1);
            margin-bottom: 28px;
            display: flex;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
        }
        .filter-bar .form-label {
            font-size: .63rem;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 6px;
            display: block;
        }
        .filter-bar .form-control,
        .filter-bar .form-select {
            border: 1.5px solid rgba(201,169,110,.25);
            border-radius: var(--radius-md);
            background: var(--cream);
            color: var(--dark);
            font-size: .84rem;
            font-family: 'DM Sans', sans-serif;
            padding: 9px 13px;
            transition: border-color .2s, box-shadow .2s;
        }
        .filter-bar .form-control:focus,
        .filter-bar .form-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(201,169,110,.12);
            outline: none;
            background: var(--white);
        }
        .btn-filter {
            padding: 10px 24px;
            background: var(--dark);
            color: var(--gold-light);
            border: none;
            border-radius: 50px;
            font-size: .82rem;
            font-weight: 600;
            letter-spacing: .06em;
            cursor: pointer;
            transition: background .22s, color .22s;
            white-space: nowrap;
        }
        .btn-filter:hover { background: var(--dark-soft); color: var(--gold); }
        #customRange { display: none; gap: 12px; align-items: flex-end; }
        #customRange.visible { display: flex; }

        /* ─── Export Buttons ─────────────────────────────────────── */
        .export-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }
        .btn-export {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 20px;
            border-radius: 50px;
            font-size: .78rem;
            font-weight: 600;
            letter-spacing: .06em;
            cursor: pointer;
            border: 1.5px solid var(--border);
            background: var(--white);
            color: var(--dark-soft);
            transition: background .2s, color .2s, border-color .2s;
        }
        .btn-export:hover { background: var(--gold-dim); border-color: var(--gold); color: var(--dark); }
        .btn-export.primary { background: var(--dark); color: var(--gold-light); border-color: var(--dark); }
        .btn-export.primary:hover { background: var(--dark-soft); color: var(--gold); }

        /* ─── Stat Grid ──────────────────────────────────────────── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 24px 22px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(201,169,110,.1);
            position: relative;
            overflow: hidden;
            transition: transform .25s, box-shadow .25s;
        }
        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .3s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-deep); }
        .stat-card:hover::after { transform: scaleX(1); }
        .stat-icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            background: var(--gold-dim);
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; color: var(--gold);
            margin-bottom: 14px;
        }
        .stat-label {
            font-size: .62rem; font-weight: 700;
            letter-spacing: .18em; text-transform: uppercase;
            color: var(--muted); margin-bottom: 5px;
        }
        .stat-value {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600; font-size: 2.2rem;
            color: var(--dark); line-height: 1;
            margin-bottom: 8px;
        }
        .stat-sub { font-size: .73rem; color: var(--muted); }
        .stat-sub.green { color: #5a8a5a; }
        .stat-sub.red   { color: #b43c3c; }
        .stat-sub.gold  { color: var(--gold); }

        /* ─── Chart Cards ────────────────────────────────────────── */
        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }
        .chart-grid.single { grid-template-columns: 1fr; }
        .chart-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 28px 30px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(201,169,110,.1);
        }
        .chart-card-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600; font-size: 1.2rem;
            color: var(--dark); margin-bottom: 6px;
        }
        .chart-card-sub {
            font-size: .72rem; color: var(--muted);
            margin-bottom: 22px;
        }
        .chart-wrap { position: relative; height: 240px; }

        /* ─── Two-Col Layout ─────────────────────────────────────── */
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }

        /* ─── Leaderboard ────────────────────────────────────────── */
        .leaderboard-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 28px 30px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(201,169,110,.1);
        }
        .leaderboard-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600; font-size: 1.2rem;
            color: var(--dark); margin-bottom: 20px;
        }
        .lb-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(201,169,110,.08);
        }
        .lb-item:last-child { border-bottom: none; }
        .lb-rank {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600; font-size: 1.3rem;
            color: var(--gold-light); width: 24px;
            text-align: center; flex-shrink: 0;
        }
        .lb-rank.top { color: var(--gold); }
        .lb-name { flex: 1; font-size: .86rem; font-weight: 600; color: var(--dark-soft); }
        .lb-count {
            font-size: .78rem; font-weight: 700;
            color: var(--gold);
            background: var(--gold-dim);
            border: 1px solid var(--border);
            padding: 3px 10px; border-radius: 50px;
        }

        /* ─── Table Card ─────────────────────────────────────────── */
        .table-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            border: 1px solid rgba(201,169,110,.1);
            overflow: hidden;
            margin-bottom: 28px;
        }
        .table-card-header {
            padding: 20px 28px 16px;
            border-bottom: 1px solid rgba(201,169,110,.12);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .table-card-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600; font-size: 1.2rem; color: var(--dark);
        }
        .report-table { width: 100%; border-collapse: collapse; }
        .report-table thead tr {
            background: var(--cream);
            border-bottom: 2px solid rgba(201,169,110,.15);
        }
        .report-table thead th {
            font-size: .62rem; font-weight: 700;
            letter-spacing: .18em; text-transform: uppercase;
            color: var(--muted); padding: 12px 18px; white-space: nowrap;
        }
        .report-table tbody tr {
            border-bottom: 1px solid rgba(201,169,110,.07);
            transition: background .15s;
        }
        .report-table tbody tr:last-child { border-bottom: none; }
        .report-table tbody tr:hover { background: rgba(201,169,110,.04); }
        .report-table tbody td {
            padding: 13px 18px; font-size: .84rem;
            color: var(--dark-soft); vertical-align: middle;
        }

        /* Status badges */
        .badge-status {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 10px; border-radius: 50px;
            font-size: .65rem; font-weight: 700;
            letter-spacing: .1em; text-transform: uppercase;
        }
        .badge-status::before {
            content: ''; width: 4px; height: 4px;
            border-radius: 50%; background: currentColor;
        }
        .badge-confirmed  { background: rgba(201,169,110,.13); color: var(--gold); }
        .badge-completed  { background: rgba(90,138,90,.1);    color: #5a8a5a; }
        .badge-cancelled  { background: rgba(180,60,60,.08);   color: #b43c3c; }

        /* Empty state */
        .empty-state { text-align: center; padding: 52px 24px; }
        .empty-state i { font-size: 2.2rem; color: var(--gold); opacity: .3; display: block; margin-bottom: 14px; }
        .empty-state p { font-family: 'Cormorant Garamond', serif; font-size: 1.1rem; color: var(--muted); }

        /* ─── Print Styles ───────────────────────────────────────── */
        @media print {
            .sidebar, .filter-bar, .export-bar, .mobile-toggle,
            .sidebar-overlay { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 20px !important; }
            .stat-card, .chart-card, .leaderboard-card,
            .table-card { box-shadow: none !important; border: 1px solid #ddd !important; }
            .print-header { display: block !important; }
        }
        .print-header {
            display: none;
            margin-bottom: 24px;
            border-bottom: 2px solid var(--gold);
            padding-bottom: 16px;
        }
        .print-header h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.8rem; color: var(--dark);
        }
        .print-header p { font-size: .82rem; color: var(--muted); }

        /* ─── Responsive ─────────────────────────────────────────── */
        .sidebar-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.55); z-index: 999;
        }
        .mobile-toggle {
            display: none; position: fixed;
            top: 18px; left: 18px; z-index: 1100;
            background: var(--dark); border: 1px solid var(--border);
            color: var(--gold); width: 42px; height: 42px;
            border-radius: 10px; align-items: center;
            justify-content: center; font-size: 1.2rem; cursor: pointer;
        }
        @media (max-width: 1200px) { .stat-grid { grid-template-columns: repeat(2,1fr); } }
        @media (max-width: 991px) {
            .main-content { margin-left: 0; padding: 80px 20px 40px; }
            .chart-grid, .two-col { grid-template-columns: 1fr; }
            .mobile-toggle { display: flex; }
            .sidebar-overlay.visible { display: block; }
        }
        @media (max-width: 600px) {
            .stat-grid { grid-template-columns: 1fr; }
            .topbar { flex-direction: column; align-items: flex-start; gap: 10px; }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .stat-card   { animation: fadeUp .45s ease both; }
        .stat-card:nth-child(1) { animation-delay: .04s; }
        .stat-card:nth-child(2) { animation-delay: .10s; }
        .stat-card:nth-child(3) { animation-delay: .16s; }
        .stat-card:nth-child(4) { animation-delay: .22s; }
        .stat-card:nth-child(5) { animation-delay: .28s; }
        .stat-card:nth-child(6) { animation-delay: .34s; }
        .chart-card, .leaderboard-card, .table-card { animation: fadeUp .5s .3s ease both; }

        /* ─── No Data Overlay ────────────────────────────────────── */
        /* FIX #3: Removed stray backticks and misplaced braces */
        .no-data-overlay {
            display: none;
            position: absolute;
            inset: 0;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: var(--white);
            border-radius: var(--radius-md);
        }
        .no-data-overlay i {
            font-size: 2rem;
            color: var(--gold);
            opacity: .3;
        }
        .no-data-overlay p {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.1rem;
            color: var(--muted);
        }
        .no-data-overlay.visible {
            display: flex;
        }
    </style>
</head>
<body>

<button class="mobile-toggle" id="mobileToggle" aria-label="Open menu">
    <i class="bi bi-list"></i>
</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<?php require_once '../includes/sidebar.php'; ?>

<!-- ── Print Header (visible only when printing) ────────────────────── -->
<div class="print-header">
    <h1>Lumiére &amp; Bliss — System Report</h1>
    <p>
        Generated by: <strong><?= htmlspecialchars($admin_name) ?></strong> &nbsp;|&nbsp;
        Date: <strong><?= date('F j, Y') ?></strong> &nbsp;|&nbsp;
        Period: <strong><?= date('M d, Y', strtotime($date_from)) ?> – <?= date('M d, Y', strtotime($date_to)) ?></strong>
    </p>
</div>

<!-- ── Main Content ─────────────────────────────────────────────────── -->
<div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-title">
            <span>Analytics &amp; Reports</span>
            System Reports
        </div>
    </div>
    <div class="gold-rule"></div>

    <!-- ── Filter Bar ───────────────────────────────────────────────── -->
    <p class="section-eyebrow">Filter Period</p>
    <form method="GET" id="filterForm">
        <div class="filter-bar">
            <div>
                <label class="form-label">Report Period</label>
                <select name="filter" id="filterSelect" class="form-select" style="width:160px;"
                        onchange="toggleCustom(this.value)">
                    <option value="daily"   <?= $filter==='daily'  ?'selected':''?>>Daily</option>
                    <option value="weekly"  <?= $filter==='weekly' ?'selected':''?>>Weekly</option>
                    <option value="monthly" <?= $filter==='monthly'?'selected':''?>>Monthly</option>
                    <option value="yearly"  <?= $filter==='yearly' ?'selected':''?>>Yearly</option>
                    <option value="custom"  <?= $filter==='custom' ?'selected':''?>>Custom Range</option>
                </select>
            </div>
            <div id="customRange" class="<?= $filter==='custom'?'visible':''?>">
                <div>
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control"
                           value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div>
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control"
                           value="<?= htmlspecialchars($date_to) ?>">
                </div>
            </div>
            <button type="submit" class="btn-filter">
                Apply <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>
    </form>

    <!-- ── Export Bar ───────────────────────────────────────────────── -->
    <div class="export-bar">
        <button class="btn-export primary" onclick="window.print()">
            <i class="bi bi-printer"></i> Print Report
        </button>
        <button class="btn-export" onclick="exportCSV()">
            <i class="bi bi-filetype-csv"></i> Export CSV
        </button>
        <button class="btn-export" onclick="exportPDF()">
            <i class="bi bi-file-earmark-pdf"></i> Export PDF
        </button>
    </div>

    <!-- ── Overview Stat Cards ──────────────────────────────────────── -->
    <p class="section-eyebrow">Overview — <?= date('M d', strtotime($date_from)) ?> to <?= date('M d, Y', strtotime($date_to)) ?></p>
    <div class="stat-grid">

        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-currency-exchange"></i></div>
            <div class="stat-label">Period Revenue</div>
            <div class="stat-value">₱<?= number_format($range_revenue, 0) ?></div>
            <div class="stat-sub gold">Lifetime: ₱<?= number_format($total_revenue, 0) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-label">Total Bookings</div>
            <div class="stat-value"><?= $range_transactions ?></div>
            <div class="stat-sub">All time: <?= $total_transactions ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
            <div class="stat-label">Completed</div>
            <div class="stat-value"><?= $range_completed ?></div>
            <div class="stat-sub green"><i class="bi bi-circle-fill" style="font-size:.4rem"></i> Within period</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-label">Confirmed</div>
            <div class="stat-value"><?= $range_confirmed ?></div>
            <div class="stat-sub gold">Upcoming bookings</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
            <div class="stat-label">Cancelled</div>
            <div class="stat-value"><?= $range_cancelled ?></div>
            <div class="stat-sub red">Within period</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
            <div class="stat-label">Active Therapists</div>
            <div class="stat-value"><?= $active_therapists ?></div>
            <div class="stat-sub green">Currently active</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-people"></i></div>
            <div class="stat-label">Registered Users</div>
            <div class="stat-value"><?= $total_users ?></div>
            <div class="stat-sub">Total accounts</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
            <div class="stat-label">Peak Hour</div>
            <div class="stat-value" style="font-size:1.6rem"><?= $peak_hour ?></div>
            <div class="stat-sub">Most bookings at</div>
        </div>

    </div>

    <!-- ── Charts Row 1 ─────────────────────────────────────────────── -->
    <p class="section-eyebrow">Revenue &amp; Booking Trends</p>
    <div class="chart-grid">

        <div class="chart-card">
            <div class="chart-card-title">Monthly Revenue</div>
            <div class="chart-card-sub">Last 12 months — confirmed &amp; completed</div>
            <div class="chart-wrap" id="revenueWrap">
                <canvas id="revenueChart"></canvas>
                <div class="no-data-overlay" id="revenueEmpty">
                    <i class="bi bi-bar-chart"></i>
                    <p>No revenue data available</p>
                </div>
            </div>
        </div>

        <!-- FIX #1: Was using statusChart/statusWrap/statusEmpty (duplicate IDs).
                     Corrected to apptChart/apptWrap/apptEmpty. -->
        <div class="chart-card">
            <div class="chart-card-title">Appointment Volume</div>
            <div class="chart-card-sub">Total bookings per month (last 12 months)</div>
            <div class="chart-wrap" id="apptWrap">
                <canvas id="apptChart"></canvas>
                <div class="no-data-overlay" id="apptEmpty">
                    <i class="bi bi-graph-up"></i>
                    <p>No appointment data available</p>
                </div>
            </div>
        </div>

    </div>

    <!-- ── Charts Row 2 ─────────────────────────────────────────────── -->
    <div class="chart-grid">

        <!-- FIX #2: Was missing the closing </div> for .chart-wrap -->
        <div class="chart-card">
            <div class="chart-card-title">Appointment Status Breakdown</div>
            <div class="chart-card-sub">All-time distribution</div>
            <div class="chart-wrap" style="height:220px;" id="statusWrap">
                <canvas id="statusChart"></canvas>
                <div class="no-data-overlay" id="statusEmpty">
                    <i class="bi bi-pie-chart"></i>
                    <p>No status data available</p>
                </div>
            </div><!-- /.chart-wrap -->
        </div><!-- /.chart-card -->

        <!-- Top Services Chart -->
        <div class="chart-card">
            <div class="chart-card-title">Top Services by Bookings</div>
            <div class="chart-card-sub">Most booked treatments &amp; packages</div>
            <div class="chart-wrap" id="servicesWrap">
                <canvas id="servicesChart"></canvas>
                <div class="no-data-overlay" id="servicesEmpty">
                    <i class="bi bi-droplet-half"></i>
                    <p>No service data available</p>
                </div>
            </div>
        </div>

    </div>

    <!-- ── Leaderboards ──────────────────────────────────────────────── -->
    <p class="section-eyebrow">Top Performers</p>
    <div class="two-col">

        <!-- Top Services -->
        <div class="leaderboard-card">
            <div class="leaderboard-title">Most Booked Services</div>
            <?php if (empty($top_services)): ?>
                <div class="empty-state">
                    <i class="bi bi-droplet-half"></i>
                    <p>No service data yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($top_services as $i => $svc): ?>
                <div class="lb-item">
                    <div class="lb-rank <?= $i===0?'top':''?>"><?= $i+1 ?></div>
                    <div class="lb-name"><?= htmlspecialchars($svc['name']) ?></div>
                    <div class="lb-count"><?= $svc['bookings'] ?> bookings</div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Top Therapists -->
        <div class="leaderboard-card">
            <div class="leaderboard-title">Top Therapists by Appointments</div>
            <?php if (empty($top_therapists)): ?>
                <div class="empty-state">
                    <i class="bi bi-person-badge"></i>
                    <p>No therapist data yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($top_therapists as $i => $th): ?>
                <div class="lb-item">
                    <div class="lb-rank <?= $i===0?'top':''?>"><?= $i+1 ?></div>
                    <div class="lb-name"><?= htmlspecialchars($th['name']) ?></div>
                    <div class="lb-count"><?= $th['total'] ?> sessions</div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- ── Recent Appointments Table ────────────────────────────────── -->
    <p class="section-eyebrow">Appointment Log</p>
    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-title">
                Appointments
                <span style="font-family:'DM Sans',sans-serif;font-size:.8rem;color:var(--muted);font-weight:500;">
                    (<?= count($recent_appointments) ?> records)
                </span>
            </div>
            <span style="font-size:.75rem;color:var(--muted);">
                <?= date('M d, Y', strtotime($date_from)) ?> — <?= date('M d, Y', strtotime($date_to)) ?>
            </span>
        </div>
        <div style="overflow-x:auto;">
            <table class="report-table" id="apptTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Therapist</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_appointments)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="bi bi-calendar-x"></i>
                                    <p>No appointments in this period</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_appointments as $row): ?>
                        <tr>
                            <td style="color:var(--muted);font-size:.78rem;">#<?= $row['appointment_id'] ?></td>
                            <td style="font-weight:600;"><?= htmlspecialchars($row['client']) ?></td>
                            <td><?= htmlspecialchars($row['therapist']) ?></td>
                            <td><?= date('M d, Y', strtotime($row['appointment_date'])) ?></td>
                            <td><?= date('g:i A', strtotime($row['appointment_time'])) ?></td>
                            <td style="font-family:'Cormorant Garamond',serif;font-size:1rem;font-weight:600;">
                                <span style="font-size:.72rem;color:var(--gold);">₱</span><?= number_format($row['total_amount'],2) ?>
                            </td>
                            <td>
                                <span class="badge-status badge-<?= $row['status'] ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /.main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script>

// ── Custom date range toggle ────────────────────────────────────────
function toggleCustom(val) {
    const el = document.getElementById('customRange');
    el.classList.toggle('visible', val === 'custom');
}

// ── Chart.js defaults ───────────────────────────────────────────────
Chart.defaults.font.family = "'DM Sans', sans-serif";
Chart.defaults.color       = '#8a8070';

const GOLD       = '#c9a96e';
const GOLD_LIGHT = '#e8d5b0';
const GOLD_DIM   = 'rgba(201,169,110,0.13)';
const GREEN      = '#5a8a5a';
const RED        = '#b43c3c';
const MUTED      = '#8a8070';

// ── Revenue Chart ───────────────────────────────────────────────────
const revenueData = <?= json_encode(array_map('floatval', $chart_revenue)) ?>;
if (revenueData.length === 0 || revenueData.every(v => v === 0)) {
    document.getElementById('revenueEmpty').classList.add('visible');
} else {
    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Revenue (₱)',
                data: revenueData,
                backgroundColor: GOLD_DIM,
                borderColor: GOLD,
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, border: { display: false } },
                y: {
                    grid: { color: 'rgba(201,169,110,0.08)', drawBorder: false },
                    border: { display: false },
                    ticks: { callback: v => '₱' + Number(v).toLocaleString() }
                }
            }
        }
    });
}

// ── Appointments Volume Chart ───────────────────────────────────────
// FIX #4: apptEmpty and apptChart now correctly exist in the HTML (after Fix #1)
const apptData = <?= json_encode(array_map('intval', $chart_appt_total)) ?>;
if (apptData.length === 0 || apptData.every(v => v === 0)) {
    document.getElementById('apptEmpty').classList.add('visible');
} else {
    new Chart(document.getElementById('apptChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_appt_labels) ?>,
            datasets: [{
                label: 'Appointments',
                data: apptData,
                borderColor: GOLD,
                backgroundColor: GOLD_DIM,
                borderWidth: 2.5,
                pointBackgroundColor: GOLD,
                pointRadius: 4,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, border: { display: false } },
                y: {
                    grid: { color: 'rgba(201,169,110,0.08)' },
                    border: { display: false },
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
}

// ── Status Doughnut Chart ───────────────────────────────────────────
const statusData = <?= json_encode(array_map('intval', $status_totals)) ?>;
if (statusData.length === 0 || statusData.every(v => v === 0)) {
    document.getElementById('statusEmpty').classList.add('visible');
} else {
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_map('ucfirst', $status_labels)) ?>,
            datasets: [{
                data: statusData,
                backgroundColor: [GOLD, GREEN, RED],
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 16, font: { size: 12 } }
                }
            }
        }
    });
}

// ── Top Services Chart ──────────────────────────────────────────────
const servicesData = <?= json_encode(array_map('intval', array_column($top_services, 'bookings'))) ?>;
if (servicesData.length === 0 || servicesData.every(v => v === 0)) {
    document.getElementById('servicesEmpty').classList.add('visible');
} else {
    new Chart(document.getElementById('servicesChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($top_services, 'name')) ?>,
            datasets: [{
                label: 'Bookings',
                data: servicesData,
                backgroundColor: [GOLD, GOLD_LIGHT, 'rgba(201,169,110,.5)', 'rgba(201,169,110,.3)', 'rgba(201,169,110,.15)'],
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: {
                    grid: { color: 'rgba(201,169,110,0.08)' },
                    border: { display: false },
                    ticks: { stepSize: 1 }
                },
                y: { grid: { display: false }, border: { display: false } }
            }
        }
    });
}

// ── Export CSV ──────────────────────────────────────────────────────
function exportCSV() {
    const table = document.getElementById('apptTable');
    let csv = [];
    for (let row of table.rows) {
        let cols = [];
        for (let cell of row.cells) {
            let text = cell.innerText.replace(/[\r\n]+/g,' ').trim();
            cols.push('"' + text.replace(/"/g,'""') + '"');
        }
        csv.push(cols.join(','));
    }
    const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'lumierebliss_report_<?= date('Ymd') ?>.csv';
    link.click();
}

// ── Export PDF ──────────────────────────────────────────────────────
function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape' });

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(16);
    doc.text('Lumiéré & Bliss — System Report', 14, 18);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    doc.setTextColor(138, 128, 112);
    doc.text('Generated by: <?= addslashes($admin_name) ?>   |   Date: <?= date('F j, Y') ?>   |   Period: <?= date('M d, Y', strtotime($date_from)) ?> – <?= date('M d, Y', strtotime($date_to)) ?>', 14, 26);

    // Summary row
    doc.setFontSize(10);
    doc.setTextColor(26, 26, 26);
    const summary = [
        ['Period Revenue', '₱<?= number_format($range_revenue,2) ?>'],
        ['Total Bookings', '<?= $range_transactions ?>'],
        ['Completed', '<?= $range_completed ?>'],
        ['Confirmed', '<?= $range_confirmed ?>'],
        ['Cancelled', '<?= $range_cancelled ?>'],
        ['Active Therapists', '<?= $active_therapists ?>'],
    ];
    doc.autoTable({
        startY: 32,
        head: [['Metric', 'Value']],
        body: summary,
        theme: 'grid',
        headStyles: { fillColor: [201,169,110], textColor: 255, fontStyle: 'bold' },
        styles: { fontSize: 9 },
        margin: { left: 14, right: 14 },
        tableWidth: 120
    });

    // Appointments table
    const headers = [['#','Client','Therapist','Date','Time','Amount','Status']];
    const rows = [];
    const tbl = document.getElementById('apptTable');
    for (let i = 1; i < tbl.rows.length; i++) {
        const cells = tbl.rows[i].cells;
        if (cells.length < 7) continue;
        rows.push([
            cells[0].innerText.trim(),
            cells[1].innerText.trim(),
            cells[2].innerText.trim(),
            cells[3].innerText.trim(),
            cells[4].innerText.trim(),
            cells[5].innerText.trim(),
            cells[6].innerText.trim(),
        ]);
    }
    doc.autoTable({
        startY: doc.lastAutoTable.finalY + 10,
        head: headers,
        body: rows,
        theme: 'striped',
        headStyles: { fillColor: [26,26,26], textColor: [201,169,110], fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [253,251,247] },
        styles: { fontSize: 8 },
        margin: { left: 14, right: 14 }
    });

    doc.save('lumierebliss_report_<?= date('Ymd') ?>.pdf');
}
</script>
</body>
</html>