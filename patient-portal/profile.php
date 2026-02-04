<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['patient']);

$page_title = 'My Profile - St. George Hospital';
$page_css = 'css/patient-portal.css';
$current_page = 'profile';

$message = '';
$error = '';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get patient profile data
$stmt = $pdo->prepare("SELECT * FROM patient_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();

// Handle Password Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirmation do not match.";
    } elseif (strlen($new_password) < 3) {
        $error = "New password must be at least 3 characters.";
    } else {
        if (!password_verify($current_password, $user['password'])) {
            $error = "Current password is incorrect.";
        } else {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                $message = "Password updated successfully!";
            } catch (PDOException $e) {
                $error = "Error updating password: " . $e->getMessage();
            }
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar_patient.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_patient.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Profile</h1>
                <p class="page-subtitle">View and manage your profile information</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Personal Information</h3>
            </div>
            <div class="profile-grid">
                <div class="profile-item">
                    <label>Full Name</label>
                    <p><?php echo h($user['name'] ?? $_SESSION['user_name']); ?></p>
                </div>
                <div class="profile-item">
                    <label>Email</label>
                    <p><?php echo h($user['email'] ?? 'N/A'); ?></p>
                </div>
                <div class="profile-item">
                    <label>Phone</label>
                    <p><?php echo h($user['phone'] ?? 'Not set'); ?></p>
                </div>
                <div class="profile-item">
                    <label>Patient ID</label>
                    <p><?php echo h($patient['patient_id'] ?? 'N/A'); ?></p>
                </div>
                <?php if ($patient): ?>
                    <div class="profile-item">
                        <label>Date of Birth</label>
                        <p><?php echo $patient['date_of_birth'] ? date('d M Y', strtotime($patient['date_of_birth'])) : 'N/A'; ?>
                        </p>
                    </div>
                    <div class="profile-item">
                        <label>Blood Group</label>
                        <p><?php echo h($patient['blood_group'] ?? 'N/A'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($patient && $patient['emergency_contact_name']): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Emergency Contact</h3>
                </div>
                <div class="profile-grid">
                    <div class="profile-item">
                        <label>Contact Name</label>
                        <p><?php echo h($patient['emergency_contact_name']); ?></p>
                    </div>
                    <div class="profile-item">
                        <label>Contact Phone</label>
                        <p><?php echo h($patient['emergency_contact_phone'] ?? 'N/A'); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Change Password</h3>
            </div>
            <form method="POST" style="max-width: 400px;">
                <input type="hidden" name="update_password" value="1">
                <div class="form-group">
                    <label class="form-label">Current Password *</label>
                    <input type="password" name="current_password" class="form-input"
                        placeholder="Enter current password" required>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password *</label>
                    <input type="password" name="new_password" class="form-input" placeholder="Enter new password"
                        required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password *</label>
                    <input type="password" name="confirm_password" class="form-input" placeholder="Confirm new password"
                        required>
                </div>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
        </div>
    </main>
</div>

<style>
    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 16px;
    }

    .alert-success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
</style>

<?php include '../includes/footer.php'; ?>