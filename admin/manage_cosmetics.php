<?php
session_start();
require_once '../config/db.php'; 

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
        header("Location: manage_cosmetics.php?msg=deleted");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Delete Error: " . $e->getMessage());
    }
}

// --- FIXED HANDLE SAVE (ADD/EDIT) ---
if (isset($_POST['save_cosmetic'])) {
    $id = $_POST['cosmetic_id'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? ''; // Added this
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
            // Updated UPDATE query to include description
            $sql = "UPDATE cosmetics SET name=?, description=?, price=?, size=?, image=?, category=? WHERE cosmetic_id=?";
            $pdo->prepare($sql)->execute([$name, $description, $price, $size, $image_name, $category, $id]);
        } else {
            // Updated INSERT query to include description
            $sql = "INSERT INTO cosmetics (name, description, price, size, image, category) VALUES (?, ?, ?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$name, $description, $price, $size, $image_name, $category]);
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
    <title>Manage Cosmetics | L&B Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-width: 260px; --brand-dark: #1a1a1a; --accent-gold: #c5a059; }
        body { background-color: #f8f9fa; }
        .sidebar { width: var(--sidebar-width); height: 100vh; background: var(--brand-dark); position: fixed; left: 0; top: 0; }
        .sidebar .nav-link { color: rgba(255, 255, 255, 0.7); padding: 0.8rem 1.5rem; text-decoration: none; display: block; }
        .sidebar .nav-link.active { color: white; background: rgba(255, 255, 255, 0.1); border-left: 4px solid var(--accent-gold); }
        .main-content { margin-left: var(--sidebar-width); padding: 2rem; }
        .card { border: none; border-radius: 12px; overflow: hidden; }
        .badge-category { background: #eef2ff; color: #4338ca; font-weight: 600; padding: 0.4rem 0.8rem; border-radius: 8px; font-size: 0.8rem; }
        #currentImagePreview { width: 100px; height: 100px; object-fit: cover; border-radius: 10px; border: 1px solid #eee; }
    </style>
</head>
<body>

<nav class="sidebar">
    <div class="p-4 mb-4 text-white"><h4>L&B <span style="color:var(--accent-gold)">Admin</span></h4></div>
    <div class="nav flex-column">
        <a href="dashboard.php" class="nav-link">Dashboard</a>
        <a href="manage_cosmetics.php" class="nav-link active">Cosmetics</a>
        <a href="logout.php" class="nav-link text-danger mt-5">Logout</a>
    </div>
</nav>

<div class="main-content">
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Product saved successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Cosmetics Inventory</h2>
        <button class="btn btn-dark rounded-pill px-4" onclick="openAddModal()">+ Add Product</button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <input type="text" id="searchInput" class="form-control" placeholder="Search name..." onkeyup="filterTable()">
        </div>
        <div class="col-md-6 text-end">
            <select id="categoryFilter" class="form-select w-50 ms-auto" onchange="filterTable()">
                <option value="all">All Categories</option>
                <option value="Scented Candle">Scented Candles</option>
                <option value="Essential Oil">Essential Oils</option>
                <option value="Spa Accessory">Spa Accessories</option>
            </select>
        </div>
    </div>

    <div class="card shadow-sm">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Product Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_cosmetics as $item): ?>
                <tr class="cosmetic-row" data-category="<?= htmlspecialchars($item['category']) ?>">
                    <td class="ps-4 fw-bold product-name"><?= htmlspecialchars($item['name']) ?></td>
                    <td><span class="badge-category"><?= htmlspecialchars($item['category']) ?></span></td>
                    <td>₱<?= number_format($item['price'], 2) ?></td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-light rounded-circle" onclick='editCosmetic(<?= json_encode($item) ?>)'><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger rounded-circle ms-1" onclick="confirmDelete(<?= $item['cosmetic_id'] ?>)"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="cosmeticModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0">
            <form id="cosmeticForm" action="manage_cosmetics.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <h5 class="fw-bold mb-4" id="modalTitle">Product Details</h5>
                    <input type="hidden" name="cosmetic_id" id="cosmetic_id">
                    <input type="hidden" name="existing_image" id="existing_image">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="small fw-bold">Product Name</label>
                            <input type="text" name="name" id="name" class="form-control mb-3" required>
                            <div class="row">
                                <div class="col-6">
                                    <label class="small fw-bold">Category</label>
                                    <select name="category" id="category" class="form-select">
                                        <option value="Scented Candle">Scented Candle</option>
                                        <option value="Essential Oil">Essential Oil</option>
                                        <option value="Spa Accessory">Spa Accessory</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="small fw-bold">Price</label>
                                    <input type="number" step="0.01" name="price" id="price" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <img id="currentImagePreview" src="../assets/img/cosmetics/placeholder.jpg">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Size</label>
                            <input type="text" name="size" id="size" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold">Image</label>
                            <input type="file" name="image" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="small fw-bold">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" name="save_cosmetic" class="btn btn-dark w-100 rounded-pill py-2">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0">
            <form action="manage_cosmetics.php" method="POST" class="p-4 text-center">
                <input type="hidden" name="delete_id" id="confirmDeleteId">
                <h5 class="fw-bold">Delete Item?</h5>
                <button type="submit" name="delete_cosmetic" class="btn btn-danger w-100 rounded-pill mt-3">Confirm Delete</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const cosmeticModal = new bootstrap.Modal(document.getElementById('cosmeticModal'));
const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));

function filterTable() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const category = document.getElementById('categoryFilter').value;
    const rows = document.querySelectorAll('.cosmetic-row');
    rows.forEach(row => {
        const name = row.querySelector('.product-name').textContent.toLowerCase();
        const cat = row.getAttribute('data-category');
        row.style.display = (name.includes(search) && (category === 'all' || cat === category)) ? '' : 'none';
    });
}

function openAddModal() {
    document.getElementById('cosmeticForm').reset();
    document.getElementById('cosmetic_id').value = '';
    document.getElementById('currentImagePreview').src = '../assets/img/cosmetics/placeholder.jpg';
    document.getElementById('modalTitle').innerText = 'Add New Product';
    cosmeticModal.show();
}

function editCosmetic(data) {
    document.getElementById('cosmetic_id').value = data.cosmetic_id;
    document.getElementById('name').value = data.name;
    document.getElementById('description').value = data.description;
    document.getElementById('category').value = data.category;
    document.getElementById('price').value = data.price;
    document.getElementById('size').value = data.size;
    document.getElementById('existing_image').value = data.image;
    document.getElementById('currentImagePreview').src = '../assets/img/cosmetics/' + (data.image || 'placeholder.jpg');
    document.getElementById('modalTitle').innerText = 'Edit Product';
    cosmeticModal.show();
}

function confirmDelete(id) {
    document.getElementById('confirmDeleteId').value = id;
    deleteModal.show();
}
</script>
</body>
</html>