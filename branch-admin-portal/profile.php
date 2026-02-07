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

// Handle Profile Image Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
require_once '../includes/ProfileImageHandler.php';
$imageHandler = new ProfileImageHandler($pdo);

$result = $imageHandler->uploadProfileImage($_SESSION['user_id'], $_FILES['profile_image']);

if ($result['success']) {
$message = $result['message'];
} else {
$error = $result['message'];
}
}

// Get Profile Image
require_once '../includes/ProfileImageHandler.php';
$imageHandler = new ProfileImageHandler($pdo);
$profileImage = $imageHandler->getProfileImage($_SESSION['user_id']);
?>

<div class="main-content">
    <?php include '../includes/navbar_branch_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Profile</h1>
                <p class="page-subtitle">View and manage your account</p>
            </div>
            <button class="btn btn-outline" onclick="toggleModal('uploadImageModal')">Update Photo</button>
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

        <!-- Profile Header with Image -->
        <div class="card mb-4" style="margin-bottom: 20px;">
            <div class="flex gap-4 items-center">
                <div class="relative group">
                    <?php if ($profileImage): ?>
                        <div
                            style="width: 100px; height: 100px; border-radius: 50%; overflow: hidden; border: 3px solid #0891b2;">
                            <img src="../<?php echo h($profileImage); ?>" alt="Profile"
                                style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php else: ?>
                        <div
                            style="width: 100px; height: 100px; background: #0891b2; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 36px; font-weight: bold;">
                            <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                        </div>
                    <?php endif; ?>

                    <button onclick="toggleModal('uploadImageModal')"
                        style="position: absolute; bottom: 0; right: 0; background: white; border: 1px solid #ccc; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center;"
                        title="Update Profile Picture">
                        📷
                    </button>
                </div>
                <div>
                    <h2 style="font-size: 24px; font-weight: 600;"><?php echo h($user['name']); ?></h2>
                    <p class="text-gray"><?php echo h($user['designation']); ?> | <?php echo h($user['branch_name']); ?>
                    </p>
                    <p class="text-gray text-sm">Employee ID: <?php echo h($user['employee_id']); ?></p>
                </div>
            </div>
        </div>

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

        <!-- Upload Image Modal -->
        <div id="uploadImageModal" class="card"
            style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000; width: 90%; max-width: 400px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb;">
            <div class="card-header">
                <h3 class="card-title">Update Profile Picture</h3>
                <button class="btn btn-sm btn-outline" onclick="toggleModal('uploadImageModal')">Close</button>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Select Image (JPG, PNG, GIF)</label>
                    <input type="file" name="profile_image" class="form-input" accept="image/*" required>
                    <p class="text-sm text-gray mt-1">Max size: 5MB</p>
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="button" class="btn btn-outline"
                        onclick="toggleModal('uploadImageModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>

        <!-- Modal Overlay -->
        <div id="modalOverlay"
            style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999;"
            onclick="closeAllModals()"></div>

    </main>
</div>

<script>
    function toggleModal(id) {
        var el = document.getElementById(id);
        var overlay = document.getElementById('modalOverlay');

        if (el.style.display === 'none') {
            el.style.display = 'block';
            overlay.style.display = 'block';
        } else {
            el.style.display = 'none';
            overlay.style.display = 'none';
        }
    }

    function closeAllModals() {
        document.getElementById('uploadImageModal').style.display = 'none';
        document.getElementById('modalOverlay').style.display = 'none';
    }
</script>

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