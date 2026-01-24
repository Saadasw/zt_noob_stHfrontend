<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['doctor']);

$page_title = 'Doctor Dashboard - St. George Hospital';
$page_css = 'css/doctor-portal.css';
$current_page = 'dashboard';

// Fetch Doctor Stats (Placeholder logic until we have appointments tables populated)
$today_appointments = 0;
$waiting_patients = 0;
$completed_today = 0;

include '../includes/header.php';
include '../includes/sidebar_doctor.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_doctor.php'; ?>

    <main class="page-content">
        <div class="welcome-banner">
            <h1 class="welcome-title">Good Morning, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <p class="welcome-subtitle">General Medicine | Melbourne CBD Branch</p>
            <p class="welcome-meta">Employee ID: EMP-00045 | License: MED-VIC-12345</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">📅</div>
                <div class="stat-label">Today's Appointments</div>
                <div class="stat-value"><?php echo $today_appointments; ?></div>
                <a href="appointments.php" class="stat-link">View Schedule →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">⏳</div>
                <div class="stat-label">Waiting Patients</div>
                <div class="stat-value"><?php echo $waiting_patients; ?></div>
                <a href="appointments.php" class="stat-link">View Queue →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">✅</div>
                <div class="stat-label">Completed Today</div>
                <div class="stat-value"><?php echo $completed_today; ?></div>
                <a href="appointments.php" class="stat-link">View All →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">📋</div>
                <div class="stat-label">Pending Lab Reviews</div>
                <div class="stat-value">0</div>
                <a href="lab-results.php" class="stat-link">Review Now →</a>
            </div>
        </div>

        <!-- Placeholder for rest of content -->
        <div class="card">
            <p class="text-center text-gray">Schedule and other modules are under construction.</p>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
