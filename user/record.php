<?php
include '../includes/header.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT a.*, 
               COALESCE(t.name, p.promo_name, pkg.name, 'N/A') AS treatment_name,
               th.first_name AS therapist_fname, 
               th.last_name AS therapist_lname 
        FROM appointments a
        LEFT JOIN treatments t   ON a.treatment_id = t.treatment_id
        LEFT JOIN promotions p   ON a.promo_id = p.promo_id
        LEFT JOIN packages pkg   ON a.package_id = pkg.package_id
        JOIN therapists th       ON a.therapist_id = th.therapist_id
        WHERE a.user_id = ? 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$records = $stmt->fetchAll();
?>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

<style>
    :root {
        --pure-white: #ffffff;
        --studio-surface: #fdfbf7;
        --brand-gold: #c9a96e;
        --gold-light: #e8d5b0;
        --the-dark: #1a1a1a;
        --studio-mid: #2e2e2e;
        --muted-text: #8a8070;
        --lumiere-glow: linear-gradient(135deg, var(--the-dark), var(--studio-mid));
    }

    .luxe-records-context {
        background-color: var(--studio-surface);
        font-family: 'DM Sans', sans-serif;
        font-size: 18px; 
        color: var(--the-dark);
        line-height: 1.6;
        min-height: 80vh;
    }

    /* Typography Hierarchy & Weight Pairings */
    .luxe-records-context h1, 
    .luxe-records-context h2, 
    .luxe-records-context h3, 
    .luxe-records-context h4,
    .luxe-records-context .serif-heading {
        font-family: 'Cormorant Garamond', serif;
        color: var(--the-dark);
        letter-spacing: -0.01em;
    }

    /* ── HERO ─────────────────────────────────────────────────── */
.records-hero {
    position: relative;
    min-height: 88vh;
    display: flex;
    align-items: flex-end;
    justify-content: flex-start;
    overflow: hidden;
    background: var(--the-dark);
    margin-bottom: 5rem;
}

.records-hero::before {
    content: '';
    position: absolute;
    left: 80px;
    top: 0; bottom: 0;
    width: 1px;
    background: linear-gradient(to bottom, transparent, var(--brand-gold), transparent);
    opacity: 0.6;
    z-index: 1;
}

