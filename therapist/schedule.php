<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['therapist_id'])) {
    header("Location: signin_therapist.php");
    exit();
}

$therapist_id = $_SESSION['therapist_id'];
$filter_date = $_GET['date'] ?? date('Y-m-d');

// Fetch appointments for this therapist
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

// Count sessions for the day
$session_count = count($appointments);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule | Lumiére and Bliss</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .navbar { background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .schedule-card { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .status-badge { font-size: 0.75rem; padding: 5px 12px; border-radius: 50px; }
        .session-counter { background: #5d6d7e; color: white; padding: 10px 20px; border-radius: 10px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg py-3 mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold text-secondary" href="#">L&B THERAPIST</a>
        <div class="ms-auto d-flex align-items-center">
            <span class="me-3 d-none d-md-inline small text-muted">Welcome, <b><?= $_SESSION['therapist_name'] ?></b></span>
            <a href="logout.php" class="btn btn-sm btn-outline-danger rounded-pill px-3">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h3 class="fw-bold m-0">Daily Schedule</h3>
            <p class="text-muted">Manage your appointments and client details.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="session-counter d-inline-block">
                <small class="d-block text-uppercase" style="font-size: 0.65rem; opacity: 0.8;">Daily Sessions</small>
                <span class="h4 fw-bold"><?= $session_count ?></span> <span class="small">/ 4</span>
            </div>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="schedule-card p-4 mb-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Select Date</label>
                <input type="date" name="date" class="form-control rounded-pill" value="<?= $filter_date ?>" onchange="this.form.submit()">
            </div>
        </form>
    </div>

    <!-- Appointments List -->
    <div class="schedule-card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="text-muted small">
                    <tr>
                        <th>TIME</th>
                        <th>CLIENT</th>
                        <th>SERVICE</th>
                        <th>ROOM</th>
                        <th>STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($appointments)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No appointments scheduled for this date.</td></tr>
                    <?php else: ?>
                        <?php foreach($appointments as $app): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= date('h:i A', strtotime($app['appointment_time'])) ?></div>
                                <small class="text-muted">to <?= date('h:i A', strtotime($app['end_time'])) ?></small>
                            </td>
                            <td>
                                <div class="fw-bold"><?= $app['first_name'] . ' ' . $app['last_name'] ?></div>
                                <small class="text-muted"><i class="bi bi-telephone"></i> <?= $app['contact_number'] ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border fw-normal">
                                    <?= $app['treatment_name'] ?: $app['package_name'] ?>
                                </span>
                            </td>
                            <td><?= $app['room_name'] ?></td>
                            <td>
                                <?php 
                                    $status_class = [
                                        'confirmed' => 'bg-primary-subtle text-primary',
                                        'completed' => 'bg-success-subtle text-success',
                                        'cancelled' => 'bg-danger-subtle text-danger'
                                    ][$app['status']];
                                ?>
                                <span class="status-badge <?= $status_class ?> text-uppercase fw-bold">
                                    <?= $app['status'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>