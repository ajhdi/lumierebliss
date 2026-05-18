<?php
session_start();
require_once '../config/db.php';
require_once '../includes/log_action.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}

// Handle disabling a date
if (isset($_POST['disable_date_btn'])) {
    header('Content-Type: application/json');
    try {
        $date    = $_POST['disabled_date'];
        $remarks = $_POST['remarks'];

        $stmt = $pdo->prepare("INSERT INTO disabled_dates (disabled_date, remarks) VALUES (?, ?)");
$stmt->execute([$date, $remarks]);

logAction($pdo, "Disabled date: $date — Reason: $remarks");

echo json_encode([
    "status"  => "success",
    "message" => "Date disabled successfully"
]);
    } catch (Exception $e) {
        echo json_encode([
            "status"  => "error",
            "message" => $e->getMessage()
        ]);
    }
    exit;
}

// Filter logic
$filter_date  = isset($_GET['filter_date'])  ? $_GET['filter_date']  : date('Y-m-d');
$therapist_id = isset($_GET['therapist_id']) ? $_GET['therapist_id'] : '';

$query = "SELECT a.*, u.first_name, u.last_name, t.first_name as t_fname, t.last_name as t_lname, r.room_name 
          FROM appointments a
          JOIN users u ON a.user_id = u.user_id
          JOIN therapists t ON a.therapist_id = t.therapist_id
          JOIN rooms r ON a.room_id = r.room_id
          WHERE a.status != 'cancelled'";

$params = [];
if ($filter_date) {
    $query .= " AND a.appointment_date = ?";
    $params[] = $filter_date;
}
if ($therapist_id) {
    $query .= " AND a.therapist_id = ?";
    $params[] = $therapist_id;
}
$query .= " ORDER BY a.appointment_time ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

