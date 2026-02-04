<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['branch_admin']);

$page_title = 'Branch Doctors';
$page_css = 'css/branch-admin-portal.css';
$current_page = 'doctors';

$branch_id = $_SESSION['branch_admin_branch_id'] ?? null;
if (!$branch_id) {
    die("Branch not assigned.");
}

$message = '';
$error = '';

// Handle Add Doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_doctor'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization']);
    $license = trim($_POST['license_number']);
    $fee = floatval($_POST['consultation_fee']);
    $password = password_hash('doctor123', PASSWORD_DEFAULT);

    try {
        $user_id = 'DOC-' . bin2hex(random_bytes(4));
        $profile_id = 'DP-' . bin2hex(random_bytes(4));

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO users (id, email, password, name, role, phone) VALUES (?, ?, ?, ?, 'doctor', ?)");
        $stmt->execute([$user_id, $email, $password, $name, $phone]);

        $stmt = $pdo->prepare("INSERT INTO doctor_profiles (id, user_id, branch_id, specialization, license_number, consultation_fee) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$profile_id, $user_id, $branch_id, $specialization, $license, $fee]);

        $pdo->commit();
        $message = "Doctor added successfully! Default password: doctor123";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch doctors for THIS BRANCH only
$stmt = $pdo->prepare("
    SELECT dp.*, u.name, u.email, u.phone, u.is_active
    FROM doctor_profiles dp
    JOIN users u ON dp.user_id = u.id
    WHERE dp.branch_id = ? AND dp.deleted_at IS NULL
    ORDER BY u.name
");
$stmt->execute([$branch_id]);
$doctors = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar_branch_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_branch_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Branch Doctors</h1>
                <p class="page-subtitle">Manage doctors at your branch</p>
            </div>
            <button class="btn btn-primary" onclick="toggleModal('addDoctorModal')">+ Add Doctor</button>
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
                <h3 class="card-title">👨‍⚕️ Doctors (
                    <?php echo count($doctors); ?>)
                </h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Specialization</th>
                            <th>License</th>
                            <th>Fee</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($doctors as $d): ?>
                            <tr>
                                <td>
                                    <strong>Dr.
                                        <?php echo h($d['name']); ?>
                                    </strong>
                                    <div class="text-sm text-gray">
                                        <?php echo h($d['email']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo h($d['specialization']); ?>
                                </td>
                                <td>
                                    <?php echo h($d['license_number']); ?>
                                </td>
                                <td>$
                                    <?php echo number_format($d['consultation_fee'], 2); ?>
                                </td>
                                <td>
                                    <?php if ($d['is_active'] && $d['is_available']): ?>
                                        <span class="badge badge-green">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-gray">Inactive</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($doctors)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-gray">No doctors found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Add Doctor Modal -->
<div id="addDoctorModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Doctor</h3>
            <button onclick="toggleModal('addDoctorModal')" class="btn btn-outline btn-sm">×</button>
        </div>
        <form method="POST">
            <input type="hidden" name="add_doctor" value="1">
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
                    <label class="form-label">Specialization *</label>
                    <input type="text" name="specialization" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">License Number *</label>
                    <input type="text" name="license_number" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Consultation Fee ($)</label>
                    <input type="number" name="consultation_fee" class="form-input" value="150" step="0.01">
                </div>
            </div>
            <p class="text-sm text-gray mb-4">Default password will be: doctor123</p>
            <div class="flex gap-2">
                <button type="button" onclick="toggleModal('addDoctorModal')" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Doctor</button>
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
        max-height: 90vh;
        overflow-y: auto;
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