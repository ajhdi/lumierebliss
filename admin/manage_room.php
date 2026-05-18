<?php
session_start();
require_once '../config/db.php';
require_once '../includes/log_action.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}

// Handle Room Save (Add/Edit)
// Handle Room Save (Add/Edit)
if (isset($_POST['save_room'])) {

    header('Content-Type: application/json');

    try {

        $room_name = $_POST['room_name'];
        $room_type = $_POST['room_type'];
        $fee       = $_POST['additional_fee'];
        $id        = $_POST['room_id'] ?? '';

        /* ─────────────────────────────
           IMAGE UPLOAD
        ───────────────────────────── */

        $image_name = null;

        if (
            isset($_FILES['room_image']) &&
            $_FILES['room_image']['error'] === 0
        ) {

            $upload_dir = "../assets/img/room/";

            // Create folder if not exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $tmp_name  = $_FILES['room_image']['tmp_name'];

            $extension = strtolower(
                pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION)
            );

            // Unique filename
            $image_name = 'room_' . time() . '_' . rand(1000,9999) . '.' . $extension;

            $destination = $upload_dir . $image_name;

            move_uploaded_file($tmp_name, $destination);
        }

        /* ─────────────────────────────
           UPDATE ROOM
        ───────────────────────────── */

        if (!empty($id)) {

            // If new image uploaded
            if ($image_name) {

                $stmt = $pdo->prepare("
                    UPDATE rooms 
                    SET room_name=?,
                        room_type=?,
                        additional_fee=?,
                        room_image=?
                    WHERE room_id=?
                ");

                $stmt->execute([
    $room_name,
    $room_type,
    $fee,
    $image_name,
    $id
]);

logAction($pdo, 'Edit Room', "Updated room '$room_name' (ID $id, Type: $room_type, new image uploaded).");
echo json_encode([
    "status"  => "success",
    "message" => "Room updated successfully"
]);

            } else {

                $stmt = $pdo->prepare("
                    UPDATE rooms 
                    SET room_name=?,
                        room_type=?,
                        additional_fee=?
                    WHERE room_id=?
                ");

                $stmt->execute([
    $room_name,
    $room_type,
    $fee,
    $id
]);

logAction($pdo, 'Edit Room', "Updated room '$room_name' (ID $id, Type: $room_type).");
echo json_encode([
    "status"  => "success",
    "message" => "Room updated successfully"
]);

            }  // closes: if ($image_name)

        } else {

            /* ─────────────────────────────
               INSERT ROOM
            ───────────────────────────── */

            $stmt = $pdo->prepare("
                INSERT INTO rooms (
                    room_name,
                    room_type,
                    additional_fee,
                    room_image
                ) VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $room_name,
                $room_type,
                $fee,
                $image_name
            ]);

           logAction($pdo, 'Add Room', "Added new room '$room_name' (Type: $room_type).");
echo json_encode([
    "status"  => "success",
    "message" => "Room added successfully"
]);
        }

    } catch (Exception $e) {

        echo json_encode([
            "status"  => "error",
            "message" => $e->getMessage()
        ]);
    }

    exit();
}

