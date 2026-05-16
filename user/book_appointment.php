<?php
include '../includes/header.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch User
$u_stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$u_stmt->execute([$user_id]);
$user = $u_stmt->fetch();

// Treatments (available only)
$treatments = $pdo->query("SELECT * FROM treatments WHERE status = 'available' ORDER BY name")->fetchAll();

// Packages (active only)
$packages = $pdo->query("SELECT * FROM packages WHERE status = 'available' ORDER BY name")->fetchAll();

// Therapists (active only)
$therapists = $pdo->query("SELECT * FROM therapists WHERE status = 'active' ORDER BY first_name")->fetchAll();

// Rooms (available, filtered later by day)
$rooms = $pdo->query("SELECT * FROM rooms ORDER BY room_name")->fetchAll();

// Add-ons (info only)
//$addons = $pdo->query("SELECT * FROM addons ORDER BY name")->fetchAll();

// Promotions (active)
//$promotions = $pdo->query("SELECT * FROM promotions WHERE status = 'active' AND (expiry_date IS NULL OR expiry_date >= CURDATE()) ORDER BY name")->fetchAll();

// Pre-fill from GET
$sel_tid = $_GET['treatment_id'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Book Appointment</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root {
  --gold: #c9a96e;
  --gold-light: #e8d5b0;
  --dark: #1a1a1a;
  --mid: #2e2e2e;
  --surface: #f9f7f4;
  --white: #ffffff;
  --muted: #888;
  --border: #e8e2d9;
  --success: #4a7c59;
  --radius: 16px;
  --shadow: 0 8px 40px rgba(0,0,0,0.08);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--surface);
  color: var(--dark);
  min-height: 100vh;
}

/* ── Page wrapper ── */
.booking-page {
  max-width: 860px;
  margin: 0 auto;
  padding: 40px 20px 80px;
}

/* ── Header ── */
.booking-header {
  text-align: center;
  margin-bottom: 48px;
}
.booking-header .overline {
  font-size: 11px;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--gold);
  font-weight: 500;
  margin-bottom: 12px;
}
.booking-header h1 {
  font-family: 'Cormorant Garamond', serif;
  font-size: 42px;
  font-weight: 300;
  color: var(--dark);
  line-height: 1.15;
}
.booking-header h1 em { font-style: italic; color: var(--gold); }

/* ── Progress Bar ── */
.progress-bar-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0;
  margin-bottom: 48px;
  position: relative;
}
.step-dot {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  z-index: 1;
}
.step-dot .dot {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--border);
  border: 2px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 500;
  color: var(--muted);
  transition: all .35s ease;
  position: relative;
}
.step-dot.active .dot {
  background: var(--dark);
  border-color: var(--dark);
  color: var(--white);
}
.step-dot.done .dot {
  background: var(--gold);
  border-color: var(--gold);
  color: var(--white);
}
.step-dot .label {
  font-size: 10px;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: var(--muted);
  margin-top: 8px;
  white-space: nowrap;
}
.step-dot.active .label { color: var(--dark); font-weight: 500; }
.step-dot.done .label  { color: var(--gold); }

.step-line {
  flex: 1;
  height: 2px;
  background: var(--border);
  max-width: 80px;
  margin-bottom: 24px;
  transition: background .35s ease;
}
.step-line.done { background: var(--gold); }

/* ── Card ── */
.step-card {
  background: var(--white);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow: hidden;
  animation: fadeUp .4s ease both;
}
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

.step-body { padding: 40px; }

/* ── Section titles ── */
.section-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 24px;
  font-weight: 400;
  color: var(--dark);
  margin-bottom: 6px;
}
.section-sub {
  font-size: 13px;
  color: var(--muted);
  margin-bottom: 32px;
}

/* ── Toggle (Treatment / Package) ── */
.type-toggle {
  display: flex;
  background: var(--surface);
  border-radius: 100px;
  padding: 4px;
  margin-bottom: 28px;
  width: fit-content;
  gap: 4px;
}
.type-toggle button {
  padding: 8px 22px;
  border-radius: 100px;
  border: none;
  background: transparent;
  font-size: 13px;
  font-family: 'DM Sans', sans-serif;
  font-weight: 500;
  color: var(--muted);
  cursor: pointer;
  transition: all .25s;
}
.type-toggle button.active {
  background: var(--dark);
  color: var(--white);
}

