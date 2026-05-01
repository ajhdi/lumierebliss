<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$date = $_GET['date'] ?? '';
$time = $_GET['time'] ?? '';
$therapist_id = $_GET['therapist_id'] ?? '';

// 1. If checking available TIME slots for a specific therapist
if (!empty($therapist_id) && !empty($date)) {
    // Get assigned slots
    $stmt = $pdo->prepare("SELECT time_start FROM therapist_schedule WHERE therapist_id = ?");
    $stmt->execute([$therapist_id]);
    $all_slots = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Get already BOOKED slots for that date
    $stmt = $pdo->prepare("SELECT appointment_time FROM appointments WHERE therapist_id = ? AND appointment_date = ? AND status != 'cancelled'");
    $stmt->execute([$therapist_id, $date]);
    $booked_slots = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Remove booked slots from available ones
    $available = array_values(array_diff($all_slots, $booked_slots));
    echo json_encode(['type' => 'times', 'data' => $available]);
    exit;
}

// 2. If checking available THERAPISTS & ROOMS for a specific Date/Time
if (!empty($date) && !empty($time)) {
    // Find busy therapists
    $stmt = $pdo->prepare("SELECT therapist_id FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
    $stmt->execute([$date, $time]);
    $busy_therapists = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Find busy rooms
    $stmt = $pdo->prepare("SELECT room_id FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
    $stmt->execute([$date, $time]);
    $busy_rooms = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'type' => 'resources',
        'busy_therapists' => $busy_therapists,
        'busy_rooms' => $busy_rooms
    ]);
    exit;
}