<?php
require_once '../config/db.php';
header('Content-Type: application/json');


$date         = $_GET['date']         ?? '';
$time         = $_GET['time']         ?? '';
$check        = $_GET['check']        ?? '';
$therapist_id = $_GET['therapist_id'] ?? '';


// ── 1. Time slots for a specific therapist (existing) ──────────────────────
if (!empty($therapist_id) && !empty($date)) {
    $stmt = $pdo->prepare("SELECT time_start FROM therapist_schedule WHERE therapist_id = ?");
    $stmt->execute([$therapist_id]);
    $all_slots = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare("SELECT appointment_time FROM appointments WHERE therapist_id = ? AND appointment_date = ? AND status != 'cancelled'");
    $stmt->execute([$therapist_id, $date]);
    $booked_slots = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $available = array_values(array_diff($all_slots, $booked_slots));
    echo json_encode(['type' => 'times', 'data' => $available]);
    exit;
}


// ── 2. Available time slots for a date (existing — used in Step 2) ─────────
if (!empty($date) && empty($time) && $check !== 'therapists' && $check !== 'rooms') {
    $stmt = $pdo->prepare("
        SELECT appointment_time 
        FROM appointments 
        WHERE appointment_date = ? AND status != 'cancelled'
    ");
    $stmt->execute([$date]);
    $booked = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $all_times = ['09:00:00','10:00:00','11:00:00','13:00:00','14:00:00','15:00:00','16:00:00','17:00:00'];
    $result = [];
    foreach ($all_times as $t) {
        $result[] = [
            'time'   => $t,
            'booked' => in_array($t, $booked)
        ];
    }
    echo json_encode(['available_times' => $result]);
    exit;
}


if (!empty($date) && !empty($time) && $check === 'therapists') {

    $normalizedTime = date('H:i:s', strtotime($time));

    // All active therapist IDs
    $all_stmt = $pdo->query("SELECT therapist_id FROM therapists WHERE status = 'active'");
    $all_ids  = $all_stmt->fetchAll(PDO::FETCH_COLUMN);


    $sched_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM therapist_schedule
        WHERE therapist_id = ?
          AND time_start = ?
    ");

    $not_scheduled = [];
    foreach ($all_ids as $tid) {
        $sched_stmt->execute([$tid, $normalizedTime]);
        if ($sched_stmt->fetchColumn() == 0) {
            $not_scheduled[] = (string) $tid;
        }
    }

    $stmt = $pdo->prepare("
        SELECT DISTINCT CAST(therapist_id AS CHAR)
        FROM appointments
        WHERE appointment_date = ?
          AND appointment_time = ?
          AND status IN ('confirmed', 'completed')
    ");
    $stmt->execute([$date, $normalizedTime]);
    $already_booked = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $busy = array_values(array_unique(array_merge($already_booked, $not_scheduled)));

    echo json_encode([
        'busy_therapists'  => $busy,
        'maxed_therapists' => [],
    ]);
    exit;
}


// ── 4. Available ROOMS for a date + time (existing — used in Step 4) ───────
if (!empty($date) && !empty($time) && $check === 'rooms') {
    $stmt = $pdo->prepare("
        SELECT CAST(room_id AS CHAR)
        FROM appointments
        WHERE appointment_date = ?
          AND appointment_time = ?
          AND status != 'cancelled'
    ");
    $stmt->execute([$date, $time]);
    $busy_rooms = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'busy_rooms'        => $busy_rooms,
        'busy_rooms'        => $busy_rooms,
        'closed_rooms_today' => []
    ]);
    exit;
}