<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}


// Handle Edit Therapist Details (Profile only, no password/account creation)
if (isset($_POST['save_therapist'])) {
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    $gender = $_POST['gender'];
    $specialty = $_POST['specialty'];
    $experience = $_POST['work_experience'];
    $id = $_POST['therapist_id'];
    
    $sql = "UPDATE therapists SET first_name=?, middle_name=?, last_name=?, gender=?, specialty=?, work_experience=? WHERE therapist_id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$first_name, $middle_name, $last_name, $gender, $specialty, $experience, $id]);

    // Clear old slots and save 4 new unique slots
    $pdo->prepare("DELETE FROM therapist_schedule WHERE therapist_id = ?")->execute([$id]);
    if (isset($_POST['schedule_times'])) {
        // array_unique prevents duplicate times from being saved
        $unique_times = array_unique(array_filter($_POST['schedule_times']));
        $stmt_sched = $pdo->prepare("INSERT INTO therapist_schedule (therapist_id, time_start) VALUES (?, ?)");
        foreach ($unique_times as $time) {
            $stmt_sched->execute([$id, $time]);
        }
    }

    
    
    header("Location: manage_therapist.php?msg=Updated");
    exit();
}


// Fetch Active Therapists
$therapists = $pdo->query("SELECT * FROM therapists WHERE status = 'active' ORDER BY last_name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Therapists - Lumiére and Bliss</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-width: 260px; --accent-gold: #C5A059; --dark-bg: #1a1a1a; }
        body { background-color: #f4f6f9; }
        .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; background: var(--dark-bg); color: white; z-index: 1000; transition: 0.3s; }
        .nav-link { color: rgba(255,255,255,0.6); padding: 15px 25px; display: flex; align-items: center; gap: 12px; transition: 0.2s; text-decoration: none; }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.05); border-left: 4px solid var(--accent-gold); }
        .main-content { margin-left: var(--sidebar-width); padding: 40px; transition: 0.3s; }
        .therapist-card { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
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
        <h2 class="fw-bold">Therapist Profiles</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-dark d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('active')">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </div>

    <div class="therapist-card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-muted small">
                        <th>NAME</th>
                        <th>SPECIALTY</th>
                        <th>GENDER</th>
                        <th>STATUS</th>
                        <th class="text-end">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($therapists as $t): ?>
                    <tr>
                        <td class="fw-bold"><?= $t['first_name'].' '.$t['last_name'] ?></td>
                        <td><?= $t['specialty'] ?></td>
                        <td><?= $t['gender'] ?></td>
                        <td><span class="badge bg-success-subtle text-success rounded-pill">Active</span></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-light rounded-circle" title="View/Edit Details" onclick='editTherapist(<?= json_encode($t) ?>)'><i class="bi bi-pencil"></i></button>
                            <form action="" method="POST" class="d-inline">
                                <input type="hidden" name="archive_id" value="<?= $t['therapist_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-light rounded-circle text-danger" title="Archive" onclick="return confirm('Archive this therapist?')"><i class="bi bi-archive"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Therapist Modal (Profile Info Only) -->
<div class="modal fade" id="therapistModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="" method="POST" id="therapistForm">
                <div class="modal-header border-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold">Edit Therapist Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4">
                    <input type="hidden" name="therapist_id" id="therapist_id">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label small fw-bold">First Name</label><input type="text" name="first_name" id="f_name" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Middle Name</label><input type="text" name="middle_name" id="m_name" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label small fw-bold">Last Name</label><input type="text" name="last_name" id="l_name" class="form-control" required></div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Gender</label>
                            <select name="gender" id="gender" class="form-select">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Specialty</label><input type="text" name="specialty" id="specialty" class="form-control" required></div>
                        <div class="col-12"><label class="form-label small fw-bold">Work Experience</label><textarea name="work_experience" id="experience" class="form-control" rows="4"></textarea></div>

                        <div class="col-12 mt-3">
                        <label class="form-label small fw-bold text-uppercase" style="color: var(--accent-gold);">Daily Schedule (4 Slots)</label>
                        <div class="row g-2">
                            <div class="col-3"><input type="time" name="schedule_times[]" class="form-control sched-input"></div>
                            <div class="col-3"><input type="time" name="schedule_times[]" class="form-control sched-input"></div>
                            <div class="col-3"><input type="time" name="schedule_times[]" class="form-control sched-input"></div>
                            <div class="col-3"><input type="time" name="schedule_times[]" class="form-control sched-input"></div>
                        </div>
                    </div>

                    </div>
                    <div class="alert alert-info mt-3 py-2 small">
                        <i class="bi bi-info-circle me-2"></i> Account credentials (username/password) are managed in <b>Manage Accounts</b>.
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_therapist" class="btn btn-dark rounded-pill px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editTherapist(data) {
    document.getElementById('therapist_id').value = data.therapist_id;
    document.getElementById('f_name').value = data.first_name;
    document.getElementById('m_name').value = data.middle_name;
    document.getElementById('l_name').value = data.last_name;
    document.getElementById('gender').value = data.gender;
    document.getElementById('specialty').value = data.specialty;
    document.getElementById('experience').value = data.work_experience;
    // Fetch and fill the 4 schedule slots
    const inputs = document.querySelectorAll('.sched-input');
    inputs.forEach(input => input.value = ''); // Reset first
    
    fetch(`get_schedules.php?id=${data.therapist_id}`)
        .then(res => res.json())
        .then(slots => {
            slots.forEach((slot, index) => {
                if(inputs[index]) inputs[index].value = slot.time_start;
            });
        });
    
    var myModal = new bootstrap.Modal(document.getElementById('therapistModal'));
    myModal.show();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>