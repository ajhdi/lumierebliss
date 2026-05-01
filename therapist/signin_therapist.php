<?php
session_start();
require_once '../config/db.php';

if (isset($_SESSION['therapist_id'])) {
    header('Location: schedule.php');
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM therapists WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $therapist = $stmt->fetch();

        if ($therapist && password_verify($password, $therapist['password'])) {
            $_SESSION['therapist_id'] = $therapist['therapist_id'];
            $_SESSION['therapist_name'] = $therapist['first_name'] . ' ' . $therapist['last_name'];
            header("Location: schedule.php");
            exit();
        } else {
            $error = "Invalid username, password, or account is inactive.";
        }
    } else {
        $error = "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Therapist Login | Lumiére and Bliss</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fdfbf7; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Inter', sans-serif; }
        .login-card { border: none; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); width: 100%; max-width: 400px; background: #fff; }
        .btn-therapist { background-color: #5d6d7e; border: none; color: white; transition: 0.3s; }
        .btn-therapist:hover { background-color: #34495e; color: white; }
        .brand-text { letter-spacing: 3px; font-weight: 700; color: #5d6d7e; }
    </style>
</head>
<body>

<div class="container p-3">
    <div class="card login-card mx-auto">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <h4 class="brand-text">LUMIÉRE & BLISS</h4>
                <p class="text-muted small text-uppercase">Therapist Access</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-danger py-2 small border-0"><?= $error ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Username</label>
                    <input type="text" name="username" class="form-control rounded-pill px-3" placeholder="Enter username" required>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold">Password</label>
                    <input type="password" name="password" class="form-control rounded-pill px-3" placeholder="Enter password" required>
                </div>
                <button type="submit" class="btn btn-therapist w-100 py-2 rounded-pill shadow-sm">Sign In</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>