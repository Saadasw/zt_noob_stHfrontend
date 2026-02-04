<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['admin']);

$page_title = 'Doctor Management - St. George Hospital';
$page_css = 'css/admin-portal.css';
$current_page = 'doctors';

$message = '';
$error = '';

// Fetch branches for dropdown
$branches = $pdo->query("SELECT id, name FROM branches WHERE is_active = 1")->fetchAll();

// Handle Create Doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_doctor'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization']);
    $license = trim($_POST['license_number']);
    $branch_id = $_POST['branch_id'];
    $fee = floatval($_POST['consultation_fee']);

    if (empty($name) || empty($email) || empty($license)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $pdo->beginTransaction();

            // Create user account
            $userId = 'USR-DOC-' . bin2hex(random_bytes(4));
            $password = password_hash('123', PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password, role, phone, is_active) VALUES (?, ?, ?, ?, 'doctor', ?, 1)");
            $stmt->execute([$userId, $name, $email, $password, $phone]);

            // Create doctor profile
            $docId = 'DOC-' . bin2hex(random_bytes(4));
            $stmt = $pdo->prepare("INSERT INTO doctor_profiles (id, user_id, branch_id, specialization, license_number, consultation_fee) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$docId, $userId, $branch_id, $specialization, $license, $fee]);

            $pdo->commit();
            $message = "Doctor created successfully! Login: $email / 123";

        } catch (PDOException $e) {
            $pdo->rollBack();
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $error = "Email or License number already exists.";
            } else {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Handle Update Doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_doctor'])) {
    $doctor_id = $_POST['doctor_id'];
    $user_id = $_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization']);
    $license = trim($_POST['license_number']);
    $branch_id = $_POST['branch_id'];
    $fee = floatval($_POST['consultation_fee']);
    $is_available = intval($_POST['is_available']);

    if (empty($name) || empty($email) || empty($license)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $pdo->beginTransaction();

            // Update user account
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $user_id]);

            // Update doctor profile
            $stmt = $pdo->prepare("UPDATE doctor_profiles SET branch_id = ?, specialization = ?, license_number = ?, consultation_fee = ?, is_available = ? WHERE id = ?");
            $stmt->execute([$branch_id, $specialization, $license, $fee, $is_available, $doctor_id]);

            $pdo->commit();
            $message = "Doctor updated successfully!";

        } catch (PDOException $e) {
            $pdo->rollBack();
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $error = "Email or License number already exists.";
            } else {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Handle Update Schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_schedule'])) {
    $doctor_id = $_POST['doctor_id'];
    $branch_id = $_POST['branch_id'];
    $slot_duration = intval($_POST['slot_duration']);
    $start_times = $_POST['start_time'];
    $end_times = $_POST['end_time'];
    $day_active = $_POST['day_active'] ?? [];

    try {
        $pdo->beginTransaction();

        // Update slot_duration in doctor_profiles
        $stmt = $pdo->prepare("UPDATE doctor_profiles SET slot_duration = ? WHERE id = ?");
        $stmt->execute([$slot_duration, $doctor_id]);

        // Delete existing weekly schedules for this doctor
        $stmt = $pdo->prepare("DELETE FROM doctor_weekly_schedules WHERE doctor_id = ?");
        $stmt->execute([$doctor_id]);

        // Insert new weekly schedules
        for ($i = 0; $i <= 6; $i++) {
            $is_active = isset($day_active[$i]) ? 1 : 0;
            if ($is_active && !empty($start_times[$i]) && !empty($end_times[$i])) {
                $scheduleId = 'SCH-' . bin2hex(random_bytes(4));
                $stmt = $pdo->prepare("INSERT INTO doctor_weekly_schedules (id, doctor_id, branch_id, day_of_week, start_time, end_time, is_active, slot_duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$scheduleId, $doctor_id, $branch_id, $i, $start_times[$i], $end_times[$i], 1, $slot_duration]);
            }
        }

        // Handle block date if provided
        if (!empty($_POST['block_date']) && !empty($_POST['block_reason'])) {
            $block_date = $_POST['block_date'];
            $block_reason = $_POST['block_reason'];
            $overrideId = 'OVR-' . bin2hex(random_bytes(4));
            $stmt = $pdo->prepare("INSERT INTO doctor_schedule_overrides (id, doctor_id, branch_id, date, type, reason) VALUES (?, ?, ?, ?, 'unavailable', ?)");
            $stmt->execute([$overrideId, $doctor_id, $branch_id, $block_date, $block_reason]);
        }

        $pdo->commit();
        $message = "Schedule updated successfully!";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch doctors (with branch filter)
$selected_branch_id = $_SESSION['selected_branch_id'] ?? null;

if ($selected_branch_id) {
    $stmt = $pdo->prepare("
        SELECT dp.*, u.name, u.email, u.phone, u.is_active, b.name as branch_name
        FROM doctor_profiles dp
        JOIN users u ON dp.user_id = u.id
        LEFT JOIN branches b ON dp.branch_id = b.id
        WHERE u.is_active = 1 AND dp.branch_id = ?
        ORDER BY u.name
    ");
    $stmt->execute([$selected_branch_id]);
    $doctors = $stmt->fetchAll();
} else {
    $doctors = $pdo->query("
        SELECT dp.*, u.name, u.email, u.phone, u.is_active, b.name as branch_name
        FROM doctor_profiles dp
        JOIN users u ON dp.user_id = u.id
        LEFT JOIN branches b ON dp.branch_id = b.id
        WHERE u.is_active = 1
        ORDER BY u.name
    ")->fetchAll();
}

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Doctor Management</h1>
                <p class="page-subtitle">Manage doctors and their profiles</p>
            </div>
            <button class="btn btn-primary" onclick="toggleModal('addDoctorModal')">+ Add New Doctor</button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Specialization</th>
                            <th>Branch</th>
                            <th>License No</th>
                            <th>Consult Fee</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($doctors as $d): ?>
                            <tr>
                                <td>
                                    <strong><?php echo h($d['name']); ?></strong>
                                    <div class="text-sm text-gray"><?php echo h($d['email']); ?></div>
                                </td>
                                <td><?php echo h($d['specialization']); ?></td>
                                <td><?php echo h($d['branch_name'] ?? 'N/A'); ?></td>
                                <td><?php echo h($d['license_number']); ?></td>
                                <td>$<?php echo number_format($d['consultation_fee'], 2); ?></td>
                                <td>
                                    <?php echo $d['is_available'] ? '<span class="badge badge-green">Available</span>' : '<span class="badge badge-gray">Unavailable</span>'; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline"
                                        onclick="openEditModal('<?php echo h($d['id']); ?>', '<?php echo h($d['user_id']); ?>', '<?php echo h($d['name']); ?>', '<?php echo h($d['email']); ?>', '<?php echo h($d['phone'] ?? ''); ?>', '<?php echo h($d['specialization']); ?>', '<?php echo h($d['license_number']); ?>', '<?php echo h($d['branch_id']); ?>', '<?php echo h($d['consultation_fee']); ?>', '<?php echo $d['is_available'] ? '1' : '0'; ?>')">Edit</button>
                                    <button class="btn btn-sm btn-outline"
                                        onclick="openScheduleModal('<?php echo h($d['id']); ?>', '<?php echo h($d['name']); ?>', '<?php echo h($d['branch_id']); ?>')">Schedule</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($doctors)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No doctors found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

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
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-input" required placeholder="Dr. John Smith">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" required placeholder="doctor@hospital.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-input" placeholder="+61...">
                    </div>
                </div>

                <div class="section-header">PROFESSIONAL DETAILS</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Specialization *</label>
                        <select name="specialization" class="form-select" required>
                            <option value="">Select...</option>
                            <option>General Medicine</option>
                            <option>Cardiology</option>
                            <option>Dermatology</option>
                            <option>Orthopedics</option>
                            <option>Pediatrics</option>
                            <option>Neurology</option>
                            <option>Psychiatry</option>
                            <option>Ophthalmology</option>
                            <option>ENT</option>
                            <option>Gastroenterology</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">License Number *</label>
                        <input type="text" name="license_number" class="form-input" required
                            placeholder="MED-VIC-XXXXX">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Primary Branch *</label>
                        <select name="branch_id" class="form-select" required>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?php echo $b['id']; ?>"><?php echo h($b['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Consultation Fee ($)</label>
                        <input type="number" step="0.01" name="consultation_fee" class="form-input" value="100.00">
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="button" class="btn btn-outline"
                        onclick="toggleModal('addDoctorModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Doctor Account</button>
                </div>
            </form>
        </div>

    </main>
</div>

<!-- Edit Doctor Modal -->
<div id="editDoctorModal" class="modal-overlay" style="display: none;">
    <div class="card modal-card">
        <div class="card-header">
            <h3 class="card-title">Edit Doctor</h3>
            <button class="btn btn-sm btn-outline" onclick="toggleModal('editDoctorModal')">Close</button>
        </div>
        <form method="POST">
            <input type="hidden" name="update_doctor" value="1">
            <input type="hidden" name="doctor_id" id="edit_doctor_id">
            <input type="hidden" name="user_id" id="edit_user_id">

            <div class="section-header">PERSONAL INFORMATION</div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" id="edit_name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" id="edit_email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" id="edit_phone" class="form-input">
                </div>
            </div>

            <div class="section-header">PROFESSIONAL DETAILS</div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Specialization *</label>
                    <select name="specialization" id="edit_specialization" class="form-select" required>
                        <option value="">Select...</option>
                        <option>General Medicine</option>
                        <option>Cardiology</option>
                        <option>Dermatology</option>
                        <option>Orthopedics</option>
                        <option>Pediatrics</option>
                        <option>Neurology</option>
                        <option>Psychiatry</option>
                        <option>Ophthalmology</option>
                        <option>ENT</option>
                        <option>Gastroenterology</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">License Number *</label>
                    <input type="text" name="license_number" id="edit_license" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Primary Branch *</label>
                    <select name="branch_id" id="edit_branch" class="form-select" required>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?php echo $b['id']; ?>"><?php echo h($b['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Consultation Fee ($)</label>
                    <input type="number" step="0.01" name="consultation_fee" id="edit_fee" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Availability</label>
                    <select name="is_available" id="edit_available" class="form-select">
                        <option value="1">Available</option>
                        <option value="0">Unavailable</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-2 mt-4">
                <button type="button" class="btn btn-outline" onclick="toggleModal('editDoctorModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Schedule Modal -->
<div id="scheduleModal" class="modal-overlay" style="display: none;">
    <div class="card modal-card" style="max-width: 700px;">
        <div class="card-header">
            <h3 class="card-title">Manage Schedule - <span id="schedule_doctor_name"></span></h3>
            <button class="btn btn-sm btn-outline" onclick="toggleModal('scheduleModal')">Close</button>
        </div>
        <form method="POST">
            <input type="hidden" name="update_schedule" value="1">
            <input type="hidden" name="doctor_id" id="schedule_doctor_id">
            <input type="hidden" name="branch_id" id="schedule_branch_id">

            <div class="section-header">WEEKLY WORKING HOURS</div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                        foreach ($days as $i => $day):
                            ?>
                            <tr>
                                <td><?php echo $day; ?></td>
                                <td>
                                    <input type="time" name="start_time[<?php echo $i; ?>]" class="form-input" value="09:00"
                                        style="width: 120px;">
                                </td>
                                <td>
                                    <input type="time" name="end_time[<?php echo $i; ?>]" class="form-input" value="17:00"
                                        style="width: 120px;">
                                </td>
                                <td>
                                    <input type="checkbox" name="day_active[<?php echo $i; ?>]" value="1" <?php echo ($i >= 1 && $i <= 5) ? 'checked' : ''; ?>>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="section-header" style="margin-top: 20px;">CONSULTATION SETTINGS</div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Slot Duration (minutes)</label>
                    <select name="slot_duration" class="form-select">
                        <option value="15">15 minutes</option>
                        <option value="20">20 minutes</option>
                        <option value="30" selected>30 minutes</option>
                        <option value="45">45 minutes</option>
                        <option value="60">60 minutes</option>
                    </select>
                </div>
            </div>

            <div class="section-header" style="margin-top: 20px;">BLOCK TIME OFF (Optional)</div>
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Block Date</label>
                    <input type="date" name="block_date" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Reason</label>
                    <select name="block_reason" class="form-select">
                        <option value="">Select reason...</option>
                        <option value="Personal Leave">Personal Leave</option>
                        <option value="Sick Leave">Sick Leave</option>
                        <option value="Conference">Conference</option>
                        <option value="Training">Training</option>
                        <option value="Holiday">Holiday</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-2 mt-4">
                <button type="button" class="btn btn-outline" onclick="toggleModal('scheduleModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Schedule</button>
            </div>
        </form>
    </div>
</div>

<style>
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-card {
        max-width: 600px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        margin: 0;
    }
</style>

<script>
    function toggleModal(id) {
        var el = document.getElementById(id);
        el.style.display = el.style.display === 'none' ? 'flex' : 'none';
    }

    function openEditModal(doctorId, userId, name, email, phone, specialization, license, branchId, fee, isAvailable) {
        document.getElementById('edit_doctor_id').value = doctorId;
        document.getElementById('edit_user_id').value = userId;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_phone').value = phone;
        document.getElementById('edit_license').value = license;
        document.getElementById('edit_fee').value = fee;
        document.getElementById('edit_available').value = isAvailable;

        // Set specialization dropdown
        var specSelect = document.getElementById('edit_specialization');
        for (var i = 0; i < specSelect.options.length; i++) {
            if (specSelect.options[i].value === specialization || specSelect.options[i].text === specialization) {
                specSelect.selectedIndex = i;
                break;
            }
        }

        // Set branch dropdown
        document.getElementById('edit_branch').value = branchId;

        toggleModal('editDoctorModal');
    }

    function openScheduleModal(doctorId, doctorName, branchId) {
        document.getElementById('schedule_doctor_id').value = doctorId;
        document.getElementById('schedule_doctor_name').textContent = doctorName;
        document.getElementById('schedule_branch_id').value = branchId;
        toggleModal('scheduleModal');
    }
</script>

<?php include '../includes/footer.php'; ?>