.records-hero::after {
    content: 'RECORDS';
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

.records-hero__content {
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

.records-hero__title {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 300;
    font-size: clamp(3rem, 6vw, 5.5rem);
    color: var(--pure-white);
    line-height: 1.05;
    margin-bottom: 28px;
}
.records-hero__title em {
    font-style: italic;
    color: var(--gold-light);
}

.records-hero__sub {
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
    .records-hero__content { padding: 0 32px 64px; }
    .records-hero::before  { left: 32px; }
    .scroll-cue            { right: 32px; }
}
@media (max-width: 768px) {
    .records-hero__content { padding: 0 20px 52px; }
    .records-hero::before  { display: none; }
    .scroll-cue            { right: 20px; }
}

    /* Structured Geometry Content Panel */
    .record-card-luxe {
        border: 1px solid rgba(232, 213, 176, 0.4);
        border-radius: 0px !important;
        background: var(--pure-white);
        transition: box-shadow 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    .record-card-luxe:hover {
        box-shadow: 0 30px 60px rgba(26, 26, 26, 0.04) !important;
    }

    /* Luxury High-Contrast Table Layout */
    .table-luxe {
        margin-bottom: 0;
        width: 100%;
        background-color: transparent;
    }

    .table-luxe thead th {
        background-color: var(--the-dark);
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 2px;
        font-weight: 500;
        color: var(--gold-light);
        border: none;
        padding: 20px 24px;
    }

    .table-luxe tbody tr {
        transition: background-color 0.3s ease;
    }

    .table-luxe tbody tr:hover {
        background-color: rgba(253, 251, 247, 0.7);
    }

    .table-luxe tbody td {
        padding: 26px 24px;
        vertical-align: middle;
        border-bottom: 1px solid rgba(232, 213, 176, 0.25);
        font-size: 0.95rem;
        color: var(--the-dark);
    }

    /* Text Data Element Customizations */
    .data-primary {
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        color: var(--the-dark);
        font-size: 1.05rem;
    }

    .data-secondary {
        font-family: 'DM Sans', sans-serif;
        color: var(--muted-text);
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    .treatment-title-luxe {
        font-family: 'Cormorant Garamond', serif;
        font-weight: 600;
        font-size: 1.35rem;
        color: var(--the-dark);
    }

    /* Premium Status Indicators using Lumiére Glow rules */
    .status-badge-luxe {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 6px 14px;
        border-radius: 0px !important;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        display: inline-block;
        border: 1px solid transparent;
    }

    .status-pending { 
        background: #fffcf6; 
        color: #c2780e; 
        border-color: #fce8cc;
    }
    
    .status-confirmed { 
        background: #f4fcf7; 
        color: #1c7a43; 
        border-color: #d3f4e0;
    }
    
    .status-completed { 
        background: var(--studio-surface); 
        color: var(--muted-text); 
        border-color: var(--gold-light);
    }
    
    .status-cancelled { 
        background: #fff8f8; 
        color: #b82323; 
        border-color: #fcd4d4;
    }

    .membership-pill {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.7rem;
        font-weight: 500;
        letter-spacing: 1px;
        background-color: transparent;
        color: var(--brand-gold);
        padding: 4px 10px;
        border: 1px solid var(--brand-gold);
        text-transform: uppercase;
    }

    /* Minimalist Empty State Component Style */
    .empty-state-luxe {
        padding: 100px 24px;
        text-center: center;
    }

    .btn-luxe-dark {
        background: var(--lumiere-glow);
        color: var(--pure-white) !important;
        border: none;
        border-radius: 0px !important;
        padding: 14px 36px;
        font-weight: 500;
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 2px;
        display: inline-block;
        text-decoration: none;
    }

    .btn-luxe-dark:hover {
        background: var(--brand-gold);
        color: var(--pure-white) !important;
        box-shadow: 0 10px 20px rgba(201, 169, 110, 0.15);
    }
</style>

<div class="luxe-records-context">
    <section class="records-hero">
    <div class="records-hero__content">
        <p class="hero-tagline">Personal Sanctuary Activity</p>
        <h1 class="records-hero__title">My Wellness<br><em>Records</em></h1>
        <p class="records-hero__sub">Tracking your ritual arrangements timeline since <?= date('F Y', strtotime($_SESSION['created_at'] ?? 'now')) ?>.</p>
    </div>
    <div class="scroll-cue">
        <div class="scroll-cue__line"></div>
        <span>Scroll</span>
    </div>
</section>

        <div class="card record-card shadow-sm overflow-hidden record-card-luxe">
            <div class="table-responsive">
                <table class="table table-luxe align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 22%;">Scheduled Date & Time</th>
                            <th scope="col" style="width: 32%;">Arranged Treatment</th>
                            <th scope="col" style="width: 23%;">Assigned Practitioner</th>
                            <th scope="col" style="width: 13%;">Statement Amount</th>
                            <th scope="col" style="width: 10%;">Ritual Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $r): ?>
                                <tr>
                                    <td>
                                        <div class="data-primary"><?= date('M d, Y', strtotime($r['appointment_date'])) ?></div>
                                        <div class="data-secondary"><i class="bi bi-clock me-1 small"></i> <?= date('h:i A', strtotime($r['appointment_time'])) ?></div>
                                    </td>
                                    
                                    <td>
                                        <div class="treatment-title-luxe"><?= htmlspecialchars($r['treatment_name']) ?></div>
                                    </td>
                                    
                                    <td>
                                        <div class="data-primary" style="font-weight: 500; font-size: 1rem;">
                                            <?= htmlspecialchars($r['therapist_fname'] . ' ' . $r['therapist_lname']) ?>
                                        </div>
                                        <div class="data-secondary" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">Therapist</div>
                                    </td>
                                    
                                    <td>
                                        <?php if(isset($r['payment_type']) && $r['payment_type'] === 'membership'): ?>
                                            <span class="membership-pill">Member Credit</span>
                                        <?php else: ?>
                                            <span class="data-primary" style="color: var(--the-dark); font-weight: 700;">₱<?= number_format($r['total_amount'], 2) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <?php 
                                            $statusValue = isset($r['status']) ? $r['status'] : 'Pending';
                                            $statusClass = 'status-' . strtolower($statusValue);
                                            echo "<span class='status-badge-luxe $statusClass'>" . htmlspecialchars($statusValue) . "</span>";
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="empty-state-luxe text-center">
                                    <div class="py-4">
                                        <div class="mx-auto mb-4 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; border: 1px solid var(--gold-light); background: transparent;">
                                            <i class="bi bi-calendar3 text-muted" style="font-size: 1.5rem;"></i>
                                        </div>
                                        <h3 class="h4 mb-2" style="font-weight: 600;">No Historic Logs Found</h3>
                                        <p class="text-muted mx-auto mb-4" style="max-width: 400px; font-size: 0.95rem;">You have not reserved any session parameters with Lumiére Curations at this moment.</p>
                                        <a href="treatment.php" class="btn btn-luxe-dark">
                                            Explore Our Curations
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>