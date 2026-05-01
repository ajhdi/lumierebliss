<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$id = $_GET['id'] ?? 0;

// Fetch the 4 specific slots assigned to this therapist
$stmt = $pdo->prepare("SELECT time_start FROM therapist_schedule WHERE therapist_id = ? ORDER BY time_start ASC");
$stmt->execute([$id]);
$slots = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($slots);