<?php
session_start();
require_once '../config/db.php';
require_once '../includes/log_action.php';



if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}

$msg   = "";
$error = "";

// --- LOGIC: ADD THERAPIST ACCOUNT ---
if (isset($_POST['add_therapist'])) {
    $first    = $_POST['first_name'];
    $middle   = $_POST['middle_name'];
    $last     = $_POST['last_name'];
    $gender   = $_POST['gender'];
    $specialty= $_POST['specialty'];
    $work     = $_POST['work_experience'];
    $user     = $_POST['username'];
    $pass     = $_POST['password'];
    $cpass    = $_POST['confirm_password'];

    if ($pass !== $cpass) {
        $error = "Passwords do not match.";
    } else {
        $photo = 'default_avatar.jpg';
        if (!empty($_FILES['photo']['name'])) {
            $target_dir = "../uploads/therapists/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $file_ext = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
            $photo = time() . "_" . $user . "." . $file_ext;
            move_uploaded_file($_FILES["photo"]["tmp_name"], $target_dir . $photo);
        }
        $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
        $sql  = "INSERT INTO therapists (first_name, middle_name, last_name, gender, specialty, work_experience, photo, username, password, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute([$first, $middle, $last, $gender, $specialty, $work, $photo, $user, $hashed_pass]);
            $msg = "Therapist account created successfully.";

logAction(
    $pdo,
    "Added therapist account: " . $first . " " . $last . " (" . $user . ")"
);
        } catch (PDOException $e) {
            $error = "Username already exists.";
        }
    }
}

