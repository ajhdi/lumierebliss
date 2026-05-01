<?php
include '../includes/header.php';
?>

<style>
    :root { --gold: #C5A059; --dark: #1a1a1a; }
    body { background-color: #fdfbf7; }

    .about-hero {
        padding: 100px 0;
        background-color: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.03);
    }

    .stat-card {
        border: none;
        background: transparent;
        padding: 20px;
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--dark);
        display: block;
    }

    .stat-label {
        color: var(--gold);
        text-transform: uppercase;
        font-weight: 700;
        font-size: 0.75rem;
        letter-spacing: 2px;
    }

    .mission-box {
        background: var(--dark);
        color: white;
        border-radius: 30px;
        padding: 60px;
        position: relative;
        overflow: hidden;
    }

    .mission-box::after {
        content: '"';
        position: absolute;
        top: -20px;
        right: 40px;
        font-size: 15rem;
        color: rgba(255,255,255,0.05);
        font-family: serif;
    }

    .feature-icon {
        width: 50px;
        height: 50px;
        background: #fdfbf7;
        color: var(--gold);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
        font-size: 1.5rem;
    }
</style>

<!-- Hero Section -->
<section class="about-hero">
    <div class="container text-center">
        <h6 class="text-uppercase fw-bold mb-3" style="color: var(--gold); letter-spacing: 4px;">Our Essence</h6>
        <h1 class="display-4 fw-bold mb-4 text-dark">Lumiére & Bliss</h1>
        <p class="lead text-muted mx-auto" style="max-width: 700px;">
            Born from a vision of absolute serenity, we provide a sanctuary where the art of healing meets modern luxury.
        </p>
    </div>
</section>

<!-- Stats / Trust Section -->
<section class="py-5">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <span class="stat-number">10+</span>
                    <span class="stat-label">Specialists</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <span class="stat-number">5k+</span>
                    <span class="stat-label">Sessions</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <span class="stat-number">15+</span>
                    <span class="stat-label">Treatments</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <span class="stat-number">4.9</span>
                    <span class="stat-label">Rating</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mission Section -->
<section class="py-5">
    <div class="container">
        <div class="mission-box shadow-lg">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <h2 class="fw-bold mb-4">Our Mission</h2>
                    <p class="lead opacity-75 mb-0">
                        At Lumiére & Bliss, our goal is to redefine the spa experience by integrating high-end service strategy with personalized wellness. We believe that relaxation is not a luxury, but a necessity for a balanced life.
                    </p>
                </div>
                <div class="col-lg-5 text-center d-none d-lg-block">
                    <i class="bi bi-shield-check" style="font-size: 8rem; color: var(--gold); opacity: 0.8;"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="py-5">
    <div class="container">
        <div class="row g-5 py-5">
            <div class="col-md-4">
                <div class="feature-icon"><i class="bi bi-stars"></i></div>
                <h5 class="fw-bold">Premium Quality</h5>
                <p class="small text-muted">We use only organic, high-grade oils and products for every treatment session.</p>
            </div>
            <div class="col-md-4">
                <div class="feature-icon"><i class="bi bi-person-heart"></i></div>
                <h5 class="fw-bold">Expert Care</h5>
                <p class="small text-muted">Our therapists undergo rigorous certification to ensure the highest standard of healing.</p>
            </div>
            <div class="col-md-4">
                <div class="feature-icon"><i class="bi bi-calendar-check"></i></div>
                <h5 class="fw-bold">Seamless Booking</h5>
                <p class="small text-muted">Our integrated digital platform makes scheduling your tranquility effortless.</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5 text-center">
    <div class="container">
        <hr class="opacity-10 mb-5">
        <h3 class="fw-bold mb-4">Ready to start your journey?</h3>
        <a href="treatment.php" class="btn btn-dark rounded-pill px-5 py-3 fw-bold shadow-sm">Explore Treatments</a>
    </div>
</section>

<?php include '../includes/footer.php'; ?>