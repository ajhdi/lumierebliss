<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}

$msg = "";
$error = "";

// --- LOGIC: ADD THERAPIST ACCOUNT ---
if (isset($_POST['add_therapist'])) {
    $first = $_POST['first_name'];
    $middle = $_POST['middle_name'];
    $last = $_POST['last_name'];
    $gender = $_POST['gender'];
    $specialty = $_POST['specialty'];
    $work = $_POST['work_experience'];
    $user = $_POST['username'];
    $pass = $_POST['password'];
    $cpass = $_POST['confirm_password'];

    if ($pass !== $cpass) {
        $error = "Passwords do not match.";
    } else {
        // Handle Photo Upload
        $photo = 'default_avatar.jpg'; 
        if (!empty($_FILES['photo']['name'])) {
            $target_dir = "../uploads/therapists/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            
            $file_ext = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
            $photo = time() . "_" . $user . "." . $file_ext;
            move_uploaded_file($_FILES["photo"]["tmp_name"], $target_dir . $photo);
        }

        $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO therapists (first_name, middle_name, last_name, gender, specialty, work_experience, photo, username, password, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
        $stmt = $pdo->prepare($sql);
        
        try {
            $stmt->execute([$first, $middle, $last, $gender, $specialty, $work, $photo, $user, $hashed_pass]);
            $msg = "Therapist account created successfully.";
        } catch (PDOException $e) {
            $error = "Username already exists.";
        }
    }
}

// --- LOGIC: ARCHIVE THERAPIST ---
if (isset($_POST['archive_therapist'])) {
    $id = $_POST['archive_id'];
    $stmt = $pdo->prepare("UPDATE therapists SET status = 'archived' WHERE therapist_id = ?");
    $stmt->execute([$id]);
    $msg = "Therapist account archived.";
}

// --- LOGIC: RESTORE THERAPIST ---
if (isset($_POST['restore_therapist'])) {
    $id = $_POST['restore_id'];
    $stmt = $pdo->prepare("UPDATE therapists SET status = 'active' WHERE therapist_id = ?");
    $stmt->execute([$id]);
    $msg = "Therapist account restored.";
}

// --- LOGIC: UPDATE USER MEMBERSHIP ---
if (isset($_POST['update_membership'])) {
    $type = $_POST['account_type'];
    $uses = ($type == 'member') ? 2 : 0;
    $stmt = $pdo->prepare("UPDATE users SET account_type = ?, semi_luxury_uses_left = ? WHERE user_id = ?");
    $stmt->execute([$type, $uses, $_POST['user_id']]);
    $msg = "User membership updated.";
}

// --- LOGIC: PASSWORD RESET VERIFICATION ---
if (isset($_POST['verify_admin_reset'])) {
    $admin_user = $_POST['admin_user'];
    $admin_pass = $_POST['admin_pass'];
    $target_id = $_POST['target_id'];
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
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_new_password'];
    $id = $_POST['final_id'];
    $type = $_POST['final_type'];

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
        $msg = "Password reset successful.";
    }
}

