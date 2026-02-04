<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['branch_admin']);

$page_title = 'Branch Staff';
$page_css = 'css/branch-admin-portal.css';
$current_page = 'staff';

$branch_id = $_SESSION['branch_admin_branch_id'] ?? null;
if (!$branch_id) {
    die("Branch not assigned.");
}

$message = '';
$error = '';

// Handle Add Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $designation = trim($_POST['designation']);
    $password = password_hash('staff123', PASSWORD_DEFAULT);

    try {
        $user_id = 'STF-' . bin2hex(random_bytes(4));
        $profile_id = 'SP-' . bin2hex(random_bytes(4));
        $emp_id = 'EMP-' . mt_rand(10000, 99999);

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO users (id, email, password, name, role, phone) VALUES (?, ?, ?, ?, 'staff', ?)");
        $stmt->execute([$user_id, $email, $password, $name, $phone]);

        $stmt = $pdo->prepare("INSERT INTO staff_profiles (id, user_id, branch_id, department, employee_id, designation, join_date) VALUES (?, ?, ?, ?, ?, ?, CURDATE())");
        $stmt->execute([$profile_id, $user_id, $branch_id, $department, $emp_id, $designation]);

        $pdo->commit();
        $message = "Staff added successfully! Default password: staff123";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch staff for THIS BRANCH only
$stmt = $pdo->prepare("
    SELECT sp.*, u.name, u.email, u.phone, u.is_active
    FROM staff_profiles sp
    JOIN users u ON sp.user_id = u.id
    WHERE sp.branch_id = ? AND sp.deleted_at IS NULL
    ORDER BY u.name
");
$stmt->execute([$branch_id]);
$staff = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar_branch_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_branch_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Branch Staff</h1>
                <p class="page-subtitle">Manage staff at your branch</p>
            </div>
            <button class="btn btn-primary" onclick="toggleModal('addStaffModal')">+ Add Staff</button>
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
            <div class="card-header">
                <h3 class="card-title">👤 Staff Members (
                    <?php echo count($staff); ?>)
                </h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff as $s): ?>
                            <tr>
                                <td>
                                    <?php echo h($s['employee_id']); ?>
                                </td>
                                <td>
                                    <strong>
                                        <?php echo h($s['name']); ?>
                                    </strong>
                                    <div class="text-sm text-gray">
                                        <?php echo h($s['email']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo h($s['department']); ?>
                                </td>
                                <td>
                                    <?php echo h($s['designation']); ?>
                                </td>
                                <td>
                                    <?php if ($s['is_active']): ?>
                                        <span class="badge badge-green">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-gray">Inactive</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($staff)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-gray">No staff found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Add Staff Modal -->
<div id="addStaffModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Staff</h3>
            <button onclick="toggleModal('addStaffModal')" class="btn btn-outline btn-sm">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="add_staff" value="1">
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Department *</label>
                    <select name="department" class="form-select" required>
                        <option value="">Select...</option>
                        <option value="Reception">Reception</option>
                        <option value="Laboratory">Laboratory</option>
                        <option value="Pharmacy">Pharmacy</option>
                        <option value="Billing">Billing</option>
                        <option value="Nursing">Nursing</option>
                        <option value="Administration">Administration</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Designation *</label>
                    <input type="text" name="designation" class="form-input" required
                        placeholder="e.g., Receptionist, Lab Tech">
                </div>
            </div>
            <p class="text-sm text-gray mb-4">Default password will be: staff123</p>
            <div class="flex gap-2">
                <button type="button" onclick="toggleModal('addStaffModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Staff</button>
            </div>
        </form>
    </div>
</div>

<style>
    .modal {
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

    .modal-content {
        background: white;
        padding: 24px;
        border-radius: 8px;
        width: 100%;
        max-width: 600px;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .mb-4 {
        margin-bottom: 16px;
    }

    .alert-success {
        background: #dcfce7;
        color: #166534;
        padding: 12px;
        border-radius: 4px;
        margin-bottom: 16px;
    }

    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        padding: 12px;
        border-radius: 4px;
        margin-bottom: 16px;
    }
</style>

<script>
    function toggleModal(id) {
        var el = document.getElementById(id);
        el.style.display = el.style.display === 'none' ? 'flex' : 'none';
    }
</script>

<?php include '../includes/footer.php'; ?>