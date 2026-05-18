<?php
include '../includes/header.php';
require_once '../config/db.php';


$stmt = $pdo->query("SELECT * FROM therapists WHERE status = 'active' ORDER BY first_name ASC");
$therapists = $stmt->fetchAll();


$sched_stmt = $pdo->query("SELECT therapist_id, time_start FROM therapist_schedule ORDER BY time_start ASC");
$all_schedules = $sched_stmt->fetchAll(PDO::FETCH_GROUP);
?>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">

<style>
/* ============================================================
   LUMIÈRE — THE ARTISANS PAGE
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

h1, h2, h3, h4, h5 {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 600;
    color: var(--the-dark);
    letter-spacing: -0.02em;
    line-height: 1.15;
}

/* ── HERO ─────────────────────────────────────────────────── */
.artisans-hero {
    position: relative;
    background: var(--the-dark);
    min-height: 64vh;
    display: flex;
    align-items: flex-end;
    overflow: hidden;
}

/* Decorative large background text */
.artisans-hero::before {
    content: 'ARTISANS';
    position: absolute;
    font-family: 'Cormorant Garamond', serif;
    font-weight: 600;
    font-size: clamp(6rem, 18vw, 18rem);
    color: rgba(255,255,255,0.03);
    bottom: -0.15em;
    right: -0.05em;
    white-space: nowrap;
    pointer-events: none;
    letter-spacing: -0.02em;
    line-height: 1;
}

/* Vertical gold line accent */
.artisans-hero::after {
    content: '';
    position: absolute;
    left: 80px; top: 0; bottom: 0;
    width: 1px;
    background: linear-gradient(to bottom, transparent, var(--brand-gold) 40%, transparent);
    opacity: 0.5;
}

.artisans-hero__content {
    position: relative;
    z-index: 2;
    padding: 0 80px 80px 80px;
}

.artisans-hero__eyebrow {
    font-family: 'DM Sans', sans-serif;
    font-size: 0.7rem;
    font-weight: 500;
    letter-spacing: 5px;
    text-transform: uppercase;
    color: var(--brand-gold);
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}
.artisans-hero__eyebrow::before {
    content: '';
    display: block;
    width: 40px; height: 1px;
    background: var(--brand-gold);
}

.artisans-hero__title {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 300;
    font-size: clamp(2.8rem, 6vw, 5.5rem);
    color: var(--pure-white);
    line-height: 1.05;
    margin-bottom: 20px;
}
.artisans-hero__title em {
    font-style: italic;
    color: var(--gold-light);
}

.artisans-hero__sub {
    font-size: 0.95rem;
    color: rgba(255,255,255,0.45);
    font-weight: 300;
    max-width: 400px;
    letter-spacing: 0.01em;
}

/* ── CONTAINER ───────────────────────────────────────────── */
.container-xl {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 60px;
}

/* ── SECTION HEAD ────────────────────────────────────────── */
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
    margin-bottom: 16px;
}
.section-head__label::after {
    content: '';
    flex: 1; max-width: 60px;
    height: 1px;
    background: var(--brand-gold);
}
.section-head__title {
    font-size: clamp(2rem, 4vw, 3.2rem);
}
.section-head__title span { font-weight: 300; font-style: italic; }

/* ── THERAPIST GRID ──────────────────────────────────────── */
.section-wrap { padding: var(--section-gap) 0; }

.therapists-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2px;
}

/* Individual card */
.tcard {
    position: relative;
    background: var(--pure-white);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    opacity: 0;
    transform: translateY(24px);
    animation: fadeUp 0.55s ease forwards;
}
@keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }
.tcard:nth-child(1)  { animation-delay: 0.05s; }
.tcard:nth-child(2)  { animation-delay: 0.12s; }
.tcard:nth-child(3)  { animation-delay: 0.19s; }
.tcard:nth-child(4)  { animation-delay: 0.26s; }
.tcard:nth-child(5)  { animation-delay: 0.33s; }
.tcard:nth-child(6)  { animation-delay: 0.40s; }
.tcard:nth-child(7)  { animation-delay: 0.47s; }
.tcard:nth-child(8)  { animation-delay: 0.54s; }
.tcard:nth-child(9)  { animation-delay: 0.61s; }

