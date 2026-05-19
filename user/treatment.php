<?php
include '../includes/header.php';
require_once '../config/db.php';


$stmt = $pdo->query("SELECT * FROM treatments WHERE status = 'available' AND type = 'individual' ORDER BY name ASC");
$individual_treatments = $stmt->fetchAll();

$pkg_sql = "SELECT t.*, 
            GROUP_CONCAT(t_sub.name SEPARATOR ' + ') as sub_names,
            (SELECT image FROM treatments t2 JOIN package_items pi ON t2.treatment_id = pi.treatment_id WHERE pi.package_id = t.treatment_id LIMIT 1) as img1,
            (SELECT image FROM treatments t3 JOIN package_items pi ON t3.treatment_id = pi.treatment_id WHERE pi.package_id = t.treatment_id LIMIT 1 OFFSET 1) as img2
            FROM treatments t 
            LEFT JOIN package_items pi ON t.treatment_id = pi.package_id
            LEFT JOIN treatments t_sub ON pi.treatment_id = t_sub.treatment_id
            WHERE t.status = 'available' AND t.type = 'package' 
            GROUP BY t.treatment_id
            ORDER BY t.name ASC";
$package_bundles = $pdo->query($pkg_sql)->fetchAll();
?>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">

<style>
/* ============================================================
   LUMIÈRE — SIGNATURE RITUALS PAGE
   High-Contrast Luxe · Refined Minimalism · Editorial Feel
   ============================================================ */

:root {
    --pure-white:    #ffffff;
    --studio-surface:#fdfbf7;
    --brand-gold:    #c9a96e;
    --gold-light:    #e8d5b0;
    --gold-dark:     #a07d42;
    --the-dark:      #1a1a1a;
    --studio-mid:    #2e2e2e;
    --muted-text:    #8a8070;
    --lumiere-glow:  linear-gradient(135deg, var(--the-dark), var(--studio-mid));
    --section-gap:   120px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    background-color: var(--studio-surface);
    font-family: 'DM Sans', sans-serif;
    font-size: 18px;
    color: var(--the-dark);
    line-height: 1.6;
    overflow-x: hidden;
}

h1, h2, h3, h4, h5, .serif {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 600;
    color: var(--the-dark);
    letter-spacing: -0.02em;
    line-height: 1.15;
}

/* ── HERO ─────────────────────────────────────────────────── */
.ritual-hero {
    content: 'TREATMENTS';
    position: relative;
    min-height: 88vh;
    display: flex;
    align-items: flex-end;
    justify-content: flex-start;
    overflow: hidden;
    background: var(--the-dark);
}

.ritual-hero__bg {
    position: absolute;
    inset: 0;
    background: url('../assets/img/treatments/hero-bg.jpg') center/cover no-repeat;
    opacity: 0.45;
    transform: scale(1.04);
    transition: transform 8s ease;
}
.ritual-hero:hover .ritual-hero__bg { transform: scale(1); }

/* Vertical gold accent line */
/* Vertical gold accent line */
.ritual-hero::before {
    content: '';
    position: absolute;
    left: 80px;
    top: 0; bottom: 0;
    width: 1px;
    background: linear-gradient(to bottom, transparent, var(--brand-gold), transparent);
    opacity: 0.6;
}

.ritual-hero::after {
    content: 'TREATMENT';
    position: absolute;
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(5rem, 18vw, 17rem);
    font-weight: 600;
    color: rgba(255,255,255,0.025);
    bottom: -0.1em; right: -0.04em;
    white-space: nowrap;
    pointer-events: none;
    letter-spacing: -0.02em;
}

.ritual-hero__content {
    position: relative;
    z-index: 2;
    padding: 0 80px 80px;
    max-width: 680px;
}

.ritual-hero__eyebrow {
    font-family: 'DM Sans', sans-serif;
    font-size: 0.72rem;
    font-weight: 500;
    letter-spacing: 5px;
    text-transform: uppercase;
    color: var(--brand-gold);
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}
.ritual-hero__eyebrow::before {
    content: '';
    display: block;
    width: 40px;
    height: 1px;
    background: var(--brand-gold);
}

.ritual-hero__title {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 300;
    font-size: clamp(3rem, 6vw, 5.5rem);
    color: var(--pure-white);
    line-height: 1.05;
    margin-bottom: 28px;
}
.ritual-hero__title em {
    font-style: italic;
    color: var(--gold-light);
}

