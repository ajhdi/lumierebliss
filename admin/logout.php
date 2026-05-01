<?php
require_once('../config/db.php');

// Security Check
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: signin_admin.php");
    exit();
}

$message = "";

// --- LOGIC: HANDLE ACCOUNT TYPE UPDATE (MEMBERSHIP) ---
if (isset($_POST['update_membership'])) {
    $u_id = $_POST['user_id'];
    $new_type = $_POST['account_type'];
    $stmt = $pdo->prepare("UPDATE users SET account_type = ? WHERE user_id = ?");
    if ($stmt->execute([$new_type, $u_id])) {
        $message = "User membership updated successfully!";
    }
}

// --- LOGIC: HANDLE THERAPIST ACCOUNT CREATION ---
if (isset($_POST['add_therapist_acc'])) {
    $fname = $_POST['fname'];
    $mname = $_POST['mname'];
    $lname = $_POST['lname'];
    $gender = $_POST['gender'];
    $specialty = $_POST['specialty'];
    $exp = $_POST['experience'];
    $uname = $_POST['username'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Handle Photo Upload
    $photo = "default_therapist.png";
    if (!empty($_FILES['photo']['name'])) {
        $photo = time() . "_" . $_FILES['photo']['name'];
        move_uploaded_file($_FILES['photo']['tmp_name'], "../assets/images/" . $photo);
    }

    try {
        $pdo->beginTransaction();
        // Insert into therapists table
        $stmt1 = $pdo->prepare("INSERT INTO therapists (name, specialty, photo) VALUES (?, ?, ?)");
        $stmt1->execute(["$fname $lname", $specialty, $photo]);
        $t_id = $pdo->lastInsertId();

        // Insert into therapist_accounts
        $stmt2 = $pdo->prepare("INSERT INTO therapist_accounts (therapist_id, username, password) VALUES (?, ?, ?)");
        $stmt2->execute([$t_id, $uname, $pass]);

        $pdo->commit();
        $message = "Therapist account created!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}

// Fetch Users and Therapists
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
$therapists = $pdo->query("SELECT t.*, ta.username FROM therapists t JOIN therapist_accounts ta ON t.therapist_id = ta.therapist_id WHERE t.status = 'active'")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts | Lumiére and Bliss</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary: #d4af37; --dark: #1a1a1a; --light: #f8f9fa; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); margin: 0; display: flex; }
        
        /* Shared Sidebar Style (Keep consistent across admin files) */
        .sidebar { width: 250px; background: var(--dark); color: white; min-height: 100vh; position: fixed; }
        .sidebar h3 { text-align: center; color: var(--primary); padding: 20px 0; border-bottom: 1px solid #333; }
        .sidebar a { display: block; color: #ccc; padding: 15px 25px; text-decoration: none; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #2a2a2a; color: var(--primary); border-left: 4px solid var(--primary); }
        
        .main-content { margin-left: 250px; width: calc(100% - 250px); padding: 30px; }
        
        /* Tabs */
        .tab-container { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #ddd; }
        .tab-btn { padding: 10px 20px; cursor: pointer; border: none; background: none; font-weight: bold; color: #666; }
        .tab-btn.active { color: var(--primary); border-bottom: 3px solid var(--primary); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* Tables & UI */
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
        th { background: #fdfdfd; color: #555; }
        
        .btn { padding: 8px 15px; border-radius: 4px; border: none; cursor: pointer; font-size: 0.85rem; }
        .btn-add { background: var(--primary); color: white; float: right; }
        .btn-reset { background: #555; color: white; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; }
        .badge-member { background: #e3f2fd; color: #1976d2; }
        .badge-non { background: #f5f5f5; color: #757575; }

        /* Modal */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; width: 90%; max-width: 500px; margin: 50px auto; padding: 25px; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-size: 0.9rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }

        @media (max-width: 768px) {
            .sidebar { width: 0; overflow: hidden; }
            .main-content { margin-left: 0; width: 100%; padding: 15px; }
            .btn-add { float: none; width: 100%; margin-bottom: 15px; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>L&B ADMIN</h3>
    <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
    <a href="manage_appointment.php"><i class="fas fa-calendar-check"></i> Appointments</a>
    <a href="manage_therapist.php"><i class="fas fa-user-tie"></i> Therapists</a>
    <a href="manage_room.php"><i class="fas fa-door-open"></i> Rooms</a>
    <a href="manage_account.php" class="active"><i class="fas fa-users-cog"></i> User Accounts</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-content">
    <h2>Account Management</h2>
    <?php if($message): ?>
        <div style="padding:10px; background:#d4edda; color:#155724; margin-bottom:20px; border-radius:5px;"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="tab-container">
        <button class="tab-btn active" onclick="openTab(event, 'UserTab')">User Accounts</button>
        <button class="tab-btn" onclick="openTab(event, 'TherapistTab')">Therapist Accounts</button>
    </div>

    <!-- USER TAB -->
    <div id="UserTab" class="tab-content active">
        <div class="card">
            <h3>Registered Users</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr>
                            <td><?php echo $u['first_name'].' '.$u['last_name']; ?></td>
                            <td><?php echo $u['email']; ?></td>
                            <td>
                                <span class="badge <?php echo $u['account_type'] == 'member' ? 'badge-member' : 'badge-non'; ?>">
                                    <?php echo ucfirst($u['account_type']); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display:inline-flex; gap:5px;">
                                    <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                    <select name="account_type" style="padding:5px;">
                                        <option value="non_member" <?php if($u['account_type']=='non_member') echo 'selected'; ?>>Non-Member</option>
                                        <option value="member" <?php if($u['account_type']=='member') echo 'selected'; ?>>Member</option>
                                    </select>
                                    <button type="submit" name="update_membership" class="btn" style="background:var(--dark); color:white;">Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- THERAPIST TAB -->
    <div id="TherapistTab" class="tab-content">
        <button class="btn btn-add" onclick="document.getElementById('addTherapistModal').style.display='block'">
            <i class="fas fa-plus"></i> Add Therapist Account
        </button>
        <div class="card" style="margin-top:50px;">
            <h3>Therapist Login Access</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Therapist Name</th>
                            <th>Username</th>
                            <th>Specialty</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($therapists as $t): ?>
                        <tr>
                            <td><?php echo $t['name']; ?></td>
                            <td><?php echo $t['username']; ?></td>
                            <td><?php echo $t['specialty']; ?></td>
                            <td>
                                <button class="btn btn-reset" onclick="openResetModal('<?php echo $t['username']; ?>')">Reset Password</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: ADD THERAPIST -->
<div id="addTherapistModal" class="modal">
    <div class="modal-content">
        <span style="float:right; cursor:pointer;" onclick="this.parentElement.parentElement.style.display='none'">&times;</span>
        <h3>Create Therapist Account</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group"><label>First Name</label><input type="text" name="fname" required></div>
            <div class="form-group"><label>Middle Name</label><input type="text" name="mname"></div>
            <div class="form-group"><label>Last Name</label><input type="text" name="lname" required></div>
            <div class="form-group">
                <label>Gender</label>
                <select name="gender"><option>Male</option><option>Female</option></select>
            </div>
            <div class="form-group"><label>Specialty</label><input type="text" name="specialty" required></div>
            <div class="form-group"><label>Work Experience</label><textarea name="experience"></textarea></div>
            <div class="form-group"><label>Photo</label><input type="file" name="photo"></div>
            <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
            <button type="submit" name="add_therapist_acc" class="btn btn-add" style="float:none; width:100%;">Save Account</button>
        </form>
    </div>
</div>

<!-- MODAL: ADMIN VERIFICATION FOR RESET -->
<div id="resetVerifyModal" class="modal">
    <div class="modal-content">
        <h3>Verify Admin Identity</h3>
        <p>Please enter <b>your</b> admin credentials to proceed with the reset.</p>
        <div class="form-group"><label>Admin Username</label><input type="text" id="verify_admin_user"></div>
        <div class="form-group"><label>Admin Password</label><input type="password" id="verify_admin_pass"></div>
        <button class="btn" style="background:var(--primary); color:white; width:100%;" onclick="verifyAdmin()">Verify</button>
    </div>
</div>

<script>
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) tabcontent[i].style.display = "none";
    tablinks = document.getElementsByClassName("tab-btn");
    for (i = 0; i < tablinks.length; i++) tablinks[i].className = tablinks[i].className.replace(" active", "");
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}

function openResetModal(username) {
    // Simply open the verification modal first
    document.getElementById('resetVerifyModal').style.display = 'block';
}

function verifyAdmin() {
    const user = document.getElementById('verify_admin_user').value;
    const pass = document.getElementById('verify_admin_pass').value;
    
    // Simple frontend check for this demo as requested (admin / Lumiere2026!)
    if(user === 'admin' && pass === 'Lumiere2026!') {
        alert("Identity Verified. Proceeding to set new password...");
        // In a real flow, you'd redirect to a reset_password.php?user=... or show a 2nd modal
        document.getElementById('resetVerifyModal').style.display = 'none';
    } else {
        alert("Invalid Admin Credentials!");
    }
}
</script>

</body>
</html>