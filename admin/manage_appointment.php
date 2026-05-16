<?php
session_start();
require_once '../config/db.php';

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
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');
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
    <title>Manage Appointments - Lumiére and Bliss</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-width: 260px; --accent-gold: #C5A059; --dark-bg: #1a1a1a; }
        body { background-color: #f4f6f9; }
        
        .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; background: var(--dark-bg); color: white; z-index: 1000; transition: 0.3s; }
        .nav-link { color: rgba(255,255,255,0.6); padding: 15px 25px; display: flex; align-items: center; gap: 12px; transition: 0.2s; text-decoration: none; }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.05); border-left: 4px solid var(--accent-gold); }
        
        .main-content { margin-left: var(--sidebar-width); padding: 40px; transition: 0.3s; }
        .table-container { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        <h2 class="fw-bold">Appointments: <span class="text-muted"><?= date('F d, Y', strtotime($filter_date)) ?></span></h2>
        <div>
            <button class="btn btn-dark rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#disableDateModal">
                <i class="bi bi-calendar-x me-2"></i> Disable Dates
            </button>
            <button class="btn btn-outline-dark d-lg-none ms-2" onclick="document.getElementById('sidebar').classList.toggle('active')">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="table-container mb-4">
        <form class="row g-3">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Filter Date</label>
                <input type="date" name="filter_date" class="form-control" value="<?= $filter_date ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Therapist</label>
                <select name="therapist_id" class="form-select">
                    <option value="">All Therapists</option>
                    <?php foreach($therapists as $t): ?>
                        <option value="<?= $t['therapist_id'] ?>" <?= $therapist_id == $t['therapist_id'] ? 'selected' : '' ?>>
                            <?= $t['first_name'] . ' ' . $t['last_name'] ?>
                        </option>
                    <?php foreach($therapists as $t); endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 rounded-pill" style="background: var(--dark-bg); border:none;">Apply Filter</button>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Time</th>
                        <th>Client</th>
                        <th>Therapist</th>
                        <th>Room</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($appointments)): ?>
                        <tr><td colspan="6" class="text-center py-4">No appointments found for this selection.</td></tr>
                    <?php else: ?>
                        <?php foreach($appointments as $app): ?>
                        <tr>
                            <td class="fw-bold"><?= date('g:i A', strtotime($app['appointment_time'])) ?></td>
                            <td><?= $app['first_name'] . ' ' . $app['last_name'] ?></td>
                            <td><?= $app['t_fname'] . ' ' . $app['t_lname'] ?></td>
                            <td><?= $app['room_name'] ?></td>
                            <td>
                                <span class="badge rounded-pill <?= $app['status'] == 'confirmed' ? 'bg-primary' : 'bg-success' ?>">
                                    <?= ucfirst($app['status']) ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-dark rounded-pill">View</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Disable Date Modal -->
<div class="modal fade" id="disableDateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form id="disableDateForm" action="" method="POST">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Disable Specific Date</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Date</label>
                        <input type="date" name="disabled_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Remarks (Reason)</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Holiday, emergency maintenance, etc." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4" onclick="disableDate()">Disable Date</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function disableDate() {
    const form = document.getElementById('disableDateForm'); // your form ID
    const formData = new FormData(form);
    formData.append('disable_date_btn', '1'); // flag for PHP

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
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: data.message
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Server Error',
            text: 'Something went wrong!'
        });
        console.error(error);
    });
}

</script>
</body>
</html>