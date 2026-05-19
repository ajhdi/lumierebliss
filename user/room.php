<?php
include '../includes/header.php';
require_once '../config/db.php';

// Fetch ALL rooms (excluding archived)
$stmt = $pdo->query("SELECT * FROM rooms WHERE status != 'archived' ORDER BY room_type ASC, room_name ASC");
$rooms = $stmt->fetchAll();
?>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
/* ============================================================
   LUMIÈRE — PRIVATE SUITES PAGE
   Mirrors Signature Rituals aesthetic system
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
    --lumiere-glow:   linear-gradient(135deg, var(--the-dark), var(--studio-mid));
    --section-gap:    120px;
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
.suite-hero {
    position: relative;
    min-height: 88vh;
    display: flex;
    align-items: flex-end;
    justify-content: flex-start;
    overflow: hidden;
    background: var(--the-dark);
}

.suite-hero__bg {
    position: absolute;
    inset: 0;
    background: url('../assets/img/room/hero-bg.jpg') center/cover no-repeat;
    opacity: 0.45;
    transform: scale(1.04);
    transition: transform 8s ease;
}
.suite-hero:hover .suite-hero__bg { transform: scale(1); }

/* Vertical gold accent line */
.suite-hero::before {
    content: '';
    position: absolute;
    left: 80px;
    top: 0; bottom: 0;
    width: 1px;
    background: linear-gradient(to bottom, transparent, var(--brand-gold), transparent);
    opacity: 0.6;
    z-index: 1;
}

.suite-hero::after {
    content: 'ROOMS';
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

.suite-hero__content {
    position: relative;
    z-index: 2;
    padding: 0 80px 80px;
    max-width: 680px;
}

.suite-hero__eyebrow {
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
.suite-hero__eyebrow::before {
    content: '';
    display: block;
    width: 40px; height: 1px;
    background: var(--brand-gold);
}

.suite-hero__title {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 300;
    font-size: clamp(3rem, 6vw, 5.5rem);
    color: var(--pure-white);
    line-height: 1.05;
    margin-bottom: 28px;
}
.suite-hero__title em {
    font-style: italic;
    color: var(--gold-light);
}

.suite-hero__sub {
    font-size: 1rem;
    color: rgba(255,255,255,0.55);
    font-weight: 300;
    max-width: 420px;
    letter-spacing: 0.01em;
}

/* Scroll indicator */
.scroll-cue {
    position: absolute;
    bottom: 40px; right: 80px;
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
    width: 1px; height: 60px;
    background: linear-gradient(to bottom, var(--brand-gold), transparent);
    animation: scrollPulse 2s ease-in-out infinite;
}
@keyframes scrollPulse {
    0%, 100% { opacity: 0.4; transform: scaleY(1); }
    50%       { opacity: 1;   transform: scaleY(0.7); }
}

/* ── SECTION ANATOMY ──────────────────────────────────────── */
.section-wrap { padding: var(--section-gap) 0; }

.section-head { margin-bottom: 64px; }
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
    max-width: 60px; height: 1px;
    background: var(--brand-gold);
}
.section-head__title { font-size: clamp(2.2rem, 4vw, 3.5rem); }
.section-head__title span { font-weight: 300; font-style: italic; }

/* ── ROOM CARDS GRID ──────────────────────────────────────── */
.rooms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2px;
}

.rcard {
    position: relative;
    background: var(--pure-white);
    overflow: hidden;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    opacity: 0;
    transform: translateY(28px);
    animation: fadeUp 0.6s ease forwards;
}
@keyframes fadeUp {
    to { opacity: 1; transform: translateY(0); }
}
.rcard:nth-child(1) { animation-delay: 0.05s; }
.rcard:nth-child(2) { animation-delay: 0.12s; }
.rcard:nth-child(3) { animation-delay: 0.19s; }
.rcard:nth-child(4) { animation-delay: 0.26s; }
.rcard:nth-child(5) { animation-delay: 0.33s; }
.rcard:nth-child(6) { animation-delay: 0.40s; }
.rcard:nth-child(7) { animation-delay: 0.47s; }
.rcard:nth-child(8) { animation-delay: 0.54s; }

