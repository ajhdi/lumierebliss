<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}

// Handle Room Save (Add/Edit)
if (isset($_POST['save_room'])) {
    header('Content-Type: application/json');
    try {
        $room_name = $_POST['room_name'];
        $room_type = $_POST['room_type'];
        $fee       = $_POST['additional_fee'];
        $id        = $_POST['room_id'] ?? '';

        if (!empty($id)) {
            $stmt = $pdo->prepare("UPDATE rooms SET room_name=?, room_type=?, additional_fee=? WHERE room_id=?");
            $stmt->execute([$room_name, $room_type, $fee, $id]);
            echo json_encode(["status" => "success", "message" => "Room updated successfully"]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO rooms (room_name, room_type, additional_fee) VALUES (?, ?, ?)");
            $stmt->execute([$room_name, $room_type, $fee]);
            echo json_encode(["status" => "success", "message" => "Room added successfully"]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit();
}

if (isset($_POST['archive_room_id'])) {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->prepare("UPDATE rooms SET status = 'archived' WHERE room_id = ?");
        $stmt->execute([$_POST['archive_room_id']]);
        echo json_encode(["status" => "success", "message" => "Room archived successfully"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

$room_type_filter = $_GET['room_type'] ?? '';
$status_filter    = $_GET['status']    ?? '';

$query  = "SELECT * FROM rooms WHERE 1=1";
$params = [];

if (!empty($room_type_filter)) {
    $query   .= " AND room_type = ?";
    $params[] = $room_type_filter;
}
if (!empty($status_filter)) {
    $query   .= " AND status = ?";
    $params[] = $status_filter;
}
$query .= " ORDER BY room_type ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$room_types = $pdo->query("SELECT DISTINCT room_type FROM rooms ORDER BY room_type ASC")->fetchAll();
$statuses   = $pdo->query("SELECT DISTINCT status    FROM rooms ORDER BY status    ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spa Rooms — Lumiére &amp; Bliss</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
            font-size: 18px;
            color: var(--dark);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ─── Sidebar ────────────────────────────────────────────── */
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
            background: radial-gradient(ellipse at 30% 20%, rgba(201,169,110,.07) 0%, transparent 60%);
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
        .sidebar-nav { flex: 1; padding: 24px 0; overflow-y: auto; }
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
        .nav-item:hover  { color: var(--gold-light); background: rgba(201,169,110,.06); border-left-color: rgba(201,169,110,.4); }
        .nav-item.active { color: var(--gold); background: rgba(201,169,110,.1); border-left-color: var(--gold); }
        .sidebar-footer { padding: 20px 0 28px; border-top: 1px solid var(--border); flex-shrink: 0; }
        .nav-item.danger { color: rgba(220,80,80,.7); }
        .nav-item.danger:hover { color: #e05555; background: rgba(220,80,80,.07); border-left-color: #e05555; }

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
            width: 48px;
            height: 2px;
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

        /* ─── Buttons ────────────────────────────────────────────── */
        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 26px;
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
        .btn-add:hover { background: var(--dark-soft); color: var(--gold); border-color: var(--gold); }

        /* ─── Filter Card ────────────────────────────────────────── */
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

        /* ─── Table Card ─────────────────────────────────────────── */
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
            gap: 16px;
            flex-wrap: wrap;
        }
        .table-card-header-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            font-size: 1.3rem;
            color: var(--dark);
        }
        .search-wrap { position: relative; }
        .search-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: .85rem;
            pointer-events: none;
        }
        .search-input {
            padding: 9px 14px 9px 36px;
            border: 1.5px solid rgba(201,169,110,.25);
            border-radius: 50px;
            background: var(--cream);
            color: var(--dark);
            font-size: .82rem;
            font-family: 'DM Sans', sans-serif;
            width: 220px;
            transition: border-color .2s, box-shadow .2s, width .3s;
        }
        .search-input:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(201,169,110,.12);
            width: 260px;
            background: var(--white);
        }

        /* Table */
        .room-table { width: 100%; border-collapse: collapse; }
        .room-table thead tr {
            background: var(--cream);
            border-bottom: 2px solid rgba(201,169,110,.15);
        }
        .room-table thead th {
            font-size: .67rem;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted);
            padding: 14px 20px;
            white-space: nowrap;
        }
        .room-table tbody tr {
            border-bottom: 1px solid rgba(201,169,110,.08);
            transition: background .18s;
        }
        .room-table tbody tr:last-child { border-bottom: none; }
        .room-table tbody tr:hover { background: rgba(201,169,110,.04); }
        .room-table tbody td {
            padding: 16px 20px;
            font-size: .88rem;
            color: var(--dark-soft);
            vertical-align: middle;
        }
        .room-name-cell {
            font-weight: 700;
            color: var(--dark);
            font-size: .95rem;
        }

        /* Type badge */
        .badge-type {
            display: inline-block;
            padding: 4px 12px;
            background: var(--gold-dim);
            border: 1px solid var(--border);
            border-radius: 50px;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--dark-soft);
        }

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
        .badge-available { background: rgba(90,138,90,.1); color: #5a8a5a; }
        .badge-archived  { background: rgba(180,60,60,.08); color: #b43c3c; }
        .badge-other     { background: rgba(138,128,112,.1); color: var(--muted); }

        /* Fee cell */
        .fee-cell {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            font-size: 1.05rem;
            color: var(--dark);
        }
        .fee-cell span { font-size: .75rem; color: var(--gold); margin-right: 1px; }

        /* Action buttons */
        .btn-icon {
            width: 34px; height: 34px;
            border-radius: 10px;
            border: 1.5px solid rgba(201,169,110,.25);
            background: transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .85rem;
            cursor: pointer;
            transition: background .2s, border-color .2s, color .2s;
            color: var(--dark-soft);
        }
        .btn-icon:hover { background: var(--gold-dim); border-color: var(--gold); color: var(--dark); }
        .btn-icon.danger { color: #b43c3c; border-color: rgba(180,60,60,.25); }
        .btn-icon.danger:hover { background: rgba(180,60,60,.08); border-color: #b43c3c; }

        /* Empty state */
        .empty-state { text-align: center; padding: 64px 24px; }
        .empty-state-icon { font-size: 2.5rem; color: var(--gold); opacity: .35; margin-bottom: 16px; }
        .empty-state-text { font-family: 'Cormorant Garamond', serif; font-weight: 400; font-size: 1.2rem; color: var(--muted); }

        /* ─── Modal ──────────────────────────────────────────────── */
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
            background: radial-gradient(ellipse at 20% 50%, rgba(201,169,110,.08) 0%, transparent 60%);
            pointer-events: none;
        }
        .modal-title-wrap { flex: 1; }
        .modal-eyebrow {
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 3px;
        }
        .modal-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            font-size: 1.45rem;
            color: var(--white);
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
            flex-shrink: 0;
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
        .modal-body .form-select {
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
        .modal-body .form-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(201,169,110,.12);
            outline: none;
            background: var(--white);
        }
        .field-hint { font-size: .73rem; color: var(--muted); margin-top: 6px; }
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
        .btn-modal-save {
            padding: 10px 28px;
            border-radius: 50px;
            border: none;
            background: var(--dark);
            color: var(--gold-light);
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .08em;
            cursor: pointer;
            transition: background .2s, color .2s;
            display: flex; align-items: center; gap: 7px;
        }
        .btn-modal-save:hover { background: var(--dark-soft); color: var(--gold); }

        /* Mobile */
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
            .search-input { width: 100%; }
            .search-input:focus { width: 100%; }
            .table-card-header { flex-direction: column; align-items: flex-start; }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- Mobile Toggle -->
<button class="mobile-toggle" id="mobileToggle" aria-label="Open menu"><i class="bi bi-list"></i></button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ── Sidebar ──────────────────────────────────────────────────────── -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-label">Lumiére <em>&amp;</em> Bliss</div>
        <div class="sidebar-brand-sub">Administration Console</div>
    </div>
    <div class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <a href="dashboard.php" class="nav-item"><i class="bi bi-grid-1x2"></i> Dashboard</a>

        <div class="nav-section-label">Management</div>
        <a href="manage_appointment.php" class="nav-item"><i class="bi bi-calendar-event"></i> Appointments</a>
        <a href="manage_treatments.php"  class="nav-item"><i class="bi bi-droplet-half"></i>  Treatments</a>
        <a href="manage_therapist.php"   class="nav-item"><i class="bi bi-person-badge"></i>  Therapists</a>
        <a href="manage_room.php"        class="nav-item active"><i class="bi bi-door-open"></i> Rooms</a>
        <a href="manage_account.php"     class="nav-item"><i class="bi bi-people"></i>         Accounts</a>

        <div class="nav-section-label">System</div>
        <a href="system_logs.php" class="nav-item"><i class="bi bi-shield-lock"></i> Audit Logs</a>
    </div>
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-item danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
    </div>
</nav>

<!-- ── Main Content ──────────────────────────────────────────────────── -->
<div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-title">
            <span>Room Management</span>
            Spa Rooms
        </div>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#roomModal">
            <i class="bi bi-plus-lg"></i> Add New Room
        </button>
    </div>
    <div class="gold-rule"></div>

    <!-- Filter Card -->
    <p class="section-eyebrow">Refine Results</p>
    <div class="filter-card">
        <form class="row g-3" method="GET">
            <div class="col-md-4">
                <label class="form-label">Room Type</label>
                <select name="room_type" class="form-select">
                    <option value="">All Room Types</option>
                    <?php foreach ($room_types as $t): ?>
                        <option value="<?= htmlspecialchars($t['room_type']) ?>"
                            <?= $room_type_filter == $t['room_type'] ? 'selected' : '' ?>>
                            <?= ucfirst(htmlspecialchars($t['room_type'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= htmlspecialchars($s['status']) ?>"
                            <?= $status_filter == $s['status'] ? 'selected' : '' ?>>
                            <?= ucfirst(htmlspecialchars($s['status'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn-apply">Apply Filter <i class="bi bi-arrow-right ms-1"></i></button>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <p class="section-eyebrow">Directory</p>
    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-header-title">All Rooms &nbsp;<span style="font-family:'DM Sans',sans-serif;font-size:.8rem;color:var(--muted);font-weight:500;">(<?= count($rooms) ?>)</span></div>
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="roomSearch" class="search-input" placeholder="Search room name…">
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table class="room-table" id="roomTable">
                <thead>
                    <tr>
                        <th>Room Name</th>
                        <th>Type</th>
                        <th>Additional Fee</th>
                        <th>Status</th>
                        <th style="text-align:right; padding-right:28px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rooms)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="bi bi-door-open"></i></div>
                                    <div class="empty-state-text">No rooms found for this filter</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rooms as $r): ?>
                        <tr>
                            <td class="room-name"><?= htmlspecialchars($r['room_name']) ?></td>
                            <td><span class="badge-type"><?= htmlspecialchars($r['room_type']) ?></span></td>
                            <td>
                                <div class="fee-cell">
                                    <span>₱</span><?= number_format($r['additional_fee'], 2) ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                    $s = strtolower($r['status']);
                                    $cls = $s === 'available' ? 'badge-available' : ($s === 'archived' ? 'badge-archived' : 'badge-other');
                                ?>
                                <span class="badge-status <?= $cls ?>"><?= ucfirst(htmlspecialchars($r['status'])) ?></span>
                            </td>
                            <td style="text-align:right; padding-right:28px;">
                                <div style="display:inline-flex; gap:8px;">
                                    <button class="btn-icon" title="Edit" onclick='editRoom(<?= json_encode($r) ?>)'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn-icon danger" title="Archive" onclick="archiveRoom(<?= (int)$r['room_id'] ?>)">
                                        <i class="bi bi-archive"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /main-content -->


<!-- ── Add/Edit Room Modal ─────────────────────────────────────────── -->
<div class="modal fade" id="roomModal" tabindex="-1" aria-labelledby="roomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content">
            <form id="roomForm">
                <div class="modal-header d-flex justify-content-between align-items-center">
                    <div class="modal-title-wrap">
                        <div class="modal-eyebrow" id="modalEyebrow">New Room</div>
                        <div class="modal-title" id="roomModalTitle">Add a Spa Room</div>
                    </div>
                    <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="room_id" id="room_id">

                    <div class="mb-4">
                        <label class="modal-field-label">Room Name</label>
                        <input type="text" name="room_name" id="room_name" class="form-control"
                            placeholder="e.g. Serenity Suite 1" required>
                    </div>

                    <div class="mb-4">
                        <label class="modal-field-label">Room Type</label>
                        <select name="room_type" id="room_type" class="form-select" required>
                            <option value="Standard Room">Standard Room</option>
                            <option value="Couple Room">Couple Room</option>
                            <option value="Private Room">Private Room</option>
                            <option value="Premium Suite">Premium Suite</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="modal-field-label">Additional Fee (₱)</label>
                        <input type="number" step="0.01" name="additional_fee" id="additional_fee"
                            class="form-control" value="0.00" required>
                        <div class="field-hint">Fee charged to non-members or for premium upgrades.</div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-modal-save" onclick="saveRoom()">
                        <i class="bi bi-check-lg"></i> Save Room
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ── Mobile sidebar ─────────────────────────────────────────────
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    document.getElementById('mobileToggle').addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('visible');
    });
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('visible');
    });

    // ── Edit Room (unchanged logic) ────────────────────────────────
    function editRoom(data) {
        document.getElementById('modalEyebrow').innerText  = 'Edit Room';
        document.getElementById('roomModalTitle').innerText = 'Edit Room Details';
        document.getElementById('room_id').value        = data.room_id;
        document.getElementById('room_name').value      = data.room_name;
        document.getElementById('room_type').value      = data.room_type;
        document.getElementById('additional_fee').value = data.additional_fee;
        new bootstrap.Modal(document.getElementById('roomModal')).show();
    }

    document.getElementById('roomModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('roomForm').reset();
        document.getElementById('room_id').value = '';
        document.getElementById('modalEyebrow').innerText   = 'New Room';
        document.getElementById('roomModalTitle').innerText = 'Add a Spa Room';
    });

    // ── Save Room (unchanged logic) ────────────────────────────────
    function saveRoom() {
        const form     = document.getElementById('roomForm');
        const formData = new FormData(form);
        formData.append('save_room', '1');

        fetch(window.location.pathname, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Saved!', text: data.message, timer: 1500, showConfirmButton: false });
                    bootstrap.Modal.getInstance(document.getElementById('roomModal')).hide();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Swal.fire({ icon: 'error', title: 'Oops…', text: data.message });
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Server Error', text: 'Something went wrong!' }));
    }

    // ── Archive Room (unchanged logic) ─────────────────────────────
    function archiveRoom(roomId) {
        Swal.fire({
            title: 'Archive this room?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, archive it!',
            cancelButtonText: 'Cancel'
        }).then(result => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('archive_room_id', roomId);
                fetch('manage_room.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Archived!', text: data.message, timer: 1500, showConfirmButton: false });
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Oops…', text: data.message });
                        }
                    })
                    .catch(() => Swal.fire({ icon: 'error', title: 'Server Error', text: 'Something went wrong!' }));
            }
        });
    }

    // ── Live search (unchanged logic) ─────────────────────────────
    document.getElementById('roomSearch').addEventListener('keyup', function () {
        const search = this.value.toLowerCase();
        document.querySelectorAll('#roomTable tbody tr').forEach(row => {
            const name = row.querySelector('.room-name');
            row.style.display = name && name.textContent.toLowerCase().includes(search) ? '' : 'none';
        });
    });
</script>
</body>
</html>