<?php 
include '../includes/header.php'; 
require_once '../config/db.php';

// Function to fetch by the exact ENUM values we set in the DB
function getCosmeticsByCategory($pdo, $cat) {
    $stmt = $pdo->prepare("SELECT * FROM cosmetics WHERE category = ?");
    $stmt->execute([$cat]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// These variable names MUST match the foreach loops in your sections
$candles     = getCosmeticsByCategory($pdo, 'Scented Candle');
$oils        = getCosmeticsByCategory($pdo, 'Essential Oil');
$accessories = getCosmeticsByCategory($pdo, 'Spa Accessory');
?>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">

<style>
/* ============================================================
   LUMIÈRE — L'ART DE VIVRE · COSMETICS PAGE
   High-Contrast Luxe · Editorial Minimalism
   ============================================================ */

:root {
    --pure-white:     #ffffff;
    --studio-surface: #fdfbf7;
    --brand-gold:     #c9a96e;
    --gold-light:     #e8d5b0;
    --gold-dark:      #a07d42;
    --the-dark:       #1a1a1a;
    --studio-mid:     #2e2e2e;
    --muted-text:     #8a8070;
    --section-gap:    110px;
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

h1, h2, h3, h4, h5 {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 600;
    color: var(--the-dark);
    letter-spacing: -0.02em;
    line-height: 1.15;
}

/* ── HERO ─────────────────────────────────────────────────── */
.cosmetic-hero {
    position: relative;
    min-height: 88vh;
    display: flex;
    align-items: flex-end;
    justify-content: flex-start;
    overflow: hidden;
    background: var(--the-dark);
}

.cosmetic-hero__bg {
    position: absolute;
    inset: 0;
    background: url('../assets/cosmetics-bg.jpg') center/cover no-repeat;
    opacity: 0.45;
    transform: scale(1.04);
    transition: transform 8s ease;
}
.cosmetic-hero:hover .cosmetic-hero__bg { transform: scale(1); }

.cosmetic-hero::before {
    content: '';
    position: absolute;
    left: 80px;
    top: 0; bottom: 0;
    width: 1px;
    background: linear-gradient(to bottom, transparent, var(--brand-gold), transparent);
    opacity: 0.6;
}

.cosmetic-hero::after {
    content: 'COSMETICS';
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

.cosmetic-hero__content {
    position: relative;
    z-index: 2;
    padding: 0 80px 80px;
    max-width: 680px;
}

.cosmetic-hero__eyebrow {
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
.cosmetic-hero__eyebrow::before {
    content: '';
    display: block;
    width: 40px; height: 1px;
    background: var(--brand-gold);
}

.cosmetic-hero__title {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 300;
    font-size: clamp(3rem, 6vw, 5.5rem);
    color: var(--pure-white);
    line-height: 1.05;
    margin-bottom: 28px;
}
.cosmetic-hero__title em {
    font-style: italic;
    color: var(--gold-light);
}

.cosmetic-hero__sub {
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

/* ── LAYOUT ──────────────────────────────────────────────── */
.container-xl {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 60px;
}

/* ── CATEGORY SECTION ────────────────────────────────────── */
.cat-section {
    padding: var(--section-gap) 0;
}
.cat-section + .cat-section {
    border-top: 1px solid var(--gold-light);
}

/* Dark alternate section */
.cat-section--dark {
    background: var(--the-dark);
}
.cat-section--dark .cat-section__title,
.cat-section--dark .cat-section__desc,
.cat-section--dark .section-label { color: var(--pure-white); }
.cat-section--dark .cat-section__desc { color: rgba(255,255,255,0.45); }
.cat-section--dark .pcard { background: var(--studio-mid); }
.cat-section--dark .pcard__body { border-color: rgba(201,169,110,0.2); }
.cat-section--dark .pcard__name { color: var(--pure-white); }
.cat-section--dark .pcard__size { color: rgba(255,255,255,0.35); }
.cat-section--dark .pcard__price { color: var(--gold-light); }
.cat-section--dark .btn-ghost {
    border-color: rgba(255,255,255,0.3);
    color: var(--pure-white);
}
.cat-section--dark .btn-ghost:hover {
    background: var(--brand-gold);
    border-color: var(--brand-gold);
    color: var(--pure-white);
}

/* Section head */
.cat-head {
    margin-bottom: 56px;
}
.section-label {
    font-family: 'DM Sans', sans-serif;
    font-size: 0.68rem;
    font-weight: 500;
    letter-spacing: 5px;
    text-transform: uppercase;
    color: var(--brand-gold);
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 16px;
}
.section-label::after {
    content: '';
    flex: 1; max-width: 50px;
    height: 1px;
    background: var(--brand-gold);
}
.cat-section__title {
    font-size: clamp(2rem, 3.5vw, 3rem);
    margin-bottom: 16px;
}
.cat-section__title span { font-weight: 300; font-style: italic; }
.cat-section__desc {
    font-size: 0.95rem;
    color: var(--muted-text);
    line-height: 1.8;
    max-width: 640px;
}

/* ── PRODUCT GRID ────────────────────────────────────────── */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 2px;
}

/* Product card */
.pcard {
    background: var(--pure-white);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    opacity: 0;
    transform: translateY(20px);
    animation: fadeUp 0.55s ease forwards;
}
@keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }
.pcard:nth-child(1) { animation-delay: 0.05s; }
.pcard:nth-child(2) { animation-delay: 0.13s; }
.pcard:nth-child(3) { animation-delay: 0.21s; }
.pcard:nth-child(4) { animation-delay: 0.29s; }

/* Image */
.pcard__img-wrap {
    position: relative;
    width: 100%;
    padding-top: 115%;
    overflow: hidden;
    background: #ede9e2;
}
.pcard__img-wrap img {
    position: absolute;
    inset: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.7s cubic-bezier(0.165, 0.84, 0.44, 1),
                filter 0.5s ease;
    filter: brightness(0.93);
}
.pcard:hover .pcard__img-wrap img {
    transform: scale(1.07);
    filter: brightness(1);
}

/* Hover overlay */
.pcard__overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(26,26,26,0.6) 0%, transparent 55%);
    opacity: 0;
    transition: opacity 0.4s;
    pointer-events: none;
    display: flex;
    align-items: flex-end;
    padding: 24px;
}
.pcard:hover .pcard__overlay { opacity: 1; }
.pcard__overlay-hint {
    font-size: 0.65rem;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--gold-light);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
}
.pcard__overlay-hint::before {
    content: '';
    display: block;
    width: 20px; height: 1px;
    background: var(--brand-gold);
}

/* Category tag on image */
.pcard__cat-tag {
    position: absolute;
    top: 18px; left: 18px;
    z-index: 2;
    background: rgba(26,26,26,0.68);
    backdrop-filter: blur(5px);
    color: var(--gold-light);
    font-size: 0.6rem;
    font-weight: 500;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    padding: 6px 12px;
}

/* Body */
.pcard__body {
    padding: 24px 24px 28px;
    border: 1px solid var(--gold-light);
    border-top: none;
    flex: 1;
    display: flex;
    flex-direction: column;
}
.pcard__name {
    font-size: 1.25rem;
    margin-bottom: 4px;
}
.pcard__size {
    font-size: 0.7rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--muted-text);
    font-weight: 500;
    margin-bottom: 16px;
}
.pcard__divider {
    width: 24px; height: 1px;
    background: var(--brand-gold);
    margin-bottom: 16px;
}
.pcard__footer {
    margin-top: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 18px;
    border-top: 1px solid rgba(201,169,110,0.18);
}
.pcard__price {
    font-family: 'DM Sans', sans-serif;
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--the-dark);
}
.btn-ghost {
    background: transparent;
    border: 1px solid var(--the-dark);
    color: var(--the-dark);
    font-family: 'DM Sans', sans-serif;
    font-size: 0.65rem;
    font-weight: 500;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    padding: 8px 18px;
    cursor: pointer;
    transition: background 0.25s, color 0.25s, border-color 0.25s;
    white-space: nowrap;
}
.btn-ghost:hover {
    background: var(--the-dark);
    color: var(--pure-white);
}

