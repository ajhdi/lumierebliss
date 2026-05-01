<?php
include '../includes/header.php';
require_once '../config/db.php';

// Check for the correct column name in your database
// Using first_name based on your project's naming convention
$stmt = $pdo->query("SELECT * FROM therapists WHERE status = 'active' ORDER BY first_name ASC");
$therapists = $stmt->fetchAll();
?>

<style>
    :root { --gold: #C5A059; --dark: #1a1a1a; }
    body { background-color: #fdfbf7; }

    .therapist-card {
        border: none;
        border-radius: 24px;
        background: #ffffff;
        transition: all 0.4s ease;
        border: 1px solid rgba(0,0,0,0.02);
    }

    .therapist-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.05) !important;
    }

    .avatar-circle {
        width: 70px;
        height: 70px;
        background: #f8f9fa;
        color: var(--gold);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0 auto 15px auto;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
    }

    .btn-book-specialist {
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
    }

    .btn-book-specialist:hover {
        background: var(--gold);
        color: #fff;
    }
</style>

<div class="container py-5">
    <div class="text-center mb-5 mt-4">
        <h6 class="text-uppercase fw-bold mb-2" style="color: var(--gold); letter-spacing: 3px;">The Team</h6>
        <h1 class="fw-bold text-dark">Expert Therapists</h1>
        <p class="text-muted">Dedicated professionals for your wellness journey.</p>
    </div>

    <div class="row g-4 justify-content-center">
        <?php if ($therapists): ?>
            <?php foreach ($therapists as $th): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card therapist-card shadow-sm p-4 text-center h-100">
                        <div class="avatar-circle">
                            <!-- Safe check for first_name -->
                            <?= isset($th['first_name']) ? strtoupper(substr($th['first_name'], 0, 1)) : 'T' ?>
                        </div>
                        
                        <h5 class="fw-bold mb-1">
                            <?php 
                                // Combining first and last name safely
                                $fname = $th['first_name'] ?? 'Therapist';
                                $lname = $th['last_name'] ?? '';
                                echo htmlspecialchars($fname . ' ' . $lname);
                            ?>
                        </h5>
                        <p class="small text-muted mb-4 text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;">
                            Certified Specialist
                        </p>
                        
                        <div class="mt-auto">
                            <a href="appointment.php?thid=<?= $th['therapist_id'] ?>" class="btn btn-book-specialist">
                                Book Now
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">No therapists are currently available.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>