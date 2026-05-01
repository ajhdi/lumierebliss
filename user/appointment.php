<?php
include '../includes/header.php';
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Initialize variables to prevent undefined variable errors
$sel_tid = $_GET['treatment_id'] ?? '';
$sel_thid = '';
$sel_rid = '';

// 1. Get Treatments (using your updated column names)
$all_treatments = $pdo->query("SELECT * FROM treatments")->fetchAll();
$treatments = array_filter($all_treatments, function($t) {
    return (isset($t['status']) ? $t['status'] == 'available' : true);
});

// 2. Get Therapists
$all_therapists = $pdo->query("SELECT * FROM therapists")->fetchAll();
$therapists = array_filter($all_therapists, function($th) {
    return (isset($th['status']) ? $th['status'] == 'active' : true);
});

// 3. Get Rooms
$all_rooms = $pdo->query("SELECT * FROM rooms")->fetchAll();
$rooms = array_filter($all_rooms, function($r) {
    return (isset($r['status']) ? $r['status'] == 'available' : true);
});

// 4. Fetch User Membership
$u_stmt = $pdo->prepare("SELECT account_type, semi_luxury_uses_left FROM users WHERE user_id = ?");
$u_stmt->execute([$user_id]);
$user = $u_stmt->fetch();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="row g-0">
                    <!-- Left Side: Branding -->
                    <div class="col-md-4 bg-dark text-white p-5 d-flex flex-column justify-content-center">
                        <h3 class="fw-bold mb-4">Book Your Sanctuary</h3>
                        <p class="small opacity-75 mb-4">Select your preferred specialist and treatment space. Our team will confirm your slot within minutes.</p>
                        <div class="mt-auto">
                            <span class="text-gold small fw-bold text-uppercase">Member Benefit</span>
                            <p class="small mb-0"><?= $user['account_type'] == 'member' ? "You have " . $user['semi_luxury_uses_left'] . " credits left." : "Upgrade to Member for free sessions." ?></p>
                        </div>
                    </div>

                    <!-- Right Side: The Form -->
                    <div class="col-md-8 p-5 bg-white">
                        <form action="process_booking.php" method="POST" class="row g-4">
                            <!-- Service Selection -->
                            <div class="col-12">
                                <label class="small fw-bold text-muted text-uppercase">Treatment Service</label>
                                <select name="treatment_id" class="form-select border-0 bg-light py-3" required>
                                    <option value="" disabled <?= empty($sel_tid) ? 'selected' : '' ?>>Select a service...</option>
                                    <?php foreach($treatments as $t): ?>
                                        <option value="<?= $t['treatment_id'] ?>" <?= $sel_tid == $t['treatment_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($t['name']) ?> (₱<?= number_format($t['price'], 2) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Specialist & Space -->
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted text-uppercase">Therapist</label>
                                <select name="therapist_id" id="therapist_id" class="form-select border-0 bg-light py-3" required>
                                    <option value="" selected disabled>Choose expert...</option>
                                    <?php foreach($therapists as $th): ?>
                                        <option value="<?= $th['therapist_id'] ?>">
                                            <?= htmlspecialchars($th['first_name'] . ' ' . ($th['last_name'] ?? '')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted text-uppercase">Private Room</label>
                                <select name="room_id" id="room_id" class="form-select border-0 bg-light py-3" required>
                                    <option value="" selected disabled>Select suite...</option>
                                    <?php foreach($rooms as $r): ?>
                                        <option value="<?= $r['room_id'] ?>">
                                            <?= htmlspecialchars($r['room_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Schedule -->
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted text-uppercase">Date</label>
                                <input type="date" name="appointment_date" id="appointment_date" class="form-control border-0 bg-light py-3" min="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold text-muted text-uppercase">Time</label>
                                <select name="appointment_time" id="appointment_time" class="form-select border-0 bg-light py-3" required disabled>
                                    <option value="">Select date & therapist first...</option>
                                </select>
                            </div>

                            <!-- Membership Payment logic -->
                            <?php if($user['account_type'] == 'member' && $user['semi_luxury_uses_left'] > 0): ?>
                            <div class="col-12 mt-4">
                                <div class="p-3 rounded-4 border border-warning bg-warning bg-opacity-10 d-flex align-items-center">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="use_membership" id="useMember">
                                        <label class="form-check-label fw-bold small ms-2" for="useMember">
                                            REDEEM MEMBER CREDIT (<?= $user['semi_luxury_uses_left'] ?> available)
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="col-12 mt-5">
                                <button type="submit" class="btn btn-dark w-100 rounded-pill py-3 fw-bold shadow-sm">
                                    Confirm My Appointment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const dateEl = document.getElementById('appointment_date');
const timeEl = document.getElementById('appointment_time');
const therapistEl = document.getElementById('therapist_id');
const roomEl = document.getElementById('room_id');

function updateAvailableTimes() {
    if (dateEl.value && therapistEl.value) {
        fetch(`check_availability.php?date=${dateEl.value}&therapist_id=${therapistEl.value}`)
            .then(res => res.json())
            .then(res => {
                timeEl.disabled = false;
                timeEl.innerHTML = '<option value="">Choose available time</option>';
                if(res.data) {
                    res.data.forEach(time => {
                        let displayTime = time.substring(0, 5);
                        timeEl.innerHTML += `<option value="${time}">${displayTime}</option>`;
                    });
                }
            });
    }
}

function updateAvailableResources() {
    if (dateEl.value && timeEl.value) {
        fetch(`check_availability.php?date=${dateEl.value}&time=${timeEl.value}`)
            .then(res => res.json())
            .then(res => {
                // Filter Therapists
                Array.from(therapistEl.options).forEach(opt => {
                    if(opt.value !== "") {
                        // Reset text before re-checking
                        opt.text = opt.text.replace(" (Booked)", "");
                        let isBusy = res.busy_therapists.includes(opt.value);
                        opt.disabled = isBusy;
                        if(isBusy) opt.text += " (Booked)";
                    }
                });
                // Filter Rooms
                Array.from(roomEl.options).forEach(opt => {
                    if(opt.value !== "") {
                        // Reset text before re-checking
                        opt.text = opt.text.replace(" (Occupied)", "");
                        let isBusy = res.busy_rooms.includes(opt.value);
                        opt.disabled = isBusy;
                        if(isBusy) opt.text += " (Occupied)";
                    }
                });
            });
    }
}

therapistEl.addEventListener('change', updateAvailableTimes);
dateEl.addEventListener('change', () => {
    updateAvailableTimes();
    if(timeEl.value) updateAvailableResources();
});
timeEl.addEventListener('change', updateAvailableResources);
</script>
<?php include '../includes/footer.php'; ?>