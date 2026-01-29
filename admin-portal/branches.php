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

// Fetch branches
$branches = $pdo->query("SELECT * FROM branches WHERE is_active = 1 ORDER BY name")->fetchAll();

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
            <button class="btn btn-primary" onclick="toggleModal('addBranchModal')">+ Add New Branch</button>
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
                            <td><span class="badge badge-green">Active</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline">Edit</button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Deactivate this branch?');">
                                    <input type="hidden" name="delete_branch" value="1">
                                    <input type="hidden" name="branch_id" value="<?php echo $b['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline" style="color: #dc2626;">Deactivate</button>
                                </form>
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

    </main>
</div>

<script>
function toggleModal(id) {
    var el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php include '../includes/footer.php'; ?>