/* ── Service cards ── */
.service-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 14px;
}
.service-card {
  border: 2px solid var(--border);
  border-radius: 12px;
  padding: 18px;
  cursor: pointer;
  transition: all .25s;
  position: relative;
}
.service-card:hover { border-color: var(--gold); }
.service-card.selected {
  border-color: var(--dark);
  background: var(--dark);
  color: var(--white);
}
.service-card.selected .sc-price { color: var(--gold-light); }
.service-card.selected .sc-duration { color: #aaa; }
.sc-name { font-weight: 500; font-size: 14px; margin-bottom: 4px; }
.sc-price { color: var(--gold); font-size: 15px; font-weight: 600; margin-bottom: 4px; }
.sc-duration { font-size: 12px; color: var(--muted); }
.sc-check {
  position: absolute;
  top: 12px; right: 12px;
  width: 20px; height: 20px;
  border-radius: 50%;
  background: var(--gold);
  display: none;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 11px;
}
.service-card.selected .sc-check { display: flex; }

input[type="hidden"] {}

/* ── Membership badge ── */
.member-badge {
  background: linear-gradient(135deg, #2e2e2e, #1a1a1a);
  color: white;
  border-radius: 12px;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 28px;
  gap: 12px;
}
.member-badge .mb-text { font-size: 13px; opacity: .8; }
.member-badge .mb-credits { font-size: 22px; font-weight: 600; color: var(--gold); }
.member-badge .mb-label { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; opacity: .6; }

/* ── Date / Calendar ── */
.date-picker-wrap {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 24px;
}

/* ── Form fields ── */
.field-group { margin-bottom: 20px; }
.field-label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: var(--muted);
  margin-bottom: 8px;
  display: block;
}
.field-input, .field-select {
  width: 100%;
  padding: 14px 16px;
  background: var(--surface);
  border: 1.5px solid var(--border);
  border-radius: 10px;
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
  color: var(--dark);
  outline: none;
  transition: border-color .2s;
  appearance: none;
}
.field-input:focus, .field-select:focus { border-color: var(--gold); }
.field-input[disabled], .field-select[disabled] {
  opacity: .5; cursor: not-allowed;
}
.select-wrap { position: relative; }
.select-wrap::after {
  content: '▾';
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  color: var(--muted);
  font-size: 12px;
}

/* ── Time slots ── */
.time-slots {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 8px;
}
.time-slot {
  padding: 10px 16px;
  border: 1.5px solid var(--border);
  border-radius: 8px;
  font-size: 13px;
  cursor: pointer;
  transition: all .2s;
  background: white;
}
.time-slot:hover { border-color: var(--gold); }
.time-slot.selected {
  background: var(--dark);
  border-color: var(--dark);
  color: white;
}
.time-slot.booked {
  opacity: .35;
  cursor: not-allowed;
  text-decoration: line-through;
}
.time-slot-loader { font-size: 13px; color: var(--muted); padding: 10px 0; }

/* ── Therapist cards ── */
.therapist-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 14px;
}
.therapist-card {
  border: 2px solid var(--border);
  border-radius: 12px;
  padding: 16px;
  cursor: pointer;
  transition: all .25s;
  text-align: center;
}
.therapist-card:hover { border-color: var(--gold); }
.therapist-card.selected {
  border-color: var(--dark);
  background: var(--dark);
  color: white;
}
.therapist-card.booked {
  opacity: .35;
  cursor: not-allowed;
}
.therapist-card.booked .tc-status { color: #e07070; }
.tc-avatar {
  width: 52px; height: 52px;
  border-radius: 50%;
  background: var(--gold-light);
  display: flex; align-items: center; justify-content: center;
  font-family: 'Cormorant Garamond', serif;
  font-size: 20px;
  font-weight: 600;
  color: var(--dark);
  margin: 0 auto 10px;
}
.therapist-card.selected .tc-avatar { background: rgba(255,255,255,.15); color: white; }
.tc-name { font-size: 13px; font-weight: 500; margin-bottom: 4px; }
.tc-status { font-size: 11px; color: var(--muted); }
.therapist-card.selected .tc-status { color: var(--gold-light); }

/* ── Room cards ── */
.room-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 14px;
}
.room-card {
  border: 2px solid var(--border);
  border-radius: 12px;
  padding: 18px;
  cursor: pointer;
  transition: all .25s;
}
.room-card:hover { border-color: var(--gold); }
.room-card.selected {
  border-color: var(--dark);
  background: var(--dark);
  color: white;
}
.room-card.occupied { opacity: .35; cursor: not-allowed; }
.rc-icon { font-size: 24px; margin-bottom: 8px; }
.rc-name { font-weight: 500; font-size: 14px; margin-bottom: 4px; }
.rc-info { font-size: 12px; color: var(--muted); }
.room-card.selected .rc-info { color: #aaa; }
.room-card.occupied .rc-status { color: #e07070; font-size: 11px; }

/* ── Membership condition notice ── */
.room-condition-notice {
  background: linear-gradient(135deg, #fff9ee, #fff3d0);
  border: 1px solid var(--gold-light);
  border-radius: 10px;
  padding: 14px 18px;
  font-size: 13px;
  color: #7a5c1e;
  margin-bottom: 20px;
  display: none;
}
.room-condition-notice.show { display: block; }
.room-condition-notice strong { color: #5a3e0e; }

/* ── Add-ons (info only) ── */
.addon-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 12px;
}
.addon-item {
  background: var(--surface);
  border-radius: 10px;
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
}
.addon-icon { font-size: 22px; }
.addon-name { font-size: 13px; font-weight: 500; }
.addon-desc { font-size: 11px; color: var(--muted); }
.addon-info-tag {
  margin-top: 4px;
  display: inline-block;
  padding: 2px 8px;
  background: var(--gold-light);
  border-radius: 100px;
  font-size: 10px;
  color: #7a5c1e;
  font-weight: 600;
  letter-spacing: .5px;
}

/* ── Promotions ── */
.promo-select-wrap { margin-bottom: 8px; }

/* ── Summary / Checkout ── */
.summary-block {
  background: var(--dark);
  border-radius: 14px;
  padding: 28px;
  color: white;
  margin-bottom: 24px;
}
.summary-block h3 {
  font-family: 'Cormorant Garamond', serif;
  font-size: 22px;
  font-weight: 300;
  margin-bottom: 20px;
  color: var(--gold-light);
}
.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 10px 0;
  border-bottom: 1px solid rgba(255,255,255,.08);
  font-size: 14px;
}
.summary-row:last-child { border-bottom: none; }
.summary-row .label { opacity: .65; }
.summary-row .value { font-weight: 500; text-align: right; }
.summary-row.total {
  margin-top: 8px;
  padding-top: 16px;
  border-top: 1px solid rgba(255,255,255,.2);
  border-bottom: none;
}
.summary-row.total .label {
  font-size: 16px;
  font-weight: 600;
  opacity: 1;
  color: var(--gold);
}
.summary-row.total .value {
  font-size: 22px;
  font-weight: 600;
  color: var(--gold);
}
.summary-row.discount .value { color: #6fcf97; }

.payment-notice {
  background: var(--surface);
  border-radius: 10px;
  padding: 14px 18px;
  font-size: 13px;
  color: var(--muted);
  text-align: center;
  margin-bottom: 20px;
}
.payment-notice strong { color: var(--dark); }

/* ── Use membership toggle ── */
.membership-toggle-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: linear-gradient(135deg, #fffbf0, #fff5d6);
  border: 1px solid var(--gold-light);
  border-radius: 12px;
  padding: 16px 20px;
  margin-bottom: 20px;
}
.mt-info .mt-title { font-size: 14px; font-weight: 600; }
.mt-info .mt-sub { font-size: 12px; color: var(--muted); margin-top: 2px; }
.toggle-switch {
  position: relative;
  width: 46px; height: 26px;
  flex-shrink: 0;
}
.toggle-switch input { display: none; }
.toggle-slider {
  position: absolute;
  inset: 0;
  background: var(--border);
  border-radius: 100px;
  cursor: pointer;
  transition: .3s;
}
.toggle-slider::before {
  content: '';
  position: absolute;
  width: 20px; height: 20px;
  left: 3px; top: 3px;
  background: white;
  border-radius: 50%;
  transition: .3s;
  box-shadow: 0 1px 4px rgba(0,0,0,.2);
}
.toggle-switch input:checked + .toggle-slider { background: var(--gold); }
.toggle-switch input:checked + .toggle-slider::before { transform: translateX(20px); }

/* ── Navigation buttons ── */
.step-nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 40px;
  background: var(--surface);
  border-top: 1px solid var(--border);
}
.btn-back {
  background: none;
  border: 1.5px solid var(--border);
  border-radius: 100px;
  padding: 12px 28px;
  font-size: 14px;
  font-family: 'DM Sans', sans-serif;
  cursor: pointer;
  color: var(--muted);
  transition: all .2s;
}
.btn-back:hover { border-color: var(--dark); color: var(--dark); }
.btn-next {
  background: var(--dark);
  border: none;
  border-radius: 100px;
  padding: 14px 36px;
  font-size: 14px;
  font-family: 'DM Sans', sans-serif;
  font-weight: 600;
  cursor: pointer;
  color: white;
  transition: all .25s;
  display: flex;
  align-items: center;
  gap: 8px;
}
.btn-next:hover { background: var(--gold); transform: translateY(-1px); }
.btn-next:disabled { opacity: .4; cursor: not-allowed; transform: none; }

.btn-confirm {
  background: linear-gradient(135deg, var(--gold), #b8892a);
  border: none;
  border-radius: 100px;
  padding: 16px 48px;
  font-size: 15px;
  font-family: 'DM Sans', sans-serif;
  font-weight: 700;
  cursor: pointer;
  color: white;
  transition: all .3s;
  box-shadow: 0 4px 20px rgba(201,169,110,.4);
  letter-spacing: .5px;
}
.btn-confirm:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(201,169,110,.5);
}

/* ── Hidden steps ── */
.step-panel { display: none; }
.step-panel.active { display: block; }

/* ── Validation error ── */
.field-error {
  font-size: 12px;
  color: #c0392b;
  margin-top: 6px;
}
.toast {
  position: fixed;
  bottom: 30px; left: 50%; transform: translateX(-50%);
  background: var(--dark); color: white;
  padding: 14px 28px; border-radius: 100px;
  font-size: 14px; z-index: 9999;
  opacity: 0; pointer-events: none;
  transition: opacity .3s ease;
}
.toast.show { opacity: 1; pointer-events: auto; }

/* ── Responsive ── */
@media (max-width: 600px) {
  .step-body { padding: 24px; }
  .step-nav  { padding: 20px 24px; flex-direction: column; gap: 12px; }
  .btn-next, .btn-back { width: 100%; justify-content: center; }
  .date-picker-wrap { grid-template-columns: 1fr; }
  .step-line { max-width: 40px; }
  .step-dot .label { display: none; }
}
</style>
</head>
<body>

<div class="booking-page">

  <!-- Header -->
  <div class="booking-header">
    <p class="overline">Online Booking</p>
    <h1>Reserve Your <em>Sanctuary</em></h1>
  </div>

  <!-- Progress Steps -->
  <div class="progress-bar-wrap">
    <div class="step-dot active" id="pdot-1">
      <div class="dot">1</div>
      <span class="label">Service</span>
    </div>
    <div class="step-line" id="pline-1"></div>
    <div class="step-dot" id="pdot-2">
      <div class="dot">2</div>
      <span class="label">Schedule</span>
    </div>
    <div class="step-line" id="pline-2"></div>
    <div class="step-dot" id="pdot-3">
      <div class="dot">3</div>
      <span class="label">Specialist</span>
    </div>
    <div class="step-line" id="pline-3"></div>
    <div class="step-dot" id="pdot-4">
      <div class="dot">4</div>
      <span class="label">Room</span>
    </div>
    <div class="step-line" id="pline-4"></div>
    <div class="step-dot" id="pdot-5">
      <div class="dot">5</div>
      <span class="label">Extras</span>
    </div>
    <div class="step-line" id="pline-5"></div>
    <div class="step-dot" id="pdot-6">
      <div class="dot">6</div>
      <span class="label">Review</span>
    </div>
  </div>

  <!-- FORM -->
  <form action="process_booking.php" method="POST" id="bookingForm">
    <input type="hidden" name="user_id" value="<?= $user_id ?>">
    <input type="hidden" name="booking_type" id="h_booking_type" value="treatment">
    <input type="hidden" name="treatment_id" id="h_treatment_id" value="">
    <input type="hidden" name="package_id"   id="h_package_id" value="">
    <input type="hidden" name="therapist_id" id="h_therapist_id" value="">
    <input type="hidden" name="room_id"       id="h_room_id" value="">
    <input type="hidden" name="appointment_date" id="h_date" value="">
    <input type="hidden" name="appointment_time" id="h_time" value="">
    <input type="hidden" name="promotion_id" id="h_promo_id" value="">
    <input type="hidden" name="use_membership" id="h_use_membership" value="0">

    <div class="step-card">

      <!-- ══════════════════════════════════════════
           STEP 1: Service Selection
      ══════════════════════════════════════════ -->
      <div class="step-panel active" id="step-1">
        <div class="step-body">

          <?php if($user['account_type'] == 'member'): ?>
          <div class="member-badge">
            <div>
              <div class="mb-label">Member Benefits</div>
              <div class="mb-text">You have member credits available for semi-luxury sessions.</div>
            </div>
            <div style="text-align:right;">
              <div class="mb-credits"><?= $user['semi_luxury_uses_left'] ?></div>
              <div class="mb-label">credits left</div>
            </div>
          </div>
          <?php endif; ?>

          <div class="section-title">Choose Your Service</div>
          <p class="section-sub">Select a single treatment or a curated package.</p>

          <div class="type-toggle">
            <button type="button" class="active" id="btn-show-treatments" onclick="switchServiceType('treatment')">Treatments</button>
            <button type="button" id="btn-show-packages" onclick="switchServiceType('package')">Packages</button>
          </div>

          <!-- Treatments -->
          <div id="treatment-list" class="service-grid">
            <?php foreach($treatments as $t): ?>
            <div class="service-card <?= $sel_tid == $t['treatment_id'] ? 'selected' : '' ?>"
                 onclick="selectService('treatment', '<?= $t['treatment_id'] ?>', '<?= addslashes($t['name']) ?>', <?= $t['price'] ?>, this)"
                 data-id="<?= $t['treatment_id'] ?>" data-price="<?= $t['price'] ?>">
              <div class="sc-check">✓</div>
              <div class="sc-name"><?= htmlspecialchars($t['name']) ?></div>
              <div class="sc-price">₱<?= number_format($t['price'], 2) ?></div>
              <div class="sc-duration"><?= htmlspecialchars($t['duration'] ?? '60 min') ?></div>
            </div>
            <?php endforeach; ?>
          </div>

          <!-- Packages -->
          <div id="package-list" class="service-grid" style="display:none;">
            <?php foreach($packages as $p): ?>
            <div class="service-card"
                 onclick="selectService('package', '<?= $p['package_id'] ?>', '<?= addslashes($p['name']) ?>', <?= $p['price'] ?>, this)"
                 data-id="<?= $p['package_id'] ?>" data-price="<?= $p['price'] ?>">
              <div class="sc-check">✓</div>
              <div class="sc-name"><?= htmlspecialchars($p['name']) ?></div>
              <div class="sc-price">₱<?= number_format($p['price'], 2) ?></div>
              <div class="sc-duration"><?= htmlspecialchars($p['description'] ?? '') ?></div>
            </div>
            <?php endforeach; ?>
          </div>

        </div>
        <div class="step-nav">
          <div></div>
          <button type="button" class="btn-next" onclick="goToStep(2)">
            Continue <span>→</span>
          </button>
        </div>
      </div>

      <!-- ══════════════════════════════════════════
           STEP 2: Date & Time
      ══════════════════════════════════════════ -->
      <div class="step-panel" id="step-2">
        <div class="step-body">
          <div class="section-title">Choose Your Date & Time</div>
          <p class="section-sub">Pick a date to see available time slots.</p>

          <div class="date-picker-wrap">
            <div class="field-group">
              <label class="field-label">Date</label>
              <input type="date" id="sel_date" class="field-input" min="<?= date('Y-m-d') ?>" onchange="onDateChange()">
            </div>
          </div>

          <div class="field-group">
            <label class="field-label">Available Times</label>
            <div class="time-slots" id="time-slots-wrap">
              <span class="time-slot-loader">Select a date first.</span>
            </div>
          </div>

        </div>
        <div class="step-nav">
          <button type="button" class="btn-back" onclick="goToStep(1)">← Back</button>
          <button type="button" class="btn-next" onclick="goToStep(3)">Continue →</button>
        </div>
      </div>

      <!-- ══════════════════════════════════════════
           STEP 3: Therapist
      ══════════════════════════════════════════ -->
      <div class="step-panel" id="step-3">
        <div class="step-body">
          <div class="section-title">Select Your Specialist</div>
          <p class="section-sub">Only therapists available at your chosen time are shown.</p>

          <div class="therapist-grid" id="therapist-grid">
            <span class="time-slot-loader">Loading therapists…</span>
          </div>

        </div>
        <div class="step-nav">
          <button type="button" class="btn-back" onclick="goToStep(2)">← Back</button>
          <button type="button" class="btn-next" onclick="goToStep(4)">Continue →</button>
        </div>
      </div>

      <!-- ══════════════════════════════════════════
           STEP 4: Room
      ══════════════════════════════════════════ -->
      <div class="step-panel" id="step-4">
        <div class="step-body">
          <div class="section-title">Choose Your Private Suite</div>
          <p class="section-sub">Rooms available on your selected date are shown below.</p>

          <?php if($user['account_type'] == 'member'): ?>
          <div class="room-condition-notice show" id="room-condition-notice">
            <strong>✦ Member Perk:</strong> As a member, you have access to semi-luxury and standard suites with your credits.
          </div>
          <?php endif; ?>

          <div class="room-grid" id="room-grid">
            <span class="time-slot-loader">Loading rooms…</span>
          </div>

        </div>
        <div class="step-nav">
          <button type="button" class="btn-back" onclick="goToStep(3)">← Back</button>
          <button type="button" class="btn-next" onclick="goToStep(5)">Continue →</button>
        </div>
      </div>

      <!-- ══════════════════════════════════════════
           STEP 5: Extras (Info only + Promotions)
      ══════════════════════════════════════════ -->
      <div class="step-panel" id="step-5">
        <div class="step-body">
          <div class="section-title">Complimentary Add-Ons</div>
          <p class="section-sub">These are informational. Our team prepares them for every session.</p>

          <div class="addon-list" style="margin-bottom:36px;">
            <?php if(empty($addons)): ?>
            <!-- Fallback add-ons for display -->
            <?php $default_addons = [
              ['icon'=>'🕯️','name'=>'Aromatherapy Candles','description'=>'Soy-based, hand-poured'],
              ['icon'=>'💧','name'=>'Essential Oils','description'=>'Pure therapeutic grade'],
              ['icon'=>'🍵','name'=>'Herbal Tea Service','description'=>'Served post-session'],
              ['icon'=>'🌸','name'=>'Floral Foot Soak','description'=>'Pre-treatment ritual'],
            ]; ?>
            <?php foreach($default_addons as $a): ?>
            <div class="addon-item">
              <div class="addon-icon"><?= $a['icon'] ?></div>
              <div>
                <div class="addon-name"><?= $a['name'] ?></div>
                <div class="addon-desc"><?= $a['description'] ?></div>
                <span class="addon-info-tag">INCLUDED</span>
              </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <?php foreach($addons as $a): ?>
            <div class="addon-item">
              <div class="addon-icon"><?= htmlspecialchars($a['icon'] ?? '✦') ?></div>
              <div>
                <div class="addon-name"><?= htmlspecialchars($a['name']) ?></div>
                <div class="addon-desc"><?= htmlspecialchars($a['description'] ?? '') ?></div>
                <span class="addon-info-tag">INCLUDED</span>
              </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <?php if(!empty($promotions)): ?>
          <div class="section-title" style="font-size:18px; margin-bottom:6px;">Promotions</div>
          <p class="section-sub">Apply a promotion code to your booking.</p>
          <div class="promo-select-wrap field-group">
            <label class="field-label">Select Promotion</label>
            <div class="select-wrap">
              <select id="sel_promo" class="field-select" onchange="applyPromo(this.value, this.options[this.selectedIndex].dataset.discount)">
                <option value="">No promotion</option>
                <?php foreach($promotions as $p): ?>
                <option value="<?= $p['promotion_id'] ?>"
                        data-discount="<?= $p['discount_value'] ?>"
                        data-type="<?= $p['discount_type'] ?? 'percent' ?>">
                  <?= htmlspecialchars($p['name']) ?> — <?= $p['discount_type'] == 'fixed' ? '₱'.number_format($p['discount_value'],2) : $p['discount_value'].'% off' ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <?php endif; ?>

        </div>
        <div class="step-nav">
          <button type="button" class="btn-back" onclick="goToStep(4)">← Back</button>
          <button type="button" class="btn-next" onclick="goToStep(6)">Review Booking →</button>
        </div>
      </div>

      <!-- ══════════════════════════════════════════
           STEP 6: Review & Confirm
      ══════════════════════════════════════════ -->
      <div class="step-panel" id="step-6">
        <div class="step-body">
          <div class="section-title">Review Your Booking</div>
          <p class="section-sub">Please confirm all details before proceeding.</p>

          <!-- Booking summary card -->
          <div class="summary-block">
            <h3>Appointment Summary</h3>

            <div class="summary-row">
              <span class="label">Your Name</span>
              <span class="value"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></span>
            </div>
            <div class="summary-row">
              <span class="label">Service</span>
              <span class="value" id="sum_service">—</span>
            </div>
            <div class="summary-row">
              <span class="label">Date & Time</span>
              <span class="value" id="sum_datetime">—</span>
            </div>
            <div class="summary-row">
              <span class="label">Specialist</span>
              <span class="value" id="sum_therapist">—</span>
            </div>
            <div class="summary-row">
              <span class="label">Suite</span>
              <span class="value" id="sum_room">—</span>
            </div>
            <div class="summary-row">
              <span class="label">Treatment / Package</span>
              <span class="value" id="sum_base_price">₱0.00</span>
            </div>
            <div class="summary-row" id="sum_room_fee_row">
              <span class="label">Room Fee</span>
              <span class="value" id="sum_room_fee">₱0.00</span>
            </div>
            <div class="summary-row" id="sum_discount_row" style="display:none;">
              <span class="label">Promotion Discount</span>
              <span class="value discount" id="sum_discount">−₱0.00</span>
            </div>
            <div class="summary-row" id="sum_member_row" style="display:none;">
              <span class="label">Member Credit Applied</span>
              <span class="value discount" id="sum_member_disc">−₱0.00</span>
            </div>
            <div class="summary-row">
              <span class="label">Subtotal</span>
              <span class="value" id="sum_subtotal">₱0.00</span>
            </div>
            <div class="summary-row">
              <span class="label">VAT (12%)</span>
              <span class="value" id="sum_vat">₱0.00</span>
            </div>
            <div class="summary-row total">
              <span class="label">Total Due</span>
              <span class="value" id="sum_total">₱0.00</span>
            </div>
          </div>

          <?php if($user['account_type'] == 'member' && $user['semi_luxury_uses_left'] > 0): ?>
          <div class="membership-toggle-row" id="membership-toggle-wrap">
            <div class="mt-info">
              <div class="mt-title">🏅 Redeem Member Credit</div>
              <div class="mt-sub"><?= $user['semi_luxury_uses_left'] ?> credit(s) available — covers semi-luxury session cost</div>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" id="use_membership_toggle" onchange="toggleMembership(this.checked)">
              <span class="toggle-slider"></span>
            </label>
          </div>
          <?php endif; ?>

          <div class="payment-notice">
            <strong>💳 Payment on-site.</strong> No payment is collected online. Please arrive 10 minutes early.
          </div>

          <div style="text-align:center;">
            <button type="submit" class="btn-confirm">
              ✦ Confirm My Appointment
            </button>
          </div>

        </div>
        <div class="step-nav">
          <button type="button" class="btn-back" onclick="goToStep(5)">← Back</button>
          <div></div>
        </div>
      </div>

    </div><!-- /step-card -->
  </form>

</div><!-- /booking-page -->

<div class="toast" id="toast"></div>

<script>
/* ═══════════════════════════════════════
   STATE
═══════════════════════════════════════ */
const state = {
  step: 1,
  totalSteps: 6,
  serviceType: 'treatment',
  serviceId: '<?= $sel_tid ?>',
  serviceName: '',
  servicePrice: 0,
  date: '',
  time: '',
  therapistId: '',
  therapistName: '',
  roomId: '',
  roomName: '',
  roomFee: 0,
  promoId: '',
  promoDiscount: 0,
  promoType: 'percent',
  useMembership: false,
  membershipValue: 0,
};

// PHP data available in JS
const allTherapists = <?= json_encode(array_values($therapists)) ?>;
const allRooms       = <?= json_encode(array_values($rooms)) ?>;
const userAccount    = '<?= $user['account_type'] ?>';
const memberCredits  = <?= (int)($user['semi_luxury_uses_left'] ?? 0) ?>;
const VAT_RATE       = 0.12;

/* ═══════════════════════════════════════
   STEP NAVIGATION
═══════════════════════════════════════ */
function goToStep(n) {
  if (n > state.step && !validateStep(state.step)) return;

  state.step = n;

  document.querySelectorAll('.step-panel').forEach((p, i) => {
    p.classList.toggle('active', i + 1 === n);
  });

  // Update progress dots
  for (let i = 1; i <= state.totalSteps; i++) {
    const dot  = document.getElementById(`pdot-${i}`);
    const line = document.getElementById(`pline-${i}`);
    if (!dot) continue;
    dot.classList.remove('active','done');
    if (i < n)  { dot.classList.add('done'); }
    if (i === n){ dot.classList.add('active'); }
    if (line) line.classList.toggle('done', i < n);
  }

  // Lazy-load data for steps
  if (n === 3) renderTherapists();
  if (n === 4) renderRooms();
  if (n === 6) renderSummary();

  window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ═══════════════════════════════════════
   VALIDATION
═══════════════════════════════════════ */
function validateStep(step) {
  if (step === 1) {
    if (!state.serviceId) { showToast('Please select a service.'); return false; }
  }
  if (step === 2) {
    if (!state.date) { showToast('Please select a date.'); return false; }
    if (!state.time) { showToast('Please select a time slot.'); return false; }
  }
  if (step === 3) {
    if (!state.therapistId) { showToast('Please choose a specialist.'); return false; }
  }
  if (step === 4) {
    if (!state.roomId) { showToast('Please select a suite.'); return false; }
  }
  return true;
}

/* ═══════════════════════════════════════
   SERVICE SELECTION
═══════════════════════════════════════ */
function switchServiceType(type) {
  state.serviceType = type;
  state.serviceId = '';
  state.serviceName = '';
  state.servicePrice = 0;
  document.getElementById('treatment-list').style.display = type === 'treatment' ? 'grid' : 'none';
  document.getElementById('package-list').style.display   = type === 'package'   ? 'grid' : 'none';
  document.getElementById('btn-show-treatments').classList.toggle('active', type === 'treatment');
  document.getElementById('btn-show-packages').classList.toggle('active',   type === 'package');
  document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
}

function selectService(type, id, name, price, el) {
  state.serviceId   = id;
  state.serviceName = name;
  state.servicePrice = parseFloat(price);
  document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('h_booking_type').value = type;
  if (type === 'treatment') {
    document.getElementById('h_treatment_id').value = id;
    document.getElementById('h_package_id').value   = '';
  } else {
    document.getElementById('h_package_id').value   = id;
    document.getElementById('h_treatment_id').value = '';
  }
}

/* ═══════════════════════════════════════
   DATE & TIME
═══════════════════════════════════════ */
function onDateChange() {
  const date = document.getElementById('sel_date').value;
  state.date = date;
  state.time = '';
  document.getElementById('h_date').value = date;

  if (!date) return;

  const wrap = document.getElementById('time-slots-wrap');
  wrap.innerHTML = '<span class="time-slot-loader">Checking availability…</span>';

  fetch(`check_availability.php?date=${date}`)
    .then(r => r.json())
    .then(res => {
      const available = res.available_times || [];
      if (!available.length) {
        wrap.innerHTML = '<span class="time-slot-loader" style="color:#c0392b;">No slots available on this date.</span>';
        return;
      }
      wrap.innerHTML = '';
      available.forEach(slot => {
        const btn = document.createElement('div');
        btn.className = 'time-slot' + (slot.booked ? ' booked' : '');
        btn.textContent = slot.time.substring(0,5);
        if (!slot.booked) {
          btn.onclick = () => selectTime(slot.time, btn);
        }
        wrap.appendChild(btn);
      });
    })
    .catch(() => {
      // Fallback: show default time slots if API not yet set up
      renderDefaultTimes(wrap);
    });
}

function renderDefaultTimes(wrap) {
  const slots = ['09:00','10:00','11:00','13:00','14:00','15:00','16:00','17:00'];
  wrap.innerHTML = '';
  slots.forEach(t => {
    const btn = document.createElement('div');
    btn.className = 'time-slot';
    btn.textContent = t;
    btn.onclick = () => selectTime(t + ':00', btn);
    wrap.appendChild(btn);
  });
}

function selectTime(time, el) {
  state.time = time;
  document.getElementById('h_time').value = time;
  document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
  el.classList.add('selected');
}

/* ═══════════════════════════════════════
   THERAPISTS
═══════════════════════════════════════ */
function renderTherapists() {
  const grid = document.getElementById('therapist-grid');
  if (!state.date || !state.time) {
    grid.innerHTML = '<span class="time-slot-loader" style="color:#c0392b;">Please select a date and time first.</span>';
    return;
  }
  grid.innerHTML = '<span class="time-slot-loader">Loading available specialists…</span>';

  fetch(`check_availability.php?date=${state.date}&time=${state.time}&check=therapists`)
    .then(r => r.json())
    .then(res => {
      const busy = res.busy_therapists || [];
      const maxed = res.maxed_therapists || []; // therapists with 4+ sessions
      renderTherapistCards(grid, busy, maxed);
    })
    .catch(() => {
      renderTherapistCards(grid, [], []);
    });
}

function renderTherapistCards(grid, busy, maxed) {
  grid.innerHTML = '';
  allTherapists.forEach(th => {
    const id = String(th.therapist_id);
    const isBusy  = busy.includes(id)  || maxed.includes(id);
    const initials = (th.first_name[0] + (th.last_name ? th.last_name[0] : '')).toUpperCase();
    const fullName = th.first_name + ' ' + (th.last_name || '');
    const isSelected = state.therapistId === id;

    const card = document.createElement('div');
    card.className = 'therapist-card' + (isSelected ? ' selected' : '') + (isBusy ? ' booked' : '');
    card.innerHTML = `
      <div class="tc-avatar">${initials}</div>
      <div class="tc-name">${fullName}</div>
      <div class="tc-status">${isBusy ? 'Unavailable' : 'Available'}</div>
    `;
    if (!isBusy) {
      card.onclick = () => selectTherapist(id, fullName, card);
    }
    grid.appendChild(card);
  });
}

function selectTherapist(id, name, el) {
  state.therapistId   = id;
  state.therapistName = name;
  document.getElementById('h_therapist_id').value = id;
  document.querySelectorAll('.therapist-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
}

/* ═══════════════════════════════════════
   ROOMS
═══════════════════════════════════════ */
const ROOM_ICONS = { 'standard': '🛋️', 'semi-luxury': '✨', 'luxury': '💎', 'vip': '👑' };

function renderRooms() {
  const grid = document.getElementById('room-grid');
  grid.innerHTML = '<span class="time-slot-loader">Checking room availability…</span>';

  fetch(`check_availability.php?date=${state.date}&time=${state.time}&check=rooms`)
    .then(r => r.json())
    .then(res => {
      const occupied   = res.busy_rooms         || [];
      const closedDays = res.closed_rooms_today  || [];
      renderRoomCards(grid, occupied, closedDays);
    })
    .catch(() => renderRoomCards(grid, [], []));
}

function renderRoomCards(grid, occupied, closedToday) {
  grid.innerHTML = '';

  allRooms.forEach(r => {
    const id      = String(r.room_id);
    const isOccupied = occupied.includes(id) || closedToday.includes(id);
    const roomType   = (r.room_type || 'standard').toLowerCase();
    const icon       = ROOM_ICONS[roomType] || '🛏️';
    const fee        = parseFloat(r.additional_fee || 0);
    const isSelected = state.roomId === id;

    // Membership room condition
    let memberLocked = false;
    if (userAccount !== 'member' && roomType === 'semi-luxury' && r.members_only == 1) {
      memberLocked = true;
    }

    const card = document.createElement('div');
    card.className = 'room-card' + (isSelected ? ' selected' : '') + (isOccupied || memberLocked ? ' occupied' : '');
    card.innerHTML = `
      <div class="rc-icon">${icon}</div>
      <div class="rc-name">${r.room_name}</div>
      <div class="rc-info">${roomType.charAt(0).toUpperCase() + roomType.slice(1)}${fee > 0 ? ' · +₱'+fee.toFixed(2) : ' · No extra fee'}</div>
      ${isOccupied ? '<div class="rc-status" style="font-size:11px;color:#e07070;margin-top:4px;">Occupied</div>' : ''}
      ${memberLocked ? '<div class="rc-status" style="font-size:11px;color:#e07070;margin-top:4px;">Members Only</div>' : ''}
    `;
    if (!isOccupied && !memberLocked) {
      card.onclick = () => selectRoom(id, r.room_name, fee, card);
    }
    grid.appendChild(card);
  });
}

function selectRoom(id, name, fee, el) {
  state.roomId   = id;
  state.roomName = name;
  state.roomFee  = fee;
  document.getElementById('h_room_id').value = id;
  document.querySelectorAll('.room-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
}

/* ═══════════════════════════════════════
   PROMOTIONS
═══════════════════════════════════════ */
function applyPromo(promoId, discount) {
  state.promoId = promoId;
  const sel = document.getElementById('sel_promo');
  const opt = sel.options[sel.selectedIndex];
  state.promoDiscount = parseFloat(discount || 0);
  state.promoType     = opt ? (opt.dataset.type || 'percent') : 'percent';
  document.getElementById('h_promo_id').value = promoId;
}

/* ═══════════════════════════════════════
   MEMBERSHIP TOGGLE
═══════════════════════════════════════ */
function toggleMembership(checked) {
  state.useMembership = checked;
  document.getElementById('h_use_membership').value = checked ? '1' : '0';
  renderSummary();
}

/* ═══════════════════════════════════════
   SUMMARY / PRICING
═══════════════════════════════════════ */
function renderSummary() {
  document.getElementById('sum_service').textContent   = state.serviceName || '—';
  document.getElementById('sum_therapist').textContent = state.therapistName || '—';
  document.getElementById('sum_room').textContent      = state.roomName || '—';

  // Format date/time
  let dtText = '—';
  if (state.date && state.time) {
    const d = new Date(state.date + 'T' + state.time);
    dtText = d.toLocaleDateString('en-PH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
           + ' at ' + state.time.substring(0,5);
  }
  document.getElementById('sum_datetime').textContent = dtText;

  // Price computation
  let base     = state.servicePrice;
  let roomFee  = state.roomFee;
  let discount = 0;

  if (state.promoId && state.promoDiscount > 0) {
    if (state.promoType === 'percent') {
      discount = base * (state.promoDiscount / 100);
    } else {
      discount = state.promoDiscount;
    }
  }

  let memberDisc = 0;
  if (state.useMembership && memberCredits > 0) {
    memberDisc = base; // Credit covers service cost
  }

  const subtotal = Math.max(0, base + roomFee - discount - memberDisc);
  const vat      = subtotal * VAT_RATE;
  const total    = subtotal + vat;

  document.getElementById('sum_base_price').textContent = '₱' + base.toFixed(2);
  document.getElementById('sum_room_fee').textContent   = '₱' + roomFee.toFixed(2);
  document.getElementById('sum_subtotal').textContent   = '₱' + subtotal.toFixed(2);
  document.getElementById('sum_vat').textContent        = '₱' + vat.toFixed(2);
  document.getElementById('sum_total').textContent      = '₱' + total.toFixed(2);

  const discRow = document.getElementById('sum_discount_row');
  if (discount > 0) {
    discRow.style.display = 'flex';
    document.getElementById('sum_discount').textContent = '−₱' + discount.toFixed(2);
  } else {
    discRow.style.display = 'none';
  }

  const memRow = document.getElementById('sum_member_row');
  if (memberDisc > 0) {
    memRow.style.display = 'flex';
    document.getElementById('sum_member_disc').textContent = '−₱' + memberDisc.toFixed(2);
  } else {
    memRow.style.display = 'none';
  }
}

/* ═══════════════════════════════════════
   TOAST
═══════════════════════════════════════ */
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

/* ═══════════════════════════════════════
   INIT
═══════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  // Pre-select if treatment_id in URL
  if ('<?= $sel_tid ?>') {
    const card = document.querySelector(`.service-card[data-id="<?= $sel_tid ?>"]`);
    if (card) {
      const price = parseFloat(card.dataset.price);
      selectService('treatment', '<?= $sel_tid ?>', card.querySelector('.sc-name').textContent, price, card);
    }
  }
});
</script>

</body>
</html>
<?php include '../includes/footer.php'; ?>
