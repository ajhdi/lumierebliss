<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: signin_admin.php");
    exit();
}



if (isset($_POST['save_therapist'])) {
    $id = $_POST['therapist_id'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    $gender = $_POST['gender'] ?? ''; 
    $specialty = $_POST['specialty'] ?? '';
    $experience = $_POST['work_experience'] ?? '';
    $status = $_POST['status'];


    $params = [$first_name, $middle_name, $last_name, $gender, $specialty, $experience, $status];
    $img_sql = "";
    
    
    if (!empty($_FILES['profile_pic']['name'])) {
        $image_name = time() . '_' . $_FILES['profile_pic']['name'];
        $target_dir = "../assets/img/therapists/";
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_dir . $image_name)) {
            $img_sql = ", profile_picture = ?";
            $params[] = $image_name;
        }
    }

    
    $params[] = $id; 
    $sql = "UPDATE therapists SET first_name=?, middle_name=?, last_name=?, gender=?, specialty=?, work_experience=?, status=? $img_sql WHERE therapist_id=?";
    
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);


    $pdo->prepare("DELETE FROM therapist_schedule WHERE therapist_id = ?")->execute([$id]);
    
    if ($status === 'active' && isset($_POST['schedule_times'])) {
        
        $unique_times = array_unique(array_filter($_POST['schedule_times']));
        
      
        $sched_stmt = $pdo->prepare("INSERT INTO therapist_schedule (therapist_id, time_start) VALUES (?, ?)");
        
        foreach ($unique_times as $time) {
            $sched_stmt->execute([$id, $time]);
        }
    }

    header("Location: manage_therapist.php?msg=updated");
    exit();
}

$stmt = $pdo->query("SELECT * FROM therapists ORDER BY last_name ASC");
$therapists = $stmt->fetchAll();


$sched_stmt = $pdo->query("SELECT therapist_id, time_start FROM therapist_schedule ORDER BY time_start ASC");
$all_schedules = $sched_stmt->fetchAll(PDO::FETCH_GROUP);
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Therapists - Lumiére and Bliss</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-width: 260px; --accent-gold: #C5A059; --dark-bg: #1a1a1a; }
        body { background-color: #f4f6f9; }
        .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; background: var(--dark-bg); color: white; z-index: 1000; transition: 0.3s; }
        .nav-link { color: rgba(255,255,255,0.6); padding: 15px 25px; display: flex; align-items: center; gap: 12px; transition: 0.2s; text-decoration: none; }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.05); border-left: 4px solid var(--accent-gold); }
        .main-content { margin-left: var(--sidebar-width); padding: 40px; transition: 0.3s; }
        .therapist-card { background: white; border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
        }/* Fix for the ghost scrollbar */
.table-responsive {
    overflow-x: hidden; /* Hide horizontal overflow on desktop */
}

