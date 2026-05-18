<?php
// /admin/signin_admin.php
session_start();
require_once '../config/db.php';
require_once '../includes/log_action.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    // Note: In production, use password_verify($password, $admin['password'])
    // For initial setup, we check plain text or hashed based on your preference
    if ($admin && ($password === $admin['password'] || password_verify($password, $admin['password']))) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['role'] = 'admin';
        logAction($pdo, 'Admin logged in successfully.');
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password.";
        // Log failed attempt using the typed username
        $stmt2 = $pdo->prepare("
    INSERT INTO system_logs (user_type, user_identifier, action)
    VALUES (?, ?, ?)
");

$stmt2->execute([
    'Admin',
    $username,
    'Failed login attempt.'
]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Lumiére and Bliss</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gold: #C5A059;
            --dark-bg: #1a1a1a;
        }
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            border-radius: 15px;
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background-color: var(--dark-bg);
            border: none;
            padding: 12px;
        }
        .btn-primary:hover {
            background-color: #333;
        }
        .brand-text {
            font-family: 'Playfair Display', serif;
            text-align: center;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-text">
        <h2 class="fw-bold">Lumiére & Bliss</h2>
        <p class="text-muted small">ADMINISTRATOR ACCESS</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger small"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label small fw-bold">Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-4">
            <label class="form-label small fw-bold">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 rounded-pill">Sign In</button>
    </form>
</div>

</body>
</html>