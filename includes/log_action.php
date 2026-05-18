<?php
function logAction($pdo, $action)
{
    // Check if admin session exists
    if (!isset($_SESSION['admin_username'])) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO system_logs (user_type, user_identifier, action)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        'Admin',
        $_SESSION['admin_username'],
        $action
    ]);
}
?>