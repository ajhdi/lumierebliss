<?php
include '../includes/header.php';
require_once '../config/db.php';

// Fetch only available rooms
$stmt = $pdo->query("SELECT * FROM rooms WHERE status = 'available' ORDER BY room_name ASC");
$rooms = $stmt->fetchAll();
?>

<style>
    :root { --gold: #C5A059; --dark: #1a1a1a; }
    body { background-color: #fdfbf7; }

    .room-card {
        border: none;
        border-radius: 24px;
        background: #ffffff;
        transition: all 0.4s ease;
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.02);
    }

    .room-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.05) !important;
    }

    .room-icon-box {
        height: 160px;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--gold);
        font-size: 3.5rem;
    }

    .room-type-badge {
        background: var(--gold);
        color: white;
        font-size: 0.65rem;
        padding: 4px 12px;
        border-radius: 50px;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 700;
    }

    .btn-view-room {
        background: var(--dark);
        color: #fff;
        border-radius: 50px;
        padding: 10px 20px;
        font-weight: 600;
        font-size: 0.85rem;
        transition: 0.3s;
        text-decoration: none;
        display: block;
        width: 100%;
        text-align: center;
    }

    .btn-view-room:hover {
        background: var(--gold);
        color: #fff;
    }
</style>

<div class="container py-5">
    <!-- Header -->
    <div class="text-center mb-5 mt-4">
        <h6 class="text-uppercase fw-bold mb-2" style="color: var(--gold); letter-spacing: 3px;">The Sanctuary</h6>
        <h1 class="fw-bold text-dark">Our Private Suites</h1>
        <p class="text-muted mx-auto" style="max-width: 500px;">Explore our tranquil spaces designed for ultimate privacy and relaxation.</p>
    </div>

    <div class="row g-4">
        <?php if ($rooms): ?>
            <?php foreach ($rooms as $r): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card room-card shadow-sm h-100">
                        <!-- Room Visual Placeholder -->
                        <div class="room-icon-box">
                            <i class="bi bi-door-open"></i>
                        </div>
                        
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="mb-2">
                                <span class="room-type-badge"><?= htmlspecialchars($r['room_type'] ?? 'Standard') ?></span>
                            </div>
                            
                            <h4 class="fw-bold mb-1"><?= htmlspecialchars($r['room_name']) ?></h4>
                            <p class="small text-muted mb-4">
                                Optimized for a serene and quiet treatment experience.
                            </p>
                            
                            <div class="mt-auto pt-3 border-top">
                                <a href="appointment.php?rid=<?= $r['room_id'] ?>" class="btn btn-view-room">
                                    Book This Room
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="py-5 bg-white rounded-5 shadow-sm">
                    <i class="bi bi-house-exclamation display-1 text-light"></i>
                    <p class="mt-3 text-muted">All rooms are currently occupied or undergoing maintenance.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>