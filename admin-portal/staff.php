<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['admin']);

$page_title = 'Staff Management - St. George Hospital';
$page_css = 'css/admin-portal.css';
$current_page = 'staff';

$message = '';
$error = '';

// Default departments
$departments = [
    'reception' => 'Reception - Front desk, patient registration, appointments',
    'laboratory' => 'Laboratory - Lab test processing, result entry',
    'pharmacy' => 'Pharmacy - Medicine dispensing',
    'billing' => 'Billing - Bill creation, payment collection'
];

// Fetch branches for dropdown
$branches = $pdo->query("SELECT id, name FROM branches WHERE is_active = 1")->fetchAll();

// Get selected branch filter
$selected_branch_id = $_SESSION['selected_branch_id'] ?? null;
$selected_branch_name = $_SESSION['selected_branch_name'] ?? 'All Branches';

// Handle Create Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_staff'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = $_POST['department'];
    $designation = trim($_POST['designation']);
    $employee_id = trim($_POST['employee_id']);
    $branch_id = $_POST['branch_id'];

    if (empty($name) || empty($email) || empty($employee_id) || empty($department)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $pdo->beginTransaction();

            // Create user account
            $userId = 'USR-STF-' . bin2hex(random_bytes(4));
            $password = password_hash('123', PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password, role, phone, is_active) VALUES (?, ?, ?, ?, 'staff', ?, 1)");
            $stmt->execute([$userId, $name, $email, $password, $phone]);

            // Create staff profile
            $staffId = 'STF-' . bin2hex(random_bytes(4));
            $stmt = $pdo->prepare("INSERT INTO staff_profiles (id, user_id, branch_id, department, employee_id, designation) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$staffId, $userId, $branch_id, $department, $employee_id, $designation]);

            $pdo->commit();
            $message = "Staff member created successfully! Login: $email / 123";

        } catch (PDOException $e) {
            $pdo->rollBack();
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $error = "Email or Employee ID already exists.";
            } else {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Handle Update Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    $staff_id = $_POST['staff_id'];
    $user_id = $_POST['user_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = $_POST['department'];
    $designation = trim($_POST['designation']);
    $branch_id = $_POST['branch_id'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name) || empty($email)) {
        $error = "Name and Email are required.";
    } else {
        try {
            $pdo->beginTransaction();

            // Update user
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name, $email, $phone, $is_active, $user_id]);

            // Update staff profile
            $stmt = $pdo->prepare("UPDATE staff_profiles SET branch_id = ?, department = ?, designation = ? WHERE id = ?");
            $stmt->execute([$branch_id, $department, $designation, $staff_id]);

            $pdo->commit();
            $message = "Staff member updated successfully!";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle Delete Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_staff'])) {
    $user_id = $_POST['user_id'];
    try {
        // Soft delete - deactivate user and mark profile as deleted
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE users SET is_active = 0, deleted_at = NOW() WHERE id = ?")->execute([$user_id]);
        $pdo->prepare("UPDATE staff_profiles SET deleted_at = NOW() WHERE user_id = ?")->execute([$user_id]);
        $pdo->commit();
        $message = "Staff member deactivated successfully.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch Staff (with branch filter)
if ($selected_branch_id) {
    $stmt = $pdo->prepare("
        SELECT sp.*, u.name, u.email, u.phone, u.is_active, b.name as branch_name
        FROM staff_profiles sp
        JOIN users u ON sp.user_id = u.id
        LEFT JOIN branches b ON sp.branch_id = b.id
        WHERE u.is_active = 1 AND sp.branch_id = ?
        ORDER BY u.name
    ");
    $stmt->execute([$selected_branch_id]);
    $staff_list = $stmt->fetchAll();
} else {
    $staff_list = $pdo->query("
        SELECT sp.*, u.name, u.email, u.phone, u.is_active, b.name as branch_name
        FROM staff_profiles sp
        JOIN users u ON sp.user_id = u.id
        LEFT JOIN branches b ON sp.branch_id = b.id
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
                <h1 class="page-title">Staff Management</h1>
                <p class="page-subtitle">Manage staff members |
                    <?php echo h($selected_branch_name); ?>
                </p>
            </div>
            <button class="btn btn-primary" onclick="toggleModal('addStaffModal')">+ Add New Staff</button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo h($message); ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo h($error); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Branch</th>
                            <th>Designation</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff_list as $s): ?>
                            <tr>
                                <td><strong>
                                        <?php echo h($s['employee_id']); ?>
                                    </strong></td>
                                <td>
                                    <strong>
                                        <?php echo h($s['name']); ?>
                                    </strong>
                                    <div class="text-sm text-gray">
                                        <?php echo h($s['email']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $dept_badge = 'badge-gray';
                                    if ($s['department'] == 'reception')
                                        $dept_badge = 'badge-blue';
                                    if ($s['department'] == 'laboratory')
                                        $dept_badge = 'badge-green';
                                    if ($s['department'] == 'pharmacy')
                                        $dept_badge = 'badge-yellow';
                                    if ($s['department'] == 'billing')
                                        $dept_badge = 'badge-purple';
                                    ?>
                                    <span class="badge <?php echo $dept_badge; ?>">
                                        <?php echo ucfirst(h($s['department'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo h($s['branch_name'] ?? 'N/A'); ?>
                                </td>
                                <td>
                                    <?php echo h($s['designation']); ?>
                                </td>
                                <td>
                                    <?php echo $s['is_active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-red">Inactive</span>'; ?>
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <button class="btn btn-sm btn-outline" onclick="openEditModal(this)"
                                            data-staffid="<?php echo h($s['id']); ?>"
                                            data-userid="<?php echo h($s['user_id']); ?>"
                                            data-name="<?php echo h($s['name']); ?>"
                                            data-email="<?php echo h($s['email']); ?>"
                                            data-phone="<?php echo h($s['phone'] ?? ''); ?>"
                                            data-department="<?php echo h($s['department']); ?>"
                                            data-designation="<?php echo h($s['designation']); ?>"
                                            data-branchid="<?php echo h($s['branch_id']); ?>"
                                            data-active="<?php echo $s['is_active']; ?>">Edit</button>
                                        <form method="POST" onsubmit="return confirm('Deactivate this staff member?');"
                                            style="display:inline;">
                                            <input type="hidden" name="delete_staff" value="1">
                                            <input type="hidden" name="user_id" value="<?php echo $s['user_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline"
                                                style="color: #dc2626; border-color: #dc2626;">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($staff_list)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No staff members found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Staff Modal -->
        <div id="addStaffModal" class="card" style="display: none; border: 2px solid #2563eb;">
            <div class="card-header">
                <h3 class="card-title">Add New Staff Member</h3>
                <button class="btn btn-sm btn-outline" onclick="toggleModal('addStaffModal')">Close</button>
            </div>
            <form method="POST">
                <input type="hidden" name="create_staff" value="1">

                <div class="section-header">PERSONAL INFORMATION</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-input" required placeholder="John Smith">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" required placeholder="staff@hospital.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-input" placeholder="+61...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Employee ID *</label>
                        <input type="text" name="employee_id" class="form-input" required placeholder="EMP-XXX">
                    </div>
                </div>

                <div class="section-header">WORK DETAILS</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Department *</label>
                        <select name="department" class="form-select" required>
                            <option value="">Select Department...</option>
                            <?php foreach ($departments as $key => $label): ?>
                                <option value="<?php echo $key; ?>">
                                    <?php echo h($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Designation</label>
                        <input type="text" name="designation" class="form-input"
                            placeholder="e.g., Senior Receptionist">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Branch *</label>
                        <select name="branch_id" class="form-select" required>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?php echo $b['id']; ?>">
                                    <?php echo h($b['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="button" class="btn btn-outline" onclick="toggleModal('addStaffModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Staff Account</button>
                </div>
            </form>
        </div>

        <!-- Edit Staff Modal -->
        <div id="editStaffModal" class="card" style="display: none; border: 2px solid #22c55e;">
            <div class="card-header">
                <h3 class="card-title">Edit Staff Member</h3>
                <button class="btn btn-sm btn-outline" onclick="toggleModal('editStaffModal')">Close</button>
            </div>
            <form method="POST">
                <input type="hidden" name="update_staff" value="1">
                <input type="hidden" name="staff_id" id="edit_staff_id">
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

                <div class="section-header">WORK DETAILS</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Department *</label>
                        <select name="department" id="edit_department" class="form-select" required>
                            <?php foreach ($departments as $key => $label): ?>
                                <option value="<?php echo $key; ?>">
                                    <?php echo h($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Designation</label>
                        <input type="text" name="designation" id="edit_designation" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Branch *</label>
                        <select name="branch_id" id="edit_branch_id" class="form-select" required>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?php echo $b['id']; ?>">
                                    <?php echo h($b['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1">
                        Active Staff Member
                    </label>
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="button" class="btn btn-outline"
                        onclick="toggleModal('editStaffModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>

    </main>
</div>

<script>
    function toggleModal(id) {
        var el = document.getElementById(id);
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
        if (el.style.display === 'block') {
            el.scrollIntoView({ behavior: "smooth" });
        }
    }

    function openEditModal(btn) {
        document.getElementById('edit_staff_id').value = btn.dataset.staffid;
        document.getElementById('edit_user_id').value = btn.dataset.userid;
        document.getElementById('edit_name').value = btn.dataset.name;
        document.getElementById('edit_email').value = btn.dataset.email;
        document.getElementById('edit_phone').value = btn.dataset.phone || '';
        document.getElementById('edit_department').value = btn.dataset.department;
        document.getElementById('edit_designation').value = btn.dataset.designation || '';
        document.getElementById('edit_branch_id').value = btn.dataset.branchid;
        document.getElementById('edit_is_active').checked = btn.dataset.active == '1';

        var modal = document.getElementById('editStaffModal');
        modal.style.display = 'block';
        modal.scrollIntoView({ behavior: "smooth" });
    }
</script>

<?php include '../includes/footer.php'; ?>