/* Empty state */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 64px 0;
}
.empty-state p {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.2rem;
    font-style: italic;
    color: var(--muted-text);
}

/* ── MODAL ───────────────────────────────────────────────── */
.modal-backdrop { backdrop-filter: blur(6px); }

#cosmeticDetailsModal .modal-content {
    background: var(--studio-surface);
    border: none;
    border-radius: 0;
    box-shadow: 0 40px 120px rgba(0,0,0,0.28);
    overflow: hidden;
}

.modal-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 580px;
}

.modal-split__image {
    position: relative;
    overflow: hidden;
    background: #ede9e2;
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
    background: linear-gradient(to top, rgba(26,26,26,0.25) 0%, transparent 55%);
    pointer-events: none;
}
.modal-cat-tag {
    position: absolute;
    bottom: 24px; left: 24px;
    z-index: 2;
    background: rgba(26,26,26,0.7);
    backdrop-filter: blur(5px);
    color: var(--gold-light);
    font-size: 0.62rem;
    font-weight: 500;
    letter-spacing: 3px;
    text-transform: uppercase;
    padding: 7px 14px;
}

.modal-split__body {
    padding: 60px 52px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    overflow-y: auto;
    max-height: 90vh;
}

.modal-close-btn {
    position: absolute;
    top: 22px; right: 22px;
    background: none; border: none;
    cursor: pointer;
    font-size: 1.1rem;
    color: var(--muted-text);
    opacity: 0.6;
    transition: opacity 0.2s;
    width: 30px; height: 30px;
    display: flex; align-items: center; justify-content: center;
}
.modal-close-btn:hover { opacity: 1; }