// Fetch Data
$active_therapists = $pdo->query("SELECT * FROM therapists WHERE status='active' ORDER BY last_name ASC")->fetchAll();
$archived_therapists = $pdo->query("SELECT * FROM therapists WHERE status='archived' ORDER BY last_name ASC")->fetchAll();
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts - Lumiére and Bliss</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-width: 260px; --accent-gold: #C5A059; --dark-bg: #1a1a1a; }
        body { background-color: #f4f6f9; font-family: 'Inter', sans-serif; }
        .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; background: var(--dark-bg); color: white; z-index: 1000; transition: 0.3s; }
        .nav-link { color: rgba(255,255,255,0.6); padding: 15px 25px; display: flex; align-items: center; gap: 12px; transition: 0.2s; text-decoration: none; }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.05); border-left: 4px solid var(--accent-gold); }
        .main-content { margin-left: var(--sidebar-width); padding: 40px; transition: 0.3s; }
        .account-card { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .nav-tabs .nav-link { border: none; color: #6c757d; font-weight: 600; padding: 10px 20px; }
        .nav-tabs .nav-link.active { color: var(--dark-bg); border-bottom: 3px solid var(--accent-gold); background: none; }
        .form-control, .form-select { border: 1px solid #e1e1e1; padding: 10px 15px; }
        .form-control:focus { box-shadow: none; border-color: var(--accent-gold); }
        @media (max-width: 991px) { .sidebar { transform: translateX(-100%); } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="p-4 mb-4">
        <h4 class="fw-bold mb-0 text-white">L&B <span style="color: var(--accent-gold);">Admin</span></h4>
    </div>
    <div class="nav flex-column">
        <a href="dashboard.php" class="nav-link active"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="manage_appointment.php" class="nav-link"><i class="bi bi-calendar-event"></i> Appointments</a>
        
        <!-- Added Treatments Option Here -->
        <a href="manage_treatments.php" class="nav-link"><i class="bi bi-droplet-half"></i> Treatments</a>
        
        <a href="manage_therapist.php" class="nav-link"><i class="bi bi-person-badge"></i> Therapists</a>
        <a href="manage_room.php" class="nav-link"><i class="bi bi-door-open"></i> Rooms</a>
        <a href="manage_account.php" class="nav-link"><i class="bi bi-people"></i> Accounts</a>
        <a href="system_logs.php" class="nav-link"><i class="bi bi-shield-lock"></i> Logs</a>
        <a href="logout.php" class="nav-link text-danger mt-5"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
</nav>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Account Management</h2>
        <button class="btn btn-outline-dark d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('active')">
            <i class="bi bi-list"></i>
        </button>
    </div>

    <?php if($msg): ?> <div class="alert alert-success alert-dismissible fade show"><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div> <?php endif; ?>
    <?php if($error): ?> <div class="alert alert-danger alert-dismissible fade show"><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div> <?php endif; ?>

    <div class="account-card p-4">
        <ul class="nav nav-tabs mb-4" id="accountTabs">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#therapistTab">Therapists</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#userTab">Users</a></li>
        </ul>

        <div class="tab-content">
            <!-- THERAPIST TAB -->
            <div class="tab-pane fade show active" id="therapistTab">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Therapist Accounts</h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-dark rounded-pill btn-sm px-4" data-bs-toggle="modal" data-bs-target="#addTherapistModal">
                            <i class="bi bi-plus-lg me-1"></i> Add Account
                        </button>
                        <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" type="button" data-bs-toggle="collapse" data-bs-target="#archiveSection">
                            <i class="bi bi-archive me-1"></i> Archive (<?= count($archived_therapists) ?>)
                        </button>
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-hover align-middle">
                        <thead class="small text-muted">
                            <tr><th>USERNAME</th><th>NAME</th><th>SPECIALTY</th><th class="text-end">ACTION</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($active_therapists as $t): ?>
                            <tr>
                                <td class="fw-bold"><?= $t['username'] ?></td>
                                <td><?= $t['first_name'].' '.$t['last_name'] ?></td>
                                <td><?= $t['specialty'] ?></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3 me-1" onclick="openResetModal('therapist', <?= $t['therapist_id'] ?>)">Reset</button>
                                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('Archive this therapist?')">
                                        <input type="hidden" name="archive_id" value="<?= $t['therapist_id'] ?>">
                                        <button type="submit" name="archive_therapist" class="btn btn-sm btn-light rounded-circle text-danger shadow-sm"><i class="bi bi-archive"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="collapse" id="archiveSection">
                    <div class="p-4 bg-light rounded-4 border-0 mb-4">
                        <h6 class="fw-bold text-muted mb-3">Archived Accounts</h6>
                        <table class="table table-sm align-middle">
                            <tbody>
                                <?php foreach($archived_therapists as $at): ?>
                                <tr>
                                    <td><?= $at['first_name'].' '.$at['last_name'] ?></td>
                                    <td class="text-end">
                                        <form action="" method="POST" class="d-inline">
                                            <input type="hidden" name="restore_id" value="<?= $at['therapist_id'] ?>">
                                            <button type="submit" name="restore_therapist" class="btn btn-sm btn-success rounded-pill px-3">Restore</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; if(empty($archived_therapists)) echo "<tr><td class='text-muted small'>Empty.</td></tr>"; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- USER TAB -->
            <div class="tab-pane fade" id="userTab">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="small text-muted">
                            <tr><th>CLIENT NAME</th><th>EMAIL</th><th>TYPE</th><th class="text-end">ACTION</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): ?>
                            <tr>
                                <td class="fw-bold"><?= $u['first_name'].' '.$u['last_name'] ?></td>
                                <td><?= $u['email'] ?></td>
                                <td>
                                    <form action="" method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                        <select name="account_type" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                            <option value="non_member" <?= $u['account_type'] == 'non_member' ? 'selected' : '' ?>>Non-Member</option>
                                            <option value="member" <?= $u['account_type'] == 'member' ? 'selected' : '' ?>>Member</option>
                                        </select>
                                        <input type="hidden" name="update_membership" value="1">
                                    </form>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="openResetModal('user', <?= $u['user_id'] ?>)">Reset Password</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Therapist Account -->
<div class="modal fade" id="addTherapistModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pt-4 px-4">
                <h5 class="fw-bold">Create Therapist Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body px-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">First Name</label>
                            <input type="text" name="first_name" class="form-control rounded-pill" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control rounded-pill">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Last Name</label>
                            <input type="text" name="last_name" class="form-control rounded-pill" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Gender</label>
                            <select name="gender" class="form-select rounded-pill" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Specialty</label>
                            <input type="text" name="specialty" class="form-control rounded-pill" placeholder="e.g. Swedish Massage" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Work Experience</label>
                            <textarea name="work_experience" class="form-control" rows="2" style="border-radius: 15px;" placeholder="Brief background..." required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Profile Photo (Optional)</label>
                            <input type="file" name="photo" class="form-control rounded-pill" accept="image/*">
                        </div>
                        <hr class="my-2">
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Username</label>
                            <input type="text" name="username" class="form-control rounded-pill" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Password</label>
                            <input type="password" name="password" id="pass" class="form-control rounded-pill" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Confirm Password</label>
                            <input type="password" name="confirm_password" id="cpass" class="form-control rounded-pill" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showPass">
                                <label class="form-check-label small" for="showPass">Show Password</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_therapist" class="btn btn-dark rounded-pill px-4">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Admin Verification -->
<div class="modal fade" id="adminVerifyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 20px;">
            <div class="modal-header border-0 pt-4 px-4">
                <h6 class="fw-bold">Security Verification</h6>
            </div>
            <form action="" method="POST">
                <div class="modal-body px-4">
                    <input type="hidden" name="target_type" id="target_type">
                    <input type="hidden" name="target_id" id="target_id">
                    <p class="small text-muted mb-3">Enter admin credentials to authorize reset.</p>
                    <input type="text" name="admin_user" class="form-control rounded-pill mb-2" placeholder="Admin Username" required>
                    <input type="password" name="admin_pass" class="form-control rounded-pill" placeholder="Admin Password" required>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="submit" name="verify_admin_reset" class="btn btn-dark w-100 rounded-pill">Verify</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Final Password Reset -->
<div class="modal fade" id="finalResetModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 20px;">
            <div class="modal-header border-0 pt-4 px-4">
                <h6 class="fw-bold">Set New Password</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body px-4">
                    <input type="hidden" name="final_type" value="<?= $_POST['target_type'] ?? '' ?>">
                    <input type="hidden" name="final_id" value="<?= $_POST['target_id'] ?? '' ?>">
                    
                    <div class="mb-3">
                        <label class="form-label small text-muted">New Password</label>
                        <input type="password" name="new_password" id="reset_pass" class="form-control rounded-pill" placeholder="••••••••" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small text-muted">Confirm New Password</label>
                        <input type="password" name="confirm_new_password" id="reset_confirm" class="form-control rounded-pill" placeholder="••••••••" required>
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="showResetPass">
                        <label class="form-check-label small text-muted" for="showResetPass">Show Passwords</label>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="submit" name="finalize_reset" class="btn btn-success w-100 rounded-pill py-2">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openResetModal(type, id) {
    document.getElementById('target_type').value = type;
    document.getElementById('target_id').value = id;
    new bootstrap.Modal(document.getElementById('adminVerifyModal')).show();
}


document.getElementById('showPass').addEventListener('change', function() {
    const type = this.checked ? 'text' : 'password';
    document.getElementById('pass').type = type;
    document.getElementById('cpass').type = type;
});


<?php if(isset($show_reset_modal)): ?>
    new bootstrap.Modal(document.getElementById('finalResetModal')).show();
<?php endif; ?>
</script>
</body>
</html>