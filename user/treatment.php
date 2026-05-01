<?php
include '../includes/header.php';
require_once '../config/db.php';

// Fetch all active treatments
$stmt = $pdo->query("SELECT * FROM treatments ORDER BY name ASC");
$treatments = $stmt->fetchAll();
?>

<style>
    .treatment-card {
        border: none;
        border-radius: 20px;
        transition: all 0.3s ease;
        background: #fff;
    }
    .treatment-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.08) !important;
    }
    .category-pill {
        font-size: 0.7rem;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: #C5A059;
        font-weight: 700;
    }
    .price-tag {
        font-size: 1.25rem;
        font-weight: 800;
        color: #1a1a1a;
    }
    .duration-label {
        font-size: 0.85rem;
        color: #6c757d;
    }
    .btn-book {
        background: #1a1a1a;
        color: #fff;
        border-radius: 10px;
        padding: 8px 20px;
        font-size: 0.9rem;
        transition: 0.3s;
    }
    .btn-book:hover {
        background: #C5A059;
        color: #fff;
    }
</style>

<div class="container py-5">
    <div class="row mb-5 text-center">
        <div class="col-lg-6 mx-auto">
            <h6 class="category-pill mb-2">Service Menu</h6>
            <h1 class="fw-bold mb-3">Holistic Wellness</h1>
            <p class="text-muted">Discover treatments designed to harmonize your physical and mental well-being.</p>
        </div>
    </div>

    <div class="row g-4">
        <?php if (count($treatments) > 0): ?>
            <?php foreach ($treatments as $t): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card treatment-card shadow-sm h-100 p-3">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($t['name']) ?></h5>
                                    <span class="duration-label">
                                        <i class="bi bi-clock me-1"></i> <?= $t['duration'] ?> Minutes
                                    </span>
                                </div>
                            </div>
                            
                            <p class="small text-muted mb-4 flex-grow-1">
                                <?= htmlspecialchars($t['description']) ?>
                            </p>
                            
                            <hr class="opacity-25 mb-4">
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="small text-muted d-block" style="font-size: 0.7rem;">INVESTMENT</span>
                                    <span class="price-tag">₱<?= number_format($t['price'], 2) ?></span>
                                </div>
                                <a href="appointment.php?tid=<?= $t['treatment_id'] ?>" class="btn btn-book text-decoration-none">
                                    Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">No treatments are currently available. Please check back later.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>