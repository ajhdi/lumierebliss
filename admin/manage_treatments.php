<?php
session_start();
require_once '../config/db.php';
require_once '../includes/log_action.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}


if (isset($_POST['delete_treatment'])) {
    $delete_id = $_POST['delete_id'];
    try {
        $pdo->beginTransaction();

        $pdo->prepare("DELETE FROM package_items WHERE package_id = ? OR treatment_id = ?")->execute([$delete_id, $delete_id]);
        $pdo->prepare("DELETE FROM packages WHERE package_id = ?")->execute([$delete_id]);
        $pdo->prepare("DELETE FROM treatments WHERE treatment_id = ?")->execute([$delete_id]);
        $pdo->commit();
        logAction($pdo, 'Delete Treatment', "Deleted treatment ID $delete_id.");
        header("Location: manage_treatments.php?msg=deleted");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Delete Error: " . $e->getMessage());
    }
}


if (isset($_POST['save_treatment'])) {
    $id = $_POST['treatment_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $duration = $_POST['duration'] ?? 0;
    $price = $_POST['price'] ?? 0;
    $type = $_POST['type'] ?? 'individual';
    $selected_treatments = $_POST['package_contents'] ?? [];

    $image_name = 'default.jpg';
    if (!empty($_FILES['service_image']['name'])) {
        $image_name = time() . '_' . $_FILES['service_image']['name'];
        $upload_path = "../assets/img/treatments/" . $image_name;

        if (!is_dir("../assets/img/treatments/")) {
            mkdir("../assets/img/treatments/", 0777, true);
        }
        move_uploaded_file($_FILES['service_image']['tmp_name'], $upload_path);
    } elseif (empty($id) && $type === 'individual') {
        // Adding a new individual treatment with no image — block it
        die(json_encode(['error' => 'Service image is required.']));
    }

    if (!empty($name)) {
        try {
            if (empty($id)) {
                $stmt = $pdo->prepare("INSERT INTO treatments (name, description, duration_minutes, price, type, image, status) VALUES (?, ?, ?, ?, ?, ?, 'available')");
                $stmt->execute([$name, $description, $duration, $price, $type, $image_name]);
                $id = $pdo->lastInsertId();

                if ($type === 'package') {
                    $pdo->prepare("INSERT INTO packages (package_id, name, price) VALUES (?, ?, ?)")->execute([$id, $name, $price]);
                    logAction($pdo, 'Add Package', "Added new package '$name' (ID $id).");
                } else {
                    logAction($pdo, 'Add Treatment', "Added new treatment '$name' (ID $id).");
                }
            } else {
               
                if (!empty($_FILES['service_image']['name'])) {
                    $stmt = $pdo->prepare("UPDATE treatments SET name=?, description=?, duration_minutes=?, price=?, type=?, image=? WHERE treatment_id=?");
                    $stmt->execute([$name, $description, $duration, $price, $type, $image_name, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE treatments SET name=?, description=?, duration_minutes=?, price=?, type=? WHERE treatment_id=?");
                    $stmt->execute([$name, $description, $duration, $price, $type, $id]);
                }
            }

            
           if ($type === 'package') {
                $pdo->prepare("DELETE FROM package_items WHERE package_id = ?")->execute([$id]);
                if (!empty($selected_treatments)) {
                    $stmt_item = $pdo->prepare("INSERT INTO package_items (package_id, treatment_id) VALUES (?, ?)");
                    foreach ($selected_treatments as $t_id) {
                        $stmt_item->execute([$id, $t_id]);
                    }
                }
                if (!empty($_POST['treatment_id'])) {
                    logAction($pdo, 'Edit Package', "Updated package '$name' (ID $id).");
                }
            } else {
                if (!empty($_POST['treatment_id'])) {
                    logAction($pdo, 'Edit Treatment', "Updated treatment '$name' (ID $id).");
                }
            }

            header("Location: manage_treatments.php?msg=success");
            exit();
        } catch (PDOException $e) {
            die("Save Error: " . $e->getMessage());
        }
    }
}
$sort = $_GET['sort'] ?? 'alphabetical';
$order_query = "name ASC";

if ($sort === 'recent') {
    $order_query = "treatment_id DESC";
}

$sort_indiv = $_GET['sort_indiv'] ?? 'recent'; 
$order_indiv = ($sort_indiv === 'alphabetical') ? "name ASC" : "treatment_id DESC";

$sort_pkg = $_GET['sort_pkg'] ?? 'recent';
$order_pkg = ($sort_pkg === 'alphabetical') ? "name ASC" : "treatment_id DESC";

$individual = $pdo->query("SELECT * FROM treatments WHERE status = 'available' AND type = 'individual' ORDER BY $order_indiv")->fetchAll();
$packages = $pdo->query("SELECT * FROM treatments WHERE status = 'available' AND type = 'package' ORDER BY $order_pkg")->fetchAll();

$package_map = [];
$content_query = $pdo->query("SELECT package_id, treatment_id FROM package_items");
while ($row = $content_query->fetch(PDO::FETCH_ASSOC)) {
    $package_map[$row['package_id']][] = $row['treatment_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services | L&B Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        :root {
           --sidebar-w: 270px;
            --gold: #c9a96e;
            --gold-light: #e8d5b0;
            --dark:#0d0d0d;
            --dark-soft: #2e2e2e;
            --cream: #fdfbf7;
            --white: #ffffff;
            --muted: #8a8070;
            --border: rgba(201,169,110,0.18);
            --shadow: 0 8px 40px rgba(26,26,26,0.10);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--cream);
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            color: var(--dark);
            min-height: 100vh;
        }


        /* ─── MAIN CONTENT ────────────────────────── */
        .main-content {
           margin-left: var(--sidebar-w);
            min-height: 100vh;
            padding: 48px 48px 64px;
            transition: margin 0.3s ease;
        }

        /* ─── PAGE HEADER ─────────────────────────── */
        .page-header {
            margin-bottom: 40px;
            padding-bottom: 28px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
        }

        .page-header-left .eyebrow {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 6px;
        }

        .page-header-left h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.6rem;
            font-weight: 600;
            color: var(--dark);
            line-height: 1.1;
        }

        .page-header-left p {
            font-size: 0.9rem;
            color: var(--muted);
            margin-top: 6px;
        }

        /* ─── TOAST NOTIFICATION ──────────────────── */
        .lb-toast {
            position: fixed;
            top: 28px; right: 28px;
            z-index: 9999;
            background: var(--dark);
            color: var(--white);
            padding: 16px 24px 16px 20px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.875rem;
            box-shadow: 0 12px 48px rgba(0,0,0,0.25);
            border-left: 3px solid var(--gold);
            transform: translateX(120%);
            opacity: 0;
            transition: all 0.4s cubic-bezier(.4,0,.2,1);
        }
        .lb-toast.show { transform: translateX(0); opacity: 1; }
        .lb-toast .toast-icon { color: var(--gold); font-size: 1rem; }

        /* ─── TABS ────────────────────────────────── */
        .lb-tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 32px;
            background: rgba(26,26,26,0.04);
            border-radius: 8px;
            padding: 5px;
            width: fit-content;
        }

        .lb-tab-btn {
            padding: 10px 26px;
            border: none;
            border-radius: 6px;
            background: transparent;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--muted);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .lb-tab-btn.active {
            background: var(--dark);
            color: var(--white);
            box-shadow: 0 4px 16px rgba(26,26,26,0.18);
        }

        .lb-tab-btn.active i { color: var(--gold); }
        .lb-tab-btn:hover:not(.active) { color: var(--dark); background: rgba(26,26,26,0.06); }

        /* ─── TOOLBAR ─────────────────────────────── */
        .section-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            gap: 16px;
        }

        .toolbar-left { display: flex; align-items: center; gap: 12px; }

        .search-wrap {
            position: relative;
        }

        .search-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 0.85rem;
            pointer-events: none;
        }

        .search-input {
            padding: 10px 16px 10px 38px;
            border: 1.5px solid rgba(26,26,26,0.1);
            border-radius: 8px;
            background: var(--white);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            color: var(--dark);
            width: 230px;
            transition: all 0.25s ease;
            outline: none;
        }

        .search-input::placeholder { color: #bbb; }

        .search-input:focus {
            border-color: var(--gold);
            width: 270px;
            box-shadow: 0 0 0 3px rgba(201,169,110,0.12);
        }

        .filter-select {
            padding: 10px 16px;
            border: 1.5px solid rgba(26,26,26,0.1);
            border-radius: 8px;
            background: var(--white);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem;
            color: var(--dark);
            cursor: pointer;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            padding-right: 32px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%238a8070' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            transition: border-color 0.2s;
        }

        .filter-select:focus { border-color: var(--gold); }

        /* ─── ADD BUTTON ──────────────────────────── */
        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            background: var(--dark);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.22s ease;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .btn-add:hover {
            background: var(--dark-soft);
            box-shadow: 0 6px 20px rgba(26,26,26,0.22);
            transform: translateY(-1px);
        }

        .btn-add i { color: var(--gold); }

        /* ─── DATA TABLE ──────────────────────────── */
        .data-card {
            background: var(--white);
            border-radius: 12px;
            border: 1px solid rgba(26,26,26,0.07);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .lb-table {
            width: 100%;
            border-collapse: collapse;
        }

        .lb-table thead tr {
            background: #f9f7f4;
            border-bottom: 1.5px solid rgba(26,26,26,0.07);
        }

        .lb-table thead th {
            padding: 14px 20px;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .lb-table tbody tr {
            border-bottom: 1px solid rgba(26,26,26,0.05);
            transition: background 0.15s ease;
        }

        .lb-table tbody tr:last-child { border-bottom: none; }
        .lb-table tbody tr:hover { background: #fdfaf5; }

        .lb-table td {
            padding: 16px 20px;
            vertical-align: middle;
        }

        .treatment-name {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.925rem;
        }

        .treatment-desc {
            font-size: 0.8rem;
            color: var(--muted);
            margin-top: 2px;
            max-width: 320px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .duration-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            background: rgba(26,26,26,0.05);
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--dark);
        }

        .duration-badge i { color: var(--muted); font-size: 0.75rem; }

        .price-text {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--dark);
            font-variant-numeric: tabular-nums;
        }

        .price-currency {
            font-size: 0.75rem;
            color: var(--gold);
            font-weight: 600;
            margin-right: 1px;
        }

        .action-cell { text-align: right; }

        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: 1.5px solid;
            background: transparent;
            cursor: pointer;
            transition: all 0.18s ease;
            font-size: 0.85rem;
        }

        .btn-icon-edit {
            border-color: rgba(201,169,110,0.35);
            color: var(--gold);
        }
        .btn-icon-edit:hover {
            background: var(--gold);
            color: var(--white);
            border-color: var(--gold);
            box-shadow: 0 4px 12px rgba(201,169,110,0.3);
        }

        .btn-icon-view {
    border-color: rgba(26,26,26,0.18);
    color: var(--muted);
}
.btn-icon-view:hover {
    background: var(--dark);
    color: var(--white);
    border-color: var(--dark);
    box-shadow: 0 4px 12px rgba(26,26,26,0.18);
}

.btn-icon-delete {
    border-color: rgba(220,53,69,0.25);
    color: #dc3545;
}
.btn-icon-delete:hover {
    background: #dc3545;
    color: var(--white);
    border-color: #dc3545;
    box-shadow: 0 4px 12px rgba(220,53,69,0.25);
}

        .action-btns { display: flex; gap: 8px; justify-content: flex-end; }

        /* ─── NO RESULTS ──────────────────────────── */
        .no-results-row td {
            text-align: center;
            padding: 52px 20px;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .no-results-row .no-results-icon {
            font-size: 2rem;
            color: var(--gold-light);
            display: block;
            margin-bottom: 10px;
        }

        /* ─── MODAL ───────────────────────────────── */
        .modal-content {
            border: none;
            border-radius: 14px;
            box-shadow: 0 32px 80px rgba(0,0,0,0.18);
        }

        .modal-header {
            padding: 28px 32px 22px;
            border-bottom: 1px solid var(--border);
            background: var(--dark);
            border-radius: 14px 14px 0 0;
        }

        .modal-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--white);
            letter-spacing: 0.02em;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.5;
        }
        .modal-header .btn-close:hover { opacity: 1; }

        .modal-body { padding: 28px 32px; background: var(--cream); }

        .lb-label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .lb-input, .lb-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid rgba(26,26,26,0.12);
            border-radius: 8px;
            background: var(--white);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
            color: var(--dark);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .lb-input:focus, .lb-textarea:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(201,169,110,0.12);
        }

        .lb-textarea { resize: vertical; min-height: 90px; }

        .lb-input.bg-muted { background: #f5f3ef; color: var(--muted); }

        /* ─── PACKAGE SELECTION GRID ──────────────── */
        .package-selection-wrap {
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: 10px;
            padding: 20px;
        }

        .package-selection-label {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 14px;
        }

        .treatment-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            max-height: 260px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .treatment-grid::-webkit-scrollbar { width: 4px; }
        .treatment-grid::-webkit-scrollbar-track { background: transparent; }
        .treatment-grid::-webkit-scrollbar-thumb { background: var(--gold-light); border-radius: 4px; }

        .treatment-selectable-card {
            border: 1.5px solid rgba(26,26,26,0.1) !important;
            border-radius: 9px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #fdfbf7;
            position: relative;
        }

        .treatment-selectable-card:hover {
            border-color: var(--gold-light) !important;
            background: #fffdf9;
        }

        .treatment-selectable-card.selected {
            border-color: var(--gold) !important;
            background: linear-gradient(135deg, #fffdf9 0%, #fdf6e8 100%);
        }

        .treatment-selectable-card .selected-icon {
            display: none;
            position: absolute;
            top: 8px; right: 8px;
            color: var(--gold);
            font-size: 0.9rem;
        }

        .treatment-selectable-card.selected .selected-icon { display: block; }

        .card-thumb {
            width: 42px;
            height: 42px;
            border-radius: 7px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .card-info .card-title {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--dark);
            line-height: 1.3;
        }

        .card-info .card-meta {
            font-size: 0.75rem;
            color: var(--muted);
            margin-top: 2px;
        }

        .selection-counter {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 14px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
        }

        .selection-hint {
            font-size: 0.78rem;
            color: var(--muted);
        }

        .selection-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            background: var(--dark);
            color: var(--gold-light);
        }

        .selection-badge.complete {
            background: linear-gradient(135deg, #1a1a1a, #2e2e2e);
            color: var(--gold);
        }

        /* ─── CURRENT IMAGE PREVIEW ───────────────── */
        .current-img-preview {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px;
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .current-img-preview img {
            width: 56px;
            height: 56px;
            border-radius: 7px;
            object-fit: cover;
        }

        .current-img-preview .img-label {
            font-size: 0.78rem;
            color: var(--muted);
        }

        /* ─── MODAL FOOTER ────────────────────────── */
        .modal-footer {
            padding: 18px 32px;
            border-top: 1px solid var(--border);
            background: var(--cream);
            border-radius: 0 0 14px 14px;
            gap: 10px;
        }

        .btn-cancel {
            padding: 10px 24px;
            border: 1.5px solid rgba(26,26,26,0.15);
            border-radius: 8px;
            background: transparent;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--muted);
            cursor: pointer;
            transition: all 0.18s ease;
        }
        .btn-cancel:hover { border-color: var(--dark); color: var(--dark); }

        .btn-save {
            padding: 10px 28px;
            border: none;
            border-radius: 8px;
            background: var(--dark);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--white);
            cursor: pointer;
            transition: all 0.22s ease;
            letter-spacing: 0.03em;
        }

        .btn-save:hover {
            background: var(--dark-soft);
            box-shadow: 0 6px 20px rgba(26,26,26,0.22);
        }

        /* ─── DELETE MODAL ────────────────────────── */
        .delete-modal .modal-content {
            border-radius: 14px;
        }

        .delete-modal .modal-body {
            text-align: center;
            padding: 40px 32px;
        }

        .delete-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fff0f0, #ffe5e5);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.6rem;
            color: #dc3545;
        }

        .delete-modal h5 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.35rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 6px;
        }

        .delete-modal p {
            font-size: 0.875rem;
            color: var(--muted);
            margin-bottom: 24px;
        }

        .btn-danger-confirm {
            padding: 10px 22px;
            border: none;
            border-radius: 8px;
            background: #dc3545;
            color: var(--white);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.18s ease;
            width: 100%;
        }
        .btn-danger-confirm:hover { background: #bb2d3b; box-shadow: 0 4px 12px rgba(220,53,69,0.28); }

        /* ─── SHAKE ANIMATION ─────────────────────── */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            75% { transform: translateX(6px); }
        }
        .shake { animation: shake 0.2s ease-in-out 0s 2; }

        /* ─── GOLD DIVIDER ────────────────────────── */
        .gold-divider {
            width: 40px;
            height: 2px;
            background: linear-gradient(90deg, var(--gold), transparent);
            margin-bottom: 28px;
        }

        /* ─── VALIDATION TOAST ────────────────────── */
        .validation-toast-wrap {
            position: fixed;
            bottom: 28px; right: 28px;
            z-index: 9999;
        }

        .validation-toast {
            background: var(--dark);
            color: var(--white);
            padding: 14px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.875rem;
            box-shadow: 0 12px 40px rgba(0,0,0,0.2);
            border-left: 3px solid #dc3545;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
        }
        .validation-toast.show { transform: translateY(0); opacity: 1; }
        .validation-toast i { color: #ff6b6b; }

        /* ─── RESPONSIVE ──────────────────────────── */
        @media (max-width: 991px) {
            .treatment-grid { grid-template-columns: 1fr; }
            .page-header { flex-direction: column; align-items: flex-start; }
        }

        /* ─── SUCCESS BANNER ──────────────────────── */
        .success-banner {
            display: flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, var(--dark), var(--dark-soft));
            color: var(--gold-light);
            border-radius: 10px;
            padding: 14px 20px;
            margin-bottom: 28px;
            font-size: 0.875rem;
            animation: slideInTop 0.4s ease forwards;
        }

        @keyframes slideInTop {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .success-banner.fade-out {
            opacity: 0;
            transform: translateY(-8px);
            transition: opacity 0.5s ease, transform 0.5s ease;
            pointer-events: none;
        }

        .success-banner i { color: var(--gold); font-size: 1rem; }

        /* Package name badge in table */
        .pkg-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--gold);
            background: rgba(201,169,110,0.1);
            padding: 2px 9px;
            border-radius: 20px;
            margin-left: 8px;
            vertical-align: middle;
        }

        /* Form row spacing */
        .form-grid {
            display: grid;
            gap: 18px;
        }
        .form-grid-2 {
            grid-template-columns: 1fr 1fr;
        }

        @media (max-width: 576px) {
            .form-grid-2 { grid-template-columns: 1fr; }
        }

        /* ── Therapist-style modal classes ── */
        .modal-section-label {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }
        .modal-section-label-text {
            font-size: .62rem;
            font-weight: 700;
            letter-spacing: .22em;
            text-transform: uppercase;
            color: var(--gold);
            white-space: nowrap;
        }
        .modal-section-label-rule {
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, rgba(201,169,110,.3), transparent);
        }
        .modal-field-label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }
        .inline-error {
            display: none;
            padding: 14px 18px;
            border-radius: 8px;
            background: rgba(220,53,69,0.06);
            border: 1px solid rgba(220,53,69,0.18);
            font-size: .82rem;
            color: #8b2222;
            margin-bottom: 22px;
            align-items: flex-start;
            gap: 10px;
        }
        .inline-error.visible { display: flex; }
        .inline-error i { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }
        .btn-modal-cancel {
            padding: 10px 24px;
            border: 1.5px solid rgba(26,26,26,0.15);
            border-radius: 8px;
            background: transparent;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--muted);
            cursor: pointer;
            transition: all 0.18s ease;
        }
        .btn-modal-cancel:hover { border-color: var(--dark); color: var(--dark); }
        .btn-modal-save {
            padding: 10px 28px;
            border: none;
            border-radius: 8px;
            background: var(--dark);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--white);
            cursor: pointer;
            transition: all 0.22s ease;
            letter-spacing: 0.03em;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-modal-save:hover {
            background: var(--dark-soft);
            box-shadow: 0 6px 20px rgba(26,26,26,0.22);
        }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>


