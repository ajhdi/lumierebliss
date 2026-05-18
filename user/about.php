<?php
include '../includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,400&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    :root {
        --pure-white: #ffffff;
        --studio-surface: #fdfbf7;
        --brand-gold: #c9a96e;
        --gold-light: #e8d5b0;
        --the-dark: #1a1a1a;
        --studio-mid: #2e2e2e;
        --muted-text: #8a8070;
        --border-subtle: rgba(201, 169, 110, 0.15);
        --lumiere-gradient-dark: linear-gradient(135deg, var(--the-dark) 0%, var(--studio-mid) 100%);
    }

    /* Core High-End Page Context Scaffolding */
    .luxe-about-context {
        background-color: var(--studio-surface);
        font-family: 'DM Sans', sans-serif;
        color: var(--the-dark);
        /* 18px Base Premium Readability Font-Scale Rules */
        font-size: 1.125rem;
        line-height: 1.75;
        letter-spacing: -0.01em;
        -webkit-font-smoothing: antialiased;
    }

    /* Structural Typography Overhauls matching Brand Identity Rules */
    .luxe-about-context h1.hero-title {
        font-family: 'Cormorant Garamond', serif;
        font-weight: 300 !important; /* Kept light for elegant first impression theme */
        letter-spacing: -0.02em;
        color: var(--the-dark);
    }
    
    .luxe-about-context h2,
    .luxe-about-context h3,
    .luxe-about-context h4,
    .luxe-about-context h5,
    .serif-title {
        font-family: 'Cormorant Garamond', serif;
        font-weight: 600 !important; /* Post-hero headers elevated to bold 600 */
        letter-spacing: -0.01em;
        color: var(--the-dark);
    }

    /* Accent Signatures */
    .section-tagline {
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 5px;
        color: var(--brand-gold);
        font-size: 0.8rem;
    }

    /* Calibrated Space Buffer inside Content Section Layout */
    .about-hero-wrapper {
        padding-top: 8rem !important;
        padding-bottom: 5rem !important;
        background-color: var(--pure-white);
        border-bottom: 1px solid var(--border-subtle);
    }

    /* Minimalist Metrics Frame Component */
    .stat-card {
        border: none;
        background: transparent;
        padding: 1.5rem 1rem;
        transition: transform 0.4s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-4px);
    }

    .stat-number {
        font-family: 'Cormorant Garamond', serif;
        font-size: 3.5rem;
        font-weight: 400;
        line-height: 1;
        color: var(--the-dark);
        display: block;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-family: 'DM Sans', sans-serif;
        color: var(--brand-gold);
        text-transform: uppercase;
        font-weight: 700;
        font-size: 0.72rem;
        letter-spacing: 2px;
    }

    /* Luxury Monolithic Mission Section Panel */
    .mission-box {
        background: var(--lumiere-gradient-dark);
        color: var(--pure-white);
        border-radius: 0px !important; /* True luxury utilizes sharp, intentional geometry */
        padding: 5rem 4rem;
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(201, 169, 110, 0.2);
    }

    .mission-box::after {
        content: '“';
        position: absolute;
        top: -40px;
        right: 30px;
        font-size: 22rem;
        color: rgba(201, 169, 110, 0.04);
        font-family: 'Cormorant Garamond', serif;
        line-height: 1;
    }

    .mission-box h2 {
        color: var(--gold-light) !important;
    }

    /* Premium Features / Values Grid Components */
    .feature-item-wrapper {
        padding: 1rem;
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    .feature-item-wrapper:hover {
        transform: translateY(-5px);
    }

    .feature-icon {
        width: 60px;
        height: 60px;
        background: var(--pure-white);
        color: var(--brand-gold);
        border: 1px solid var(--border-subtle);
        border-radius: 0px !important; /* Sharp, boutique minimalist aesthetic */
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        font-size: 1.5rem;
        box-shadow: 0 8px 25px rgba(138, 128, 112, 0.05);
    }

    .feature-item-wrapper h5 {
        font-size: 1.6rem;
        margin-bottom: 0.75rem;
    }

    .feature-item-wrapper p {
        font-size: 1rem;
        color: var(--muted-text);
        line-height: 1.6;
    }

    /* Elite Action Interaction Buttons */
    .btn-luxe-cta {
        background: var(--the-dark);
        color: var(--pure-white);
        border: 1px solid var(--the-dark);
        border-radius: 0px !important;
        padding: 16px 45px;
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        display: inline-block;
        text-decoration: none;
        position: relative;
        z-index: 1;
        overflow: hidden;
    }

    .btn-luxe-cta::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: var(--lumiere-gradient-dark);
        z-index: -1;
        opacity: 1;
        transition: opacity 0.4s ease;
    }

    .btn-luxe-cta:hover {
        background: transparent;
        color: var(--the-dark);
        border-color: var(--the-dark);
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(26, 26, 26, 0.08);
    }
    
    .btn-luxe-cta:hover::before {
        opacity: 0;
    }