// Get therapists for filter dropdown
$therapists = $pdo->query("SELECT therapist_id, first_name, last_name FROM therapists WHERE status='active'")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments — Lumiére &amp; Bliss</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* ─── Design Tokens ─────────────────────────────────────────── */
        :root {
            --white:       #ffffff;
            --cream:       #fdfbf7;
            --gold:        #c9a96e;
            --gold-light:  #e8d5b0;
            --gold-dim:    rgba(201,169,110,0.14);
            --dark:        #1a1a1a;
            --dark-soft:   #2e2e2e;
            --muted:       #8a8070;
            --border:      rgba(201,169,110,0.22);
            --sidebar-w:   270px;
            --radius-lg:   18px;
            --radius-md:   12px;
            --shadow:      0 8px 32px rgba(26,26,26,0.07);
            --shadow-deep: 0 16px 48px rgba(26,26,26,0.13);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--cream);
            font-family: 'DM Sans', sans-serif;
            font-size: 18px;
            color: var(--dark);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ─── Sidebar ───────────────────────────────────────────────── */
        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--sidebar-w);
            background: var(--dark);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: transform .35s cubic-bezier(.4,0,.2,1);
        }
        .sidebar::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 30% 20%, rgba(201,169,110,0.07) 0%, transparent 60%);
            pointer-events: none;
        }
        .sidebar-brand {
            padding: 36px 28px 28px;
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .sidebar-brand-label {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            font-size: 1.55rem;
            color: var(--white);
            letter-spacing: .08em;
            line-height: 1.1;
        }
        .sidebar-brand-label em { font-style: italic; color: var(--gold); }
        .sidebar-brand-sub {
            font-size: .7rem;
            font-weight: 500;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted);
            margin-top: 4px;
        }
        .sidebar-nav {
            flex: 1;
            padding: 24px 0;
            overflow-y: auto;
        }
        .nav-section-label {
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--muted);
            padding: 16px 28px 8px;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 28px;
            color: rgba(255,255,255,.5);
            font-size: .88rem;
            font-weight: 500;
            text-decoration: none;
            transition: color .2s, background .2s;
            border-left: 3px solid transparent;
        }
        .nav-item i { font-size: 1.05rem; width: 20px; text-align: center; flex-shrink: 0; }
        .nav-item:hover {
            color: var(--gold-light);
            background: rgba(201,169,110,.06);
            border-left-color: rgba(201,169,110,.4);
        }
        .nav-item.active { color: var(--gold); background: rgba(201,169,110,.1); border-left-color: var(--gold); }
        .sidebar-footer { padding: 20px 0 28px; border-top: 1px solid var(--border); flex-shrink: 0; }
        .nav-item.danger { color: rgba(220,80,80,.7); }
        .nav-item.danger:hover { color: #e05555; background: rgba(220,80,80,.07); border-left-color: #e05555; }

        /* ─── Layout ────────────────────────────────────────────────── */
        .main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            padding: 48px 44px 60px;
            transition: margin .35s cubic-bezier(.4,0,.2,1);
        }

        /* ─── Topbar ────────────────────────────────────────────────── */
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
        .topbar-date-pill {
            font-size: .82rem;
            font-weight: 600;
            color: var(--gold);
            background: var(--gold-dim);
            border: 1px solid var(--border);
            border-radius: 50px;
            padding: 6px 16px;
            letter-spacing: .04em;
        }

        /* Gold rule */
        .gold-rule {
            width: 48px;
            height: 2px;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
            border-radius: 2px;
            margin: 16px 0 36px;
        }

        /* Section eyebrow */
        .section-eyebrow {
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 16px;
        }

        /* ─── Action button ─────────────────────────────────────────── */
        .btn-disable {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 24px;
            background: var(--dark);
            color: var(--gold-light);
            border: 1.5px solid var(--border);
            border-radius: 50px;
            font-size: .82rem;
            font-weight: 600;
            letter-spacing: .06em;
            cursor: pointer;
            text-decoration: none;
            transition: background .22s, color .22s, border-color .22s;
        }
        .btn-disable:hover {
            background: var(--dark-soft);
            color: var(--gold);
            border-color: var(--gold);
        }
        .btn-disable i { font-size: .9rem; }

        /* ─── Filter Card ───────────────────────────────────────────── */
        .filter-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 28px 32px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(201,169,110,.1);
            margin-bottom: 24px;
            animation: fadeUp .4s ease both;
        }
        .filter-card .form-label {
            font-size: .67rem;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
            display: block;
        }
        .filter-card .form-control,
        .filter-card .form-select {
            border: 1.5px solid rgba(201,169,110,.25);
            border-radius: var(--radius-md);
            background: var(--cream);
            color: var(--dark);
            font-size: .88rem;
            font-family: 'DM Sans', sans-serif;
            padding: 10px 14px;
            transition: border-color .2s, box-shadow .2s;
        }
        .filter-card .form-control:focus,
        .filter-card .form-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(201,169,110,.12);
            outline: none;
            background: var(--white);
        }
        .btn-apply {
            width: 100%;
            padding: 11px 0;
            background: var(--dark);
            color: var(--gold-light);
            border: none;
            border-radius: 50px;
            font-size: .82rem;
            font-weight: 600;
            letter-spacing: .08em;
            cursor: pointer;
            transition: background .22s, color .22s;
        }
        .btn-apply:hover { background: var(--dark-soft); color: var(--gold); }

        /* ─── Table Card ────────────────────────────────────────────── */
        .table-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            border: 1px solid rgba(201,169,110,.1);
            overflow: hidden;
            animation: fadeUp .45s .08s ease both;
        }
        .table-card-header {
            padding: 22px 32px 18px;
            border-bottom: 1px solid rgba(201,169,110,.12);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-card-header-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            font-size: 1.3rem;
            color: var(--dark);
        }
        .record-count {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--muted);
            background: var(--cream);
            border: 1px solid var(--border);
            border-radius: 50px;
            padding: 4px 14px;
        }

        /* Table styles */
        .appt-table { width: 100%; border-collapse: collapse; }
        .appt-table thead tr {
            background: var(--cream);
            border-bottom: 2px solid rgba(201,169,110,.15);
        }
        .appt-table thead th {
            font-size: .67rem;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted);
            padding: 14px 20px;
            white-space: nowrap;
        }
        .appt-table tbody tr {
            border-bottom: 1px solid rgba(201,169,110,.08);
            transition: background .18s;
        }
        .appt-table tbody tr:last-child { border-bottom: none; }
        .appt-table tbody tr:hover { background: rgba(201,169,110,.04); }
        .appt-table tbody td {
            padding: 16px 20px;
            font-size: .88rem;
            color: var(--dark-soft);
            vertical-align: middle;
        }

        .time-cell {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--dark);
            white-space: nowrap;
        }
        .client-name { font-weight: 600; color: var(--dark); }
        .therapist-name { color: var(--muted); font-size: .82rem; }

        /* Status badges */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
        }
        .badge-status::before {
            content: '';
            width: 5px; height: 5px;
            border-radius: 50%;
            background: currentColor;
        }
        .badge-confirmed { background: rgba(74,122,170,.1); color: #4a7aaa; }
        .badge-completed { background: rgba(90,138,90,.1); color: #5a8a5a; }

        /* View button */
        .btn-view {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            border: 1.5px solid rgba(201,169,110,.35);
            border-radius: 50px;
            background: transparent;
            color: var(--dark);
            font-size: .76rem;
            font-weight: 600;
            letter-spacing: .06em;
            cursor: pointer;
            transition: background .2s, border-color .2s, color .2s;
        }
        .btn-view:hover { background: var(--gold-dim); border-color: var(--gold); color: var(--dark); }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 64px 24px;
        }
        .empty-state-icon {
            font-size: 2.5rem;
            color: var(--gold);
            opacity: .35;
            margin-bottom: 16px;
        }
        .empty-state-text {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 400;
            font-size: 1.2rem;
            color: var(--muted);
        }

        /* ─── Modal ─────────────────────────────────────────────────── */
        .modal-content {
            border: none;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-deep);
            background: var(--white);
            overflow: hidden;
        }
        .modal-header {
            background: var(--dark);
            padding: 24px 32px 20px;
            border-bottom: 2px solid var(--border);
            position: relative;
        }
        .modal-header::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 20% 50%, rgba(201,169,110,0.08) 0%, transparent 60%);
            pointer-events: none;
        }
        .modal-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            font-size: 1.45rem;
            color: var(--white);
        }
        .modal-title span {
            display: block;
            font-family: 'DM Sans', sans-serif;
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 3px;
        }
        .btn-close-custom {
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 8px;
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            color: rgba(255,255,255,.6);
            font-size: .9rem;
            cursor: pointer;
            transition: background .2s, color .2s;
        }
        .btn-close-custom:hover { background: rgba(255,255,255,.15); color: var(--white); }

        .modal-body { padding: 32px; }
        .modal-field-label {
            font-size: .67rem;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
            display: block;
        }
        .modal-body .form-control,
        .modal-body textarea.form-control {
            border: 1.5px solid rgba(201,169,110,.25);
            border-radius: var(--radius-md);
            background: var(--cream);
            color: var(--dark);
            font-size: .88rem;
            font-family: 'DM Sans', sans-serif;
            padding: 11px 14px;
            transition: border-color .2s, box-shadow .2s;
        }
        .modal-body .form-control:focus,
        .modal-body textarea.form-control:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(201,169,110,.12);
            outline: none;
            background: var(--white);
        }
        .modal-footer {
            padding: 20px 32px 28px;
            border-top: 1px solid rgba(201,169,110,.12);
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        .btn-modal-cancel {
            padding: 10px 24px;
            border-radius: 50px;
            border: 1.5px solid rgba(201,169,110,.25);
            background: transparent;
            color: var(--muted);
            font-size: .82rem;
            font-weight: 600;
            letter-spacing: .06em;
            cursor: pointer;
            transition: border-color .2s, color .2s;
        }
        .btn-modal-cancel:hover { border-color: var(--gold); color: var(--dark); }
        .btn-modal-confirm {
            padding: 10px 28px;
            border-radius: 50px;
            border: none;
            background: #c0392b;
            color: var(--white);
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .08em;
            cursor: pointer;
            transition: background .2s;
            display: flex; align-items: center; gap: 7px;
        }
        .btn-modal-confirm:hover { background: #a93226; }

        /* Mobile overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.55);
            z-index: 999;
        }
        .mobile-toggle {
            display: none;
            position: fixed;
            top: 18px; left: 18px;
            z-index: 1100;
            background: var(--dark);
            border: 1px solid var(--border);
            color: var(--gold);
            width: 42px; height: 42px;
            border-radius: 10px;
            align-items: center; justify-content: center;
            font-size: 1.2rem;
            cursor: pointer;
        }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 80px 20px 40px; }
            .mobile-toggle { display: flex; }
            .sidebar-overlay.visible { display: block; }
        }
        @media (max-width: 600px) {
            .topbar { flex-direction: column; align-items: flex-start; gap: 12px; }
            .filter-card .row > div { margin-bottom: 12px; }
        }

        /* Animations */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- Mobile Toggle -->
<button class="mobile-toggle" id="mobileToggle" aria-label="Open menu">
    <i class="bi bi-list"></i>
</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ── Sidebar ─────────────────────────────────────────────────────── -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-label">Lumiére <em>&amp;</em> Bliss</div>
        <div class="sidebar-brand-sub">Administration Console</div>
    </div>
    <div class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <a href="dashboard.php" class="nav-item"><i class="bi bi-grid-1x2"></i> Dashboard</a>

        <div class="nav-section-label">Management</div>
        <a href="manage_appointment.php" class="nav-item active"><i class="bi bi-calendar-event"></i> Appointments</a>
        <a href="manage_treatments.php" class="nav-item"><i class="bi bi-droplet-half"></i> Treatments</a>
        <a href="manage_therapist.php" class="nav-item"><i class="bi bi-person-badge"></i> Therapists</a>
        <a href="manage_room.php" class="nav-item"><i class="bi bi-door-open"></i> Rooms</a>
        <a href="manage_account.php" class="nav-item"><i class="bi bi-people"></i> Accounts</a>

        <div class="nav-section-label">System</div>
        <a href="system_logs.php" class="nav-item"><i class="bi bi-shield-lock"></i> Audit Logs</a>
    </div>
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-item danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
    </div>
</nav>

<!-- ── Main Content ────────────────────────────────────────────────── -->
<div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-title">
            <span>Appointment Management</span>
            Schedule Overview
        </div>
        <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
            <div class="topbar-date-pill">
                <i class="bi bi-calendar3 me-1"></i>
                <?= date('F d, Y', strtotime($filter_date)) ?>
            </div>
            <button class="btn-disable" data-bs-toggle="modal" data-bs-target="#disableDateModal">
                <i class="bi bi-calendar-x"></i> Disable a Date
            </button>
        </div>
    </div>

    <div class="gold-rule"></div>

    <!-- ── Filter Card ─────────────────────────────────────────────── -->
    <p class="section-eyebrow">Refine Results</p>
    <div class="filter-card">
        <form class="row g-3" method="GET">
            <div class="col-md-4">
                <label class="form-label">Filter by Date</label>
                <input type="date" name="filter_date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Filter by Therapist</label>
                <select name="therapist_id" class="form-select">
                    <option value="">All Therapists</option>
                    <?php foreach($therapists as $t): ?>
                        <option value="<?= $t['therapist_id'] ?>" <?= $therapist_id == $t['therapist_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn-apply">
                    Apply Filter <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- ── Table Card ──────────────────────────────────────────────── -->
    <p class="section-eyebrow">Appointments</p>
    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-header-title">Daily Schedule</div>
            <div class="record-count"><?= count($appointments) ?> record<?= count($appointments) !== 1 ? 's' : '' ?></div>
        </div>

        <div style="overflow-x:auto;">
            <table class="appt-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Client</th>
                        <th>Therapist</th>
                        <th>Room</th>
                        <th>Status</th>
                        <th style="text-align:right; padding-right:28px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($appointments)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="bi bi-calendar2-x"></i></div>
                                    <div class="empty-state-text">No appointments found for this selection</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($appointments as $app): ?>
                        <tr>
                            <td class="time-cell">
                                <?= date('g:i A', strtotime($app['appointment_time'])) ?>
                            </td>
                            <td>
                                <div class="client-name"><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></div>
                            </td>
                            <td>
                                <div class="therapist-name"><?= htmlspecialchars($app['t_fname'] . ' ' . $app['t_lname']) ?></div>
                            </td>
                            <td>
                                <div style="font-size:.85rem; color:var(--muted);"><?= htmlspecialchars($app['room_name']) ?></div>
                            </td>
                            <td>
                                <?php if($app['status'] === 'confirmed'): ?>
                                    <span class="badge-status badge-confirmed">Confirmed</span>
                                <?php else: ?>
                                    <span class="badge-status badge-completed">Completed</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right; padding-right:28px;">

                                <button class="btn-view"
                                    onclick='viewAppointment(<?= json_encode($app) ?>)'>

                                    View <i class="bi bi-arrow-right" style="font-size:.7rem;"></i>

                                </button>

                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /main-content -->


<!-- ── Disable Date Modal ──────────────────────────────────────────── -->
<div class="modal fade" id="disableDateModal" tabindex="-1" aria-labelledby="disableDateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content">
            <form id="disableDateForm">
                <div class="modal-header d-flex justify-content-between align-items-center">
                    <div class="modal-title">
                        <span>Studio Calendar</span>
                        Disable a Date
                    </div>
                    <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="mb-4">
                        <label class="modal-field-label">Select Date to Disable</label>
                        <input type="date" name="disabled_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div>
                        <label class="modal-field-label">Reason / Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3"
                            placeholder="e.g. Public holiday, emergency maintenance…" required></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-modal-confirm" onclick="disableDate()">
                        <i class="bi bi-calendar-x"></i> Disable Date
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Appointment Summary Modal -->
<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300;1,400&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
:root{
    --gold:#c9a96e;
    --gold-light:#e8d5aa;
    --gold-dim:rgba(201,169,110,.15);
    --dark:#0d0d0d;
    --surface:#141414;
    --surface2:#1a1a1a;
    --border:rgba(201,169,110,.18);
    --text-muted:rgba(255,255,255,.4);
}

/* Modal */
#appointmentSummaryModal .modal-content{
    background:var(--surface);
    border:1px solid var(--border)!important;
    border-radius:8px!important;
    overflow:hidden;
    box-shadow:
        0 40px 100px rgba(0,0,0,.8),
        0 0 0 1px var(--border);
}

/* Top gold line */
.modal-crown{
    height:3px;
    background:linear-gradient(
        90deg,
        transparent 0%,
        var(--gold) 30%,
        var(--gold-light) 50%,
        var(--gold) 70%,
        transparent 100%
    );
}

/* Header */
#appointmentSummaryModal .modal-header{
    background:var(--dark);
    border-bottom:1px solid var(--border)!important;
    padding:1.75rem 2.25rem 1.5rem;
    position:relative;
    overflow:hidden;
}

#appointmentSummaryModal .modal-header::before{
    content:'';
    position:absolute;
    inset:0;
    background:
        radial-gradient(
            ellipse 60% 120% at 50% -10%,
            rgba(201,169,110,.08) 0%,
            transparent 70%
        );
}

