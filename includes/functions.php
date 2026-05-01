<?php
// Sanitize user inputs to prevent XSS attacks
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Quick check for membership status
function isMember($account_type) {
    return ($account_type === 'member');
}

// Format currency for the Spa's services
function formatMoney($number) {
    return "₱" . number_format($number, 2);
}

// System Logging helper (for requirement #16 in your list)
function logAction($pdo, $user_type, $user_id, $action) {
    $stmt = $pdo->prepare("INSERT INTO system_logs (user_type, user_id, action) VALUES (?, ?, ?)");
    $stmt->execute([$user_type, $user_id, $action]);
}
?>