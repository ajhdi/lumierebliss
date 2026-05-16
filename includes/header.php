<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = isset($_SESSION['user_id']);
$base_url = "/lumierebliss/"; 

// Logic to determine the target for guest users
$guest_redirect = isset($is_guest) ? 'signin.php' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lumiére and Bliss | Luxury Wellness</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --spa-gold: #c9a96e; 
            --spa-gold-light: #e8d5b0;
            --spa-dark: #1a1a1a; 
            --spa-muted: #8a8070;
            --spa-cream: #fdfbf7;
        }

        body { font-family: 'DM Sans', sans-serif; background-color: var(--spa-cream); }

        /* --- Navigation Bar --- */
        .navbar { 
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1); 
            padding: 20px 0; 
            background: transparent !important;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            min-height: 90px;
        }

        .navbar.scrolled { 
            background: rgba(255, 255, 255, 0.98) !important; 
            padding: 0 !important; 
            min-height: 55px; 
            box-shadow: 0 4px 20px rgba(26,26,26,0.05);
            border-bottom: 1px solid #ede8df;
        }

        .navbar-brand {
            transition: all 0.4s ease;
            opacity: 1;
            visibility: visible;
        }

        .navbar.scrolled .navbar-brand {
            opacity: 0;
            visibility: hidden;
            width: 0;
            margin: 0;
            padding: 0;
            pointer-events: none;
        }

        .nav-logo {
            height: 75px; 
            width: auto;
            transition: 0.4s;
        }

        .nav-link { 
            text-transform: uppercase; 
            font-size: 11px; 
            letter-spacing: 0.15em; 
            color: rgba(255,255,255,0.9) !important; 
            margin: 0 15px;
            font-weight: 600;
            line-height: 55px; 
            padding: 0 !important;
            transition: 0.3s;
        }
        
        .navbar.scrolled .nav-link { 
            color: var(--spa-dark) !important; 
            line-height: 55px; 
        }

        .nav-link:hover { color: var(--spa-gold) !important; transform: translateY(-1px); }

        .action-area { 
            display: flex; 
            align-items: center; 
            white-space: nowrap; 
            height: 55px; 
        }

        .btn-book {
            text-decoration: none;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.1em;
            color: white !important;
            border-bottom: 1px solid var(--spa-gold);
            font-weight: 700;
            padding-bottom: 2px;
            transition: 0.3s;
        }

        .btn-signout {
            text-decoration: none;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 1px;
            font-weight: 500;
            color: rgba(255,255,255,0.7) !important;
            transition: 0.3s;
        }

        .navbar.scrolled .btn-book { color: var(--spa-dark) !important; border-color: var(--spa-dark); }
        .navbar.scrolled .btn-signout { color: var(--spa-muted) !important; }

        .btn-book:hover { color: var(--spa-gold) !important; border-color: var(--spa-gold); }
        .btn-signout:hover { color: var(--spa-gold) !important; }

        .navbar-toggler { border: none; outline: none !important; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
    <div class="container-fluid px-5"> 
        <a class="navbar-brand" href="<?php echo isset($is_guest) ? 'index.php' : 'home.php'; ?>">
            <img src="<?php echo $base_url; ?>assets/logoheader.png" alt="Lumiére and Bliss" class="nav-logo">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userNav">
            <i class="bi bi-list text-white fs-1" id="navIcon"></i>
        </button>
        
        <div class="collapse navbar-collapse" id="userNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="<?php echo isset($is_guest) ? 'index.php' : 'home.php'; ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo isset($is_guest) ? 'signin.php' : 'treatment.php'; ?>">Treatments</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo isset($is_guest) ? 'signin.php' : 'cosmetics.php'; ?>">Cosmetics</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo isset($is_guest) ? 'signin.php' : 'therapist.php'; ?>">Therapists</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo isset($is_guest) ? 'signin.php' : 'room.php'; ?>">Rooms</a></li>
                <?php if($is_logged_in): ?>
                    <li class="nav-item"><a class="nav-link" href="record.php">Records</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo isset($is_guest) ? 'signin.php' : 'promotion.php'; ?>">Promotions</a></li>
            </ul>

            <div class="action-area">
                <?php if($is_logged_in): ?>
                    <a href="appointment.php" class="btn-book">Book Now</a>
                    <a href="signout.php" class="btn-signout ms-4">Sign Out</a>
                <?php else: ?>
                    <a href="signin.php" class="btn-book">Sign In</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
    window.addEventListener('scroll', function() {
        const nav = document.getElementById('mainNav');
        const icon = document.getElementById('navIcon');
        
        if (window.scrollY > 40) {
            nav.classList.add('scrolled');
            if(icon) {
                icon.classList.remove('text-white');
                icon.classList.add('text-dark');
            }
        } else {
            nav.classList.remove('scrolled');
            if(icon) {
                icon.classList.remove('text-dark');
                icon.classList.add('text-white');
            }
        }
    });
</script>