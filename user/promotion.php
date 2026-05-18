<?php
include '../includes/header.php';
require_once '../config/db.php';

$is_logged_in = isset($_SESSION['user_id']);
$account_type = $_SESSION['account_type'] ?? 'guest';
$user_birthdate = $_SESSION['birthdate'] ?? null; // store user's birthdate in session on login

// Fetch all promotions from admin (table has no status column)
$promotions = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM promotions ORDER BY promo_id DESC");
    $stmt->execute();
    $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silent fallback
}

// Helper: Is today a weekend (Fri/Sat/Sun)?
$today_dow = date('N'); // 1=Mon … 7=Sun
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
        background: linear-gradient(rgba(17, 17, 17, 0.75), rgba(17, 17, 17, 0.85)), url('../assets/img/spa-bg.jpg') center/cover no-repeat;
        padding: 140px 0 100px 0;
        border-bottom: 1px solid var(--brand-gold);
    }

    .hero-tagline {
        font-family: 'DM Sans', sans-serif;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 4px;
        color: var(--gold-light);
        font-size: 0.8rem;
        display: inline-block;
    }

    .card-editorial-light {
        background: var(--pure-white);
        border: 1px solid rgba(201, 169, 110, 0.15);
        border-radius: 12px !important;
        box-shadow: 0 10px 40px rgba(26, 26, 26, 0.02) !important;
        transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.4s ease;
        padding: 45px 40px;
    }

    .card-editorial-light .serif-title {
        color: #111111;
        font-size: 2.2rem;
        margin-bottom: 30px;
    }

    .card-editorial-light .feature-item {
        color: #8c8273;
        font-size: 1.05rem;
        margin-bottom: 18px;
    }

    .card-editorial-light .btn-link-editorial {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: #111111;
        text-decoration: none;
        display: inline-block;
        margin-top: 40px;
        border-bottom: 1px solid #111111;
        padding-bottom: 4px;
        transition: color 0.3s ease, border-color 0.3s ease;
    }

    .card-editorial-light .btn-link-editorial:hover {
        color: var(--brand-gold);
        border-color: var(--brand-gold);
    }

    .card-editorial-dark {
        background: #151515;
        border: 1px solid rgba(201, 169, 110, 0.3);
        border-radius: 12px !important;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12) !important;
        transition: transform 0.4s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.4s ease;
        padding: 45px 40px;
    }

    .card-editorial-dark .serif-title {
        color: var(--pure-white);
        font-size: 2.2rem;
        margin-bottom: 30px;
    }

    .card-editorial-dark .feature-item {
        color: #a39887;
        font-size: 1.05rem;
        margin-bottom: 18px;
    }

    .card-editorial-dark .btn-link-editorial {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--brand-gold);
        text-decoration: none;
        display: inline-block;
        margin-top: 40px;
        border-bottom: 1px solid var(--brand-gold);
        padding-bottom: 4px;
        transition: color 0.3s ease, letter-spacing 0.3s ease;
    }

    .card-editorial-dark .btn-link-editorial:hover {
        color: var(--pure-white);
        border-color: var(--pure-white);
        letter-spacing: 3px;
    }

    .card-editorial-dark:hover, .card-editorial-light:hover {
        transform: translateY(-6px);
    }

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

    .promo-category-badge {
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--brand-gold);
        font-size: 0.75rem;
    }

    .promo-banner-card h4 {
        font-size: 2.4rem;
        color: var(--the-dark);
    }

    .promo-tagline-italic {
        font-family: 'Cormorant Garamond', serif;
        font-style: italic;
        font-size: 1.25rem;
        color: var(--muted-text);
    }

    .services-heading {
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--the-dark);
    }

    .meta-spec-item {
        font-size: 0.9rem;
        color: var(--the-dark);
        font-weight: 500;
    }

    .meta-spec-item i {
        color: var(--brand-gold);
        font-size: 1rem;
    }

    .price-strike {
        text-decoration: line-through;
        color: #b5ac9e;
        font-size: 1.25rem;
        font-weight: 300;
    }

    .price-active {
        color: var(--the-dark);
        font-size: 2.2rem;
        font-weight: 400;
        font-family: 'Cormorant Garamond', serif;
        margin-left: 8px;
    }

    .price-savings-tag {
        font-family: 'DM Sans', sans-serif;
        color: var(--brand-gold);
        font-size: 0.9rem;
        font-weight: 500;
    }

    .terms-onsite-notice {
        font-size: 0.78rem;
        font-style: italic;
        color: var(--muted-text);
        line-height: 1.5;
    }

    .btn-luxe-action {
        background: var(--the-dark);
        color: var(--pure-white);
        border: 1px solid var(--the-dark);
        border-radius: 0px;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.8rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 2px;
        padding: 12px 35px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: block;
        text-align: center;
    }

    .btn-luxe-action:hover {
        background: transparent;
        color: var(--the-dark);
        border-color: var(--the-dark);
    }

    .btn-luxe-action.disabled-promo {
        background: #ccc;
        border-color: #ccc;
        color: #666;
        cursor: not-allowed;
        pointer-events: none;
    }

    .promo-notice-tag {
        display: inline-block;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        padding: 3px 10px;
        border-radius: 20px;
        margin-bottom: 10px;
    }

    .notice-birthday  { background: #fff3cd; color: #856404; }
    .notice-weekend   { background: #e8f4fd; color: #1a6496; }
    .notice-member    { background: #f0fdf4; color: #15803d; }
    .notice-login     { background: #fef3c7; color: #92400e; }
</style>

<div class="luxe-promo-context">

    <div class="promo-hero-luxe text-center mb-5">
        <div class="container py-2">
            <span class="hero-tagline mb-2">Tier Arrangements</span>
            <h1 class="display-4 text-white" style="font-weight: 300;">Elevate Your <span style="font-weight: 500;">Experience</span></h1>
            <div class="mx-auto my-3" style="width: 40px; height: 1px; background: var(--brand-gold);"></div>
            <p class="lead text-white-50 mx-auto" style="max-width: 480px; font-size: 0.95rem; letter-spacing: 0.5px;">Choose a framework tailored to your rhythm and lifestyle of continuous restoration.</p>
        </div>
    </div>

    <div class="container my-5 pb-5">

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
                                <a href="index.php" class="btn-link-editorial">Learn More</a>
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
                            <div class="feature-item">2 Free Semi-Luxury Room Uses</div>
                            <div class="feature-item">Priority Therapist Selection</div>
                            <div class="feature-item">Exclusive Monthly Rituals</div>
                        </div>
                        <div>
                            <?php if($account_type === 'member'): ?>
                                <a href="home.php" class="btn-link-editorial">Manage Plan</a>
                            <?php else: ?>
                                <a href="membership_payment.php" class="btn-link-editorial">View All Offers</a>
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
                    <span class="hero-tagline" style="color: var(--brand-gold);">Curated Selections</span>
                    <h3 class="display-6 mt-1" style="font-size: 2.2rem; font-weight: 300;">Exclusive Offers & Curation Banners</h3>
                </div>

                <div class="d-flex flex-column gap-5">
                    <?php if (!empty($promotions)): ?>
                        <?php foreach ($promotions as $promo):
                            // Use actual DB column names: promo_name, included_service, duration_minutes, price_now, promo_id
                            $savings = $promo['original_price'] - $promo['price_now'];

                            // --- Determine booking button state based on promo name ---
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
                                    $notice_html  = '<span class="promo-notice-tag notice-birthday"><i class="bi bi-cake2 me-1"></i>Available in your birth month only</span>';
                                } else {
                                    $notice_html = '<span class="promo-notice-tag notice-birthday"><i class="bi bi-cake2 me-1"></i>Happy birth month! You qualify.</span>';
                                }
                                $terms_notice = '* Requires validation of birthdate via account profile or physical ID onsite.';

                            } elseif (str_contains($promo_name, 'weekend')) {
                                $badge_label = 'Weekend Deal';
                                if (!$is_weekend) {
                                    $btn_text     = 'Available Fri – Sun Only';
                                    $btn_disabled = true;
                                    $notice_html  = '<span class="promo-notice-tag notice-weekend"><i class="bi bi-calendar-week me-1"></i>Book on Friday, Saturday, or Sunday</span>';
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
                                    $notice_html = '<span class="promo-notice-tag notice-login"><i class="bi bi-unlock me-1"></i>Open to all registered users</span>';
                                }

                            } elseif (str_contains($promo_name, 'couple')) {
                                $badge_label = 'Ongoing Offer';
                                $notice_html = '<span class="promo-notice-tag notice-member"><i class="bi bi-people me-1"></i>Ongoing Offer</span>';
                                if ($account_type === 'member') {
                                    $terms_notice = '* Lumiére Circle members: this promotion does not count against your 2 free semi-luxury room upgrade allocation.';
                                } else {
                                    $terms_notice = '* Available to all guests. Premium members retain their seasonal room upgrade allocation separately.';
                                }
                            }
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
                </div>

            </div>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>