<?php
require_once '../config/db.php';

// Define your desired credentials here
$user = 'admin';
$pass = 'Lumiere2026!'; // Use a strong password

// We hash the password for security
$hashed_password = password_hash($pass, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
    $stmt->execute([$user, $hashed_password]);
    echo "Admin account created successfully! <br> Username: $user <br> Password: $pass";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>