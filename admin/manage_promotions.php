<?php
session_start();
require_once '../config/db.php';

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
            $stmt->execute([$promo_name, $tagline, $included_service, $duration_minutes, $original_price, $price_now, $valid_dates, $id]);
            echo json_encode(["status" => "success", "message" => "Promotion updated successfully"]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO promotions (promo_name, tagline, included_service, duration_minutes, original_price, price_now, valid_dates)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$promo_name, $tagline, $included_service, $duration_minutes, $original_price, $price_now, $valid_dates]);
            echo json_encode(["status" => "success", "message" => "Promotion added successfully"]);
        }
        exit();
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit();
    }
}

/* ================= DELETE PROMOTION ================= */
if (isset($_POST['delete_id'])) {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->prepare("DELETE FROM promotions WHERE promo_id = ?");
        $stmt->execute([$_POST['delete_id']]);
        echo json_encode(["status" => "success", "message" => "Promotion deleted successfully"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit();
}

/* ================= FETCH DATA ================= */
$promotions = $pdo->query("SELECT * FROM promotions ORDER BY promo_id DESC")->fetchAll(PDO::FETCH_ASSOC);
if (!$promotions) $promotions = [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Promotions - Lumiére and Bliss</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-width: 260px; --accent-gold: #C5A059; --dark-bg: #1a1a1a; }
        body { background-color: #f4f6f9; }
        .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; background: var(--dark-bg); color: white; z-index: 1000; transition: 0.3s; }
        .nav-link { color: rgba(255,255,255,0.6); padding: 15px 25px; display: flex; align-items: center; gap: 12px; transition: 0.2s; text-decoration: none; }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.05); border-left: 4px solid var(--accent-gold); }
        .main-content { margin-left: var(--sidebar-width); padding: 40px; transition: 0.3s; }
        .promo-card { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .badge-active   { background: #d1fae5; color: #065f46; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
        .promo-type-badge { font-size: 0.7rem; padding: 2px 8px; border-radius: 20px; background: #f3f4f6; color: #374151; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<nav class="sidebar" id="sidebar">
    <div class="p-4 mb-4">
        <h4 class="fw-bold mb-0 text-white">L&B <span style="color: var(--accent-gold);">Admin</span></h4>
    </div>
    <div class="nav flex-column">
        <a href="dashboard.php" class="nav-link"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
        <a href="manage_appointment.php" class="nav-link"><i class="bi bi-calendar-event"></i> Appointments</a>
        <a href="manage_treatments.php" class="nav-link"><i class="bi bi-droplet-half"></i> Treatments</a>
        <a href="manage_promotions.php" class="nav-link active"><i class="bi bi-tag"></i> Promotions</a>
        <a href="manage_therapist.php" class="nav-link"><i class="bi bi-person-badge"></i> Therapists</a>
        <a href="manage_room.php" class="nav-link"><i class="bi bi-door-open"></i> Rooms</a>
        <a href="manage_account.php" class="nav-link"><i class="bi bi-people"></i> Accounts</a>
        <a href="system_logs.php" class="nav-link"><i class="bi bi-shield-lock"></i> Logs</a>
        <a href="logout.php" class="nav-link text-danger mt-5"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
</nav>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Promotions</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-dark rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#promoModal" onclick="resetModal()">
                <i class="bi bi-plus-lg me-2"></i> Add New Promotion
            </button>
            <button class="btn btn-outline-dark d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('active')">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </div>

    <div class="promo-card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-muted small">
                        <th>NAME</th>
                        <th>TAGLINE</th>
                        <th>INCLUDED SERVICE</th>
                        <th>DURATION</th>
                        <th>ORIGINAL</th>
                        <th>PROMO PRICE</th>
                        <th>VALID DATES</th>
                        <th class="text-end">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($promotions as $p): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($p['promo_name']) ?></td>
                        <td class="text-muted small" style="max-width:180px;"><?= htmlspecialchars($p['tagline']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['included_service']) ?></span></td>
                        <td><?= htmlspecialchars($p['duration_minutes']) ?> mins</td>
                        <td class="text-decoration-line-through text-muted small">₱<?= number_format($p['original_price'], 2) ?></td>
                        <td>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3">
                                ₱<?= number_format($p['price_now'], 2) ?>
                            </span>
                        </td>
                        <td class="small"><?= htmlspecialchars($p['valid_dates']) ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-light rounded-circle" onclick='editPromo(<?= json_encode($p) ?>)'>
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-light rounded-circle text-danger" onclick="deletePromo(<?= $p['promo_id'] ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($promotions)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No promotions yet. Add one to get started.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Promotion Modal -->
<div class="modal fade" id="promoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 px-4 pt-4">
                <h5 class="modal-title fw-bold" id="promoModalTitle">Add New Promotion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4">
                <input type="hidden" id="promo_id">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small fw-bold">Promotion Name</label>
                        <input type="text" id="promo_name" class="form-control" placeholder="e.g. Birthday Bliss Celebration">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Tagline</label>
                        <input type="text" id="tagline" class="form-control" placeholder="e.g. A luxurious gift of restoration for your special day.">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Included Service</label>
                        <textarea id="included_service" class="form-control" rows="3" placeholder="e.g. Signature Full Body Massage + Himalayan Salt Scrub + complimentary herbal tea"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Duration (minutes)</label>
                        <input type="number" id="duration_minutes" class="form-control" placeholder="e.g. 90">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Original Price (₱)</label>
                        <input type="number" step="0.01" id="original_price" class="form-control" placeholder="e.g. 2150.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Promo Price (₱)</label>
                        <input type="number" step="0.01" id="price_now" class="form-control" placeholder="e.g. 1850.00">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Valid Dates</label>
                        <input type="text" id="valid_dates" class="form-control" placeholder="e.g. May 1 – June 30, 2026">
                        <div class="form-text small text-muted mt-1">
                            <i class="bi bi-info-circle me-1"></i>This promotion will be visible to customers on the promotions page.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" onclick="savePromo()" class="btn btn-dark rounded-pill px-4">Save Promotion</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function resetModal() {
    document.getElementById('promo_id').value         = '';
    document.getElementById('promo_name').value       = '';
    document.getElementById('tagline').value          = '';
    document.getElementById('included_service').value = '';
    document.getElementById('duration_minutes').value = '';
    document.getElementById('original_price').value   = '';
    document.getElementById('price_now').value        = '';
    document.getElementById('valid_dates').value      = '';
    document.getElementById('promoModalTitle').innerText = 'Add New Promotion';
}

document.getElementById('promoModal').addEventListener('hidden.bs.modal', resetModal);

function editPromo(p) {
    document.getElementById('promoModalTitle').innerText  = 'Edit Promotion';
    document.getElementById('promo_id').value             = p.promo_id;
    document.getElementById('promo_name').value           = p.promo_name;
    document.getElementById('tagline').value              = p.tagline;
    document.getElementById('included_service').value     = p.included_service;
    document.getElementById('duration_minutes').value     = p.duration_minutes;
    document.getElementById('original_price').value       = p.original_price;
    document.getElementById('price_now').value            = p.price_now;
    document.getElementById('valid_dates').value          = p.valid_dates;
    new bootstrap.Modal(document.getElementById('promoModal')).show();
}

function savePromo() {
    const fd = new FormData();
    fd.append('save_promotion',   '1');
    fd.append('promo_id',         document.getElementById('promo_id').value);
    fd.append('promo_name',       document.getElementById('promo_name').value);
    fd.append('tagline',          document.getElementById('tagline').value);
    fd.append('included_service', document.getElementById('included_service').value);
    fd.append('duration_minutes', document.getElementById('duration_minutes').value);
    fd.append('original_price',   document.getElementById('original_price').value);
    fd.append('price_now',        document.getElementById('price_now').value);
    fd.append('valid_dates',      document.getElementById('valid_dates').value);

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

function deletePromo(id) {
    Swal.fire({
        title: 'Delete this promotion?',
        text: 'This cannot be undone and will no longer be visible to customers.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545'
    }).then(result => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('delete_id', id);
            fetch(window.location.pathname, { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Deleted!', text: data.message, timer: 1500, showConfirmButton: false });
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