<?php
session_start();
require_once '../config/db.php';

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
    }

    if (!empty($name)) {
        try {
            if (empty($id)) {
                $stmt = $pdo->prepare("INSERT INTO treatments (name, description, duration_minutes, price, type, image, status) VALUES (?, ?, ?, ?, ?, ?, 'available')");
                $stmt->execute([$name, $description, $duration, $price, $type, $image_name]);
                $id = $pdo->lastInsertId();

                if ($type === 'package') {
                    $pdo->prepare("INSERT INTO packages (package_id, name, price) VALUES (?, ?, ?)")->execute([$id, $name, $price]);
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
            }
            
            header("Location: manage_treatments.php?msg=success");
            exit();
        } catch (PDOException $e) {
            die("Save Error: " . $e->getMessage());
        }
    }
}

$individual = $pdo->query("SELECT * FROM treatments WHERE status = 'available' AND type = 'individual' ORDER BY name ASC")->fetchAll();
$packages = $pdo->query("SELECT * FROM treatments WHERE status = 'available' AND type = 'package' ORDER BY name ASC")->fetchAll();

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

   
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }

        .treatment-selectable-card {
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid transparent !important;
}

.treatment-selectable-card:hover {
    background-color: #f8f9fa !important;
    border-color: #dee2e6 !important;
}

.treatment-selectable-card.selected {
    border-color: var(--accent-gold) !important;
    background-color: #fffdf9 !important;
}

