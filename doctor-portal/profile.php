<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'My Profile - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'profile';

$message = '';
$error = '';

// Get doctor profile data
$stmt = $pdo->prepare("
    SELECT dp.*, u.name, u.email, u.phone, b.name as branch_name 
    FROM doctor_profiles dp 
    JOIN users u ON dp.user_id = u.id 
    LEFT JOIN branches b ON dp.branch_id = b.id
    WHERE dp.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch();

// Get working hours from doctor_weekly_schedules
$weekly_schedule = [];
if ($doctor) {
    $stmt = $pdo->prepare("SELECT * FROM doctor_weekly_schedules WHERE doctor_id = ? ORDER BY day_of_week");
    $stmt->execute([$doctor['id']]);
    $schedule_rows = $stmt->fetchAll();
    foreach ($schedule_rows as $row) {
        $weekly_schedule[$row['day_of_week']] = $row;
    }
}

// Handle Password Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirmation do not match.";
    } elseif (strlen($new_password) < 3) {
        $error = "New password must be at least 3 characters.";
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!password_verify($current_password, $user['password'])) {
            $error = "Current password is incorrect.";
        } else {
            // Update password
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
include '../includes/sidebar_doctor.php';

// Day names helper
$day_names = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
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
    <?php include '../includes/navbar_doctor.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Profile</h1>
                <p class="page-subtitle">View and update your profile information</p>
            </div>
            <button class="btn btn-outline" onclick="toggleModal('uploadImageModal')">Update Photo</button>
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
                            <?php echo strtoupper(substr($_SESSION['user_name'], 0, 2)); ?>
                        </div>
                    <?php endif; ?>

                    <button onclick="toggleModal('uploadImageModal')"
                        style="position: absolute; bottom: 0; right: 0; background: white; border: 1px solid #ccc; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center;"
                        title="Update Profile Picture">
                        📷
                    </button>
                </div>

                <div>
                    <h2 style="font-size: 20px;"><?php echo h($_SESSION['user_name']); ?></h2>
                    <p class="text-gray"><?php echo ucfirst(h($_SESSION['user_role'])); ?> |
                        <?php echo h($doctor['branch_name'] ?? 'N/A'); ?>
                    </p>
                    <p class="text-sm text-gray">Employee ID: <?php echo h($_SESSION['user_id']); ?></p>
                </div>
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

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Professional Information</h3>
            </div>
            <div class="profile-grid">
                <div class="profile-item">
                    <label>Medical License</label>
                    <p><?php echo h($doctor['license_number'] ?? 'N/A'); ?></p>
                </div>
                <div class="profile-item">
                    <label>Email</label>
                    <p><?php echo h($doctor['email'] ?? 'N/A'); ?></p>
                </div>
                <div class="profile-item">
                    <label>Specialization</label>
                    <p><?php echo h($doctor['specialization'] ?? 'N/A'); ?></p>
                </div>
                <div class="profile-item">
                    <label>Consultation Fee</label>
                    <p>$<?php echo number_format($doctor['consultation_fee'] ?? 0, 2); ?></p>
                </div>
                <div class="profile-item">
                    <label>Phone</label>
                    <p><?php echo h($doctor['phone'] ?? 'N/A'); ?></p>
                </div>
                <div class="profile-item">
                    <label>Slot Duration</label>
                    <p><?php echo ($doctor['slot_duration'] ?? 30); ?> minutes</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Working Hours</h3>
            </div>
            <?php if (empty($weekly_schedule)): ?>
                <p class="text-gray">No schedule configured. Please contact administrator.</p>
            <?php else: ?>
                <div class="profile-grid">
                    <?php for ($i = 0; $i <= 6; $i++): ?>
                        <div class="profile-item">
                            <label><?php echo $day_names[$i]; ?></label>
                            <?php if (isset($weekly_schedule[$i]) && $weekly_schedule[$i]['is_active']): ?>
                                <p><?php echo date('h:i A', strtotime($weekly_schedule[$i]['start_time'])); ?> -
                                    <?php echo date('h:i A', strtotime($weekly_schedule[$i]['end_time'])); ?>
                                </p>
                            <?php else: ?>
                                <p class="text-gray">Day Off</p>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>

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
                    <label class="form-label">Confirm Password *</label>
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