<?php
// /admin/dashboard.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}

// 1. ANALYTICS: Basic Counts
$therapist_count = $pdo->query("SELECT COUNT(*) FROM therapists WHERE status='active'")->fetchColumn();
$room_count = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status='available'")->fetchColumn();
$confirmed_count = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='confirmed'")->fetchColumn();

// 2. ANALYTICS: Completed Appointments (Current Month)
$completed_month = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='completed' AND MONTH(appointment_date) = MONTH(CURRENT_DATE())")->fetchColumn();

// 3. REPORTS: Estimated Revenue (Confirmed + Completed)
$revenue_query = $pdo->query("SELECT SUM(total_amount) FROM appointments WHERE status != 'cancelled'")->fetchColumn();
$est_revenue = $revenue_query ? number_format($revenue_query, 2) : "0.00";

// 4. REPORTS: Most Booked Service
// This joins treatments and packages to see what is most popular
$popular_service = $pdo->query("
    SELECT name, COUNT(*) as count FROM (
        SELECT t.name FROM appointments a JOIN treatments t ON a.treatment_id = t.treatment_id
        UNION ALL
        SELECT p.name FROM appointments a JOIN packages p ON a.package_id = p.package_id
    ) as services 
    GROUP BY name ORDER BY count DESC LIMIT 1
")->fetch();

// 5. REPORTS: Peak Booking Time
$peak_time = $pdo->query("
    SELECT HOUR(appointment_time) as hour, COUNT(*) as count 
    FROM appointments 
    GROUP BY hour ORDER BY count DESC LIMIT 1
")->fetch();
$display_peak = $peak_time ? date("g:i A", strtotime($peak_time['hour'] . ":00")) : "N/A";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Lumiére and Bliss</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --sidebar-width: 260px;
            --accent-gold: #C5A059;
            --dark-bg: #1a1a1a;
        }
        body { background-color: #f4f6f9; font-family: 'Inter', sans-serif; }
        
        /* Consistent Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            background: var(--dark-bg);
            color: white;
            transition: 0.3s;
            z-index: 1000;
        }
        .nav-link {
            color: rgba(255,255,255,0.6);
            padding: 15px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: 0.2s;
        }
        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.05);
            border-left: 4px solid var(--accent-gold);
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 40px;
            transition: 0.3s;
        }

        .card-stat {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .card-stat:hover { transform: translateY(-5px); }
        .icon-box {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            color: var(--accent-gold);
            font-size: 1.2rem;
        }

        /* Mobile View */
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="p-4 mb-4">
        <h4 class="fw-bold mb-0 text-white">L&B <span style="color: var(--accent-gold);">Admin</span></h4>
    </div>
    <div class="nav flex-column">
        <a href="dashboard.php" class="nav-link active"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="manage_appointment.php" class="nav-link"><i class="bi bi-calendar-event"></i> Appointments</a>
        
        <!-- Added Treatments Option Here -->
        <a href="manage_treatments.php" class="nav-link"><i class="bi bi-droplet-half"></i> Treatments</a>
        
        <a href="manage_therapist.php" class="nav-link"><i class="bi bi-person-badge"></i> Therapists</a>
        <a href="manage_room.php" class="nav-link"><i class="bi bi-door-open"></i> Rooms</a>
        <a href="manage_account.php" class="nav-link"><i class="bi bi-people"></i> Accounts</a>
        <a href="system_logs.php" class="nav-link"><i class="bi bi-shield-lock"></i> Logs</a>
        <a href="logout.php" class="nav-link text-danger mt-5"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
</nav>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold">Welcome Back, Admin</h2>
            <p class="text-muted">Here is what's happening at Lumiére and Bliss today.</p>
        </div>
        <button class="btn btn-dark d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('active')">
            <i class="bi bi-list"></i>
        </button>
    </div>

    <!-- Top Analytics Row -->
    <div class="row g-4 mb-5">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card card-stat p-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted small fw-bold">THERAPISTS</h6>
                        <h3 class="fw-bold mb-0"><?= $therapist_count ?></h3>
                    </div>
                    <div class="icon-box"><i class="bi bi-person-heart"></i></div>
                </div>
                <p class="small text-success mt-2 mb-0">Currently Active</p>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card card-stat p-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted small fw-bold">ROOMS</h6>
                        <h3 class="fw-bold mb-0"><?= $room_count ?></h3>
                    </div>
                    <div class="icon-box"><i class="bi bi-house-door"></i></div>
                </div>
                <p class="small text-primary mt-2 mb-0">Total Capacity</p>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card card-stat p-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted small fw-bold">CONFIRMED</h6>
                        <h3 class="fw-bold mb-0"><?= $confirmed_count ?></h3>
                    </div>
                    <div class="icon-box"><i class="bi bi-check2-circle"></i></div>
                </div>
                <p class="small text-warning mt-2 mb-0">Upcoming Bookings</p>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card card-stat p-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted small fw-bold">COMPLETED</h6>
                        <h3 class="fw-bold mb-0"><?= $completed_month ?></h3>
                    </div>
                    <div class="icon-box"><i class="bi bi-flag"></i></div>
                </div>
                <p class="small text-muted mt-2 mb-0">This Month</p>
            </div>
        </div>
    </div>

    <!-- Reports Section -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 rounded-4" style="min-height: 350px;">
                <h5 class="fw-bold mb-4">Estimated Revenue Tracking</h5>
                <div class="d-flex align-items-baseline gap-2">
                    <h1 class="fw-bold text-dark">₱<?= $est_revenue ?></h1>
                    <span class="text-muted small">Lifetime Gross</span>
                </div>
                <hr>
                <div class="mt-4">
                    <p class="text-muted mb-2">System Insight</p>
                    <div class="alert alert-light border-0 small">
                        <i class="bi bi-info-circle me-2"></i> Revenue is calculated based on confirmed and completed appointments.
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 rounded-4">
                <h5 class="fw-bold mb-4">Quick Insights</h5>
                
                <div class="mb-4">
                    <label class="text-muted small d-block">MOST BOOKED SERVICE</label>
                    <span class="fw-bold"><?= $popular_service ? $popular_service['name'] : 'No data yet' ?></span>
                </div>

                <div class="mb-4">
                    <label class="text-muted small d-block">PEAK BOOKING TIME</label>
                    <span class="fw-bold"><?= $display_peak ?></span>
                </div>

                <a href="reports.php" class="btn btn-outline-dark w-100 rounded-pill btn-sm py-2">View Detailed Reports</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>