.ritual-hero__sub {
    font-size: 1rem;
    color: rgba(255,255,255,0.55);
    font-weight: 300;
    max-width: 420px;
    letter-spacing: 0.01em;
}

/* Scroll indicator */
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

/* ── SECTION ANATOMY ──────────────────────────────────────── */
.section-wrap { padding: var(--section-gap) 0; }
.section-wrap--dark {
    background: var(--the-dark);
    color: var(--pure-white);
}
.section-wrap--dark h2,
.section-wrap--dark h3,
.section-wrap--dark h4,
.section-wrap--dark p { color: var(--pure-white); }

.section-head {
    margin-bottom: 64px;
}
.section-head__label {
    font-size: 0.7rem;
    letter-spacing: 5px;
    text-transform: uppercase;
    color: var(--brand-gold);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
}
.section-head__label::after {
    content: '';
    flex: 1;
    max-width: 60px;
    height: 1px;
    background: var(--brand-gold);
}
.section-head__title {
    font-size: clamp(2.2rem, 4vw, 3.5rem);
}
.section-head__title span { font-weight: 300; font-style: italic; }

/* ── INDIVIDUAL TREATMENT CARDS ───────────────────────────── */
.treatments-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2px;
}

.tcard {
    position: relative;
    background: var(--pure-white);
    overflow: hidden;
    cursor: pointer;
    display: flex;
    flex-direction: column;
}

.tcard__image-wrap {
    position: relative;
    height: 320px;
    overflow: hidden;
}
.tcard__image-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.7s cubic-bezier(0.165, 0.84, 0.44, 1);
    filter: brightness(0.92);
}
.tcard:hover .tcard__image-wrap img {
    transform: scale(1.06);
    filter: brightness(1);
}

/* Gold corner accent */
.tcard__image-wrap::after {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: linear-gradient(to bottom, transparent 55%, rgba(26,26,26,0.55) 100%);
    pointer-events: none;
}

.tcard__duration {
    position: absolute;
    top: 18px; right: 18px;
    z-index: 2;
    background: rgba(26,26,26,0.7);
    backdrop-filter: blur(6px);
    color: var(--gold-light);
    font-size: 0.68rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    padding: 6px 14px;
    font-weight: 500;
}

.tcard__body {
    padding: 28px 28px 32px;
    flex: 1;
    display: flex;
    flex-direction: column;
    border-bottom: 1px solid var(--gold-light);
    border-left: 1px solid var(--gold-light);
    border-right: 1px solid var(--gold-light);
}

.tcard__name {
    font-size: 1.35rem;
    margin-bottom: 6px;
}

.tcard__divider {
    width: 32px;
    height: 1px;
    background: var(--brand-gold);
    margin: 14px 0;
}

.tcard__footer {
    margin-top: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 20px;
    border-top: 1px solid rgba(201,169,110,0.2);
}

.tcard__price {
    font-family: 'DM Sans', sans-serif;
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--the-dark);
}

.btn-ghost {
    background: transparent;
    border: 1px solid var(--the-dark);
    color: var(--the-dark);
    font-size: 0.7rem;
    font-weight: 500;
    letter-spacing: 2px;
    text-transform: uppercase;
    padding: 9px 20px;
    cursor: pointer;
    transition: background 0.25s, color 0.25s, border-color 0.25s;
}
.btn-ghost:hover {
    background: var(--the-dark);
    color: var(--pure-white);
}

/* Stagger reveal animation */
.tcard {
    opacity: 0;
    transform: translateY(28px);
    animation: fadeUp 0.6s ease forwards;
}
@keyframes fadeUp {
    to { opacity: 1; transform: translateY(0); }
}
.tcard:nth-child(1)  { animation-delay: 0.05s; }
.tcard:nth-child(2)  { animation-delay: 0.12s; }
.tcard:nth-child(3)  { animation-delay: 0.19s; }
.tcard:nth-child(4)  { animation-delay: 0.26s; }
.tcard:nth-child(5)  { animation-delay: 0.33s; }
.tcard:nth-child(6)  { animation-delay: 0.40s; }
.tcard:nth-child(7)  { animation-delay: 0.47s; }
.tcard:nth-child(8)  { animation-delay: 0.54s; }

