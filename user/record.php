<?php
include '../includes/header.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch appointments with joined data for Treatment and Therapist names
// Note: Adjust column names if your DB uses first_name/last_name for therapists
$sql = "SELECT a.*, t.name AS treatment_name, th.first_name AS therapist_fname, th.last_name AS therapist_lname 
        FROM appointments a
        JOIN treatments t ON a.treatment_id = t.treatment_id
        JOIN therapists th ON a.therapist_id = th.therapist_id
        WHERE a.user_id = ? 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$records = $stmt->fetchAll();
?>

<style>
    :root { --gold: #C5A059; --dark: #1a1a1a; }
    body { background-color: #fdfbf7; }

    .record-card {
        border: none;
        border-radius: 24px;
        background: #ffffff;
        border: 1px solid rgba(0,0,0,0.03);
    }

    .table thead th {
        background-color: #f8f9fa;
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 1px;
        font-weight: 700;
        color: #888;
        border: none;
        padding: 15px 20px;
    }

    .table tbody td {
        padding: 20px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f1f1;
        font-size: 0.9rem;
    }

    .status-badge {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 50px;
        text-transform: uppercase;
    }

    .status-pending { background: #fff4e6; color: #d9480f; }
    .status-confirmed { background: #ebfbee; color: #2b8a3e; }
    .status-completed { background: #f1f3f5; color: #495057; }
    .status-cancelled { background: #fff5f5; color: #c92a2a; }

    .empty-state {
        padding: 80px 0;
        text-align: center;
    }
</style>

<div class="container py-5">
    <div class="row mb-4 align-items-end">
        <div class="col-md-6">
            <h6 class="text-uppercase fw-bold mb-2" style="color: var(--gold); letter-spacing: 2px;">Activity</h6>
            <h1 class="fw-bold text-dark m-0">My Records</h1>
        </div>
        <div class="col-md-6 text-md-end">
            <p class="text-muted small mb-0">Tracking your wellness journey since <?= date('M Y', strtotime($_SESSION['created_at'] ?? 'now')) ?></p>
        </div>
    </div>

    <div class="card record-card shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Treatment</th>
                        <th>Therapist</th>
                        <th>Payment</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($records): ?>
                        <?php foreach ($records as $r): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= date('M d, Y', strtotime($r['appointment_date'])) ?></div>
                                    <div class="small text-muted"><?= date('h:i A', strtotime($r['appointment_time'])) ?></div>
                                </td>
                                <td>
                                    <div class="fw-medium"><?= htmlspecialchars($r['treatment_name']) ?></div>
                                </td>
                                <td>
                                    <div class="small text-muted">
                                        <?= htmlspecialchars($r['therapist_fname'] . ' ' . $r['therapist_lname']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if($r['payment_type'] === 'membership'): ?>
                                        <span class="badge bg-light text-dark border fw-normal">Member Credit</span>
                                    <?php else: ?>
                                        <span class="text-dark fw-medium">₱<?= number_format($r['amount'], 2) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                        $statusClass = 'status-' . strtolower($r['status']);
                                        echo "<span class='status-badge $statusClass'>" . htmlspecialchars($r['status']) . "</span>";
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-state">
                                <i class="bi bi-calendar-x display-4 text-light d-block mb-3"></i>
                                <p class="text-muted mb-4">You haven't booked any appointments yet.</p>
                                <a href="treatment.php" class="btn btn-dark rounded-pill px-4">Find a Treatment</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>