.treatment-selectable-card.selected .selected-check {
    display: block !important;
}
.treatment-selectable-card { cursor: pointer; transition: 0.2s; border: 2px solid #dee2e6 !important; }
.treatment-selectable-card:hover { border-color: #C5A059 !important; background: #fffcf5; }
.treatment-selectable-card.selected { border-color: #28a745 !important; background: #f8fff9; }
.treatment-selectable-card.selected .selected-icon { display: block !important; }
    </style>
</head>
<body>


<nav class="sidebar" id="sidebar">
    <div class="p-4 mb-4">
        <h4 class="fw-bold mb-0 text-white">L&B <span style="color: var(--accent-gold);">Admin</span></h4>
    </div>
    <div class="nav flex-column">
        <a href="dashboard.php" class="nav-link active"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="manage_appointment.php" class="nav-link"><i class="bi bi-calendar-event"></i> Appointments</a>
        
     
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
    <button class="btn btn-outline-dark px-4" onclick="openModal('individual')">
    <i class="bi bi-plus-lg me-2"></i> New Treatment
</button>
<button class="btn btn-dark px-4" onclick="openModal('package')">
    <i class="bi bi-box-seam me-2"></i> New Package
</button>
</div>
    </div>

   
    <h5 class="mb-3 opacity-75">Individual Treatments</h5>
    <div class="card p-3">
        <table class="table align-middle">
            <thead>
                <tr><th>Treatment Name</th><th>Duration</th><th>Price</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
    <?php foreach($individual as $indiv): ?>
    <tr>
        <td class="fw-bold text-dark"><?= htmlspecialchars($indiv['name']) ?></td>
        <td><span class="badge bg-light text-dark border"><?= $indiv['duration_minutes'] ?> mins</span></td>
        <td class="fw-bold text-primary">₱<?= number_format($indiv['price'], 2) ?></td>
        <td class="text-end px-4">
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-sm btn-outline-primary rounded-circle" 
                        onclick='editService(<?= json_encode($indiv) ?>)'>
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger rounded-circle" 
                        onclick="confirmDelete(<?= $indiv['treatment_id'] ?>)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
            </tbody>
        </table>
    </div>


    <div class="mt-4" id="packages-section">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold m-0"><i class="bi bi-box-seam me-2"></i>Exclusive Packages</h5>
    </div>
    <div class="table-responsive bg-white rounded shadow-sm border p-3">
        <table class="table align-middle m-0">
            <thead>
                <tr class="text-muted small">
                    <th>PACKAGE NAME</th>
                    <th>DURATION</th>
                    <th>PRICE</th>
                    <th class="text-end px-4">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($packages)): ?>
                    <tr><td colspan="4" class="text-center py-4 text-muted">No packages found.</td></tr>
                <?php else: ?>
                    <?php foreach($packages as $p): ?>
<tr>
    <td class="fw-bold text-dark"><?= htmlspecialchars($p['name']) ?></td>
    <td><span class="badge bg-light text-dark border"><?= $p['duration_minutes'] ?> mins</span></td>
    <td class="fw-bold text-primary">₱<?= number_format($p['price'], 2) ?></td>
    <td class="text-end px-4">
        <div class="d-flex justify-content-end gap-2">
            <button class="btn btn-sm btn-outline-primary rounded-circle" 
                    onclick='editService(<?= json_encode($p) ?>, <?= json_encode($package_map[$p['treatment_id']] ?? []) ?>)'>
                <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger rounded-circle" 
                    onclick="confirmDelete(<?= $p['treatment_id'] ?>)">
                <i class="bi bi-trash"></i>
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

<div class="modal fade" id="serviceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form action="" method="POST" id="serviceForm" enctype="multipart/form-data">
                <div class="modal-body px-4">
                    <input type="hidden" name="treatment_id" id="treatment_id">
                    <input type="hidden" name="type" id="type">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="small fw-bold text-muted mb-1">Service Name</label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>

                        <div id="packageSelectionArea" style="display: none;" class="col-12">
    <div class="p-3 rounded border bg-light">
        <label class="small fw-bold mb-3 d-block text-dark">Select Individual Treatments to Bundle:</label>
        
       <div class="row g-2" style="max-height: 400px; overflow-y: auto;" id="treatmentGrid">
    <?php foreach($individual as $indiv): ?>
        <div class="col-6">
            <div class="treatment-selectable-card p-2 border rounded bg-white h-100 position-relative" 
                 id="card_<?= $indiv['treatment_id'] ?>"
                 onclick="toggleTreatmentSelection(this, 'treat_<?= $indiv['treatment_id'] ?>')">
                
                <input class="package-checkbox d-none" type="checkbox" 
                       name="package_contents[]" 
                       value="<?= $indiv['treatment_id'] ?>" 
                       id="treat_<?= $indiv['treatment_id'] ?>" 
                       onchange="updateSelectionCount()">
                
                <div class="d-flex align-items-center gap-2">
                    <img src="../assets/img/treatments/<?= !empty($indiv['image']) ? $indiv['image'] : 'default.jpg' ?>" 
                         class="rounded" style="width: 45px; height: 45px; object-fit: cover;">
                    <div style="line-height: 1.2;">
                        <span class="fw-bold small text-dark d-block"><?= htmlspecialchars($indiv['name']) ?></span>
                        <small class="text-muted"><?= $indiv['duration_minutes'] ?>m | ₱<?= number_format($indiv['price'], 2) ?></small>
                    </div>
                </div>

                <div class="selected-icon position-absolute top-0 end-0 p-1 d-none">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 0.8rem;"></i>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

        <div class="mt-3 d-flex justify-content-between align-items-center">
            <small class="text-muted" style="font-size: 0.75rem;">Select 2 Treatments</small>
            <span class="badge bg-secondary" id="selectionCount">0 selected</span>
        </div>
    </div>
</div>

                        <div class="col-6">
    <label class="small fw-bold text-muted mb-1">Duration (Mins)</label>
    <input type="number" name="duration" id="duration" class="form-control" required>
</div>
<div class="col-6">
    <label class="small fw-bold text-muted mb-1">Price (₱)</label>
    <input type="number" step="0.01" name="price" id="price" class="form-control" required>
</div>
<div class="col-12" id="imageUploadArea">
    <label class="small fw-bold text-muted mb-1">Service Image</label>
    <div id="currentImagePreview" class="mb-2 d-none">
        <p class="small text-muted mb-1">Current Image:</p>
        <img id="modalImageDisplay" src="" class="rounded border" style="width: 100px; height: 100px; object-fit: cover;">
    </div>
    <input type="file" name="service_image" class="form-control" accept="image/*">
</div>
<div class="col-12">
    <label class="small fw-bold text-muted mb-1">Description</label>
    <textarea name="description" id="description" class="form-control" rows="3"></textarea>
</div>
                        
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_treatment" class="btn btn-dark rounded-pill px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updateSelectionCount() {
    const checkboxes = document.querySelectorAll('.package-checkbox:checked');
    const checkedCount = checkboxes.length;
    const counterDisplay = document.getElementById('selectionCount');
    const durationInput = document.getElementById('duration');
    const serviceType = document.getElementById('type').value;

    counterDisplay.innerText = `${checkedCount} selected`;
    
    
    if (checkedCount === 2) {
        counterDisplay.className = "badge bg-success";
    } else {
        counterDisplay.className = "badge bg-secondary";
    }

    if (serviceType === 'package') {
        let totalDuration = 0;
        checkboxes.forEach(cb => {
            const card = cb.closest('.treatment-selectable-card');
            const text = card.innerText;
            
            const matches = text.match(/(\d+)m/);
            if (matches) {
                totalDuration += parseInt(matches[1]);
            }
        });
        durationInput.value = totalDuration;
    }
}
function toggleTreatmentSelection(card, checkboxId) {
    const checkbox = document.getElementById(checkboxId);
    const checkedCount = document.querySelectorAll('.package-checkbox:checked').length;

   
    if (!checkbox.checked && checkedCount >= 2) {
        alert("A package must consist of exactly 2 treatments.");
        return;
    }


    checkbox.checked = !checkbox.checked;
    
    
    if (checkbox.checked) {
        card.classList.add('selected');
    } else {
        card.classList.remove('selected');
    }
    
    updateSelectionCount();
}


function openModal(serviceType) {
    const form = document.getElementById('serviceForm');
    form.reset();
    
   
    document.querySelectorAll('.treatment-selectable-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    document.getElementById('treatment_id').value = '';
    document.getElementById('type').value = serviceType;
    document.getElementById('modalTitle').innerText = serviceType === 'package' ? 'Build New Package' : 'Add New Treatment';
    
    const packageArea = document.getElementById('packageSelectionArea');
    const durationInput = document.getElementById('duration');
    
    if (serviceType === 'package') {
        packageArea.style.display = 'block';
        durationInput.readOnly = true;
        durationInput.classList.add('bg-light');
    } else {
        packageArea.style.display = 'none';
        durationInput.readOnly = false;
        durationInput.classList.remove('bg-light');
    }
    
    var serviceModal = new bootstrap.Modal(document.getElementById('serviceModal'));
    serviceModal.show();
}

function editService(data, contents = []) {
    const durationInput = document.getElementById('duration');
    const packageArea = document.getElementById('packageSelectionArea');
    const imageArea = document.getElementById('imageUploadArea');
    const previewArea = document.getElementById('currentImagePreview');
    const modalImage = document.getElementById('modalImageDisplay');
    
    document.getElementById('serviceForm').reset();
    document.getElementById('treatment_id').value = data.treatment_id;
    document.getElementById('type').value = data.type;
    document.getElementById('name').value = data.name;
    document.getElementById('duration').value = data.duration_minutes;
    document.getElementById('price').value = data.price;
    document.getElementById('description').value = data.description;
    
    if(data.type === 'package') {
        packageArea.style.display = 'block';
        imageArea.style.display = 'none'; 
        document.getElementById('modalTitle').innerText = 'Edit Package';
        durationInput.readOnly = true;
        durationInput.classList.add('bg-light');
        
        contents.forEach(t_id => {
            let cb = document.getElementById('treat_' + t_id);
            if(cb) {
                cb.checked = true;
                cb.closest('.treatment-selectable-card').classList.add('selected');
            }
        });
        updateSelectionCount();
    } else {
        packageArea.style.display = 'none';
        imageArea.style.display = 'block'; 
        document.getElementById('modalTitle').innerText = 'Edit Treatment';
        durationInput.readOnly = false;
        durationInput.classList.remove('bg-light');

      
        if (data.image && data.image !== 'default.jpg') {
            modalImage.src = "../assets/img/treatments/" + data.image;
            previewArea.classList.remove('d-none');
        } else {
            previewArea.classList.add('d-none');
        }
    }
    
    var serviceModal = new bootstrap.Modal(document.getElementById('serviceModal'));
    serviceModal.show();
}
function confirmDelete(id) {
    
    document.getElementById('confirmDeleteId').value = id;
    
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    deleteModal.show();

}

document.getElementById('serviceForm').onsubmit = function(e) {
    const type = document.getElementById('type').value;
    if (type === 'package') {
        const checkedCount = document.querySelectorAll('.package-checkbox:checked').length;
        if (checkedCount !== 2) {
            alert("Please select exactly 2 treatments for this package.");
            e.preventDefault();
            return false;
        }
    }
};

</script>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <div class="text-danger mb-3">
                    <i class="bi bi-exclamation-octagon" style="font-size: 3rem;"></i>
                </div>
                <h5 class="fw-bold">Remove Service?</h5>
                <p class="text-muted small">This action will permanently delete this item.</p>
                
                <form action="manage_treatments.php" method="POST">
                    <input type="hidden" name="delete_id" id="confirmDeleteId">
                    <div class="d-flex gap-2 mt-3">
                        <button type="button" class="btn btn-light w-100 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_treatment" class="btn btn-danger w-100 rounded-pill">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>