/* ── INTERLUDE BANNER ──────────────────────────────────────── */
.interlude {
    background: var(--the-dark);
    padding: 80px 0;
    position: relative;
    overflow: hidden;
}
.interlude::before {
    content: 'PACKAGE';
    position: absolute;
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(6rem, 18vw, 18rem);
    font-weight: 600;
    color: rgba(255,255,255,0.025);
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    white-space: nowrap;
    pointer-events: none;
    letter-spacing: 0.3em;
}
.interlude__inner {
    text-align: center;
    position: relative;
    z-index: 1;
}
.interlude__quote {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 300;
    font-style: italic;
    font-size: clamp(1.6rem, 3vw, 2.6rem);
    color: var(--gold-light);
    max-width: 700px;
    margin: 0 auto 20px;
    line-height: 1.4;
}
.interlude__attr {
    font-size: 0.7rem;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.3);
}

/* ── PACKAGE BUNDLES ───────────────────────────────────────── */
.bundle-list { display: flex; flex-direction: column; gap: 2px; }

.bundle-item {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 560px;
    background: var(--pure-white);
    overflow: hidden;
    transition: box-shadow 0.4s;
}
.bundle-item:hover { box-shadow: 0 24px 80px rgba(0,0,0,0.12); }
.bundle-item--reverse { direction: rtl; }
.bundle-item--reverse > * { direction: ltr; }

.bundle-item__images {
    position: relative;
    display: grid;
    grid-template-columns: 1fr 1fr;
    overflow: hidden;
}
.bundle-item__images img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.8s cubic-bezier(0.165, 0.84, 0.44, 1);
}
.bundle-item:hover .bundle-item__images img { transform: scale(1.04); }

/* Vertical divider between images */
.bundle-item__images::after {
    content: '';
    position: absolute;
    left: 50%; top: 15%; bottom: 15%;
    width: 1px;
    background: rgba(255,255,255,0.4);
    pointer-events: none;
}

.bundle-item__content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 64px 72px;
    border: 1px solid var(--gold-light);
}

.bundle-item__tag {
    font-size: 0.68rem;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: var(--brand-gold);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}
.bundle-item__tag::before {
    content: '';
    width: 28px; height: 1px;
    background: var(--brand-gold);
}

.bundle-item__name {
    font-size: clamp(1.8rem, 3vw, 2.8rem);
    margin-bottom: 10px;
}

.bundle-item__sub {
    font-size: 0.85rem;
    letter-spacing: 1.5px;
    color: var(--brand-gold);
    text-transform: uppercase;
    font-weight: 500;
    margin-bottom: 24px;
}

.bundle-item__desc {
    font-size: 0.95rem;
    color: var(--muted-text);
    line-height: 1.75;
    margin-bottom: 40px;
    max-width: 380px;
}

.bundle-item__meta {
    display: flex;
    align-items: baseline;
    gap: 24px;
    margin-bottom: 36px;
    padding-bottom: 36px;
    border-bottom: 1px solid var(--gold-light);
}
.bundle-item__price {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2.2rem;
    font-weight: 600;
    color: var(--the-dark);
}
.bundle-item__duration {
    font-size: 0.75rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--muted-text);
    font-weight: 500;
}

.btn-luxe {
    display: inline-block;
    background: var(--the-dark);
    color: var(--pure-white);
    font-size: 0.72rem;
    font-weight: 500;
    letter-spacing: 3px;
    text-transform: uppercase;
    text-decoration: none;
    padding: 16px 40px;
    transition: background 0.3s, color 0.3s, letter-spacing 0.3s;
    align-self: flex-start;
}
.btn-luxe:hover {
    background: var(--brand-gold);
    color: var(--pure-white);
    letter-spacing: 4px;
}

/* ── MODAL REDESIGN ───────────────────────────────────────── */
.modal-backdrop { backdrop-filter: blur(6px); }

#detailsModal .modal-content {
    background: var(--studio-surface);
    border: none;
    border-radius: 0;
    box-shadow: 0 40px 120px rgba(0,0,0,0.25);
    overflow: hidden;
}

.modal-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 560px;
}

.modal-split__image {
    position: relative;
    overflow: hidden;
}
.modal-split__image img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
}
.modal-split__image::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(to right, transparent, rgba(253,251,247,0.12));
    pointer-events: none;
}

.modal-split__body {
    padding: 64px 56px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
}