// --- LOGIC: ARCHIVE THERAPIST ---
if (isset($_POST['archive_therapist'])) {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->prepare("UPDATE therapists SET status = 'archived' WHERE therapist_id = ?");
$stmt->execute([$_POST['archive_id']]);

$getTherapist = $pdo->prepare("
    SELECT first_name, last_name, username
    FROM therapists
    WHERE therapist_id = ?
");
$getTherapist->execute([$_POST['archive_id']]);
$therapist = $getTherapist->fetch();

logAction(
    $pdo,
    "Archived therapist account: " .
    $therapist['first_name'] . " " .
    $therapist['last_name'] .
    " (" . $therapist['username'] . ")"
);
        echo json_encode(["status" => "success", "message" => "Therapist archived successfully"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// --- LOGIC: RESTORE THERAPIST ---
if (isset($_POST['restore_therapist'])) {
    $stmt = $pdo->prepare("UPDATE therapists SET status = 'active' WHERE therapist_id = ?");
$stmt->execute([$_POST['restore_id']]);

$getTherapist = $pdo->prepare("
    SELECT first_name, last_name, username
    FROM therapists
    WHERE therapist_id = ?
");
$getTherapist->execute([$_POST['restore_id']]);
$therapist = $getTherapist->fetch();

logAction(
    $pdo,
    "Restored therapist account: " .
    $therapist['first_name'] . " " .
    $therapist['last_name'] .
    " (" . $therapist['username'] . ")"
);

$msg = "Therapist account restored.";
}

// --- LOGIC: UPDATE USER MEMBERSHIP ---
if (isset($_POST['update_membership'])) {
    $type = $_POST['account_type'];
    $uses = ($type == 'member') ? 2 : 0;
    $stmt = $pdo->prepare("UPDATE users SET account_type = ?, semi_luxury_uses_left = ? WHERE user_id = ?");
$stmt->execute([$type, $uses, $_POST['user_id']]);

$getUser = $pdo->prepare("
    SELECT first_name, last_name, email
    FROM users
    WHERE user_id = ?
");
$getUser->execute([$_POST['user_id']]);
$userData = $getUser->fetch();

logAction(
    $pdo,
    "Updated membership to " . strtoupper($type) .
    " for user: " .
    $userData['first_name'] . " " .
    $userData['last_name'] .
    " (" . $userData['email'] . ")"
);

$msg = "User membership updated.";
}

// --- LOGIC: PASSWORD RESET VERIFICATION ---
if (isset($_POST['verify_admin_reset'])) {
    $admin_user  = $_POST['admin_user'];
    $admin_pass  = $_POST['admin_pass'];
    $target_id   = $_POST['target_id'];
    $target_type = $_POST['target_type'];
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$admin_user]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($admin_pass, $admin['password'])) {
        $show_reset_modal = true;
    } else {
        $error = "Invalid admin credentials.";
    }
}

// --- LOGIC: FINAL PASSWORD UPDATE ---
if (isset($_POST['finalize_reset'])) {
    $new_pass     = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_new_password'];
    $id           = $_POST['final_id'];
    $type         = $_POST['final_type'];
    if ($new_pass !== $confirm_pass) {
        $error = "New passwords do not match.";
    } else {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        if ($type == 'therapist') {
            $stmt = $pdo->prepare("UPDATE therapists SET password = ? WHERE therapist_id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        }
        $stmt->execute([$hashed_pass, $id]);
        if ($type == 'therapist') {

    $getAccount = $pdo->prepare("
        SELECT first_name, last_name, username
        FROM therapists
        WHERE therapist_id = ?
    ");
    $getAccount->execute([$id]);
    $acc = $getAccount->fetch();

    logAction(
        $pdo,
        "Reset password for therapist: " .
        $acc['first_name'] . " " .
        $acc['last_name'] .
        " (" . $acc['username'] . ")"
    );

} else {

    $getAccount = $pdo->prepare("
        SELECT first_name, last_name, email
        FROM users
        WHERE user_id = ?
    ");
    $getAccount->execute([$id]);
    $acc = $getAccount->fetch();

    logAction(
        $pdo,
        "Reset password for user: " .
        $acc['first_name'] . " " .
        $acc['last_name'] .
        " (" . $acc['email'] . ")"
    );
}

$msg = "Password reset successful.";
    }
}

// Fetch Data
$active_therapists   = $pdo->query("SELECT * FROM therapists WHERE status='active'   ORDER BY last_name ASC")->fetchAll();
$archived_therapists = $pdo->query("SELECT * FROM therapists WHERE status='archived' ORDER BY last_name ASC")->fetchAll();
$users               = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Management — Lumiére &amp; Bliss</title>

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

    
        /* ─── Layout ─────────────────────────────────────────────── */
        .main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            padding: 48px 44px 60px;
            transition: margin .35s cubic-bezier(.4,0,.2,1);
        }
        .topbar { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10px; }
        .topbar-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600; font-size: 2.4rem; color: var(--dark); line-height: 1.1;
        }
        .topbar-title span {
            display: block; font-family: 'DM Sans', sans-serif;
            font-size: .75rem; font-weight: 500; letter-spacing: .18em;
            text-transform: uppercase; color: var(--muted); margin-bottom: 6px;
        }
        .gold-rule {
            width: 48px; height: 2px;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
            border-radius: 2px; margin: 16px 0 36px;
        }
        .section-eyebrow {
            font-size: .68rem; font-weight: 700; letter-spacing: .22em;
            text-transform: uppercase; color: var(--gold); margin-bottom: 16px;
        }

        /* ─── Alert banner ───────────────────────────────────────── */
        .luxe-alert {
            display: flex; align-items: center; gap: 12px;
            padding: 14px 20px; border-radius: var(--radius-md);
            font-size: .85rem; font-weight: 500; margin-bottom: 28px;
            animation: fadeUp .35s ease both;
        }
        .luxe-alert.success { background: rgba(90,138,90,.1); border: 1px solid rgba(90,138,90,.25); color: #4a7a4a; }
        .luxe-alert.danger  { background: rgba(180,60,60,.08); border: 1px solid rgba(180,60,60,.2);  color: #b43c3c; }
        .luxe-alert i { font-size: 1rem; }

        /* ─── Main Card ──────────────────────────────────────────── */
        .account-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            border: 1px solid rgba(201,169,110,.1);
            overflow: hidden;
            animation: fadeUp .4s ease both;
        }

        /* ─── Custom Tabs ────────────────────────────────────────── */
        .tab-bar {
            display: flex;
            border-bottom: 1px solid rgba(201,169,110,.15);
            padding: 0 32px;
            background: var(--cream);
        }
        .tab-btn {
            padding: 18px 6px 15px;
            margin-right: 32px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-family: 'DM Sans', sans-serif;
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--muted);
            cursor: pointer;
            transition: color .2s, border-color .2s;
        }
        .tab-btn:hover  { color: var(--dark); }
        .tab-btn.active { color: var(--dark); border-bottom-color: var(--gold); }
        .tab-pane-content { display: none; padding: 32px; }
        .tab-pane-content.active { display: block; }

        /* ─── Sub-header inside tab ──────────────────────────────── */
        .tab-subheader {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 22px; flex-wrap: wrap; gap: 12px;
        }
        .tab-subheader-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600; font-size: 1.3rem; color: var(--dark);
        }
        .tab-actions { display: flex; gap: 10px; flex-wrap: wrap; }

        /* ─── Buttons ────────────────────────────────────────────── */
        .btn-primary-dark {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 22px; border-radius: 50px;
            background: var(--dark); color: var(--gold-light);
            border: 1.5px solid var(--border);
            font-size: .8rem; font-weight: 700; letter-spacing: .08em;
            cursor: pointer; text-decoration: none;
            transition: background .22s, color .22s, border-color .22s;
        }
        .btn-primary-dark:hover { background: var(--dark-soft); color: var(--gold); border-color: var(--gold); }

        .btn-ghost {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 18px; border-radius: 50px;
            background: transparent; color: var(--muted);
            border: 1.5px solid rgba(201,169,110,.25);
            font-size: .8rem; font-weight: 600; letter-spacing: .06em;
            cursor: pointer;
            transition: border-color .2s, color .2s, background .2s;
        }
        .btn-ghost:hover { border-color: var(--gold); color: var(--dark); background: var(--gold-dim); }

        /* ─── Search ─────────────────────────────────────────────── */
        .search-wrap { position: relative; margin-bottom: 20px; }
        .search-wrap i {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%); color: var(--muted);
            font-size: .85rem; pointer-events: none;
        }
        .search-input {
            padding: 10px 16px 10px 38px;
            border: 1.5px solid rgba(201,169,110,.25);
            border-radius: 50px; background: var(--cream);
            color: var(--dark); font-size: .85rem;
            font-family: 'DM Sans', sans-serif; width: 100%;
            transition: border-color .2s, box-shadow .2s;
        }
        .search-input:focus {
            outline: none; border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(201,169,110,.12); background: var(--white);
        }
        .search-input::placeholder { color: var(--muted); }

        /* ─── Tables ─────────────────────────────────────────────── */
        .luxe-table { width: 100%; border-collapse: collapse; }
        .luxe-table thead tr {
            background: var(--cream); border-bottom: 2px solid rgba(201,169,110,.15);
        }
        .luxe-table thead th {
            font-size: .67rem; font-weight: 700; letter-spacing: .18em;
            text-transform: uppercase; color: var(--muted); padding: 13px 18px; white-space: nowrap;
        }
        .luxe-table tbody tr {
            border-bottom: 1px solid rgba(201,169,110,.08); transition: background .18s;
        }
        .luxe-table tbody tr:last-child { border-bottom: none; }
        .luxe-table tbody tr:hover { background: rgba(201,169,110,.04); }
        .luxe-table tbody td {
            padding: 15px 18px; font-size: .88rem; color: var(--dark-soft); vertical-align: middle;
        }
        .cell-bold { font-weight: 700; color: var(--dark); }
        .cell-muted { font-size: .8rem; color: var(--muted); }

        /* Action icon buttons */
        .btn-icon {
            width: 33px; height: 33px; border-radius: 9px;
            border: 1.5px solid rgba(201,169,110,.25); background: transparent;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: .82rem; cursor: pointer;
            transition: background .2s, border-color .2s, color .2s; color: var(--dark-soft);
        }
        .btn-icon:hover { background: var(--gold-dim); border-color: var(--gold); color: var(--dark); }
        .btn-icon.warn  { color: #c07a30; border-color: rgba(192,122,48,.25); }
        .btn-icon.warn:hover { background: rgba(192,122,48,.1); border-color: #c07a30; }
        .btn-icon.danger { color: #b43c3c; border-color: rgba(180,60,60,.25); }
        .btn-icon.danger:hover { background: rgba(180,60,60,.08); border-color: #b43c3c; }

        /* ─── Membership badge & select ─────────────────────────── */
        .badge-member {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 12px; border-radius: 50px; font-size: .7rem;
            font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
        }
        .badge-member::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
        .badge-member.member     { background: var(--gold-dim); border: 1px solid var(--border); color: #a07a30; }
        .badge-member.non-member { background: rgba(138,128,112,.1); border: 1px solid rgba(138,128,112,.2); color: var(--muted); }

        .membership-select {
            border: 1.5px solid rgba(201,169,110,.25); border-radius: var(--radius-md);
            background: var(--cream); color: var(--dark);
            font-size: .8rem; font-family: 'DM Sans', sans-serif;
            padding: 6px 10px; cursor: pointer;
            transition: border-color .2s;
        }
        .membership-select:focus { outline: none; border-color: var(--gold); }

        /* ─── Archive collapse section ───────────────────────────── */
        .archive-section {
            background: var(--cream); border: 1px solid var(--border);
            border-radius: var(--radius-md); padding: 24px; margin-top: 8px;
        }
        .archive-section-title {
            font-size: .68rem; font-weight: 700; letter-spacing: .18em;
            text-transform: uppercase; color: var(--muted); margin-bottom: 16px;
        }
        .archive-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 0; border-bottom: 1px solid rgba(201,169,110,.1);
            font-size: .85rem;
        }
        .archive-row:last-child { border-bottom: none; }
        .btn-restore {
            padding: 6px 18px; border-radius: 50px;
            border: 1.5px solid rgba(90,138,90,.3);
            background: rgba(90,138,90,.08); color: #4a7a4a;
            font-size: .76rem; font-weight: 700; letter-spacing: .06em;
            cursor: pointer; transition: background .2s, border-color .2s;
        }
        .btn-restore:hover { background: rgba(90,138,90,.16); border-color: #4a7a4a; }
        .empty-archive { font-size: .82rem; color: var(--muted); font-style: italic; }

        /* ─── Modal shared styles ────────────────────────────────── */
        .modal-content {
            border: none; border-radius: var(--radius-lg);
            box-shadow: var(--shadow-deep); background: var(--white); overflow: hidden;
        }
        .modal-header {
            background: var(--dark); padding: 24px 32px 20px;
            border-bottom: 2px solid var(--border); position: relative;
        }
        .modal-header::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(ellipse at 20% 50%, rgba(201,169,110,.08) 0%, transparent 60%);
            pointer-events: none;
        }
        .modal-eyebrow {
            font-size: .65rem; font-weight: 700; letter-spacing: .2em;
            text-transform: uppercase; color: var(--gold); margin-bottom: 3px;
        }
        .modal-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600; font-size: 1.45rem; color: var(--white);
        }
        .btn-close-custom {
            background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
            border-radius: 8px; width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            color: rgba(255,255,255,.6); font-size: .9rem; cursor: pointer;
            transition: background .2s, color .2s; flex-shrink: 0;
        }
        .btn-close-custom:hover { background: rgba(255,255,255,.15); color: var(--white); }
        .modal-body { padding: 28px 32px; }
        .modal-section-divider {
            border: none; border-top: 1px solid rgba(201,169,110,.15);
            margin: 20px 0 18px;
        }
        .modal-field-label {
            font-size: .67rem; font-weight: 700; letter-spacing: .16em;
            text-transform: uppercase; color: var(--muted);
            margin-bottom: 8px; display: block;
        }
        .modal-body .form-control,
        .modal-body .form-select,
        .modal-body textarea.form-control {
            border: 1.5px solid rgba(201,169,110,.25); border-radius: var(--radius-md);
            background: var(--cream); color: var(--dark);
            font-size: .88rem; font-family: 'DM Sans', sans-serif; padding: 10px 14px;
            transition: border-color .2s, box-shadow .2s;
        }
        .modal-body .form-control:focus,
        .modal-body .form-select:focus,
        .modal-body textarea.form-control:focus {
            border-color: var(--gold); box-shadow: 0 0 0 3px rgba(201,169,110,.12);
            outline: none; background: var(--white);
        }
        .form-check-input:checked { background-color: var(--gold); border-color: var(--gold); }
        .form-check-label { font-size: .8rem; color: var(--muted); }
        .modal-footer {
            padding: 18px 32px 26px; border-top: 1px solid rgba(201,169,110,.12);
            display: flex; gap: 12px; justify-content: flex-end;
        }
        .btn-modal-cancel {
            padding: 10px 22px; border-radius: 50px;
            border: 1.5px solid rgba(201,169,110,.25); background: transparent;
            color: var(--muted); font-size: .82rem; font-weight: 600;
            letter-spacing: .06em; cursor: pointer;
            transition: border-color .2s, color .2s;
        }
        .btn-modal-cancel:hover { border-color: var(--gold); color: var(--dark); }
        .btn-modal-submit {
            padding: 10px 26px; border-radius: 50px; border: none;
            background: var(--dark); color: var(--gold-light);
            font-size: .82rem; font-weight: 700; letter-spacing: .08em;
            cursor: pointer; transition: background .2s, color .2s;
            display: flex; align-items: center; gap: 7px;
        }
        .btn-modal-submit:hover { background: var(--dark-soft); color: var(--gold); }
        .btn-modal-submit.success-btn { background: #3a6e3a; color: #c5e8c5; }
        .btn-modal-submit.success-btn:hover { background: #2e5a2e; }


        @media (max-width: 991px) {
    .main-content { margin-left: 0; padding: 80px 20px 40px; }
}
        @media (max-width: 600px) {
            .topbar { flex-direction: column; align-items: flex-start; gap: 12px; }
            .tab-subheader { flex-direction: column; align-items: flex-start; }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<?php require_once '../includes/sidebar.php'; ?>

<!-- ── Main Content ──────────────────────────────────────────────────── -->
<div class="main-content">

    <div class="topbar">
        <div class="topbar-title">
            <span>Administration</span>
            Account Management
        </div>
    </div>
    <div class="gold-rule"></div>

    <!-- Alerts -->
    <?php if ($msg): ?>
    <div class="luxe-alert success" id="autoAlert">
        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="luxe-alert danger">
        <i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <p class="section-eyebrow">Manage Accounts</p>

    <!-- ── Tabbed Card ─────────────────────────────────────────────── -->
    <div class="account-card">

        <!-- Tab Bar -->
        <div class="tab-bar">
            <button class="tab-btn active" data-target="therapistTab">
                <i class="bi bi-person-badge me-1"></i> Therapists
            </button>
            <button class="tab-btn" data-target="userTab">
                <i class="bi bi-people me-1"></i> Clients
            </button>
        </div>

        <!-- ── Therapist Tab ────────────────────────────────────────── -->
        <div class="tab-pane-content active" id="therapistTab">
            <div class="tab-subheader">
                <div class="tab-subheader-title">Therapist Accounts</div>
                <div class="tab-actions">
                    <button class="btn-primary-dark"
                        data-bs-toggle="modal" data-bs-target="#addTherapistModal">
                        <i class="bi bi-plus-lg"></i> Add Account
                    </button>
                    <button class="btn-ghost" type="button"
                        data-bs-toggle="collapse" data-bs-target="#archiveSection">
                        <i class="bi bi-archive"></i>
                        Archive <span style="background:var(--gold-dim);border:1px solid var(--border);border-radius:50px;padding:1px 8px;font-size:.7rem;color:var(--dark);"><?= count($archived_therapists) ?></span>
                    </button>
                </div>
            </div>

            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="therapistSearch" class="search-input" placeholder="Search name or username…">
            </div>

            <div style="overflow-x:auto;">
                <table class="luxe-table" id="therapistTable">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Specialty</th>
                            <th style="text-align:right; padding-right:20px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_therapists as $t): ?>
                        <tr>
                            <td class="cell-bold therapist-username"><?= htmlspecialchars($t['username']) ?></td>
                            <td class="therapist-name"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></td>
                            <td class="cell-muted"><?= htmlspecialchars($t['specialty']) ?></td>
                            <td style="text-align:right; padding-right:20px;">
                                <div style="display:inline-flex;gap:8px;">
                                    <button class="btn-icon warn" title="Reset Password"
                                        onclick="openResetModal('therapist', <?= (int)$t['therapist_id'] ?>)">
                                        <i class="bi bi-key"></i>
                                    </button>
                                    <button class="btn-icon danger" title="Archive"
                                        onclick="archiveTherapist(<?= (int)$t['therapist_id'] ?>)">
                                        <i class="bi bi-archive"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($active_therapists)): ?>
                        <tr><td colspan="4" style="text-align:center;padding:40px;color:var(--muted);font-style:italic;">No active therapists found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Archive collapse -->
            <div class="collapse mt-4" id="archiveSection">
                <div class="archive-section">
                    <div class="archive-section-title">Archived Therapists</div>
                    <?php if (empty($archived_therapists)): ?>
                        <div class="empty-archive">No archived therapists.</div>
                    <?php else: ?>
                        <?php foreach ($archived_therapists as $at): ?>
                        <div class="archive-row">
                            <span><?= htmlspecialchars($at['first_name'] . ' ' . $at['last_name']) ?></span>
                            <form action="" method="POST" class="d-inline">
                                <input type="hidden" name="restore_id" value="<?= $at['therapist_id'] ?>">
                                <button type="submit" name="restore_therapist" class="btn-restore">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Restore
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Users / Clients Tab ──────────────────────────────────── -->
        <div class="tab-pane-content" id="userTab">
            <div class="tab-subheader">
                <div class="tab-subheader-title">Client Accounts</div>
            </div>

            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="userSearch" class="search-input" placeholder="Search client name…">
            </div>

            <div style="overflow-x:auto;">
                <table class="luxe-table" id="userTable">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>Email</th>
                            <th>Membership</th>
                            <th style="text-align:right; padding-right:20px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="cell-bold user-name"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                            <td class="cell-muted"><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <form action="" method="POST" class="d-inline membership-form">
                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                    <select name="account_type" class="membership-select"
                                        data-current="<?= htmlspecialchars($u['account_type']) ?>">
                                        <option value="non_member" <?= $u['account_type']=='non_member' ? 'selected' : '' ?>>Non-Member</option>
                                        <option value="member"     <?= $u['account_type']=='member'     ? 'selected' : '' ?>>Member</option>
                                    </select>
                                    <input type="hidden" name="update_membership" value="1">
                                </form>
                            </td>
                            <td style="text-align:right; padding-right:20px;">
                                <button class="btn-icon warn" title="Reset Password"
                                    onclick="openResetModal('user', <?= (int)$u['user_id'] ?>)">
                                    <i class="bi bi-key"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                        <tr><td colspan="4" style="text-align:center;padding:40px;color:var(--muted);font-style:italic;">No clients registered.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /account-card -->
</div><!-- /main-content -->


<!-- ── Modal: Add Therapist ───────────────────────────────────────── -->
<div class="modal fade" id="addTherapistModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center">
                <div>
                    <div class="modal-eyebrow">New Staff</div>
                    <div class="modal-title">Create Therapist Account</div>
                </div>
                <button type="button" class="btn-close-custom" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="modal-field-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="modal-field-label">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="modal-field-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="modal-field-label">Gender</label>
                            <select name="gender" class="form-select" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="modal-field-label">Specialty</label>
                            <input type="text" name="specialty" class="form-control" placeholder="e.g. Swedish Massage" required>
                        </div>
                        <div class="col-12">
                            <label class="modal-field-label">Work Experience</label>
                            <textarea name="work_experience" class="form-control" rows="2" placeholder="Brief background…" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="modal-field-label">Profile Photo <span style="font-weight:400;text-transform:none;letter-spacing:0;">(Optional)</span></label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <hr class="modal-section-divider">
                    <div style="font-size:.68rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--gold);margin-bottom:16px;">Login Credentials</div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="modal-field-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="modal-field-label">Password</label>
                            <input type="password" name="password" id="pass" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="modal-field-label">Confirm Password</label>
                            <input type="password" name="confirm_password" id="cpass" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showPass">
                                <label class="form-check-label" for="showPass">Show passwords</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_therapist" class="btn-modal-submit">
                        <i class="bi bi-check-lg"></i> Create Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal: Admin Verification ─────────────────────────────────── -->
<div class="modal fade" id="adminVerifyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center">
                <div>
                    <div class="modal-eyebrow">Security</div>
                    <div class="modal-title" style="font-size:1.2rem;">Verify Identity</div>
                </div>
                <button type="button" class="btn-close-custom" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="target_type" id="target_type">
                    <input type="hidden" name="target_id"   id="target_id">
                    <p style="font-size:.82rem;color:var(--muted);margin-bottom:18px;">Enter admin credentials to authorize this password reset.</p>
                    <div class="mb-3">
                        <label class="modal-field-label">Admin Username</label>
                        <input type="text" name="admin_user" class="form-control" required>
                    </div>
                    <div>
                        <label class="modal-field-label">Admin Password</label>
                        <input type="password" name="admin_pass" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="verify_admin_reset" class="btn-modal-submit">
                        <i class="bi bi-shield-lock"></i> Verify
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal: Final Password Reset ───────────────────────────────── -->
<div class="modal fade" id="finalResetModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center">
                <div>
                    <div class="modal-eyebrow">Credential Update</div>
                    <div class="modal-title" style="font-size:1.2rem;">Set New Password</div>
                </div>
                <button type="button" class="btn-close-custom" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="final_type" value="<?= htmlspecialchars($_POST['target_type'] ?? '') ?>">
                    <input type="hidden" name="final_id"   value="<?= htmlspecialchars($_POST['target_id']   ?? '') ?>">
                    <div class="mb-3">
                        <label class="modal-field-label">New Password</label>
                        <input type="password" name="new_password" id="reset_pass" class="form-control" placeholder="••••••••" required>
                    </div>
                    <div class="mb-3">
                        <label class="modal-field-label">Confirm New Password</label>
                        <input type="password" name="confirm_new_password" id="reset_confirm" class="form-control" placeholder="••••••••" required>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="showResetPass">
                        <label class="form-check-label" for="showResetPass">Show passwords</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="finalize_reset" class="btn-modal-submit success-btn w-100">
                        <i class="bi bi-check-lg"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

    // ── Custom Tabs ────────────────────────────────────────────────
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-pane-content').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(this.dataset.target).classList.add('active');
        });
    });

    // ── Auto-dismiss success alert ─────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        const alert = document.getElementById('autoAlert');
        if (alert) setTimeout(() => { alert.style.opacity = '0'; alert.style.transition = 'opacity .5s'; setTimeout(() => alert.remove(), 500); }, 3000);
    });

    // ── Show/hide password (add therapist modal) ───────────────────
    document.getElementById('showPass').addEventListener('change', function () {
        const type = this.checked ? 'text' : 'password';
        document.getElementById('pass').type = type;
        document.getElementById('cpass').type = type;
    });

    // ── Show/hide password (reset modal) ──────────────────────────
    document.getElementById('showResetPass').addEventListener('change', function () {
        const type = this.checked ? 'text' : 'password';
        document.getElementById('reset_pass').type = type;
        document.getElementById('reset_confirm').type = type;
    });

    // ── Open reset verification modal ─────────────────────────────
    function openResetModal(type, id) {
        document.getElementById('target_type').value = type;
        document.getElementById('target_id').value   = id;
        new bootstrap.Modal(document.getElementById('adminVerifyModal')).show();
    }

    // ── Auto-open final reset modal if PHP set flag ────────────────
    <?php if (isset($show_reset_modal)): ?>
        new bootstrap.Modal(document.getElementById('finalResetModal')).show();
    <?php endif; ?>

    // ── Archive therapist (AJAX) ───────────────────────────────────
    function archiveTherapist(id) {
        Swal.fire({
            title: 'Archive this therapist?',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Yes, archive', cancelButtonText: 'Cancel'
        }).then(result => {
            if (result.isConfirmed) {
                const fd = new FormData();
                fd.append('archive_id', id); fd.append('archive_therapist', '1');
                fetch(window.location.pathname, { method: 'POST', body: fd })
                    .then(r => r.json())
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

    // ── Membership change confirmation ─────────────────────────────
    document.querySelectorAll('.membership-select').forEach(select => {
        select.addEventListener('change', function (e) {
            e.preventDefault();
            const form = this.closest('form');
            const prev = this.dataset.current;
            const newVal = this.value;
            Swal.fire({
                icon: 'warning', title: 'Change Membership Type?',
                html: `Change to <b>${newVal === 'member' ? 'Member' : 'Non-Member'}</b>?`,
                showCancelButton: true, confirmButtonText: 'Yes, Change', cancelButtonText: 'Cancel'
            }).then(result => { result.isConfirmed ? form.submit() : (this.value = prev); });
        });
    });

    // ── Password strength validation (add therapist) ───────────────
    document.addEventListener('DOMContentLoaded', function () {
        const therapistForm = document.querySelector('#addTherapistModal form');
        therapistForm.addEventListener('submit', function (e) {
            const pw = document.getElementById('pass').value;
            const cpw = document.getElementById('cpass').value;
            if (!/[A-Z]/.test(pw) || !/[a-z]/.test(pw) || !/[^A-Za-z0-9]/.test(pw)) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Weak Password', html: 'Must contain:<br>• 1 uppercase<br>• 1 lowercase<br>• 1 special character' });
                return;
            }
            if (pw !== cpw) { e.preventDefault(); Swal.fire({ icon: 'error', title: 'Mismatch', text: 'Passwords do not match.' }); }
        });

        // ── Password strength validation (reset modal) ─────────────
        const resetForm = document.querySelector('#finalResetModal form');
        resetForm.addEventListener('submit', function (e) {
            const pw = document.getElementById('reset_pass').value;
            const cpw = document.getElementById('reset_confirm').value;
            if (!/[A-Z]/.test(pw) || !/[a-z]/.test(pw) || !/[^A-Za-z0-9]/.test(pw)) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Weak Password', html: 'Must contain:<br>• 1 uppercase<br>• 1 lowercase<br>• 1 special character' });
                return;
            }
            if (pw !== cpw) { e.preventDefault(); Swal.fire({ icon: 'error', title: 'Mismatch', text: 'Passwords do not match.' }); }
        });
    });

    // ── Live search: therapists ────────────────────────────────────
    document.getElementById('therapistSearch').addEventListener('keyup', function () {
        const q    = this.value.toLowerCase();
        const rows = document.querySelectorAll('#therapistTable tbody tr:not(#noTherapistResult)');
        let visible = 0;

        rows.forEach(row => {
            const u = row.querySelector('.therapist-username')?.textContent.toLowerCase() || '';
            const n = row.querySelector('.therapist-name')?.textContent.toLowerCase()    || '';
            const show = u.includes(q) || n.includes(q);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        let noResult = document.getElementById('noTherapistResult');
        if (visible === 0) {
            if (!noResult) {
                noResult = document.createElement('tr');
                noResult.id = 'noTherapistResult';
                noResult.innerHTML = `<td colspan="99">
                    <div style="text-align:center; padding: 56px 24px;">
                        <i class="bi bi-search" style="font-size:2.2rem; color:var(--gold-light); opacity:.6; display:block; margin-bottom:18px;"></i>
                        <p style="font-family:'DM Sans',sans-serif; font-size:.88rem; color:var(--muted); font-weight:400; letter-spacing:.01em;">
                            No therapists match "<strong style="font-weight:700; color:var(--dark-soft);">${this.value}</strong>"
                        </p>
                    </div>
                </td>`;
                document.querySelector('#therapistTable tbody').appendChild(noResult);
            } else {
                noResult.querySelector('p').innerHTML =
                    `No therapists match "<strong style="font-weight:700; color:var(--dark-soft);">${this.value}</strong>"`;
                noResult.style.display = '';
            }
        } else if (noResult) {
            noResult.style.display = 'none';
        }
    });

    // ── Live search: users ─────────────────────────────────────────
    document.getElementById('userSearch').addEventListener('keyup', function () {
        const q    = this.value.toLowerCase();
        const rows = document.querySelectorAll('#userTable tbody tr:not(#noUserResult)');
        let visible = 0;

        rows.forEach(row => {
            const n = row.querySelector('.user-name')?.textContent.toLowerCase() || '';
            const show = n.includes(q);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        let noResult = document.getElementById('noUserResult');
        if (visible === 0) {
            if (!noResult) {
                noResult = document.createElement('tr');
                noResult.id = 'noUserResult';
                noResult.innerHTML = `<td colspan="5">
                    <div style="text-align:center; padding: 56px 24px;">
                        <i class="bi bi-search" style="font-size:2.2rem; color:var(--gold-light); opacity:.6; display:block; margin-bottom:18px;"></i>
                        <p style="font-family:'DM Sans',sans-serif; font-size:.88rem; color:var(--muted); font-weight:400; letter-spacing:.01em;">
                             No rooms match "<strong style="font-weight:700; color:var(--dark-soft);">${this.value}</strong>"
                        </p>
                    </div>
                  </td>`;
                document.querySelector('#userTable tbody').appendChild(noResult);
            } else {
                noResult.querySelector('p').innerHTML =
                    `No rooms match "<strong style="font-weight:700; color:var(--dark-soft);">${this.value}</strong>"`;
                noResult.style.display = '';
}
        } else if (noResult) {
            noResult.style.display = 'none';
        }
    });
</script>
</body>
</html>