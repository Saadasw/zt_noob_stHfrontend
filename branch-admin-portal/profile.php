<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['branch_admin']);

$page_title = 'My Profile';
$page_css = 'css/branch-admin-portal.css';
$current_page = 'profile';

$branch_id = $_SESSION['branch_admin_branch_id'] ?? null;
$message = '';
$error = '';

// Get user and profile info
$stmt = $pdo->prepare("SELECT u.*, bap.employee_id, bap.designation, b.name as branch_name 
                       FROM users u 
                       JOIN branch_admin_profiles bap ON u.id = bap.user_id 
                       JOIN branches b ON bap.branch_id = b.id
                       WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (empty($current) || empty($new) || empty($confirm)) {
        $error = "All password fields are required.";
    } elseif (!password_verify($current, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    } elseif (strlen($new) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $_SESSION['user_id']]);
        $message = "Password updated successfully!";
    }
}

include '../includes/header.php';
include '../includes/sidebar_branch_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_branch_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Profile</h1>
                <p class="page-subtitle">View and manage your account</p>
            </div>
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

        <div class="grid-2">
            <!-- Profile Info -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">👤 Profile Information</h3>
                </div>
                <div class="profile-grid">
                    <div class="profile-item">
                        <label>Full Name</label>
                        <p>
                            <?php echo h($user['name']); ?>
                        </p>
                    </div>
                    <div class="profile-item">
                        <label>Email</label>
                        <p>
                            <?php echo h($user['email']); ?>
                        </p>
                    </div>
                    <div class="profile-item">
                        <label>Phone</label>
                        <p>
                            <?php echo h($user['phone'] ?? 'N/A'); ?>
                        </p>
                    </div>
                    <div class="profile-item">
                        <label>Employee ID</label>
                        <p>
                            <?php echo h($user['employee_id']); ?>
                        </p>
                    </div>
                    <div class="profile-item">
                        <label>Designation</label>
                        <p>
                            <?php echo h($user['designation']); ?>
                        </p>
                    </div>
                    <div class="profile-item">
                        <label>Branch</label>
                        <p>
                            <?php echo h($user['branch_name']); ?>
                        </p>
                    </div>
                    <div class="profile-item">
                        <label>Role</label>
                        <p><span class="badge badge-blue">Branch Admin</span></p>
                    </div>
                </div>
            </div>

            <!-- Password Change -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🔒 Change Password</h3>
                </div>
                <form method="POST">
                    <input type="hidden" name="change_password" value="1">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-input" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-input" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </form>
            </div>
        </div>
    </main>
</div>

<style>
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

<?php include '../includes/footer.php'; ?>