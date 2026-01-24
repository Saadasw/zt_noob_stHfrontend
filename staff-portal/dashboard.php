<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
// Allow staff and admin to view staff portal for testing? No, stick to role.
require_role(['staff']);

$page_title = 'Staff Dashboard - St. George Hospital';
$page_css = 'css/staff-portal.css';
$current_page = 'dashboard';

include '../includes/header.php';
include '../includes/sidebar_staff.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_staff.php'; ?>

    <main class="page-content">
        <div class="welcome-banner">
            <h1 class="welcome-title">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <p class="welcome-subtitle">Staff ID: STF-00012 | Role: General Staff | <?php echo date('l, d F Y'); ?></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">📅</div>
                <div class="stat-label">Today's Appointments</div>
                <div class="stat-value">0</div>
                <a href="appointments.php" class="stat-link">View All →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">⏳</div>
                <div class="stat-label">Waiting Patients</div>
                <div class="stat-value">0</div>
                <a href="appointments.php" class="stat-link">View Queue →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">🔬</div>
                <div class="stat-label">Pending Lab Results</div>
                <div class="stat-value">0</div>
                <a href="laboratory.php" class="stat-link">Enter Results →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">💊</div>
                <div class="stat-label">Pending Prescriptions</div>
                <div class="stat-value">0</div>
                <a href="pharmacy.php" class="stat-link">Dispense →</a>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
