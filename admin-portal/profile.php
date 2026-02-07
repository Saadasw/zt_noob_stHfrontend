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
// Handle Profile Image Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    require_once '../includes/ProfileImageHandler.php';
    $imageHandler = new ProfileImageHandler($pdo);

    $result = $imageHandler->uploadProfileImage($userId, $_FILES['profile_image']);

    if ($result['success']) {
        $message = $result['message'];
    } else {
        $error = $result['message'];
    }
}

// Get Profile Image
require_once '../includes/ProfileImageHandler.php';
$imageHandler = new ProfileImageHandler($pdo);
$profileImage = $imageHandler->getProfileImage($userId);
?>

<div class="main-content">
    <?php include '../includes/navbar_admin.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Profile</h1>
                <p class="page-subtitle">View and update your profile information</p>
            </div>
            <div class="flex gap-2">
                <button class="btn btn-outline" onclick="toggleModal('editProfileModal')">Edit Details</button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="flex gap-4 items-center mb-4">
                <div class="relative group">
                    <?php if ($profileImage): ?>
                        <div
                            style="width: 100px; height: 100px; border-radius: 50%; overflow: hidden; border: 3px solid #2563eb;">
                            <img src="../<?php echo h($profileImage); ?>" alt="Profile"
                                style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    <?php else: ?>
                        <div
                            style="width: 100px; height: 100px; background: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 36px; font-weight: bold;">
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
                    <p class="text-gray">User ID: <?php echo h($user['id']); ?></p>
                    <p class="text-gray">Role: <span
                            class="badge badge-blue"><?php echo h(ucfirst($user['role'])); ?></span></p>
                    <p class="text-gray"><?php echo h($user['email']); ?></p>
                    <p class="text-gray"><?php echo h($user['phone'] ?? 'Phone not set'); ?></p>
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
                    <input type="password" name="current_password" class="form-input"
                        placeholder="Enter current password" required>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-input" placeholder="Enter new password"
                        required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-input" placeholder="Confirm new password"
                        required>
                </div>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
        </div>

        <!-- Edit Profile Modal -->
        <div id="editProfileModal" class="card"
            style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000; width: 90%; max-width: 500px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border: 1px solid #e5e7eb;">
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
                    <input type="email" class="form-input" value="<?php echo h($user['email']); ?>" disabled
                        style="background-color: #f3f4f6; cursor: not-allowed;">
                </div>

                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-input" value="<?php echo h($user['phone'] ?? ''); ?>">
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="button" class="btn btn-outline"
                        onclick="toggleModal('editProfileModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
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
        document.getElementById('editProfileModal').style.display = 'none';
        document.getElementById('uploadImageModal').style.display = 'none';
        document.getElementById('modalOverlay').style.display = 'none';
    }
</script>

<?php include '../includes/footer.php'; ?>