.header-eyebrow{
    font-family:'Montserrat',sans-serif;
    font-size:.65rem;
    letter-spacing:.3em;
    text-transform:uppercase;
    color:var(--gold);
    opacity:.7;
    margin-bottom:.3rem;
}

.modal-title-main{
    font-family:'Cormorant Garamond',serif;
    font-size:2rem;
    font-weight:300;
    color:#fff;
    letter-spacing:.04em;
}

.modal-title-main em{
    color:var(--gold);
    font-style:italic;
}

.modal-ref{
    font-family:'Montserrat',sans-serif;
    font-size:.6rem;
    letter-spacing:.18em;
    color:var(--text-muted);
    margin-top:.4rem;
}

/* Close */
.btn-close-custom{
    background:transparent;
    border:1px solid rgba(255,255,255,.12);
    color:rgba(255,255,255,.5);
    width:34px;
    height:34px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    transition:.3s;
}

.btn-close-custom:hover{
    border-color:var(--gold);
    color:var(--gold);
    transform:rotate(90deg);
}

/* Body */
#appointmentSummaryModal .modal-body{
    padding:0;
    background:var(--surface);
}

/* Divider */
.section-divider{
    display:flex;
    align-items:center;
    gap:.75rem;
    padding:.75rem 2.25rem;
    background:var(--dark);
}

