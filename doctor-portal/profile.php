<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'My Profile - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'profile';

include '../includes/header.php';
include '../includes/sidebar_doctor.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_doctor.php'; ?>

    <main class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">My Profile</h1>
                <p class="page-subtitle">View and update your profile information</p>
            </div>
            <button class="btn btn-outline">Edit</button>
        </div>

        <div class="card">
            <div class="flex gap-4 items-center mb-4">
                <div
                    style="width: 80px; height: 80px; background: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 28px; font-weight: bold;">
                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 2)); ?></div>
                <div>
                    <h2 style="font-size: 20px;"><?php echo h($_SESSION['user_name']); ?></h2>
                    <p class="text-gray"><?php echo isset($_SESSION['user_role']) ? ucfirst(h($_SESSION['user_role'])) : 'Doctor'; ?> | Melbourne CBD Branch</p>
                    <p class="text-sm text-gray">Employee ID: <?php echo h($_SESSION['user_id']); ?></p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Professional Information</h3>
            </div>
            <div class="profile-grid">
                <div class="profile-item"><label>Medical License</label>
                    <p>MED-VIC-12345</p>
                </div>
                <div class="profile-item"><label>License Expiry</label>
                    <p>30 June 2026</p>
                </div>
                <div class="profile-item"><label>Specialization</label>
                    <p>General Practice / Family Medicine</p>
                </div>
                <div class="profile-item"><label>Experience</label>
                    <p>15 years</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Working Hours</h3>
            </div>
            <div class="profile-grid">
                <div class="profile-item"><label>Monday</label>
                    <p>09:00 AM - 05:00 PM</p>
                </div>
                <div class="profile-item"><label>Tuesday</label>
                    <p>09:00 AM - 05:00 PM</p>
                </div>
                <div class="profile-item"><label>Wednesday</label>
                    <p>Day Off</p>
                </div>
                <div class="profile-item"><label>Thursday</label>
                    <p>09:00 AM - 05:00 PM</p>
                </div>
                <div class="profile-item"><label>Friday</label>
                    <p>09:00 AM - 05:00 PM</p>
                </div>
                <div class="profile-item"><label>Saturday</label>
                    <p>Day Off</p>
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
                    <label class="form-label">Confirm Password</label>
                    <input type="password" class="form-input" placeholder="Confirm new password">
                </div>
                <button class="btn btn-primary">Update Password</button>
            </form>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
