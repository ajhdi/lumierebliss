<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = isset($_SESSION['user_id']);
$is_member = (isset($_SESSION['account_type']) && $_SESSION['account_type'] === 'member');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lumiére and Bliss | Premium Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --spa-gold: #C5A059; --spa-dark: #1a1a1a; }
        body { font-family: 'Inter', sans-serif; background-color: #fdfcf9; }
        .navbar { background: white; border-bottom: 1px solid #eee; }
        .nav-link { font-weight: 500; color: #555 !important; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { color: var(--spa-gold) !important; }
        .btn-gold { background: var(--spa-gold); color: white; border: none; border-radius: 50px; }
        .btn-gold:hover { background: #b08d4a; color: white; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top py-3">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            LUMIÉRE <span style="color: var(--spa-gold);">AND</span> BLISS
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#userNav">
            <i class="bi bi-list fs-2"></i>
        </button>
        
        <div class="collapse navbar-collapse" id="userNav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?= $is_logged_in ? 'home.php' : 'index.php' ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="promotion.php">Promotions</a></li>
                <li class="nav-item"><a class="nav-link" href="treatment.php">Treatments</a></li>
                <li class="nav-item"><a class="nav-link" href="therapist.php">Therapists</a></li>
                
                <!-- Added Rooms link -->
                <li class="nav-item"><a class="nav-link" href="room.php">Rooms</a></li>
                
                <!-- Records moved before Appointment -->
                <?php if($is_logged_in): ?>
                    <li class="nav-item"><a class="nav-link" href="record.php">Records</a></li>
                <?php endif; ?>

                <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
            </ul>
            <div class="d-flex gap-2 align-items-center">
                <?php if($is_logged_in): ?>
                    <a href="appointment.php" class="btn btn-gold px-4 btn-sm py-2">Book Now</a>
                    <div class="dropdown">
                        <button class="btn btn-outline-dark btn-sm rounded-circle px-2" data-bs-toggle="dropdown">
                            <i class="bi bi-person-fill"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow mt-3">
                            <li><a class="dropdown-item small" href="appointment.php">My Appointments</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item small text-danger" href="signout.php">Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="signin.php" class="btn btn-outline-dark btn-sm px-4 rounded-pill">Sign In</a>
                    <a href="signup.php" class="btn btn-gold btn-sm px-4 rounded-pill">Join Us</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>