.section-divider span{
    font-family:'Montserrat',sans-serif;
    font-size:.58rem;
    letter-spacing:.25em;
    text-transform:uppercase;
    color:var(--gold);
    opacity:.65;
}

.section-divider::before,
.section-divider::after{
    content:'';
    flex:1;
    height:1px;
    background:linear-gradient(90deg,transparent,var(--border));
}

.section-divider::after{
    background:linear-gradient(270deg,transparent,var(--border));
}

/* Rows */
.summary-row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:1rem 2.25rem;
    border-bottom:1px solid rgba(255,255,255,.04);
    transition:.2s;
}

.summary-row:hover{
    background:rgba(255,255,255,.02);
}

.row-label{
    font-family:'Montserrat',sans-serif;
    font-size:.68rem;
    letter-spacing:.14em;
    text-transform:uppercase;
    color:var(--text-muted);
}

.row-value{
    font-family:'Cormorant Garamond',serif;
    font-size:1.15rem;
    color:rgba(255,255,255,.9);
    text-align:right;
}

/* Total */
.total-area{
    background:var(--dark);
    padding:1.6rem 2.25rem 1.8rem;
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
    position:relative;
    overflow:hidden;
}

.total-area::before{
    content:'';
    position:absolute;
    inset:0;
    background:
        radial-gradient(
            ellipse 80% 120% at 80% 110%,
            rgba(201,169,110,.06) 0%,
            transparent 65%
        );
}