/* Portrait */
.tcard__portrait {
    position: relative;
    width: 100%;
    padding-top: 130%;
    overflow: hidden;
    background: #e8e4de;
}
.tcard__portrait img {
    position: absolute;
    inset: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.7s cubic-bezier(0.165, 0.84, 0.44, 1),
                filter 0.5s ease;
    filter: grayscale(15%) brightness(0.95);
}
.tcard:hover .tcard__portrait img {
    transform: scale(1.06);
    filter: grayscale(0%) brightness(1);
}

/* Placeholder when no photo */
.tcard__portrait-placeholder {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: linear-gradient(145deg, #ede9e1, #d8d0c4);
}
.tcard__portrait-placeholder .initials {
    font-family: 'Cormorant Garamond', serif;
    font-size: 3.5rem;
    font-weight: 300;
    color: var(--muted-text);
    letter-spacing: 0.05em;
}
.tcard__portrait-placeholder .brand-mark {
    font-size: 0.65rem;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: var(--brand-gold);
    font-weight: 500;
}

/* Overlay on hover */
.tcard__overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(26,26,26,0.7) 0%, transparent 55%);
    opacity: 0;
    transition: opacity 0.4s ease;
    display: flex;
    align-items: flex-end;
    padding: 28px;
    pointer-events: none;
}
.tcard:hover .tcard__overlay { opacity: 1; }
.tcard__overlay-cta {
    font-size: 0.68rem;
    font-weight: 500;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--gold-light);
    display: flex;
    align-items: center;
    gap: 12px;
}
.tcard__overlay-cta::before {
    content: '';
    display: block;
    width: 24px; height: 1px;
    background: var(--brand-gold);
}

/* Body */
.tcard__body {
    padding: 24px 26px 28px;
    border: 1px solid var(--gold-light);
    border-top: none;
    flex: 1;
    display: flex;
    flex-direction: column;
}
.tcard__specialty {
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--brand-gold);
    margin-bottom: 8px;
}
.tcard__name {
    font-size: 1.5rem;
    margin-bottom: 0;
}
.tcard__divider {
    width: 28px; height: 1px;
    background: var(--brand-gold);
    margin: 16px 0;
}
.tcard__cta-wrap { margin-top: auto; }
.btn-ghost {
    display: block;
    width: 100%;
    background: transparent;
    border: 1px solid var(--the-dark);
    color: var(--the-dark);
    font-family: 'DM Sans', sans-serif;
    font-size: 0.7rem;
    font-weight: 500;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    padding: 12px 20px;
    cursor: pointer;
    transition: background 0.25s, color 0.25s;
    text-align: center;
}
.btn-ghost:hover {
    background: var(--the-dark);
    color: var(--pure-white);
}

/* ── MODAL ───────────────────────────────────────────────── */
.modal-backdrop { backdrop-filter: blur(6px); }

#therapistDetailModal .modal-content {
    background: var(--studio-surface);
    border: none;
    border-radius: 0;
    box-shadow: 0 40px 120px rgba(0,0,0,0.28);
    overflow: hidden;
}

.modal-split {
    display: grid;
    grid-template-columns: 5fr 7fr;
    min-height: 600px;
}

.modal-split__portrait {
    position: relative;
    overflow: hidden;
    background: #d8d0c4;
}
.modal-split__portrait img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
}
/* Bottom gradient on modal image */
.modal-split__portrait::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(26,26,26,0.35) 0%, transparent 50%);
    pointer-events: none;
}
/* Specialty tag on photo */
.modal-photo-tag {
    position: absolute;
    bottom: 28px; left: 28px;
    z-index: 2;
    background: rgba(26,26,26,0.72);
    backdrop-filter: blur(6px);
    color: var(--gold-light);
    font-size: 0.65rem;
    font-weight: 500;
    letter-spacing: 3px;
    text-transform: uppercase;
    padding: 8px 16px;
}