<!-- ─── MAIN CONTENT ─────────────────────────────────── -->
<main class="main-content">

    <?php if (isset($_GET['msg'])): ?>
        <div class="success-banner" id="successBanner">
            <i class="bi bi-check-circle-fill"></i>
            <?= $_GET['msg'] === 'deleted' ? 'Service has been removed from the catalog.' : 'Service saved successfully.' ?>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-left">
            <div class="eyebrow">Lumière &amp; Bliss Studio</div>
            <h1>Treatment Management</h1>
            <p>Curate and manage your clinic's offerings and exclusive bundles.</p>
        </div>
    </div>

    <!-- ─── TABS ─────────────────────────────────────── -->
    <div class="lb-tabs" id="mainTabs">
        <button class="lb-tab-btn active" id="indiv-tab" onclick="switchTab('individual')">
           </i> Individual Treatments
        </button>
        <button class="lb-tab-btn" id="pkg-tab" onclick="switchTab('package')">
            </i> Exclusive Packages
        </button>
    </div>

    <!-- ─── INDIVIDUAL TREATMENTS PANEL ─────────────── -->
    <div id="panel-individual">
        <div class="section-toolbar">
            <div class="toolbar-left">
                <div class="search-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" class="search-input" id="searchIndiv"
                        placeholder="Search treatments…"
                        onkeyup="liveSearch('searchIndiv', 'indivTable')">
                </div>
                <select class="filter-select" onchange="applySort('sort_indiv', this.value)">
                    <option value="recent" <?= ($sort_indiv == 'recent') ? 'selected' : '' ?>>Recently Added</option>
                    <option value="alphabetical" <?= ($sort_indiv == 'alphabetical') ? 'selected' : '' ?>>A – Z</option>
                </select>
            </div>
            <button class="btn-add" onclick="openModal('individual')">
                <i class="bi bi-plus-lg"></i> Add Treatment
            </button>
        </div>

        <div class="data-card">
            <table class="lb-table" id="indivTable">
                <thead>
                    <tr>
                        <th>Treatment</th>
                        <th>Duration</th>
                        <th>Price</th>
                        <th style="text-align:right; padding-right:24px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($individual as $indiv): ?>
                    <tr>
                        <td>
                            <div class="treatment-name"><?= htmlspecialchars($indiv['name']) ?></div>
                            <?php if (!empty($indiv['description'])): ?>
                            <div class="treatment-desc"><?= htmlspecialchars($indiv['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="duration-badge">
                                <i class="bi bi-clock"></i>
                                <?= $indiv['duration_minutes'] ?> mins
                            </span>
                        </td>
                        <td>
                            <span class="price-currency">₱</span><span class="price-text"><?= number_format($indiv['price'], 2) ?></span>
                        </td>
                        <td class="action-cell">
                           <div class="action-btns" style="padding-right:4px;">
                                <button class="btn-icon btn-icon-view" title="View"
                                    onclick='viewService(<?= json_encode($indiv) ?>)'>
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn-icon btn-icon-edit" title="Edit"
                                    onclick='editService(<?= json_encode($indiv) ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn-icon btn-icon-delete" title="Delete"
                                    onclick="confirmDelete(<?= $indiv['treatment_id'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="no-results-row" id="noResultsIndiv" style="display:none;">
                        <td colspan="4">
                            <span class="no-results-icon"><i class="bi bi-search"></i></span>
                            No treatments match your search.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ─── PACKAGES PANEL ───────────────────────────── -->
    <div id="panel-package" style="display:none;">
        <div class="section-toolbar">
            <div class="toolbar-left">
                <div class="search-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" class="search-input" id="searchPkg"
                        placeholder="Search packages…"
                        onkeyup="liveSearch('searchPkg', 'pkgTable')">
                </div>
                <select class="filter-select" onchange="applySort('sort_pkg', this.value)">
                    <option value="recent" <?= ($sort_pkg == 'recent') ? 'selected' : '' ?>>Recently Added</option>
                    <option value="alphabetical" <?= ($sort_pkg == 'alphabetical') ? 'selected' : '' ?>>A – Z</option>
                </select>
            </div>
            <button class="btn-add" onclick="openModal('package')">
                <i class="bi bi-box-seam"></i> Add Package
            </button>
        </div>

        <div class="data-card">
            <table class="lb-table" id="pkgTable">
                <thead>
                    <tr>
                        <th>Package</th>
                        <th>Duration</th>
                        <th>Price</th>
                        <th style="text-align:right; padding-right:24px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($packages as $p): ?>
                    <tr>
                        <td>
                            <div class="treatment-name">
                                <?= htmlspecialchars($p['name']) ?>
                                <span class="pkg-badge">Package</span>
                            </div>
                            <?php if (!empty($p['description'])): ?>
                            <div class="treatment-desc"><?= htmlspecialchars($p['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="duration-badge">
                                <i class="bi bi-clock"></i>
                                <?= $p['duration_minutes'] ?> mins
                            </span>
                        </td>
                        <td>
                            <span class="price-currency">₱</span><span class="price-text"><?= number_format($p['price'], 2) ?></span>
                        </td>
                        <td class="action-cell">
                            <div class="action-btns" style="padding-right:4px;">
                                <button class="btn-icon btn-icon-view" title="View"
                                    onclick='viewService(<?= json_encode($p) ?>, <?= json_encode($package_map[$p['treatment_id']] ?? []) ?>)'>
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn-icon btn-icon-edit" title="Edit"
                                    onclick='editService(<?= json_encode($p) ?>, <?= json_encode($package_map[$p['treatment_id']] ?? []) ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn-icon btn-icon-delete" title="Delete"
                                    onclick="confirmDelete(<?= $p['treatment_id'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="no-results-row" id="noResultsPkg" style="display:none;">
                        <td colspan="4">
                            <span class="no-results-icon"><i class="bi bi-search"></i></span>
                            No packages match your search.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</main>

<!-- ─── SERVICE MODAL ────────────────────────────────── -->
<div class="modal fade" id="serviceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border:none;border-radius:18px;overflow:hidden;box-shadow:0 32px 80px rgba(0,0,0,0.22);">
            <form action="" method="POST" id="serviceForm" enctype="multipart/form-data">

                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    style="position:absolute;top:18px;right:18px;z-index:10;filter:brightness(0) invert(1);opacity:.7;"></button>

                <input type="hidden" name="treatment_id" id="treatment_id">
                <input type="hidden" name="type" id="type">

                <div style="display:flex;min-height:480px;">

                    <!-- ── Left: Photo Panel ── -->
                    <div style="width:42%;flex-shrink:0;position:relative;background:var(--dark);overflow:hidden;" id="editPhotoPanel">
                        <img id="editImagePreview"
                             src="../assets/img/treatments/default.jpg"
                             alt="Service"
                             style="width:100%;height:100%;object-fit:cover;display:block;opacity:.88;">
                        <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(26,26,26,.85) 0%,transparent 55%);pointer-events:none;"></div>
                        <div style="position:absolute;bottom:0;left:0;right:0;padding:28px 28px 80px;">
                            <div style="font-size:.62rem;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:var(--gold);margin-bottom:6px;" id="editTypeLabelOnPhoto">Individual Treatment</div>
                            <div style="font-family:'Cormorant Garamond',serif;font-size:1.8rem;font-weight:600;color:var(--white);line-height:1.1;" id="editNameOnPhoto">New Service</div>
                        </div>
                        <div id="imageUploadArea">
                            <label style="position:absolute;bottom:22px;left:28px;display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:8px;border:1.5px solid rgba(255,255,255,.25);background:rgba(255,255,255,.12);color:var(--white);font-size:.8rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;">
                                <i class="bi bi-camera-fill"></i> Change Photo
                                <input type="file" name="service_image" id="serviceImageInput" accept="image/*" style="display:none;" onchange="previewEditImage(this)">
                            </label>
                        </div>
                        <p id="imageRequiredHint" style="position:absolute;bottom:6px;left:28px;font-size:.72rem;color:#ff6b6b;display:none;">* Image required</p>
                    </div>

                    <!-- ── Right: Form Panel ── -->
                    <div style="flex:1;background:var(--cream);display:flex;flex-direction:column;">

                        <!-- Header band -->
                        <div style="padding:32px 36px 24px;border-bottom:1px solid var(--border);background:var(--white);">
                            <div style="font-size:.62rem;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:var(--gold);margin-bottom:6px;" id="modalEyebrow">Add New Service</div>
                            <div style="font-family:'Cormorant Garamond',serif;font-size:2rem;font-weight:600;color:var(--dark);line-height:1.1;" id="modalTitle">New Service</div>
                        </div>

                        <!-- Body -->
                        <div style="flex:1;padding:28px 36px;overflow-y:auto;max-height:55vh;scrollbar-width:thin;scrollbar-color:rgba(201,169,110,.3) transparent;">

                            <!-- Inline error -->
                            <div class="inline-error" id="serviceValidationError">
                                <i class="bi bi-exclamation-circle-fill"></i>
                                <span id="serviceErrorText"></span>
                            </div>

                            <!-- Section: Service Info -->
                            <div class="modal-section-label">
                                <span class="modal-section-label-text">Service Details</span>
                                <div class="modal-section-label-rule"></div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="modal-field-label">Service Name</label>
                                    <input type="text" name="name" id="name"
                                           style="width:100%;padding:11px 16px;border:1.5px solid rgba(26,26,26,0.12);border-radius:8px;background:var(--white);font-family:'DM Sans',sans-serif;font-size:.9rem;color:var(--dark);outline:none;"
                                           placeholder="e.g. Deep Tissue Massage" required
                                           oninput="document.getElementById('editNameOnPhoto').textContent = this.value || 'New Service'; document.getElementById('modalTitle').textContent = this.value || 'New Service';">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-6">
                                    <label class="modal-field-label">Duration (Minutes)</label>
                                    <input type="number" name="duration" id="duration"
                                           style="width:100%;padding:11px 16px;border:1.5px solid rgba(26,26,26,0.12);border-radius:8px;background:var(--white);font-family:'DM Sans',sans-serif;font-size:.9rem;color:var(--dark);outline:none;"
                                           placeholder="60" required>
                                </div>
                                <div class="col-6">
                                    <label class="modal-field-label">Price (₱)</label>
                                    <input type="number" step="0.01" name="price" id="price"
                                           style="width:100%;padding:11px 16px;border:1.5px solid rgba(26,26,26,0.12);border-radius:8px;background:var(--white);font-family:'DM Sans',sans-serif;font-size:.9rem;color:var(--dark);outline:none;"
                                           placeholder="0.00" required>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="modal-field-label">Description</label>
                                    <textarea name="description" id="description"
                                              style="width:100%;padding:11px 16px;border:1.5px solid rgba(26,26,26,0.12);border-radius:8px;background:var(--white);font-family:'DM Sans',sans-serif;font-size:.9rem;color:var(--dark);outline:none;resize:vertical;min-height:80px;line-height:1.6;"
                                              placeholder="Brief description of the service…" required></textarea>
                                </div>
                            </div>

                            <!-- Section: Package Bundle (shown only for packages) -->
                            <div id="packageSelectionArea" style="display:none;">
                                <div class="modal-section-label">
                                    <span class="modal-section-label-text">Bundle Treatments</span>
                                    <div class="modal-section-label-rule"></div>
                                </div>
                                <div class="package-selection-wrap">
                                    <div class="package-selection-label">Select 2 Treatments to Bundle</div>
                                    <div class="treatment-grid" id="treatmentGrid">
                                        <?php foreach($individual as $indiv): ?>
                                        <div class="treatment-selectable-card"
                                             id="card_<?= $indiv['treatment_id'] ?>"
                                             onclick="toggleTreatmentSelection(this, 'treat_<?= $indiv['treatment_id'] ?>')">
                                            <input class="package-checkbox d-none" type="checkbox"
                                                   name="package_contents[]"
                                                   value="<?= $indiv['treatment_id'] ?>"
                                                   id="treat_<?= $indiv['treatment_id'] ?>"
                                                   onchange="updateSelectionCount()">
                                            <div style="display:flex;align-items:center;gap:10px;">
                                                <img src="../assets/img/treatments/<?= !empty($indiv['image']) ? $indiv['image'] : 'default.jpg' ?>"
                                                     class="card-thumb" alt="">
                                                <div class="card-info">
                                                    <div class="card-title"><?= htmlspecialchars($indiv['name']) ?></div>
                                                    <div class="card-meta"><?= $indiv['duration_minutes'] ?>m &nbsp;·&nbsp; ₱<?= number_format($indiv['price'], 2) ?></div>
                                                </div>
                                            </div>
                                            <i class="bi bi-check-circle-fill selected-icon"></i>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="selection-counter">
                                        <span class="selection-hint">Exactly 2 treatments required</span>
                                        <span class="selection-badge" id="selectionCount">0 selected</span>
                                    </div>
                                </div>
                            </div>

                        </div><!-- /body -->

                        <!-- Footer -->
                        <div style="padding:18px 36px;border-top:1px solid var(--border);background:var(--white);display:flex;justify-content:flex-end;gap:10px;">
                            <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="save_treatment" class="btn-modal-save">
                                <i class="bi bi-check-lg"></i> Save Service
                            </button>
                        </div>

                    </div><!-- /right panel -->
                </div><!-- /flex row -->
            </form>
        </div>
    </div>
</div>

<!-- ─── VIEW SERVICE MODAL ──────────────────────────────── -->
<div class="modal fade" id="viewServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border:none;border-radius:18px;overflow:hidden;box-shadow:0 32px 80px rgba(0,0,0,0.22);">

            <button type="button" class="btn-close" data-bs-dismiss="modal"
                style="position:absolute;top:18px;right:18px;z-index:10;filter:brightness(0) invert(1);opacity:.7;"></button>

            <div style="display:flex;min-height:480px;">

                <!-- ── Left: Photo Panel ── -->
                <div style="width:42%;flex-shrink:0;position:relative;background:var(--dark);overflow:hidden;">
                    <img id="viewImage"
                         src="../assets/img/treatments/default.jpg"
                         alt="Service"
                         style="width:100%;height:100%;object-fit:cover;display:block;opacity:.88;">
                    <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(26,26,26,.85) 0%,transparent 55%);pointer-events:none;"></div>
                    <div style="position:absolute;bottom:0;left:0;right:0;padding:28px 28px 24px;">
                        <div style="font-size:.62rem;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:var(--gold);margin-bottom:6px;" id="viewTypeBadge">—</div>
                        <div style="font-family:'Cormorant Garamond',serif;font-size:1.8rem;font-weight:600;color:var(--white);line-height:1.1;" id="viewNameOnPhoto">—</div>
                    </div>
                </div>

                <!-- ── Right: Details Panel ── -->
                <div style="flex:1;background:var(--cream);display:flex;flex-direction:column;">

                    <!-- Header band -->
                    <div style="padding:32px 36px 24px;border-bottom:1px solid var(--border);background:var(--white);">
                        <div style="font-size:.62rem;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:var(--gold);margin-bottom:6px;">Service Profile</div>
                        <div style="font-family:'Cormorant Garamond',serif;font-size:2rem;font-weight:600;color:var(--dark);line-height:1.1;" id="viewName">—</div>
                    </div>

                    <!-- Info body -->
                    <div style="flex:1;padding:28px 36px;overflow-y:auto;">

                        <!-- Duration + Price row -->
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
                            <div style="background:var(--white);border:1px solid rgba(201,169,110,.15);border-radius:10px;padding:14px 16px;">
                                <div style="font-size:.62rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);margin-bottom:5px;">Duration</div>
                                <div style="font-size:.95rem;color:var(--dark);font-weight:600;" id="viewDuration">—</div>
                            </div>
                            <div style="background:var(--white);border:1px solid rgba(201,169,110,.15);border-radius:10px;padding:14px 16px;">
                                <div style="font-size:.62rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);margin-bottom:5px;">Price</div>
                                <div style="font-size:.95rem;color:var(--dark);font-weight:700;" id="viewPrice">—</div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div style="margin-bottom:24px;">
                            <div style="font-size:.62rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);margin-bottom:8px;">Description</div>
                            <div style="font-size:.88rem;color:var(--dark-soft);line-height:1.7;background:var(--white);border:1px solid rgba(201,169,110,.15);border-radius:10px;padding:14px 16px;" id="viewDescription">—</div>
                        </div>

                        <!-- Included Treatments (packages only) -->
                        <div id="viewPackageContents" style="display:none;">
                            <div style="font-size:.62rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);margin-bottom:10px;">Included Treatments</div>
                            <div id="viewTreatmentList" style="display:flex;flex-direction:column;gap:8px;"></div>
                        </div>

                    </div>

                    <!-- Footer -->
                    <div style="padding:18px 36px;border-top:1px solid var(--border);background:var(--white);display:flex;justify-content:flex-end;gap:10px;">
                        <button type="button" class="btn-modal-cancel" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn-modal-save" id="viewEditBtn">
                            <i class="bi bi-pencil"></i> Edit Service
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- ─── DELETE CONFIRM MODAL ─────────────────────────── -->
<div class="modal fade delete-modal" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <div class="delete-icon">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <h5>Remove Service?</h5>
                <p>This action is permanent and cannot be undone.</p>

                <form action="manage_treatments.php" method="POST">
                    <input type="hidden" name="delete_id" id="confirmDeleteId">
                    <div style="display:flex; gap:10px;">
                        <button type="button" class="btn-cancel w-100" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_treatment" class="btn-danger-confirm">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ─── VALIDATION TOAST ──────────────────────────────── -->
<div class="validation-toast-wrap">
    <div class="validation-toast" id="validationToast">
        <i class="bi bi-exclamation-circle-fill"></i>
        <span id="toastMessage">Please select exactly 2 treatments.</span>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

// ─── TAB MANAGEMENT ────────────────────────────────────
function switchTab(type) {
    const indivPanel = document.getElementById('panel-individual');
    const pkgPanel = document.getElementById('panel-package');
    const indivBtn = document.getElementById('indiv-tab');
    const pkgBtn = document.getElementById('pkg-tab');

    if (type === 'individual') {
        indivPanel.style.display = 'block';
        pkgPanel.style.display = 'none';
        indivBtn.classList.add('active');
        pkgBtn.classList.remove('active');
    } else {
        indivPanel.style.display = 'none';
        pkgPanel.style.display = 'block';
        pkgBtn.classList.add('active');
        indivBtn.classList.remove('active');
    }
    localStorage.setItem('activeServiceTab', type);
}

document.addEventListener('DOMContentLoaded', function() {
    const saved = localStorage.getItem('activeServiceTab');
    if (saved) switchTab(saved);

    if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
    const scrollPos = localStorage.getItem('manage_treatments_scroll');
    if (scrollPos) {
        window.scrollTo(0, parseInt(scrollPos));
        localStorage.removeItem('manage_treatments_scroll');
    }
});

window.addEventListener('load', () => {
    const scrollPos = localStorage.getItem('manage_treatments_scroll');
    if (scrollPos) {
        window.scrollTo(0, parseInt(scrollPos));
        localStorage.removeItem('manage_treatments_scroll');
    }
});

// ─── SORT ──────────────────────────────────────────────
function applySort(param, value) {
    const url = new URL(window.location.href);
    url.searchParams.set(param, value);
    url.searchParams.delete('msg'); // never carry over flash messages on sort
    window.location.href = url.toString();
}

// ─── LIVE SEARCH ───────────────────────────────────────
function liveSearch(inputId, tableId) {
    const filter = document.getElementById(inputId).value.toLowerCase();
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');
    const noResultsId = (tableId === 'indivTable') ? 'noResultsIndiv' : 'noResultsPkg';
    const noResultsRow = document.getElementById(noResultsId);
    let hasVisible = false;

    for (let i = 1; i < rows.length; i++) {
        if (rows[i] === noResultsRow) continue;
        const nameCell = rows[i].getElementsByTagName('td')[0];
        if (nameCell) {
            const txt = nameCell.textContent || nameCell.innerText;
            const show = txt.toLowerCase().indexOf(filter) > -1;
            rows[i].style.display = show ? '' : 'none';
            if (show) hasVisible = true;
        }
    }

    noResultsRow.style.display = hasVisible ? 'none' : '';
}

// ─── VALIDATION TOAST ──────────────────────────────────
function showValidationError(message) {
    const toast = document.getElementById('validationToast');
    document.getElementById('toastMessage').innerText = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

// ─── PACKAGE SELECTION ─────────────────────────────────
function toggleTreatmentSelection(card, checkboxId) {
    const checkbox = document.getElementById(checkboxId);
    const checkedCount = document.querySelectorAll('.package-checkbox:checked').length;

    if (!checkbox.checked && checkedCount >= 2) {
        card.classList.add('shake');
        setTimeout(() => card.classList.remove('shake'), 400);
        showValidationError('A package must consist of exactly 2 treatments.');
        return;
    }

    checkbox.checked = !checkbox.checked;
    card.classList.toggle('selected', checkbox.checked);
    updateSelectionCount();
}

function updateSelectionCount() {
    const checkboxes = document.querySelectorAll('.package-checkbox:checked');
    const checkedCount = checkboxes.length;
    const counterDisplay = document.getElementById('selectionCount');
    const durationInput = document.getElementById('duration');
    const serviceType = document.getElementById('type').value;

    counterDisplay.innerText = `${checkedCount} selected`;
    counterDisplay.className = checkedCount === 2 ? 'selection-badge complete' : 'selection-badge';

    if (serviceType === 'package') {
        let totalDuration = 0;
        checkboxes.forEach(cb => {
            const card = cb.closest('.treatment-selectable-card');
            const matches = card.innerText.match(/(\d+)m/);
            if (matches) totalDuration += parseInt(matches[1]);
        });
        durationInput.value = totalDuration;
    }
}

// ─── OPEN MODAL ────────────────────────────────────────
function openModal(serviceType) {
    const form = document.getElementById('serviceForm');
    form.reset();

    document.querySelectorAll('.treatment-selectable-card').forEach(card => card.classList.remove('selected'));
    document.getElementById('serviceValidationError').classList.remove('visible');

    document.getElementById('treatment_id').value = '';
    document.getElementById('type').value = serviceType;

    const isPackage = serviceType === 'package';
    document.getElementById('modalEyebrow').innerText  = isPackage ? 'Build New Package' : 'Add New Treatment';
    document.getElementById('modalTitle').innerText    = isPackage ? 'New Package' : 'New Treatment';
    document.getElementById('editNameOnPhoto').innerText = isPackage ? 'New Package' : 'New Treatment';
    document.getElementById('editTypeLabelOnPhoto').innerText = isPackage ? 'Exclusive Package' : 'Individual Treatment';

    document.getElementById('editImagePreview').src = '../assets/img/treatments/default.jpg';
    document.getElementById('packageSelectionArea').style.display = isPackage ? 'block' : 'none';

    const durationInput = document.getElementById('duration');
    durationInput.readOnly = isPackage;
    durationInput.style.background = isPackage ? '#f5f3ef' : '';
    durationInput.style.color = isPackage ? 'var(--muted)' : '';

    const hint = document.getElementById('imageRequiredHint');
    if (hint) hint.style.display = (!isPackage) ? 'block' : 'none';

    new bootstrap.Modal(document.getElementById('serviceModal')).show();
}



// ─── EDIT SERVICE ──────────────────────────────────────
// ─── EDIT IMAGE PREVIEW ────────────────────────────────
function previewEditImage(input) {
    const preview = document.getElementById('editImagePreview');
    const placeholder = document.getElementById('editImagePlaceholder');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ─── EDIT SERVICE ──────────────────────────────────────
function editService(data, contents = []) {
    document.getElementById('serviceForm').reset();
    document.getElementById('serviceValidationError').classList.remove('visible');

    const isPackage = data.type === 'package';
    const durationInput = document.getElementById('duration');

    document.getElementById('treatment_id').value = data.treatment_id;
    document.getElementById('type').value         = data.type;
    document.getElementById('name').value         = data.name;
    document.getElementById('duration').value     = data.duration_minutes;
    document.getElementById('price').value        = data.price;
    document.getElementById('description').value  = data.description;

    document.getElementById('modalEyebrow').innerText      = isPackage ? 'Edit Package' : 'Edit Treatment';
    document.getElementById('modalTitle').innerText        = data.name;
    document.getElementById('editNameOnPhoto').innerText   = data.name;
    document.getElementById('editTypeLabelOnPhoto').innerText = isPackage ? 'Exclusive Package' : 'Individual Treatment';

    const imgSrc = (data.image && data.image !== 'default.jpg')
        ? '../assets/img/treatments/' + data.image
        : '../assets/img/treatments/default.jpg';
    document.getElementById('editImagePreview').src = imgSrc;

    document.getElementById('packageSelectionArea').style.display = isPackage ? 'block' : 'none';
    durationInput.readOnly    = isPackage;
    durationInput.style.background = isPackage ? '#f5f3ef' : '';
    durationInput.style.color      = isPackage ? 'var(--muted)' : '';

    if (isPackage) {
        document.querySelectorAll('.treatment-selectable-card').forEach(c => c.classList.remove('selected'));
        contents.forEach(t_id => {
            const cb = document.getElementById('treat_' + t_id);
            if (cb) { cb.checked = true; cb.closest('.treatment-selectable-card').classList.add('selected'); }
        });
        updateSelectionCount();
    }

    new bootstrap.Modal(document.getElementById('serviceModal')).show();
}


// ─── INDIVIDUAL TREATMENTS MAP (for package view) ──────
const individualMap = <?= json_encode(array_column($individual, null, 'treatment_id')) ?>;

// ─── VIEW SERVICE ──────────────────────────────────────
let _currentViewService = null;

function viewService(data, contents = []) {
    _currentViewService = { data, contents };

    const isPackage = data.type === 'package';

    const imgSrc = (data.image && data.image !== 'default.jpg')
        ? '../assets/img/treatments/' + data.image
        : '../assets/img/treatments/default.jpg';

    document.getElementById('viewImage').src              = imgSrc;
    document.getElementById('viewNameOnPhoto').innerText  = data.name;
    document.getElementById('viewTypeBadge').innerText    = isPackage ? 'Exclusive Package' : 'Individual Treatment';
    document.getElementById('viewName').innerText         = data.name;
    document.getElementById('viewDuration').innerText     = data.duration_minutes + ' minutes';
    document.getElementById('viewPrice').innerText        = '₱' + parseFloat(data.price).toLocaleString('en-PH', {minimumFractionDigits: 2});
    document.getElementById('viewDescription').innerText  = data.description || '—';

    const pkgSection = document.getElementById('viewPackageContents');
    const treatList  = document.getElementById('viewTreatmentList');

    if (isPackage && contents.length > 0) {
        pkgSection.style.display = 'block';
        treatList.innerHTML = '';
        contents.forEach(t_id => {
            const t = individualMap[t_id];
            if (!t) return;
            treatList.innerHTML += `
                <div style="display:flex;align-items:center;gap:12px;background:var(--white);border:1px solid rgba(201,169,110,.15);border-radius:10px;padding:10px 14px;">
                    <img src="../assets/img/treatments/${t.image || 'default.jpg'}"
                         style="width:40px;height:40px;border-radius:6px;object-fit:cover;">
                    <div>
                        <div style="font-size:.875rem;font-weight:600;color:var(--dark);">${t.name}</div>
                        <div style="font-size:.78rem;color:var(--muted);">${t.duration_minutes} mins &nbsp;·&nbsp; ₱${parseFloat(t.price).toLocaleString('en-PH', {minimumFractionDigits:2})}</div>
                    </div>
                </div>`;
        });
    } else {
        pkgSection.style.display = 'none';
    }

    document.getElementById('viewEditBtn').onclick = function() {
        bootstrap.Modal.getInstance(document.getElementById('viewServiceModal')).hide();
        setTimeout(() => editService(_currentViewService.data, _currentViewService.contents), 350);
    };

    new bootstrap.Modal(document.getElementById('viewServiceModal')).show();
}

// ─── FORM VALIDATION ───────────────────────────────────
document.getElementById('serviceForm').onsubmit = function(e) {
    const type        = document.getElementById('type').value;
    const treatmentId = document.getElementById('treatment_id').value;
    const imageInput  = document.getElementById('serviceImageInput');

    if (type === 'package') {
        const checkedCount = document.querySelectorAll('.package-checkbox:checked').length;
        if (checkedCount !== 2) {
            e.preventDefault();
            showServiceError('Please select exactly 2 treatments before saving.');
            return false;
        }
    }

    if (type === 'individual' && !treatmentId && imageInput && !imageInput.files.length) {
        e.preventDefault();
        showServiceError('A service image is required.');
        return false;
    }
};

function showServiceError(message) {
    const box = document.getElementById('serviceValidationError');
    document.getElementById('serviceErrorText').innerText = message;
    box.classList.add('visible');
    document.querySelector('#serviceModal [style*="overflow-y:auto"]').scrollTo({ top: 0, behavior: 'smooth' });
}

// ─── AUTO-DISMISS SUCCESS BANNER ───────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const banner = document.getElementById('successBanner');
    if (banner) {
        setTimeout(() => {
            banner.classList.add('fade-out');
            setTimeout(() => banner.remove(), 500);
        }, 3500);
    }
});

</script>
</body>
</html>