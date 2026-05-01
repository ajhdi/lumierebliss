<?php
session_start();
require_once '../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit();
}

$error = "";
$success = "";

if (isset($_GET['msg']) && $_GET['msg'] == 'success') {
    $success = "Account created! Please sign in.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set Session Data
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['first_name'];
            $_SESSION['account_type'] = $user['account_type'];
            
            header("Location: home.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Lumiére and Bliss</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --spa-gold: #C5A059; }
        body { 
            background: #fdfbf7; 
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .login-card { 
            border: none; 
            border-radius: 25px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.05); 
            width: 100%; 
            max-width: 420px; 
            background: #fff;
            overflow: hidden;
        }
        .login-header {
            background: #1a1a1a;
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .form-control {
            border-radius: 12px;
            padding: 12px 15px;
            border: 1px solid #eee;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: var(--spa-gold);
        }
        .btn-signin {
            background: var(--spa-gold);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px;
            font-weight: 600;
            transition: 0.3s;
        }
        .btn-signin:hover {
            background: #b08d4a;
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="container p-3">
    <div class="login-card mx-auto">
        <div class="login-header">
            <h4 class="fw-bold mb-0">LUMIÉRE & BLISS</h4>
            <p class="small text-muted mb-0" style="letter-spacing: 2px;">WELCOME BACK</p>
        </div>
        
        <div class="card-body p-4 p-md-5">
            <?php if($error): ?>
                <div class="alert alert-danger small py-2 border-0 mb-4"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success small py-2 border-0 mb-4"><?= $success ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control bg-light" placeholder="name@example.com" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <label class="form-label small fw-bold">Password</label>
                        <a href="forgot_password.php" class="text-decoration-none small text-muted">Forgot?</a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control bg-light" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-signin w-100 mt-3 shadow-sm">Sign In</button>
            </form>

            <div class="text-center mt-4">
                <p class="small text-muted">Don't have an account? <a href="signup.php" class="text-decoration-none fw-bold" style="color: var(--spa-gold);">Sign Up</a></p>
                <hr>
                <a href="index.php" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i> Back to Home</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>