.modal-split__body {
    padding: 60px 56px;
    display: flex;
    flex-direction: column;
    position: relative;
    overflow-y: auto;
    max-height: 90vh;
}

.modal-close-btn {
    position: absolute;
    top: 24px; right: 24px;
    background: none; border: none;
    cursor: pointer;
    width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    color: var(--muted-text);
    font-size: 1.2rem;
    opacity: 0.6;
    transition: opacity 0.2s;
}
.modal-close-btn:hover { opacity: 1; }

.modal-eyebrow {
    font-size: 0.65rem;
    font-weight: 500;
    letter-spacing: 5px;
    text-transform: uppercase;
    color: var(--brand-gold);
    margin-bottom: 14px;
}

#modalName {
    font-size: clamp(2rem, 3vw, 2.8rem);
    margin-bottom: 0;
}

.modal-divider {
    width: 40px; height: 1px;
    background: var(--brand-gold);
    margin: 24px 0;
}

.modal-section-label {
    font-family: 'DM Sans', sans-serif;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--muted-text);
    margin-bottom: 10px;
}

.modal-gender-value {
    font-size: 1rem;
    color: var(--the-dark);
    margin-bottom: 28px;
}

.modal-experience {
    font-size: 0.95rem;
    color: var(--muted-text);
    line-height: 1.85;
    margin-bottom: 32px;
}

/* Schedule slots */
.schedule-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 0;
}
.schedule-pill {
    font-family: 'DM Sans', sans-serif;
    font-size: 0.72rem;
    font-weight: 500;
    letter-spacing: 1.5px;
    color: var(--the-dark);
    border: 1px solid var(--gold-light);
    background: var(--pure-white);
    padding: 7px 16px;
    white-space: nowrap;
}
.schedule-empty {
    font-size: 0.85rem;
    color: var(--muted-text);
    font-style: italic;
}

/* ── RESPONSIVE ──────────────────────────────────────────── */
@media (max-width: 1024px) {
    .container-xl { padding: 0 32px; }
    .artisans-hero__content { padding: 0 32px 64px; }
    .artisans-hero::after { left: 32px; }
}
@media (max-width: 768px) {
    :root { --section-gap: 72px; }
    .therapists-grid { grid-template-columns: 1fr 1fr; }
    .modal-split { grid-template-columns: 1fr; }
    .modal-split__portrait { min-height: 280px; }
    .modal-split__body { padding: 36px 28px; max-height: none; }
    .container-xl { padding: 0 20px; }
    .artisans-hero__content { padding: 0 20px 52px; }
    .artisans-hero::after { display: none; }
}
@media (max-width: 480px) {
    .therapists-grid { grid-template-columns: 1fr; }
}
</style>

<!-- ══════════════════════════════════════════════════════════
     HERO
     ══════════════════════════════════════════════════════════ -->
<section class="artisans-hero">
    <div class="artisans-hero__content">
        <p class="artisans-hero__eyebrow">Lumiére Studio</p>
        <h1 class="artisans-hero__title">The <em>Artisans</em><br>of Bliss</h1>
        <p class="artisans-hero__sub">Professional hands dedicated to your complete restoration.</p>
    </div>
</section>


<!-- ══════════════════════════════════════════════════════════
     THERAPISTS GRID
     ══════════════════════════════════════════════════════════ -->
