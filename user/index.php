<?php 
// Flag to indicate this is the guest landing page for header/footer logic
$is_guest = true; 
include '../includes/header.php'; 
?>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<style>
    :root {
        --cream: #fdfbf7;
        --warm-white: #f9f6f0;
        --gold: #c9a96e;
        --gold-light: #e8d5b0;
        --dark: #1a1a1a;
        --dark-soft: #2e2e2e;
        --muted: #8a8070;
        --border: #ede8df;
    }

    body { 
        font-family: 'DM Sans', sans-serif; 
        background-color: var(--cream); 
        color: var(--dark); 
        overflow-x: hidden; 
        font-size: 18px; 
    }

    section { padding: 100px 0; position: relative; }
    .split-section { padding: 0 !important; background-color: #ffffff; }
    
    h1, h2, h3, .brand-serif { 
        font-family: 'Cormorant Garamond', serif; 
    }

    section h2, section h3 {
        font-weight: 600 !important;
    }

    .hero-container {
        position: relative;
        height: 100vh;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        color: white;
        overflow: hidden;
        background: var(--dark);
    }

    .hero-container::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse 300px 300px at 20% 20%, rgba(201,169,110,0.15) 0%, transparent 70%),
            radial-gradient(ellipse 250px 250px at 80% 75%, rgba(201,169,110,0.1) 0%, transparent 70%);
        z-index: 2;
    }

    #bg-video {
        position: absolute;
        top: 50%; left: 50%;
        min-width: 100%; min-height: 100%;
        width: auto; height: auto;
        z-index: 1;
        transform: translate(-50%, -50%);
        object-fit: cover;
        opacity: 0.6;
    }

    .hero-overlay {
        position: absolute; top: 0; left: 0;
        height: 100%; width: 100%;
        background: rgba(26, 26, 26, 0.3);
        z-index: 2;
    }

    .hero-content { position: relative; z-index: 3; padding: 0 20px; }
    .hero-title { 
        font-size: clamp(2.5rem, 8vw, 5.5rem); 
        letter-spacing: -1px; 
        margin-bottom: 15px; 
        font-weight: 400; 
    }
    
    .hero-tagline { 
        letter-spacing: 5px; 
        font-size: 0.8rem; 
        text-transform: uppercase; 
        color: var(--gold-light);
        opacity: 0.9;
        font-weight: 500;
    }

    .concept-label { 
        color: var(--gold); 
        text-transform: uppercase; 
        letter-spacing: 0.25em; 
        font-weight: 700; 
        font-size: 12px;
    }

    .gold-divider { width: 50px; height: 1px; background: var(--gold); margin: 30px auto; }
    .short-dark-divider { width: 50px; height: 1px; background: var(--dark); margin: 0 auto; opacity: 0.3; }

    .custom-container { max-width: 1000px; margin: 0 auto; }
    .membership-brief {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 45px 40px;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.3s ease;
    }
    
    .membership-brief.active-tier { 
        background: var(--dark); 
        color: white; 
        box-shadow: 0 12px 30px rgba(0,0,0,0.15);
    }
    
    .membership-brief ul li { font-size: 15px; margin-bottom: 12px; color: var(--muted); }
    .active-tier ul li { color: rgba(255,255,255,0.7); }

    .brand-content-wrapper { max-width: 500px; padding: 80px 20px; }
    .brand-description { line-height: 1.8; font-size: 17px; font-weight: 400; color: var(--muted); }
    
    .btn-discover-serif {
        font-family: 'Cormorant Garamond', serif;
        font-style: italic;
        color: var(--dark);
        font-weight: 600;
        text-decoration: none;
        border-bottom: 1px solid var(--gold);
        font-size: 1.4rem;
        transition: 0.3s;
    }

    .btn-luxe {
        background: var(--dark);
        color: white;
        border-radius: 12px;
        padding: 16px 35px;
        font-size: 12px;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
    }

    .split-image-container { height: 100%; min-height: 650px; width: 100%; overflow: hidden; background: #f0f0f0; }
    .split-image-container img { height: 100%; width: 100%; object-fit: cover; display: block; }

    .btn-discover-std {
        border-bottom: 1px solid var(--dark);
        color: var(--dark);
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.1em;
        padding-bottom: 3px;
        display: inline-block;
    }

    @media (max-width: 991px) {
        .split-image-container { min-height: 400px; }
        .split-section .row { flex-direction: column-reverse; }
    }
</style>

<div class="hero-container">
    <video autoplay muted loop playsinline id="bg-video">
        <source src="<?php echo $base_url; ?>assets/video/homevideo.mp4" type="video/mp4">
    </video>
    <div class="hero-overlay"></div>
    <div class="hero-content" data-aos="fade-up">
        <h1 class="hero-title">Lumiére & Bliss</h1>
        <p class="hero-tagline">Where Healing Light Meets Therapeutic Bliss</p>
    </div>
</div>

<section data-aos="fade-up">
    <div class="container text-center" style="max-width: 850px;">
        <h6 class="concept-label mb-3">The Philosophy</h6>
        <h2 class="display-5 mb-4">A Sanctuary for the Spirit</h2>
        <div class="gold-divider"></div>
        <p class="brand-description mx-auto">Inspired by the profound stillness of luxury wellness sanctuaries, Lumiére & Bliss is a temple of therapeutic excellence.</p>
        <a href="signin.php" class="btn-discover-std mt-4">DISCOVER OUR STORY</a>
    </div>
</section>

<section class="exclusivity-section" style="background-color: var(--warm-white);" data-aos="fade-up">
    <div class="container custom-container">
        <div class="text-center mb-5">
            <h6 class="concept-label mb-2">Exclusivity</h6>
            <h2 class="h1 mb-2">Elevate Your Experience</h2>
            <p class="brand-description small">Memberships are managed exclusively onsite by our Lumiére administrators.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-md-5">
                <div class="membership-brief">
                    <div>
                        <h3 class="h3 mb-4">The Guest</h3>
                        <ul class="list-unstyled">
                            <li>Access to Standard Rooms</li>
                            <li>Individual Treatment Options</li>
                            <li>Standard Scheduling</li>
                        </ul>
                    </div>
                    <a href="signin.php" class="btn-discover-std">LEARN MORE</a>
                </div>
            </div>
            <div class="col-md-5">
                <div class="membership-brief active-tier">
                    <div>
                        <h3 class="h3 mb-4 text-white">The Lumiére Circle</h3>
                        <ul class="list-unstyled">
                            <li>2 Free Semi-Luxury Room Uses</li>
                            <li>Priority Therapist Selection</li>
                            <li>Exclusive Monthly Rituals</li>
                        </ul>
                    </div>
                    <a href="signin.php" class="btn-luxe btn-gold w-100 text-center">VIEW ALL OFFERS</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="split-section" data-aos="fade-up">
    <div class="container-fluid p-0">
        <div class="row g-0 align-items-center">
            <div class="col-lg-6 d-flex flex-column align-items-center justify-content-center text-center">
                <div class="brand-content-wrapper">
                    <h6 class="concept-label mb-3">Therapists</h6>
                    <h2 class="h2 mb-4">Professional Care</h2>
                    <div class="short-dark-divider mb-4"></div>
                    <p class="brand-description mb-5">
                        Our therapeutic excellence stems from the blending of rhythmic techniques with 
                        modern serenity.
                    </p>
                    <a href="signin.php" class="btn-discover-serif">Discover</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="split-image-container">
                    <img src="<?php echo $base_url; ?>assets/therapist.jpg" alt="Therapists">
                </div>
            </div>
        </div>
    </div>
</section>

<section class="split-section" data-aos="fade-up">
    <div class="container-fluid p-0">
        <div class="row g-0 align-items-center flex-row-reverse">
            <div class="col-lg-6 d-flex flex-column align-items-center justify-content-center text-center">
                <div class="brand-content-wrapper">
                    <h6 class="concept-label mb-3">Treatments</h6>
                    <h2 class="h2 mb-4">Signature Rituals</h2>
                    <div class="short-dark-divider mb-4"></div>
                    <p class="brand-description mb-5">
                        To recover inner harmony, Lumiére & Bliss offers treatments that include 
                        Signature proposals and rituals dedicated to couples.
                    </p>
                    <a href="signin.php" class="btn-discover-serif">Discover</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="split-image-container">
                   <img src="<?php echo $base_url; ?>assets/treatment.jpg" alt="Treatments">
                </div>
            </div>
        </div>
    </div>
</section>

<section class="split-section" data-aos="fade-up">
    <div class="container-fluid p-0">
        <div class="row g-0 align-items-center">
            <div class="col-lg-6 d-flex flex-column align-items-center justify-content-center text-center">
                <div class="brand-content-wrapper">
                    <h6 class="concept-label mb-3">Cosmetic</h6>
                    <h2 class="h2 mb-4">Natural Beauty</h2>
                    <div class="short-dark-divider mb-4"></div>
                    <p class="brand-description mb-5">
                        Formulations rich in natural ingredients and medicinal plants, helping the skin to 
                        breath and rediscovering its natural beauty.
                    </p>
                    <a href="signin.php" class="btn-discover-serif">Discover</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="split-image-container">
                   <img src="<?php echo $base_url; ?>assets/cosmetics.jpg" alt="Cosmetic">
                </div>
            </div>
        </div>
    </div>
</section>

<section class="split-section" data-aos="fade-up">
    <div class="container-fluid p-0">
        <div class="row g-0 align-items-center flex-row-reverse">
            <div class="col-lg-6 d-flex flex-column align-items-center justify-content-center text-center">
                <div class="brand-content-wrapper">
                    <h6 class="concept-label mb-3">Room Selection</h6>
                    <h2 class="h2 mb-4">Your Sanctuary</h2>
                    <div class="short-dark-divider mb-4"></div>
                    <p class="brand-description mb-5">
                        Choose your sanctuary. From standard tranquility to our Lumiére semi-luxury suites, 
                        each space is designed for complete thermal and acoustic bliss.
                    </p>
                    <a href="signin.php" class="btn-discover-serif">Discover</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="split-image-container">
                   <img src="<?php echo $base_url; ?>assets/rooms.jpg" alt="Room Selection">
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 1200, once: true });
</script>