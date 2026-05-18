<?php
session_start();
require_once '../config/db.php';
require_once '../includes/log_action.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}

if (isset($_POST['save_therapist'])) {
    $id          = $_POST['therapist_id'];
    $first_name  = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name   = $_POST['last_name'];
    $gender      = $_POST['gender']         ?? '';
    $specialty   = $_POST['specialty']      ?? '';
    $experience  = $_POST['work_experience'] ?? '';
    $status      = $_POST['status'];

    $params  = [$first_name, $middle_name, $last_name, $gender, $specialty, $experience, $status];
    $img_sql = "";

    if (!empty($_FILES['profile_pic']['name'])) {
        $image_name = time() . '_' . $_FILES['profile_pic']['name'];
        $target_dir = "../assets/img/therapists/";

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_dir . $image_name)) {
            $img_sql  = ", profile_picture = ?";
            $params[] = $image_name;
        }
    }

    $params[] = $id;
    $sql      = "UPDATE therapists SET first_name=?, middle_name=?, last_name=?, gender=?, specialty=?, work_experience=?, status=? $img_sql WHERE therapist_id=?";
    $stmt     = $pdo->prepare($sql);
    $stmt->execute($params);

    $fullName = $first_name . ' ' . $last_name;
    logAction($pdo, 'Edit Therapist', "Updated therapist profile: {$fullName} (ID: {$id}), Status: {$status}");

    $pdo->prepare("DELETE FROM therapist_schedule WHERE therapist_id = ?")->execute([$id]);

    if ($status === 'active' && isset($_POST['schedule_times'])) {
        $unique_times = array_unique(array_filter($_POST['schedule_times']));
        $sched_stmt   = $pdo->prepare("INSERT INTO therapist_schedule (therapist_id, time_start) VALUES (?, ?)");
        foreach ($unique_times as $time) {
            $sched_stmt->execute([$id, $time]);
        }
        $timeList = implode(', ', $unique_times);
        logAction($pdo, 'Edit Therapist Schedule', "Updated schedule for therapist ID: {$id} — Slots: {$timeList}");
    }

    header("Location: manage_therapist.php?msg=updated");
    exit();
}

$stmt       = $pdo->query("SELECT * FROM therapists ORDER BY last_name ASC");
$therapists = $stmt->fetchAll();

