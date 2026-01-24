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
    $postcode = trim($_POST['postcode']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    
    if (empty($name) || empty($code) || empty($address)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $id = 'BR-' . strtoupper(substr(uniqid(), -5)); // Simple ID
            
            $sql = "INSERT INTO branches (id, name, code, address, city, state, postcode, phone, email, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id, $name, $code, $address, $city, $state, $postcode, $phone, $email]);

            $message = "Branch created successfully!";
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch Branches
$stmt = $pdo->query("SELECT * FROM branches ORDER BY name ASC");
$branches = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Branch Management</h1>
                <p class="page-subtitle">Manage hospital branches across locations</p>
            </div>
            <button class="btn btn-primary" onclick="toggleModal('addBranchModal')">+ Add New Branch</button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <!-- Branch List -->
        <?php foreach ($branches as $branch): ?>
        <div class="branch-card">
            <div class="branch-header">
                <div>
                    <div class="branch-name">🏥 <?php echo h($branch['name']); ?></div>
                    <div class="branch-meta">Branch ID: <?php echo h($branch['code']); ?></div>
                </div>
                <?php echo $branch['is_active'] ? '<span class="badge badge-green">🟢 Active</span>' : '<span class="badge badge-red">🔴 Inactive</span>'; ?>
            </div>
            <p class="text-sm"><strong>Address:</strong> <?php echo h($branch['address'] . ', ' . $branch['city'] . ' ' . $branch['state'] . ' ' . $branch['postcode']); ?></p>
            <p class="text-sm"><strong>Phone:</strong> <?php echo h($branch['phone']); ?></p>
            <p class="text-sm"><strong>Email:</strong> <?php echo h($branch['email']); ?></p>
            
            <div class="flex gap-2 mt-4">
                <button class="btn btn-sm btn-outline">View Details</button>
                <button class="btn btn-sm btn-outline">Edit</button>
                <button class="btn btn-sm btn-outline">Manage Staff</button>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($branches)): ?>
            <div class="card"><p class="text-center">No branches found.</p></div>
        <?php endif; ?>

        <p class="text-sm text-gray">Total Branches: <?php echo count($branches); ?></p>

        <!-- Add Branch Modal -->
        <div id="addBranchModal" class="card" style="display: none; border: 2px solid #2563eb;">
            <div class="card-header">
                <h3 class="card-title">Add New Branch</h3>
                <button class="btn btn-sm btn-outline" onclick="toggleModal('addBranchModal')">Close</button>
            </div>

            <form method="POST">
                <input type="hidden" name="create_branch" value="1">
                
                <div class="section-header">BRANCH INFORMATION</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Branch Name *</label>
                        <input type="text" name="name" class="form-input" required placeholder="e.g., Perth CBD">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Branch Code *</label>
                        <input type="text" name="code" class="form-input" required placeholder="e.g., PERTH">
                    </div>
                </div>

                <div class="section-header">CONTACT INFORMATION</div>
                <div class="form-group">
                    <label class="form-label">Address *</label>
                    <input type="text" name="address" class="form-input" required placeholder="Street address">
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label class="form-label">City *</label>
                        <input type="text" name="city" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">State *</label>
                        <select name="state" class="form-select">
                            <option>VIC</option>
                            <option>NSW</option>
                            <option>QLD</option>
                            <option>WA</option>
                            <option>SA</option>
                            <option>TAS</option>
                            <option>ACT</option>
                            <option>NT</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Postcode *</label>
                        <input type="text" name="postcode" class="form-input" required>
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Phone *</label>
                        <input type="tel" name="phone" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" required>
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="button" class="btn btn-outline" onclick="toggleModal('addBranchModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Branch</button>
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
