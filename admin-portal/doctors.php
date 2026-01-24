<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['admin']);

$page_title = 'Doctor Management - St. George Hospital';
$page_css = 'css/admin-portal.css';
$current_page = 'doctors';

$message = '';
$error = '';

// Handle Create Doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_doctor'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $branch_id = $_POST['branch_id'];
    $department = $_POST['department'];
    $license = trim($_POST['license']);
    $specialization = trim($_POST['specialization']);
    $fee = $_POST['fee'];
    $password = $_POST['password'];

    if (empty($email) || empty($password) || empty($license)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Create User
            $name = $first_name . ' ' . $last_name;
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $userId = 'USR-DOC-' . time();
            
            $sql = "INSERT INTO users (id, email, password, name, role, phone) VALUES (?, ?, ?, ?, 'doctor', ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $email, $hashed_password, $name, $phone]);

            // 2. Create Profile
            $profileId = 'DOC-' . time();
            $sql = "INSERT INTO doctor_profiles (id, user_id, branch_id, department, specialization, license_number, consultation_fee) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$profileId, $userId, $branch_id, $department, $specialization, $license, $fee]);
            
            // 3. Create Default Schedule (Mon-Fri 9-5)
            $scheduleSql = "INSERT INTO doctor_weekly_schedules (id, doctor_id, branch_id, day_of_week, start_time, end_time, slot_duration) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($scheduleSql);
            
            // Loop Mon(1) to Fri(5)
            for ($day = 1; $day <= 5; $day++) {
                $schedId = 'SCH-' . $profileId . '-' . $day;
                $stmt->execute([$schedId, $profileId, $branch_id, $day, '09:00:00', '17:00:00', 30]);
            }

            $pdo->commit();
            $message = "Doctor created successfully!";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch Doctors with Branch Name
$sql = "SELECT d.*, u.name as doctor_name, u.email, u.phone, b.name as branch_name 
        FROM doctor_profiles d 
        JOIN users u ON d.user_id = u.id 
        JOIN branches b ON d.branch_id = b.id 
        ORDER BY u.name ASC";
$doctors = $pdo->query($sql)->fetchAll();

// Fetch Branches for Dropdown
$branches_list = $pdo->query("SELECT id, name FROM branches WHERE is_active=1")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Doctor Management</h1>
                <p class="page-subtitle">Manage doctor profiles, specializations, and schedules</p>
            </div>
            <button class="btn btn-primary" onclick="toggleModal('addDoctorModal')">+ Add New Doctor</button>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <!-- Doctor List -->
        <?php foreach ($doctors as $doc): ?>
        <div class="doctor-card">
            <div class="branch-header">
                <div>
                    <div class="branch-name">👨‍⚕️ <?php echo h($doc['doctor_name']); ?></div>
                    <div class="branch-meta">License: <?php echo h($doc['license_number']); ?></div>
                </div>
                <span class="badge badge-green">🟢 Active</span>
            </div>
            <p class="text-sm"><strong>Department:</strong> <?php echo h($doc['department']); ?></p>
            <p class="text-sm"><strong>Specialization:</strong> <?php echo h($doc['specialization']); ?></p>
            <p class="text-sm"><strong>Branch:</strong> <?php echo h($doc['branch_name']); ?></p>
            <p class="text-sm"><strong>Fee:</strong> $<?php echo number_format($doc['consultation_fee'], 2); ?></p>
            
            <div class="flex gap-2 mt-4">
                <button class="btn btn-sm btn-outline">View Profile</button>
                <button class="btn btn-sm btn-outline">Edit</button>
                <button class="btn btn-sm btn-outline">Manage Schedule</button>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($doctors)): ?>
            <div class="card"><p class="text-center">No doctors found.</p></div>
        <?php endif; ?>


        <!-- Add Doctor Modal -->
        <div id="addDoctorModal" class="card" style="display: none; border: 2px solid #2563eb;">
            <div class="card-header">
                <h3 class="card-title">Add New Doctor</h3>
                <button class="btn btn-sm btn-outline" onclick="toggleModal('addDoctorModal')">Close</button>
            </div>

            <form method="POST">
                <input type="hidden" name="create_doctor" value="1">
                
                <div class="section-header">PERSONAL INFORMATION</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone *</label>
                        <input type="tel" name="phone" class="form-input" required>
                    </div>
                </div>

                <div class="section-header">PROFESSIONAL INFORMATION</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Assigned Branch *</label>
                        <select name="branch_id" class="form-select" required>
                            <option value="">Select Branch</option>
                            <?php foreach ($branches_list as $b): ?>
                                <option value="<?php echo $b['id']; ?>"><?php echo h($b['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Department *</label>
                        <select name="department" class="form-select" required>
                            <option>General Medicine</option>
                            <option>Cardiology</option>
                            <option>Orthopedics</option>
                            <option>Pediatrics</option>
                            <option>Dermatology</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">License Number *</label>
                        <input type="text" name="license" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Specialization</label>
                        <input type="text" name="specialization" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Consultation Fee ($) *</label>
                        <input type="number" name="fee" class="form-input" value="85.00" step="0.01">
                    </div>
                </div>

                <div class="section-header">ACCOUNT CREDENTIALS</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-input" required value="Doctor123">
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="button" class="btn btn-outline" onclick="toggleModal('addDoctorModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Doctor</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
function toggleModal(id) {
    var el = document.getElementById(id);
    if (el.style.display === 'none') {
        el.style.display = 'block';
        el.scrollIntoView({behavior: "smooth"});
    } else {
        el.style.display = 'none';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