$sched_stmt   = $pdo->query("SELECT therapist_id, time_start FROM therapist_schedule ORDER BY time_start ASC");
$all_schedules = $sched_stmt->fetchAll(PDO::FETCH_GROUP);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Therapists — Lumiére &amp; Bliss</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* ─── Design Tokens ───────────────────────────────────────── */
        :root {
            --white:       #ffffff;
            --cream:       #fdfbf7;
            --gold:        #c9a96e;
            --gold-light:  #e8d5b0;
            --gold-dim:    rgba(201,169,110,.13);
            --dark:        #0d0d0d;
            --dark-soft:   #2e2e2e;
            --muted:       #8a8070;
            --border:      rgba(201,169,110,.22);
            --sidebar-w:   270px;
            --radius-lg:   18px;
            --radius-md:   12px;
            --shadow:      0 8px 32px rgba(26,26,26,.07);
            --shadow-deep: 0 24px 64px rgba(26,26,26,.18);
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

        /* ─── Table ──────────────────────────────────────────────── */
        .therapist-table { width: 100%; border-collapse: collapse; }
        .therapist-table thead tr {
            background: var(--cream);
            border-bottom: 2px solid rgba(201,169,110,.15);
        }
        .therapist-table thead th {
            font-size: .67rem;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted);
            padding: 14px 20px;
            white-space: nowrap;
        }
        .therapist-table tbody tr {
            border-bottom: 1px solid rgba(201,169,110,.08);
            transition: background .18s;
        }
        .therapist-table tbody tr:last-child { border-bottom: none; }
        .therapist-table tbody tr:hover { background: rgba(201,169,110,.04); }
        .therapist-table tbody td {
            padding: 16px 20px;
            font-size: .88rem;
            color: var(--dark-soft);
            vertical-align: middle;
        }

        /* ─── Avatar Cell ─────────────────────────────────────────── */
        .therapist-avatar-cell { display: flex; align-items: center; gap: 14px; }
        .therapist-avatar {
            width: 46px; height: 46px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border);
            flex-shrink: 0;
            background: var(--cream);
        }
        .therapist-name { font-weight: 700; color: var(--dark); font-size: .95rem; line-height: 1.2; }
        .therapist-sub  { font-size: .76rem; color: var(--muted); margin-top: 2px; }

        /* ─── Badges ─────────────────────────────────────────────── */
        .badge-specialty {
            display: inline-block;
            padding: 4px 12px; border-radius: 50px;
            font-size: .72rem; font-weight: 600; letter-spacing: .06em;
            background: var(--gold-dim); color: #7a6240;
            border: 1px solid rgba(201,169,110,.28);
        }
        .badge-gender {
            display: inline-block;
            padding: 4px 12px; border-radius: 50px;
            font-size: .72rem; font-weight: 600; letter-spacing: .05em;
            background: rgba(26,26,26,.06); color: var(--dark-soft);
            border: 1px solid rgba(26,26,26,.1);
        }
        .badge-active {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 12px; border-radius: 50px;
            font-size: .72rem; font-weight: 600;
            background: rgba(34,139,70,.08); color: #1a6b36;
            border: 1px solid rgba(34,139,70,.2);
        }
        .badge-active::before  { content:''; width:6px; height:6px; border-radius:50%; background:#22a855; display:block; }
        .badge-inactive {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 12px; border-radius: 50px;
            font-size: .72rem; font-weight: 600;
            background: rgba(180,60,60,.07); color: #8b2222;
            border: 1px solid rgba(180,60,60,.18);
        }
        .badge-inactive::before { content:''; width:6px; height:6px; border-radius:50%; background:#c04444; display:block; }

        /* ─── Schedule Pills ──────────────────────────────────────── */
        .schedule-pills { display: flex; flex-wrap: wrap; gap: 5px; }
        .schedule-pill {
            padding: 3px 10px; border-radius: 50px;
            font-size: .72rem; font-weight: 500;
            background: var(--cream); color: var(--dark-soft);
            border: 1px solid rgba(201,169,110,.2);
            white-space: nowrap;
        }

        /* ─── Action Buttons ─────────────────────────────────────── */
        .btn-icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 34px; height: 34px; border-radius: 50%;
            border: 1.5px solid rgba(201,169,110,.25);
            background: var(--cream); color: var(--dark-soft);
            font-size: .85rem; cursor: pointer;
            transition: background .2s, border-color .2s, color .2s;
        }
        .btn-icon:hover { background: var(--gold-dim); border-color: var(--gold); color: var(--dark); }

        /* ─── Empty State ────────────────────────────────────────── */
        .empty-state { text-align: center; padding: 56px 24px; }
        .empty-state-icon { font-size: 2.8rem; color: var(--gold-light); margin-bottom: 16px; }
        .empty-state-text { font-size: .9rem; color: var(--muted); }

        /* ─── Modal Base ─────────────────────────────────────────── */
        .modal-content {
            border: none;
            border-radius: 18px;
            box-shadow: 0 32px 80px rgba(0,0,0,.22);
            overflow: hidden;
        }
        .btn-modal-cancel {
            padding: 10px 24px;
            border: 1.5px solid rgba(26,26,26,.15);
            border-radius: 8px; background: transparent;
            font-family: 'DM Sans', sans-serif;
            font-size: .875rem; font-weight: 500;
            color: var(--muted); cursor: pointer;
            transition: all .18s ease;
        }
        .btn-modal-cancel:hover { border-color: var(--dark); color: var(--dark); }
        .btn-modal-save {
            padding: 10px 28px; border: none; border-radius: 8px;
            background: var(--dark);
            font-family: 'DM Sans', sans-serif;
            font-size: .875rem; font-weight: 700;
            color: var(--white); cursor: pointer;
            transition: all .22s ease; letter-spacing: .03em;
            display: flex; align-items: center; gap: 8px;
        }
        .btn-modal-save:hover { background: var(--dark-soft); box-shadow: 0 6px 20px rgba(26,26,26,.22); }

        /* ─── Edit Modal Specific ────────────────────────────────── */
        .modal-section-label { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
        .modal-section-label-text {
            font-size: .62rem; font-weight: 700;
            letter-spacing: .22em; text-transform: uppercase; color: var(--gold);
        }
        .modal-section-label-rule {
            flex: 1; height: 1px;
            background: linear-gradient(90deg, rgba(201,169,110,.3), transparent);
        }
        .modal-field-label {
            display: block; font-size: .72rem; font-weight: 700;
            letter-spacing: .12em; text-transform: uppercase;
            color: var(--muted); margin-bottom: 8px;
        }
        #therapistModal .modal-body .form-control,
        #therapistModal .modal-body .form-select {
            width: 100%; padding: 11px 16px;
            border: 1.5px solid rgba(26,26,26,.12); border-radius: 8px;
            background: var(--white);
            font-family: 'DM Sans', sans-serif; font-size: .9rem; color: var(--dark);
            outline: none; transition: border-color .2s, box-shadow .2s;
        }
        #therapistModal .modal-body .form-control:focus,
        #therapistModal .modal-body .form-select:focus {
            border-color: var(--gold); box-shadow: 0 0 0 3px rgba(201,169,110,.12);
        }
        #therapistModal .modal-body textarea.form-control { resize: vertical; min-height: 80px; line-height: 1.6; }
        .field-hint { font-size: .71rem; color: var(--muted); margin-top: 5px; display: flex; align-items: center; gap: 5px; }
        .field-hint i { font-size: .7rem; }
        .modal-section-divider { height: 1px; background: var(--border); margin: 24px 0; }

        /* ─── Schedule Grid ──────────────────────────────────────── */
        .schedule-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .schedule-slot { position: relative; }
        .schedule-slot-label {
            font-size: .6rem; font-weight: 700; letter-spacing: .18em;
            text-transform: uppercase; color: var(--muted); margin-bottom: 6px; display: block;
        }
        #therapistModal .modal-body .schedule-input {
            width: 100%; padding: 11px 12px;
            border: 1.5px solid rgba(26,26,26,.12); border-radius: 8px;
            background: var(--white); color: var(--dark);
            font-size: .85rem; font-family: 'DM Sans', sans-serif;
            outline: none; transition: border-color .2s, box-shadow .2s; text-align: center;
        }
        #therapistModal .modal-body .schedule-input:focus {
            border-color: var(--gold); box-shadow: 0 0 0 3px rgba(201,169,110,.12);
        }
        #therapistModal .modal-body .schedule-input:disabled {
            background: rgba(26,26,26,.03); color: var(--muted); border-color: rgba(26,26,26,.08);
        }

        /* ─── Status Dot ─────────────────────────────────────────── */
        #therapistModal .status-select-wrap { position: relative; }
        #therapistModal #status { padding-left: 36px; font-weight: 600; }
        #therapistModal .status-dot {
            position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
            width: 8px; height: 8px; border-radius: 50%; background: #22a855;
            z-index: 1; pointer-events: none; transition: background .2s;
        }

        /* ─── Inline Error ───────────────────────────────────────── */
        .inline-error {
            display: none; padding: 14px 18px; border-radius: 8px;
            background: rgba(220,53,69,.06); border: 1px solid rgba(220,53,69,.18);
            font-size: .82rem; color: #8b2222; margin-bottom: 22px;
            align-items: flex-start; gap: 10px;
        }
        .inline-error.visible { display: flex; }
        .inline-error i { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }

        /* ─── Responsive ─────────────────────────────────────────── */
        @media (max-width: 991px) {
            
            .schedule-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 767px) {
            .topbar { flex-direction: column; align-items: flex-start; gap: 12px; }
            #therapistModal .modal-dialog { max-width: 98vw; margin: 10px; }
            #therapistModal .modal-content > form > div { flex-direction: column !important; }
            #therapistModal #editPhotoPanel { width: 100% !important; height: 240px; }
            #viewTherapistModal .modal-dialog { max-width: 98vw; margin: 10px; }
            #viewTherapistModal .modal-content > div { flex-direction: column !important; }
            #viewTherapistModal #viewPhotoPanel { width: 100% !important; height: 240px; }
        }
        @media (max-width: 600px) {
            .table-card-header { flex-direction: column; align-items: flex-start; }
            .schedule-grid { grid-template-columns: repeat(2, 1fr); }
        }

       
        .mobile-toggle:hover { border-color: var(--gold); background: var(--gold-dim); }
        @media (max-width: 991px) {
            .mobile-toggle { display: inline-flex; }
        }

        /* ─── Scrollbar styling (global) ────────────────────────── */
        ::-webkit-scrollbar        { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track  { background: transparent; }
        ::-webkit-scrollbar-thumb  { background: rgba(201,169,110,.28); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(201,169,110,.5); }

        /* ─── Table card footer / pagination hint ────────────────── */
        .table-footer {
            padding: 14px 28px;
            border-top: 1px solid rgba(201,169,110,.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: .74rem;
            color: var(--muted);
            background: var(--cream);
        }
        .table-footer-count { font-weight: 600; color: var(--dark-soft); }

        /* ─── Photo panel min-height on mobile ───────────────────── */
        @media (max-width: 767px) {
            #editPhotoPanel, #viewPhotoPanel { min-height: 220px; }
        }

        /* ─── Utility: gold text ─────────────────────────────────── */
        .text-gold  { color: var(--gold); }
        .text-muted-lb { color: var(--muted); }

        /* ─── Hover lift on table rows ───────────────────────────── */
        .therapist-table tbody tr {
            transition: background .18s, box-shadow .18s;
        }
        .therapist-table tbody tr:hover {
            background: rgba(201,169,110,.045);
            box-shadow: inset 3px 0 0 var(--gold);
        }

        /* ─── Schedule slot num label ────────────────────────────── */
        .schedule-slot-num {
            position: absolute;
            top: -2px; right: 0;
            font-size: .58rem; font-weight: 700;
            letter-spacing: .1em; color: rgba(201,169,110,.5);
            text-transform: uppercase;
        }

        /* ─── View modal grid responsive ─────────────────────────── */
        @media (max-width: 600px) {
            #viewTherapistModal [style*="grid-template-columns:1fr 1fr"] {
                grid-template-columns: 1fr !important;
            }
        }

        /* ─── Print tweak ─────────────────────────────────────────── */
        @media print {
            .sidebar, .filter-card, .btn-icon, .topbar { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 20px !important; }
        }

        /* ─── Animations ─────────────────────────────────────────── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(.96); }
            to   { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>


<!-- ── Main Content ──────────────────────────────────────────────────── -->
<div class="main-content">

    <!-- Mobile toggle -->
    <button class="d-lg-none btn btn-sm mb-3"
        style="border:1.5px solid var(--border);background:var(--white);border-radius:8px;padding:7px 14px;"
        onclick="document.getElementById('sidebar').classList.toggle('open')">
        <i class="bi bi-list" style="font-size:1.1rem;"></i>
    </button>

    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-title">
            <span>Team Management</span>
            Therapist Profiles
        </div>
    </div>
    <div class="gold-rule"></div>

    <!-- Filter Row -->
    <p class="section-eyebrow">Refine Results</p>
    <div class="filter-card">
        <div class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label">Search Therapist</label>
                <div style="position:relative;">
                    <i class="bi bi-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.85rem;pointer-events:none;"></i>
                    <input type="text" id="therapistSearch" class="form-control" style="padding-left:36px;" placeholder="Name, specialty, or status…">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Gender</label>
                <select id="genderFilter" class="form-select">
                    <option value="All">All Genders</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <p class="section-eyebrow">Directory</p>
    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-header-title">
                All Therapists &nbsp;<span style="font-family:'DM Sans',sans-serif;font-size:.8rem;color:var(--muted);font-weight:500;">(<?= count($therapists) ?>)</span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="therapist-table" id="therapistTable">
                <thead>
                    <tr>
                        <th>Therapist</th>
                        <th>Specialty</th>
                        <th>Gender</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th style="text-align:right;padding-right:28px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($therapists)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="bi bi-person-badge"></i></div>
                                    <div class="empty-state-text">No therapists found</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($therapists as $t): ?>
                        <?php
                            $photo     = (!empty($t['profile_picture'])) ? $t['profile_picture'] : 'default_therapist.png';
                            $schedules = $all_schedules[$t['therapist_id']] ?? [];
                            $sched_times = array_column($schedules, 'time_start');
                            $isActive  = strtolower($t['status']) === 'active';
                        ?>
                        <tr>
                            <td>
                                <div class="therapist-avatar-cell">
                                    <img src="../assets/img/therapists/<?= htmlspecialchars($photo) ?>"
                                         class="therapist-avatar"
                                         alt="<?= htmlspecialchars($t['first_name']) ?>">
                                    <div>
                                        <div class="therapist-name"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></div>
                                        <div class="therapist-sub"><?= htmlspecialchars($t['username'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge-specialty"><?= htmlspecialchars($t['specialty']) ?></span></td>
                            <td><span class="badge-gender"><?= htmlspecialchars($t['gender']) ?></span></td>
                            <td>
                                <?php if (!empty($sched_times)): ?>
                                    <div class="schedule-pills">
                                        <?php foreach ($sched_times as $time): ?>
                                            <span class="schedule-pill"><?= date('g:i A', strtotime($time)) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size:.78rem;color:var(--muted);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isActive): ?>
                                    <span class="badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;padding-right:28px;">
                                <div style="display:inline-flex;gap:8px;">
                                    <button class="btn-icon" title="View"
                                        onclick='viewTherapist(<?= json_encode(array_merge($t, ["schedules" => $sched_times])) ?>)'>
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn-icon" title="Edit"
                                        onclick='editTherapist(<?= json_encode(array_merge($t, ["schedules" => $sched_times])) ?>)'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr id="noResultsRow" style="display:none;">
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="bi bi-person-exclamation"></i></div>
                                    <div class="empty-state-text">No therapists match your search</div>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /main-content -->


<!-- ══════════════════════════════════════════════════════════════════
     EDIT THERAPIST MODAL
══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="therapistModal" tabindex="-1" aria-labelledby="therapistModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <form action="manage_therapist.php" method="POST" enctype="multipart/form-data" id="therapistForm">

                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    style="position:absolute;top:18px;right:18px;z-index:10;filter:brightness(0) invert(1);opacity:.7;"></button>

                <div style="display:flex;min-height:480px;">

                    <!-- Left: Photo Panel -->
                    <div style="width:42%;flex-shrink:0;position:relative;background:var(--dark);overflow:hidden;" id="editPhotoPanel">
                        <img id="profilePreview"
                             src="../assets/img/therapists/default_therapist.png"
                             alt="Therapist"
                             style="width:100%;height:100%;object-fit:cover;display:block;opacity:.88;">
                        <div style="position:absolute;inset:0;background:linear-gradient(to top, rgba(26,26,26,.85) 0%, transparent 55%);pointer-events:none;"></div>
                        <div style="position:absolute;bottom:0;left:0;right:0;padding:28px 28px 80px;">
                            <div style="font-size:.62rem;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:var(--gold);margin-bottom:6px;" id="editSpecialtyLabel">—</div>
                            <div style="font-family:'Cormorant Garamond',serif;font-size:1.8rem;font-weight:600;color:var(--white);line-height:1.1;" id="editNameOnPhoto">—</div>
                        </div>
                        <label style="position:absolute;bottom:22px;left:28px;display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:8px;border:1.5px solid rgba(255,255,255,.25);background:rgba(255,255,255,.12);color:var(--white);font-size:.8rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;">
                            <i class="bi bi-camera-fill"></i> Change Photo
                            <input type="file" name="profile_pic" style="display:none;" onchange="previewImage(this)">
                        </label>
                    </div>

                    <!-- Right: Form Panel -->
                    <div style="flex:1;background:var(--cream);display:flex;flex-direction:column;">

                        <!-- Header band -->
                        <div style="padding:32px 36px 24px;border-bottom:1px solid var(--border);background:var(--white);">
                            <div style="font-size:.62rem;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:var(--gold);margin-bottom:6px;">Edit Therapist Profile</div>
                            <div style="font-family:'Cormorant Garamond',serif;font-size:2rem;font-weight:600;color:var(--dark);line-height:1.1;" id="modalTitle">—</div>
                            <div style="font-size:.8rem;color:var(--muted);margin-top:5px;" id="usernameDisplay">—</div>
                        </div>

                        <!-- Modal Body -->
                        <div class="modal-body" style="flex:1;padding:28px 36px;overflow-y:auto;max-height:55vh;background:var(--cream);scrollbar-width:thin;scrollbar-color:rgba(201,169,110,.3) transparent;">

                            <!-- Inline error -->
                            <div class="inline-error" id="validationError">
                                <i class="bi bi-exclamation-circle-fill"></i>
                                <span id="errorText"></span>
                            </div>

                            <input type="hidden" name="therapist_id" id="therapist_id">
                            <input type="hidden" id="username">

                            <!-- Account Section -->
                            <div class="modal-section-label">
                                <span class="modal-section-label-text">Account Details</span>
                                <div class="modal-section-label-rule"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="modal-field-label">Account Status</label>
                                    <div class="status-select-wrap">
                                        <div class="status-dot" id="statusDot"></div>
                                        <select name="status" id="status" class="form-select" onchange="toggleScheduleDisability(); updateStatusDot();">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                    <div class="field-hint"><i class="bi bi-info-circle"></i> Controls schedule availability.</div>
                                </div>
                            </div>

                            <div class="modal-section-divider"></div>

                            <!-- Personal Info -->
                            <div class="modal-section-label">
                                <span class="modal-section-label-text">Personal Information</span>
                                <div class="modal-section-label-rule"></div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="modal-field-label">First Name</label>
                                    <input type="text" name="first_name" id="first_name" class="form-control" required placeholder="e.g. Maria">
                                </div>
                                <div class="col-md-4">
                                    <label class="modal-field-label">Middle Name</label>
                                    <input type="text" name="middle_name" id="middle_name" class="form-control" required placeholder="e.g. Santos">
                                </div>
                                <div class="col-md-4">
                                    <label class="modal-field-label">Last Name</label>
                                    <input type="text" name="last_name" id="last_name" class="form-control" required placeholder="e.g. Cruz">
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="modal-field-label">Gender</label>
                                    <select name="gender" id="gender" class="form-select">
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="modal-field-label">Specialty</label>
                                    <input type="text" name="specialty" id="specialty" class="form-control" required placeholder="e.g. Swedish Massage">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="modal-field-label">Work Experience</label>
                                <textarea name="work_experience" id="work_experience" class="form-control" rows="3" required placeholder="Brief description of professional background…"></textarea>
                            </div>

                            <div class="modal-section-divider"></div>

                            <!-- Schedule -->
                            <div class="modal-section-label">
                                <span class="modal-section-label-text">Daily Time Schedule</span>
                                <div class="modal-section-label-rule"></div>
                            </div>
                            <div class="field-hint mb-3" style="font-size:.78rem;">
                                <i class="bi bi-clock"></i>
                                Assign up to 4 time slots — each must be at least 1 hour apart.
                            </div>
                            <div class="schedule-grid">
                                <?php for ($i = 0; $i < 4; $i++): ?>
                                <div class="schedule-slot">
                                    <span class="schedule-slot-label">Slot <?= $i + 1 ?></span>
                                    <input type="time" name="schedule_times[]" class="schedule-input" required>
                                </div>
                                <?php endfor; ?>
                            </div>

                        </div><!-- /modal-body -->

                        <!-- Footer -->
                        <div style="padding:18px 36px;border-top:1px solid var(--border);background:var(--white);display:flex;justify-content:flex-end;gap:10px;">
                            <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="save_therapist" class="btn-modal-save">
                                <i class="bi bi-check-lg"></i> Save Changes
                            </button>
                        </div>

                    </div><!-- /right panel -->
                </div><!-- /flex row -->

            </form>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════════════
     VIEW THERAPIST MODAL
══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="viewTherapistModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">

            <button type="button" class="btn-close" data-bs-dismiss="modal"
                style="position:absolute;top:18px;right:18px;z-index:10;filter:brightness(0) invert(1);opacity:.7;"></button>

            <div style="display:flex;min-height:480px;">

                <!-- Left: Photo Panel -->
                <div style="width:42%;flex-shrink:0;position:relative;background:var(--dark);overflow:hidden;" id="viewPhotoPanel">
                    <img id="viewPhoto"
                         src="../assets/img/therapists/default_therapist.png"
                         alt="Therapist"
                         style="width:100%;height:100%;object-fit:cover;display:block;opacity:.88;">
                    <div style="position:absolute;inset:0;background:linear-gradient(to top, rgba(26,26,26,.85) 0%, transparent 55%);pointer-events:none;"></div>
                    <div style="position:absolute;bottom:0;left:0;right:0;padding:28px 28px 24px;">
                        <div style="font-size:.62rem;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:var(--gold);margin-bottom:6px;" id="viewSpecialtyLabel">—</div>
                        <div style="font-family:'Cormorant Garamond',serif;font-size:1.8rem;font-weight:600;color:var(--white);line-height:1.1;" id="viewNameOnPhoto">—</div>
                    </div>
                </div>

                <!-- Right: Details Panel -->
                <div style="flex:1;background:var(--cream);display:flex;flex-direction:column;">

                    <!-- Header band -->
                    <div style="padding:32px 36px 24px;border-bottom:1px solid var(--border);background:var(--white);">
                        <div style="font-size:.62rem;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:var(--gold);margin-bottom:6px;">Therapist Profile</div>
                        <div style="font-family:'Cormorant Garamond',serif;font-size:2rem;font-weight:600;color:var(--dark);line-height:1.1;" id="viewFullName">—</div>
                        <div style="font-size:.8rem;color:var(--muted);margin-top:5px;" id="viewUsername">—</div>
                    </div>

                    <!-- Info body -->
                    <div style="flex:1;padding:28px 36px;overflow-y:auto;">

                        <div style="display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap;">
                            <span id="viewStatusBadge"></span>
                            <span id="viewGenderBadge" class="badge-gender"></span>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
                            <div>
                                <div style="font-size:.62rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);margin-bottom:5px;">Specialty</div>
                                <div style="font-size:.92rem;color:var(--dark);font-weight:500;" id="viewSpecialty">—</div>
                            </div>
                            <div>
                                <div style="font-size:.62rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);margin-bottom:5px;">Gender</div>
                                <div style="font-size:.92rem;color:var(--dark);font-weight:500;" id="viewGender">—</div>
                            </div>
                        </div>

                        <div style="margin-bottom:24px;">
                            <div style="font-size:.62rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);margin-bottom:8px;">Work Experience</div>
                            <div style="font-size:.88rem;color:var(--dark-soft);line-height:1.7;background:var(--white);border:1px solid rgba(201,169,110,.15);border-radius:10px;padding:14px 16px;" id="viewExperience">—</div>
                        </div>

                        <div>
                            <div style="font-size:.62rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);margin-bottom:10px;">Daily Schedule</div>
                            <div class="schedule-pills" id="viewSchedule">
                                <span style="font-size:.78rem;color:var(--muted);">No schedule set</span>
                            </div>
                        </div>

                    </div>

                    <!-- Footer -->
                    <div style="padding:18px 36px;border-top:1px solid var(--border);background:var(--white);display:flex;justify-content:flex-end;gap:10px;">
                        <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn-modal-save" id="viewEditBtn">
                            <i class="bi bi-pencil"></i> Edit Profile
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

    // ── Edit Therapist ─────────────────────────────────────────────
    function editTherapist(therapist) {
        const errorBox = document.getElementById('validationError');
        if (errorBox) errorBox.classList.remove('visible');

        document.getElementById('therapistForm').reset();

        document.getElementById('therapist_id').value    = therapist.therapist_id;
        document.getElementById('first_name').value      = therapist.first_name;
        document.getElementById('middle_name').value     = therapist.middle_name;
        document.getElementById('last_name').value       = therapist.last_name;
        document.getElementById('gender').value          = therapist.gender;
        document.getElementById('specialty').value       = therapist.specialty;
        document.getElementById('work_experience').value = therapist.work_experience;
        document.getElementById('status').value          = therapist.status;
        document.getElementById('username').value        = therapist.username || 'N/A';
        document.getElementById('usernameDisplay').textContent    = '@' + (therapist.username || 'N/A');
        document.getElementById('editNameOnPhoto').textContent    = therapist.first_name + ' ' + therapist.last_name;
        document.getElementById('editSpecialtyLabel').textContent = therapist.specialty || '—';
        document.getElementById('modalTitle').textContent =
            therapist.first_name + ' ' +
            (therapist.middle_name ? therapist.middle_name + ' ' : '') +
            therapist.last_name;

        const photo = therapist.profile_picture
            ? '../assets/img/therapists/' + therapist.profile_picture
            : '../assets/img/therapists/default_therapist.png';
        document.getElementById('profilePreview').src = photo;

        const times  = therapist.schedules || [];
        const inputs = document.querySelectorAll('.schedule-input');
        inputs.forEach((input, index) => {
            input.value = times[index] ? times[index].substring(0, 5) : '';
        });

        updateStatusDot();
        toggleScheduleDisability();
        new bootstrap.Modal(document.getElementById('therapistModal')).show();
    }

    // ── Status Dot ─────────────────────────────────────────────────
    function updateStatusDot() {
        const status = document.getElementById('status').value;
        const dot    = document.getElementById('statusDot');
        if (!dot) return;
        dot.style.background = status === 'active' ? '#22a855' : '#c04444';
    }

    // ── Form Validation ────────────────────────────────────────────
    document.getElementById('therapistForm').addEventListener('submit', function (e) {
        const errorBox = document.getElementById('validationError');
        if (errorBox) errorBox.classList.remove('visible');

        if (document.getElementById('status').value !== 'inactive') {
            const inputs = document.querySelectorAll('.schedule-input');
            let allFilled = true;
            const times   = [];

            inputs.forEach(i => {
                if (!i.value) allFilled = false;
                else times.push(i.value);
            });

            if (!allFilled) {
                e.preventDefault();
                showInlineError("All fields, including all 4 schedule slots, are required.");
                return;
            }

            for (let i = 0; i < times.length; i++) {
                for (let j = i + 1; j < times.length; j++) {
                    const [h1, m1] = times[i].split(':').map(Number);
                    const [h2, m2] = times[j].split(':').map(Number);
                    const diff = Math.abs((h1 * 60 + m1) - (h2 * 60 + m2));
                    if (diff < 60) {
                        e.preventDefault();
                        showInlineError("Schedule Conflict: Slots must be at least 1 hour apart.");
                        return;
                    }
                }
            }
        }
    });

    function showInlineError(message) {
        const errorBox  = document.getElementById('validationError');
        const errorText = document.getElementById('errorText');
        if (errorBox && errorText) {
            errorText.innerText = message;
            errorBox.classList.add('visible');
            document.querySelector('#therapistModal .modal-body').scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    // ── Schedule Disable Toggle ────────────────────────────────────
    function toggleScheduleDisability() {
        const status = document.getElementById('status').value;
        const inputs = document.querySelectorAll('.schedule-input');
        inputs.forEach(input => {
            input.disabled = (status === 'inactive');
        });
    }

    // ── Image Preview ──────────────────────────────────────────────
    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('profilePreview').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // ── Table Filter ───────────────────────────────────────────────
    function filterTherapists() {
        const searchText   = document.getElementById('therapistSearch').value.toLowerCase();
        const genderValue  = document.getElementById('genderFilter').value;
        const rows         = document.querySelectorAll('#therapistTable tbody tr:not(#noResultsRow)');
        const noResultsRow = document.getElementById('noResultsRow');
        let visibleCount   = 0;

        rows.forEach(row => {
            const rowText   = row.textContent.toLowerCase();
            const rowGender = row.cells[2] ? row.cells[2].textContent.trim() : '';
            const matchesSearch = rowText.includes(searchText);
            const matchesGender = (genderValue === 'All' || rowGender === genderValue);

            if (matchesSearch && matchesGender) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (noResultsRow) {
            noResultsRow.style.display = visibleCount === 0 ? '' : 'none';
        }
    }

    document.getElementById('therapistSearch').addEventListener('keyup', filterTherapists);
    document.getElementById('genderFilter').addEventListener('change', filterTherapists);

    // ── Success Toast ──────────────────────────────────────────────
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            icon: 'success',
            title: 'Saved!',
            text: 'Therapist profile has been updated successfully.',
            timer: 1800,
            showConfirmButton: false
        });
    });
    <?php endif; ?>

    // ── View Therapist Modal ───────────────────────────────────────
    let _currentViewTherapist = null;

    function viewTherapist(therapist) {
        _currentViewTherapist = therapist;

        const photo = therapist.profile_picture
            ? '../assets/img/therapists/' + therapist.profile_picture
            : '../assets/img/therapists/default_therapist.png';

        document.getElementById('viewPhoto').src                  = photo;
        document.getElementById('viewNameOnPhoto').textContent    = therapist.first_name + ' ' + therapist.last_name;
        document.getElementById('viewSpecialtyLabel').textContent = therapist.specialty || '—';
        document.getElementById('viewFullName').textContent       =
            therapist.first_name + ' ' +
            (therapist.middle_name ? therapist.middle_name + ' ' : '') +
            therapist.last_name;
        document.getElementById('viewUsername').textContent   = therapist.username ? '@' + therapist.username : '—';
        document.getElementById('viewSpecialty').textContent  = therapist.specialty    || '—';
        document.getElementById('viewGender').textContent     = therapist.gender       || '—';
        document.getElementById('viewExperience').textContent = therapist.work_experience || 'No details provided.';

        const genderBadge = document.getElementById('viewGenderBadge');
        genderBadge.textContent = therapist.gender || '—';

        const statusBadge = document.getElementById('viewStatusBadge');
        const isActive    = therapist.status?.toLowerCase() === 'active';
        statusBadge.className   = isActive ? 'badge-active' : 'badge-inactive';
        statusBadge.textContent = isActive ? 'Active' : 'Inactive';

        const scheduleWrap = document.getElementById('viewSchedule');
        const times = therapist.schedules || [];
        if (times.length > 0) {
            scheduleWrap.innerHTML = times.map(t => {
                const [h, m] = t.split(':').map(Number);
                const ampm   = h >= 12 ? 'PM' : 'AM';
                const hr     = h % 12 || 12;
                return `<span class="schedule-pill">${hr}:${String(m).padStart(2, '0')} ${ampm}</span>`;
            }).join('');
        } else {
            scheduleWrap.innerHTML = '<span style="font-size:.78rem;color:var(--muted);">No schedule set</span>';
        }

        document.getElementById('viewEditBtn').onclick = function () {
            bootstrap.Modal.getInstance(document.getElementById('viewTherapistModal')).hide();
            setTimeout(() => editTherapist(_currentViewTherapist), 350);
        };

        new bootstrap.Modal(document.getElementById('viewTherapistModal')).show();
    }

</script>
</body>
</html>