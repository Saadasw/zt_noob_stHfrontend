<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['admin']);

$page_title = 'Lab Test Types - St. George Hospital';
$page_css = 'css/admin-portal.css';
$current_page = 'lab-tests';

$message = '';
$error = '';

// Ensure table has new columns (auto-migration)
try {
    $pdo->exec("ALTER TABLE lab_test_types 
        ADD COLUMN IF NOT EXISTS category VARCHAR(100) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS sample_type VARCHAR(100) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS turnaround_hours INT DEFAULT 24,
        ADD COLUMN IF NOT EXISTS fasting_required TINYINT(1) DEFAULT 0");
} catch (PDOException $e) {
    // Columns might already exist or table doesn't exist yet
}

// Handle Create Lab Test Type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_test_type'])) {
    $name = trim($_POST['name']);
    $code = strtoupper(trim($_POST['code']));
    $category = $_POST['category'];
    $price = floatval($_POST['price']);
    $sample_type = $_POST['sample_type'];
    $turnaround_hours = intval($_POST['turnaround_hours']);
    $fasting_required = isset($_POST['fasting_required']) ? 1 : 0;
    $description = trim($_POST['description']);

    if (empty($name) || empty($code) || $price <= 0) {
        $error = "Please fill in all required fields (Name, Code, Price).";
    } else {
        try {
            $id = 'LTT-' . bin2hex(random_bytes(4));
            $stmt = $pdo->prepare("INSERT INTO lab_test_types (id, name, code, category, price, sample_type, turnaround_hours, fasting_required, description, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$id, $name, $code, $category, $price, $sample_type, $turnaround_hours, $fasting_required, $description]);
            $message = "Lab test type added successfully!";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $error = "A test type with this code already exists.";
            } else {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Handle Update Lab Test Type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_test_type'])) {
    $id = $_POST['test_id'];
    $name = trim($_POST['name']);
    $code = strtoupper(trim($_POST['code']));
    $category = $_POST['category'];
    $price = floatval($_POST['price']);
    $sample_type = $_POST['sample_type'];
    $turnaround_hours = intval($_POST['turnaround_hours']);
    $fasting_required = isset($_POST['fasting_required']) ? 1 : 0;
    $description = trim($_POST['description']);

    if (empty($name) || empty($code) || $price <= 0) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE lab_test_types SET name = ?, code = ?, category = ?, price = ?, sample_type = ?, turnaround_hours = ?, fasting_required = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $code, $category, $price, $sample_type, $turnaround_hours, $fasting_required, $description, $id]);
            $message = "Lab test type updated successfully!";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle Delete Lab Test Type (soft delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_test_type'])) {
    $id = $_POST['test_id'];
    try {
        $stmt = $pdo->prepare("UPDATE lab_test_types SET is_active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Lab test type deleted successfully.";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch Lab Test Types
$test_types = [];
try {
    $test_types = $pdo->query("SELECT * FROM lab_test_types WHERE is_active = 1 ORDER BY category, name")->fetchAll();
} catch (PDOException $e) {
    // Table might not exist
}

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Lab Test Types</h1>
                <p class="page-subtitle">Manage laboratory test types and pricing</p>
            </div>
            <button class="btn btn-primary" onclick="toggleModal('addTestModal')">+ Add Lab Test Type</button>
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
                <h3 class="card-title">All Lab Test Types</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Sample</th>
                            <th>Price</th>
                            <th>TAT</th>
                            <th>Fasting</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($test_types as $t): ?>
                            <tr>
                                <td><strong>
                                        <?php echo h($t['code']); ?>
                                    </strong></td>
                                <td>
                                    <?php echo h($t['name']); ?>
                                </td>
                                <td><span class="badge badge-blue">
                                        <?php echo h($t['category'] ?? 'General'); ?>
                                    </span></td>
                                <td>
                                    <?php echo h($t['sample_type'] ?? '-'); ?>
                                </td>
                                <td style="font-weight: 600;">$
                                    <?php echo number_format($t['price'], 2); ?>
                                </td>
                                <td>
                                    <?php echo h($t['turnaround_hours'] ?? 24); ?>h
                                </td>
                                <td>
                                    <?php if ($t['fasting_required'] ?? 0): ?>
                                        <span class="badge badge-yellow">Yes</span>
                                    <?php else: ?>
                                        <span class="badge badge-gray">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <button class="btn btn-sm btn-outline" onclick="openEditModal(this)"
                                            data-id="<?php echo h($t['id']); ?>" data-name="<?php echo h($t['name']); ?>"
                                            data-code="<?php echo h($t['code']); ?>"
                                            data-category="<?php echo h($t['category'] ?? ''); ?>"
                                            data-price="<?php echo $t['price']; ?>"
                                            data-sample="<?php echo h($t['sample_type'] ?? ''); ?>"
                                            data-tat="<?php echo $t['turnaround_hours'] ?? 24; ?>"
                                            data-fasting="<?php echo $t['fasting_required'] ?? 0; ?>"
                                            data-desc="<?php echo h($t['description'] ?? ''); ?>">Edit</button>
                                        <form method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this test type?');"
                                            style="display:inline;">
                                            <input type="hidden" name="delete_test_type" value="1">
                                            <input type="hidden" name="test_id" value="<?php echo $t['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline"
                                                style="color: #dc2626; border-color: #dc2626;">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($test_types)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No lab test types found. Add some to get started.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Lab Test Type Modal -->
        <div id="addTestModal" class="card" style="display: none; border: 2px solid #2563eb;">
            <div class="card-header">
                <h3 class="card-title">Add New Lab Test Type</h3>
                <button class="btn btn-sm btn-outline" onclick="toggleModal('addTestModal')">Close</button>
            </div>
            <form method="POST">
                <input type="hidden" name="create_test_type" value="1">

                <div class="section-header">TEST INFORMATION</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Test Name *</label>
                        <input type="text" name="name" class="form-input" required
                            placeholder="e.g., Complete Blood Count">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Code *</label>
                        <input type="text" name="code" class="form-input" required placeholder="e.g., CBC">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="Hematology">Hematology</option>
                            <option value="Biochemistry">Biochemistry</option>
                            <option value="Microbiology">Microbiology</option>
                            <option value="Immunology">Immunology</option>
                            <option value="Pathology">Pathology</option>
                            <option value="Radiology">Radiology</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price ($) *</label>
                        <input type="number" step="0.01" name="price" class="form-input" required placeholder="0.00">
                    </div>
                </div>

                <div class="section-header">SAMPLE & TIMING</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Sample Type</label>
                        <select name="sample_type" class="form-select">
                            <option value="Blood">Blood</option>
                            <option value="Urine">Urine</option>
                            <option value="Stool">Stool</option>
                            <option value="Swab">Swab</option>
                            <option value="Tissue">Tissue</option>
                            <option value="Sputum">Sputum</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Turnaround Time (hours)</label>
                        <input type="number" name="turnaround_hours" class="form-input" value="24" min="1">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" name="fasting_required" value="1">
                        Fasting Required
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" rows="2"
                        placeholder="Brief description..."></textarea>
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="button" class="btn btn-outline" onclick="toggleModal('addTestModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Test Type</button>
                </div>
            </form>
        </div>

        <!-- Edit Lab Test Type Modal -->
        <div id="editTestModal" class="card" style="display: none; border: 2px solid #22c55e;">
            <div class="card-header">
                <h3 class="card-title">Edit Lab Test Type</h3>
                <button class="btn btn-sm btn-outline" onclick="toggleModal('editTestModal')">Close</button>
            </div>
            <form method="POST">
                <input type="hidden" name="update_test_type" value="1">
                <input type="hidden" name="test_id" id="edit_test_id">

                <div class="section-header">TEST INFORMATION</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Test Name *</label>
                        <input type="text" name="name" id="edit_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Code *</label>
                        <input type="text" name="code" id="edit_code" class="form-input" required>
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" id="edit_category" class="form-select">
                            <option value="Hematology">Hematology</option>
                            <option value="Biochemistry">Biochemistry</option>
                            <option value="Microbiology">Microbiology</option>
                            <option value="Immunology">Immunology</option>
                            <option value="Pathology">Pathology</option>
                            <option value="Radiology">Radiology</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price ($) *</label>
                        <input type="number" step="0.01" name="price" id="edit_price" class="form-input" required>
                    </div>
                </div>

                <div class="section-header">SAMPLE & TIMING</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Sample Type</label>
                        <select name="sample_type" id="edit_sample" class="form-select">
                            <option value="Blood">Blood</option>
                            <option value="Urine">Urine</option>
                            <option value="Stool">Stool</option>
                            <option value="Swab">Swab</option>
                            <option value="Tissue">Tissue</option>
                            <option value="Sputum">Sputum</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Turnaround Time (hours)</label>
                        <input type="number" name="turnaround_hours" id="edit_tat" class="form-input" min="1">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" name="fasting_required" id="edit_fasting" value="1">
                        Fasting Required
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_desc" class="form-textarea" rows="2"></textarea>
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="button" class="btn btn-outline" onclick="toggleModal('editTestModal')">Cancel</button>
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
        document.getElementById('edit_test_id').value = btn.dataset.id;
        document.getElementById('edit_name').value = btn.dataset.name;
        document.getElementById('edit_code').value = btn.dataset.code;
        document.getElementById('edit_category').value = btn.dataset.category || 'Other';
        document.getElementById('edit_price').value = btn.dataset.price;
        document.getElementById('edit_sample').value = btn.dataset.sample || 'Blood';
        document.getElementById('edit_tat').value = btn.dataset.tat || 24;
        document.getElementById('edit_fasting').checked = btn.dataset.fasting == '1';
        document.getElementById('edit_desc').value = btn.dataset.desc || '';

        var modal = document.getElementById('editTestModal');
        modal.style.display = 'block';
        modal.scrollIntoView({ behavior: "smooth" });
    }
</script>

<?php include '../includes/footer.php'; ?>