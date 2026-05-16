<?php
/**
 * check_availability.php
 * 
 * Handles all availability checks for the booking page:
 *  - Available time slots for a given date (with therapist filtering)
 *  - Busy therapists for a given date+time (max 4 sessions/day enforced)
 *  - Busy / closed rooms for a given date+time
 */

require_once '../config/db.php';
header('Content-Type: application/json');

// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');

$date        = $_GET['date']         ?? '';
$time        = $_GET['time']         ?? '';
$therapist_id = $_GET['therapist_id'] ?? '';
$check       = $_GET['check']        ?? '';

if (!$date) {
    echo json_encode(['error' => 'Date is required']);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

// Get day of week (0=Sunday, 1=Monday, ..., 6=Saturday)
$dayOfWeek = date('w', strtotime($date)); // PHP: 0=Sun, 6=Sat
// Convert to day name for availability checks
$dayNames = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
$dayName  = $dayNames[$dayOfWeek];

/* ═══════════════════════════════════════════════════════════
   CASE 1: Get available time slots for a date
   (used on Step 2, no specific therapist yet)
═══════════════════════════════════════════════════════════ */
if (!$time && !$check) {
    $business_hours = [
        '09:00:00','10:00:00','11:00:00',
        '13:00:00','14:00:00','15:00:00','16:00:00','17:00:00'
    ];

    // Get all bookings on this date (confirmed/pending)
    $stmt = $pdo->prepare("
        SELECT appointment_time, therapist_id
        FROM appointments
        WHERE appointment_date = ?
          AND status NOT IN ('cancelled', 'no_show')
    ");
    $stmt->execute([$date]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count bookings per slot
    $slot_counts = [];
    foreach ($bookings as $b) {
        $t = $b['appointment_time'];
        $slot_counts[$t] = ($slot_counts[$t] ?? 0) + 1;
    }

    // Get total active therapists
    $therapist_count = (int)$pdo->query("SELECT COUNT(*) FROM therapists WHERE status = 'active'")->fetchColumn();
    $max_per_slot    = max(1, $therapist_count); // Can't exceed number of therapists

    $result = [];
    $now = date('Y-m-d') === $date ? date('H:i:s') : '00:00:00';

    foreach ($business_hours as $slot) {
        $count  = $slot_counts[$slot] ?? 0;
        $booked = ($count >= $max_per_slot) || ($slot <= $now && date('Y-m-d') === $date);
        $result[] = [
            'time'   => $slot,
            'booked' => $booked,
            'count'  => $count,
        ];
    }

    echo json_encode(['available_times' => $result]);
    exit;
}

/* ═══════════════════════════════════════════════════════════
   CASE 2: Get available times for a specific therapist
   (legacy — still supported for backward compat)
═══════════════════════════════════════════════════════════ */
if ($therapist_id && !$check) {
    $stmt = $pdo->prepare("
        SELECT appointment_time
        FROM appointments
        WHERE appointment_date = ?
          AND therapist_id = ?
          AND status NOT IN ('cancelled', 'no_show')
    ");
    $stmt->execute([$date, $therapist_id]);
    $booked_times = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $business_hours = [
        '09:00:00','10:00:00','11:00:00',
        '13:00:00','14:00:00','15:00:00','16:00:00','17:00:00'
    ];

    $available = array_values(array_filter($business_hours, function($t) use ($booked_times) {
        return !in_array($t, $booked_times);
    }));

    echo json_encode(['data' => $available]);
    exit;
}

/* ═══════════════════════════════════════════════════════════
   CASE 3: Get busy therapists for date+time
═══════════════════════════════════════════════════════════ */
if ($time && $check === 'therapists') {
    // Therapists booked at this exact slot
    $stmt = $pdo->prepare("
        SELECT therapist_id
        FROM appointments
        WHERE appointment_date = ?
          AND appointment_time = ?
          AND status NOT IN ('cancelled', 'no_show')
    ");
    $stmt->execute([$date, $time]);
    $busy_at_time = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Therapists who have hit the 4-session daily limit
    $stmt2 = $pdo->prepare("
        SELECT therapist_id
        FROM appointments
        WHERE appointment_date = ?
          AND status NOT IN ('cancelled', 'no_show')
        GROUP BY therapist_id
        HAVING COUNT(*) >= 4
    ");
    $stmt2->execute([$date]);
    $maxed = $stmt2->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'busy_therapists'  => array_values(array_unique(array_merge(
            array_map('strval', $busy_at_time),
            array_map('strval', $maxed)
        ))),
        'maxed_therapists' => array_values(array_map('strval', $maxed)),
    ]);
    exit;
}

/* ═══════════════════════════════════════════════════════════
   CASE 4: Get busy/unavailable rooms for date+time
═══════════════════════════════════════════════════════════ */
if ($check === 'rooms') {
    // Rooms occupied at this exact slot
    $stmt = $pdo->prepare("
        SELECT room_id
        FROM appointments
        WHERE appointment_date = ?
          AND appointment_time = ?
          AND status NOT IN ('cancelled', 'no_show')
    ");
    $stmt->execute([$date, $time ?: '00:00:00']);
    $busy_rooms = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Rooms closed today (not available on this day of week)
    // Expects a `room_availability` table: room_id, day_name (e.g. 'monday')
    // OR a days_available JSON column on rooms table.
    $closed_today = [];
    try {
        $stmt3 = $pdo->prepare("
            SELECT r.room_id
            FROM rooms r
            WHERE r.status != 'available'
               OR (
                  r.days_available IS NOT NULL
                  AND JSON_SEARCH(r.days_available, 'one', ?) IS NULL
               )
        ");
        $stmt3->execute([$dayName]);
        $closed_today = $stmt3->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        // If rooms table doesn't have days_available column, skip
        $closed_today = [];
    }

    echo json_encode([
        'busy_rooms'        => array_values(array_map('strval', $busy_rooms)),
        'closed_rooms_today' => array_values(array_map('strval', $closed_today)),
    ]);
    exit;
}

/* ═══════════════════════════════════════════════════════════
   CASE 5: Legacy — both busy_therapists and busy_rooms
   (used by old JS code, kept for backward compat)
═══════════════════════════════════════════════════════════ */
if ($time && !$check) {
    $stmt = $pdo->prepare("
        SELECT therapist_id, room_id
        FROM appointments
        WHERE appointment_date = ?
          AND appointment_time = ?
          AND status NOT IN ('cancelled', 'no_show')
    ");
    $stmt->execute([$date, $time]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $busy_therapists = array_values(array_unique(array_filter(array_column($rows, 'therapist_id'))));
    $busy_rooms      = array_values(array_unique(array_filter(array_column($rows, 'room_id'))));

    echo json_encode([
        'busy_therapists' => array_map('strval', $busy_therapists),
        'busy_rooms'      => array_map('strval', $busy_rooms),
    ]);
    exit;
}

echo json_encode(['error' => 'Invalid request']);
