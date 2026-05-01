<?php
require_once '../config/db.php';
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first = trim($_POST['first_name']);
    $middle = trim($_POST['middle_name']);
    $last = trim($_POST['last_name']);
    $suffix = trim($_POST['suffix']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact_number']);
    $gender = $_POST['gender'];
    $bday_input = $_POST['birthdate'];
    $pass = $_POST['password'];
    $conf_pass = $_POST['confirm_password'];

    // Strict Age Check (18+)
    $bday = new DateTime($bday_input);
    $today = new DateTime();
    $age = $today->diff($bday)->y;

    if ($age < 18) {
        $error = "You must be at least 18 years old to create an account.";
    } elseif ($pass !== $conf_pass) {
        $error = "Passwords do not match.";
    } elseif (strlen($pass) < 8 || !preg_match('/[A-Z]/', $pass) || !preg_match('/[0-9]/', $pass)) {
        $error = "Password must be 8+ chars, including an uppercase letter and a number.";
    } else {
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        
        // Matches DB: user_id(AI), account_type(default), uses(default), created_at(current_timestamp)
        $sql = "INSERT INTO users (first_name, middle_name, last_name, suffix, email, contact_number, birthdate, gender, password, account_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'guest')";
        
        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute([$first, $middle, $last, $suffix, $email, $contact, $bday_input, $gender, $hashed]);
            header("Location: signin.php?msg=success");
            exit();
        } catch (PDOException $e) {
            // Check for duplicate email (Unique constraint in DB)
            if ($e->getCode() == 23000) {
                $error = "This email address is already registered.";
            } else {
                $error = "An error occurred. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Lumiére and Bliss</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fdfbf7; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; font-family: 'Inter', sans-serif; }
        .card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .form-control, .form-select { border-radius: 10px; padding: 12px; border: 1px solid #eee; }
        .btn-dark { background: #1a1a1a; border: none; transition: 0.3s; }
        .btn-dark:hover { background: #333; transform: translateY(-1px); }
    </style>
</head>
<body>

<div class="card p-4 w-100" style="max-width: 650px;">
    <h3 class="fw-bold text-center mb-1">Join Lumiére & Bliss</h3>
    <p class="text-center text-muted small mb-4">Create your account to start booking</p>
    
    <?php if($error): ?> 
        <div class="alert alert-danger small py-2 border-0 mb-4"><?= $error ?></div> 
    <?php endif; ?>

    <form action="" method="POST" class="row g-3">
        <!-- Name Group -->
        <div class="col-md-4">
            <label class="small fw-bold">First Name</label>
            <input type="text" name="first_name" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="small fw-bold">Middle Name</label>
            <input type="text" name="middle_name" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="small fw-bold">Last Name</label>
            <input type="text" name="last_name" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label class="small fw-bold">Suffix</label>
            <input type="text" name="suffix" class="form-control" placeholder="Jr.">
        </div>
        
        <!-- Contact & Info -->
        <div class="col-md-7">
            <label class="small fw-bold">Email Address</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="col-md-5">
            <label class="small fw-bold">Contact Number</label>
            <input type="text" name="contact_number" class="form-control" placeholder="09xxxxxxxxx" required>
        </div>
        
        <div class="col-md-6">
            <label class="small fw-bold">Birthdate</label>
            <!-- UI constraint: disables dates that make user under 18 -->
            <input type="date" name="birthdate" class="form-control" 
                   max="<?= date('Y-m-d', strtotime('-18 years')); ?>" required>
        </div>
        <div class="col-md-6">
            <label class="small fw-bold">Gender</label>
            <select name="gender" class="form-select" required>
                <option value="" disabled selected>Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <!-- Security -->
        <div class="col-md-6">
            <label class="small fw-bold">Password</label>
            <input type="password" name="password" class="form-control" placeholder="8+ chars, 1 Upper, 1 Num" required>
        </div>
        <div class="col-md-6">
            <label class="small fw-bold">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
        </div>
        
        <div class="col-12 mt-4">
            <button type="submit" class="btn btn-dark w-100 rounded-pill py-3 fw-bold shadow-sm">Create Account</button>
            <p class="text-center mt-3 small text-muted">
                Already have an account? <a href="signin.php" class="text-decoration-none fw-bold text-dark">Sign In</a>
            </p>
        </div>
    </form>
</div>

</body>
</html>