<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}

// Fetch logs with the most recent first
$logs = $pdo->query("SELECT * FROM system_logs ORDER BY created_at DESC")->fetchAll();

// Optional: Logic to clear logs (Admin only)
if (isset($_POST['clear_logs'])) {
    $pdo->query("DELETE FROM system_logs");
    header("Location: system_logs.php?msg=Cleared");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - Lumiére and Bliss</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-width: 260px; --accent-gold: #C5A059; --dark-bg: #1a1a1a; }
        body { background-color: #f4f6f9; }
        .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; background: var(--dark-bg); color: white; z-index: 1000; transition: 0.3s; }
        .nav-link { color: rgba(255,255,255,0.6); padding: 15px 25px; display: flex; align-items: center; gap: 12px; transition: 0.2s; text-decoration: none; }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.05); border-left: 4px solid var(--accent-gold); }
        .main-content { margin-left: var(--sidebar-width); padding: 40px; transition: 0.3s; }
        .log-card { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .log-entry { border-left: 3px solid #dee2e6; padding-left: 15px; margin-bottom: 15px; }
        .log-entry.important { border-left-color: var(--accent-gold); }
        
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

<nav class="sidebar" id="sidebar">
    <div class="p-4 mb-4 text-white">
        <h4 class="fw-bold">L&B <span style="color: var(--accent-gold);">Admin</span></h4>
    </div>
    <div class="nav flex-column">
        <a href="dashboard.php" class="nav-link"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="manage_appointment.php" class="nav-link"><i class="bi bi-calendar-event"></i> Appointments</a>
        <a href="manage_therapist.php" class="nav-link"><i class="bi bi-person-badge"></i> Therapists</a>
        <a href="manage_room.php" class="nav-link"><i class="bi bi-door-open"></i> Rooms</a>
        <a href="manage_account.php" class="nav-link"><i class="bi bi-people"></i> Accounts</a>
        <a href="system_logs.php" class="nav-link active"><i class="bi bi-shield-lock"></i> Logs</a>
        <a href="logout.php" class="nav-link text-danger mt-5"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
</nav>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">System Audit Logs</h2>
        <div class="d-flex gap-2">
            <form action="" method="POST" onsubmit="return confirm('This will permanently delete all logs. Continue?')">
                <button type="submit" name="clear_logs" class="btn btn-outline-danger rounded-pill px-4 btn-sm">Clear Logs</button>
            </form>
            <button class="btn btn-outline-dark d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('active')">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </div>

    <div class="log-card p-4">
        <?php if (empty($logs)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-clipboard-x display-4"></i>
                <p class="mt-2">No system logs found.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr class="small text-muted">
                            <th>TIMESTAMP</th>
                            <th>USER/ADMIN</th>
                            <th>ACTION</th>
                            <th>DETAILS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($logs as $log): ?>
                        <tr>
                            <td class="text-nowrap small text-muted">
                                <?= date('M d, Y | h:i A', strtotime($log['created_at'])) ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark fw-normal border">
                                    <?= htmlspecialchars($log['performed_by']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold"><?= htmlspecialchars($log['action']) ?></span>
                            </td>
                            <td class="small">
                                <?= htmlspecialchars($log['details']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>