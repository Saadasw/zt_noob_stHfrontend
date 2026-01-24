<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['staff']);

$page_title = 'My Profile - St. George Hospital';
$page_css = 'css/staff-portal.css';
$current_page = 'profile';

include '../includes/header.php';
include '../includes/sidebar_staff.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_staff.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Profile</h1>
                <p class="page-subtitle">View and update your profile information</p>
            </div>
        </div>

        <div class="card">
            <div class="flex gap-4 items-center mb-4">
                <div
                    style="width: 80px; height: 80px; background: #059669; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 28px; font-weight: bold;">
                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 2)); ?></div>
                <div>
                    <h2 style="font-size: 20px;"><?php echo h($_SESSION['user_name']); ?></h2>
                    <p class="text-gray">Staff ID: <?php echo h($_SESSION['user_id']); ?></p>
                    <p class="text-gray">Role: <?php echo h($_SESSION['user_role']); ?></p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Personal Information</h3>
            </div>
            <div class="profile-grid">
                <div class="profile-item"><label>Full Name</label>
                    <p><?php echo h($_SESSION['user_name']); ?></p>
                </div>
                <div class="profile-item"><label>Email</label>
                    <p><?php echo h($_SESSION['user_email'] ?? 'jane.smith@stgeorgehospital.com.au'); ?></p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Change Password</h3>
            </div>
            <form style="max-width: 400px;">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" class="form-input" placeholder="Enter current password">
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" class="form-input" placeholder="Enter new password">
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" class="form-input" placeholder="Confirm new password">
                </div>
                <button class="btn btn-primary">Update Password</button>
            </form>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
