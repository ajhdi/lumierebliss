<?php
session_start();
// Redirect to home if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

include '../includes/header.php';
?>

<!-- Hero Section -->
<section class="py-5 text-center bg-white border-bottom">
    <div class="container py-5 mt-4">
        <h6 class="text-uppercase fw-bold mb-3" style="color: var(--spa-gold); letter-spacing: 5px;">Sanctuary of Peace</h6>
        <h1 class="display-4 fw-bold mb-3 text-dark">Your Journey to <span style="color: var(--spa-gold);">Serenity</span></h1>
        <p class="lead text-muted mx-auto mb-5" style="max-width: 600px;">Experience the pinnacle of relaxation with our signature treatments, designed to harmonize your body and mind.</p>
        <div class="mt-4">
            <!-- Force login for booking -->
            <a href="signin.php" class="btn btn-gold btn-lg px-5 py-3 shadow-sm fw-bold">Book Your Session</a>
        </div>
    </div>
</section>

<!-- Treatments Preview -->
<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-end mb-5">
            <div>
                <h2 class="fw-bold m-0 text-dark">Our Treatments</h2>
                <p class="text-muted mb-0">Signature services for your well-being</p>
            </div>
            <!-- This also directs to signin so they can see the full interactive catalog -->
            <a href="signin.php" class="text-decoration-none fw-bold small text-uppercase" style="color: var(--spa-gold); letter-spacing: 1px;">View All <i class="bi bi-arrow-right"></i></a>
        </div>
        
        <div class="row g-4">
            <?php
            $previews = [
                ['name' => 'Swedish Massage', 'time' => '60m', 'price' => '₱800', 'icon' => 'bi-wind'],
                ['name' => 'Hot Stone Massage', 'time' => '90m', 'price' => '₱1,200', 'icon' => 'bi-fire'],
                ['name' => 'Aroma Bliss', 'time' => '120m', 'price' => '₱1,400', 'icon' => 'bi-droplet-half']
            ];
            foreach($previews as $p): ?>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-5 text-center rounded-4 border-top border-4" style="border-top-color: var(--spa-gold) !important;">
                    <div class="mb-3">
                        <i class="bi <?= $p['icon'] ?> display-5 text-muted opacity-50"></i>
                    </div>
                    <h5 class="fw-bold text-dark"><?= $p['name'] ?></h5>
                    <p class="small text-muted mb-4"><?= $p['time'] ?> of pure relaxation</p>
                    <h6 class="fw-bold fs-5 mb-4" style="color: var(--spa-gold);"><?= $p['price'] ?></h6>
                    <!-- Force login for booking -->
                    <a href="signin.php" class="btn btn-outline-dark btn-sm rounded-pill px-4 py-2 fw-bold">Book Now</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Special Membership Callout -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="p-5 rounded-5 bg-dark text-white text-center">
            <h2 class="fw-bold mb-3">Join the 18+ Elite Membership</h2>
            <p class="opacity-75 mb-4 mx-auto" style="max-width: 500px;">Sign up today to enjoy exclusive semi-luxury credits and priority booking for our top therapists.</p>
            <a href="signup.php" class="btn btn-gold px-5 py-3 rounded-pill fw-bold">Create Free Account</a>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>