<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['admin']);

$page_title = 'Service Charges - St. George Hospital';
$page_css = 'css/admin-portal.css';
$current_page = 'services';

$message = '';
$error = '';

// Handle Create Service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_service'])) {
    $name = trim($_POST['name']);
    $code = trim($_POST['code']);
    $category = $_POST['category'];
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);

    if (empty($name) || empty($code) || $price <= 0) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $id = 'SVC-' . bin2hex(random_bytes(4));
            $stmt = $pdo->prepare("INSERT INTO services (id, name, code, category, price, description, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$id, $name, $code, $category, $price, $description]);
            $message = "Service added successfully!";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                // Create services table
                $pdo->exec("CREATE TABLE IF NOT EXISTS services (
                    id VARCHAR(255) PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    code VARCHAR(50) UNIQUE NOT NULL,
                    category VARCHAR(100),
                    price FLOAT NOT NULL DEFAULT 0,
                    description TEXT,
                    is_active TINYINT(1) DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                $stmt = $pdo->prepare("INSERT INTO services (id, name, code, category, price, description, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([$id, $name, $code, $category, $price, $description]);
                $message = "Services table created and service added!";
            } else {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// Handle Update Price
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_price'])) {
    $service_id = $_POST['service_id'];
    $new_price = floatval($_POST['new_price']);
    try {
        $stmt = $pdo->prepare("UPDATE services SET price = ? WHERE id = ?");
        $stmt->execute([$new_price, $service_id]);
        $message = "Price updated successfully!";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch services
$services = [];
try {
    $services = $pdo->query("SELECT * FROM services WHERE is_active = 1 ORDER BY category, name")->fetchAll();
} catch (PDOException $e) {
    // Table might not exist yet
}

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Service Charges</h1>
                <p class="page-subtitle">Manage service pricing and charges</p>
            </div>
            <button class="btn btn-primary" onclick="toggleModal('addServiceModal')">+ Add Service</button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Current Service Charges</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Service Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $s): ?>
                        <tr>
                            <td><strong><?php echo h($s['code']); ?></strong></td>
                            <td><?php echo h($s['name']); ?></td>
                            <td><span class="badge badge-gray"><?php echo h($s['category']); ?></span></td>
                            <td style="font-weight: 600;">$<?php echo number_format($s['price'], 2); ?></td>
                            <td>
                                <form method="POST" style="display: flex; gap: 4px; align-items: center;">
                                    <input type="hidden" name="update_price" value="1">
                                    <input type="hidden" name="service_id" value="<?php echo $s['id']; ?>">
                                    <input type="number" step="0.01" name="new_price" value="<?php echo $s['price']; ?>" class="form-input" style="width: 100px;">
                                    <button type="submit" class="btn btn-sm btn-outline">Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($services)): ?>
                        <tr><td colspan="5" class="text-center">No services found. Add some to get started.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Service Modal -->
        <div id="addServiceModal" class="card" style="display: none; border: 2px solid #2563eb;">
            <div class="card-header">
                <h3 class="card-title">Add New Service</h3>
                <button class="btn btn-sm btn-outline" onclick="toggleModal('addServiceModal')">Close</button>
            </div>
            <form method="POST">
                <input type="hidden" name="create_service" value="1">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Service Name *</label>
                        <input type="text" name="name" class="form-input" required placeholder="e.g., General Consultation">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Code *</label>
                        <input type="text" name="code" class="form-input" required placeholder="e.g., CONS-GEN">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="Consultation">Consultation</option>
                            <option value="Laboratory">Laboratory</option>
                            <option value="Radiology">Radiology</option>
                            <option value="Procedure">Procedure</option>
                            <option value="Room">Room Charges</option>
                            <option value="Pharmacy">Pharmacy</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price ($) *</label>
                        <input type="number" step="0.01" name="price" class="form-input" required placeholder="0.00">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" rows="2" placeholder="Brief description..."></textarea>
                </div>
                <div class="flex gap-2 mt-4">
                    <button type="button" class="btn btn-outline" onclick="toggleModal('addServiceModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Service</button>
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