.modal-close {
    position: absolute;
    top: 28px; right: 28px;
    background: none;
    border: none;
    cursor: pointer;
    width: 36px; height: 36px;
    display: flex; align-items: center; justify-content: center;
    opacity: 0.4;
    transition: opacity 0.2s;
    font-size: 1.4rem;
    color: var(--the-dark);
}
.modal-close:hover { opacity: 1; }

.modal-eyebrow {
    font-size: 0.68rem;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: var(--brand-gold);
    font-weight: 500;
    margin-bottom: 16px;
}

#detailName {
    font-size: clamp(1.8rem, 3vw, 2.4rem);
    margin-bottom: 6px;
}

.modal-duration {
    font-size: 0.78rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--muted-text);
    font-weight: 500;
    margin-bottom: 28px;
    display: flex; align-items: center; gap: 8px;
}
.modal-duration::before {
    content: '';
    display: block;
    width: 20px; height: 1px;
    background: var(--muted-text);
}

#detailDescription {
    font-size: 0.95rem;
    color: var(--muted-text);
    line-height: 1.8;
    flex: 1;
    margin-bottom: 36px;
}

.modal-footer-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    padding-top: 28px;
    border-top: 1px solid var(--gold-light);
}

.modal-price-label {
    font-size: 0.65rem;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--muted-text);
    margin-bottom: 6px;
}
#detailPrice {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2rem;
    font-weight: 600;
    line-height: 1;
}

/* ── UTILITIES ───────────────────────────────────────────── */
.container-xl { max-width: 1400px; margin: 0 auto; padding: 0 60px; }

@media (max-width: 991px) {
    .container-xl { padding: 0 28px; }
    .ritual-hero::before { left: 28px; }
    .ritual-hero__content { padding: 0 28px 60px; }
    .scroll-cue { right: 28px; }
    .bundle-item { grid-template-columns: 1fr; }
    .bundle-item--reverse { direction: ltr; }
    .bundle-item__images { min-height: 280px; }
    .bundle-item__content { padding: 40px 32px; }
    .modal-split { grid-template-columns: 1fr; }
    .modal-split__image { height: 260px; }
    .modal-split__body { padding: 36px 28px; }
    :root { --section-gap: 72px; }
}

@media (max-width: 640px) {
    .treatments-grid { grid-template-columns: 1fr 1fr; gap: 2px; }
    .tcard__image-wrap { height: 220px; }
}
@media (max-width: 420px) {
    .treatments-grid { grid-template-columns: 1fr; }
}
</style>

<!-- ══════════════════════════════════════════════════════════
     HERO
     ══════════════════════════════════════════════════════════ -->
<section class="ritual-hero">
    <div class="ritual-hero__bg"></div>

    <div class="ritual-hero__content">
        <p class="ritual-hero__eyebrow">Lumiére Bliss</p>
        <h1 class="ritual-hero__title">Signature<br><em>Rituals</em></h1>
        <p class="ritual-hero__sub">Moments of devoted care, thoughtfully composed for mind, skin, and spirit.</p>
    </div>

    <div class="scroll-cue">
        <div class="scroll-cue__line"></div>
        <span>Scroll</span>
    </div>
</section>


<!-- ══════════════════════════════════════════════════════════
     INDIVIDUAL TREATMENTS
     ══════════════════════════════════════════════════════════ -->
<section class="section-wrap">
    <div class="container-xl">
        <div class="section-head">
            <p class="section-head__label">Curated for You</p>
            <h2 class="section-head__title">Individual <span>Treatments</span></h2>
        </div>

        <div class="treatments-grid">
            <?php if (!empty($individual_treatments)): ?>
                <?php foreach ($individual_treatments as $t): ?>
                    <article class="tcard">
                        <div class="tcard__image-wrap">
                            <img src="../assets/img/treatments/<?= !empty($t['image']) ? htmlspecialchars($t['image']) : 'default.jpg' ?>"
                                 alt="<?= htmlspecialchars($t['name']) ?>">
                            <span class="tcard__duration">
                                <?= htmlspecialchars($t['duration_minutes']) ?> min
                            </span>
                        </div>
                        <div class="tcard__body">
                            <h3 class="tcard__name"><?= htmlspecialchars($t['name']) ?></h3>
                            <div class="tcard__divider"></div>
                            <div class="tcard__footer">
                                <span class="tcard__price">₱<?= number_format($t['price'], 2) ?></span>
                                <button class="btn-ghost"
                                        onclick='showDetails(<?= json_encode($t) ?>)'>
                                    Discover
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>