.rcard__image-wrap {
    position: relative;
    height: 320px;
    overflow: hidden;
}
.rcard__image-wrap img {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.7s cubic-bezier(0.165, 0.84, 0.44, 1);
    filter: brightness(0.92);
}
.rcard:hover .rcard__image-wrap img {
    transform: scale(1.06);
    filter: brightness(1);
}
.rcard__image-wrap::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, transparent 55%, rgba(26,26,26,0.55) 100%);
    pointer-events: none;
}

/* Fallback placeholder */
.rcard__placeholder {
    width: 100%; height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: linear-gradient(135deg, var(--studio-surface), #f3ede0);
    color: var(--brand-gold);
}
.rcard__placeholder i { font-size: 2.5rem; }
.rcard__placeholder span {
    font-size: 0.62rem;
    text-transform: uppercase;
    letter-spacing: 2px;
    opacity: 0.7;
}

/* Type badge top-right */
.rcard__type-tag {
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

/* Status dot — available/unavailable */
.rcard__status {
    position: absolute;
    top: 18px; left: 18px;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.62rem;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    font-weight: 700;
    padding: 5px 12px;
    backdrop-filter: blur(6px);
}
.rcard__status--available {
    background: rgba(90,138,90,0.75);
    color: #d4f0d4;
}
.rcard__status--unavailable {
    background: rgba(180,60,60,0.70);
    color: #fdd;
}

.rcard__body {
    padding: 28px 28px 32px;
    flex: 1;
    display: flex;
    flex-direction: column;
    border-bottom: 1px solid var(--gold-light);
    border-left: 1px solid var(--gold-light);
    border-right: 1px solid var(--gold-light);
}

.rcard__name {
    font-size: 1.35rem;
    margin-bottom: 6px;
    font-family: 'Cormorant Garamond', serif;
    font-weight: 600;
    color: var(--the-dark);
}

.rcard__divider {
    width: 32px; height: 1px;
    background: var(--brand-gold);
    margin: 14px 0;
}

.rcard__footer {
    margin-top: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 20px;
    border-top: 1px solid rgba(201,169,110,0.2);
}

.rcard__fee {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--the-dark);
}
.rcard__fee--free {
    font-size: 0.8rem;
    font-family: 'DM Sans', sans-serif;
    font-weight: 500;
    letter-spacing: 1px;
    color: var(--muted-text);
    text-transform: uppercase;
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
.btn-ghost:disabled,
.btn-ghost[disabled] {
    opacity: 0.35;
    cursor: not-allowed;
    pointer-events: none;
}

/* ── INTERLUDE BANNER ──────────────────────────────────────── */
.interlude {
    background: var(--the-dark);
    padding: 80px 0;
    position: relative;
    overflow: hidden;
}
.interlude::before {
    content: 'LUMIÈRE';
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
.interlude__inner { text-align: center; position: relative; z-index: 1; }
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

/* ── EMPTY STATE ──────────────────────────────────────────── */
.empty-sanctuary {
    border: 1px dashed var(--brand-gold);
    background: var(--pure-white);
    border-radius: 20px;
    padding: 3.5rem 2rem;
    text-align: center;
}
.empty-sanctuary i {
    background: linear-gradient(135deg, var(--brand-gold), var(--gold-light));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    display: inline-block;
}

/* ── MODAL ─────────────────────────────────────────────────── */
.modal-backdrop { backdrop-filter: blur(6px); }

#suiteModal .modal-content {
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
    background: var(--the-dark);
}
.modal-split__image img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
}
.modal-split__image__placeholder {
    width: 100%; height: 100%;
    min-height: 360px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14px;
    color: var(--brand-gold);
    background: linear-gradient(135deg, #1a1a1a, #2e2e2e);
}
.modal-split__image__placeholder i { font-size: 3rem; }
.modal-split__image::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(to right, transparent, rgba(253,251,247,0.06));
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

#suiteModalName {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(1.8rem, 3vw, 2.4rem);
    font-weight: 600;
    margin-bottom: 6px;
}

.modal-type-row {
    font-size: 0.78rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--muted-text);
    font-weight: 500;
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.modal-type-row::before {
    content: '';
    display: block;
    width: 20px; height: 1px;
    background: var(--muted-text);
}

.modal-footer-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
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
#suiteModalFee {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2rem;
    font-weight: 600;
    line-height: 1;
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
.btn-luxe--disabled {
    opacity: 0.35;
    pointer-events: none;
    cursor: not-allowed;
}

/* Status pill inside modal */
.modal-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    padding: 5px 14px;
    margin-bottom: 28px;
    align-self: flex-start;
}
.modal-status-pill--available { background: rgba(90,138,90,0.12); color: #4a7a4a; }
.modal-status-pill--unavailable { background: rgba(180,60,60,0.1); color: #b43c3c; }
.modal-status-pill::before {
    content: '';
    width: 5px; height: 5px;
    border-radius: 50%;
    background: currentColor;
}

/* ── UTILITIES ───────────────────────────────────────────── */
.container-xl { max-width: 1400px; margin: 0 auto; padding: 0 60px; }

@media (max-width: 991px) {
    .container-xl { padding: 0 28px; }
    .suite-hero::before { left: 28px; }
    .suite-hero__content { padding: 0 28px 60px; }
    .scroll-cue { right: 28px; }
    .modal-split { grid-template-columns: 1fr; }
    .modal-split__image { height: 260px; }
    .modal-split__body { padding: 36px 28px; }
    :root { --section-gap: 72px; }
}
@media (max-width: 640px) {
    .rooms-grid { grid-template-columns: 1fr 1fr; gap: 2px; }
    .rcard__image-wrap { height: 220px; }
}
@media (max-width: 420px) {
    .rooms-grid { grid-template-columns: 1fr; }
}
</style>


<!-- ══════════════════════════════════════════════════════════
     HERO
     ══════════════════════════════════════════════════════════ -->
<section class="suite-hero">
    <div class="suite-hero__bg"></div>

    <div class="suite-hero__content">
        <p class="suite-hero__eyebrow">Lumiére Curations</p>
        <h1 class="suite-hero__title">Private<br><em>Suites</em></h1>
        <p class="suite-hero__sub">Tranquil spaces designed for complete disconnect, absolute privacy, and structured restoration.</p>
    </div>

    <div class="scroll-cue">
        <div class="scroll-cue__line"></div>
        <span>Scroll</span>
    </div>
</section>


<!-- ══════════════════════════════════════════════════════════
     ROOMS GRID
     ══════════════════════════════════════════════════════════ -->
<section class="section-wrap">
    <div class="container-xl">
        <div class="section-head">
            <p class="section-head__label">Our Sanctuaries</p>
            <h2 class="section-head__title">All <span>Suites &amp; Rooms</span></h2>
        </div>

        <?php if (!empty($rooms)): ?>
            <div class="rooms-grid">
                <?php foreach ($rooms as $r):
                    $is_available = strtolower($r['status']) === 'available';
                    $has_image    = !empty($r['room_image']);
                    $fee_zero     = floatval($r['additional_fee']) == 0;
                ?>
                    <article class="rcard">
                        <div class="rcard__image-wrap">

                            <?php if ($has_image): ?>
                                <img src="../assets/img/room/<?= htmlspecialchars($r['room_image']) ?>"
                                     alt="<?= htmlspecialchars($r['room_name']) ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="rcard__placeholder">
                                    <i class="bi bi-door-open-fill"></i>
                                    <span>Lumiére Space</span>
                                </div>
                            <?php endif; ?>

                            <!-- Status badge -->
                            <span class="rcard__status <?= $is_available ? 'rcard__status--available' : 'rcard__status--unavailable' ?>">
                                <?= $is_available ? 'Available' : ucfirst(htmlspecialchars($r['status'])) ?>
                            </span>

                            <!-- Type tag -->
                            <span class="rcard__type-tag">
                                <?= htmlspecialchars($r['room_type']) ?>
                            </span>

                        </div>

                        <div class="rcard__body">
                            <h3 class="rcard__name"><?= htmlspecialchars($r['room_name']) ?></h3>
                            <div class="rcard__divider"></div>

                            <div class="rcard__footer">
                                <?php if ($fee_zero): ?>
                                    <span class="rcard__fee rcard__fee--free">No extra fee</span>
                                <?php else: ?>
                                    <span class="rcard__fee">₱<?= number_format($r['additional_fee'], 2) ?></span>
                                <?php endif; ?>

                                <button class="btn-ghost"
                                        onclick='showSuiteDetails(<?= json_encode($r) ?>)'
                                        <?= !$is_available ? 'disabled' : '' ?>>
                                    <?= $is_available ? 'Discover' : 'Unavailable' ?>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="col-md-8 mx-auto text-center pt-2 pb-5">
                <div class="empty-sanctuary">
                    <i class="bi bi-moon-stars display-3 mb-4"></i>
                    <h4 class="serif mb-3" style="font-size: 2.2rem;">Sanctuary At Capacity</h4>
                    <p style="font-size: 0.95rem; color: var(--muted-text); max-width: 480px; margin: 0 auto;">
                        All suites are currently hosting active rituals or undergoing scheduled maintenance curation.
                        Please verify alternatives later or contact the concierge.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>


<!-- ══════════════════════════════════════════════════════════
     INTERLUDE QUOTE
     ══════════════════════════════════════════════════════════ -->
<div class="interlude">
    <div class="interlude__inner">
        <p class="interlude__quote">"Space is not merely a backdrop — it is the first ritual."</p>
        <p class="interlude__attr">— The Lumiére Philosophy</p>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     SUITE DETAIL MODAL
     ══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="suiteModal" tabindex="-1" aria-labelledby="suiteModalName" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-split">

                <!-- Image Panel -->
                <div class="modal-split__image">
                    <img id="suiteModalImage" src="" alt="" style="display:none;">
                    <div id="suiteModalPlaceholder" class="modal-split__image__placeholder">
                        <i class="bi bi-door-open-fill"></i>
                        <span style="font-size:0.62rem;letter-spacing:2px;text-transform:uppercase;opacity:.6;">Lumiére Space</span>
                    </div>
                </div>

                <!-- Info Panel -->
                <div class="modal-split__body">
                    <button class="modal-close" data-bs-dismiss="modal" aria-label="Close">✕</button>

                    <p class="modal-eyebrow">Suite Profile</p>
                    <h2 id="suiteModalName"></h2>
                    <p class="modal-type-row" id="suiteModalType"></p>
                    <span id="suiteModalStatusPill" class="modal-status-pill"></span>

                    <div class="modal-footer-row">
                        <div>
                            <p class="modal-price-label">Additional Fee</p>
                            <p id="suiteModalFee"></p>
                        </div>
                        <a id="suiteModalBookBtn" href="#" class="btn-luxe">Book This Suite</a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>


<?php include '../includes/footer.php'; ?>

<script>
function showSuiteDetails(data) {
    // Name & type
    document.getElementById('suiteModalName').innerText = data.room_name;
    document.getElementById('suiteModalType').innerText = data.room_type;

    // Status pill
    const pill    = document.getElementById('suiteModalStatusPill');
    const avail   = data.status.toLowerCase() === 'available';
    pill.className = 'modal-status-pill ' + (avail ? 'modal-status-pill--available' : 'modal-status-pill--unavailable');
    pill.innerText = data.status.charAt(0).toUpperCase() + data.status.slice(1);

    // Fee
    const fee = parseFloat(data.additional_fee);
    document.getElementById('suiteModalFee').innerText = fee > 0
        ? '₱' + fee.toLocaleString(undefined, { minimumFractionDigits: 2 })
        : 'No additional fee';

    // Image
    const img         = document.getElementById('suiteModalImage');
    const placeholder = document.getElementById('suiteModalPlaceholder');
    if (data.room_image && data.room_image !== '') {
        img.src              = '../assets/img/room/' + data.room_image;
        img.alt              = data.room_name;
        img.style.display    = 'block';
        placeholder.style.display = 'none';
    } else {
        img.style.display         = 'none';
        placeholder.style.display = 'flex';
    }

    // Book button
    const btn = document.getElementById('suiteModalBookBtn');
    btn.href  = 'appointment.php?rid=' + data.room_id;
    if (!avail) {
        btn.classList.add('btn-luxe--disabled');
        btn.innerText = 'Currently Unavailable';
    } else {
        btn.classList.remove('btn-luxe--disabled');
        btn.innerText = 'Book This Suite';
    }

    new bootstrap.Modal(document.getElementById('suiteModal')).show();
}
</script>