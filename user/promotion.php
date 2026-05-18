<?php
include '../includes/header.php';
require_once '../config/db.php';

$is_logged_in = isset($_SESSION['user_id']);
$account_type = $_SESSION['account_type'] ?? 'guest';
$user_birthdate = $_SESSION['birthdate'] ?? null;

// Fetch active and archived promotions separately
$promotions = [];
$archived_promotions = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM promotions WHERE is_archived = 0 ORDER BY promo_id DESC");
    $stmt->execute();
    $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt2 = $pdo->prepare("SELECT * FROM promotions WHERE is_archived = 1 ORDER BY promo_id DESC");
    $stmt2->execute();
    $archived_promotions = $stmt2->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silent fallback
}

// Helper: Is today a weekend (Fri/Sat/Sun)?
$today_dow = date('N');
$is_weekend = in_array($today_dow, [5, 6, 7]);

// Helper: Is user's birth month this month?
$is_birth_month = false;
if ($is_logged_in && $user_birthdate) {
    $is_birth_month = (date('m', strtotime($user_birthdate)) === date('m'));
}
?>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,400&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    :root {
        --pure-white: #ffffff;
        --studio-surface: #fdfbf7;
        --brand-gold: #c9a96e;
        --gold-light: #e8d5b0;
        --gold-deep: #b39257;
        --the-dark: #111111;
        --studio-mid: #1a1a1a;
        --muted-text: #8a8070;
        --border-subtle: rgba(201, 169, 110, 0.2);
    }

    .luxe-promo-context {
        background-color: var(--studio-surface);
        font-family: 'DM Sans', sans-serif;
        font-size: 16px;
        color: var(--the-dark);
        line-height: 1.6;
    }

    .luxe-promo-context h1,
    .luxe-promo-context h2,
    .luxe-promo-context h3,
    .luxe-promo-context h4,
    .serif-title {
        font-family: 'Cormorant Garamond', serif;
        color: var(--the-dark);
        font-weight: 400;
        letter-spacing: -0.01em;
    }

    .promo-hero-luxe {
    position: relative;
    min-height: 88vh;
    display: flex;
    align-items: flex-end;
    justify-content: flex-start;
    overflow: hidden;
    background: var(--the-dark);
    border-bottom: 1px solid var(--brand-gold);
}

.promo-hero-luxe__bg {
    position: absolute;
    inset: 0;
    background: url('../assets/img/spa-bg.jpg') center/cover no-repeat;
    opacity: 0.45;
    transform: scale(1.04);
    transition: transform 8s ease;
}
.promo-hero-luxe:hover .promo-hero-luxe__bg { transform: scale(1); }

/* Vertical gold accent line */
.promo-hero-luxe::before {
    content: '';
    position: absolute;
    left: 80px;
    top: 0; bottom: 0;
    width: 1px;
    background: linear-gradient(to bottom, transparent, var(--brand-gold), transparent);
    opacity: 0.6;
    z-index: 1;
}

/* Ghosted display text */
.promo-hero-luxe::after {
    content: 'OFFERS';
    position: absolute;
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(5rem, 18vw, 17rem);
    font-weight: 600;
    color: rgba(255,255,255,0.025);
    bottom: -0.1em; right: -0.04em;
    white-space: nowrap;
    pointer-events: none;
    letter-spacing: -0.02em;
    z-index: 1;
}

.promo-hero-luxe__content {
    position: relative;
    z-index: 2;
    padding: 0 80px 80px;
    max-width: 680px;
}

.hero-tagline {
    font-family: 'DM Sans', sans-serif;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 5px;
    color: var(--brand-gold);
    font-size: 0.72rem;
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}
.hero-tagline::before {
    content: '';
    display: block;
    width: 40px; height: 1px;
    background: var(--brand-gold);
}

.promo-hero-luxe__title {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 300;
    font-size: clamp(3rem, 6vw, 5.5rem);
    color: var(--pure-white);
    line-height: 1.05;
    margin-bottom: 28px;
}
.promo-hero-luxe__title em {
    font-style: italic;
    color: var(--gold-light);
}

