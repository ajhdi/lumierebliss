<?php
/**
 * process_booking.php
 *
 * Handles booking submission:
 *  1. Validates all inputs
 *  2. Checks for double-booking (therapist, room)
 *  3. Enforces therapist 4-session/day limit
 *  4. Applies membership credit if selected
 *  5. Inserts appointment record
 *  6. Sends confirmation email to user
 *  7. Sends notification email to admin/therapist
 *  8. Redirects to confirmation page
 */

session_start();
require_once '../config/db.php';
// require_once '../user/config_mail.php'; // Your PHPMailer / SMTP config

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: book_appointment.php");
    exit;
}

/* ─── Collect & sanitize inputs ─── */
$user_id          = (int)$_SESSION['user_id'];
$booking_type     = in_array($_POST['booking_type'] ?? '', ['treatment','package']) ? $_POST['booking_type'] : 'treatment';
$treatment_id     = !empty($_POST['treatment_id'])  ? (int)$_POST['treatment_id']  : null;
$package_id       = !empty($_POST['package_id'])    ? (int)$_POST['package_id']    : null;
$therapist_id     = (int)($_POST['therapist_id']    ?? 0);
$room_id          = (int)($_POST['room_id']         ?? 0);
$appointment_date = $_POST['appointment_date']       ?? '';
$appointment_time = $_POST['appointment_time']       ?? '';
$promotion_id     = !empty($_POST['promotion_id'])   ? (int)$_POST['promotion_id']  : null;
$use_membership   = ($_POST['use_membership'] ?? '0') === '1';
$end_time = null;
echo "<pre>";

print_r([
    'user_id' => $user_id,
    'booking_type' => $booking_type,
    'treatment_id' => $treatment_id,
    'package_id' => $package_id,
    'therapist_id' => $therapist_id,
    'room_id' => $room_id,
    'appointment_date' => $appointment_date,
    'appointment_time' => $appointment_time,
    'promotion_id' => $promotion_id,
    'use_membership' => $use_membership
]);

echo "</pre>";


/* ─── Validate required fields ─── */
$errors = [];
if (!$therapist_id) $errors[] = "Therapist is required.";
if (!$room_id)      $errors[] = "Room is required.";
if (!$appointment_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointment_date)) $errors[] = "Valid date is required.";
if (!$appointment_time) $errors[] = "Time is required.";
if ($booking_type === 'treatment' && !$treatment_id) $errors[] = "Treatment is required.";
if ($booking_type === 'package'   && !$package_id)   $errors[] = "Package is required.";
if (strtotime($appointment_date) < strtotime(date('Y-m-d'))) $errors[] = "Cannot book in the past.";

if (!empty($errors)) {
    $_SESSION['booking_error'] = implode(' ', $errors);
    header("Location: book_appointment.php");
    exit;
}

/* ─── Fetch user ─── */
$u_stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$u_stmt->execute([$user_id]);
$user = $u_stmt->fetch();
if (!$user) { die('User not found.'); }

/* ─── Fetch service details ─── */
$service_name  = '';
$service_price = 0;

if ($booking_type === 'treatment') {
    $s_stmt = $pdo->prepare("SELECT * FROM treatments WHERE treatment_id = ? AND status = 'available'");
    $s_stmt->execute([$treatment_id]);
    $service = $s_stmt->fetch();
} else {
    $s_stmt = $pdo->prepare("SELECT * FROM packages WHERE package_id = ? AND status = 'available'");
    $s_stmt->execute([$package_id]);
    $service = $s_stmt->fetch();
}

if (!$service) {
    $_SESSION['booking_error'] = "Selected service is no longer available.";
    // header("Location: book_appointment.php");
    // exit;
    echo "Selected service is no longer available\n";
    die();
}
$service_name  = $service['name'];
$service_price = (float)$service['price'];

/* ─── Fetch room ─── */
$r_stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id = ? AND status = 'available'");
$r_stmt->execute([$room_id]);
$room = $r_stmt->fetch();
if (!$room) {
    $_SESSION['booking_error'] = "Selected room is not available.";
    // header("Location: book_appointment.php");
    // exit;
    echo "Selected room is not available.\n";
    die();
}
$room_fee = (float)($room['additional_fee'] ?? 0);

/* ─── Fetch therapist ─── */
$th_stmt = $pdo->prepare("SELECT * FROM therapists WHERE therapist_id = ? AND status = 'active'");
$th_stmt->execute([$therapist_id]);
$therapist = $th_stmt->fetch();
if (!$therapist) {
    $_SESSION['booking_error'] = "Selected therapist is not available.";
    // header("Location: book_appointment.php");
    // exit;
    echo "Selected therapist is not available. \n";
    die();
}
$therapist_name = trim($therapist['first_name'] . ' ' . ($therapist['last_name'] ?? ''));

