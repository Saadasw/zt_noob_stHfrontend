<?php
require '../includes/auth_session.php';
require '../config/db_connect.php';
require_role(['patient']);

$page_title = 'Patient Dashboard - St. George Hospital';
$page_css = 'css/patient-portal.css';
$current_page = 'dashboard';

include '../includes/header.php';
include '../includes/sidebar_patient.php';
?>

<div class="main-content">
    <?php include '../includes/navbar_patient.php'; ?>

    <main class="page-content">
        <div class="welcome-banner">
            <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <p class="welcome-subtitle">Patient Portal</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">📅</div>
                <div class="stat-label">Upcoming Appointments</div>
                <div class="stat-value">0</div>
                <a href="appointments.php" class="stat-link">View All →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">🔬</div>
                <div class="stat-label">Lab Results Ready</div>
                <div class="stat-value">0</div>
                <a href="lab-results.php" class="stat-link">View Results →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">💊</div>
                <div class="stat-label">Active Prescriptions</div>
                <div class="stat-value">0</div>
                <a href="prescriptions.php" class="stat-link">View All →</a>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">💳</div>
                <div class="stat-label">Outstanding Bills</div>
                <div class="stat-value">$0.00</div>
                <a href="billing.php" class="stat-link">Pay Now →</a>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
