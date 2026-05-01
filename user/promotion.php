<?php
include '../includes/header.php';
require_once '../config/db.php';

// Check if user is logged in to show personalized "Join" buttons
$is_logged_in = isset($_SESSION['user_id']);
$account_type = $_SESSION['account_type'] ?? 'guest';
?>

<style>
    .promo-hero {
        background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('../assets/img/spa-bg.jpg');
        background-size: cover;
        background-position: center;
        color: white;
        padding: 100px 0;
    }
    .pricing-card {
        transition: transform 0.3s ease;
        border: 1px solid #eee;
    }
    .pricing-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 45px rgba(0,0,0,0.1) !important;
    }
    .feature-check { color: #C5A059; margin-right: 10px; }
</style>

<!-- Hero Header -->
<div class="promo-hero text-center mb-5">
    <div class="container">
        <h1 class="display-4 fw-bold">Elevate Your Experience</h1>
        <p class="lead">Choose a plan that fits your lifestyle of wellness.</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4 justify-content-center">
        
        <!-- Guest / Standard Plan -->
        <div class="col-md-5 col-lg-4">
            <div class="card h-100 border-0 shadow-sm rounded-4 pricing-card">
                <div class="card-body p-5">
                    <h5 class="fw-bold text-muted small text-uppercase mb-3">Standard</h5>
                    <h2 class="fw-bold mb-4">Guest <span class="fs-6 fw-normal text-muted">/ Always Free</span></h2>
                    <hr>
                    <ul class="list-unstyled mb-5">
                        <li class="mb-3"><i class="bi bi-check2 feature-check"></i> Pay-per-session rates</li>
                        <li class="mb-3"><i class="bi bi-check2 feature-check"></i> Standard treatment menu</li>
                        <li class="mb-3"><i class="bi bi-check2 feature-check"></i> Online booking access</li>
                        <li class="mb-3 text-muted opacity-50"><i class="bi bi-x"></i> No free monthly sessions</li>
                    </ul>
                    <?php if($account_type === 'guest'): ?>
                        <button class="btn btn-outline-dark w-100 rounded-pill disabled">Current Plan</button>
                    <?php else: ?>
                        <a href="index.php" class="btn btn-outline-dark w-100 rounded-pill">View Benefits</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Semi-Luxury / Member Plan -->
        <div class="col-md-5 col-lg-4">
            <div class="card h-100 border-0 shadow-lg rounded-4 pricing-card border-top border-5 border-gold">
                <div class="card-body p-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-gold small text-uppercase mb-0">Premium</h5>
                        <span class="badge bg-gold text-white rounded-pill px-3">POPULAR</span>
                    </div>
                    <h2 class="fw-bold mb-4">Member <span class="fs-6 fw-normal text-muted">/ One-time Fee</span></h2>
                    <hr>
                    <ul class="list-unstyled mb-5">
                        <li class="mb-3"><i class="bi bi-check2-all feature-check"></i> <strong>5 Free</strong> Semi-Luxury Sessions</li>
                        <li class="mb-3"><i class="bi bi-check2-all feature-check"></i> 15% Off all additional services</li>
                        <li class="mb-3"><i class="bi bi-check2-all feature-check"></i> Priority therapist booking</li>
                        <li class="mb-3"><i class="bi bi-check2-all feature-check"></i> Exclusive member-only lounge</li>
                    </ul>
                    
                    <?php if($account_type === 'member'): ?>
                        <div class="text-center">
                            <p class="small text-success fw-bold mb-2"><i class="bi bi-star-fill"></i> You are a Member!</p>
                            <a href="home.php" class="btn btn-dark w-100 rounded-pill">Manage Membership</a>
                        </div>
                    <?php else: ?>
                        <a href="membership_payment.php" class="btn btn-gold w-100 rounded-pill shadow-sm py-2 fw-bold">Upgrade Now</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- FAQ / Membership Details -->
    <div class="row mt-5 pt-5">
        <div class="col-md-8 mx-auto text-center">
            <h4 class="fw-bold mb-4">How it Works</h4>
            <div class="row g-4 text-start">
                <div class="col-md-6">
                    <h6><i class="bi bi-info-circle me-2 text-gold"></i> Use-as-you-go</h6>
                    <p class="small text-muted">Your 5 sessions are stored in your profile. When booking, select 'Use Membership Credit' to pay 0.00 pesos.</p>
                </div>
                <div class="col-md-6">
                    <h6><i class="bi bi-arrow-repeat me-2 text-gold"></i> Easy Tracking</h6>
                    <p class="small text-muted">Your dashboard will always show how many sessions you have left in real-time.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>