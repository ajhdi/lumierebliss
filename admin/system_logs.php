<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Fetch Logs
|--------------------------------------------------------------------------
| Updated for your NEW system_logs table structure:
| - user_type
| - user_identifier
| - action
| - created_at
|
| Since you removed the "details" column but want to KEEP details,
| we will display the full action text as the details.
|--------------------------------------------------------------------------
*/

$filter_date = $_GET['filter_date'] ?? '';

if ($filter_date) {
    $stmt = $pdo->prepare("
        SELECT * 
        FROM system_logs 
        WHERE DATE(created_at) = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$filter_date]);
} else {
    $stmt = $pdo->query("
        SELECT * 
        FROM system_logs 
        ORDER BY created_at DESC
    ");
}

$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs — Lumiére &amp; Bliss</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        :root {
            --white: #ffffff;
            --cream: #fdfbf7;
            --gold: #c9a96e;
            --gold-light: #e8d5b0;
            --dark: #1a1a1a;
            --dark-soft: #2e2e2e;
            --muted: #8a8070;
            --border: rgba(201,169,110,.22);
            --sidebar-w: 270px;
            --radius-lg: 18px;
            --shadow: 0 8px 32px rgba(26,26,26,.07);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: var(--cream);
            font-family: 'DM Sans', sans-serif;
            color: var(--dark);
            overflow-x: hidden;
        }

        .main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            padding: 48px 44px 60px;
        }

        .topbar-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.4rem;
            font-weight: 600;
            line-height: 1.1;
        }

        .topbar-title span {
            display: block;
            font-size: .75rem;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 6px;
            font-family: 'DM Sans', sans-serif;
        }

        .gold-rule {
            width: 48px;
            height: 2px;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
            margin: 16px 0 36px;
        }

        .section-eyebrow {
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 16px;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-wrap {
            position: relative;
        }

        .search-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
        }

        .search-input {
            width: 260px;
            padding: 10px 16px 10px 38px;
            border-radius: 50px;
            border: 1.5px solid rgba(201,169,110,.25);
            outline: none;
        }

        .filter-select {
            padding: 10px 16px;
            border-radius: 50px;
            border: 1.5px solid rgba(201,169,110,.25);
            outline: none;
        }

        .record-pill {
            padding: 6px 16px;
            border-radius: 50px;
            border: 1px solid var(--border);
            font-size: .72rem;
            text-transform: uppercase;
            color: var(--muted);
            background: var(--cream);
            font-weight: 700;
        }

        .table-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid rgba(201,169,110,.1);
        }

        .luxe-table {
            width: 100%;
            border-collapse: collapse;
        }

        .luxe-table thead tr {
            background: var(--cream);
            border-bottom: 2px solid rgba(201,169,110,.15);
        }

        .luxe-table thead th {
            padding: 14px 20px;
            font-size: .67rem;
            text-transform: uppercase;
            letter-spacing: .18em;
            color: var(--muted);
        }

        .luxe-table tbody tr {
            border-bottom: 1px solid rgba(201,169,110,.08);
        }

        .luxe-table tbody tr:hover {
            background: rgba(201,169,110,.04);
        }

        .luxe-table tbody td {
            padding: 15px 20px;
            font-size: .875rem;
            vertical-align: middle;
        }

        .cell-user {
            font-weight: 700;
            color: var(--dark);
        }

        .cell-details {
            color: var(--muted);
            max-width: 450px;
        }

        .cell-time {
            white-space: nowrap;
            color: var(--muted);
        }

        .cell-time strong {
            display: block;
            color: var(--dark-soft);
        }

        .user-type {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .type-admin {
            background: rgba(74,122,170,.1);
            color: #4a7aaa;
        }

        .type-user {
            background: rgba(90,138,90,.1);
            color: #4a7a4a;
        }

        .type-therapist {
            background: rgba(120,80,160,.1);
            color: #7850a0;
        }

        .action-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .badge-login {
            background: rgba(90,138,90,.1);
            color: #4a7a4a;
        }

        .badge-logout {
            background: rgba(138,128,112,.12);
            color: #8a8070;
        }

        .badge-add {
            background: rgba(74,122,170,.1);
            color: #4a7aaa;
        }

        .badge-edit {
            background: rgba(192,122,48,.1);
            color: #c07a30;
        }

        .badge-delete {
            background: rgba(180,60,60,.08);
            color: #b43c3c;
        }

        .badge-default {
            background: rgba(138,128,112,.1);
            color: #8a8070;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state-icon {
            font-size: 2.5rem;
            color: var(--gold);
            opacity: .35;
            margin-bottom: 15px;
        }

        .empty-state-text {
            font-size: 1.1rem;
            color: var(--muted);
            font-family: 'Cormorant Garamond', serif;
        }

        @media (max-width: 991px) {
            .main-content {
                margin-left: 0;
                padding: 30px 20px;
            }

            .search-input {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main-content">

    <div class="topbar-title">
        <span>System</span>
        Audit Logs
    </div>

    <div class="gold-rule"></div>

    <p class="section-eyebrow">Activity Record</p>

    <div class="toolbar">

        <div style="display:flex; gap:10px; flex-wrap:wrap;">

            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text"
                       class="search-input"
                       id="searchInput"
                       placeholder="Search user or action...">
            </div>

            <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap;">

                <input type="date"
                       name="filter_date"
                       class="filter-select"
                       value="<?= htmlspecialchars($filter_date) ?>"
                       onchange="this.form.submit()">

                <?php if ($filter_date): ?>
                    <a href="system_logs.php"
                       class="filter-select"
                       style="text-decoration:none; color:#b43c3c;">
                        Clear
                    </a>
                <?php endif; ?>

            </form>

        </div>

        <div class="record-pill" id="recordCount">
            <?= count($logs) ?> record<?= count($logs) != 1 ? 's' : '' ?>
        </div>

    </div>

    <div class="table-card">

        <div style="overflow-x:auto;">

            <table class="luxe-table" id="logsTable">

                <thead>
                    <tr>
                        <th>User Type</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>

                <tbody id="logsBody">

                <?php if ($logs): ?>

                    <?php foreach ($logs as $log): ?>

                        <?php
                            $action = $log['action'];
                            $action_lower = strtolower($action);

                            if (str_contains($action_lower, 'login')) {
                                $badge = 'badge-login';
                            } elseif (str_contains($action_lower, 'logout')) {
                                $badge = 'badge-logout';
                            } elseif (str_contains($action_lower, 'add')) {
                                $badge = 'badge-add';
                            } elseif (
                                str_contains($action_lower, 'edit') ||
                                str_contains($action_lower, 'update')
                            ) {
                                $badge = 'badge-edit';
                            } elseif (str_contains($action_lower, 'delete')) {
                                $badge = 'badge-delete';
                            } else {
                                $badge = 'badge-default';
                            }

                            $user_type = strtolower($log['user_type']);

                            $dt = new DateTime($log['created_at']);
                        ?>

                        <tr class="log-row"
                            data-action="<?= htmlspecialchars($action) ?>"
                            data-user="<?= htmlspecialchars($log['user_identifier']) ?>">

                            <td>
                                <span class="user-type type-<?= $user_type ?>">
                                    <?= htmlspecialchars($log['user_type']) ?>
                                </span>
                            </td>

                            <td class="cell-user">
                                <?= htmlspecialchars($log['user_identifier']) ?>
                            </td>

                            <td>
                                <span class="action-badge <?= $badge ?>">
                                    <?= htmlspecialchars($action) ?>
                                </span>
                            </td>

                            <!-- KEEP DETAILS -->
                            <td class="cell-details">
                                <?= htmlspecialchars($action) ?>
                            </td>

                            <td class="cell-time">
                                <strong><?= $dt->format('M d, Y') ?></strong>
                                <?= $dt->format('g:i A') ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="bi bi-shield-lock"></i>
                                </div>

                                <div class="empty-state-text">
                                    No activity has been recorded yet.
                                </div>
                            </div>
                        </td>
                    </tr>

                <?php endif; ?>

                <tr id="emptyRow" style="display:none;">
                    <td colspan="5">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="bi bi-shield-x"></i>
                            </div>

                            <div class="empty-state-text">
                                No logs match your search.
                            </div>
                        </div>
                    </td>
                </tr>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script>
function filterLogs() {

    const search = document.getElementById('searchInput').value.toLowerCase();

    const rows = document.querySelectorAll('.log-row');

    let visible = 0;

    rows.forEach(row => {

        const action = row.dataset.action.toLowerCase();
        const user = row.dataset.user.toLowerCase();

        const show =
            action.includes(search) ||
            user.includes(search);

        row.style.display = show ? '' : 'none';

        if (show) visible++;
    });

    document.getElementById('emptyRow').style.display =
        visible === 0 ? '' : 'none';

    document.getElementById('recordCount').textContent =
        visible + ' record' + (visible !== 1 ? 's' : '');
}

document.getElementById('searchInput')
    .addEventListener('keyup', filterLogs);
</script>

</body>
</html>