if (isset($_POST['archive_room_id'])) {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->prepare("UPDATE rooms SET status = 'archived' WHERE room_id = ?");
$stmt->execute([$_POST['archive_room_id']]);

$archivedRoom = $pdo->prepare("SELECT room_name FROM rooms WHERE room_id = ?");
$archivedRoom->execute([$_POST['archive_room_id']]);
$archivedRoomName = $archivedRoom->fetchColumn();
logAction($pdo, 'Archive Room', "Archived room '$archivedRoomName' (ID {$_POST['archive_room_id']}).");

echo json_encode(["status" => "success", "message" => "Room archived successfully"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

$room_type_filter = $_GET['room_type'] ?? '';
$status_filter    = $_GET['status']    ?? '';

$query  = "SELECT * FROM rooms WHERE 1=1";
$params = [];

if (!empty($room_type_filter)) {
    $query   .= " AND room_type = ?";
    $params[] = $room_type_filter;
}
if (!empty($status_filter)) {
    $query   .= " AND status = ?";
    $params[] = $status_filter;
}
$query .= " ORDER BY room_type ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$room_types = $pdo->query("SELECT DISTINCT room_type FROM rooms ORDER BY room_type ASC")->fetchAll();
$statuses   = $pdo->query("SELECT DISTINCT status    FROM rooms ORDER BY status    ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spa Rooms — Lumiére &amp; Bliss</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* ─── Tokens ─────────────────────────────────────────────── */
        :root {
            --white:       #ffffff;
            --cream:       #fdfbf7;
            --gold:        #c9a96e;
            --gold-light:  #e8d5b0;
            --gold-dim:    rgba(201,169,110,.13);
            --dark:#0d0d0d;
            --dark-soft:   #2e2e2e;
            --muted:       #8a8070;
            --border:      rgba(201,169,110,.22);
            --sidebar-w:   270px;
            --radius-lg:   18px;
            --radius-md:   12px;
            --shadow:      0 8px 32px rgba(26,26,26,.07);
            --shadow-deep: 0 16px 48px rgba(26,26,26,.13);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--cream);
            font-family: 'DM Sans', sans-serif;
            font-size: 18px;
            color: var(--dark);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ─── Layout ─────────────────────────────────────────────── */
        .main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            padding: 48px 44px 60px;
            transition: margin .35s cubic-bezier(.4,0,.2,1);
        }

        /* ─── Topbar ─────────────────────────────────────────────── */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 10px;
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
        .gold-rule {
            width: 48px;
            height: 2px;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
            border-radius: 2px;
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

        /* ─── Buttons ────────────────────────────────────────────── */
        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 26px;
            background: var(--dark);
            color: var(--gold-light);
            border: 1.5px solid var(--border);
            border-radius: 50px;
            font-size: .82rem;
            font-weight: 600;
            letter-spacing: .06em;
            cursor: pointer;
            text-decoration: none;
            transition: background .22s, color .22s, border-color .22s;
        }
        .btn-add:hover { background: var(--dark-soft); color: var(--gold); border-color: var(--gold); }

        /* ─── Filter Card ────────────────────────────────────────── */
        .filter-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 28px 32px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(201,169,110,.1);
            margin-bottom: 24px;
            animation: fadeUp .4s ease both;
        }
        .filter-card .form-label {
            font-size: .67rem;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
            display: block;
        }
        .filter-card .form-control,
        .filter-card .form-select {
            border: 1.5px solid rgba(201,169,110,.25);
            border-radius: var(--radius-md);
            background: var(--cream);
            color: var(--dark);
            font-size: .88rem;
            font-family: 'DM Sans', sans-serif;
            padding: 10px 14px;
            transition: border-color .2s, box-shadow .2s;
        }
        .filter-card .form-control:focus,
        .filter-card .form-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(201,169,110,.12);
            outline: none;
            background: var(--white);
        }
        .btn-apply {
            width: 100%;
            padding: 11px 0;
            background: var(--dark);
            color: var(--gold-light);
            border: none;
            border-radius: 50px;
            font-size: .82rem;
            font-weight: 600;
            letter-spacing: .08em;
            cursor: pointer;
            transition: background .22s, color .22s;
        }
        .btn-apply:hover { background: var(--dark-soft); color: var(--gold); }

        /* ─── Table Card ─────────────────────────────────────────── */
        .table-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            border: 1px solid rgba(201,169,110,.1);
            overflow: hidden;
            animation: fadeUp .45s .08s ease both;
        }
        .table-card-header {
            padding: 22px 32px 18px;
            border-bottom: 1px solid rgba(201,169,110,.12);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }
        .table-card-header-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            font-size: 1.3rem;
            color: var(--dark);
        }
        .search-wrap { position: relative; }
        .search-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: .85rem;
            pointer-events: none;
        }
        .search-input {
            padding: 9px 14px 9px 36px;
            border: 1.5px solid rgba(201,169,110,.25);
            border-radius: 50px;
            background: var(--cream);
            color: var(--dark);
            font-size: .82rem;
            font-family: 'DM Sans', sans-serif;
            width: 220px;
            transition: border-color .2s, box-shadow .2s, width .3s;
        }
        .search-input:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(201,169,110,.12);
            width: 260px;
            background: var(--white);
        }

        /* Table */
        .room-table { width: 100%; border-collapse: collapse; }
        .room-table thead tr {
            background: var(--cream);
            border-bottom: 2px solid rgba(201,169,110,.15);
        }
        .room-table thead th {
            font-size: .67rem;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--muted);
            padding: 14px 20px;
            white-space: nowrap;
        }
        .room-table tbody tr {
            border-bottom: 1px solid rgba(201,169,110,.08);
            transition: background .18s;
        }
        .room-table tbody tr:last-child { border-bottom: none; }
        .room-table tbody tr:hover { background: rgba(201,169,110,.04); }
        .room-table tbody td {
            padding: 16px 20px;
            font-size: .88rem;
            color: var(--dark-soft);
            vertical-align: middle;
        }
        .room-name-cell {
            font-weight: 700;
            color: var(--dark);
            font-size: .95rem;
        }

        /* Type badge */
        .badge-type {
            display: inline-block;
            padding: 4px 12px;
            background: var(--gold-dim);
            border: 1px solid var(--border);
            border-radius: 50px;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--dark-soft);
        }

        /* Status badges */
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
        }
        .badge-status::before {
            content: '';
            width: 5px; height: 5px;
            border-radius: 50%;
            background: currentColor;
        }
        .badge-available { background: rgba(90,138,90,.1); color: #5a8a5a; }
        .badge-archived  { background: rgba(180,60,60,.08); color: #b43c3c; }
        .badge-other     { background: rgba(138,128,112,.1); color: var(--muted); }

        /* Fee cell */
        .fee-cell {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            font-size: 1.05rem;
            color: var(--dark);
        }
        .fee-cell span { font-size: .75rem; color: var(--gold); margin-right: 1px; }

        /* Action buttons */
        .btn-icon {
            width: 34px; height: 34px;
            border-radius: 10px;
            border: 1.5px solid rgba(201,169,110,.25);
            background: transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .85rem;
            cursor: pointer;
            transition: background .2s, border-color .2s, color .2s;
            color: var(--dark-soft);
        }
        .btn-icon:hover { background: var(--gold-dim); border-color: var(--gold); color: var(--dark); }
        .btn-icon.danger { color: #b43c3c; border-color: rgba(180,60,60,.25); }
        .btn-icon.danger:hover { background: rgba(180,60,60,.08); border-color: #b43c3c; }

        /* Empty state */
        .empty-state { text-align: center; padding: 64px 24px; }
        .empty-state-icon { font-size: 2.5rem; color: var(--gold); opacity: .35; margin-bottom: 16px; }
        .empty-state-text { font-family: 'Cormorant Garamond', serif; font-weight: 400; font-size: 1.2rem; color: var(--muted); }

        /* ─── Modal ──────────────────────────────────────────────── */
        .modal-content {
            border: none;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-deep);
            background: var(--white);
            overflow: hidden;
        }
        .modal-header {
            background: var(--dark);
            padding: 24px 32px 20px;
            border-bottom: 2px solid var(--border);
            position: relative;
        }
        .modal-header::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 20% 50%, rgba(201,169,110,.08) 0%, transparent 60%);
            pointer-events: none;
        }
        .modal-title-wrap { flex: 1; }
        .modal-eyebrow {
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 3px;
        }
        .modal-title {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            font-size: 1.45rem;
            color: var(--white);
        }
        .btn-close-custom {
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 8px;
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            color: rgba(255,255,255,.6);
            font-size: .9rem;
            cursor: pointer;
            transition: background .2s, color .2s;
            flex-shrink: 0;
        }
        .btn-close-custom:hover { background: rgba(255,255,255,.15); color: var(--white); }
        .modal-body { padding: 32px; }
        .modal-field-label {
            font-size: .67rem;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
            display: block;
        }
        .modal-body .form-control,
        .modal-body .form-select {
            border: 1.5px solid rgba(201,169,110,.25);
            border-radius: var(--radius-md);
            background: var(--cream);
            color: var(--dark);
            font-size: .88rem;
            font-family: 'DM Sans', sans-serif;
            padding: 11px 14px;
            transition: border-color .2s, box-shadow .2s;
        }
        .modal-body .form-control:focus,
        .modal-body .form-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(201,169,110,.12);
            outline: none;
            background: var(--white);
        }
        .field-hint { font-size: .73rem; color: var(--muted); margin-top: 6px; }
        .modal-footer {
            padding: 20px 32px 28px;
            border-top: 1px solid rgba(201,169,110,.12);
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        .btn-modal-cancel {
            padding: 10px 24px;
            border-radius: 50px;
            border: 1.5px solid rgba(201,169,110,.25);
            background: transparent;
            color: var(--muted);
            font-size: .82rem;
            font-weight: 600;
            letter-spacing: .06em;
            cursor: pointer;
            transition: border-color .2s, color .2s;
        }
        .btn-modal-cancel:hover { border-color: var(--gold); color: var(--dark); }
        .btn-modal-save {
            padding: 10px 28px;
            border-radius: 50px;
            border: none;
            background: var(--dark);
            color: var(--gold-light);
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .08em;
            cursor: pointer;
            transition: background .2s, color .2s;
            display: flex; align-items: center; gap: 7px;
        }
        .btn-modal-save:hover { background: var(--dark-soft); color: var(--gold); }

       
        @media (max-width: 991px) {
            
        }
        @media (max-width: 600px) {
            .topbar { flex-direction: column; align-items: flex-start; gap: 12px; }
            .search-input { width: 100%; }
            .search-input:focus { width: 100%; }
            .table-card-header { flex-direction: column; align-items: flex-start; }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>
<?php include '../includes/sidebar.php'; ?>

<!-- ── Main Content ──────────────────────────────────────────────────── -->
<div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-title">
            <span>Room Management</span>
            Spa Rooms
        </div>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#roomModal">
            <i class="bi bi-plus-lg"></i> Add New Room
        </button>
    </div>
    <div class="gold-rule"></div>

    <!-- Filter Card -->
    <p class="section-eyebrow">Refine Results</p>
    <div class="filter-card">
        <form class="row g-3" method="GET">
            <div class="col-md-4">
                <label class="form-label">Room Type</label>
                <select name="room_type" class="form-select">
                    <option value="">All Room Types</option>
                    <?php foreach ($room_types as $t): ?>
                        <option value="<?= htmlspecialchars($t['room_type']) ?>"
                            <?= $room_type_filter == $t['room_type'] ? 'selected' : '' ?>>
                            <?= ucfirst(htmlspecialchars($t['room_type'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= htmlspecialchars($s['status']) ?>"
                            <?= $status_filter == $s['status'] ? 'selected' : '' ?>>
                            <?= ucfirst(htmlspecialchars($s['status'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn-apply">Apply Filter <i class="bi bi-arrow-right ms-1"></i></button>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <p class="section-eyebrow">Directory</p>
    <div class="table-card">
        <div class="table-card-header">
            <div class="table-card-header-title">All Rooms &nbsp;<span style="font-family:'DM Sans',sans-serif;font-size:.8rem;color:var(--muted);font-weight:500;">(<?= count($rooms) ?>)</span></div>
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="roomSearch" class="search-input" placeholder="Search room name…">
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table class="room-table" id="roomTable">
                <thead>
                    <tr>
                        <th>Room Name</th>
                        <th>Type</th>
                        <th>Additional Fee</th>
                        <th>Status</th>
                        <th style="text-align:right; padding-right:28px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rooms)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-state-icon"><i class="bi bi-door-open"></i></div>
                                    <div class="empty-state-text">No rooms found for this filter</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rooms as $r): ?>
                        <tr>
                            <td class="room-name"><?= htmlspecialchars($r['room_name']) ?></td>
                            <td><span class="badge-type"><?= htmlspecialchars($r['room_type']) ?></span></td>
                            <td>
                                <div class="fee-cell">
                                    <span>₱</span><?= number_format($r['additional_fee'], 2) ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                    $s = strtolower($r['status']);
                                    $cls = ($s === 'active' || $s === 'available') ? 'badge-available'
                                    : ($s === 'archived' ? 'badge-archived' : 'badge-other');
                                ?>
                                <span class="badge-status <?= $cls ?>"><?= ucfirst(htmlspecialchars($r['status'])) ?></span>
                            </td>
                            <td style="text-align:right; padding-right:28px;">
                                <div style="display:inline-flex; gap:8px;">
                                    <button class="btn-icon" title="View" onclick='viewRoom(<?= json_encode($r) ?>)'>
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn-icon" title="Edit" onclick='editRoom(<?= json_encode($r) ?>)'>
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn-icon danger" title="Archive" onclick="archiveRoom(<?= (int)$r['room_id'] ?>)">
                                        <i class="bi bi-archive"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /main-content -->


<!-- ── View Room Modal ────────────────────────────────────────────── -->
<div class="modal fade" id="roomViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:900px;">
        <div class="modal-content" style="
            border-radius:var(--radius-lg);
            overflow:hidden;
            border:none;
            box-shadow:var(--shadow-deep);
            display:flex;
            flex-direction:row;
            min-height:520px;
        ">

            <!-- ── LEFT PANEL: Image + Name ──────────────────────── -->
            <div style="
                width:38%;
                flex-shrink:0;
                position:relative;
                overflow:hidden;
                background:var(--dark);
            ">
                <!-- Background room image -->
                <img id="view_room_image"
                     src="../assets/img/room/default.jpg"
                     style="
                        position:absolute;
                        inset:0;
                        width:100%;
                        height:100%;
                        object-fit:cover;
                        opacity:.4;
                     ">

                <!-- Dark gradient bottom -->
                <div style="
                    position:absolute;
                    inset:0;
                    background:linear-gradient(to top,rgba(20,20,20,.95) 30%,rgba(20,20,20,.3) 70%,transparent 100%);
                    pointer-events:none;
                "></div>

                <!-- Ambient gold glow top -->
                <div style="
                    position:absolute;
                    inset:0;
                    background:radial-gradient(ellipse at 30% 15%,rgba(201,169,110,.08) 0%,transparent 60%);
                    pointer-events:none;
                "></div>

                <!-- Bottom-left: Type label + Room Name -->
                <div style="
                    position:absolute;
                    bottom:36px;
                    left:32px;
                    right:24px;
                    z-index:2;
                ">
                    <div id="view_left_type" style="
                        font-size:.62rem;
                        font-weight:700;
                        letter-spacing:.22em;
                        text-transform:uppercase;
                        color:var(--gold);
                        margin-bottom:10px;
                    "></div>
                    <div id="view_left_name" style="
                        font-family:'Cormorant Garamond',serif;
                        font-weight:600;
                        font-size:1.75rem;
                        color:var(--white);
                        line-height:1.2;
                    "></div>
                </div>
            </div>

            <!-- ── RIGHT PANEL: Details ───────────────────────────── -->
            <div style="
                flex:1;
                background:var(--cream);
                display:flex;
                flex-direction:column;
                padding:36px 40px 32px;
            ">

                <!-- Eyebrow -->
                <div style="
                    font-size:.65rem;
                    font-weight:700;
                    letter-spacing:.22em;
                    text-transform:uppercase;
                    color:var(--muted);
                    margin-bottom:10px;
                ">Room Profile</div>

                <!-- Room Name (large) -->
                <div id="view_room_name_title" style="
                    font-family:'Cormorant Garamond',serif;
                    font-weight:600;
                    font-size:2.2rem;
                    color:var(--dark);
                    line-height:1.15;
                    margin-bottom:6px;
                "></div>

                <!-- Subtitle (@japan equivalent — room type) -->
                <div id="view_room_type_sub" style="
                    font-size:.82rem;
                    color:var(--muted);
                    margin-bottom:20px;
                    letter-spacing:.02em;
                "></div>

                <!-- Divider -->
                <div style="
                    height:1px;
                    background:rgba(201,169,110,.2);
                    margin-bottom:22px;
                "></div>

                <!-- Badges -->
                <div style="
                    display:flex;
                    gap:10px;
                    flex-wrap:wrap;
                    margin-bottom:28px;
                ">
                    <span id="view_badge_status" class="badge-status"></span>
                    <span id="view_badge_type"   class="badge-type"></span>
                </div>

                <!-- Fields -->
                <div style="display:flex;flex-direction:column;gap:22px;flex:1;">

                    <!-- Row: Room Type + Status -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                        <div>
                            <div style="
                                font-size:.62rem;
                                font-weight:700;
                                letter-spacing:.2em;
                                text-transform:uppercase;
                                color:var(--muted);
                                margin-bottom:7px;
                            ">Room Type</div>
                            <div id="view_field_type" style="
                                font-size:.95rem;
                                color:var(--dark);
                                font-weight:500;
                            "></div>
                        </div>
                        <div>
                            <div style="
                                font-size:.62rem;
                                font-weight:700;
                                letter-spacing:.2em;
                                text-transform:uppercase;
                                color:var(--muted);
                                margin-bottom:7px;
                            ">Status</div>
                            <div id="view_field_status" style="
                                font-size:.95rem;
                                color:var(--dark);
                                font-weight:500;
                            "></div>
                        </div>
                    </div>

                    <!-- Additional Fee -->
                    <div>
                        <div style="
                            font-size:.62rem;
                            font-weight:700;
                            letter-spacing:.2em;
                            text-transform:uppercase;
                            color:var(--muted);
                            margin-bottom:7px;
                        ">Additional Fee</div>
                        <div id="view_field_fee" style="
                            font-family:'Cormorant Garamond',serif;
                            font-weight:600;
                            font-size:1.55rem;
                            color:var(--dark);
                        "></div>
                    </div>

                </div>

                <!-- Footer -->
                <div style="
                    display:flex;
                    align-items:center;
                    justify-content:flex-end;
                    gap:16px;
                    padding-top:28px;
                    border-top:1px solid rgba(201,169,110,.15);
                    margin-top:28px;
                ">
                    <button
                        type="button"
                        data-bs-dismiss="modal"
                        style="
                            background:none;
                            border:none;
                            font-size:.88rem;
                            font-weight:500;
                            color:var(--muted);
                            cursor:pointer;
                            padding:4px 8px;
                            transition:color .2s;
                        "
                        onmouseover="this.style.color='var(--dark)'"
                        onmouseout="this.style.color='var(--muted)'">
                        Close
                    </button>

                    <button
                        type="button"
                        id="view_edit_btn"
                        onclick="openEditFromView()"
                        style="
                            display:inline-flex;
                            align-items:center;
                            gap:8px;
                            padding:12px 28px;
                            background:var(--dark);
                            color:var(--gold-light);
                            border:none;
                            border-radius:50px;
                            font-size:.82rem;
                            font-weight:700;
                            letter-spacing:.06em;
                            cursor:pointer;
                            transition:background .22s, color .22s;
                        "
                        onmouseover="this.style.background='var(--dark-soft)';this.style.color='var(--gold)'"
                        onmouseout="this.style.background='var(--dark)';this.style.color='var(--gold-light)'">
                        <i class="bi bi-pencil"></i> Edit Room
                    </button>
                </div>

            </div><!-- /right panel -->
        </div>
    </div>
</div>
        
<!-- ── Add/Edit Room Modal ─────────────────────────────────────────── -->
<div class="modal fade" id="roomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:900px;">
        <div class="modal-content" style="
            border-radius:var(--radius-lg);
            overflow:hidden;
            border:none;
            box-shadow:var(--shadow-deep);
            display:flex;
            flex-direction:row;
            min-height:520px;
        ">
            <form id="roomForm" enctype="multipart/form-data" style="display:contents;">

                <!-- ── LEFT PANEL: Image Preview ─────────────────── -->
                <div style="
                    width:38%;
                    flex-shrink:0;
                    position:relative;
                    overflow:hidden;
                    background:var(--dark);
                ">
                    <!-- Background room image -->
                    <img id="room_preview"
                         src="../assets/img/room/default.jpg"
                         style="
                            position:absolute;
                            inset:0;
                            width:100%;
                            height:100%;
                            object-fit:cover;
                            opacity:.4;
                         ">

                    <!-- Dark gradient -->
                    <div style="
                        position:absolute;
                        inset:0;
                        background:linear-gradient(to top,rgba(20,20,20,.95) 30%,rgba(20,20,20,.3) 70%,transparent 100%);
                        pointer-events:none;
                    "></div>

                    <!-- Gold glow -->
                    <div style="
                        position:absolute;
                        inset:0;
                        background:radial-gradient(ellipse at 30% 15%,rgba(201,169,110,.08) 0%,transparent 60%);
                        pointer-events:none;
                    "></div>

                    <!-- Upload trigger overlay -->
                    <label for="room_image" style="
                        position:absolute;
                        inset:0;
                        z-index:3;
                        cursor:pointer;
                        display:flex;
                        flex-direction:column;
                        align-items:center;
                        justify-content:center;
                        gap:8px;
                        opacity:0;
                        transition:opacity .2s;
                        background:rgba(0,0,0,.35);
                    " id="imageUploadOverlay"
                       onmouseover="this.style.opacity='1'"
                       onmouseout="this.style.opacity='0'">
                        <i class="bi bi-cloud-arrow-up" style="font-size:2rem;color:var(--white);"></i>
                        <span style="font-size:.78rem;font-weight:700;color:var(--white);letter-spacing:.08em;text-transform:uppercase;">Change Photo</span>
                    </label>

                    <input type="file"
                           name="room_image"
                           id="room_image"
                           accept="image/*"
                           style="display:none;"
                           onchange="previewRoomImage(event)">

                    <!-- Bottom-left: Type label + Room Name live preview -->
                    <div style="
                        position:absolute;
                        bottom:36px;
                        left:32px;
                        right:24px;
                        z-index:2;
                    ">
                        <div id="edit_left_type" style="
                            font-size:.62rem;
                            font-weight:700;
                            letter-spacing:.22em;
                            text-transform:uppercase;
                            color:var(--gold);
                            margin-bottom:10px;
                        ">Room Type</div>
                        <div id="edit_left_name" style="
                            font-family:'Cormorant Garamond',serif;
                            font-weight:600;
                            font-size:1.75rem;
                            color:var(--white);
                            line-height:1.2;
                        ">Room Name</div>
                        <div style="margin-top:12px;font-size:.72rem;color:rgba(255,255,255,.4);letter-spacing:.06em;">
                            <i class="bi bi-camera" style="margin-right:4px;"></i> Hover to change photo
                        </div>
                    </div>
                </div>

                <!-- ── RIGHT PANEL: Form Fields ───────────────────── -->
                <div style="
                    flex:1;
                    background:var(--cream);
                    display:flex;
                    flex-direction:column;
                ">
                    <!-- Header -->
                    <div style="
                        background:var(--dark);
                        padding:24px 32px 20px;
                        border-bottom:2px solid var(--border);
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                        position:relative;
                    ">
                        <div style="position:absolute;inset:0;background:radial-gradient(ellipse at 80% 50%,rgba(201,169,110,.08) 0%,transparent 60%);pointer-events:none;"></div>
                        <div style="position:relative;">
                            <div class="modal-eyebrow" id="modalEyebrow">New Room</div>
                            <div class="modal-title" id="roomModalTitle">Add a Spa Room</div>
                        </div>
                        <button type="button" class="btn-close-custom" data-bs-dismiss="modal" style="position:relative;">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div style="padding:28px 32px;flex:1;overflow-y:auto;">
                        <input type="hidden" name="room_id" id="room_id">

                        <!-- Room Name -->
                        <div style="margin-bottom:20px;">
                            <label class="modal-field-label">Room Name</label>
                            <input type="text"
                                name="room_name"
                                id="room_name"
                                class="form-control"
                                placeholder="e.g. Serenity Suite 1"
                                required
                                oninput="document.getElementById('edit_left_name').textContent = this.value || 'Room Name'">
                        </div>

                        <!-- Room Type -->
                        <div style="margin-bottom:20px;">
                            <label class="modal-field-label">Room Type</label>
                            <select name="room_type"
                                    id="room_type"
                                    class="form-select"
                                    required
                                    onchange="handleRoomTypeChange(this.value); document.getElementById('edit_left_type').textContent = this.value || 'Room Type';">
                                <option value="" disabled selected hidden>Select Room Type</option>
                                <option value="Standard Room">Standard Room</option>
                                <option value="Couple Room">Couple Room</option>
                                <option value="Private Room">Private Room</option>
                                <option value="Premium Suite">Premium Suite</option>
                            </select>
                        </div>

                        <!-- Additional Fee -->
                        <div style="margin-bottom:8px;">
                            <label class="modal-field-label">Additional Fee (₱)</label>
                            <input type="number"
                                step="0.01"
                                name="additional_fee"
                                id="additional_fee"
                                class="form-control"
                                value="0.00"
                                required
                                min="0"
                                disabled>
                            <div class="field-hint">Fee charged to non-members or for premium upgrades.</div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div style="
                        padding:18px 32px 26px;
                        border-top:1px solid rgba(201,169,110,.12);
                        display:flex;
                        gap:12px;
                        justify-content:flex-end;
                    ">
                        <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn-modal-save" onclick="saveRoom()">
                            <i class="bi bi-check-lg"></i> Save Room
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    

    // ── View Room ──────────────────────────────────────────────────
    let _currentRoomData = null;

    function viewRoom(data) {
        _currentRoomData = data;

        // Left panel
        document.getElementById('view_left_name').textContent      = data.room_name;
        document.getElementById('view_left_type').textContent      = data.room_type;
        document.getElementById('view_room_type_sub').textContent  = '@' + data.room_type.toLowerCase().replace(/\s+/g, '');

        // Right panel – header
        document.getElementById('view_room_name_title').textContent = data.room_name;
        document.getElementById('view_room_type_sub').textContent   = data.room_type;

        // Badges
        const s   = (data.status || '').toLowerCase();
        const cls = (s === 'active' || s === 'available') ? 'badge-available'
                : (s === 'archived' ? 'badge-archived' : 'badge-other');
        const statusBadge = document.getElementById('view_badge_status');
        statusBadge.className = 'badge-status ' + cls;
        statusBadge.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);

        document.getElementById('view_badge_type').textContent = data.room_type;

        // Fields
        document.getElementById('view_field_type').textContent   = data.room_type;
        document.getElementById('view_field_status').textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
        document.getElementById('view_field_fee').innerHTML      = '<span style="font-size:.85rem;color:var(--gold);margin-right:2px;">₱</span>'
                                                                + parseFloat(data.additional_fee).toFixed(2);

        // Image
        const img = data.room_image && data.room_image !== ''
                ? '../assets/img/room/' + data.room_image
                : '../assets/img/room/default.jpg';
        document.getElementById('view_room_image').src = img;

        new bootstrap.Modal(document.getElementById('roomViewModal')).show();
    }

    function openEditFromView() {
        // Close view modal, then open edit modal with stored data
        const viewModalEl = document.getElementById('roomViewModal');
        const viewModal   = bootstrap.Modal.getInstance(viewModalEl);
        if (viewModal) viewModal.hide();

        viewModalEl.addEventListener('hidden.bs.modal', function onHidden() {
            viewModalEl.removeEventListener('hidden.bs.modal', onHidden);
            editRoom(_currentRoomData);
        });
    }
    
    
    // ── Edit Room (unchanged logic) ────────────────────────────────
    function editRoom(data) {

    // Console log whole object
    console.log("ROOM DATA:", data);

    document.getElementById('modalEyebrow').innerText  = 'Edit Room';
    document.getElementById('roomModalTitle').innerText = 'Edit Room Details';

    document.getElementById('room_id').value        = data.room_id;
    document.getElementById('room_name').value      = data.room_name;
    document.getElementById('room_type').value      = data.room_type;
    document.getElementById('additional_fee').value = data.additional_fee;
    handleRoomTypeChange(data.room_type);

    // Default image
    let imagePath = '../assets/img/room/default.jpg';

    // Debug image field

    if (data.room_image && data.room_image !== '') {

        imagePath = '../assets/img/room/' + data.room_image;
    }

    // Final image path
    console.log("FINAL IMAGE PATH:", imagePath);

    document.getElementById('room_preview').src = imagePath;

    // Sync left panel live preview
    document.getElementById('edit_left_name').textContent = data.room_name;
    document.getElementById('edit_left_type').textContent = data.room_type;

    new bootstrap.Modal(
        document.getElementById('roomModal')
    ).show();
}

    // document.getElementById('roomModal')
    // .addEventListener('show.bs.modal', function () {
    //     // Disable fee field every time modal opens fresh
    //     const feeInput = document.getElementById('additional_fee');
    //     feeInput.value    = '0.00';
    //     feeInput.disabled = true;
    //     feeInput.readOnly = false;
    //     feeInput.style.background  = '';
    //     feeInput.style.color       = '';
    //     feeInput.style.cursor      = '';
    //     feeInput.style.borderColor = '';

    //     // Reset room type to placeholder
    //     document.getElementById('room_type').value = '';
    // });

        document.getElementById('roomModal')
        .addEventListener('hidden.bs.modal', function () {

            document.getElementById('roomForm').reset();

            document.getElementById('room_id').value = '';

            document.getElementById('modalEyebrow').innerText = 'New Room';

            document.getElementById('roomModalTitle').innerText = 'Add a Spa Room';

            // Reset image preview
            document.getElementById('room_preview').src =
                '../assets/img/room/default.jpg';

            // Reset left panel live preview
            document.getElementById('edit_left_name').textContent = 'Room Name';
            document.getElementById('edit_left_type').textContent = 'Room Type';

            handleRoomTypeChange('');   // unlock the fee field on close

        });

        function saveRoom() {
            // ── Validation ──────────────────────────────────────────
            const roomName = document.getElementById('room_name').value.trim();
            const roomType = document.getElementById('room_type').value.trim();
            const fee      = document.getElementById('additional_fee').value.trim();

            const roomId    = document.getElementById('room_id').value;
            const imageFile = document.getElementById('room_image').files[0];

    // Image is required only when adding a new room
    if (!roomId && !imageFile) {
        Swal.fire({ icon: 'warning', title: 'Missing Field', text: 'Please upload a Room Image.' });
        document.getElementById('room_image').focus();
        return;
    }

    if (!roomName) {
        Swal.fire({ icon: 'warning', title: 'Missing Field', text: 'Please enter a Room Name.' });
        document.getElementById('room_name').focus();
        return;
    }

        if (!roomName) {
            Swal.fire({ icon: 'warning', title: 'Missing Field', text: 'Please enter a Room Name.' });
            document.getElementById('room_name').focus();
            return;
        }
        if (!roomType) {
            Swal.fire({ icon: 'warning', title: 'Missing Field', text: 'Please select a Room Type.' });
            document.getElementById('room_type').focus();
            return;
        }
        if (fee === '' || isNaN(fee) || Number(fee) < 0) {
            Swal.fire({ icon: 'warning', title: 'Invalid Fee', text: 'Please enter a valid Additional Fee (0 or more).' });
            document.getElementById('additional_fee').focus();
            return;
        }

        // ── Submit ───────────────────────────────────────────────
        const form     = document.getElementById('roomForm');
        const formData = new FormData(form);
        formData.append('save_room', '1');

        fetch(window.location.pathname, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Saved!', text: data.message, timer: 1500, showConfirmButton: false });
                    bootstrap.Modal.getInstance(document.getElementById('roomModal')).hide();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Swal.fire({ icon: 'error', title: 'Oops…', text: data.message });
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Server Error', text: 'Something went wrong!' }));
    }

    // ── Archive Room (unchanged logic) ─────────────────────────────
    function archiveRoom(roomId) {
        Swal.fire({
            title: 'Archive this room?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, archive it!',
            cancelButtonText: 'Cancel'
        }).then(result => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('archive_room_id', roomId);
                fetch('manage_room.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Archived!', text: data.message, timer: 1500, showConfirmButton: false });
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Oops…', text: data.message });
                        }
                    })
                    .catch(() => Swal.fire({ icon: 'error', title: 'Server Error', text: 'Something went wrong!' }));
            }
        });
    }

    // ── Live search (unchanged logic) ─────────────────────────────
    document.getElementById('roomSearch').addEventListener('keyup', function () {
    const search = this.value.toLowerCase();
    const rows   = document.querySelectorAll('#roomTable tbody tr:not(#noSearchResult)');
    let visible  = 0;

    rows.forEach(row => {
        const name = row.querySelector('.room-name');
        const show = name && name.textContent.toLowerCase().includes(search);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    let noResult = document.getElementById('noSearchResult');
    if (visible === 0) {
        if (!noResult) {
            noResult = document.createElement('tr');
            noResult.id = 'noSearchResult';
            noResult.innerHTML = `<td colspan="5">
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="bi bi-search"></i></div>
                    <div class="empty-state-text">No rooms match "<strong>${this.value}</strong>"</div>
                </div></td>`;
            document.querySelector('#roomTable tbody').appendChild(noResult);
        } else {
            noResult.querySelector('.empty-state-text').innerHTML =
                `No rooms match "<strong>${this.value}</strong>"`;
            noResult.style.display = '';
        }
    } else if (noResult) {
        noResult.style.display = 'none';
    }
});

    // ── Room Type → Fee Lock ───────────────────────────────────────────
    function handleRoomTypeChange(type) {
    const feeInput = document.getElementById('additional_fee');

    if (!type) {
        // No room type selected yet — keep fee disabled
        feeInput.value    = '0.00';
        feeInput.disabled = true;
        feeInput.readOnly = false;
        feeInput.style.background  = '';
        feeInput.style.color       = '';
        feeInput.style.cursor      = '';
        feeInput.style.borderColor = '';
    } else if (type === 'Standard Room') {
        // Standard Room — enable but lock to 0
        feeInput.value    = '0.00';
        feeInput.disabled = false;
        feeInput.readOnly = true;
        feeInput.style.background  = 'var(--gold-dim)';
        feeInput.style.color       = 'var(--muted)';
        feeInput.style.cursor      = 'not-allowed';
        feeInput.style.borderColor = 'rgba(201,169,110,.15)';
    } else {
        // Other room types — fully editable
        feeInput.disabled = false;
        feeInput.readOnly = false;
        feeInput.style.background  = '';
        feeInput.style.color       = '';
        feeInput.style.cursor      = '';
        feeInput.style.borderColor = '';
    }
}

    function previewRoomImage(event) {

        const file = event.target.files[0];

        if (!file) return;

        const reader = new FileReader();

        reader.onload = function(e) {

            document.getElementById('room_preview').src = e.target.result;
        };

        reader.readAsDataURL(file);
    }
</script>
</body>

</html>