<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['admin']);

$page_title = 'My Profile - St. George Hospital';
$page_css = 'css/admin-portal.css';
$current_page = 'profile';

$message = '';
$error = '';

$userId = $_SESSION['user_id'];

// Fetch latest user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);

    if (empty($name)) {
        $error = "Name is required.";
    } else {
        try {
            $sql = "UPDATE users SET name = ?, phone = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $phone, $userId]);
            
            // Update Session
            $_SESSION['user_name'] = $name;
            
            $message = "Profile updated successfully.";
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (empty($current) || empty($new) || empty($confirm)) {
        $error = "All password fields are required.";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    } else {
        // Verify current password
        if (password_verify($current, $user['password'])) {
            try {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $userId]);
                $message = "Password updated successfully.";
            } catch (PDOException $e) {
                $error = "Error updating password: " . $e->getMessage();
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Profile</h1>
                <p class="page-subtitle">View and update your profile information</p>
            </div>
            <button class="btn btn-outline" onclick="toggleModal('editProfileModal')">Edit</button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="flex gap-4 items-center mb-4">
                <div
                    style="width: 80px; height: 80px; background: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 28px; font-weight: bold;">
                    <?php echo strtoupper(substr($user['name'], 0, 2)); ?></div>
                <div>
                    <h2 style="font-size: 20px;"><?php echo h($user['name']); ?></h2>
                    <p class="text-gray">User ID: <?php echo h($user['id']); ?></p>
                    <p class="text-gray">Role: <?php echo h(ucfirst($user['role'])); ?></p>
                    <p class="text-gray">Email: <?php echo h($user['email']); ?></p>
                    <p class="text-gray">Phone: <?php echo h($user['phone'] ?? 'Not set'); ?></p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Account Info</h3>
            </div>
            <div class="profile-grid">
                <div class="profile-item"><label>Last Login</label>
                    <p><?php echo date('d F Y, h:i A'); ?></p>
                </div>
                <div class="profile-item"><label>Account Created</label>
                    <p><?php echo date('d F Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Change Password</h3>
            </div>
            <form method="POST" style="max-width: 400px;">
                <input type="hidden" name="change_password" value="1">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-input" placeholder="Enter current password" required>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-input" placeholder="Enter new password" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-input" placeholder="Confirm new password" required>
                </div>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
        </div>

        <!-- Edit Profile Modal -->
        <div id="editProfileModal" class="card" style="display: none; border: 2px solid #2563eb; margin-top: 20px;">
            <div class="card-header">
                <h3 class="card-title">Edit Profile</h3>
                <button class="btn btn-sm btn-outline" onclick="toggleModal('editProfileModal')">Close</button>
            </div>

            <form method="POST">
                <input type="hidden" name="update_profile" value="1">
                
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-input" value="<?php echo h($user['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email (Managed by Admin)</label>
                    <input type="email" class="form-input" value="<?php echo h($user['email']); ?>" disabled style="background-color: #f3f4f6; cursor: not-allowed;">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-input" value="<?php echo h($user['phone'] ?? ''); ?>">
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="button" class="btn btn-outline" onclick="toggleModal('editProfileModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
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
            el.scrollIntoView({ behavior: "smooth" });
        } else {
            el.style.display = 'none';
        }
    }
</script>

<?php include '../includes/footer.php'; ?>