</style>

<div class="luxe-about-context">

    <section class="about-hero-wrapper">
        <div class="container text-center" data-aos="fade-up">
            <span class="section-tagline mb-3 d-block">Our Essence</span>
            <h1 class="display-3 hero-title mb-4">Lumiére & <span style="font-style: italic;">Bliss</span></h1>
            <div class="mx-auto my-4" style="width: 60px; height: 1px; background: var(--brand-gold); opacity: 0.7;"></div>
            <p class="text-muted mx-auto mb-0" style="max-width: 720px; font-size: 1.25rem; line-height: 1.8;">
                Born from a holistic vision of absolute interior serenity, we operate a private sanctuary where the delicate art of physical healing flawlessly converges with contemporary programmatic luxury.
            </p>
        </div>
    </section>

    <section class="py-5" style="background-color: var(--pure-white);">
        <div class="container">
            <div class="row text-center g-4">
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-card">
                        <span class="stat-number">10+</span>
                        <span class="stat-label">Master Specialists</span>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-card">
                        <span class="stat-number">5k+</span>
                        <span class="stat-label">Restorative Sessions</span>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-card">
                        <span class="stat-number">15+</span>
                        <span class="stat-label">Curated Treatments</span>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-card">
                        <span class="stat-number">4.9</span>
                        <span class="stat-label">Guest Rating Score</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 my-4">
        <div class="container">
            <div class="mission-box shadow-sm" data-aos="fade-up">
                <div class="row align-items-center">
                    <div class="col-lg-8 position-relative" style="z-index: 2;">
                        <span class="section-tagline mb-3 d-block" style="color: var(--gold-light);">The Paradigm</span>
                        <h2 class="display-5 mb-4">Our Mission</h2>
                        <p class="mb-0 opacity-75" style="font-size: 1.2rem; line-height: 1.8; font-weight: 300;">
                            At Lumiére & Bliss, our permanent goal is to radically redefine the modern spa trajectory by integrating premium architectural service strategy with deeply hyper-personalized wellness. We confidently believe that systematic relaxation is not a negotiable luxury, but a mandatory requirement for a highly balanced life.
                        </p>
                    </div>
                    <div class="col-lg-4 text-center d-none d-lg-block position-relative" style="z-index: 2;">
                        <i class="bi bi-shield-check" style="font-size: 7.5rem; color: var(--brand-gold); opacity: 0.85;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row g-5 py-3">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-item-wrapper">
                        <div class="feature-icon"><i class="bi bi-stars"></i></div>
                        <h5>Premium Quality</h5>
                        <p>We source and implement only certified organic, high-grade botanical oils and formulation products uniformly across every therapy session.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-item-wrapper">
                        <div class="feature-icon"><i class="bi bi-person-heart"></i></div>
                        <h5>Expert Care</h5>
                        <p>Our operational practitioners undergo constant, rigorous medical and anatomical certifications to consistently guarantee the highest margins of physical recovery.</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-item-wrapper">
                        <div class="feature-icon"><i class="bi bi-calendar-check"></i></div>
                        <h5>Seamless Booking</h5>
                        <p>Our completely customized digital management interface infrastructure makes planning your next therapeutic window instantaneous and frictionless.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 text-center bg-white border-top" style="border-color: var(--border-subtle) !important;">
        <div class="container py-4">
            <span class="section-tagline mb-3 d-block">Sanctuary Entrance</span>
            <h3 class="display-5 mb-4">Ready to start your journey?</h3>
            <div class="mt-4">
                <a href="treatment.php" class="btn-luxe-cta">Explore Treatments</a>
            </div>
        </div>
    </section>

</div>

<?php include '../includes/footer.php'; ?>