/* ─── ANTI-OVERBOOKING: Therapist at this slot ─── */
$dup_th = $pdo->prepare("
    SELECT COUNT(*) FROM appointments
    WHERE therapist_id = ? AND appointment_date = ? AND appointment_time = ?
      AND status NOT IN ('cancelled', 'no_show')
");
$dup_th->execute([$therapist_id, $appointment_date, $appointment_time]);
if ((int)$dup_th->fetchColumn() > 0) {
    $_SESSION['booking_error'] = "This therapist is already booked at that time. Please choose another.";
    // header("Location: book_appointment.php");
    // exit;
    echo "This therapist is already booked at that tim \n";
    die();
}

/* ─── ANTI-OVERBOOKING: Room at this slot ─── */
$dup_room = $pdo->prepare("
    SELECT COUNT(*) FROM appointments
    WHERE room_id = ? AND appointment_date = ? AND appointment_time = ?
      AND status NOT IN ('cancelled', 'no_show')
");
$dup_room->execute([$room_id, $appointment_date, $appointment_time]);
if ((int)$dup_room->fetchColumn() > 0) {
    $_SESSION['booking_error'] = "That room is already occupied at the requested time. Please choose another.";
    // header("Location: book_appointment.php");
    // exit;
    echo "room already occupied \n";
    die();
}

/* ─── DAILY LIMIT: Therapist max 4 sessions per day ─── */
$daily_count = $pdo->prepare("
    SELECT COUNT(*) FROM appointments
    WHERE therapist_id = ? AND appointment_date = ?
      AND status NOT IN ('cancelled', 'no_show')
");
$daily_count->execute([$therapist_id, $appointment_date]);
if ((int)$daily_count->fetchColumn() >= 4) {
    $_SESSION['booking_error'] = "This therapist has reached the maximum 4 sessions for that day. Please choose another.";
    // header("Location: book_appointment.php");
    // exit;
    echo "therapist has reached the maximum 4 \n";
    die();
}

/* ─── DUPLICATE CHECK: Same user same slot ─── */
$dup_user = $pdo->prepare("
    SELECT COUNT(*) FROM appointments
    WHERE user_id = ? AND appointment_date = ? AND appointment_time = ?
      AND status NOT IN ('cancelled', 'no_show')
");
$dup_user->execute([$user_id, $appointment_date, $appointment_time]);
if ((int)$dup_user->fetchColumn() > 0) {
    $_SESSION['booking_error'] = "You already have a booking at that time.";
    // header("Location: book_appointment.php");
    // exit;
    echo "therapist has reached the maximum 4 \n";
    die();
}

/* ─── Promotion ─── */
$discount      = 0;
$discount_type = 'percent';
$promo_name    = null;

if ($promotion_id) {
    $p_stmt = $pdo->prepare("
        SELECT * FROM promotions
        WHERE promotion_id = ? AND status = 'active'
          AND (expiry_date IS NULL OR expiry_date >= CURDATE())
    ");
    $p_stmt->execute([$promotion_id]);
    $promo = $p_stmt->fetch();
    if ($promo) {
        $promo_name    = $promo['name'];
        $discount_type = $promo['discount_type'] ?? 'percent';
        $discount_val  = (float)$promo['discount_value'];
        $discount = $discount_type === 'fixed'
            ? $discount_val
            : $service_price * ($discount_val / 100);
    }
}

/* ─── Membership Credit ─── */
$membership_discount = 0;
if ($use_membership && $user['account_type'] === 'member' && $user['semi_luxury_uses_left'] > 0) {
    $membership_discount = $service_price; // Credits cover the service cost
}

/* ─── Price Calculation ─── */
$subtotal = max(0, $service_price + $room_fee - $discount - $membership_discount);
$vat      = $subtotal * 0.12;
$total    = $subtotal + $vat;

/* ─── Insert Appointment ─── */
try {
    echo "pumasok naba dito \n";

    $pdo->beginTransaction();

    $insert = $pdo->prepare("
    INSERT INTO appointments (
        user_id,
        therapist_id,
        room_id,
        treatment_id,
        package_id,
        promo_id,
        appointment_date,
        appointment_time,
        end_time,
        subtotal,
        vat,
        total_amount,
        status,
        created_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?,
        'confirmed',
        NOW()
    )
");
    $insert->execute([
        $user_id,
        $therapist_id,
        $room_id,
        $treatment_id,
        $package_id,
        $promotion_id,
        $appointment_date,
        $appointment_time,
        $end_time,
        $subtotal,
        $vat,
        $total
    ]);

    $appointment_id = $pdo->lastInsertId();

    // Deduct membership credit if used
    if ($use_membership && $user['semi_luxury_uses_left'] > 0) {
        $pdo->prepare("
            UPDATE users SET semi_luxury_uses_left = semi_luxury_uses_left - 1 WHERE user_id = ?
        ")->execute([$user_id]);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Booking insert failed: ' . $e->getMessage());
    $_SESSION['booking_error'] = "A system error occurred. Please try again.";
    header("Location: book_appointment.php");
    exit;
}

/* ─── Send Confirmation Email to User ─── */
$formatted_date = date('l, F j, Y', strtotime($appointment_date));
$formatted_time = date('g:i A', strtotime($appointment_time));
$user_email     = $user['email'];
$user_name      = trim($user['first_name'] . ' ' . $user['last_name']);

$email_subject  = "Your Appointment is Confirmed — #{$appointment_id}";
$email_body     = "
<!DOCTYPE html>
<html>
<head>
  <style>
    body { font-family: 'Georgia', serif; background: #f9f7f4; color: #1a1a1a; }
    .wrap { max-width: 560px; margin: 40px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
    .header { background: #1a1a1a; padding: 32px; text-align: center; }
    .header h1 { color: #c9a96e; font-size: 24px; margin: 0; letter-spacing: 1px; }
    .header p  { color: rgba(255,255,255,.6); font-size: 13px; margin: 8px 0 0; }
    .body   { padding: 32px; }
    .row    { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f0ece4; font-size: 14px; }
    .row .label { color: #888; }
    .row .value { font-weight: 600; text-align: right; }
    .total-row  { background: #1a1a1a; color: white; padding: 16px 32px; display: flex; justify-content: space-between; }
    .notice { background: #fff9ee; border-left: 3px solid #c9a96e; padding: 14px 16px; font-size: 13px; color: #7a5c1e; margin: 20px 0; border-radius: 4px; }
    .footer { text-align: center; padding: 24px; font-size: 12px; color: #999; }
  </style>
</head>
<body>
<div class='wrap'>
  <div class='header'>
    <h1>Appointment Confirmed</h1>
    <p>Booking Reference #" . str_pad($appointment_id, 6, '0', STR_PAD_LEFT) . "</p>
  </div>
  <div class='body'>
    <p style='margin-bottom:24px;'>Dear <strong>{$user_name}</strong>, your sanctuary awaits.</p>
    <div class='row'><span class='label'>Service</span><span class='value'>{$service_name}</span></div>
    <div class='row'><span class='label'>Date</span><span class='value'>{$formatted_date}</span></div>
    <div class='row'><span class='label'>Time</span><span class='value'>{$formatted_time}</span></div>
    <div class='row'><span class='label'>Specialist</span><span class='value'>{$therapist_name}</span></div>
    <div class='row'><span class='label'>Suite</span><span class='value'>{$room['room_name']}</span></div>
    <div class='row'><span class='label'>Total Due</span><span class='value' style='color:#c9a96e;'>₱" . number_format($total, 2) . "</span></div>
    <div class='notice'>
      <strong>Payment on-site.</strong> Please arrive 10 minutes before your appointment. Payment will be collected at the front desk.
    </div>
  </div>
  <div class='footer'>
    To cancel or reschedule, please contact us at least 24 hours in advance.<br>
    We look forward to seeing you.
  </div>
</div>
</body>
</html>
";
// No phpmailer and vendor/autoload.php
// try {
//     sendEmail($user_email, $email_subject, $email_body);
// } catch (Exception $e) {
//     error_log('Confirmation email failed: ' . $e->getMessage());
//     // Don't block booking for email failure
// }

// /* ─── Send Notification to Admin ─── */
// $admin_email   = ADMIN_EMAIL ?? 'admin@yourspa.com';
// $admin_subject = "New Booking #{$appointment_id} — {$user_name}";
// $admin_body    = "New booking received.\n\nRef: #{$appointment_id}\nUser: {$user_name}\nService: {$service_name}\nDate: {$formatted_date} at {$formatted_time}\nTherapist: {$therapist_name}\nRoom: {$room['room_name']}\nTotal: ₱".number_format($total,2);

// try {
//     sendEmail($admin_email, $admin_subject, $admin_body, false); // plain text to admin
// } catch (Exception $e) {
//     error_log('Admin notification email failed: ' . $e->getMessage());
// }

/* ─── Redirect to success ─── */
$_SESSION['booking_success'] = [
    'appointment_id' => $appointment_id,
    'service_name'   => $service_name,
    'date'           => $formatted_date,
    'time'           => $formatted_time,
    'therapist'      => $therapist_name,
    'room'           => $room['room_name'],
    'total'          => number_format($total, 2),
];

header("Location: booking_success.php");
exit;
