<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}

// Handle Room Save (Add/Edit)
if (isset($_POST['save_room'])) {
    header('Content-Type: application/json');
    try {

        $room_name = $_POST['room_name'];
        $room_type = $_POST['room_type'];
        $fee = $_POST['additional_fee'];
        $id = $_POST['room_id'] ?? '';

        if (!empty($id)) {

            $stmt = $pdo->prepare("UPDATE rooms SET room_name=?, room_type=?, additional_fee=? WHERE room_id=?");
            $stmt->execute([$room_name, $room_type, $fee, $id]);

            echo json_encode([
                "status" => "success",
                "message" => "Room updated successfully"
            ]);
            exit();

        } else {

            $stmt = $pdo->prepare("INSERT INTO rooms (room_name, room_type, additional_fee) VALUES (?, ?, ?)");
            $stmt->execute([$room_name, $room_type, $fee]);

            echo json_encode([
                "status" => "success",
                "message" => "Room added successfully"
            ]);
            exit();
        }

    } catch (Exception $e) {

        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
        exit();
    }
}

if (isset($_POST['archive_room_id'])) {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->prepare("UPDATE rooms SET status = 'archived' WHERE room_id = ?");
        $stmt->execute([$_POST['archive_room_id']]);

        echo json_encode([
            "status" => "success",
            "message" => "Room archived successfully"
        ]);
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
    exit; // prevent HTML output
}


$rooms = $pdo->query("SELECT * FROM rooms WHERE status = 'active' ORDER BY room_type ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rooms - Lumiére and Bliss</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-width: 260px; --accent-gold: #C5A059; --dark-bg: #1a1a1a; }
        body { background-color: #f4f6f9; }
        .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; background: var(--dark-bg); color: white; z-index: 1000; transition: 0.3s; }
        .nav-link { color: rgba(255,255,255,0.6); padding: 15px 25px; display: flex; align-items: center; gap: 12px; transition: 0.2s; text-decoration: none; }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.05); border-left: 4px solid var(--accent-gold); }
        .main-content { margin-left: var(--sidebar-width); padding: 40px; transition: 0.3s; }
        .room-card { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }
        
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Spa Rooms</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-dark rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#roomModal">
                <i class="bi bi-plus-lg me-2"></i> Add New Room
            </button>
            <button class="btn btn-outline-dark d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('active')">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </div>

    <div class="room-card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr class="text-muted small">
                        <th>ROOM NAME</th>
                        <th>TYPE</th>
                        <th>ADDITIONAL FEE</th>
                        <th>STATUS</th>
                        <th class="text-end">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rooms as $r): ?>
                    <tr>
                        <td class="fw-bold"><?= $r['room_name'] ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $r['room_type'] ?></span></td>
                        <td>₱<?= number_format($r['additional_fee'], 2) ?></td>
                        <td><span class="badge bg-success-subtle text-success rounded-pill">Active</span></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-light rounded-circle" onclick='editRoom(<?= json_encode($r) ?>)'><i class="bi bi-pencil"></i></button>
                            <form action="" method="POST" class="d-inline">
                                <input type="hidden" name="archive_room_id" value="<?= $r['room_id'] ?>">
                                <button type="button" 
                                        class="btn btn-sm btn-light rounded-circle text-danger" 
                                        onclick="archiveRoom(<?= $r['room_id'] ?>)">
                                    <i class="bi bi-archive"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Room Modal -->
<div class="modal fade" id="roomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="" method="POST" id="roomForm">
                <div class="modal-header border-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold" id="roomModalTitle">Add New Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4">
                    <input type="hidden" name="room_id" id="room_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Room Name</label>
                        <input type="text" name="room_name" id="room_name" class="form-control" placeholder="e.g. Serenity Suite 1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Room Type</label>
                        <select name="room_type" id="room_type" class="form-select" required>
                            <option value="Standard Room">Standard Room</option>
                            <option value="Couple Room">Couple Room</option>
                            <option value="Private Room">Private Room</option>
                            <option value="Premium Suite">Premium Suite</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Additional Fee (₱)</label>
                        <input type="number" step="0.01" name="additional_fee" id="additional_fee" class="form-control" value="0.00" required>
                        <div class="form-text small text-muted">Fee charged to non-members or for premium upgrades.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" onclick="saveRoom()" class="btn btn-dark rounded-pill px-4">
                        Save Room
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editRoom(data) {
    document.getElementById('roomModalTitle').innerText = "Edit Room Details";
    document.getElementById('room_id').value = data.room_id;
    document.getElementById('room_name').value = data.room_name;
    document.getElementById('room_type').value = data.room_type;
    document.getElementById('additional_fee').value = data.additional_fee;
    
    var myModal = new bootstrap.Modal(document.getElementById('roomModal'));
    myModal.show();
}

document.getElementById('roomModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('roomForm').reset();
    document.getElementById('room_id').value = "";
    document.getElementById('roomModalTitle').innerText = "Add New Room";
});

function saveRoom() {
    const form = document.getElementById('roomForm');
    const formData = new FormData(form);
    formData.append('save_room', '1');
    fetch(window.location.pathname,  {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {

        if (data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: data.message,
                timer: 1500,
                showConfirmButton: false
            });

            // close modal
            bootstrap.Modal.getInstance(document.getElementById('roomModal')).hide();

            // optional reload table or page
            setTimeout(() => location.reload(), 1000);

        } else {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: data.message
            });
        }

    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Server Error',
            text: 'Something went wrong!'
        });
        console.error(error);
    });
}

function archiveRoom(roomId) {
    Swal.fire({
        title: 'Archive this room?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, archive it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('archive_room_id', roomId);

            fetch('manage_room.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Archived!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'Something went wrong!'
                });
                console.error(error);
            });
        }
    });
}

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>