.promo-hero-luxe__sub {
    font-size: 1rem;
    color: rgba(255,255,255,0.55);
    font-weight: 300;
    max-width: 420px;
    letter-spacing: 0.01em;
}

.scroll-cue {
    position: absolute;
    bottom: 40px;
    right: 80px;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: rgba(255,255,255,0.4);
    font-size: 0.65rem;
    letter-spacing: 3px;
    text-transform: uppercase;
}
.scroll-cue__line {
    width: 1px;
    height: 60px;
    background: linear-gradient(to bottom, var(--brand-gold), transparent);
    animation: scrollPulse 2s ease-in-out infinite;
}
@keyframes scrollPulse {
    0%, 100% { opacity: 0.4; transform: scaleY(1); }
    50%       { opacity: 1;   transform: scaleY(0.7); }
}

@media (max-width: 1024px) {
    .promo-hero-luxe__content { padding: 0 32px 64px; }
    .promo-hero-luxe::before  { left: 32px; }
    .scroll-cue               { right: 32px; }
}
@media (max-width: 768px) {
    .promo-hero-luxe__content { padding: 0 20px 52px; }
    .promo-hero-luxe::before  { display: none; }
    .scroll-cue               { right: 20px; }
}

    .card-editorial-light {
    background: var(--pure-white);
    border: 1px solid rgba(201, 169, 110, 0.25);
    border-radius: 0 !important;
    box-shadow: none !important;
    transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    padding: 52px 48px;
}

.card-editorial-light .serif-title {
    color: #111111;
    font-size: 2.4rem;
    font-weight: 300;
    margin-bottom: 36px;
    padding-bottom: 24px;
    border-bottom: 1px solid rgba(201, 169, 110, 0.25);
}

.card-editorial-light .feature-item {
    color: #8c8273;
    font-size: 0.95rem;
    margin-bottom: 20px;
    padding-left: 16px;
    border-left: 2px solid rgba(201, 169, 110, 0.3);
    line-height: 1.5;
}

.card-editorial-light .btn-link-editorial {
    font-family: 'DM Sans', sans-serif;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: #111111;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-top: 44px;
    transition: color 0.3s ease, gap 0.3s ease;
}
.card-editorial-light .btn-link-editorial::after {
    content: '';
    display: block;
    width: 28px; height: 1px;
    background: #111111;
    transition: width 0.3s ease;
}
.card-editorial-light .btn-link-editorial:hover { color: var(--brand-gold); gap: 14px; }
.card-editorial-light .btn-link-editorial:hover::after { background: var(--brand-gold); width: 36px; }

.card-editorial-dark {
    background: #111111;
    border: 1px solid rgba(201, 169, 110, 0.2);
    border-radius: 0 !important;
    box-shadow: none !important;
    transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    padding: 52px 48px;
}

.card-editorial-dark .serif-title {
    color: var(--pure-white);
    font-size: 2.4rem;
    font-weight: 300;
    margin-bottom: 36px;
    padding-bottom: 24px;
    border-bottom: 1px solid rgba(201, 169, 110, 0.2);
}

.card-editorial-dark .feature-item {
    color: #a39887;
    font-size: 0.95rem;
    margin-bottom: 20px;
    padding-left: 16px;
    border-left: 2px solid rgba(201, 169, 110, 0.35);
    line-height: 1.5;
}

.card-editorial-dark .btn-link-editorial {
    font-family: 'DM Sans', sans-serif;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: var(--brand-gold);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-top: 44px;
    transition: color 0.3s ease, gap 0.3s ease;
}
.card-editorial-dark .btn-link-editorial::after {
    content: '';
    display: block;
    width: 28px; height: 1px;
    background: var(--brand-gold);
    transition: width 0.3s ease, background 0.3s ease;
}
.card-editorial-dark .btn-link-editorial:hover { color: var(--pure-white); gap: 14px; }
.card-editorial-dark .btn-link-editorial:hover::after { background: var(--pure-white); width: 36px; }

