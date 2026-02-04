<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['admin']);

$page_title = 'Branch Admins';
$page_css = 'css/admin-portal.css';
$current_page = 'branch-admins';

$message = '';
$error = '';

// Fetch branches for dropdown
$branches = $pdo->query("SELECT id, name, code FROM branches WHERE is_active = 1 ORDER BY name")->fetchAll();

// Handle Add Branch Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_branch_admin'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $branch_id = $_POST['branch_id'];
    $designation = trim($_POST['designation']) ?: 'Branch Administrator';
    $password = password_hash('123', PASSWORD_DEFAULT);

    if (empty($name) || empty($email) || empty($branch_id)) {
        $error = "Name, email, and branch are required.";
    } else {
        try {
            $user_id = 'BA-' . bin2hex(random_bytes(4));
            $profile_id = 'BAP-' . bin2hex(random_bytes(4));
            $emp_id = 'EMP-BA-' . mt_rand(10000, 99999);

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO users (id, email, password, name, role, phone) VALUES (?, ?, ?, ?, 'branch_admin', ?)");
            $stmt->execute([$user_id, $email, $password, $name, $phone]);

            $stmt = $pdo->prepare("INSERT INTO branch_admin_profiles (id, user_id, branch_id, employee_id, designation) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$profile_id, $user_id, $branch_id, $emp_id, $designation]);

            $pdo->commit();
            $message = "Branch Admin added successfully! Default password: admin123";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_branch_admin'])) {
    $user_id = $_POST['user_id'];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM branch_admin_profiles WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
        $pdo->commit();
        $message = "Branch Admin deleted.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all branch admins
$branch_admins = $pdo->query("
    SELECT u.*, bap.employee_id, bap.designation, bap.branch_id, b.name as branch_name, b.code as branch_code
    FROM users u
    JOIN branch_admin_profiles bap ON u.id = bap.user_id
    JOIN branches b ON bap.branch_id = b.id
    WHERE u.role = 'branch_admin' AND u.deleted_at IS NULL
    ORDER BY b.name, u.name
")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <nav class="navbar">
        <div class="branch-selector">
            <strong>Super Admin</strong> - All Branches
        </div>
        <div class="user-info">
            <div>
                <div class="user-name">
                    <?php echo h($_SESSION['user_name']); ?>
                </div>
                <div class="user-role">Administrator</div>
            </div>
        </div>
    </nav>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Branch Admins</h1>
                <p class="page-subtitle">Manage administrators for each branch</p>
            </div>
            <button class="btn btn-primary" onclick="toggleModal('addBranchAdminModal')">+ Add Branch Admin</button>
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
                <h3 class="card-title">👤 Branch Administrators (
                    <?php echo count($branch_admins); ?>)
                </h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Branch</th>
                            <th>Designation</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($branch_admins as $ba): ?>
                            <tr>
                                <td>
                                    <?php echo h($ba['employee_id']); ?>
                                </td>
                                <td>
                                    <strong>
                                        <?php echo h($ba['name']); ?>
                                    </strong>
                                    <div class="text-sm text-gray">
                                        <?php echo h($ba['email']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo h($ba['branch_name']); ?>
                                    <div class="text-sm text-gray">
                                        <?php echo h($ba['branch_code']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo h($ba['designation']); ?>
                                </td>
                                <td>
                                    <?php if ($ba['is_active']): ?>
                                        <span class="badge badge-green">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-gray">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;"
                                        onsubmit="return confirm('Delete this Branch Admin?');">
                                        <input type="hidden" name="delete_branch_admin" value="1">
                                        <input type="hidden" name="user_id" value="<?php echo $ba['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($branch_admins)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-gray">No branch admins found. Add one to get
                                    started.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Add Branch Admin Modal -->
<div id="addBranchAdminModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Branch Admin</h3>
            <button onclick="toggleModal('addBranchAdminModal')" class="btn btn-outline btn-sm">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="add_branch_admin" value="1">
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
                    <label class="form-label">Branch *</label>
                    <select name="branch_id" class="form-select" required>
                        <option value="">Select Branch...</option>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?php echo $b['id']; ?>">
                                <?php echo h($b['name']); ?> (
                                <?php echo h($b['code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Designation</label>
                    <input type="text" name="designation" class="form-input" value="Branch Administrator">
                </div>
            </div>
            <p class="text-sm text-gray mb-4">Default password will be: 123</p>
            <div class="flex gap-2">
                <button type="button" onclick="toggleModal('addBranchAdminModal')"
                    class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Branch Admin</button>
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

    .btn-danger {
        background: #ef4444;
        color: white;
    }

    .btn-danger:hover {
        background: #dc2626;
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