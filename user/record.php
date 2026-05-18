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
               th.last_name AS therapist_lname,
               r.room_name, r.room_type
        FROM appointments a
        LEFT JOIN treatments t   ON a.treatment_id = t.treatment_id
        LEFT JOIN promotions p   ON a.promo_id = p.promo_id
        LEFT JOIN packages pkg   ON a.package_id = pkg.package_id
        JOIN therapists th       ON a.therapist_id = th.therapist_id
        LEFT JOIN rooms r        ON a.room_id = r.room_id
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
        font-size: 16px;
        color: var(--the-dark);
        line-height: 1.6;
        min-height: 80vh;
    }
    .luxe-records-context h1,
    .luxe-records-context h2,
    .luxe-records-context h3,
    .luxe-records-context h4 {
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
        left: 80px; top: 0; bottom: 0;
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
    .records-hero__title em { font-style: italic; color: var(--gold-light); }
    .records-hero__sub {
        font-size: 1rem;
        color: rgba(255,255,255,0.55);
        font-weight: 300;
        max-width: 420px;
        letter-spacing: 0.01em;
    }
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

    /* ── TABLE SECTION ─────────────────────────────────────────── */
    .table-section-wrapper {
        padding: 0 60px 80px;
    }
    .record-card-luxe {
        border: 1px solid rgba(232, 213, 176, 0.4);
        border-radius: 0 !important;
        background: var(--pure-white);
        box-shadow: 0 4px 24px rgba(26,26,26,0.04);
        overflow: hidden;
    }
    .table-luxe {
        margin-bottom: 0;
        width: 100%;
    }
    .table-luxe thead th {
        background-color: var(--the-dark);
        text-transform: uppercase;
        font-size: 0.68rem;
        letter-spacing: 2px;
        font-weight: 500;
        color: var(--gold-light);
        border: none;
        padding: 14px 18px;
        white-space: nowrap;
    }
    .table-luxe tbody tr {
        transition: background-color 0.25s ease;
    }
    .table-luxe tbody tr:hover {
        background-color: #fdfbf7;
    }
    .table-luxe tbody td {
        padding: 16px 18px;
        vertical-align: middle;
        border-bottom: 1px solid rgba(232, 213, 176, 0.25);
        font-size: 0.88rem;
    }
    .data-primary {
        font-weight: 700;
        color: var(--the-dark);
        font-size: 0.92rem;
    }
    .data-secondary {
        color: var(--muted-text);
        font-size: 0.76rem;
        letter-spacing: 0.3px;
        margin-top: 2px;
    }
    .treatment-title-luxe {
        font-family: 'Cormorant Garamond', serif;
        font-weight: 600;
        font-size: 1.15rem;
        color: var(--the-dark);
        line-height: 1.2;
    }

    /* ── STATUS BADGES ─────────────────────────────────────────── */
    .status-badge-luxe {
        font-size: 0.62rem;
        font-weight: 700;
        padding: 4px 11px;
        border-radius: 0 !important;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        display: inline-block;
        border: 1px solid transparent;
    }
    .status-confirmed { background: #f4fcf7; color: #1c7a43; border-color: #d3f4e0; }
    .status-completed { background: var(--studio-surface); color: var(--muted-text); border-color: var(--gold-light); }
    .status-cancelled { background: #fff8f8; color: #b82323; border-color: #fcd4d4; }

    /* ── ACTION BUTTONS ────────────────────────────────────────── */
    .btn-action {
        font-size: 0.62rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        padding: 5px 13px;
        border-radius: 0;
        cursor: pointer;
        transition: all 0.22s ease;
        display: inline-block;
        border: 1px solid;
        background: transparent;
        text-decoration: none;
        white-space: nowrap;
        line-height: 1.6;
    }
    .btn-action-view {
        border-color: var(--the-dark);
        color: var(--the-dark);
    }
    .btn-action-view:hover {
        background: var(--the-dark);
        color: var(--pure-white);
    }
    .btn-action-cancel {
        border-color: #d9534f;
        color: #d9534f;
    }
    .btn-action-cancel:hover {
        background: #d9534f;
        color: var(--pure-white);
    }
    .btn-action-disabled {
        border-color: #ddd;
        color: #ccc;
        cursor: not-allowed;
        pointer-events: none;
    }
    .actions-col {
        display: flex;
        flex-direction: column;
        gap: 5px;
        align-items: flex-start;
    }

    /* ── EMPTY STATE ───────────────────────────────────────────── */
    .btn-luxe-dark {
        background: var(--lumiere-glow);
        color: var(--pure-white) !important;
        border: none;
        border-radius: 0 !important;
        padding: 12px 32px;
        font-weight: 500;
        transition: all 0.4s ease;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 2px;
        display: inline-block;
        text-decoration: none;
    }
    .btn-luxe-dark:hover { background: var(--brand-gold); }

    /* ── VIEW MODAL ────────────────────────────────────────────── */
    .modal-luxe .modal-content {
        border-radius: 0;
        border: 1px solid var(--gold-light);
        font-family: 'DM Sans', sans-serif;
    }
    .modal-luxe .modal-header {
        background: var(--the-dark);
        border-bottom: 1px solid rgba(232, 213, 176, 0.3);
        padding: 20px 28px;
        border-radius: 0;
    }
    .modal-luxe .modal-title {
        font-family: 'Cormorant Garamond', serif;
        font-weight: 400;
        font-size: 1.6rem;
        color: var(--pure-white);
    }
    .modal-luxe .btn-close { filter: invert(1) opacity(0.6); }
    .modal-luxe .modal-body {
        background: var(--studio-surface);
        padding: 28px;
    }
    .modal-luxe .modal-footer {
        background: var(--studio-surface);
        border-top: 1px solid rgba(232, 213, 176, 0.3);
        padding: 16px 28px;
    }
    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 12px 0;
        border-bottom: 1px solid rgba(232, 213, 176, 0.3);
    }
    .detail-row:last-child { border-bottom: none; }
    .detail-label {
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--muted-text);
        font-weight: 500;
        min-width: 130px;
    }
    .detail-value {
        font-size: 0.9rem;
        color: var(--the-dark);
        font-weight: 500;
        text-align: right;
    }
    .detail-value.serif {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.1rem;
    }
    .modal-total-row {
        background: var(--the-dark);
        padding: 14px 18px;
        margin-top: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-total-label {
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 2px;
        color: var(--gold-light);
    }
    .modal-total-value {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        color: var(--pure-white);
        font-weight: 600;
    }

    /* ── CANCEL MODAL ──────────────────────────────────────────── */
    .cancel-warning-box {
        background: #fff8f8;
        border: 1px solid #fcd4d4;
        padding: 14px 18px;
        display: flex;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 20px;
    }
    .cancel-warning-box i { color: #d9534f; font-size: 1.1rem; margin-top: 2px; }
    .cancel-warning-text { font-size: 0.85rem; color: #b82323; line-height: 1.5; }
    .cancel-form-label {
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--muted-text);
        font-weight: 600;
        margin-bottom: 6px;
        display: block;
    }
    .cancel-form-control {
        border-radius: 0;
        border: 1px solid rgba(232,213,176,0.6);
        background: var(--pure-white);
        font-size: 0.88rem;
        padding: 10px 14px;
        width: 100%;
        resize: none;
        outline: none;
        font-family: 'DM Sans', sans-serif;
        transition: border-color 0.2s;
    }
    .cancel-form-control:focus { border-color: var(--brand-gold); box-shadow: none; }
    .btn-confirm-cancel {
        background: #d9534f;
        color: #fff;
        border: none;
        border-radius: 0;
        padding: 10px 28px;
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        cursor: pointer;
        transition: background 0.22s ease;
        font-family: 'DM Sans', sans-serif;
    }
    .btn-confirm-cancel:hover { background: #b82323; }
    .btn-modal-close {
        background: transparent;
        color: var(--muted-text);
        border: 1px solid rgba(232,213,176,0.6);
        border-radius: 0;
        padding: 10px 28px;
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        cursor: pointer;
        transition: all 0.22s ease;
        font-family: 'DM Sans', sans-serif;
    }
    .btn-modal-close:hover { border-color: var(--the-dark); color: var(--the-dark); }

    /* ── TOOLTIP for locked ────────────────────────────────────── */
    .lock-note {
        font-size: 0.65rem;
        color: var(--muted-text);
        letter-spacing: 0.3px;
        margin-top: 2px;
    }

    @media (max-width: 1024px) {
        .table-section-wrapper { padding: 0 24px 60px; }
        .records-hero__content { padding: 0 32px 64px; }
        .records-hero::before  { left: 32px; }
        .scroll-cue            { right: 32px; }
    }
    @media (max-width: 768px) {
        .table-section-wrapper { padding: 0 16px 48px; }
        .records-hero__content { padding: 0 20px 52px; }
        .records-hero::before  { display: none; }
        .scroll-cue            { right: 20px; }
    }
</style>

<div class="luxe-records-context">

    <!-- HERO -->
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

    <?php if (!empty($_SESSION['cancel_success'])): ?>
    <div style="margin: 0 60px 24px; padding: 14px 20px; background:#f4fcf7; border:1px solid #d3f4e0; color:#1c7a43; font-size:.88rem;">
        <?= htmlspecialchars($_SESSION['cancel_success']) ?>
    </div>
    <?php unset($_SESSION['cancel_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['cancel_error'])): ?>
    <div style="margin: 0 60px 24px; padding: 14px 20px; background:#fff8f8; border:1px solid #fcd4d4; color:#b82323; font-size:.88rem;">
        <?= htmlspecialchars($_SESSION['cancel_error']) ?>
    </div>
    <?php unset($_SESSION['cancel_error']); ?>
<?php endif; ?>

    <!-- TABLE -->
    <div class="table-section-wrapper">
        <div class="record-card-luxe">
            <div class="table-responsive">
                <table class="table-luxe table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Scheduled Date & Time</th>
                            <th>Treatment</th>
                            <th>Therapist</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($records)): ?>
                            <?php foreach ($records as $r): ?>
                                <?php
                                    $apptDate  = new DateTime($r['appointment_date']);
                                    $today     = new DateTime('today');
                                    $diff      = $today->diff($apptDate);
                                    // positive = appt is in the future
                                    $daysUntil = ($apptDate >= $today) ? (int)$diff->days : -(int)$diff->days;
                                    $canCancel = $r['status'] === 'confirmed' && $daysUntil >= 7;
                                    $tooLate   = $r['status'] === 'confirmed' && $daysUntil < 7;
                                ?>
                                <tr>
                                    <td>
                                        <div class="data-primary"><?= date('M d, Y', strtotime($r['appointment_date'])) ?></div>
                                        <div class="data-secondary"><i class="bi bi-clock me-1"></i><?= date('h:i A', strtotime($r['appointment_time'])) ?></div>
                                    </td>
                                    <td>
                                        <div class="treatment-title-luxe"><?= htmlspecialchars($r['treatment_name']) ?></div>
                                    </td>
                                    <td>
                                        <div class="data-primary" style="font-weight:500;"><?= htmlspecialchars($r['therapist_fname'] . ' ' . $r['therapist_lname']) ?></div>
                                        <div class="data-secondary" style="text-transform:uppercase;letter-spacing:0.5px;">Therapist</div>
                                    </td>
                                    <td>
                                        <span class="data-primary">₱<?= number_format($r['total_amount'], 2) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                            $statusVal   = $r['status'] ?? 'confirmed';
                                            $statusClass = 'status-' . strtolower($statusVal);
                                            echo "<span class='status-badge-luxe $statusClass'>" . htmlspecialchars($statusVal) . "</span>";
                                        ?>
                                    </td>
                                    <td>
                                        <div class="actions-col">
                                            <!-- VIEW -->
                                            <button class="btn-action btn-action-view"
                                                onclick='openViewModal(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)'>
                                                <i class="bi bi-eye me-1"></i>View
                                            </button>

                                            <!-- CANCEL -->
                                            <?php if ($r['status'] === 'cancelled' || $r['status'] === 'completed'): ?>
                                                <button class="btn-action btn-action-disabled" disabled>
                                                    <i class="bi bi-x-circle me-1"></i>Cancel
                                                </button>
                                            <?php elseif ($canCancel): ?>
                                                <button class="btn-action btn-action-cancel"
                                                    onclick="openCancelModal(<?= (int)$r['appointment_id'] ?>, '<?= htmlspecialchars($r['treatment_name'], ENT_QUOTES) ?>', '<?= date('M d, Y', strtotime($r['appointment_date'])) ?>')">
                                                    <i class="bi bi-x-circle me-1"></i>Cancel
                                                </button>
                                            <?php elseif ($tooLate): ?>
                                                <button class="btn-action btn-action-disabled" disabled
                                                    title="Cancellations must be made at least 7 days before the appointment.">
                                                    <i class="bi bi-lock-fill me-1"></i>Locked
                                                </button>
                                                <div class="lock-note">Within 7-day window</div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center" style="padding: 80px 24px;">
                                    <div class="mx-auto mb-4 d-flex align-items-center justify-content-center"
                                        style="width:64px;height:64px;border:1px solid var(--gold-light);">
                                        <i class="bi bi-calendar3 text-muted" style="font-size:1.4rem;"></i>
                                    </div>
                                    <h3 class="h5 mb-2" style="font-family:'Cormorant Garamond',serif;font-weight:600;">No Records Found</h3>
                                    <p class="text-muted mb-4" style="font-size:0.9rem;max-width:360px;margin:0 auto 20px;">You have not reserved any sessions with Lumiére yet.</p>
                                    <a href="treatment.php" class="btn-luxe-dark">Explore Our Curations</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div><!-- end .luxe-records-context -->


<!-- ════════════════════════════════════════════
     VIEW MODAL
════════════════════════════════════════════ -->
<div class="modal fade modal-luxe" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Appointment <em style="color:var(--gold-light);font-style:italic;">Details</em></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="viewModalBody"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-close" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<!-- ════════════════════════════════════════════
     CANCEL MODAL
════════════════════════════════════════════ -->
<div class="modal fade modal-luxe" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel <em style="color:var(--gold-light);font-style:italic;">Booking</em></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background:var(--studio-surface);padding:28px;">
                <div class="cancel-warning-box">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div class="cancel-warning-text">
                        You are about to cancel <strong id="cancelTreatmentName"></strong> scheduled on <strong id="cancelApptDate"></strong>. This action cannot be undone.
                    </div>
                </div>
                <form method="POST" action="cancel_appointment.php" id="cancelForm">
                    <input type="hidden" name="appointment_id" id="cancelApptId">
                    <label class="cancel-form-label" for="cancelReason">
                        Reason for Cancellation <span style="color:#d9534f;">*</span>
                    </label>
                    <textarea class="cancel-form-control" id="cancelReason" name="reason" rows="4"
                        placeholder="Please let us know why you are cancelling this appointment…" required></textarea>
                </form>
            </div>
            <div class="modal-footer" style="background:var(--studio-surface);border-top:1px solid rgba(232,213,176,0.3);padding:16px 28px;">
                <button type="button" class="btn-modal-close" data-bs-dismiss="modal">Keep Booking</button>
                <button type="submit" form="cancelForm" class="btn-confirm-cancel">
                    <i class="bi bi-x-circle me-1"></i>Confirm Cancellation
                </button>
            </div>
        </div>
    </div>
</div>


<script>
function openViewModal(record) {
    const statusClass = 'status-' + (record.status || 'confirmed').toLowerCase();
    const statusBadge = `<span class="status-badge-luxe ${statusClass}">${escHtml(record.status || 'confirmed')}</span>`;

    const roomDisplay = record.room_name
        ? `${escHtml(record.room_name)} <span style="color:var(--muted-text);font-size:0.8rem;">(${escHtml(record.room_type)})</span>`
        : (escHtml(record.room_type) || 'N/A');

    const subtotal    = fmtMoney(record.subtotal);
    const vat         = fmtMoney(record.vat);
    const total       = fmtMoney(record.total_amount);
    const timeStr     = fmtTime(record.appointment_time);
    const dateStr     = fmtDate(record.appointment_date);

    document.getElementById('viewModalBody').innerHTML = `
        <div class="detail-row">
            <span class="detail-label">Treatment</span>
            <span class="detail-value serif">${escHtml(record.treatment_name)}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Date</span>
            <span class="detail-value">${dateStr}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Time</span>
            <span class="detail-value">${timeStr}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Therapist</span>
            <span class="detail-value">${escHtml(record.therapist_fname + ' ' + record.therapist_lname)}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Room</span>
            <span class="detail-value">${roomDisplay}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Status</span>
            <span class="detail-value">${statusBadge}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Subtotal</span>
            <span class="detail-value">₱${subtotal}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">VAT (12%)</span>
            <span class="detail-value">₱${vat}</span>
        </div>
        <div class="modal-total-row">
            <span class="modal-total-label">Total Amount</span>
            <span class="modal-total-value">₱${total}</span>
        </div>
    `;

    new bootstrap.Modal(document.getElementById('viewModal')).show();
}

function openCancelModal(apptId, treatmentName, apptDate) {
    document.getElementById('cancelApptId').value              = apptId;
    document.getElementById('cancelTreatmentName').textContent = treatmentName;
    document.getElementById('cancelApptDate').textContent      = apptDate;
    document.getElementById('cancelReason').value              = '';
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}

/* Helpers */
function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str || ''));
    return d.innerHTML;
}
function fmtMoney(val) {
    return parseFloat(val || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function fmtTime(t) {
    if (!t) return 'N/A';
    const parts = t.split(':');
    let h = parseInt(parts[0]);
    const m = parts[1];
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return `${h}:${m} ${ampm}`;
}
function fmtDate(d) {
    if (!d) return 'N/A';
    const obj = new Date(d + 'T00:00:00');
    return obj.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}
</script>

<?php include '../includes/footer.php'; ?>