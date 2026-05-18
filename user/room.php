<?php
include '../includes/header.php';
require_once '../config/db.php';

// Fetch only available rooms - Functional logic kept strictly intact
$stmt = $pdo->query("SELECT * FROM rooms WHERE status = 'available' ORDER BY room_name ASC");
$rooms = $stmt->fetchAll();
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
        
        /* Lumiére Metallic Glow Gradient Vectors */
        --lumiere-glow-gradient: linear-gradient(135deg, var(--the-dark) 0%, var(--studio-mid) 100%);
        --lumiere-gold-shimmer: linear-gradient(90deg, #c9a96e 0%, #e8d5b0 50%, #c9a96e 100%);
    }

    /* Core High-End Layout Overhaul */
    .luxe-room-context {
        background-color: var(--studio-surface);
        font-family: 'DM Sans', sans-serif;
        color: var(--the-dark);
        /* Upgrade baseline text scale to 18px per design specification rules */
        font-size: 1.125rem; 
        line-height: 1.7;
        letter-spacing: -0.01em;
        -webkit-font-smoothing: antialiased;
    }

    /* Calibrated Space Buffer below Fixed Navigation Header */
    .sanctuary-header-wrapper {
        padding-top: 6.5rem !important; 
        margin-top: 1rem !important;    
        margin-bottom: 4.5rem !important;
    }

    /* Structural Typography Alignment */
    .luxe-room-context h1 {
        font-family: 'Cormorant Garamond', serif;
        color: var(--the-dark);
        font-weight: 300; /* Regular/Light for primary impression title */
        letter-spacing: -0.02em;
    }

    /* Section Boldness Rule Shift: Post-Hero Heading Weights Elevated to 600 */
    .luxe-room-context h4, 
    .serif-title {
        font-family: 'Cormorant Garamond', serif;
        color: var(--the-dark);
        font-weight: 600 !important; 
        letter-spacing: -0.01em;
    }

    /* Immersive Accent Signatures */
    .section-tagline {
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 5px;
        color: var(--brand-gold);
        font-size: 0.8rem;
    }

    /* Luxurious Asymmetric Suite Cards Layout */
    .room-card {
        border: 1px solid var(--border-subtle);
        border-radius: 16px !important;
        background: var(--pure-white);
        transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(138, 128, 112, 0.04) !important;
    }

    .room-card:hover {
        transform: translateY(-8px);
        border-color: rgba(201, 169, 110, 0.35);
        box-shadow: 0 30px 60px rgba(26, 22, 17, 0.08) !important;
    }

    /* Immersive Architectural Media Frame Container */
    .room-visual-wrapper {
        height: 280px;
        background: #faf8f5;
        position: relative;
        overflow: hidden;
    }

    .room-image-render {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 1.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .room-card:hover .room-image-render {
        transform: scale(1.06);
    }

    /* Modern Geometric Shimmer Overlay Layer over Image Container */
    .room-visual-wrapper::after {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(to bottom, rgba(26,26,26,0) 60%, rgba(26,26,26,0.2) 100%);
        pointer-events: none;
    }

    /* Premium Placeholder Canvas Block Frame when no asset image database entry exists */
    .room-fallback-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: var(--brand-gold);
        background: linear-gradient(135deg, var(--studio-surface) 0%, #f3ede0 100%);
        gap: 12px;
    }
    
    .room-fallback-placeholder i {
        font-size: 2.5rem;
        font-weight: 300;
    }

    /* Custom Refined Metadata Badges */
    .room-type-badge {
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2.5px;
        color: var(--brand-gold);
        font-size: 0.72rem;
        display: inline-block;
    }

    .room-card h4 {
        font-size: 1.95rem;
        margin-top: 4px;
        margin-bottom: 14px;
        color: var(--the-dark);
    }

    .room-description-text {
        font-size: 1rem; /* Standardized for enhanced accessibility readable scale */
        color: var(--muted-text);
        line-height: 1.7;
    }
    
    .header-intro-p {
        font-size: 1.125rem;
        color: var(--muted-text);
    }

    /* Luxurious Lumiére Interactive Action Control Components */
    .btn-view-room {
        background: var(--the-dark);
        color: var(--pure-white);
        border: 1px solid var(--the-dark);
        border-radius: 8px; 
        padding: 16px 32px;
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        text-align: center;
        position: relative;
        z-index: 1;
        overflow: hidden;
    }

    .btn-view-room::before {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: var(--lumiere-glow-gradient);
        z-index: -1;
        opacity: 1;
        transition: opacity 0.4s ease;
    }

    .btn-view-room:hover {
        color: var(--the-dark);
        background: transparent;
        border-color: var(--the-dark);
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(26, 26, 26, 0.1);
    }
    
    .btn-view-room:hover::before {
        opacity: 0;
    }

    /* Minimalist High-Contrast Empty Curation Fallback State Box */
    .fallback-card-box {
        border: 1px dashed var(--brand-gold);
        background: var(--pure-white);
        border-radius: 20px;
        padding: 3.5rem 2rem !important;
        box-shadow: 0 10px 30px rgba(201, 169, 110, 0.03);
    }
    
    .fallback-card-box i {
        background: linear-gradient(135deg, var(--brand-gold), var(--gold-light));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        display: inline-block;
    }
</style>

<div class="luxe-room-context">
    <div class="container py-5">
        
        <div class="text-center sanctuary-header-wrapper">
            <span class="section-tagline mb-3 d-block">Lumiére Curations</span>
            <h1 class="display-4">Our Private <span style="font-weight: 500; font-style: italic;">Suites</span></h1>
            <div class="mx-auto my-4" style="width: 60px; height: 1px; background: var(--brand-gold); opacity: 0.7;"></div>
            <p class="header-intro-p mx-auto" style="max-width: 580px;">Explore our tranquil spaces designed for complete disconnect, absolute privacy, and structured restoration.</p>
        </div>

        <div class="row g-5 justify-content-center">
            <?php if ($rooms): ?>
                <?php foreach ($rooms as $r): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card room-card h-100">
                            
                            <div class="room-visual-wrapper">
                                <?php if (!empty($r['room_image']) && file_exists('../assets/img/rooms/' . $r['room_image'])): ?>
                                    <img src="../assets/img/rooms/<?= htmlspecialchars($r['room_image']) ?>" class="room-image-render" alt="<?= htmlspecialchars($r['room_name']) ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="room-fallback-placeholder">
                                        <i class="bi bi-door-open-fill"></i>
                                        <span style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 2px; opacity: 0.8;">Lumiére Space Frame</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body p-4 d-flex flex-column">
                                <div class="mb-1">
                                    <span class="room-type-badge"><?= htmlspecialchars($r['room_type'] ?? 'Standard Room Arrangement') ?></span>
                                </div>
                                
                                <h4><?= htmlspecialchars($r['room_name']) ?></h4>
                                
                                <p class="room-description-text mb-4">
                                    <?= htmlspecialchars($r['description'] ?? 'Optimized configuration for a quiet, immersive, and serene therapeutic experience.') ?>
                                </p>
                                
                                <div class="mt-auto pt-4 border-top" style="border-color: rgba(201, 169, 110, 0.1) !important;">
                                    <a href="appointment.php?rid=<?= $r['room_id'] ?>" class="btn btn-view-room">
                                        <span>Book This Suite</span>
                                        <i class="bi bi-arrow-right style="font-size: 0.95rem;"></i>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-md-8 text-center pt-2 pb-5">
                    <div class="fallback-card-box">
                        <i class="bi bi-moon-stars display-3 mb-4"></i>
                        <h4 class="serif-title mb-3" style="font-size: 2.2rem;">Sanctuary At Capacity</h4>
                        <p class="room-description-text mx-auto mb-0" style="max-width: 480px;">All suites are currently hosting active rituals or undergoing scheduled maintenance curation. Please verify alternatives later or contact the concierge.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>