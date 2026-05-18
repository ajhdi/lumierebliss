<?php
// /includes/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    /* ─── Sidebar ───────────────────────────────────────────────── */
    .sidebar {
        position: fixed;
        inset: 0 auto 0 0;
        width: var(--sidebar-w);
        background: var(--dark);
        display: flex;
        flex-direction: column;
        z-index: 1000;
        transition: transform .35s cubic-bezier(.4,0,.2,1);
    }
    .sidebar::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse at 30% 20%, rgba(201,169,110,0.07) 0%, transparent 60%);
        pointer-events: none;
    }

    /* Brand */
    .sidebar-brand {
        padding: 36px 28px 28px;
        border-bottom: 1px solid var(--border);
    }
    .sidebar-brand-label {
        font-family: 'Cormorant Garamond', serif;
        font-weight: 300;
        font-size: 1.55rem;
        color: var(--white);
        letter-spacing: .08em;
        line-height: 1.1;
    }
    .sidebar-brand-label em {
        font-style: italic;
        color: var(--gold);
    }
    .sidebar-brand-sub {
        font-size: .7rem;
        font-weight: 500;
        letter-spacing: .18em;
        text-transform: uppercase;
        color: var(--muted);
        margin-top: 4px;
    }

    /* Nav */
    .sidebar-nav {
        flex: 1;
        padding: 24px 0;
        overflow-y: auto;
    }
    .nav-section-label {
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: .2em;
        text-transform: uppercase;
        color: var(--muted);
        padding: 16px 28px 8px;
    }
    .nav-item {
        display: flex;
        align-items: center;
        gap: 13px;
        padding: 13px 28px;
        color: rgba(255,255,255,.5);
        font-size: .88rem;
        font-weight: 500;
        text-decoration: none;
        transition: color .2s, background .2s;
        position: relative;
        border-left: 3px solid transparent;
    }
    .nav-item i {
        font-size: 1.05rem;
        width: 20px;
        text-align: center;
        flex-shrink: 0;
    }
    .nav-item:hover {
        color: var(--gold-light);
        background: rgba(201,169,110,.06);
        border-left-color: rgba(201,169,110,.4);
    }
    .nav-item.active {
        color: var(--gold);
        background: rgba(201,169,110,.1);
        border-left-color: var(--gold);
    }
    .nav-item.active i { color: var(--gold); }

    /* Footer */
    .sidebar-footer {
        padding: 20px 0 28px;
        border-top: 1px solid var(--border);
    }
    .nav-item.danger { color: rgba(220,80,80,.7); }
    .nav-item.danger:hover {
        color: #e05555;
        background: rgba(220,80,80,.07);
        border-left-color: #e05555;
    }

    /* Mobile toggle */
    .mobile-toggle {
        display: none;
        position: fixed;
        top: 18px;
        left: 18px;
        z-index: 1100;
        background: var(--dark);
        border: 1px solid var(--border);
        color: var(--gold);
        width: 42px;
        height: 42px;
        border-radius: 10px;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        cursor: pointer;
    }
    .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.55);
        z-index: 999;
    }

    @media (max-width: 991px) {
        .sidebar { transform: translateX(-100%); }
        .sidebar.open { transform: translateX(0); }
        .main-content { margin-left: 0 !important; padding: 80px 24px 40px !important; }
        .mobile-toggle { display: flex; }
        .sidebar-overlay.visible { display: block; }
    }
</style>

<!-- Mobile Toggle -->
<button class="mobile-toggle" id="mobileToggle" aria-label="Open menu">
    <i class="bi bi-list"></i>
</button>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-label">Lumiére <em>&amp;</em> Bliss</div>
        <div class="sidebar-brand-sub">Administration Console</div>
    </div>

    <div class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <a href="dashboard.php" class="nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>

        <div class="nav-section-label">Management</div>
        <a href="manage_appointment.php" class="nav-item <?= $current_page === 'manage_appointment.php' ? 'active' : '' ?>">
            <i class="bi bi-calendar-event"></i> Appointments
        </a>
         <a href="manage_cosmetics.php" class="nav-item <?= $current_page === 'manage_cosmetics.php' ? 'active' : '' ?>">
            <i class="bi bi-stars"></i> Cosmetics
        </a>
        <a href="manage_promotions.php" class="nav-item <?= $current_page === 'manage_promotion.php' ? 'active' : '' ?>">
            <i class="bi bi-tag"></i> Promotions
        </a>
        <a href="manage_therapist.php" class="nav-item <?= $current_page === 'manage_therapist.php' ? 'active' : '' ?>">
            <i class="bi bi-person-badge"></i> Therapists
        </a>
        <a href="manage_treatments.php" class="nav-item <?= $current_page === 'manage_treatments.php' ? 'active' : '' ?>">
            <i class="bi bi-droplet-half"></i> Treatments
        </a>
        <a href="manage_room.php" class="nav-item <?= $current_page === 'manage_room.php' ? 'active' : '' ?>">
            <i class="bi bi-door-open"></i> Rooms
        </a>
        <a href="reports.php" class="nav-item <?= $current_page === 'manage_room.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-text"></i> Reports
        </a>
        <a href="manage_account.php" class="nav-item <?= $current_page === 'manage_account.php' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Accounts
        </a>

        <div class="nav-section-label">System</div>
        <a href="system_logs.php" class="nav-item <?= $current_page === 'system_logs.php' ? 'active' : '' ?>">
            <i class="bi bi-shield-lock"></i> Audit Logs
        </a>
    </div>

    <div class="sidebar-footer">
        <a href="logout.php" class="nav-item danger">
            <i class="bi bi-box-arrow-right"></i> Sign Out
        </a>
    </div>
</nav>

<script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle  = document.getElementById('mobileToggle');

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('visible');
    });
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('visible');
    });
</script>