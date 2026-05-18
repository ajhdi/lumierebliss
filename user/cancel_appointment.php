<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: record.php");
    exit();
}

$user_id        = $_SESSION['user_id'];
$appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
$reason         = trim($_POST['reason'] ?? '');

if (!$appointment_id || $reason === '') {
    $_SESSION['cancel_error'] = "Missing required fields.";
    header("Location: record.php");
    exit();
}

// Fetch appointment — must belong to this user and be confirmed
$stmt = $pdo->prepare(
    "SELECT * FROM appointments 
     WHERE appointment_id = ? AND user_id = ? AND status = 'confirmed'"
);
$stmt->execute([$appointment_id, $user_id]);
$appt = $stmt->fetch();

if (!$appt) {
    $_SESSION['cancel_error'] = "Appointment not found or cannot be cancelled.";
    header("Location: record.php");
    exit();
}

// Enforce 7-day rule — use date strings to avoid timezone issues
$apptDate  = new DateTime($appt['appointment_date']);
$apptDate->setTime(0, 0, 0);
$today     = new DateTime();
$today->setTime(0, 0, 0);

$diff      = $today->diff($apptDate);
// $diff->days is always positive; $diff->invert = 1 means appt is in the past
$daysUntil = $diff->invert === 0 ? (int)$diff->days : -(int)$diff->days;

if ($daysUntil < 7) {
    $_SESSION['cancel_error'] = "Cancellations must be made at least 7 days before the appointment date.";
    header("Location: record.php");
    exit();
}

// Run both queries in a transaction
try {
    $pdo->beginTransaction();

    $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ?")
        ->execute([$appointment_id]);

    $pdo->prepare("INSERT INTO cancellations (appointment_id, reason, remarks) VALUES (?, ?, ?)")
        ->execute([$appointment_id, $reason, 'Cancelled by user via portal']);

    $pdo->commit();
    $_SESSION['cancel_success'] = "Your appointment has been successfully cancelled.";

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['cancel_error'] = "Something went wrong: " . $e->getMessage();
}

header("Location: record.php");
exit();