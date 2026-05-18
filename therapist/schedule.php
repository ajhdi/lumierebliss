<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['therapist_id'])) {
    header("Location: signin_therapist.php");
    exit();
}

$therapist_id = $_SESSION['therapist_id'];
$filter_date = $_GET['date'] ?? date('Y-m-d');

$query = "SELECT a.*, u.first_name, u.last_name, u.contact_number, 
          r.room_name, t.name as treatment_name, p.name as package_name
          FROM appointments a
          JOIN users u ON a.user_id = u.user_id
          JOIN rooms r ON a.room_id = r.room_id
          LEFT JOIN treatments t ON a.treatment_id = t.treatment_id
          LEFT JOIN packages p ON a.package_id = p.package_id
          WHERE a.therapist_id = ? AND a.appointment_date = ?
          ORDER BY a.appointment_time ASC";

$stmt = $pdo->prepare($query);
$stmt->execute([$therapist_id, $filter_date]);
$appointments = $stmt->fetchAll();

$session_count = count($appointments);

$disabled_dates_query = $pdo->query("SELECT disabled_date, remarks FROM disabled_dates")->fetchAll(PDO::FETCH_ASSOC);
$disabled_json = json_encode($disabled_dates_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule | Lumiére and Bliss</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        /* ─── Design Tokens ─── */
        :root {
            --pure-white:   #ffffff;
            --studio-surf:  #fdfbf7;
            --brand-gold:   #c9a96e;
            --gold-light:   #e8d5b0;
            --the-dark:     #1a1a1a;
            --studio-mid:   #2e2e2e;
            --muted-text:   #8a8070;
            --gold-dim:     rgba(201,169,110,.18);
            --gold-line:    rgba(201,169,110,.35);
        }

        /* ─── Base ─── */
        *, *::before, *::after { box-sizing: border-box; }

        body {
            background-color: var(--studio-surf);
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            color: var(--the-dark);
            min-height: 100vh;
        }

        h1,h2,h3,h4,h5,h6,
        .serif { font-family: 'Cormorant Garamond', serif; }

        /* ─── Scrollbar ─── */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--studio-surf); }
        ::-webkit-scrollbar-thumb { background: var(--brand-gold); border-radius: 4px; }

        /* ──────────────────────────────────────
           NAVBAR
        ────────────────────────────────────── */
        .lb-nav {
            background: var(--the-dark);
            border-bottom: 1px solid rgba(201,169,110,.25);
            padding: 0;
        }

        .lb-nav .container-xl {
            display: flex;
            align-items: stretch;
            height: 68px;
        }

        /* Brand */
        .lb-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            padding-right: 36px;
            border-right: 1px solid rgba(201,169,110,.2);
        }

        .lb-brand-mark {
            width: 34px;
            height: 34px;
            border: 1.5px solid var(--brand-gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--brand-gold);
            font-size: 13px;
            font-family: 'Cormorant Garamond', serif;
            font-weight: 400;
            letter-spacing: .03em;
            flex-shrink: 0;
        }

        .lb-brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }

        .lb-brand-text span:first-child {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 400;
            font-size: 17px;
            color: var(--pure-white);
            letter-spacing: .06em;
        }

        .lb-brand-text span:last-child {
            font-family: 'DM Sans', sans-serif;
            font-size: 9px;
            font-weight: 500;
            color: var(--brand-gold);
            letter-spacing: .22em;
            text-transform: uppercase;
        }

        /* Nav section label */
        .lb-nav-section {
            display: flex;
            align-items: center;
            padding: 0 28px;
            color: var(--gold-light);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .2em;
            text-transform: uppercase;
            border-right: 1px solid rgba(201,169,110,.15);
        }

        /* Nav right cluster */
        .lb-nav-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 0;
        }

        .lb-nav-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 22px;
            height: 100%;
            border-left: 1px solid rgba(201,169,110,.15);
        }

        .lb-nav-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--brand-gold), var(--gold-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: var(--the-dark);
            flex-shrink: 0;
        }

        .lb-nav-name {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .lb-nav-name .label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .15em;
            color: var(--muted-text);
        }

        .lb-nav-name .name {
            font-size: 13px;
            font-weight: 600;
            color: var(--pure-white);
        }

        .lb-logout {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 0 22px;
            height: 100%;
            border-left: 1px solid rgba(201,169,110,.15);
            color: var(--muted-text);
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: .04em;
            transition: color .2s, background .2s;
        }

        .lb-logout:hover {
            color: var(--brand-gold);
            background: rgba(201,169,110,.07);
        }

        /* ──────────────────────────────────────
           HERO STRIP
        ────────────────────────────────────── */
        .lb-hero {
            background: linear-gradient(135deg, var(--the-dark) 0%, var(--studio-mid) 100%);
            border-bottom: 1px solid rgba(201,169,110,.2);
            padding: 48px 0 44px;
            position: relative;
            overflow: hidden;
        }

        .lb-hero::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 320px; height: 320px;
            border: 1px solid rgba(201,169,110,.1);
            border-radius: 50%;
            pointer-events: none;
        }

        .lb-hero::after {
            content: '';
            position: absolute;
            bottom: -80px; left: 38%;
            width: 200px; height: 200px;
            border: 1px solid rgba(201,169,110,.07);
            border-radius: 50%;
            pointer-events: none;
        }

        .lb-hero-eyebrow {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .25em;
            text-transform: uppercase;
            color: var(--brand-gold);
            margin-bottom: 10px;
        }

        .lb-hero-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: clamp(34px, 4vw, 50px);
            color: var(--pure-white);
            line-height: 1.1;
            margin-bottom: 8px;
        }

        .lb-hero-title em {
            font-style: italic;
            color: var(--gold-light);
        }

        .lb-hero-sub {
            font-size: 13px;
            color: var(--muted-text);
            font-weight: 400;
        }

        /* Date Display in Hero */
        .lb-hero-date-badge {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(201,169,110,.25);
            border-radius: 12px;
            padding: 18px 28px;
            text-align: center;
            min-width: 180px;
        }

        .lb-hero-date-badge .day-num {
            font-family: 'Cormorant Garamond', serif;
            font-size: 52px;
            font-weight: 300;
            color: var(--pure-white);
            line-height: 1;
        }

        .lb-hero-date-badge .day-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--brand-gold);
            margin-top: 4px;
        }

        .lb-hero-date-badge .month-year {
            font-size: 12px;
            color: var(--muted-text);
            margin-top: 2px;
        }

        /* ──────────────────────────────────────
           MAIN CONTENT WRAPPER
        ────────────────────────────────────── */
        .lb-main {
            padding: 44px 0 80px;
        }

        /* ──────────────────────────────────────
           STAT CARDS ROW
        ────────────────────────────────────── */
        .lb-stat-card {
            background: var(--pure-white);
            border: 1px solid rgba(201,169,110,.18);
            border-radius: 14px;
            padding: 24px 28px;
            display: flex;
            align-items: center;
            gap: 18px;
            transition: box-shadow .25s, transform .2s;
        }

        .lb-stat-card:hover {
            box-shadow: 0 8px 32px rgba(201,169,110,.14);
            transform: translateY(-2px);
        }

        .lb-stat-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .lb-stat-icon.gold {
            background: linear-gradient(135deg, var(--brand-gold), var(--gold-light));
            color: var(--the-dark);
        }

        .lb-stat-icon.dark {
            background: linear-gradient(135deg, var(--the-dark), var(--studio-mid));
            color: var(--brand-gold);
        }

        .lb-stat-icon.muted {
            background: rgba(201,169,110,.12);
            color: var(--brand-gold);
        }

        .lb-stat-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted-text);
            margin-bottom: 4px;
        }

        .lb-stat-value {
            font-family: 'Cormorant Garamond', serif;
            font-size: 28px;
            font-weight: 600;
            color: var(--the-dark);
            line-height: 1;
        }

        .lb-stat-value.danger { color: #c0392b; }

        /* ──────────────────────────────────────
           SESSION PROGRESS
        ────────────────────────────────────── */
        .lb-progress-wrap {
            background: var(--pure-white);
            border: 1px solid rgba(201,169,110,.18);
            border-radius: 14px;
            padding: 22px 28px;
        }

        .lb-progress-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 12px;
        }

        .lb-progress-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted-text);
        }

        .lb-progress-count {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 600;
            color: var(--the-dark);
        }

        .lb-progress-count.full { color: #c0392b; }

        .lb-track {
            height: 6px;
            background: rgba(201,169,110,.15);
            border-radius: 99px;
            overflow: hidden;
        }

        .lb-bar {
            height: 100%;
            border-radius: 99px;
            transition: width .6s cubic-bezier(.4,0,.2,1);
            background: linear-gradient(90deg, var(--brand-gold), var(--gold-light));
        }

        .lb-bar.full { background: linear-gradient(90deg, #c0392b, #e74c3c); }
        .lb-bar.warn { background: linear-gradient(90deg, #d4a017, #f39c12); }

        /* ──────────────────────────────────────
           DATE FILTER CARD
        ────────────────────────────────────── */
        .lb-filter-card {
            background: var(--pure-white);
            border: 1px solid rgba(201,169,110,.18);
            border-radius: 14px;
            padding: 28px 32px;
        }

        .lb-section-heading {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            font-weight: 600;
            color: var(--the-dark);
            margin-bottom: 4px;
        }

        .lb-section-sub {
            font-size: 12px;
            color: var(--muted-text);
            letter-spacing: .04em;
        }

        .lb-divider {
            border: none;
            border-top: 1px solid rgba(201,169,110,.18);
            margin: 20px 0;
        }

        .lb-form-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted-text);
            margin-bottom: 8px;
            display: block;
        }

        .lb-input {
            background: var(--studio-surf);
            border: 1px solid rgba(201,169,110,.3);
            border-radius: 8px;
            padding: 11px 16px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--the-dark);
            width: 100%;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        .lb-input:focus {
            border-color: var(--brand-gold);
            box-shadow: 0 0 0 3px rgba(201,169,110,.15);
        }

        /* ──────────────────────────────────────
           APPOINTMENTS TABLE CARD
        ────────────────────────────────────── */
        .lb-table-card {
            background: var(--pure-white);
            border: 1px solid rgba(201,169,110,.18);
            border-radius: 14px;
            overflow: hidden;
        }

        .lb-table-header {
            padding: 28px 32px 0;
        }

        .lb-table-scroll {
            overflow-x: auto;
            padding: 0 0 4px;
        }

        table.lb-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        table.lb-table thead tr {
            background: linear-gradient(90deg, var(--the-dark), var(--studio-mid));
        }

        table.lb-table thead th {
            padding: 14px 20px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: var(--brand-gold);
            white-space: nowrap;
            border: none;
        }

        table.lb-table thead th:first-child { padding-left: 32px; }
        table.lb-table thead th:last-child  { padding-right: 32px; }

        table.lb-table tbody tr {
            border-bottom: 1px solid rgba(201,169,110,.1);
            transition: background .18s;
        }

        table.lb-table tbody tr:last-child { border-bottom: none; }

        table.lb-table tbody tr:hover { background: rgba(201,169,110,.04); }

        table.lb-table tbody td {
            padding: 20px 20px;
            vertical-align: middle;
            border: none;
        }

        table.lb-table tbody td:first-child { padding-left: 32px; }
        table.lb-table tbody td:last-child  { padding-right: 32px; }

        /* Time cell */
        .td-time-main {
            font-family: 'Cormorant Garamond', serif;
            font-size: 20px;
            font-weight: 600;
            color: var(--the-dark);
            line-height: 1.1;
        }

        .td-time-end {
            font-size: 11px;
            color: var(--muted-text);
            margin-top: 2px;
        }

        .td-time-bar {
            width: 2px;
            height: 38px;
            background: linear-gradient(to bottom, var(--brand-gold), transparent);
            border-radius: 2px;
            margin-right: 14px;
            flex-shrink: 0;
        }

        /* Client cell */
        .td-client-name {
            font-size: 15px;
            font-weight: 600;
            color: var(--the-dark);
        }

        .td-client-phone {
            font-size: 11px;
            color: var(--muted-text);
            margin-top: 2px;
        }

        /* Service badge */
        .lb-service-badge {
            display: inline-block;
            padding: 5px 14px;
            border: 1px solid var(--gold-line);
            border-radius: 99px;
            font-size: 11px;
            font-weight: 500;
            color: var(--studio-mid);
            background: rgba(201,169,110,.07);
            white-space: nowrap;
        }

        /* Room */
        .td-room {
            font-size: 13px;
            font-weight: 500;
            color: var(--the-dark);
        }

        /* Status badges */
        .lb-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 13px;
            border-radius: 99px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .lb-status::before {
            content: '';
            width: 5px; height: 5px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .lb-status.confirmed {
            background: rgba(201,169,110,.12);
            color: #7a5c2e;
        }

        .lb-status.confirmed::before { background: var(--brand-gold); }

        .lb-status.completed {
            background: rgba(39,174,96,.1);
            color: #1e7e46;
        }

        .lb-status.completed::before { background: #27ae60; }

        .lb-status.cancelled {
            background: rgba(192,57,43,.1);
            color: #8b2020;
        }

        .lb-status.cancelled::before { background: #c0392b; }

        /* Empty state */
        .lb-empty {
            padding: 80px 20px;
            text-align: center;
        }

        .lb-empty-icon {
            width: 60px; height: 60px;
            border: 1.5px solid var(--gold-line);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--brand-gold);
            margin: 0 auto 20px;
        }

        .lb-empty-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 22px;
            font-weight: 600;
            color: var(--the-dark);
            margin-bottom: 6px;
        }

        .lb-empty-sub {
            font-size: 13px;
            color: var(--muted-text);
        }

        /* ──────────────────────────────────────
           GOLD ORNAMENT DIVIDERS
        ────────────────────────────────────── */
        .lb-ornament {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 32px 0 24px;
        }

        .lb-ornament-line {
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, transparent, var(--gold-line), transparent);
        }

        .lb-ornament-gem {
            width: 7px; height: 7px;
            background: var(--brand-gold);
            transform: rotate(45deg);
            flex-shrink: 0;
        }

        /* ──────────────────────────────────────
           FOOTER
        ────────────────────────────────────── */
        .lb-footer {
            border-top: 1px solid rgba(201,169,110,.15);
            padding: 24px 0;
            text-align: center;
        }

        .lb-footer-text {
            font-size: 11px;
            color: var(--muted-text);
            letter-spacing: .08em;
        }

        .lb-footer-brand {
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            color: var(--brand-gold);
        }

        /* ──────────────────────────────────────
           RESPONSIVE
        ────────────────────────────────────── */
        @media (max-width: 768px) {
            .lb-hero { padding: 36px 0 32px; }
            .lb-hero-date-badge { display: none; }
            .lb-hero-title { font-size: 30px; }
            .lb-nav .lb-nav-section { display: none; }
            table.lb-table tbody td:first-child,
            table.lb-table thead th:first-child { padding-left: 20px; }
            table.lb-table tbody td:last-child,
            table.lb-table thead th:last-child  { padding-right: 20px; }
        }

        /* ──────────────────────────────────────
           FADE-IN ANIMATION
        ────────────────────────────────────── */
        .lb-fade-in {
            opacity: 0;
            transform: translateY(16px);
            animation: fadeUp .5s forwards;
        }

        .lb-fade-in:nth-child(1) { animation-delay: .05s; }
        .lb-fade-in:nth-child(2) { animation-delay: .12s; }
        .lb-fade-in:nth-child(3) { animation-delay: .19s; }
        .lb-fade-in:nth-child(4) { animation-delay: .26s; }

        @keyframes fadeUp {
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════
     NAVIGATION
════════════════════════════════════════ -->
<nav class="lb-nav">
    <div class="container-xl">
        <!-- Brand -->
        <a href="#" class="lb-brand">
            <div class="lb-brand-mark">L</div>
            <div class="lb-brand-text">
                <span>Lumiére &amp; Bliss</span>
                <span>Therapist Portal</span>
            </div>
        </a>

        <!-- Section Label -->
        <div class="lb-nav-section d-none d-md-flex">
            Daily Schedule
        </div>

        <!-- Right -->
        <div class="lb-nav-right">
            <div class="lb-nav-pill d-none d-sm-flex">
                <div class="lb-nav-avatar">
                    <?= strtoupper(substr($_SESSION['therapist_name'], 0, 1)) ?>
                </div>
                <div class="lb-nav-name">
                    <span class="label">Signed in as</span>
                    <span class="name"><?= htmlspecialchars($_SESSION['therapist_name']) ?></span>
                </div>
            </div>
            <a href="logout.php" class="lb-logout">
                <i class="bi bi-box-arrow-right"></i>
                <span class="d-none d-sm-inline">Sign Out</span>
            </a>
        </div>
    </div>
</nav>

<!-- ═══════════════════════════════════════
     HERO STRIP
════════════════════════════════════════ -->
<section class="lb-hero">
    <div class="container-xl">
        <div class="d-flex align-items-center justify-content-between gap-4">
            <!-- Text -->
            <div>
                <div class="lb-hero-eyebrow">Lumiére &amp; Bliss · Wellness Studio</div>
                <h1 class="lb-hero-title">
                    Daily <em>Schedule</em>
                </h1>
                <p class="lb-hero-sub">Manage your appointments and client details with care.</p>
            </div>

            <!-- Date badge (desktop) -->
            <div class="lb-hero-date-badge flex-shrink-0">
                <?php
                    $ts = strtotime($filter_date);
                ?>
                <div class="day-num"><?= date('d', $ts) ?></div>
                <div class="day-label"><?= date('l', $ts) ?></div>
                <div class="month-year"><?= date('F Y', $ts) ?></div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════
     MAIN CONTENT
════════════════════════════════════════ -->
<main class="lb-main">
    <div class="container-xl">

        <!-- ── STAT ROW ── -->
        <div class="row g-3 mb-4">
            <!-- Sessions -->
            <div class="col-6 col-lg-3 lb-fade-in">
                <div class="lb-stat-card">
                    <div class="lb-stat-icon gold">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div>
                        <div class="lb-stat-label">Today's Sessions</div>
                        <div class="lb-stat-value <?= ($session_count >= 4) ? 'danger' : '' ?>">
                            <?= $session_count ?><span style="font-size:15px;color:var(--muted-text)">/4</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Remaining -->
            <div class="col-6 col-lg-3 lb-fade-in">
                <div class="lb-stat-card">
                    <div class="lb-stat-icon dark">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="lb-stat-label">Slots Remaining</div>
                        <div class="lb-stat-value"><?= max(0, 4 - $session_count) ?></div>
                    </div>
                </div>
            </div>

            <!-- Date -->
            <div class="col-6 col-lg-3 lb-fade-in">
                <div class="lb-stat-card">
                    <div class="lb-stat-icon muted">
                        <i class="bi bi-calendar3"></i>
                    </div>
                    <div>
                        <div class="lb-stat-label">Selected Date</div>
                        <div style="font-size:14px;font-weight:600;color:var(--the-dark);margin-top:2px;">
                            <?= date('M j, Y', strtotime($filter_date)) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Capacity bar -->
            <div class="col-6 col-lg-3 lb-fade-in">
                <div class="lb-progress-wrap h-100 d-flex flex-column justify-content-center">
                    <div class="lb-progress-header">
                        <span class="lb-progress-label">Daily Capacity</span>
                        <span class="lb-progress-count <?= ($session_count >= 4) ? 'full' : '' ?>">
                            <?= $session_count ?>/4
                        </span>
                    </div>
                    <?php
                        $pct       = min(($session_count / 4) * 100, 100);
                        $bar_cls   = '';
                        if ($session_count >= 4) $bar_cls = 'full';
                        elseif ($session_count >= 3) $bar_cls = 'warn';
                    ?>
                    <div class="lb-track">
                        <div class="lb-bar <?= $bar_cls ?>" style="width: <?= $pct ?>%"></div>
                    </div>
                    <div style="font-size:10px;color:var(--muted-text);margin-top:8px;letter-spacing:.05em;">
                        <?php
                            if ($session_count >= 4) echo 'Fully booked for today';
                            elseif ($session_count >= 3) echo 'One slot remaining';
                            else echo (4 - $session_count) . ' slots still available';
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── GOLD ORNAMENT ── -->
        <div class="lb-ornament">
            <div class="lb-ornament-line"></div>
            <div class="lb-ornament-gem"></div>
            <div class="lb-ornament-line"></div>
        </div>

        <!-- ── DATE FILTER ── -->
        <div class="lb-filter-card mb-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="lb-section-heading">Select a Date</div>
                    <div class="lb-section-sub">Choose any date to view the scheduled appointments.</div>
                </div>
            </div>
            <hr class="lb-divider">
            <form method="GET">
                <label class="lb-form-label" for="scheduleDate">Appointment Date</label>
                <div class="d-flex align-items-center gap-3" style="max-width: 320px;">
                    <input
                        type="date"
                        name="date"
                        id="scheduleDate"
                        class="lb-input"
                        value="<?= $filter_date ?>"
                    >
                </div>
            </form>
        </div>

        <!-- ── APPOINTMENTS TABLE ── -->
        <div class="lb-table-card">
            <div class="lb-table-header pb-4">
                <div class="lb-section-heading">Appointment Schedule</div>
                <div class="lb-section-sub"><?= date('l, F j, Y', strtotime($filter_date)) ?></div>
            </div>

            <div class="lb-table-scroll">
                <table class="lb-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th>Room</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($appointments)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="lb-empty">
                                    <div class="lb-empty-icon">
                                        <i class="bi bi-calendar-x"></i>
                                    </div>
                                    <div class="lb-empty-title">No Appointments</div>
                                    <div class="lb-empty-sub">There are no sessions scheduled for this date.</div>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($appointments as $app): ?>
                        <tr>
                            <!-- Time -->
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="td-time-bar"></div>
                                    <div>
                                        <div class="td-time-main">
                                            <?= date('h:i A', strtotime($app['appointment_time'])) ?>
                                        </div>
                                        <div class="td-time-end">
                                            until <?= date('h:i A', strtotime($app['end_time'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Client -->
                            <td>
                                <div class="td-client-name">
                                    <?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?>
                                </div>
                                <div class="td-client-phone">
                                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($app['contact_number']) ?>
                                </div>
                            </td>

                            <!-- Service -->
                            <td>
                                <span class="lb-service-badge">
                                    <?= htmlspecialchars($app['treatment_name'] ?: $app['package_name']) ?>
                                </span>
                            </td>

                            <!-- Room -->
                            <td>
                                <div class="td-room">
                                    <i class="bi bi-door-open me-1" style="color:var(--brand-gold)"></i>
                                    <?= htmlspecialchars($app['room_name']) ?>
                                </div>
                            </td>

                            <!-- Status -->
                            <td>
                                <?php
                                    $sc = strtolower($app['status']);
                                ?>
                                <span class="lb-status <?= $sc ?>">
                                    <?= ucfirst($sc) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /container -->
</main>

<!-- ═══════════════════════════════════════
     FOOTER
════════════════════════════════════════ -->
<footer class="lb-footer">
    <div class="container-xl">
        <div class="lb-footer-text">
            &copy; <?= date('Y') ?> <span class="lb-footer-brand">Lumiére and Bliss</span> &nbsp;·&nbsp; Therapist Portal &nbsp;·&nbsp; All rights reserved.
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.getElementById('scheduleDate').addEventListener('change', function () {
    const selectedDate  = this.value;
    const disabledDates = <?= $disabled_json ?>;
    const restriction   = disabledDates.find(d => d.disabled_date === selectedDate);

    if (restriction) {
        Swal.fire({
            icon: 'error',
            title: 'Date Unavailable',
            text: `The administrator has disabled this date. Reason: ${restriction.remarks || 'No reason provided.'}`,
            confirmButtonColor: '#c9a96e',
            confirmButtonText: 'Understood',
            background: '#fdfbf7',
            color: '#1a1a1a',
            customClass: {
                title:      'swal-lb-title',
                popup:      'swal-lb-popup',
            }
        });
        this.value = "<?= $filter_date ?>";
    } else {
        this.form.submit();
    }
});
</script>

<style>
/* SweetAlert2 brand overrides */
.swal-lb-popup  { border: 1px solid rgba(201,169,110,.35) !important; border-radius: 14px !important; }
.swal-lb-title  { font-family: 'Cormorant Garamond', serif !important; font-weight: 600 !important; }
</style>

</body>
</html>