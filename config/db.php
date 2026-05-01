<?php
// /config/db.php

$host = 'localhost';
$db   = 'lumierebliss';
$user = 'root'; // Change this to your database username
$pass = '';     // Change this to your database password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // In a production system, you'd log this instead of echoing it
    die("Database connection failed: " . $e->getMessage());
}
?>