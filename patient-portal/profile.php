<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['patient']);

$page_title = 'My Profile - St. George Hospital';
$page_css = 'css/patient-portal.css';
$current_page = 'profile';

include '../includes/header.php';
include '../includes/sidebar_patient.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_patient.php'; ?>

    <main class="page-content">
        <div class="tabs mb-4">
            <button class="tab active">Personal Info</button>
            <button class="tab">Emergency Contact</button>
            <button class="tab">Change Password</button>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Personal Information</h3>
                <button class="btn btn-outline btn-sm">Edit</button>
            </div>

            <div class="profile-section">
                <div class="profile-grid">
                    <div class="profile-item">
                        <label>Full Name</label>
                        <p><?php echo h($_SESSION['user_name']); ?></p>
                    </div>
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