.total-label-sub{
    font-family:'Montserrat',sans-serif;
    font-size:.6rem;
    letter-spacing:.25em;
    text-transform:uppercase;
    color:var(--gold);
    opacity:.65;
}

.total-label{
    font-family:'Cormorant Garamond',serif;
    font-size:1.15rem;
    color:rgba(255,255,255,.55);
}

.total-amount{
    font-family:'Cormorant Garamond',serif;
    font-size:3rem;
    line-height:1;
    font-weight:300;
    color:var(--gold);
}

.total-amount sup{
    font-size:1rem;
    color:var(--gold-light);
}

/* Footer */
.modal-footer-custom{
    padding:1.25rem 2.25rem 1.75rem;
    display:flex;
    justify-content:flex-end;
    gap:.75rem;
    border-top:1px solid var(--border);
    background:var(--surface);
}

.btn-outline-gold{
    font-family:'Montserrat',sans-serif;
    font-size:.65rem;
    letter-spacing:.18em;
    text-transform:uppercase;
    background:transparent;
    border:1px solid var(--border);
    color:rgba(255,255,255,.45);
    padding:.7rem 1.5rem;
    transition:.3s;
}

.btn-outline-gold:hover{
    border-color:rgba(255,255,255,.25);
    color:#fff;
}