@media (max-width: 991px) {
    .table-responsive {
        overflow-x: auto; /* Re-enable scrolling only on mobile/tablets */
    }
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
        <a href="manage_cosmetics.php" class="nav-link"><i class="bi bi-droplet-half"></i> Cosmetics</a>
        <a href="manage_therapist.php" class="nav-link"><i class="bi bi-person-badge"></i> Therapists</a>
        <a href="manage_room.php" class="nav-link"><i class="bi bi-door-open"></i> Rooms</a>
        <a href="manage_account.php" class="nav-link"><i class="bi bi-people"></i> Accounts</a>
        <a href="system_logs.php" class="nav-link"><i class="bi bi-shield-lock"></i> Logs</a>
        <a href="logout.php" class="nav-link text-danger mt-5"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
</nav>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Therapist Profiles</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-dark d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('active')">
                <i class="bi bi-list"></i>
            </button>
        </div>
    </div>

    <div class="therapist-card p-4">
        <div class="table-responsive">
            <div class="d-flex justify-content-between align-items-center mb-4">
    <div class="col-md-5">
        <div class="input-group shadow-sm rounded-3">
            <span class="input-group-text bg-white border-end-0">
                <i class="bi bi-search text-muted"></i>
            </span>
            <input type="text" id="therapistSearch" class="form-control border-start-0 ps-0" placeholder="Search by name, specialty, or status...">
        </div>
    </div>
    
    <div class="col-md-3">
        <select id="genderFilter" class="form-select shadow-sm rounded-3">
            <option value="All">All Genders</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select>
    </div>
</div>
            <table id="therapistTable" class="table table-hover align-middle">
                <thead>
    <tr class="text-muted small">
        <th>NAME</th>
        <th>SPECIALTY</th>
        <th>GENDER</th>
        <th class="text-center">STATUS</th> <th class="text-center">ACTIONS</th> </tr>
</thead>
<tbody>
    <?php foreach ($therapists as $t): ?>
    <tr>
        <td class="py-3">
            <div class="d-flex align-items-center gap-3">
                <?php 
                    $photo = (!empty($t['profile_picture'])) ? $t['profile_picture'] : 'default_therapist.png';
                ?>
                <img src="../assets/img/therapists/<?= $photo ?>" 
                     class="rounded-circle border" style="width: 45px; height: 45px; object-fit: cover;">
                <div>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($t['first_name'] . ' ' . $t['last_name']) ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($t['specialty']) ?></div>
                </div>
            </div>
        </td>
        <td><?= htmlspecialchars($t['specialty']) ?></td>
        <td><?= htmlspecialchars($t['gender']) ?></td>
        <td class="text-center">
            <?php if($t['status'] == 'active'): ?>
                <span class="badge bg-success-subtle text-success rounded-pill border border-success-subtle px-3">Active</span>
            <?php else: ?>
                <span class="badge bg-danger-subtle text-danger rounded-pill border border-danger-subtle px-3">Inactive</span>
            <?php endif; ?>
        </td>
        <td class="text-center">
            <button class="btn btn-sm btn-light rounded-circle border shadow-sm" 
        onclick='editTherapist(<?= json_encode(array_merge($t, ["schedules" => array_column($all_schedules[$t['therapist_id']] ?? [], "time_start")])) ?>)'>
    <i class="bi bi-pencil-square text-primary"></i>
</button>
        </td>
    </tr>
    <?php endforeach; ?>
    <tr id="noResultsRow" class="d-none">
    <td colspan="5" class="text-center py-5 text-muted">
        <i class="bi bi-person-exclamation d-block mb-2" style="font-size: 2rem;"></i>
        No therapists found matching your search.
    </td>
</tr>
</tbody>
            </table>
        </div>
    </div>
</div>

 <div class="modal fade" id="therapistModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            
            <form action="manage_therapist.php" method="POST" enctype="multipart/form-data" id="therapistForm">
                <div class="modal-header border-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold" id="modalTitle">Therapist Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4">
                   

                    <input type="hidden" name="therapist_id" id="therapist_id">
                    
                    <div class="text-center mb-4">
                        <img id="profilePreview" src="../assets/img/therapists/default_therapist.png" 
                             class="rounded-circle border shadow-sm" style="width: 120px; height: 120px; object-fit: cover;">
                        <div class="mt-2">
                            <label class="btn btn-sm btn-outline-primary rounded-pill">
                                Change Photo <input type="file" name="profile_pic" class="d-none" onchange="previewImage(this)">
                            </label>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Username</label>
                            <input type="text" id="username" class="form-control bg-light" readonly>
                            <small class="text-muted">Username cannot be changed.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-primary">Account Status</label>
                            <select name="status" id="status" class="form-select" onchange="toggleScheduleDisability()">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">First Name</label>
                            <input type="text" name="first_name" id="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Middle Name</label>
                            <input type="text" name="middle_name" id="middle_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select name="gender" id="gender" class="form-select">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Specialty</label>
                            <input type="text" name="specialty" id="specialty" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Work Experience</label>
                            <textarea name="work_experience" id="work_experience" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="col-12 mt-4">
                        <h6 class="fw-bold border-bottom pb-2 mb-3">Daily Time Schedule</h6>
                        <p class="text-muted small mb-2">Select 4 unique time slots from available booking times.</p>
                        <div class="row g-2">
                            <?php 
                            $valid_slots = [
                                '09:00' => '9:00 AM',
                                '10:00' => '10:00 AM',
                                '11:00' => '11:00 AM',
                                '13:00' => '1:00 PM',
                                '14:00' => '2:00 PM',
                                '15:00' => '3:00 PM',
                                '16:00' => '4:00 PM',
                                '17:00' => '5:00 PM',
                            ];
                            for($i=0; $i<4; $i++): ?>
                            <div class="col-md-3">
                                <select name="schedule_times[]" class="form-select schedule-input" required>
                                    <option value="">-- Slot <?= $i+1 ?> --</option>
                                    <?php foreach($valid_slots as $val => $label): ?>
                                    <option value="<?= $val ?>"><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    </div> 
                </div> 
                <div id="validationError" class="d-none mb-3">
        <div class="p-3 border-start border-danger border-4 bg-light d-flex align-items-center rounded-end shadow-sm">
            <i class="bi bi-exclamation-circle-fill text-danger me-2"></i>
            <small id="errorText" class="text-dark fw-bold"></small>
        </div>
    </div>
                <div class="modal-footer border-0 px-4 pb-4">
                    <button type="submit" name="save_therapist" class="btn btn-dark rounded-pill px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

function editTherapist(therapist) {
    const errorBox = document.getElementById('validationError');
    if (errorBox) errorBox.classList.add('d-none');

    document.getElementById('therapistForm').reset();
    
    // Fill basic info
    document.getElementById('therapist_id').value = therapist.therapist_id;
    document.getElementById('first_name').value = therapist.first_name;
    document.getElementById('middle_name').value = therapist.middle_name;
    document.getElementById('last_name').value = therapist.last_name;
    document.getElementById('gender').value = therapist.gender;
    document.getElementById('specialty').value = therapist.specialty;
    document.getElementById('work_experience').value = therapist.work_experience;
    document.getElementById('status').value = therapist.status;
    document.getElementById('modalTitle').innerText = 'Edit Therapist';

    // Fix: Show the therapist's username (linked to their account)
    document.getElementById('username').value = therapist.username || 'N/A';

    // Fix: Update the Profile Preview image
    const photo = therapist.profile_picture ? '../assets/img/therapists/' + therapist.profile_picture : '../assets/img/therapists/default_therapist.png';
    document.getElementById('profilePreview').src = photo;

    // Fill schedule slots
    const times = therapist.schedules || [];
    const inputs = document.querySelectorAll('.schedule-input');
    inputs.forEach((input, index) => {
        input.value = times[index] ? times[index].substring(0, 5) : '';
    });

    toggleScheduleDisability(); // Ensure inputs are correctly enabled/disabled
    const therapistModal = new bootstrap.Modal(document.getElementById('therapistModal'));
    therapistModal.show();
}
document.getElementById('therapistForm').addEventListener('submit', function(e) {
    const errorBox = document.getElementById('validationError');
    const errorText = document.getElementById('errorText');
    
    // Hide error box initially
    if (errorBox) errorBox.classList.add('d-none');

    // Basic required check for schedule (if active)
    if (document.getElementById('status').value !== 'inactive') {
        const inputs = document.querySelectorAll('.schedule-input');
        let allFilled = true;
        const times = [];

        inputs.forEach(i => { 
            if(!i.value) allFilled = false;
            else times.push(i.value);
        });

        if (!allFilled) {
            e.preventDefault();
            showInlineError("All fields, including all 4 schedule slots, are required.");
            return;
        }

        // Duplicate check
                const uniqueTimes = new Set(times);
                if (uniqueTimes.size !== times.length) {
                    e.preventDefault();
                    showInlineError("Schedule Conflict: Each slot must be a different time.");
                    return;
                }
            }
        });

function showInlineError(message) {
    const errorBox = document.getElementById('validationError');
    const errorText = document.getElementById('errorText');
    if (errorBox && errorText) {
        errorText.innerText = message;
        errorBox.classList.remove('d-none');
        // Scroll modal to top to see error
        document.querySelector('.modal-body').scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function toggleScheduleDisability() {
    const status = document.getElementById('status').value;
    const inputs = document.querySelectorAll('.schedule-input');
    
    inputs.forEach(input => {
        if (status === 'inactive') {
            input.disabled = true;
            input.classList.add('bg-light');
        } else {
            input.disabled = false;
            input.classList.remove('bg-light');
        }
    });
}




function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Combined Filter Function for Search and Gender
function filterTherapists() {
    const searchText = document.getElementById('therapistSearch').value.toLowerCase();
    const genderValue = document.getElementById('genderFilter').value;
    const rows = document.querySelectorAll('tbody tr:not(#noResultsRow)');
    const noResultsRow = document.getElementById('noResultsRow');
    let visibleCount = 0;

    rows.forEach(row => {
        const rowText = row.textContent.toLowerCase();
        // Index 2 is the GENDER column
        const rowGender = row.cells[2].textContent.trim(); 

        const matchesSearch = rowText.includes(searchText);
        const matchesGender = (genderValue === "All" || rowGender === genderValue);

        if (matchesSearch && matchesGender) {
            row.style.display = "";
            visibleCount++;
        } else {
            row.style.display = "none";
        }
    });

    // Handle "No Results" message
    if (visibleCount === 0) {
        noResultsRow.classList.remove('d-none');
    } else {
        noResultsRow.classList.add('d-none');
    }
}

// Attach event listeners to both inputs
document.getElementById('therapistSearch').addEventListener('keyup', filterTherapists);
document.getElementById('genderFilter').addEventListener('change', filterTherapists);


</script>
</body>
</html>

