<?php
session_start();
require_once '../config/db.php'; 
require_once '../includes/log_action.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}

// --- HANDLE DELETE ---
if (isset($_POST['delete_cosmetic'])) {
    $delete_id = $_POST['delete_id'];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM appointment_cosmetics WHERE cosmetic_id = ?")->execute([$delete_id]);
        $pdo->prepare("DELETE FROM cosmetics WHERE cosmetic_id = ?")->execute([$delete_id]);
        $pdo->commit();
        logAction($pdo, 'Delete Cosmetic', "Deleted cosmetic ID $delete_id.");
        header("Location: manage_cosmetics.php?msg=deleted");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Delete Error: " . $e->getMessage());
    }
}

// --- HANDLE SAVE (ADD/EDIT) ---
if (isset($_POST['save_cosmetic'])) {
    $id = $_POST['cosmetic_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $size = $_POST['size'] ?? '';
    $category = $_POST['category'] ?? '';
    
    $image_name = $_POST['existing_image'] ?? 'placeholder.jpg';
    if (!empty($_FILES['image']['name'])) {
        $image_name = time() . '_' . $_FILES['image']['name'];
        if (!is_dir("../assets/img/cosmetics/")) {
            mkdir("../assets/img/cosmetics/", 0777, true);
        }
        move_uploaded_file($_FILES['image']['tmp_name'], "../assets/img/cosmetics/" . $image_name);
    }

    try {
        if (!empty($id)) {
            $sql = "UPDATE cosmetics SET name=?, description=?, price=?, size=?, image=?, category=? WHERE cosmetic_id=?";
            $pdo->prepare($sql)->execute([$name, $description, $price, $size, $image_name, $category, $id]);
            logAction($pdo, 'Edit Cosmetic', "Updated cosmetic '$name' (ID $id).");
        } else {
            $sql = "INSERT INTO cosmetics (name, description, price, size, image, category) VALUES (?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$name, $description, $price, $size, $image_name, $category]);
            logAction($pdo, 'Add Cosmetic', "Added new cosmetic '$name' under '$category'.");
        }
        header("Location: manage_cosmetics.php?msg=success");
        exit();
    } catch (PDOException $e) {
        die("Save Error: " . $e->getMessage());
    }
}

try {
    $all_cosmetics = $pdo->query("SELECT * FROM cosmetics ORDER BY category ASC, name ASC")->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cosmetics | L&B Admin</title>
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
            font-size: 18px;
            color: var(--dark);
            min-height: 100vh;
        }

        

        /* ─── MAIN CONTENT ────────────────────────── */
        .main-content {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            padding: 48px 48px 64px;
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
            flex-wrap: wrap;
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
            transition: opacity 0.5s ease, transform 0.5s ease;
        }

        .success-banner i { color: var(--gold); font-size: 1rem; }

        .success-banner.fade-out {
            opacity: 0;
            transform: translateY(-8px);
            pointer-events: none;
        }

        @keyframes slideInTop {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ─── TOOLBAR ─────────────────────────────── */
        .section-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            gap: 16px;
            flex-wrap: wrap;
        }

        .toolbar-left { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

        .search-wrap { position: relative; }

        .search-wrap i {
            position: absolute;
            left: 14px; top: 50%;
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
            padding: 10px 32px 10px 16px;
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

        .lb-table { width: 100%; border-collapse: collapse; }

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

        .lb-table td { padding: 14px 20px; vertical-align: middle; }

        /* ─── PRODUCT CELL ────────────────────────── */
        .product-cell { display: flex; align-items: center; gap: 14px; }

        .product-thumb {
            width: 48px;
            height: 48px;
            border-radius: 9px;
            object-fit: cover;
            border: 1px solid rgba(26,26,26,0.07);
            flex-shrink: 0;
            background: #f5f3ef;
        }

        .product-name {
            font-weight: 600;
            font-size: 0.925rem;
            color: var(--dark);
            line-height: 1.3;
        }

        .product-size {
            font-size: 0.78rem;
            color: var(--muted);
            margin-top: 2px;
        }

        /* ─── CATEGORY BADGE ──────────────────────── */
        .cat-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            white-space: nowrap;
        }

        .cat-candle   { background: rgba(201,169,110,0.12); color: #a07840; }
        .cat-oil      { background: rgba(100,160,100,0.10); color: #3a7a3a; }
        .cat-accessory{ background: rgba(80,100,200,0.10);  color: #3a4aaa; }
        .cat-default  { background: rgba(26,26,26,0.07);    color: var(--muted); }

        /* ─── PRICE ───────────────────────────────── */
        .price-text { font-weight: 700; font-size: 0.95rem; color: var(--dark); font-variant-numeric: tabular-nums; }
        .price-currency { font-size: 0.75rem; color: var(--gold); font-weight: 600; margin-right: 1px; }

        /* ─── ACTION BUTTONS ──────────────────────── */
        .action-btns { display: flex; gap: 8px; justify-content: flex-end; }

        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px; height: 34px;
            border-radius: 8px;
            border: 1.5px solid;
            background: transparent;
            cursor: pointer;
            transition: all 0.18s ease;
            font-size: 0.85rem;
        }

        .btn-icon-edit { border-color: rgba(201,169,110,0.35); color: var(--gold); }
        .btn-icon-edit:hover {
            background: var(--gold); color: var(--white);
            border-color: var(--gold);
            box-shadow: 0 4px 12px rgba(201,169,110,0.3);
        }

        .btn-icon-delete { border-color: rgba(220,53,69,0.25); color: #dc3545; }
        .btn-icon-delete:hover {
            background: #dc3545; color: var(--white);
            border-color: #dc3545;
            box-shadow: 0 4px 12px rgba(220,53,69,0.25);
        }

        /* ─── NO RESULTS ──────────────────────────── */
        .no-results-row { display: none; }
        .no-results-row td {
            text-align: center;
            padding: 52px 20px;
            color: var(--muted);
            font-size: 0.9rem;
        }
        .no-results-icon { font-size: 2rem; color: var(--gold-light); display: block; margin-bottom: 10px; }

        /* ─── MODAL ───────────────────────────────── */
        .modal-content {
            border: none;
            border-radius: 14px;
            box-shadow: 0 32px 80px rgba(0,0,0,0.18);
            overflow: hidden;
        }

        .modal-header {
            padding: 28px 32px 22px;
            border-bottom: 1px solid var(--border);
            background: var(--dark);
        }

        .modal-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--white);
            letter-spacing: 0.02em;
        }

        .modal-header .btn-close { filter: brightness(0) invert(1); opacity: 0.5; }
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

        .lb-input, .lb-select, .lb-textarea {
            width: 100%;
            padding: 11px 16px;
            border: 1.5px solid rgba(26,26,26,0.12);
            border-radius: 8px;
            background: var(--white);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
            color: var(--dark);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .lb-input:focus, .lb-select:focus, .lb-textarea:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(201,169,110,0.12);
        }

        .lb-textarea { resize: vertical; min-height: 80px; }

        /* ─── IMAGE PREVIEW (MODAL) ───────────────── */
        .img-preview-wrap {
            position: relative;
            width: 100%;
            aspect-ratio: 1 / 1;
            border-radius: 10px;
            overflow: hidden;
            border: 1.5px solid var(--border);
            background: #f5f3ef;
        }

        .img-preview-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .img-preview-wrap .img-overlay {
            position: absolute;
            inset: 0;
            background: rgba(26,26,26,0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .img-preview-wrap:hover .img-overlay { opacity: 1; }

        .img-overlay span {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--white);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        /* ─── MODAL FOOTER ────────────────────────── */
        .modal-footer {
            padding: 18px 32px;
            border-top: 1px solid var(--border);
            background: var(--cream);
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
        .delete-modal .modal-body {
            text-align: center;
            padding: 40px 32px;
            background: var(--cream);
        }

        .delete-icon {
            width: 64px; height: 64px;
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

        /* ─── FORM GRID ───────────────────────────── */
        .form-grid { display: grid; gap: 18px; }
        .form-grid-2 { grid-template-columns: 1fr 1fr; }
        .form-grid-3 { grid-template-columns: 1fr 1fr 1fr; }

        @media (max-width: 576px) {
            .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; }
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
            .main-content { margin-left: 0; padding: 28px 20px; }
        }
        .gold-rule {
            width: 48px;
            height: 2px;
            background: linear-gradient(90deg, var(--gold), var(--gold-light));
            border-radius: 2px;
            margin-bottom: 36px;
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
            <?= $_GET['msg'] === 'deleted' ? 'Product has been removed from the inventory.' : 'Product saved successfully.' ?>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-left">
            <div class="eyebrow">Lumière &amp; Bliss Studio</div>
            <h1>Cosmetics Inventory</h1>
            <div class="gold-rule"></div>
        </div>
        
    </div>
        

    <!-- Toolbar -->
    <div class="section-toolbar">
        <div class="toolbar-left">
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" class="search-input" id="searchInput"
                    placeholder="Search products…"
                    onkeyup="filterTable()">
            </div>
            <select class="filter-select" id="categoryFilter" onchange="filterTable()">
                <option value="all">All Categories</option>
                <option value="Scented Candle">Scented Candles</option>
                <option value="Essential Oil">Essential Oils</option>
                <option value="Spa Accessory">Spa Accessories</option>
            </select>
        </div>
        <button class="btn-add" onclick="openAddModal()">
            <i class="bi bi-plus-lg"></i> Add Product
        </button>
    </div>

    <!-- Table -->
    <div class="data-card">
        <table class="lb-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th style="text-align:right; padding-right:24px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_cosmetics as $item): ?>
                <?php
                    $catClass = 'cat-default';
                    if ($item['category'] === 'Scented Candle')  $catClass = 'cat-candle';
                    elseif ($item['category'] === 'Essential Oil')  $catClass = 'cat-oil';
                    elseif ($item['category'] === 'Spa Accessory') $catClass = 'cat-accessory';
                ?>
                <tr class="cosmetic-row" data-category="<?= htmlspecialchars($item['category']) ?>">
                    <td>
                        <div class="product-cell">
                            <img class="product-thumb"
                                 src="../assets/img/cosmetics/<?= htmlspecialchars($item['image'] ?: 'placeholder.jpg') ?>"
                                 alt="<?= htmlspecialchars($item['name']) ?>">
                            <div>
                                <div class="product-name"><?= htmlspecialchars($item['name']) ?></div>
                                <?php if (!empty($item['size'])): ?>
                                <div class="product-size"><?= htmlspecialchars($item['size']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="cat-badge <?= $catClass ?>">
                            <?= htmlspecialchars($item['category']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="price-currency">₱</span><span class="price-text"><?= number_format($item['price'], 2) ?></span>
                    </td>
                    <td style="text-align:right; padding-right:20px;">
                        <div class="action-btns">
                            <button class="btn-icon btn-icon-edit" title="Edit"
                                onclick='editCosmetic(<?= json_encode($item) ?>)'>
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn-icon btn-icon-delete" title="Delete"
                                onclick="confirmDelete(<?= $item['cosmetic_id'] ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr class="no-results-row" id="noResultsRow">
                    <td colspan="4">
                        <span class="no-results-icon"><i class="bi bi-search"></i></span>
                        No products match your search.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</main>

<!-- ─── COSMETIC MODAL ───────────────────────────────── -->
<div class="modal fade" id="cosmeticModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="cosmeticForm" action="manage_cosmetics.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="cosmetic_id" id="cosmetic_id">
                    <input type="hidden" name="existing_image" id="existing_image">

                    <div style="display:grid; grid-template-columns: 1fr 140px; gap: 24px; align-items: start;">
                        <!-- Left: fields -->
                        <div class="form-grid">
                            <!-- Name -->
                            <div>
                                <label class="lb-label">Product Name</label>
                                <input type="text" name="name" id="name" class="lb-input" placeholder="e.g. Lavender Essential Oil" required>
                            </div>

                            <!-- Category + Price -->
                            <div class="form-grid form-grid-2">
                                <div>
                                    <label class="lb-label">Category</label>
                                    <select name="category" id="category" class="lb-select">
                                        <option value="Scented Candle">Scented Candle</option>
                                        <option value="Essential Oil">Essential Oil</option>
                                        <option value="Spa Accessory">Spa Accessory</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="lb-label">Price (₱)</label>
                                    <input type="number" step="0.01" name="price" id="price" class="lb-input" placeholder="0.00" required>
                                </div>
                            </div>

                            <!-- Size + Image file -->
                            <div class="form-grid form-grid-2">
                                <div>
                                    <label class="lb-label">Size / Volume</label>
                                    <input type="text" name="size" id="size" class="lb-input" placeholder="e.g. 100ml" required>
                                </div>
                                <div>
                                    <label class="lb-label">Product Image</label>
                                    <input type="file" name="image" id="imageInput" class="lb-input"
                                        accept="image/*" style="padding:9px 14px;"
                                        onchange="previewImage(this)">
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="lb-label">Description</label>
                                <textarea name="description" id="description" class="lb-textarea"
                                    placeholder="Brief description of the product…"></textarea>
                            </div>
                        </div>

                        <!-- Right: image preview -->
                        <div>
                            <label class="lb-label" style="margin-bottom:8px;">Preview</label>
                            <div class="img-preview-wrap">
                                <img id="currentImagePreview" src="../assets/img/cosmetics/placeholder.jpg" alt="Preview">
                                <div class="img-overlay"><span>Change</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-end">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_cosmetic" class="btn-save">Save Product</button>
                </div>
            </form>
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
                <h5>Remove Product?</h5>
                <p>This action is permanent and cannot be undone.</p>
                <form action="manage_cosmetics.php" method="POST">
                    <input type="hidden" name="delete_id" id="confirmDeleteId">
                    <div style="display:flex; gap:10px;">
                        <button type="button" class="btn-cancel w-100" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_cosmetic" class="btn-danger-confirm">Delete</button>
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
        <span id="toastMessage">An error occurred.</span>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

const cosmeticModal  = new bootstrap.Modal(document.getElementById('cosmeticModal'));
const deleteModal    = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));

// ─── FILTER TABLE ──────────────────────────────────────
function filterTable() {
    const search   = document.getElementById('searchInput').value.toLowerCase();
    const category = document.getElementById('categoryFilter').value;
    const rows     = document.querySelectorAll('.cosmetic-row');
    let hasVisible = false;

    rows.forEach(row => {
        const name = row.querySelector('.product-name').textContent.toLowerCase();
        const cat  = row.getAttribute('data-category');
        const show = name.includes(search) && (category === 'all' || cat === category);
        row.style.display = show ? '' : 'none';
        if (show) hasVisible = true;
    });

    document.getElementById('noResultsRow').style.display = hasVisible ? 'none' : '';
}

// ─── OPEN ADD MODAL ────────────────────────────────────
function openAddModal() {
    document.getElementById('cosmeticForm').reset();
    document.getElementById('cosmetic_id').value   = '';
    document.getElementById('existing_image').value = '';
    document.getElementById('currentImagePreview').src = '../assets/img/cosmetics/placeholder.jpg';
    document.getElementById('modalTitle').innerText = 'Add New Product';
    cosmeticModal.show();
}

// ─── EDIT COSMETIC ─────────────────────────────────────
function editCosmetic(data) {
    document.getElementById('cosmetic_id').value    = data.cosmetic_id;
    document.getElementById('name').value           = data.name;
    document.getElementById('description').value    = data.description;
    document.getElementById('category').value       = data.category;
    document.getElementById('price').value          = data.price;
    document.getElementById('size').value           = data.size;
    document.getElementById('existing_image').value = data.image;
    document.getElementById('currentImagePreview').src =
        '../assets/img/cosmetics/' + (data.image || 'placeholder.jpg');
    document.getElementById('modalTitle').innerText = 'Edit Product';
    cosmeticModal.show();
}

// ─── CONFIRM DELETE ────────────────────────────────────
function confirmDelete(id) {
    document.getElementById('confirmDeleteId').value = id;
    deleteModal.show();
}

// ─── IMAGE PREVIEW ON FILE SELECT ─────────────────────
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('currentImagePreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
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