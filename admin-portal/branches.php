<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['admin']);

$page_title = 'Branch Management - St. George Hospital';
$page_css = 'css/admin-portal.css';
$current_page = 'branches';

$message = '';
$error = '';

// Handle Create Branch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_branch'])) {
    $name = trim($_POST['name']);
    $code = trim($_POST['code']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);

    if (empty($name) || empty($code) || empty($address)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $id = 'BR-' . strtoupper(substr($code, 0, 3)) . '-' . mt_rand(10, 99);
            $stmt = $pdo->prepare("INSERT INTO branches (id, name, code, address, city, state, phone, email, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$id, $name, $code, $address, $city, $state, $phone, $email]);
            $message = "Branch created successfully!";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $error = "Branch code already exists.";
            } else {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Handle Update Branch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_branch'])) {
    $id = $_POST['branch_id'];
    $name = trim($_POST['name']);
    $code = trim($_POST['code']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);

    if (empty($name) || empty($code) || empty($address)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE branches SET name = ?, code = ?, address = ?, city = ?, state = ?, phone = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $code, $address, $city, $state, $phone, $email, $id]);
            $message = "Branch updated successfully!";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $error = "Branch code already exists.";
            } else {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Handle Restore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_branch'])) {
    $id = $_POST['branch_id'];
    try {
        $stmt = $pdo->prepare("UPDATE branches SET is_active = 1, deleted_at = NULL WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Branch restored successfully!";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_branch'])) {
    $id = $_POST['branch_id'];
    try {
        $stmt = $pdo->prepare("UPDATE branches SET is_active = 0, deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Branch deactivated.";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get deactivation filter
$show_deactivated = isset($_GET['show_deactivated']) && $_GET['show_deactivated'] == 1;

// Fetch branches
$query = "SELECT * FROM branches " . ($show_deactivated ? "WHERE is_active = 0" : "WHERE is_active = 1") . " ORDER BY name";
$branches = $pdo->query($query)->fetchAll();

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Branch Management</h1>
                <p class="page-subtitle">Manage hospital branches and locations</p>
            </div>
            <div class="flex gap-2">
                <a href="?show_deactivated=<?php echo $show_deactivated ? '0' : '1'; ?>" class="btn btn-outline">
                    <?php echo $show_deactivated ? 'View Active Branches' : 'View Deactivated'; ?>
                </a>
                <button class="btn btn-primary" onclick="toggleModal('addBranchModal')">+ Add New Branch</button>
            </div>
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
                            <th>Code</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>City</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($branches as $b): ?>
                        <tr>
                            <td><strong><?php echo h($b['code']); ?></strong></td>
                            <td><?php echo h($b['name']); ?></td>
                            <td><?php echo h($b['address']); ?></td>
                            <td><?php echo h($b['city']); ?>, <?php echo h($b['state']); ?></td>
                            <td><?php echo h($b['phone']); ?></td>
                            <td><span class="badge <?php echo $b['is_active'] ? 'badge-green' : 'badge-red'; ?>">
                                <?php echo $b['is_active'] ? 'Active' : 'Deactivated'; ?>
                            </span></td>
                            <td>
                                <?php if ($b['is_active']): ?>
                                    <button class="btn btn-sm btn-outline" onclick="openEditModal(this)"
                                        data-id="<?php echo h($b['id']); ?>"
                                        data-name="<?php echo h($b['name']); ?>"
                                        data-code="<?php echo h($b['code']); ?>"
                                        data-address="<?php echo h($b['address']); ?>"
                                        data-city="<?php echo h($b['city']); ?>"
                                        data-state="<?php echo h($b['state']); ?>"
                                        data-phone="<?php echo h($b['phone']); ?>"
                                        data-email="<?php echo h($b['email']); ?>">Edit</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Deactivate this branch?');">
                                        <input type="hidden" name="delete_branch" value="1">
                                        <input type="hidden" name="branch_id" value="<?php echo $b['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline" style="color: #dc2626; border-color: #dc2626;">Deactivate</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Restore this branch?');">
                                        <input type="hidden" name="restore_branch" value="1">
                                        <input type="hidden" name="branch_id" value="<?php echo $b['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline" style="color: #059669; border-color: #059669;">Restore</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($branches)): ?>
                        <tr><td colspan="7" class="text-center">No branches found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Branch Modal -->
        <div id="addBranchModal" class="card" style="display: none; border: 2px solid #2563eb;">
            <div class="card-header">
                <h3 class="card-title">Add New Branch</h3>
                <button class="btn btn-sm btn-outline" onclick="toggleModal('addBranchModal')">Close</button>
            </div>
            <form method="POST">
                <input type="hidden" name="create_branch" value="1">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Branch Name *</label>
                        <input type="text" name="name" class="form-input" required placeholder="e.g., Melbourne CBD">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Branch Code *</label>
                        <input type="text" name="code" class="form-input" required placeholder="e.g., MEL-CBD">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Address *</label>
                    <input type="text" name="address" class="form-input" required placeholder="Street address">
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-input" placeholder="City">
                    </div>
                    <div class="form-group">
                        <label class="form-label">State</label>
                        <input type="text" name="state" class="form-input" placeholder="State">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-input" placeholder="Phone number">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" placeholder="branch@hospital.com">
                    </div>
                </div>
                <div class="flex gap-2 mt-4">
                    <button type="button" class="btn btn-outline" onclick="toggleModal('addBranchModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Branch</button>
                </div>
            </form>
        </div>

        </div>

        <!-- Edit Branch Modal -->
        <div id="editBranchModal" class="card" style="display: none; border: 2px solid #22c55e;">
            <div class="card-header">
                <h3 class="card-title">Edit Branch</h3>
                <button class="btn btn-sm btn-outline" onclick="toggleModal('editBranchModal')">Close</button>
            </div>
            <form method="POST">
                <input type="hidden" name="update_branch" value="1">
                <input type="hidden" name="branch_id" id="edit_branch_id">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Branch Name *</label>
                        <input type="text" name="name" id="edit_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Branch Code *</label>
                        <input type="text" name="code" id="edit_code" class="form-input" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Address *</label>
                    <input type="text" name="address" id="edit_address" class="form-input" required>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" name="city" id="edit_city" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">State</label>
                        <input type="text" name="state" id="edit_state" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" id="edit_phone" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-input">
                    </div>
                </div>
                <div class="flex gap-2 mt-4">
                    <button type="button" class="btn btn-outline" onclick="toggleModal('editBranchModal')">Cancel</button>
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
    document.getElementById('edit_branch_id').value = btn.dataset.id;
    document.getElementById('edit_name').value = btn.dataset.name;
    document.getElementById('edit_code').value = btn.dataset.code;
    document.getElementById('edit_address').value = btn.dataset.address;
    document.getElementById('edit_city').value = btn.dataset.city || '';
    document.getElementById('edit_state').value = btn.dataset.state || '';
    document.getElementById('edit_phone').value = btn.dataset.phone || '';
    document.getElementById('edit_email').value = btn.dataset.email || '';

    var modal = document.getElementById('editBranchModal');
    modal.style.display = 'block';
    modal.scrollIntoView({ behavior: "smooth" });
}
</script>

<?php include '../includes/footer.php'; ?>
