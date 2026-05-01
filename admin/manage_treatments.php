<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}

// Handle Add/Edit
// Handle Add/Edit
if (isset($_POST['save_treatment'])) {
    $id = $_POST['treatment_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $duration = $_POST['duration']; // This comes from your form input 'name="duration"'
    $price = $_POST['price'];
    $type = $_POST['type'];
    $selected_treatments = $_POST['package_contents'] ?? [];

    if (empty($id)) {
        // Updated column name to duration_minutes
        $stmt = $pdo->prepare("INSERT INTO treatments (name, description, duration_minutes, price, type, status) VALUES (?, ?, ?, ?, ?, 'available')");
        $stmt->execute([$name, $description, $duration, $price, $type]);
        $id = $pdo->lastInsertId();
    } else {
        // Updated column name to duration_minutes
        $stmt = $pdo->prepare("UPDATE treatments SET name=?, description=?, duration_minutes=?, price=?, type=? WHERE treatment_id=?");
        $stmt->execute([$name, $description, $duration, $price, $type, $id]);
        
        // Clear old package items if editing
        $pdo->prepare("DELETE FROM package_items WHERE package_id = ?")->execute([$id]);
    }

    // Handle package items logic...
    if ($type === 'package' && !empty($selected_treatments)) {
        foreach ($selected_treatments as $t_id) {
            $pdo->prepare("INSERT INTO package_items (package_id, treatment_id) VALUES (?, ?)")->execute([$id, $t_id]);
        }
    }
    header("Location: manage_treatments.php");
    exit();
}

// Handle Archive
if (isset($_POST['archive_id'])) {
    $stmt = $pdo->prepare("UPDATE treatments SET status = 'archived' WHERE treatment_id = ?");
    $stmt->execute([$_POST['archive_id']]);
    header("Location: manage_treatments.php");
    exit();
}

// Fetch separately
// Fetch individual treatments to populate the package selection list
$individual = $pdo->query("SELECT * FROM treatments WHERE status = 'available' AND type = 'individual' ORDER BY name ASC")->fetchAll();
$packages = $pdo->query("SELECT * FROM treatments WHERE status = 'available' AND type = 'package' ORDER BY name ASC")->fetchAll();

// Logic to get package contents for editing via AJAX or JSON
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
    <title>Manage Services | L&B Admin</title>
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
        <h2 class="fw-bold">Service Management</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-light px-4" onclick="openModal('individual')">
                <i class="bi bi-plus-lg me-2"></i> New Treatment
            </button>
            <button class="btn btn-blue px-4" onclick="openModal('package')">
                <i class="bi bi-box-seam me-2"></i> New Package
            </button>
        </div>
    </div>

    <!-- Individual Treatments -->
    <h5 class="mb-3 opacity-75">Individual Treatments</h5>
    <div class="card p-3">
        <table class="table align-middle">
            <thead>
                <tr><th>Treatment Name</th><th>Duration</th><th>Price</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach($individual as $t): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($t['name']) ?></td>
                    <td><?= $t['duration_minutes'] ?> mins</td>
                    <td>₱<?= number_format($t['price'], 2) ?></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-dark" onclick='editService(<?= json_encode($t) ?>)'><i class="bi bi-pencil"></i></button>
                        <form action="" method="POST" class="d-inline">
                            <input type="hidden" name="archive_id" value="<?= $t['treatment_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-dark text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Packages -->
    <h5 class="mb-3 text-gold">Exclusive Packages</h5>
    <div class="card p-3">
        <table class="table align-middle">
            <thead>
                <tr><th>Package Name</th><th>Duration</th><th>Price</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach($packages as $p): ?>
                <tr>
                    <td class="fw-bold text-gold"><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= $p['duration'] ?> mins</td>
                    <td>₱<?= number_format($p['price'], 2) ?></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-dark" onclick='editService(<?= json_encode($p) ?>, <?= json_encode($package_map[$p['treatment_id']] ?? []) ?>)'><i class="bi bi-pencil"></i></button>
                        <form action="" method="POST" class="d-inline">
                            <input type="hidden" name="archive_id" value="<?= $p['treatment_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-dark text-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="serviceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="" method="POST" id="serviceForm">
                <div class="modal-header border-0">
                    <h5 class="fw-bold" id="modalTitle">New Service</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="treatment_id" id="treatment_id">
                    <input type="hidden" name="type" id="type">
                    
                    <div class="mb-3">
                        <label class="small text-muted mb-1">Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>

                    <!-- Package Selection Area -->
                    <!-- Package Selection Area -->
<div id="packageSelectionArea" style="display: none;" class="mb-4 p-3 rounded border border-secondary bg-dark">
    <label class="small text-gold fw-bold mb-2 d-block">
        <i class="bi bi- stars me-1"></i> Bundle Individual Treatments:
    </label>
    
    <!-- This functions like a scrollable combo-box list -->
    <div style="max-height: 180px; overflow-y: auto; border: 1px solid #444;" class="p-2 rounded bg-opacity-10 bg-white">
        <?php if(empty($individual)): ?>
            <p class="text-muted small mb-0 p-2">No individual treatments available to bundle.</p>
        <?php else: ?>
            <?php foreach($individual as $indiv): ?>
                <div class="form-check custom-check mb-2">
                    <input class="form-check-input" type="checkbox" 
                           name="package_contents[]" 
                           value="<?= $indiv['treatment_id'] ?>" 
                           id="treat_<?= $indiv['treatment_id'] ?>">
                    <label class="form-check-label small text-white-50" for="treat_<?= $indiv['treatment_id'] ?>">
                        <?= htmlspecialchars($indiv['name']) ?> 
                        <span class="text-gold">(<?= $indiv['duration'] ?>m)</span>
                    </label>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="mt-2 d-flex justify-content-between align-items-center">
        <small class="text-muted italic" style="font-size: 0.75rem;">* Select at least 2 services</small>
        <span class="badge bg-secondary" id="selectionCount">0 selected</span>
    </div>
</div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="small text-muted mb-1">Duration (Mins)</label>
                            <input type="number" name="duration" id="duration" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small text-muted mb-1">Price (₱)</label>
                            <input type="number" step="0.01" name="price" id="price" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="small text-muted mb-1">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" name="save_treatment" class="btn btn-blue w-100 py-2">Save Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const serviceModal = new bootstrap.Modal(document.getElementById('serviceModal'));

function openModal(serviceType) {
    document.getElementById('serviceForm').reset();
    document.getElementById('treatment_id').value = '';
    document.getElementById('type').value = serviceType;
    document.getElementById('modalTitle').innerText = serviceType === 'package' ? 'Build New Package' : 'Add New Treatment';
    
    const packageArea = document.getElementById('packageSelectionArea');
    packageArea.style.display = (serviceType === 'package') ? 'block' : 'none';
    
    serviceModal.show();
}

function editService(data, contents = []) {
    document.getElementById('serviceForm').reset();
    document.getElementById('treatment_id').value = data.treatment_id;
    document.getElementById('type').value = data.type;
    document.getElementById('name').value = data.name;
    document.getElementById('duration').value = data.duration;
    document.getElementById('price').value = data.price;
    document.getElementById('description').value = data.description;
    
    const packageArea = document.getElementById('packageSelectionArea');
    if(data.type === 'package') {
        packageArea.style.display = 'block';
        document.getElementById('modalTitle').innerText = 'Edit Package';
        // Pre-check the treatments in this package
        contents.forEach(t_id => {
            let cb = document.getElementById('treat_' + t_id);
            if(cb) cb.checked = true;
        });
    } else {
        packageArea.style.display = 'none';
        document.getElementById('modalTitle').innerText = 'Edit Treatment';
    }
    
    serviceModal.show();
}

document.getElementById('serviceForm').onsubmit = function(e) {
    const type = document.getElementById('type').value;
    if (type === 'package') {
        const checked = document.querySelectorAll('input[name="package_contents[]"]:checked');
        if (checked.length < 2) {
            alert("A package must include at least 2 different treatments.");
            e.preventDefault();
            return false;
        }
    }
};
</script>
</body>
</html>