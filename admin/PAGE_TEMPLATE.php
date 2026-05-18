<?php
// /admin/manage_treatments.php  (Template — apply same pattern to ALL admin pages)
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}

// ── Your page-specific PHP/queries go here ── //
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Treatments — Lumiére &amp; Bliss</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- ✅ Shared Admin CSS (sidebar + layout tokens) -->
    <link rel="stylesheet" href="assets/css/admin.css">

    <style>
        /* Page-specific styles only go here */
    </style>
</head>
<body>

    <?php require_once 'includes/sidebar.php'; ?>

    <div class="main-content">

        <!-- Top Bar -->
        <div class="topbar">
            <div class="topbar-title">
                <span>Management</span>
                Treatments
            </div>
            <div class="topbar-date">
                <strong id="js-date"></strong>
                Lumiére &amp; Bliss Studio
            </div>
        </div>

        <div class="gold-rule"></div>

        <p class="section-eyebrow">All Treatments</p>

        <!-- ── Your page content goes here ── -->

    </div><!-- /.main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const d = new Date();
        document.getElementById('js-date').textContent = d.toLocaleDateString('en-US', {
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
    </script>
</body>
</html>