.modal-eyebrow {
    font-size: 0.65rem;
    font-weight: 500;
    letter-spacing: 5px;
    text-transform: uppercase;
    color: var(--brand-gold);
    margin-bottom: 12px;
}

#modalProductName {
    font-size: clamp(1.8rem, 3vw, 2.5rem);
    margin-bottom: 0;
}

.modal-divider {
    width: 36px; height: 1px;
    background: var(--brand-gold);
    margin: 22px 0;
}

.modal-size-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.68rem;
    font-weight: 500;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--muted-text);
    border: 1px solid var(--gold-light);
    background: var(--pure-white);
    padding: 7px 16px;
    margin-bottom: 24px;
}

.modal-desc {
    font-size: 0.95rem;
    color: var(--muted-text);
    line-height: 1.85;
    flex: 1;
    margin-bottom: 0;
}

.modal-footer-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-top: 32px;
    padding-top: 28px;
    border-top: 1px solid var(--gold-light);
}

.modal-price-label {
    font-size: 0.62rem;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--muted-text);
    margin-bottom: 6px;
}

#modalProductPrice {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2rem;
    font-weight: 600;
    line-height: 1;
    color: var(--the-dark);
}

/* ── RESPONSIVE ──────────────────────────────────────────── */
@media (max-width: 1024px) {
    .container-xl { padding: 0 32px; }
    .cosmetic-hero__content { padding: 0 32px 64px; }
    .cosmetic-hero::before { left: 32px; }
    .scroll-cue { right: 32px; }
}
@media (max-width: 768px) {
    :root { --section-gap: 72px; }
    .container-xl { padding: 0 20px; }
    .cosmetic-hero__content { padding: 0 20px 52px; }
    .cosmetic-hero::before { display: none; }
    .scroll-cue { right: 20px; }
    .products-grid { grid-template-columns: 1fr 1fr; }
    .modal-split { grid-template-columns: 1fr; }
    .modal-split__image { min-height: 260px; }
    .modal-split__body { padding: 36px 28px; max-height: none; }
}
@media (max-width: 420px) {
    .products-grid { grid-template-columns: 1fr; }
}
</style>

