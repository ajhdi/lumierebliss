<?php
include '../includes/header.php';
require_once '../config/db.php';

// Access control: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch fresh data from DB to ensure 'uses_left' is accurate
$stmt = $pdo->prepare("SELECT account_type, semi_luxury_uses_left, created_at FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch();

$is_member = ($user_data['account_type'] === 'member');
?>

<div class="container py-5">
    <!-- Welcome Header -->
    <div class="row mb-5">
        <div class="col-md-8">
            <h2 class="fw-bold">Hello, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h2>
            <p class="text-muted">Welcome back to your sanctuary. Ready for some relaxation?</p>
        </div>
        <div class="col-md-4 text-md-end">
            <span class="badge rounded-pill px-3 py-2 <?= $is_member ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                <i class="bi bi-patch-check-fill me-1"></i> 
                <?= strtoupper($user_data['account_type']) ?> STATUS
            </span>
        </div>
    </div>

    <div class="row g-4">
        <!-- Membership Card / Promo -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100" style="background: linear-gradient(135deg, #1a1a1a, #333); color: white;">
                <h5 class="fw-bold mb-3">Lumiére Membership</h5>
                
                <?php if($is_member): ?>
                    <p class="small opacity-75">Your semi-luxury package is active.</p>
                    <div class="display-5 fw-bold mb-1"><?= $user_data['semi_luxury_uses_left'] ?></div>
                    <p class="small text-uppercase tracking-wider">Sessions Remaining</p>
                    <a href="appointment.php" class="btn btn-light btn-sm w-100 rounded-pill mt-3">Use a Session</a>
                <?php else: ?>
                    <p class="small opacity-75">Unlock exclusive rates and free monthly sessions by becoming a member.</p>
                    <hr class="border-secondary">
                    <a href="promotion.php" class="btn btn-gold btn-sm w-100 rounded-pill mt-auto">View Packages</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-8">
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                        <div class="display-6 text-gold mb-2"><i class="bi bi-calendar-plus"></i></div>
                        <h6 class="fw-bold">Book Appointment</h6>
                        <p class="small text-muted">Schedule your next massage or treatment.</p>
                        <a href="appointment.php" class="stretched-link"></a>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                        <div class="display-6 text-gold mb-2"><i class="bi bi-clock-history"></i></div>
                        <h6 class="fw-bold">My History</h6>
                        <p class="small text-muted">View your past and upcoming sessions.</p>
                        <a href="record.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Suggested Treatments (Minimalist List) -->
    <div class="mt-5">
        <h5 class="fw-bold mb-4">Recommended for You</h5>
        <div class="row g-3">
            <?php
            $stmt = $pdo->query("SELECT * FROM treatments LIMIT 3");
            while($row = $stmt->fetch()):
            ?>
            <div class="col-md-4">
                <div class="d-flex align-items-center p-3 bg-white rounded-4 shadow-sm">
                    <div class="flex-shrink-0 bg-light rounded-3 p-3 text-gold">
                        <i class="bi bi-flower1 fs-4"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-0 fw-bold"><?= $row['name'] ?></h6>
                        <small class="text-muted">Starting at P<?= number_format($row['price'], 2) ?></small>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 