<!-- ══════════════════════════════════════════════════════════
     INTERLUDE QUOTE
     ══════════════════════════════════════════════════════════ -->
<div class="interlude">
    <div class="interlude__inner">
        <p class="interlude__quote">"True luxury is the art of becoming, not merely having."</p>
        <p class="interlude__attr">— The Lumiére Philosophy</p>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     EXCLUSIVE COLLECTIONS (Packages)
     ══════════════════════════════════════════════════════════ -->
<section class="section-wrap">
    <div class="container-xl">
        <div class="section-head">
            <p class="section-head__label">Exclusive Collections</p>
            <h2 class="section-head__title">Signature <span>Packages</span></h2>
        </div>
    </div>

    <?php if (!empty($package_bundles)): ?>
        <div class="bundle-list">
            <?php $counter = 0; foreach ($package_bundles as $t):
                $reverse_class = ($counter % 2 !== 0) ? 'bundle-item--reverse' : '';
            ?>
                <div class="bundle-item <?= $reverse_class ?>">
                    <!-- Dual Images -->
                    <div class="bundle-item__images">
                        <img src="../assets/img/treatments/<?= $t['img1'] ?: 'default.jpg' ?>"
                             alt="<?= htmlspecialchars($t['name']) ?> – view 1">
                        <img src="../assets/img/treatments/<?= $t['img2'] ?: 'default.jpg' ?>"
                             alt="<?= htmlspecialchars($t['name']) ?> – view 2">
                    </div>

                    <!-- Content -->
                    <div class="bundle-item__content">
                        <p class="bundle-item__tag">Signature Package</p>
                        <h2 class="bundle-item__name"><?= htmlspecialchars($t['name']) ?></h2>
                        <p class="bundle-item__sub"><?= htmlspecialchars($t['sub_names']) ?></p>
                        <p class="bundle-item__desc"><?= htmlspecialchars($t['description']) ?></p>

                        <div class="bundle-item__meta">
                            <span class="bundle-item__price">₱<?= number_format($t['price'], 2) ?></span>
                            <span class="bundle-item__duration">
                                <i class="bi bi-clock me-1"></i><?= htmlspecialchars($t['duration_minutes']) ?> min
                            </span>
                        </div>

                        <a href="appointment.php?tid=<?= $t['treatment_id'] ?>" class="btn-luxe">
                            Reserve Bundle
                        </a>
                    </div>
                </div>
            <?php $counter++; endforeach; ?>
        </div>
    <?php endif; ?>
</section>


<!-- ══════════════════════════════════════════════════════════
     TREATMENT DETAIL MODAL
     ══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailName" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-split">
                <!-- Image Panel -->
                <div class="modal-split__image">
                    <img id="detailImage" src="" alt="">
                </div>

                <!-- Info Panel -->
                <div class="modal-split__body">
                    <button class="modal-close" data-bs-dismiss="modal" aria-label="Close">✕</button>

                    <p class="modal-eyebrow">Treatment Insight</p>
                    <h2 id="detailName"></h2>
                    <p class="modal-duration" id="detailDuration"></p>
                    <p id="detailDescription"></p>

                    <div class="modal-footer-row">
                        <div>
                            <p class="modal-price-label">Price</p>
                            <p id="detailPrice"></p>
                        </div>
                        <a id="detailBookBtn" href="" class="btn-luxe">Book Session</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function showDetails(data) {
    document.getElementById('detailName').innerText        = data.name;
    document.getElementById('detailDescription').innerText = data.description;
    document.getElementById('detailDuration').innerText    = data.duration_minutes + " minutes";
    document.getElementById('detailPrice').innerText       = "₱" + parseFloat(data.price).toLocaleString(undefined, { minimumFractionDigits: 2 });

    const imagePath = "../assets/img/treatments/" + (data.image ? data.image : 'default.jpg');
    document.getElementById('detailImage').src   = imagePath;
    document.getElementById('detailImage').alt   = data.name;

    document.getElementById('detailBookBtn').href = "appointment.php?tid=" + data.treatment_id;

    var myModal = new bootstrap.Modal(document.getElementById('detailsModal'));
    myModal.show();
}
</script>