.card-editorial-dark:hover, .card-editorial-light:hover { transform: translateY(-4px); }

    /* ---- Active promo card ---- */
    .promo-banner-card {
        background: var(--pure-white);
        border: 1px solid rgba(201, 169, 110, 0.25);
        border-radius: 12px !important;
        box-shadow: 0 12px 45px rgba(26, 26, 26, 0.03);
        transition: transform 0.4s ease, box-shadow 0.4s ease;
    }
    .promo-banner-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 50px rgba(201, 169, 110, 0.08);
    }

    /* ---- Archived / expired promo card ---- */
    .promo-banner-card.is-archived {
        background: #f7f7f5;
        border: 1px solid #e0ddd8;
        box-shadow: none;
        opacity: 0.72;
        filter: grayscale(35%);
        pointer-events: none; /* disable hover & clicks on the whole card */
    }
    .promo-banner-card.is-archived:hover { transform: none; box-shadow: none; }
    .promo-banner-card.is-archived h4,
    .promo-banner-card.is-archived .promo-tagline-italic,
    .promo-banner-card.is-archived .meta-spec-item,
    .promo-banner-card.is-archived .services-heading,
    .promo-banner-card.is-archived .text-muted { color: #b0aca5 !important; }
    .promo-banner-card.is-archived .price-active { color: #b0aca5; }
    .promo-banner-card.is-archived .price-savings-tag { color: #c5bfb7; }

    .badge-expired {
        display: inline-block;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        padding: 3px 10px;
        border-radius: 20px;
        background: #e5e2dd;
        color: #7a7469;
        margin-bottom: 10px;
    }

    .archived-section-label {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 3px;
        color: #b0aca5;
    }

    .promo-category-badge {
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--brand-gold);
        font-size: 0.75rem;
    }

    .promo-banner-card h4 { font-size: 2.4rem; color: var(--the-dark); }
    .promo-tagline-italic { font-family: 'Cormorant Garamond', serif; font-style: italic; font-size: 1.25rem; color: var(--muted-text); }
    .services-heading { font-family: 'DM Sans', sans-serif; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: var(--the-dark); }
    .meta-spec-item { font-size: 0.9rem; color: var(--the-dark); font-weight: 500; }
    .meta-spec-item i { color: var(--brand-gold); font-size: 1rem; }
    .price-strike { text-decoration: line-through; color: #b5ac9e; font-size: 1.25rem; font-weight: 300; }
    .price-active { color: var(--the-dark); font-size: 2.2rem; font-weight: 400; font-family: 'Cormorant Garamond', serif; margin-left: 8px; }
    .price-savings-tag { font-family: 'DM Sans', sans-serif; color: var(--brand-gold); font-size: 0.9rem; font-weight: 500; }
    .terms-onsite-notice { font-size: 0.78rem; font-style: italic; color: var(--muted-text); line-height: 1.5; }

    .btn-luxe-action {
        background: var(--the-dark); color: var(--pure-white);
        border: 1px solid var(--the-dark); border-radius: 0px;
        font-family: 'DM Sans', sans-serif; font-size: 0.8rem;
        font-weight: 500; text-transform: uppercase; letter-spacing: 2px;
        padding: 12px 35px; transition: all 0.3s ease;
        text-decoration: none; display: block; text-align: center;
    }
    .btn-luxe-action:hover { background: transparent; color: var(--the-dark); border-color: var(--the-dark); }
    .btn-luxe-action.disabled-promo {
        background: #d6d3ce; border-color: #d6d3ce;
        color: #9a9590; cursor: not-allowed; pointer-events: none;
    }

    .promo-notice-tag {
        display: inline-block;
        font-family: 'DM Sans', sans-serif; font-size: 0.72rem;
        font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px;
        padding: 3px 10px; border-radius: 20px; margin-bottom: 10px;
    }
    .notice-birthday { background: #fff3cd; color: #856404; }
    .notice-weekend  { background: #e8f4fd; color: #1a6496; }
    .notice-member   { background: #f0fdf4; color: #15803d; }
    .notice-login    { background: #fef3c7; color: #92400e; }
</style>

<div class="luxe-promo-context">

    <section class="promo-hero-luxe mb-5">
    <div class="promo-hero-luxe__bg"></div>
    <div class="promo-hero-luxe__content">
        <p class="hero-tagline">Lumiére Bliss</p>
        <h1 class="promo-hero-luxe__title">Elevate Your<br><em>Experience</em></h1>
        <p class="promo-hero-luxe__sub">Choose a framework tailored to your rhythm and lifestyle of continuous restoration.</p>
    </div>
    <div class="scroll-cue">
        <div class="scroll-cue__line"></div>
        <span>Scroll</span>
    </div>
</section>

    <div class="container my-5 pb-5">
        <!-- Tier Header -->
<div class="text-center mb-5 pb-2">
    <span style="font-family:'DM Sans',sans-serif; font-size:0.72rem; font-weight:500; letter-spacing:5px; text-transform:uppercase; color:var(--brand-gold); display:inline-flex; align-items:center; gap:14px;">
        <span style="display:block; width:40px; height:1px; background:var(--brand-gold);"></span>
        Exclusivity
    </span>
    <h2 style="font-family:'Cormorant Garamond',serif; font-weight:300; font-size:clamp(2rem,4vw,3rem); margin-top:16px; margin-bottom:16px;">Elevate Your <em style="font-style:italic; color:var(--brand-gold);">Experience</em></h2>
    <p style="font-size:0.95rem; color:var(--muted-text); max-width:480px; margin:0 auto;">Avail the <strong style="color:var(--the-dark); font-weight:600;">Lumiére Circle</strong> membership onsite and unlock a world of exclusive privileges curated for devoted members.</p>
    <div style="width:40px; height:1px; background:var(--brand-gold); margin:28px auto 0;"></div>
</div>

<!-- Tier Cards -->
<div class="row g-4 justify-content-center mb-5 pb-5">
            <div class="col-md-6 col-lg-5">
    <div class="card h-100 card-editorial-light">
        <div class="card-body p-0 d-flex flex-column">
            <h2 class="serif-title">The Guest</h2>
            <div class="flex-grow-1">
                <div class="feature-item">Access to Standard Rooms</div>
                <div class="feature-item">Individual Treatment Options</div>
                <div class="feature-item">Standard Scheduling</div>
            </div>
            <div>
                <?php if($account_type === 'guest'): ?>
                    <span class="btn-link-editorial opacity-50" style="cursor: default;">Current Selection</span>
                <?php else: ?>
                   
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="col-md-6 col-lg-5">
    <div class="card h-100 card-editorial-dark">
        <div class="card-body p-0 d-flex flex-column">
            <h2 class="serif-title">The Lumiére Circle</h2>
            <div class="flex-grow-1">
                <div class="feature-item">Complimentary Semi-Luxury Room Upgrade</div>
                <div class="feature-item">Elevated Member Booking Privileges</div>
                <div class="feature-item">Personalized Spa Experience</div>
                <div class="feature-item">Access to Member-Exclusive Promotions</div>
            </div>
            <div>
                <?php if($account_type === 'member'): ?>
                    <a href="home.php" class="btn-link-editorial">Manage Plan</a>
                <?php else: ?>
                    
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
        </div>

        <!-- Promotions Section -->
        <div class="row pt-5" style="border-top: 1px solid var(--border-subtle);">
            <div class="col-md-11 mx-auto">
                <div class="text-center mb-5">
    <span class="hero-tagline" style="color: var(--brand-gold); display: flex; justify-content: center;">Curated Selections</span>
    <h3 class="display-6 mt-1" style="font-size: 2.2rem; font-weight: 300; font-family: 'Cormorant Garamond', serif;">Exclusive Offers & Curation Banners</h3>
</div>

                <div class="d-flex flex-column gap-5">

                    <?php
                    // ---- Reusable helper to build promo card variables ----
                    function buildPromoVars($promo, $is_logged_in, $is_birth_month, $is_weekend, $account_type) {
                        $savings      = $promo['original_price'] - $promo['price_now'];
                        $promo_name   = strtolower($promo['promo_name']);
                        $btn_text     = 'Book Promotion';
                        $btn_href     = 'appointment.php?promo_id=' . $promo['promo_id'];
                        $btn_disabled = false;
                        $notice_html  = '';
                        $badge_label  = 'Limited-Time Offer';
                        $terms_notice = '* Terms apply. Digital or onsite documentation verification may be required upon check-in.';

                        if (str_contains($promo_name, 'birthday')) {
                            $badge_label = 'Birthday Exclusive';
                            if (!$is_logged_in) {
                                $btn_text    = 'Sign In to Book';
                                $btn_href    = 'signin.php?redirect=promotion.php';
                                $notice_html = '<span class="promo-notice-tag notice-birthday"><i class="bi bi-cake2 me-1"></i>Valid during your birth month</span>';
                            } elseif (!$is_birth_month) {
                                $btn_text     = 'Not Your Birth Month';
                                $btn_disabled = true;
                            } else {
                                $notice_html = '<span class="promo-notice-tag notice-birthday"><i class="bi bi-cake2 me-1"></i>Happy birth month! You qualify.</span>';
                            }
                            $terms_notice = '* Requires validation of birthdate via account profile or physical ID onsite.';

                        } elseif (str_contains($promo_name, 'weekend')) {
                            $badge_label = 'Weekend Deal';
                            if (!$is_weekend) {
                                $btn_text     = 'Available Fri – Sun Only';
                                $btn_disabled = true;
                            } else {
                                $notice_html = '<span class="promo-notice-tag notice-weekend"><i class="bi bi-calendar-week me-1"></i>Weekend booking — you qualify today!</span>';
                            }
                            $terms_notice = '* Exclusively available for Friday, Saturday, and Sunday bookings.';

                        } elseif (str_contains($promo_name, 'relax') || str_contains($promo_name, 'glow')) {
                            $badge_label = 'Seasonal Offer';
                            if (!$is_logged_in) {
                                $btn_text    = 'Sign In to Book';
                                $btn_href    = 'signin.php?redirect=promotion.php';
                                $notice_html = '<span class="promo-notice-tag notice-login"><i class="bi bi-lock me-1"></i>Sign in to access this offer</span>';
                            } else {
                            }

                        } elseif (str_contains($promo_name, 'couple')) {
                            $badge_label = 'Ongoing Offer';
                            $terms_notice = $account_type === 'member'
                                ? '* Lumiére Circle members: this promotion does not count against your 2 free semi-luxury room upgrade allocation.'
                                : '* Available to all guests. Premium members retain their seasonal room upgrade allocation separately.';
                        }

                        return compact('savings', 'btn_text', 'btn_href', 'btn_disabled', 'notice_html', 'badge_label', 'terms_notice');
                    }
                    ?>

                    <?php if (!empty($promotions)): ?>
                        <?php foreach ($promotions as $promo):
                            extract(buildPromoVars($promo, $is_logged_in, $is_birth_month, $is_weekend, $account_type));
                        ?>
                        <div class="card promo-banner-card border-0 p-4 p-md-5">
                            <div class="row align-items-center g-4">
                                <div class="col-lg-8 col-md-7">
                                    <?= $notice_html ?>
                                    <span class="promo-category-badge d-block"><?= htmlspecialchars($badge_label) ?></span>
                                    <h4 class="mt-1 mb-2"><?= htmlspecialchars($promo['promo_name']) ?></h4>
                                    <p class="promo-tagline-italic mb-4">"<?= htmlspecialchars($promo['tagline']) ?>"</p>

                                    <div class="mb-4">
                                        <span class="services-heading d-block mb-2">Included Services:</span>
                                        <span class="text-muted" style="font-size: 1.05rem;"><?= nl2br(htmlspecialchars($promo['included_service'])) ?></span>
                                    </div>

                                    <div class="d-flex flex-wrap gap-4 border-top pt-3" style="border-color: rgba(201, 169, 110, 0.15) !important;">
                                        <div class="meta-spec-item"><i class="bi bi-clock me-2"></i><?= htmlspecialchars($promo['duration_minutes']) ?> Minutes</div>
                                        <div class="meta-spec-item"><i class="bi bi-calendar3 me-2"></i><?= htmlspecialchars($promo['valid_dates']) ?></div>
                                    </div>
                                </div>

                                <div class="col-lg-4 col-md-5 text-md-end d-flex flex-column align-items-md-end justify-content-center ps-md-4" style="border-left: 1px solid rgba(201, 169, 110, 0.15);">
                                    <div class="mb-4 text-start text-md-end">
                                        <div class="d-flex align-items-baseline justify-content-md-end">
                                            <span class="price-strike">₱<?= number_format($promo['original_price'], 2) ?></span>
                                            <span class="price-active">₱<?= number_format($promo['price_now'], 2) ?></span>
                                        </div>
                                        <div class="price-savings-tag mt-1">(Save ₱<?= number_format($savings, 2) ?>)</div>
                                    </div>
                                    <div class="mt-2 w-100">
                                        <a href="<?= $btn_disabled ? '#' : htmlspecialchars($btn_href) ?>"
                                           class="btn-luxe-action mb-3 <?= $btn_disabled ? 'disabled-promo' : '' ?>">
                                            <?= htmlspecialchars($btn_text) ?>
                                        </a>
                                        <p class="terms-onsite-notice mb-0"><?= htmlspecialchars($terms_notice) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-tag" style="font-size: 2.5rem; color: var(--brand-gold); opacity: 0.4;"></i>
                            <p class="mt-3" style="color: var(--muted-text); font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; font-style: italic;">No active promotions at the moment. Check back soon.</p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($archived_promotions)): ?>

                        <!-- Expired / Archived Section Divider -->
                        <div class="d-flex align-items-center gap-3 mt-3">
                            <div style="flex:1; height:1px; background:#e0ddd8;"></div>
                            <span class="archived-section-label">Past Promotions</span>
                            <div style="flex:1; height:1px; background:#e0ddd8;"></div>
                        </div>

                        <?php foreach ($archived_promotions as $promo):
                            extract(buildPromoVars($promo, $is_logged_in, $is_birth_month, $is_weekend, $account_type));
                        ?>
                        <div class="card promo-banner-card is-archived border-0 p-4 p-md-5">
                            <div class="row align-items-center g-4">
                                <div class="col-lg-8 col-md-7">
                                    <span class="badge-expired"><i class="bi bi-slash-circle me-1"></i>Expired</span>
                                    <span class="promo-category-badge d-block" style="color:#b0aca5;"><?= htmlspecialchars($badge_label) ?></span>
                                    <h4 class="mt-1 mb-2"><?= htmlspecialchars($promo['promo_name']) ?></h4>
                                    <p class="promo-tagline-italic mb-4">"<?= htmlspecialchars($promo['tagline']) ?>"</p>

                                    <div class="mb-4">
                                        <span class="services-heading d-block mb-2">Included Services:</span>
                                        <span class="text-muted" style="font-size: 1.05rem;"><?= nl2br(htmlspecialchars($promo['included_service'])) ?></span>
                                    </div>

                                    <div class="d-flex flex-wrap gap-4 border-top pt-3" style="border-color: #e8e4df !important;">
                                        <div class="meta-spec-item"><i class="bi bi-clock me-2" style="color:#c5bfb7;"></i><?= htmlspecialchars($promo['duration_minutes']) ?> Minutes</div>
                                        <div class="meta-spec-item"><i class="bi bi-calendar3 me-2" style="color:#c5bfb7;"></i><?= htmlspecialchars($promo['valid_dates']) ?></div>
                                    </div>
                                </div>

                                <div class="col-lg-4 col-md-5 text-md-end d-flex flex-column align-items-md-end justify-content-center ps-md-4" style="border-left: 1px solid #e8e4df;">
                                    <div class="mb-4 text-start text-md-end">
                                        <div class="d-flex align-items-baseline justify-content-md-end">
                                            <span class="price-strike">₱<?= number_format($promo['original_price'], 2) ?></span>
                                            <span class="price-active" style="color:#b0aca5;">₱<?= number_format($promo['price_now'], 2) ?></span>
                                        </div>
                                        <div class="price-savings-tag mt-1" style="color:#c5bfb7;">(Save ₱<?= number_format($savings, 2) ?>)</div>
                                    </div>
                                    <div class="mt-2 w-100">
                                        <span class="btn-luxe-action disabled-promo mb-3">This Offer Has Ended</span>
                                        <p class="terms-onsite-notice mb-0" style="color:#c5bfb7;">* This promotion is no longer available for booking.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>