.btn-gold{
    font-family:'Montserrat',sans-serif;
    font-size:.65rem;
    letter-spacing:.18em;
    text-transform:uppercase;
    background:var(--gold);
    border:none;
    color:#000;
    padding:.7rem 2rem;
    font-weight:600;
    transition:.3s;
}

.btn-gold:hover{
    background:var(--gold-light);
}
</style>

<!-- Appointment Summary Modal -->
<div class="modal fade"
     id="appointmentSummaryModal"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content border-0">

            <!-- Gold Line -->
            <div class="modal-crown"></div>

            <!-- Header -->
            <div class="modal-header border-0 d-flex justify-content-between align-items-start">

                <div>
                    <div class="header-eyebrow">
                        Booking Confirmation
                    </div>

                    <h3 class="modal-title-main">
                        Appointment <em>Summary</em>
                    </h3>

                    <div class="modal-ref">
                        REF #APT-2026-00847
                    </div>
                </div>

                <button class="btn-close-custom"
                        data-bs-dismiss="modal">
                    ✕
                </button>

            </div>

            <!-- Body -->
            <div class="modal-body">

                <!-- Guest -->
                <div class="section-divider">
                    <span>Guest Details</span>
                </div>

                <div class="summary-row">
                    <span class="row-label">Your Name</span>
                    <span class="row-value" id="sum_name"></span>
                </div>

                <div class="summary-row">
                    <span class="row-label">Date & Time</span>
                    <span class="row-value" id="sum_datetime"></span>
                </div>

                <div class="summary-row">
                    <span class="row-label">Specialist</span>
                    <span class="row-value" id="sum_therapist"></span>
                </div>

                <div class="summary-row">
                    <span class="row-label">Suite</span>
                    <span class="row-value" id="sum_room"></span>
                </div>

                <!-- Service -->
                <div class="section-divider">
                    <span>Service</span>
                </div>

                <div class="summary-row">
                    <span class="row-label">Treatment / Package</span>
                    <span class="row-value" id="sum_service"></span>
                </div>

                <div class="summary-row">
                    <span class="row-label">Subtotal</span>
                    <span class="row-value" id="sum_subtotal"></span>
                </div>

                <div class="summary-row">
                    <span class="row-label">VAT (12%)</span>
                    <span class="row-value" id="sum_vat"></span>
                </div>

                <!-- Total -->
                <div class="total-area">

                    <div>
                        <div class="total-label-sub">
                            Amount Payable
                        </div>

                        <div class="total-label">
                            Total Due
                        </div>
                    </div>

                    <div class="total-amount"
                         id="sum_total">
                    </div>

                </div>

            </div>

            <!-- Footer -->
            <div class="modal-footer-custom">

                <button class="btn-outline-gold"
                        data-bs-dismiss="modal">
                    Close
                </button>

            </div>

        </div>

    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ── Mobile sidebar ──────────────────────────────────────────────
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('sidebarOverlay');
    const toggle   = document.getElementById('mobileToggle');
    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('visible');
    });
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('visible');
    });

    // ── Disable Date (unchanged logic) ─────────────────────────────
    function disableDate() {
        const form     = document.getElementById('disableDateForm');
        const formData = new FormData(form);
        formData.append('disable_date_btn', '1');

        fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Disabled!',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                setTimeout(() => location.reload(), 1000);
            } else {
                Swal.fire({ icon: 'error', title: 'Oops…', text: data.message });
            }
        })
        .catch(error => {
            Swal.fire({ icon: 'error', title: 'Server Error', text: 'Something went wrong!' });
            console.error(error);
        });
    }
    function viewAppointment(data) {

        // Full Name
        document.getElementById('sum_name').textContent =
            data.first_name + ' ' + data.last_name;

        // Date & Time
        const dateObj = new Date(
            data.appointment_date + 'T' + data.appointment_time
        );

        document.getElementById('sum_datetime').textContent =
            dateObj.toLocaleString('en-US', {
                weekday: 'long',
                month: 'long',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

        // Therapist
        document.getElementById('sum_therapist').textContent =
            data.t_fname + ' ' + data.t_lname;

        // Room
        document.getElementById('sum_room').textContent =
            data.room_name;

        // Service
        document.getElementById('sum_service').textContent =
            '₱' + parseFloat(data.subtotal).toFixed(2);

        // Subtotal
        document.getElementById('sum_subtotal').textContent =
            '₱' + parseFloat(data.subtotal).toFixed(2);

        // VAT
        document.getElementById('sum_vat').textContent =
            '₱' + parseFloat(data.vat).toFixed(2);

        // Total
        document.getElementById('sum_total').textContent =
            '₱' + parseFloat(data.total_amount).toFixed(2);

        // Open Modal
        new bootstrap.Modal(
            document.getElementById('appointmentSummaryModal')
        ).show();

    }
</script>
</body>
</html>