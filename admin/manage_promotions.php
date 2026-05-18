<?php
session_start();
require_once '../config/db.php';
require_once '../includes/log_action.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}

/* ================= SAVE PROMOTION ================= */
if (isset($_POST['save_promotion'])) {
    header('Content-Type: application/json');
    try {
        $promo_name       = trim($_POST['promo_name']);
        $tagline          = trim($_POST['tagline']);
        $included_service = trim($_POST['included_service']);
        $duration_minutes = intval($_POST['duration_minutes']);
        $original_price   = floatval($_POST['original_price']);
        $price_now        = floatval($_POST['price_now']);
        $valid_dates      = trim($_POST['valid_dates']);
        $id               = $_POST['promo_id'] ?? '';

        if (!empty($id)) {
            $stmt = $pdo->prepare("
                UPDATE promotions 
                SET promo_name=?, tagline=?, included_service=?, duration_minutes=?, original_price=?, price_now=?, valid_dates=?
                WHERE promo_id=?
            ");
            logAction($pdo, "Updated promotion: $promo_name");
echo json_encode(["status" => "success", "message" => "Promotion updated successfully"]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO promotions (promo_name, tagline, included_service, duration_minutes, original_price, price_now, valid_dates)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
           logAction($pdo, "Added promotion: $promo_name");
echo json_encode(["status" => "success", "message" => "Promotion added successfully"]);
        }
        exit();
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit();
    }
}

/* ================= ARCHIVE PROMOTION ================= */
if (isset($_POST['archive_id'])) {
    header('Content-Type: application/json');
    try {
       $stmt = $pdo->prepare("UPDATE promotions SET is_archived = 1 WHERE promo_id = ?");
$stmt->execute([$_POST['archive_id']]);

$archived = $pdo->prepare("SELECT promo_name FROM promotions WHERE promo_id = ?");
$archived->execute([$_POST['archive_id']]);
$archivedName = $archived->fetchColumn();
logAction($pdo, "Archived promotion: $archivedName");

echo json_encode(["status" => "success", "message" => "Promotion archived successfully"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit();
}

/* ================= RESTORE PROMOTION ================= */
if (isset($_POST['restore_id'])) {
    header('Content-Type: application/json');
    try {
       $stmt = $pdo->prepare("UPDATE promotions SET is_archived = 0 WHERE promo_id = ?");
$stmt->execute([$_POST['restore_id']]);

$restored = $pdo->prepare("SELECT promo_name FROM promotions WHERE promo_id = ?");
$restored->execute([$_POST['restore_id']]);
$restoredName = $restored->fetchColumn();
logAction($pdo, "Restored promotion: $restoredName");

echo json_encode(["status" => "success", "message" => "Promotion restored successfully"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit();
}

/* ================= FETCH DATA ================= */
$promotions          = $pdo->query("SELECT * FROM promotions WHERE is_archived = 0 ORDER BY promo_id DESC")->fetchAll(PDO::FETCH_ASSOC);
$archived_promotions = $pdo->query("SELECT * FROM promotions WHERE is_archived = 1 ORDER BY promo_id DESC")->fetchAll(PDO::FETCH_ASSOC);
if (!$promotions)          $promotions = [];
if (!$archived_promotions) $archived_promotions = [];

/* ================= FETCH TREATMENTS, COSMETICS & ROOMS FROM DB ================= */
$treatments = $pdo->query("SELECT treatment_id, name FROM treatments WHERE status = 'available' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$cosmetics  = $pdo->query("SELECT cosmetic_id, name FROM cosmetics ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$rooms      = $pdo->query("SELECT room_id, room_name, room_type, additional_fee FROM rooms WHERE status = 'active' ORDER BY room_name ASC")->fetchAll(PDO::FETCH_ASSOC);
if (!$treatments) $treatments = [];
if (!$cosmetics)  $cosmetics  = [];
if (!$rooms)      $rooms      = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Promotions — Lumiére &amp; Bliss</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* ─── Design Tokens ─────────────────────────────────────────── */
        :root {
            --white:        #ffffff;
            --cream:        #fdfbf7;
            --gold:         #c9a96e;
            --gold-light:   #e8d5b0;
            --gold-dim:     rgba(201,169,110,0.15);
            --dark:         #1a1a1a;
            --dark-soft:    #2e2e2e;
            --muted:        #8a8070;
            --border:       rgba(201,169,110,0.22);
            --sidebar-w:    270px;
            --radius-lg:    18px;
            --radius-md:    12px;
            --shadow:       0 8px 32px rgba(26,26,26,0.08);
            --shadow-deep:  0 16px 48px rgba(26,26,26,0.14);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            background: var(--cream);
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            color: var(--dark);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            padding: 48px 44px 60px;
            transition: margin .35s cubic-bezier(.4,0,.2,1);
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 48px;
        }
        .topbar-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            font-size: 2.4rem;
            color: var(--dark);
            line-height: 1.1;
        }
        .topbar-title span {
            display: block;
            font-family: 'DM Sans', sans-serif;
            font-size: .75rem;
            font-weight: 500;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 6px;
        }
        .topbar-actions { display: flex; gap: 10px; align-items: center; }

        .gold-rule {
            width: 48px; height: 2px;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
            border-radius: 2px;
            margin-bottom: 36px;
        }

        select.form-control {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 16 16'%3E%3Cpath fill='%238a8070' d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 36px;
            cursor: pointer;
        }
        select.form-control:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px var(--gold-dim);
        }

        .section-eyebrow {
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 20px;
        }

        .btn-gold {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 22px;
            background: var(--dark); color: var(--white);
            border: 1.5px solid var(--dark);
            border-radius: 50px;
            font-family: 'DM Sans', sans-serif;
            font-size: .8rem; font-weight: 600; letter-spacing: .06em;
            cursor: pointer; text-decoration: none;
            transition: background .22s, border-color .22s, color .22s;
        }
        .btn-gold:hover { background: var(--gold); border-color: var(--gold); color: var(--white); }

        .btn-outline-gold {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 22px;
            background: transparent; color: var(--dark-soft);
            border: 1.5px solid var(--border);
            border-radius: 50px;
            font-family: 'DM Sans', sans-serif;
            font-size: .8rem; font-weight: 600; letter-spacing: .06em;
            cursor: pointer; text-decoration: none;
            transition: background .22s, border-color .22s, color .22s;
        }
        .btn-outline-gold:hover { border-color: var(--gold); color: var(--gold); }

        .badge-count {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 18px; height: 18px; padding: 0 5px;
            background: var(--gold-dim); color: var(--gold);
            border-radius: 20px; font-size: .65rem; font-weight: 700;
        }

        .promo-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 8px 0;
            box-shadow: var(--shadow);
            border: 1px solid rgba(201,169,110,.1);
        }

        .promo-table { width: 100%; border-collapse: collapse; }
        .promo-table thead tr { border-bottom: 1px solid rgba(201,169,110,.15); }
        .promo-table thead th {
            padding: 14px 24px;
            font-size: .63rem; font-weight: 700;
            letter-spacing: .2em; text-transform: uppercase;
            color: var(--muted);
        }
        .promo-table tbody tr {
            border-bottom: 1px solid rgba(201,169,110,.08);
            transition: background .18s;
        }
        .promo-table tbody tr:last-child { border-bottom: none; }
        .promo-table tbody tr:hover { background: rgba(201,169,110,.04); }
        .promo-table tbody td { padding: 18px 24px; vertical-align: middle; }
        .promo-name { font-weight: 600; color: var(--dark); font-size: .95rem; }
        .promo-dates { font-size: .8rem; color: var(--muted); }

        .action-btn {
            width: 34px; height: 34px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 50%;
            border: 1.5px solid rgba(201,169,110,.25);
            background: transparent; color: var(--muted);
            font-size: .8rem; cursor: pointer;
            transition: background .18s, border-color .18s, color .18s;
            margin-left: 4px;
        }
        .action-btn:hover { background: var(--gold-dim); border-color: var(--gold); color: var(--gold); }
        .action-btn.warn:hover { background: rgba(234,179,8,.1); border-color: #ca8a04; color: #ca8a04; }

        .empty-state { text-align: center; padding: 64px 24px; }
        .empty-state i { font-size: 2.5rem; color: var(--gold-light); display:block; margin-bottom: 16px; }
        .empty-state p { color: var(--muted); font-size: .875rem; }

        /* ─── Modals ─────────────────────────────────────────────────── */
        .modal-content { border: none; border-radius: var(--radius-lg) !important; box-shadow: var(--shadow-deep); }
        .modal-header  { border-bottom: none !important; padding: 32px 32px 16px !important; }
        .modal-body    { padding: 0 32px 8px !important; }
        .modal-footer  { border-top: none !important; padding: 16px 32px 28px !important; }

        .modal-eyebrow {
            font-size: .63rem; font-weight: 700;
            letter-spacing: .2em; text-transform: uppercase;
            color: var(--gold); margin-bottom: 6px;
        }
        .modal-title-styled {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600; font-size: 1.6rem;
            color: var(--dark); line-height: 1.15;
        }

        .detail-row {
            display: flex; flex-direction: column; gap: 4px;
            padding: 16px 0;
            border-bottom: 1px solid rgba(201,169,110,.1);
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label {
            font-size: .62rem; font-weight: 700;
            letter-spacing: .2em; text-transform: uppercase;
            color: var(--muted);
        }
        .detail-value { font-size: .93rem; color: var(--dark); font-weight: 500; }
        .detail-value.gold { color: var(--gold); font-weight: 700; }

        .form-label-styled {
            font-size: .63rem; font-weight: 700;
            letter-spacing: .15em; text-transform: uppercase;
            color: var(--muted); margin-bottom: 8px; display: block;
        }
        .form-control {
            border: 1.5px solid rgba(201,169,110,.25);
            border-radius: var(--radius-md);
            padding: 10px 14px;
            font-family: 'DM Sans', sans-serif;
            font-size: .88rem; color: var(--dark);
            background: var(--cream);
            transition: border-color .2s, box-shadow .2s;
            width: 100%;
        }
        .form-control:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(201,169,110,.12);
            background: var(--white);
            outline: none;
        }
        .form-text-styled {
            font-size: .72rem; color: var(--muted);
            margin-top: 6px;
            display: flex; align-items: flex-start; gap: 6px;
        }
        .form-text-styled i { color: var(--gold); margin-top: 1px; flex-shrink: 0; }

        .modal-gold-rule {
            width: 36px; height: 2px;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
            border-radius: 2px;
            margin: 20px 0 24px;
        }

        .badge-archived {
            display: inline-block;
            background: rgba(201,169,110,.12); color: var(--gold);
            font-size: .62rem; padding: 3px 10px;
            border-radius: 20px; font-weight: 700;
            letter-spacing: .08em; text-transform: uppercase;
        }

        .btn-restore {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 16px;
            background: transparent; color: var(--dark-soft);
            border: 1.5px solid var(--border);
            border-radius: 50px;
            font-family: 'DM Sans', sans-serif;
            font-size: .75rem; font-weight: 600;
            cursor: pointer;
            transition: background .18s, border-color .18s, color .18s;
        }
        .btn-restore:hover { background: rgba(201,169,110,.1); border-color: var(--gold); color: var(--gold); }

        /* ─── Sequential Items Builder ──────────────────────────────── */
        .builder-wrapper {
            border: 1.5px solid rgba(201,169,110,.25);
            border-radius: var(--radius-md);
            background: var(--cream);
            overflow: hidden;
        }

        /* Step tabs */
        .builder-tabs {
            display: flex;
            border-bottom: 1.5px solid rgba(201,169,110,.2);
            background: var(--white);
        }
        .builder-tab {
            flex: 1;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 14px 10px;
            font-size: .72rem; font-weight: 700;
            letter-spacing: .12em; text-transform: uppercase;
            color: var(--muted);
            border: none; background: none;
            cursor: pointer;
            border-bottom: 2.5px solid transparent;
            margin-bottom: -1.5px;
            transition: color .2s, border-color .2s, background .2s;
            position: relative;
        }
        .builder-tab:hover { color: var(--dark); background: rgba(201,169,110,.04); }
        .builder-tab.active {
            color: var(--dark);
            border-bottom-color: var(--gold);
            background: var(--cream);
        }
        .builder-tab .tab-icon { font-size: .95rem; }
        .builder-tab .tab-badge {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 18px; height: 18px; padding: 0 5px;
            background: var(--gold-dim); color: var(--gold);
            border-radius: 20px; font-size: .62rem; font-weight: 700;
        }
        .builder-tab.has-items .tab-badge { background: var(--gold); color: var(--white); }

        /* Step panels */
        .builder-panels { padding: 16px; }
        .builder-panel { display: none; }
        .builder-panel.active { display: block; }

        /* Tags area */
        .items-tags {
            display: flex; flex-wrap: wrap; gap: 8px;
            min-height: 38px;
            margin-bottom: 12px;
            padding: 8px;
            background: var(--white);
            border-radius: 10px;
            border: 1px dashed rgba(201,169,110,.3);
        }
        .item-tag {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px;
            background: var(--gold-dim);
            border: 1px solid rgba(201,169,110,.35);
            border-radius: 20px;
            font-size: .78rem; font-weight: 600;
            color: var(--dark-soft);
        }
        .item-tag .tag-type {
            font-size: .58rem; font-weight: 700;
            letter-spacing: .1em; text-transform: uppercase;
            color: var(--gold);
        }
        .item-tag .remove-tag {
            background: none; border: none;
            color: var(--muted); cursor: pointer;
            font-size: .75rem; padding: 0; line-height: 1;
            transition: color .15s;
        }
        .item-tag .remove-tag:hover { color: #c0392b; }
        .items-tags-empty {
            font-size: .8rem; color: var(--muted);
            font-style: italic;
            padding: 4px 2px;
            width: 100%;
        }

        /* Add-item row */
        .add-item-row {
            display: flex; gap: 8px; align-items: center;
            flex-wrap: wrap;
        }
        .add-item-row select { flex: 1; min-width: 160px; }
        .btn-add-item {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 9px 18px;
            background: var(--dark); color: var(--white);
            border: none; border-radius: 50px;
            font-family: 'DM Sans', sans-serif;
            font-size: .78rem; font-weight: 600;
            cursor: pointer; white-space: nowrap;
            transition: background .2s;
        }
        .btn-add-item:hover { background: var(--gold); }

        /* Step hint */
        .step-hint {
            font-size: .72rem; color: var(--muted);
            margin-top: 10px;
            display: flex; align-items: flex-start; gap: 6px;
        }
        .step-hint i { color: var(--gold); flex-shrink: 0; margin-top: 1px; }

        @media (max-width: 991px) {
            .main-content { margin-left: 0; padding: 80px 24px 40px; }
        }
        @media (max-width: 600px) {
            .topbar { flex-direction: column; align-items: flex-start; gap: 16px; }
            .promo-table thead th:nth-child(2),
            .promo-table tbody td:nth-child(2) { display: none; }
            .builder-tab span.tab-label { display: none; }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .topbar          { animation: fadeUp .45s ease both; }
        .gold-rule       { animation: fadeUp .45s .06s ease both; }
        .section-eyebrow { animation: fadeUp .45s .1s ease both; }
        .promo-card      { animation: fadeUp .5s .15s ease both; }
    </style>
</head>
<body>

<?php require_once '../includes/sidebar.php'; ?>

<!-- ── Main Content ───────────────────────────────────────────────── -->
<div class="main-content">

    <div class="topbar">
        <div class="topbar-title">
            <span>Admin Panel</span>
            Manage Promotions
        </div>
        <div class="topbar-actions">
            <button class="btn-outline-gold" data-bs-toggle="modal" data-bs-target="#archiveListModal">
                <i class="bi bi-archive"></i> Archived
                <?php if (count($archived_promotions) > 0): ?>
                    <span class="badge-count"><?= count($archived_promotions) ?></span>
                <?php endif; ?>
            </button>
            <button class="btn-gold" data-bs-toggle="modal" data-bs-target="#promoModal" onclick="resetModal()">
                <i class="bi bi-plus-lg"></i> Add New Promotion
            </button>
        </div>
    </div>

    <div class="gold-rule"></div>

    <p class="section-eyebrow">Active Promotions</p>

    <div class="promo-card">
        <table class="promo-table">
            <thead>
                <tr>
                    <th>Promotion Name</th>
                    <th>Valid Dates</th>
                    <th style="text-align:right; padding-right:28px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($promotions as $p): ?>
                <tr>
                    <td>
                        <div class="promo-name"><?= htmlspecialchars($p['promo_name']) ?></div>
                        <?php if (!empty($p['tagline'])): ?>
                            <div class="promo-dates" style="margin-top:3px;"><?= htmlspecialchars($p['tagline']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="promo-dates"><?= htmlspecialchars($p['valid_dates']) ?></div>
                    </td>
                    <td style="text-align:right;">
                        <button class="action-btn" title="View Details" onclick='viewPromo(<?= json_encode($p) ?>)'>
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="action-btn" title="Edit Promotion" onclick='editPromo(<?= json_encode($p) ?>)'>
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="action-btn warn" title="Archive" onclick="archivePromo(<?= $p['promo_id'] ?>, '<?= htmlspecialchars(addslashes($p['promo_name'])) ?>')">
                            <i class="bi bi-archive"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if (empty($promotions)): ?>
                <tr>
                    <td colspan="3">
                        <div class="empty-state">
                            <i class="bi bi-tag"></i>
                            <p>No promotions yet. Add one to get started.</p>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div><!-- /.main-content -->


<!-- ===================== VIEW PROMOTION MODAL ===================== -->
<div class="modal fade" id="viewPromoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="modal-eyebrow">Promotion Details</div>
                    <div class="modal-title-styled" id="viewPromoName">—</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-gold-rule" style="margin-left:32px;"></div>
            <div class="modal-body">
                <div class="detail-row">
                    <span class="detail-label">Tagline</span>
                    <span class="detail-value" id="viewTagline">—</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Included Services</span>
                    <span class="detail-value" id="viewIncludedService">—</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Duration</span>
                    <span class="detail-value" id="viewDuration">—</span>
                </div>
                <div class="detail-row">
                    <div style="display:flex; gap:32px;">
                        <div>
                            <span class="detail-label">Original Price</span>
                            <span class="detail-value" style="text-decoration:line-through; color:var(--muted);" id="viewOriginalPrice">—</span>
                        </div>
                        <div>
                            <span class="detail-label">Promo Price</span>
                            <span class="detail-value gold" id="viewPromoPrice">—</span>
                        </div>
                    </div>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Valid Dates</span>
                    <span class="detail-value" id="viewValidDates">—</span>
                </div>
            </div>
            <div class="modal-footer" style="padding-top:20px !important;">
                <button class="btn-outline-gold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<!-- ===================== ARCHIVED PROMOTIONS MODAL ===================== -->
<div class="modal fade" id="archiveListModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="modal-eyebrow">Promotions</div>
                    <div class="modal-title-styled">
                        <i class="bi bi-archive me-2" style="font-size:1.3rem; color:var(--muted); vertical-align:middle;"></i>Archived Promotions
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-gold-rule" style="margin-left:32px;"></div>
            <div class="modal-body" style="padding-bottom:24px !important;">
                <?php if (empty($archived_promotions)): ?>
                    <div class="empty-state" style="padding:48px 24px;">
                        <i class="bi bi-archive" style="font-size:2.2rem; color:var(--gold-light); display:block; margin-bottom:12px;"></i>
                        <p>No archived promotions.</p>
                    </div>
                <?php else: ?>
                <table class="promo-table">
                    <thead>
                        <tr>
                            <th>Promotion Name</th>
                            <th>Valid Dates</th>
                            <th style="text-align:right; padding-right:8px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($archived_promotions as $ap): ?>
                        <tr>
                            <td>
                                <div class="promo-name"><?= htmlspecialchars($ap['promo_name']) ?></div>
                                <span class="badge-archived" style="margin-top:4px; display:inline-block;">Archived</span>
                            </td>
                            <td><div class="promo-dates"><?= htmlspecialchars($ap['valid_dates']) ?></div></td>
                            <td style="text-align:right;">
                                <button class="btn-restore" onclick="restorePromo(<?= $ap['promo_id'] ?>, '<?= htmlspecialchars(addslashes($ap['promo_name'])) ?>')">
                                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<!-- ===================== ADD / EDIT PROMOTION MODAL ===================== -->
<div class="modal fade" id="promoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="modal-eyebrow">Promotions</div>
                    <div class="modal-title-styled" id="promoModalTitle">Add New Promotion</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-gold-rule" style="margin-left:32px;"></div>
            <div class="modal-body">
                <input type="hidden" id="promo_id">
                <div class="row g-3">

                    <!-- Promotion Name -->
                    <div class="col-12">
                        <label class="form-label-styled">Promotion Name <span class="text-danger">*</span></label>
                        <input type="text" id="promo_name" class="form-control" placeholder="e.g. Birthday Bliss Celebration" required>
                    </div>

                    <!-- Tagline -->
                    <div class="col-12">
                        <label class="form-label-styled">Tagline <span class="text-danger">*</span></label>
                        <input type="text" id="tagline" class="form-control" placeholder="e.g. A luxurious gift of restoration for your special day." required>
                    </div>

                    <!-- ══════════════ SEQUENTIAL ITEMS BUILDER ══════════════ -->
                    <div class="col-12">
                        <label class="form-label-styled">What's Included <span class="text-danger">*</span></label>

                        <div class="builder-wrapper">

                            <!-- Step Tabs -->
                            <div class="builder-tabs">

                                <!-- Tab 1: Treatments -->
                                <button type="button" class="builder-tab active" id="tabTreatment" onclick="switchBuilderTab('treatment')">
                                    <i class="bi bi-stars tab-icon"></i>
                                    <span class="tab-label">Treatments</span>
                                    <span class="tab-badge" id="badgeTreatment">0</span>
                                </button>

                                <!-- Tab 2: Cosmetics -->
                                <button type="button" class="builder-tab" id="tabCosmetic" onclick="switchBuilderTab('cosmetic')">
                                    <i class="bi bi-droplet tab-icon"></i>
                                    <span class="tab-label">Cosmetics</span>
                                    <span class="tab-badge" id="badgeCosmetic">0</span>
                                </button>

                                <!-- Tab 3: Room -->
                                <button type="button" class="builder-tab" id="tabRoom" onclick="switchBuilderTab('room')">
                                    <i class="bi bi-door-open tab-icon"></i>
                                    <span class="tab-label">Room</span>
                                    <span class="tab-badge" id="badgeRoom">0</span>
                                </button>

                            </div><!-- /.builder-tabs -->

                            <!-- Panels -->
                            <div class="builder-panels">

                                <!-- Panel: Treatments -->
                                <div class="builder-panel active" id="panelTreatment">
                                    <div class="items-tags" id="tagsTreatment">
                                        <span class="items-tags-empty">No treatments added yet.</span>
                                    </div>
                                    <div class="add-item-row">
                                        <select id="selectTreatment" class="form-control">
                                            <option value="" disabled selected>— Select a Treatment —</option>
                                            <?php foreach ($treatments as $t): ?>
                                                <option value="<?= htmlspecialchars($t['name']) ?>"><?= htmlspecialchars($t['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn-add-item" onclick="addItem('treatment')">
                                            <i class="bi bi-plus-lg"></i> Add
                                        </button>
                                    </div>
                                    <div class="step-hint">
                                        <i class="bi bi-info-circle-fill"></i>
                                        Add as many treatments as this promotion includes, then switch to Cosmetics or Room.
                                    </div>
                                </div>

                                <!-- Panel: Cosmetics -->
                                <div class="builder-panel" id="panelCosmetic">
                                    <div class="items-tags" id="tagsCosmetic">
                                        <span class="items-tags-empty">No cosmetics added yet.</span>
                                    </div>
                                    <div class="add-item-row">
                                        <select id="selectCosmetic" class="form-control">
                                            <option value="" disabled selected>— Select a Cosmetic —</option>
                                            <?php foreach ($cosmetics as $c): ?>
                                                <option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn-add-item" onclick="addItem('cosmetic')">
                                            <i class="bi bi-plus-lg"></i> Add
                                        </button>
                                    </div>
                                    <div class="step-hint">
                                        <i class="bi bi-info-circle-fill"></i>
                                        Add as many cosmetics as this promotion includes.
                                    </div>
                                </div>

                                <!-- Panel: Room -->
                                <div class="builder-panel" id="panelRoom">
                                    <div class="items-tags" id="tagsRoom">
                                        <span class="items-tags-empty">No room added yet.</span>
                                    </div>
                                    <div class="add-item-row">
                                        <select id="selectRoom" class="form-control">
                                            <option value="" disabled selected>— Select a Room —</option>
                                            <?php foreach ($rooms as $r): ?>
                                                <option
                                                    value="<?= htmlspecialchars($r['room_name']) ?>"
                                                    data-type="<?= htmlspecialchars($r['room_type']) ?>"
                                                    data-fee="<?= $r['additional_fee'] ?>">
                                                    <?= htmlspecialchars($r['room_name']) ?>
                                                    (<?= htmlspecialchars($r['room_type']) ?>)
                                                    <?php if ($r['additional_fee'] > 0): ?>
                                                        — +₱<?= number_format($r['additional_fee'], 2) ?>
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn-add-item" onclick="addItem('room')">
                                            <i class="bi bi-plus-lg"></i> Add
                                        </button>
                                    </div>
                                    <div class="step-hint">
                                        <i class="bi bi-info-circle-fill"></i>
                                        Select the room type included in this promotion.
                                    </div>
                                </div>

                            </div><!-- /.builder-panels -->
                        </div><!-- /.builder-wrapper -->
                    </div>
                    <!-- ══════════════ END BUILDER ══════════════ -->

                    <!-- Duration, Original Price, Promo Price -->
                    <div class="col-md-4">
                        <label class="form-label-styled">Duration (minutes) <span class="text-danger">*</span></label>
                        <input type="number" id="duration_minutes" class="form-control" placeholder="e.g. 90" required min="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-styled">Original Price (₱) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="original_price" class="form-control" placeholder="e.g. 2150.00" required min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label-styled">Promo Price (₱) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="price_now" class="form-control" placeholder="e.g. 1850.00" required min="0">
                    </div>

                    <!-- Valid Dates -->
                    <div class="col-12">
                        <label class="form-label-styled">Valid Dates <span class="text-danger">*</span></label>
                        <input type="text" id="valid_dates" class="form-control" placeholder="e.g. May 1 – June 30, 2026" required>
                        <div class="form-text-styled">
                            <i class="bi bi-info-circle-fill"></i>
                            This promotion will be visible to customers on the promotions page.
                        </div>
                    </div>

                </div>
            </div><!-- /.modal-body -->
            <div class="modal-footer">
                <button type="button" class="btn-outline-gold" data-bs-dismiss="modal">Cancel</button>
                <button type="button" onclick="savePromo()" class="btn-gold">Save Promotion</button>
            </div>
        </div>
    </div>
</div>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ─── Included Items State ─────────────────────────────────────────── */
// Each category holds its own array of item names
const itemsState = {
    treatment: [],
    cosmetic:  [],
    room:      []
};

let activeTab = 'treatment';

/* ─── Tab Switcher ─────────────────────────────────────────────────── */
function switchBuilderTab(type) {
    activeTab = type;

    ['treatment', 'cosmetic', 'room'].forEach(t => {
        document.getElementById('tab'   + cap(t)).classList.toggle('active', t === type);
        document.getElementById('panel' + cap(t)).classList.toggle('active', t === type);
    });
}

function cap(str) { return str.charAt(0).toUpperCase() + str.slice(1); }

/* ─── Add Item ─────────────────────────────────────────────────────── */
function addItem(type) {
    const sel  = document.getElementById('select' + cap(type));
    const name = sel.value;

    if (!name) {
        Swal.fire({
            icon: 'warning',
            title: 'Nothing selected',
            text: 'Please choose a ' + type + ' first.',
            confirmButtonColor: '#c9a96e'
        });
        return;
    }

    // Prevent exact duplicates within the same category
    if (itemsState[type].includes(name)) {
        Swal.fire({
            icon: 'info',
            title: 'Already added',
            text: `"${name}" is already in this section.`,
            confirmButtonColor: '#c9a96e'
        });
        return;
    }

    itemsState[type].push(name);
    sel.value = '';  // reset dropdown
    renderTags(type);
    updateBadge(type);
}

/* ─── Remove Item ──────────────────────────────────────────────────── */
function removeItem(type, index) {
    itemsState[type].splice(index, 1);
    renderTags(type);
    updateBadge(type);
}

/* ─── Render Tags ──────────────────────────────────────────────────── */
const typeLabels = {
    treatment: '✦ Treatment',
    cosmetic:  '◈ Cosmetic',
    room:      '⬡ Room'
};
const emptyMessages = {
    treatment: 'No treatments added yet.',
    cosmetic:  'No cosmetics added yet.',
    room:      'No room added yet.'
};

function renderTags(type) {
    const container = document.getElementById('tags' + cap(type));
    const items     = itemsState[type];

    if (items.length === 0) {
        container.innerHTML = `<span class="items-tags-empty">${emptyMessages[type]}</span>`;
        return;
    }

    container.innerHTML = '';
    items.forEach((name, idx) => {
        const tag = document.createElement('div');
        tag.className = 'item-tag';
        tag.innerHTML = `
            <span class="tag-type">${typeLabels[type]}</span>
            <span>${escapeHtml(name)}</span>
            <button class="remove-tag" title="Remove" onclick="removeItem('${type}', ${idx})">
                <i class="bi bi-x-lg"></i>
            </button>`;
        container.appendChild(tag);
    });
}

/* ─── Update Badge Count ───────────────────────────────────────────── */
function updateBadge(type) {
    const count = itemsState[type].length;
    const badge = document.getElementById('badge' + cap(type));
    const tab   = document.getElementById('tab'   + cap(type));
    badge.textContent = count;
    tab.classList.toggle('has-items', count > 0);
}

/* ─── Build included_service string ───────────────────────────────── */
function buildIncludedService() {
    const all = [
        ...itemsState.treatment,
        ...itemsState.cosmetic,
        ...itemsState.room
    ];
    return all.join(' + ');
}

/* ─── Total item count ─────────────────────────────────────────────── */
function totalItems() {
    return itemsState.treatment.length + itemsState.cosmetic.length + itemsState.room.length;
}

/* ─── Helpers ──────────────────────────────────────────────────────── */
function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

/* ─── Reset Modal ──────────────────────────────────────────────────── */
function resetModal() {
    document.getElementById('promo_id').value         = '';
    document.getElementById('promo_name').value       = '';
    document.getElementById('tagline').value          = '';
    document.getElementById('duration_minutes').value = '';
    document.getElementById('original_price').value   = '';
    document.getElementById('price_now').value        = '';
    document.getElementById('valid_dates').value      = '';
    document.getElementById('selectTreatment').value  = '';
    document.getElementById('selectCosmetic').value   = '';
    document.getElementById('selectRoom').value       = '';
    document.getElementById('promoModalTitle').innerText = 'Add New Promotion';

    itemsState.treatment = [];
    itemsState.cosmetic  = [];
    itemsState.room      = [];

    ['treatment', 'cosmetic', 'room'].forEach(t => {
        renderTags(t);
        updateBadge(t);
    });

    switchBuilderTab('treatment');
}

document.getElementById('promoModal').addEventListener('hidden.bs.modal', resetModal);

/* ─── Edit Promo ───────────────────────────────────────────────────── */
// PHP-rendered list of treatment & room names for type detection
const treatmentNames = <?= json_encode(array_column($treatments, 'name')) ?>;
const roomNames      = <?= json_encode(array_column($rooms,      'room_name')) ?>;

function editPromo(p) {
    document.getElementById('promoModalTitle').innerText  = 'Edit Promotion';
    document.getElementById('promo_id').value             = p.promo_id;
    document.getElementById('promo_name').value           = p.promo_name;
    document.getElementById('tagline').value              = p.tagline;
    document.getElementById('duration_minutes').value     = p.duration_minutes;
    document.getElementById('original_price').value       = p.original_price;
    document.getElementById('price_now').value            = p.price_now;
    document.getElementById('valid_dates').value          = p.valid_dates;

    // Reset state
    itemsState.treatment = [];
    itemsState.cosmetic  = [];
    itemsState.room      = [];

    // Re-parse included_service back into categories
    if (p.included_service) {
        p.included_service.split(' + ').forEach(raw => {
            const name = raw.trim();
            if (!name) return;
            if (treatmentNames.includes(name)) {
                itemsState.treatment.push(name);
            } else if (roomNames.includes(name)) {
                itemsState.room.push(name);
            } else {
                itemsState.cosmetic.push(name);
            }
        });
    }

    ['treatment', 'cosmetic', 'room'].forEach(t => {
        renderTags(t);
        updateBadge(t);
    });

    switchBuilderTab('treatment');
    new bootstrap.Modal(document.getElementById('promoModal')).show();
}

/* ─── View Promo ───────────────────────────────────────────────────── */
function viewPromo(p) {
    document.getElementById('viewPromoName').innerText        = p.promo_name;
    document.getElementById('viewTagline').innerText          = p.tagline || '—';
    document.getElementById('viewIncludedService').innerText  = p.included_service || '—';
    document.getElementById('viewDuration').innerText         = p.duration_minutes ? p.duration_minutes + ' mins' : '—';
    document.getElementById('viewOriginalPrice').innerText    = p.original_price   ? '₱' + parseFloat(p.original_price).toLocaleString('en-PH', {minimumFractionDigits:2}) : '—';
    document.getElementById('viewPromoPrice').innerText       = p.price_now        ? '₱' + parseFloat(p.price_now).toLocaleString('en-PH',      {minimumFractionDigits:2}) : '—';
    document.getElementById('viewValidDates').innerText       = p.valid_dates || '—';
    new bootstrap.Modal(document.getElementById('viewPromoModal')).show();
}

/* ─── Save ─────────────────────────────────────────────────────────── */
function savePromo() {
    const promo_name       = document.getElementById('promo_name').value.trim();
    const tagline          = document.getElementById('tagline').value.trim();
    const duration_minutes = document.getElementById('duration_minutes').value.trim();
    const original_price   = document.getElementById('original_price').value.trim();
    const price_now        = document.getElementById('price_now').value.trim();
    const valid_dates      = document.getElementById('valid_dates').value.trim();

    if (!promo_name || !tagline || totalItems() === 0 || !duration_minutes || !original_price || !price_now || !valid_dates) {
        Swal.fire({
            icon: 'warning',
            title: 'All Fields Required',
            text: 'Please fill in all fields and add at least one treatment, cosmetic, or room.',
            confirmButtonColor: '#c9a96e'
        });
        return;
    }

    const included_service = buildIncludedService();

    const fd = new FormData();
    fd.append('save_promotion',   '1');
    fd.append('promo_id',         document.getElementById('promo_id').value);
    fd.append('promo_name',       promo_name);
    fd.append('tagline',          tagline);
    fd.append('included_service', included_service);
    fd.append('duration_minutes', duration_minutes);
    fd.append('original_price',   original_price);
    fd.append('price_now',        price_now);
    fd.append('valid_dates',      valid_dates);

    fetch(window.location.pathname, { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({ icon: 'success', title: 'Saved!', text: data.message, timer: 1500, showConfirmButton: false });
            bootstrap.Modal.getInstance(document.getElementById('promoModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            Swal.fire({ icon: 'error', title: 'Oops...', text: data.message });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'Server Error', text: 'Something went wrong!' }));
}

/* ─── Archive ──────────────────────────────────────────────────────── */
function archivePromo(id, name) {
    Swal.fire({
        title: 'Archive this promotion?',
        text: `"${name}" will be moved to the archive and hidden from customers.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, archive it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#c9a96e'
    }).then(result => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('archive_id', id);
            fetch(window.location.pathname, { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Archived!', text: data.message, timer: 1500, showConfirmButton: false });
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Swal.fire({ icon: 'error', title: 'Oops...', text: data.message });
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Server Error', text: 'Something went wrong!' }));
        }
    });
}

/* ─── Restore ──────────────────────────────────────────────────────── */
function restorePromo(id, name) {
    Swal.fire({
        title: 'Restore this promotion?',
        text: `"${name}" will be moved back to active promotions.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, restore it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#c9a96e'
    }).then(result => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('restore_id', id);
            fetch(window.location.pathname, { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Restored!', text: data.message, timer: 1500, showConfirmButton: false });
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Swal.fire({ icon: 'error', title: 'Oops...', text: data.message });
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Server Error', text: 'Something went wrong!' }));
        }
    });
}
</script>
</body>
</html>