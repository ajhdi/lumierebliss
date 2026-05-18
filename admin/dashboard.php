<?php
// /admin/dashboard.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}

// 1. ANALYTICS: Basic Counts
$therapist_count = $pdo->query("SELECT COUNT(*) FROM therapists WHERE status='active'")->fetchColumn();
$room_count      = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status='available'")->fetchColumn();
$confirmed_count = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='confirmed'")->fetchColumn();

// 2. ANALYTICS: Completed Appointments (Current Month)
$completed_month = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='completed' AND MONTH(appointment_date) = MONTH(CURRENT_DATE())")->fetchColumn();

// 3. REPORTS: Estimated Revenue (Confirmed + Completed)
$revenue_query = $pdo->query("SELECT SUM(total_amount) FROM appointments WHERE status != 'cancelled'")->fetchColumn();
$est_revenue   = $revenue_query ? number_format($revenue_query, 2) : "0.00";

// 4. REPORTS: Most Booked Service
$popular_service = $pdo->query("
    SELECT name, COUNT(*) as count FROM (
        SELECT t.name FROM appointments a JOIN treatments t ON a.treatment_id = t.treatment_id
        UNION ALL
        SELECT p.name FROM appointments a JOIN packages p ON a.package_id = p.package_id
    ) as services 
    GROUP BY name ORDER BY count DESC LIMIT 1
")->fetch();

// 5. REPORTS: Peak Booking Time
$peak_time = $pdo->query("
    SELECT HOUR(appointment_time) as hour, COUNT(*) as count 
    FROM appointments 
    GROUP BY hour ORDER BY count DESC LIMIT 1
")->fetch();
$display_peak = $peak_time ? date("g:i A", strtotime($peak_time['hour'] . ":00")) : "N/A";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Lumiére &amp; Bliss</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        /* ─── Design Tokens ─────────────────────────────────────────── */
        :root {
            --white:        #ffffff;
            --cream:        #fdfbf7;
            --gold:         #c9a96e;
            --gold-light:   #e8d5b0;
            --gold-dim:     rgba(201,169,110,0.15);
            --dark:         #1a1a1a;
            --dark-soft:    #2e2e2e;
            --muted:        #8a8070;
            --border:       rgba(201,169,110,0.22);
            --sidebar-w:    270px;
            --radius-lg:    18px;
            --radius-md:    12px;
            --shadow:       0 8px 32px rgba(26,26,26,0.08);
            --shadow-deep:  0 16px 48px rgba(26,26,26,0.14);
        }

        /* ─── Reset / Base ──────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            background: var(--cream);
            font-family: 'DM Sans', sans-serif;
            font-size: 18px;
            color: var(--dark);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ─── Main Content ──────────────────────────────────────────── */
        .main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            padding: 48px 44px 60px;
            transition: margin .35s cubic-bezier(.4,0,.2,1);
        }

        /* ─── Top Bar ───────────────────────────────────────────────── */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 48px;
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
        .topbar-date {
            font-size: .8rem;
            color: var(--muted);
            text-align: right;
        }
        .topbar-date strong {
            display: block;
            font-size: .9rem;
            color: var(--dark-soft);
            font-weight: 600;
        }

        /* Gold rule divider */
        .gold-rule {
            width: 48px;
            height: 2px;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
            border-radius: 2px;
            margin-bottom: 36px;
        }

        /* ─── Section Label ─────────────────────────────────────────── */
        .section-eyebrow {
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 20px;
        }

        /* ─── Stat Cards ────────────────────────────────────────────── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 36px;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 28px 26px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(201,169,110,.1);
            position: relative;
            overflow: hidden;
            transition: transform .25s ease, box-shadow .25s ease;
        }
        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .3s ease;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-deep); }
        .stat-card:hover::after { transform: scaleX(1); }

        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: var(--gold-dim);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: var(--gold);
            margin-bottom: 18px;
        }
        .stat-label {
            font-size: .67rem;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 6px;
        }
        .stat-value {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            font-size: 2.6rem;
            color: var(--dark);
            line-height: 1;
            margin-bottom: 10px;
        }
        .stat-tag {
            font-size: .75rem;
            font-weight: 500;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .stat-tag.success { color: #5a8a5a; }
        .stat-tag.info    { color: #4a7aaa; }
        .stat-tag.warn    { color: #a07a30; }

        /* ─── Bottom Grid ────────────────────────────────────────────── */
        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 24px;
        }

        /* Revenue Card */
        .revenue-card {
            background: var(--dark);
            border-radius: var(--radius-lg);
            padding: 40px 42px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-deep);
        }
        .revenue-card::before {
            content: '';
            position: absolute;
            top: -80px; right: -80px;
            width: 320px; height: 320px;
            background: radial-gradient(circle, rgba(201,169,110,0.12) 0%, transparent 70%);
            pointer-events: none;
        }
        .revenue-card::after {
            content: '"';
            font-family: 'Cormorant Garamond', serif;
            font-size: 18rem;
            color: rgba(201,169,110,0.04);
            position: absolute;
            top: -60px; left: 24px;
            line-height: 1;
            pointer-events: none;
            user-select: none;
        }
        .revenue-eyebrow {
            font-size: .68rem; font-weight: 700;
            letter-spacing: .2em; text-transform: uppercase;
            color: var(--gold); margin-bottom: 12px;
        }
        .revenue-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600; font-size: 1.8rem;
            color: var(--white); margin-bottom: 28px;
        }
        .revenue-amount {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300; font-size: 4rem;
            color: var(--white); line-height: 1;
        }
        .revenue-amount sup { font-size: 1.6rem; vertical-align: super; color: var(--gold); }
        .revenue-sub {
            font-size: .78rem; color: var(--gold-light);
            margin-top: 8px; opacity: .7; font-weight: 500; letter-spacing: .06em;
        }
        .revenue-divider { border: none; border-top: 1px solid rgba(201,169,110,.2); margin: 28px 0; }
        .revenue-note {
            font-size: .8rem; color: rgba(255,255,255,.4);
            display: flex; align-items: flex-start; gap: 9px; line-height: 1.5;
        }
        .revenue-note i { color: var(--gold); margin-top: 2px; flex-shrink: 0; }

        /* Insights Card */
        .insights-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 36px 32px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(201,169,110,.1);
            display: flex; flex-direction: column;
        }
        .insights-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600; font-size: 1.5rem;
            color: var(--dark); margin-bottom: 28px;
        }
        .insight-item { padding: 18px 0; border-bottom: 1px solid rgba(201,169,110,.12); }
        .insight-item:last-of-type { border-bottom: none; }
        .insight-label {
            font-size: .65rem; font-weight: 700;
            letter-spacing: .2em; text-transform: uppercase;
            color: var(--muted); margin-bottom: 6px;
        }
        .insight-value { font-size: 1rem; font-weight: 600; color: var(--dark); }
        .insight-value.empty { color: var(--muted); font-weight: 400; font-style: italic; }

        .btn-report { margin-top: auto; padding-top: 28px; }
        .btn-report a {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 13px 0;
            background: transparent;
            border: 1.5px solid var(--gold);
            border-radius: 50px;
            color: var(--dark);
            font-size: .82rem; font-weight: 600; letter-spacing: .08em;
            text-decoration: none;
            transition: background .22s, color .22s;
        }
        .btn-report a:hover { background: var(--gold); color: var(--white); }

        /* ─── Responsive ─────────────────────────────────────────────── */
        @media (max-width: 1200px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 991px) {
            .main-content { margin-left: 0; padding: 80px 24px 40px; }
            .bottom-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            .stat-grid { grid-template-columns: 1fr; }
            .topbar { flex-direction: column; align-items: flex-start; gap: 8px; }
            .topbar-date { text-align: left; }
        }

        /* ─── Fade-in animations ────────────────────────────────────── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .stat-card   { animation: fadeUp .5s ease both; }
        .stat-card:nth-child(1) { animation-delay: .05s; }
        .stat-card:nth-child(2) { animation-delay: .12s; }
        .stat-card:nth-child(3) { animation-delay: .19s; }
        .stat-card:nth-child(4) { animation-delay: .26s; }
        .revenue-card  { animation: fadeUp .5s .3s ease both; }
        .insights-card { animation: fadeUp .5s .38s ease both; }
    </style>
</head>
<body>

<?php require_once '../includes/sidebar.php'; ?>

<!-- ── Main Content ────────────────────────────────────────────────── -->
<div class="main-content">

    <!-- Top Bar -->
    <div class="topbar">
        <div class="topbar-title">
            <span>Admin Dashboard</span>
            Welcome Back
        </div>
        <div class="topbar-date">
            <strong id="js-date"></strong>
            Lumiére &amp; Bliss Studio
        </div>
    </div>

    <!-- Gold Rule -->
    <div class="gold-rule"></div>

    <!-- ── Stat Cards ──────────────────────────────────────────────── -->
    <p class="section-eyebrow">At a Glance</p>
    <div class="stat-grid">

        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-person-heart"></i></div>
            <div class="stat-label">Therapists</div>
            <div class="stat-value"><?= $therapist_count ?></div>
            <div class="stat-tag success"><i class="bi bi-circle-fill" style="font-size:.45rem"></i> Currently Active</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-door-open"></i></div>
            <div class="stat-label">Rooms</div>
            <div class="stat-value"><?= $room_count ?></div>
            <div class="stat-tag info"><i class="bi bi-circle-fill" style="font-size:.45rem"></i> Available Now</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
            <div class="stat-label">Confirmed</div>
            <div class="stat-value"><?= $confirmed_count ?></div>
            <div class="stat-tag warn"><i class="bi bi-circle-fill" style="font-size:.45rem"></i> Upcoming Bookings</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-flag"></i></div>
            <div class="stat-label">Completed</div>
            <div class="stat-value"><?= $completed_month ?></div>
            <div class="stat-tag"><i class="bi bi-circle-fill" style="font-size:.45rem"></i> This Month</div>
        </div>

    </div>

    <!-- ── Bottom Grid ─────────────────────────────────────────────── -->
    <p class="section-eyebrow" style="margin-top:16px">Reports &amp; Insights</p>
    <div class="bottom-grid">

        <!-- Revenue Card -->
        <div class="revenue-card">
            <div class="revenue-eyebrow">Financial Overview</div>
            <div class="revenue-title">Estimated Lifetime Revenue</div>
            <div class="revenue-amount"><sup>₱</sup><?= $est_revenue ?></div>
            <div class="revenue-sub">Gross · Confirmed &amp; Completed Appointments</div>
            <hr class="revenue-divider">
            <div class="revenue-note">
                <i class="bi bi-info-circle-fill"></i>
                Revenue is calculated based on confirmed and completed appointments. Cancelled bookings are excluded from this figure.
            </div>
        </div>

        <!-- Insights Card -->
        <div class="insights-card">
            <div class="insights-title">Quick Insights</div>
            <div class="insight-item">
                <div class="insight-label">Most Booked Service</div>
                <div class="insight-value <?= $popular_service ? '' : 'empty' ?>">
                    <?= $popular_service ? htmlspecialchars($popular_service['name']) : 'No data yet' ?>
                </div>
            </div>
            <div class="insight-item">
                <div class="insight-label">Peak Booking Time</div>
                <div class="insight-value <?= $display_peak === 'N/A' ? 'empty' : '' ?>">
                    <?= htmlspecialchars($display_peak) ?>
                </div>
            </div>
            <div class="btn-report">
                <a href="reports.php">View Detailed Reports <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>

    </div>
</div><!-- /.main-content -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Live date
    const d = new Date();
    document.getElementById('js-date').textContent = d.toLocaleDateString('en-US', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    });
</script>
</body>
</html>