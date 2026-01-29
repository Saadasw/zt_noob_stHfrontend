<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['admin']);

$page_title = 'Departments - St. George Hospital';
$page_css = 'css/admin-portal.css';
$current_page = 'departments';

$message = '';
$error = '';

// Handle Create Department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_department'])) {
    $name = trim($_POST['name']);
    $code = trim($_POST['code']);
    $head_doctor_id = $_POST['head_doctor_id'] ?? null;
    $description = trim($_POST['description']);

    if (empty($name) || empty($code)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $id = 'DEPT-' . strtoupper(substr($code, 0, 4)) . '-' . mt_rand(10, 99);
            $stmt = $pdo->prepare("INSERT INTO departments (id, name, code, head_doctor_id, description, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$id, $name, $code, $head_doctor_id ?: null, $description]);
            $message = "Department created successfully!";
        } catch (PDOException $e) {
            // Check if table exists
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                // Create departments table
                $pdo->exec("CREATE TABLE IF NOT EXISTS departments (
                    id VARCHAR(255) PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    code VARCHAR(50) UNIQUE NOT NULL,
                    head_doctor_id VARCHAR(255),
                    description TEXT,
                    is_active TINYINT(1) DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                // Retry insert
                $stmt = $pdo->prepare("INSERT INTO departments (id, name, code, head_doctor_id, description, is_active) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([$id, $name, $code, $head_doctor_id ?: null, $description]);
                $message = "Department table created and department added!";
            } else {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch departments (with table existence check)
$departments = [];
try {
    $departments = $pdo->query("SELECT d.*, u.name as head_name FROM departments d LEFT JOIN doctor_profiles dp ON d.head_doctor_id = dp.id LEFT JOIN users u ON dp.user_id = u.id WHERE d.is_active = 1 ORDER BY d.name")->fetchAll();
} catch (PDOException $e) {
    // Table might not exist yet
}

// Fetch doctors for dropdown
$doctors = $pdo->query("SELECT dp.id, u.name, dp.specialization FROM doctor_profiles dp JOIN users u ON dp.user_id = u.id WHERE u.is_active = 1 ORDER BY u.name")->fetchAll();

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Departments</h1>
                <p class="page-subtitle">Manage hospital departments</p>
            </div>
            <button class="btn btn-primary" onclick="toggleModal('addDeptModal')">+ Add Department</button>
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
                            <th>Head of Department</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $d): ?>
                        <tr>
                            <td><strong><?php echo h($d['code']); ?></strong></td>
                            <td><?php echo h($d['name']); ?></td>
                            <td><?php echo h($d['head_name'] ?? 'Not Assigned'); ?></td>
                            <td><?php echo h($d['description']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline">Edit</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($departments)): ?>
                        <tr><td colspan="5" class="text-center">No departments found. Add one to get started.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Department Modal -->
        <div id="addDeptModal" class="card" style="display: none; border: 2px solid #2563eb;">
            <div class="card-header">
                <h3 class="card-title">Add New Department</h3>
                <button class="btn btn-sm btn-outline" onclick="toggleModal('addDeptModal')">Close</button>
            </div>
            <form method="POST">
                <input type="hidden" name="create_department" value="1">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Department Name *</label>
                        <input type="text" name="name" class="form-input" required placeholder="e.g., Cardiology">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Code *</label>
                        <input type="text" name="code" class="form-input" required placeholder="e.g., CARD">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Head of Department</label>
                        <select name="head_doctor_id" class="form-select">
                            <option value="">Select Doctor...</option>
                            <?php foreach ($doctors as $doc): ?>
                                <option value="<?php echo $doc['id']; ?>"><?php echo h($doc['name']); ?> (<?php echo h($doc['specialization']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" rows="2" placeholder="Brief description of the department..."></textarea>
                </div>
                <div class="flex gap-2 mt-4">
                    <button type="button" class="btn btn-outline" onclick="toggleModal('addDeptModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Department</button>
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