<section class="section-wrap">
    <div class="container-xl">
        <div class="section-head">
            <p class="section-head__label">Our Specialists</p>
            <h2 class="section-head__title">Meet the <span>Practitioners</span></h2>
        </div>

        <div class="therapists-grid">
            <?php foreach ($therapists as $th):
                $initials = strtoupper(substr($th['first_name'], 0, 1) . substr($th['last_name'], 0, 1));
            ?>
                <article class="tcard">
                    <!-- Portrait -->
                    <div class="tcard__portrait">
                        <?php if (!empty($th['profile_picture'])): ?>
                            <img src="../assets/img/therapists/<?= htmlspecialchars($th['profile_picture']) ?>"
                                 alt="<?= htmlspecialchars($th['first_name'] . ' ' . $th['last_name']) ?>">
                        <?php else: ?>
                            <div class="tcard__portrait-placeholder">
                                <span class="initials"><?= $initials ?></span>
                                <span class="brand-mark">Lumiére</span>
                            </div>
                        <?php endif; ?>

                        <!-- Hover overlay -->
                        <div class="tcard__overlay">
                            <span class="tcard__overlay-cta">View Profile</span>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="tcard__body">
                        <p class="tcard__specialty"><?= htmlspecialchars($th['specialty']) ?></p>
                        <h3 class="tcard__name"><?= htmlspecialchars($th['first_name'] . ' ' . $th['last_name']) ?></h3>
                        <div class="tcard__divider"></div>
                        <div class="tcard__cta-wrap">
                            <button class="btn-ghost"
                                    onclick='showTherapistDetails(<?= json_encode($th) ?>, <?= json_encode($all_schedules[$th['therapist_id']] ?? []) ?>)'>
                                View Profile
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ══════════════════════════════════════════════════════════
     THERAPIST DETAIL MODAL
     ══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="therapistDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="modal-split">

                    <!-- Left: Portrait -->
                    <div class="modal-split__portrait">
                        <img id="modalImg" src="" alt="Therapist portrait">
                        <span id="modalPhotoTag" class="modal-photo-tag"></span>
                    </div>

                    <!-- Right: Info -->
                    <div class="modal-split__body">
                        <button class="modal-close-btn" data-bs-dismiss="modal" aria-label="Close">✕</button>

                        <p class="modal-eyebrow">Artisans of Bliss</p>
                        <h2 id="modalName"></h2>

                        <div class="modal-divider"></div>

                        <p class="modal-section-label">Practitioner Gender</p>
                        <p id="modalGender" class="modal-gender-value"></p>

                        <p class="modal-section-label">Work Experience</p>
                        <p id="modalExperience" class="modal-experience"></p>

                        <p class="modal-section-label">Availability</p>
                        <div id="modalSchedule" class="schedule-grid"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function showTherapistDetails(data, schedules) {
    // Fill Text Content
    document.getElementById('modalName').innerText = data.first_name + " " + (data.middle_name ? data.middle_name + " " : "") + data.last_name;
    document.getElementById('modalPhotoTag').innerText = data.specialty;
    document.getElementById('modalGender').innerText = data.gender;

    // Work Experience with fallback text
    document.getElementById('modalExperience').innerText = data.work_experience || "Specialist profile is currently being updated with professional background and certifications.";

    // Set Image
    document.getElementById('modalImg').src = data.profile_picture
        ? "../assets/img/therapists/" + data.profile_picture
        : "../assets/img/therapists/default_therapist.png";

    // Build Schedule
    const container = document.getElementById('modalSchedule');
    container.innerHTML = '';

    if (schedules && schedules.length > 0) {
        schedules.forEach(slot => {
            const span = document.createElement('span');
            span.className = 'schedule-pill';

            const time = new Date('1970-01-01T' + slot.time_start + 'Z').toLocaleTimeString('en-US', {
                timeZone: 'UTC', hour: 'numeric', minute: 'numeric', hour12: true
            });

            span.innerText = time;
            container.appendChild(span);
        });
    } else {
        container.innerHTML = '<span class="schedule-empty">No slots available for today.</span>';
    }

    // Show the Modal
    new bootstrap.Modal(document.getElementById('therapistDetailModal')).show();
}
</script>