<!-- ══════════════════════════════════════════════════════════
     HERO
     ══════════════════════════════════════════════════════════ -->
<section class="cosmetic-hero">
    <div class="cosmetic-hero__bg"></div>

    <div class="cosmetic-hero__content">
        <p class="cosmetic-hero__eyebrow">Lumiére Bliss</p>
        <h1 class="cosmetic-hero__title">L'Art de<br><em>Vivre</em></h1>
        <p class="cosmetic-hero__sub">Artisanal rituals and curations to carry Lumiére into your private sanctuary.</p>
    </div>

    <div class="scroll-cue">
        <div class="scroll-cue__line"></div>
        <span>Scroll</span>
    </div>
</section>


<!-- ══════════════════════════════════════════════════════════
     SCENTED CANDLES
     ══════════════════════════════════════════════════════════ -->
<section class="cat-section">
    <div class="container-xl">
        <div class="cat-head">
            <p class="section-label">Atmospheric</p>
            <h2 class="cat-section__title">Scented <span>Candles</span></h2>
            <p class="cat-section__desc">Crafted from 100% natural soy wax, poured in minimalist matte charcoal glass jars, featuring lead-free wooden wicks that emit a soothing crackle.</p>
        </div>

        <div class="products-grid">
            <?php foreach ($candles as $item): ?>
                <article class="pcard">
                    <div class="pcard__img-wrap">
                        <img src="../assets/img/cosmetics/<?= htmlspecialchars($item['image']) ?>"
                             alt="<?= htmlspecialchars($item['name']) ?>">
                        <span class="pcard__cat-tag">Scented Candle</span>
                        <div class="pcard__overlay">
                            <span class="pcard__overlay-hint">Discover</span>
                        </div>
                    </div>
                    <div class="pcard__body">
                        <h3 class="pcard__name"><?= htmlspecialchars($item['name']) ?></h3>
                        <p class="pcard__size"><?= htmlspecialchars($item['size']) ?></p>
                        <div class="pcard__divider"></div>
                        <div class="pcard__footer">
                            <span class="pcard__price">₱<?= number_format($item['price'], 2) ?></span>
                            <button class="btn-ghost"
                                    onclick='showCosmeticDetails(<?= json_encode($item) ?>)'>
                                See More
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if (empty($candles)): ?>
                <div class="empty-state">
                    <p>Our hand-poured candle collection is currently curing.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>


<!-- ══════════════════════════════════════════════════════════
     ESSENTIAL OILS — DARK VARIANT
     ══════════════════════════════════════════════════════════ -->
<section class="cat-section cat-section--dark">
    <div class="container-xl">
        <div class="cat-head">
            <p class="section-label">Therapeutic Grade</p>
            <h2 class="cat-section__title" style="color:var(--pure-white);">Essential <span>Oils</span></h2>
            <p class="cat-section__desc">100% pure, single-origin botanical extracts housed in UV-protective amber glass droppers with minimalist gold-foiled labels.</p>
        </div>

        <div class="products-grid">
            <?php foreach ($oils as $item): ?>
                <article class="pcard">
                    <div class="pcard__img-wrap">
                        <img src="../assets/img/cosmetics/<?= htmlspecialchars($item['image']) ?>"
                             alt="<?= htmlspecialchars($item['name']) ?>">
                        <span class="pcard__cat-tag">Essential Oil</span>
                        <div class="pcard__overlay">
                            <span class="pcard__overlay-hint">Discover</span>
                        </div>
                    </div>
                    <div class="pcard__body">
                        <h3 class="pcard__name"><?= htmlspecialchars($item['name']) ?></h3>
                        <p class="pcard__size"><?= htmlspecialchars($item['size']) ?></p>
                        <div class="pcard__divider"></div>
                        <div class="pcard__footer">
                            <span class="pcard__price">₱<?= number_format($item['price'], 2) ?></span>
                            <button class="btn-ghost"
                                    onclick='showCosmeticDetails(<?= json_encode($item) ?>)'>
                                See More
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if (empty($oils)): ?>
                <div class="empty-state">
                    <p>Our essential oil collection is currently being replenished.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>


<!-- ══════════════════════════════════════════════════════════
     SPA ACCESSORIES
     ══════════════════════════════════════════════════════════ -->
<section class="cat-section">
    <div class="container-xl">
        <div class="cat-head">
            <p class="section-label">Curated Self-Care</p>
            <h2 class="cat-section__title">Spa <span>Accessories</span></h2>
            <p class="cat-section__desc">Premium tactical self-care items curated to extend the Lumiére experience into your private sanctuary.</p>
        </div>

        <div class="products-grid">
            <?php foreach ($accessories as $item): ?>
                <article class="pcard">
                    <div class="pcard__img-wrap">
                        <img src="../assets/img/cosmetics/<?= htmlspecialchars($item['image']) ?>"
                             alt="<?= htmlspecialchars($item['name']) ?>">
                        <span class="pcard__cat-tag">Spa Accessory</span>
                        <div class="pcard__overlay">
                            <span class="pcard__overlay-hint">Discover</span>
                        </div>
                    </div>
                    <div class="pcard__body">
                        <h3 class="pcard__name"><?= htmlspecialchars($item['name']) ?></h3>
                        <p class="pcard__size"><?= htmlspecialchars($item['size']) ?></p>
                        <div class="pcard__divider"></div>
                        <div class="pcard__footer">
                            <span class="pcard__price">₱<?= number_format($item['price'], 2) ?></span>
                            <button class="btn-ghost"
                                    onclick='showCosmeticDetails(<?= json_encode($item) ?>)'>
                                See More
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if (empty($accessories)): ?>
                <div class="empty-state">
                    <p>Curated accessories are arriving soon.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>


<!-- ══════════════════════════════════════════════════════════
     PRODUCT DETAIL MODAL
     ══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="cosmeticDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="modal-split">

                    <!-- Left: Image -->
                    <div class="modal-split__image">
                        <img id="modalProductImage" src="" alt="">
                        <span id="modalProductCategory" class="modal-cat-tag"></span>
                    </div>

                    <!-- Right: Info -->
                    <div class="modal-split__body">
                        <button class="modal-close-btn" data-bs-dismiss="modal" aria-label="Close">✕</button>

                        <p class="modal-eyebrow">Product Detail</p>
                        <h2 id="modalProductName"></h2>
                        <div class="modal-divider"></div>

                        <span class="modal-size-badge">
                            <i class="bi bi-rulers" style="color:var(--brand-gold);"></i>
                            Size: <span id="modalProductSize"></span>
                        </span>

                        <p id="modalProductDescription" class="modal-desc"></p>

                        <div class="modal-footer-row">
                            <div>
                                <p class="modal-price-label">Price</p>
                                <p id="modalProductPrice"></p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function showCosmeticDetails(item) {
    // 1. Get the modal element
    const modalElement = document.getElementById('cosmeticDetailsModal');
    const bsModal = new bootstrap.Modal(modalElement);

    // 2. Fill in the information
    document.getElementById('modalProductName').innerText     = item.name;
    document.getElementById('modalProductCategory').innerText = item.category;
    document.getElementById('modalProductPrice').innerText    = '₱' + parseFloat(item.price).toLocaleString(undefined, { minimumFractionDigits: 2 });
    document.getElementById('modalProductSize').innerText     = item.size;
    document.getElementById('modalProductDescription').innerText = item.description;

    // 3. Set the image path
    const imagePath = '../assets/img/cosmetics/' + (item.image ? item.image : 'placeholder.jpg');
    document.getElementById('modalProductImage').src = imagePath;
    document.getElementById('modalProductImage').alt = item.name;

    // 4. Show the modal
    bsModal.show();
}
</script>